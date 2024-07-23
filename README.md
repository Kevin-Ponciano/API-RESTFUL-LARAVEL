# API REST - Laravel

## Descrição

Esta é uma pequena aplicação para criação de uma API RESTful utilizando o Laravel. A aplicação inclui autenticação JWT e
um CRUD simples de livros (Books) para exemplificar.

## Bibliotecas Utilizadas

Para auxiliar no desenvolvimento do projeto, foram utilizadas as seguintes bibliotecas externas:

- [JWT-AUTH](https://jwt-auth.readthedocs.io/en/develop/) - Para autenticação JWT
- [L5-Swagger](https://github.com/DarkaOnLine/L5-Swagger) - Para documentação da API com Swagger

## Instalação

O projeto foi desenvolvido utilizando o Laravel Sail, portanto, para rodar o projeto é necessário ter o Docker e o
Docker Compose instalados.

### Passos para Instalação

1. Clone o repositório:

    ```bash
    git clone https://github.com/Kevin-Ponciano/API-RESTFUL-LARAVEL.git
    cd API-RESTFULL
    ```

2. Instale as dependências:

   > **Nota:** A pasta `vendor` já está no repositório para facilitar a instalação. Caso queira instalar as dependências
   novamente, execute o comando abaixo:

    ```bash
    composer install
    ```

3. Copie o arquivo `.env.example` para `.env`:

    ```bash
    cp .env.example .env
    ```

4. Crie um alias para o Sail:

    ```bash
    alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'
    ```

5. Inicialize o Sail:

    ```bash
    sail up -d
    ```

6. Gere as chaves da aplicação:

    ```bash
    sail artisan key:generate # Gera a chave da aplicação
    sail artisan jwt:secret # Gera a chave do JWT
    ```

7. Execute as migrations e seeders:

    ```bash
    sail artisan migrate --seed
    ```

8. Acesse a aplicação:

   A documentação da API estará disponível em [http://localhost/api/docs](http://localhost/api/docs). Nela, você poderá
   testar a API e ver os endpoints disponíveis.

## Endpoints Disponíveis

A API oferece os seguintes endpoints para gerenciar livros:

- `GET /api/v1/books` - Lista todos os livros
- `GET /api/v1/books/{id}` - Retorna um livro específico
- `POST /api/v1/books` - Cria um novo livro
- `PUT /api/v1/books/{id}` - Atualiza um livro existente
- `DELETE /api/v1/books/{id}` - Remove um livro

## Autenticação

A autenticação é feita utilizando JWT. Para obter um token, faça login através do endpoint `/api/v1/auth/login` com as
credenciais apropriadas.

### Exemplo de Requisição de Login

```bash
POST /api/v1/auth/login
{
    "email": "test@test.com",
    "password": "123"
}
```

### Exemplo de Resposta de Login

```json
{
    "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "token_type": "bearer",
    "expires_in": 3600
}
```

## Testando a API

Você pode testar a API diretamente pela documentação Swagger em [http://localhost/api/docs](http://localhost/api/docs).
Insira o token JWT no campo apropriado para autenticar as requisições.
