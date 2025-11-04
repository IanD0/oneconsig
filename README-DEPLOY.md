# Deploy no Easypanel

## Pré-requisitos
- Conta no Easypanel
- Banco de dados MySQL configurado

## Passos para Deploy

### 1. Configurar Banco de Dados
No Easypanel, crie um serviço MySQL ou use um banco externo.

### 2. Importar Estrutura do Banco
Execute os scripts SQL:
- `usuarios.sql` - Tabela de usuários
- `ROBERTA (3).sql` - Tabela entrantes

### 3. Criar Aplicação no Easypanel
1. Acesse seu projeto no Easypanel
2. Clique em "Create Service" > "App"
3. Escolha "From Source Code"
4. Conecte seu repositório Git ou faça upload dos arquivos

### 4. Configurar Variáveis de Ambiente
No Easypanel, adicione as seguintes variáveis:

```
DB_HOST=seu_host_mysql
DB_PORT=3306
DB_NAME=ROBERTA
DB_USER=seu_usuario
DB_PASS=sua_senha
```

### 5. Configurar Build
- Build Method: Dockerfile
- Dockerfile Path: ./Dockerfile
- Port: 80

### 6. Deploy
Clique em "Deploy" e aguarde o build completar.

## Criar Primeiro Usuário
Após o deploy, acesse via SSH ou execute:

```bash
docker exec -it seu_container php /var/www/html/criar_usuario.php
```

Ou crie manualmente no banco:
```sql
INSERT INTO usuarios (usuario, senha, nome, ativo) 
VALUES ('admin', '$2y$10$hash_aqui', 'Administrador', 1);
```

Use `gerar_senha.php` para gerar o hash da senha.

## Estrutura de Arquivos
```
.
├── Dockerfile              # Container da aplicação
├── nginx.conf             # Configuração do Nginx
├── supervisord.conf       # Gerenciador de processos
├── .env                   # Variáveis de ambiente (não commitar)
├── .env.example          # Exemplo de configuração
├── conexao.php           # Conexão com banco
├── index.php             # Página principal
├── login.php             # Autenticação
└── uploads/              # Arquivos CSV importados
```

## Troubleshooting

### Erro de conexão com banco
Verifique se as variáveis DB_HOST, DB_PORT, DB_USER e DB_PASS estão corretas.

### Erro de permissão em uploads/
Execute: `chmod 777 uploads/`

### Página em branco
Verifique os logs: `docker logs seu_container`
