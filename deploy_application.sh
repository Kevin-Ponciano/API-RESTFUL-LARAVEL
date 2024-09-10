#!/bin/bash

set -e  # Termina o script em caso de erro em qualquer comando

# Verifica se o número mínimo de argumentos foi passado
if [ $# -lt 5 ]; then
    echo "Erro: Parâmetros insuficientes."
    echo "Uso: $0 <image_name> <stack_name> <service_name> <servers> <ssh_key_path> [repository_path] [branch]"
    echo "Parâmetros obrigatórios:"
    echo "  image_name       Nome da imagem Docker"
    echo "  stack_name       Nome do stack Docker"
    echo "  service_name     Nome do serviço Docker"
    echo "  servers          Lista de servidores (formato: usuario@servidor) separados por vírgula"
    echo "  ssh_key_path     Caminho para a chave SSH para autenticação no repositório Git"
    echo "Parâmetros opcionais:"
    echo "  repository_path  Caminho para o repositório (padrão: diretório atual)"
    echo "  branch           Branch do repositório Git (padrão: main)"
    exit 1
fi

# Parâmetros obrigatórios
IMAGE_NAME="$1"
STACK_NAME="$2"
SERVICE_NAME="$3"
IFS=',' read -r -a SERVERS <<< "$4"  # Converte a lista de servidores em um array
SSH_KEY_PATH="$5"

# Parâmetros opcionais
REPOSITORY_PATH="${6:-$(pwd)}"  # Se não passar, pega o path atual
BRANCH="${7:-main}"  # Se não passar, assume "main"

# Exibe as variáveis recebidas
echo "Variáveis configuradas:"
echo "  IMAGE_NAME: $IMAGE_NAME"
echo "  STACK_NAME: $STACK_NAME"
echo "  SERVICE_NAME: $SERVICE_NAME"
echo "  SERVERS: ${SERVERS[*]}"
echo "  SSH_KEY_PATH: $SSH_KEY_PATH"
echo "  REPOSITORY_PATH: $REPOSITORY_PATH"
echo "  BRANCH: $BRANCH"

# Variável para armazenar erros
ERRORS=""

# Adicionar a chave SSH ao agente SSH
echo "Adicionando a chave SSH ao agente SSH..."
eval "$(ssh-agent -s)"
ssh-add $SSH_KEY_PATH || { echo "Erro: Falha ao adicionar a chave SSH ao agente SSH."; exit 1; }

# Acessa o diretório do repositório
cd $REPOSITORY_PATH || { echo "Erro: Não foi possível acessar o diretório $REPOSITORY_PATH"; exit 1; }

# Atualizar o repositório git
echo "Atualizando o repositório git..."
git fetch && git checkout $BRANCH && git pull || { echo "Erro: Falha ao atualizar o repositório git."; exit 1; }

# Obter a última tag do repositório
echo "Obtendo a última tag do repositório..."
VERSION_BUILD=$(git tag | grep -E '^v[0-9]+' | tail -n 1)
if [ -z "$VERSION_BUILD" ]; then
  echo "Erro: Nenhuma tag de versão encontrada. Cancelando pipeline."
  exit 1
else
  echo "Tag de versão encontrada: $VERSION_BUILD"
fi

IMAGE_NAME_TAG="$IMAGE_NAME:$VERSION_BUILD"
IMAGE_NAME_LATEST="$IMAGE_NAME:latest"
OUTPUT_FILE="$IMAGE_NAME_TAG.tar"
IMAGE_PATH=~/docker-images
REMOTE_PATH=docker-images

# Sempre executar o build da imagem
echo "Removendo imagens Docker locais não utilizadas relacionadas a $IMAGE_NAME..."
docker images --filter "dangling=true" --format "{{.Repository}}:{{.Tag}}" | grep "$IMAGE_NAME" | xargs -r docker rmi || { echo "Erro: Falha ao remover imagens Docker locais não utilizadas."; exit 1; }

# Construir a imagem Docker
echo "Construindo a imagem Docker..."
docker build -t $IMAGE_NAME_TAG . || { echo "Erro: Falha ao construir a imagem Docker."; exit 1; }
echo "Imagem Docker construída com sucesso."
docker tag $IMAGE_NAME_TAG $IMAGE_NAME_LATEST || { echo "Erro: Falha ao adicionar a tag latest à imagem Docker."; exit 1; }
echo "Tag latest adicionada à imagem Docker."


# Checa se o parâmetro --new foi passado
if [[ " $@ " =~ " --new " ]]; then
    echo "Modo de criação de nova Stack selecionado: Removendo e recriando a Stack..."

    # Remover a stack existente
    echo "Removendo a stack existente..."
    docker stack rm $STACK_NAME || { echo "Erro ao remover a Stack."; exit 1; }
    
    sleep 5

    # Criar a nova stack
    echo "Criando nova Stack..."
    docker stack deploy -c docker-compose.yml $STACK_NAME || { echo "Erro ao criar a nova Stack."; exit 1; }

    echo "Nova Stack criada com sucesso."
else
    # Atualizar o serviço Docker com a nova imagem
    echo "Atualizando o serviço Docker..."
    docker service update --force --image $IMAGE_NAME_TAG $STACK_NAME"_"$SERVICE_NAME || { echo "Erro: Falha ao atualizar o serviço Docker."; exit 1; }
fi

# Função para processar cada servidor
process_server() {
    local SERVER=$1

    echo "Processando o servidor: $SERVER"
    
    ssh "$SERVER" "mkdir -p $REMOTE_PATH" || { ERRORS="$ERRORS\nFalha ao criar diretório em $SERVER"; return; }

    # Verifica se a imagem com a tag já existe no servidor e remove
    IMAGE_EXISTS=$(ssh "$SERVER" "docker images -q $IMAGE_NAME_TAG")
    if [ -n "$IMAGE_EXISTS" ]; then
        echo "A imagem $IMAGE_NAME_TAG já existe no servidor $SERVER. Removendo..."
        ssh "$SERVER" "docker rmi -f $IMAGE_NAME_TAG" || { ERRORS="$ERRORS\nFalha ao remover imagem em $SERVER"; return; }
    fi

    # Transferir a imagem Docker
    echo "Transferindo a imagem Docker para o servidor remoto: $SERVER"
    scp "$IMAGE_PATH/$OUTPUT_FILE" "$SERVER:$REMOTE_PATH" || { ERRORS="$ERRORS\nFalha ao transferir o arquivo tar para $SERVER"; return; }

    # Carregar a imagem no servidor remoto
    ssh "$SERVER" << EOF
        set -e
        docker load -i "$REMOTE_PATH/$OUTPUT_FILE" || { exit 1; }
        docker tag $IMAGE_NAME_TAG $IMAGE_NAME_LATEST || { exit 1; }
        rm "$REMOTE_PATH/$OUTPUT_FILE"
EOF
    if [ $? -ne 0 ]; then
        ERRORS="$ERRORS\nErro ao carregar a imagem no servidor $SERVER"
    else
        echo "Imagem carregada com sucesso no servidor $SERVER"
    fi
}

# Checa se o parâmetro --sync foi passado
if [[ " $@ " =~ " --sync " ]]; then
    echo "Modo de sincronização selecionado: Exportando e sincronizando imagem Docker..."
    
    # Exportar a imagem Docker local
    echo "Exportando a imagem Docker local..."
    docker save -o "$IMAGE_PATH/$OUTPUT_FILE" "$IMAGE_NAME_TAG" || { echo "Erro: Falha ao exportar a imagem Docker."; exit 1; }

    # Loop pelos servidores
    for SERVER in "${SERVERS[@]}"; do
        process_server "$SERVER" &
    done

    # Aguarda todos os processos finalizarem
    wait

    # Remove o arquivo tar local
    echo "Removendo o arquivo tar local..."
    rm "$IMAGE_PATH/$OUTPUT_FILE" || { echo "Erro: Falha ao remover o arquivo tar local."; exit 1; }
fi

# Opcional: Remover a chave SSH do agente
echo "Removendo a chave SSH do agente..."
ssh-add -d $SSH_KEY_PATH || { echo "Erro: Falha ao remover a chave SSH do agente."; exit 1; }

# Exibe erros se houver
if [ -n "$ERRORS" ]; then
    echo -e "Erros encontrados durante o processo:$ERRORS"
else
    echo "Processo concluído com sucesso."
fi
