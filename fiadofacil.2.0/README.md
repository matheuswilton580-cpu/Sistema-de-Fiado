# FiadoFácil — Sistema de Fiado

Sistema web em PHP e MySQL para controlar compras no fiado, pagamentos e dívidas de clientes de um comércio.

## Funcionalidades
- Login com dois perfis de funcionário: **Administrador** e **Usuário comum**
- Cadastro, edição, busca e exclusão de clientes (exclusão só do administrador)
- Registro de compras no fiado com limite de crédito (stored procedure transacional)
- Registro de pagamentos, com bloqueio de pagamento maior que a dívida
- Relatório de dívidas com filtros por período e situação
- Ranking de confiabilidade dos clientes
- Resumo financeiro (somente administrador)
- **Acesso do cliente (somente leitura):**
  - o cliente solicita um usuário pela tela de login;
  - o administrador aprova (escolhendo a qual cliente o acesso pertence) ou recusa;
  - o cliente aprovado vê **apenas a própria dívida**, compras e pagamentos, e não acessa nenhuma outra tela;
  - o administrador pode bloquear, desbloquear e trocar a senha do acesso de um cliente.

## Requisitos
- [XAMPP](https://www.apachefriends.org/) com Apache, PHP 8 e MySQL/MariaDB
- Navegador atualizado

## Como instalar
1. Copie a pasta do projeto para `C:\xampp\htdocs\fiadofacil` (o arquivo `login.php` deve ficar direto dentro dela).
2. No painel do XAMPP, ligue o **Apache** e o **MySQL**.
3. Abra `http://localhost/phpmyadmin`, vá na aba **Importar** e escolha `sql/banco.sql`.
   Atenção: esse arquivo apaga e recria o banco `sistema_fiado`.
4. Se o seu MySQL tiver senha, ajuste `conexao.php` (variáveis `$usuario` e `$senha`).
5. Acesse `http://localhost/fiadofacil/login.php`.

Já tem o banco de uma versão antiga e não quer perder dados? Rode no phpMyAdmin (aba SQL), nesta ordem, `sql/atualizar_banco.sql` e depois `sql/atualizar_banco_2.sql`.

## Contas de exemplo
| Perfil | Usuário | Senha |
|---|---|---|
| Administrador | `admin` | `admin123` |
| Usuário comum | `usuario` | `user123` |

Clientes de exemplo: João Silva `(62) 99999-9999`, Maria Souza `(62) 98888-8888`, Carlos Pereira `(62) 97777-7777`, Ana Lima `(62) 96666-6666`.

**Troque essas senhas antes de usar de verdade.** Elas são públicas neste repositório e o MySQL do XAMPP vem sem senha, o que é aceitável só para estudo no computador local.

## Como usar o acesso do cliente
1. **Cliente:** na tela de login, clica em "Sou cliente e quero ver minha dívida", informa nome, telefone cadastrado na loja, usuário e senha (mínimo 6 caracteres).
2. **Administrador:** menu → "Aprovar Acessos de Clientes", confere o telefone, escolhe o cliente correto e clica em **Aprovar**.
3. **Cliente:** entra com o usuário criado e vê a página "Minha Dívida".
4. **Administrador:** menu → "Gerenciar Acessos de Clientes" para bloquear, desbloquear ou trocar a senha.

## Estrutura
```
fiadofacil/
├── *.php          páginas do sistema
├── css/estilo.css visual
├── js/script.js   confirmação de exclusão
└── sql/           banco.sql e scripts de atualização
```

## Problemas comuns
- **404 ao abrir o login:** a pasta não está em `C:\xampp\htdocs` ou está duplicada (`fiadofacil\fiadofacil`).
- **"Access denied for user 'root'":** o MySQL tem senha; ajuste `conexao.php`.
- **MySQL não liga no XAMPP:** outro MySQL do Windows pode estar usando a porta 3306; pare o serviço dele em `services.msc`.
- **Erro "Duplicate column" ao importar:** use só o `banco.sql`; os `atualizar_banco` são apenas para bancos antigos.
