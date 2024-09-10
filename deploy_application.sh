#!/bin/bash

set -e  # Termina o script em caso de erro em qualquer comando

REPOSITORY_PATH=~/repositories/API-RESTFUL-LARAVEL
IMAGE_NAME="laravel-octane"
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
docker tag $IMAGE_NAME_TAG $IMAGE_NAME_LATEST


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

# Checa se o parâmetro --sync foi passado
if [[ " $@ " =~ " --sync " ]]; then
    echo "Modo de sincronização selecionado: Exportando e sincronizando imagem Docker..."
    
    # Exportar e sincronizar a imagem Docker para os servidores remotos
    echo "Exportando a imagem Docker local..."
    docker save -o "$IMAGE_PATH/$OUTPUT_FILE" "$IMAGE_NAME_TAG" || { echo "Erro: Falha ao exportar a imagem Docker."; exit 1; }

    # Loop pelos servidores para transferir e carregar a imagem
    for SERVER in "${SERVERS[@]}"; do
        echo "Transferindo a imagem Docker para o servidor remoto: $SERVER"
        ssh "$SERVER" "mkdir -p $REMOTE_PATH" || { echo "Erro: Não foi possível criar o diretório $REMOTE_PATH no servidor $SERVER."; exit 1; }
        scp "$IMAGE_PATH/$OUTPUT_FILE" "$SERVER:$REMOTE_PATH" || { echo "Erro: Falha ao transferir o arquivo tar para $SERVER."; exit 1; }
        ssh "$SERVER" << EOF
            set -e
            docker images --filter "dangling=true" --format "{{.Repository}}:{{.Tag}}" | grep "$IMAGE_NAME" | xargs -r docker rmi || { echo "Erro: Falha ao remover imagens Docker."; exit 1; }
            docker load -i "$REMOTE_PATH/$OUTPUT_FILE" || { echo "Erro ao carregar a imagem Docker no servidor remoto."; exit 1; }
            rm "$REMOTE_PATH/$OUTPUT_FILE" || { echo "Erro ao remover o arquivo temporário no servidor."; exit 1; }
EOF
    done

    echo "Sincronização concluída com sucesso."

    echo "Removendo o arquivo tar local..."
    rm "$IMAGE_PATH/$OUTPUT_FILE" || { echo "Erro: Falha ao remover o arquivo tar local."; exit 1; }
fi

# Opcional: Remover a chave SSH do agente
echo "Removendo a chave SSH do agente..."
ssh-add -d $SSH_KEY_PATH || { echo "Erro: Falha ao remover a chave SSH do agente."; exit 1; }

echo "Processo concluído com sucesso."
