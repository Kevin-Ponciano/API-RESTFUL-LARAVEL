#!/bin/bash

set -e  # Termina o script em caso de erro em qualquer comando

REPOSITORY_PATH=~/repositories/API-RESTFUL-LARAVEL
STACK_NAME=API-RESTFUL-LARAVEL
SERVICE_NAME=laravel-app
SERVERS=("ubuntu@autonix-02" "ubuntu@autonix-03") # Adicione os servidores aqui
SSH_KEY_PATH=~/.ssh/github_deploy
BRANCH=main

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
VERSION_BUILD=$(git tag | tail -n 1)
if [ -z "$VERSION_BUILD" ]; then
  echo "Erro: Nenhuma tag de versão encontrada. Cancelando pipeline."
  exit 1
else
  echo "Tag de versão encontrada: $VERSION_BUILD"
fi

# Definir nome da imagem Docker e caminhos
IMAGE_NAME="laravel-octane"
IMAGE_NAME_TAG="$IMAGE_NAME:$VERSION_BUILD"
OUTPUT_FILE="$IMAGE_NAME_TAG.tar"
IMAGE_PATH=~/docker-images
REMOTE_PATH=docker-images

# Remove as imagens Docker locais existentes não utilizadas relacionadas à imagem atual
echo "Removendo imagens Docker locais não utilizadas relacionadas a $IMAGE_NAME..."
docker images --filter "dangling=true" --format "{{.Repository}}:{{.Tag}}" | grep "$IMAGE_NAME" | xargs -r docker rmi || { echo "Erro: Falha ao remover imagens Docker locais não utilizadas."; exit 1; }

# Construir a imagem Docker
echo "Construindo a imagem Docker..."
docker build -t $IMAGE_NAME_TAG . || { echo "Erro: Falha ao construir a imagem Docker."; exit 1; }

# Atualizar o serviço Docker com a nova imagem
echo "Atualizando o serviço Docker..."
docker service update --force --image $IMAGE_NAME_TAG $STACK_NAME"_"$SERVICE_NAME || { echo "Erro: Falha ao atualizar o serviço Docker."; exit 1; }

# Exportar a imagem Docker local para um arquivo tar
echo "Exportando a imagem Docker local..."
docker save -o "$IMAGE_PATH/$OUTPUT_FILE" "$IMAGE_NAME_TAG" || { echo "Erro: Falha ao exportar a imagem Docker."; exit 1; }

# Loop pelos servidores
for SERVER in "${SERVERS[@]}"; do
    echo "Transferindo a imagem Docker para o servidor remoto: $SERVER"

    # Verificar se a imagem com a tag específica já existe no servidor remoto
    echo "Verificando se a imagem $IMAGE_NAME_TAG já existe no servidor remoto $SERVER..."
    if ssh "$SERVER" "docker images --format '{{.Repository}}:{{.Tag}}' | grep -q '$IMAGE_NAME_TAG'"; then
        echo "A imagem $IMAGE_NAME_TAG já existe no servidor remoto $SERVER. Pulando a transferência e o carregamento."
    else
        # Criar o diretório remoto se não existir
        ssh "$SERVER" "mkdir -p $REMOTE_PATH" || { echo "Erro: Não foi possível criar o diretório $REMOTE_PATH no servidor $SERVER."; exit 1; }
        
        # Transferir o arquivo tar para o servidor remoto via SCP
        echo "Imagem $IMAGE_NAME_TAG não encontrada no servidor $SERVER. Transferindo o arquivo .tar..."
        scp "$IMAGE_PATH/$OUTPUT_FILE" "$SERVER:$REMOTE_PATH" || { echo "Erro: Falha ao transferir o arquivo tar para $SERVER."; exit 1; }
        
        # Conectar ao servidor remoto via SSH e carregar a imagem Docker
        echo "Conectando ao servidor remoto: $SERVER para carregar a imagem Docker..."
        ssh "$SERVER" << EOF
            set -e  # Encerra a execução no servidor remoto caso algum erro ocorra
            
            # Remover as imagens Docker locais existentes não utilizadas relacionadas à imagem atual
            echo "Removendo imagens Docker locais não utilizadas relacionadas a $IMAGE_NAME..."
            docker images --filter "dangling=true" --format "{{.Repository}}:{{.Tag}}" | grep "$IMAGE_NAME" | xargs -r docker rmi || { echo "Erro: Falha ao remover imagens Docker locais não utilizadas."; exit 1; }

            echo "Carregando a imagem Docker no servidor remoto: $SERVER"
            docker load -i "$REMOTE_PATH/$OUTPUT_FILE" || { echo "Erro ao carregar a imagem Docker no servidor remoto: $SERVER"; exit 1; }
            
            echo "Removendo o arquivo temporário no servidor remoto: $SERVER"
            rm "$REMOTE_PATH/$OUTPUT_FILE" || { echo "Erro ao remover o arquivo temporário no servidor remoto: $SERVER"; exit 1; }
            echo "Arquivo temporário removido no servidor remoto: $SERVER"
EOF

        echo "Imagem carregada e arquivo removido no servidor remoto: $SERVER"
    fi
done

# Remover o arquivo tar local
echo "Removendo o arquivo tar local..."
rm "$IMAGE_PATH/$OUTPUT_FILE" || { echo "Erro: Falha ao remover o arquivo tar local."; exit 1; }

# Opcional: Remover a chave SSH do agente
echo "Removendo a chave SSH do agente..."
ssh-add -d $SSH_KEY_PATH || { echo "Erro: Falha ao remover a chave SSH do agente."; exit 1; }

echo "Processo concluído com sucesso em todos os servidores."
