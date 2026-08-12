# Finances

Aplicação web para organização financeira pessoal ou em equipe. O projeto permite centralizar contas, categorias, transações e lançamentos programados, além de importar extratos bancários no formato OFX.

## Funcionalidades

- Autenticação com login, cadastro, verificação de e-mail, recuperação de senha e 2FA.
- Suporte a passkeys.
- Times (teams) com troca de contexto, convites e permissões por função.
- Gestão de:
  - contas financeiras;
  - categorias;
  - programações/lançamentos recorrentes;
  - transações.
- Tabelas Livewire com busca, ordenação, paginação, seleção e exportação.
- Importação de arquivos `.ofx` com revisão dos dados antes da confirmação.
- Interface responsiva com tema claro/escuro.

## Stack

- PHP 8.3+
- Laravel 13
- Livewire 4
- Flux UI 2
- Tailwind CSS 4
- Laravel Fortify
- SQLite por padrão
- Pest 4, Larastan e Laravel Pint

## Requisitos

- PHP 8.3 ou superior
- Composer
- Node.js e npm
- SQLite ou outro banco compatível com Laravel

## Instalação

```bash
git clone <url-do-repositorio>
cd finances
composer run setup
```

O comando `setup` instala as dependências, cria o arquivo `.env` quando necessário, gera a chave da aplicação, executa as migrations, instala os pacotes JavaScript e compila os assets.

Para carregar dados de demonstração:

```bash
php artisan db:seed
```

O seeder cria um usuário de teste (`test@example.com`) e dados de exemplo para contas, categorias, programações e transações.

## Desenvolvimento

Configure o arquivo `.env` conforme o ambiente. O projeto usa SQLite por padrão:

```env
APP_NAME=Finances
APP_ENV=local
APP_DEBUG=true
DB_CONNECTION=sqlite
```

Para recompilar os assets automaticamente durante o desenvolvimento:

```bash
npm run dev
```

Em ambientes com Laravel Herd, abra o domínio local configurado para o diretório do projeto. Em outros ambientes, utilize o processo de desenvolvimento definido pelo projeto:

```bash
composer run dev
```

## Testes e qualidade

Executar a suíte de testes:

```bash
php artisan test --compact
```

Verificar estilo e análise estática:

```bash
composer run lint
composer run types:check
```

## Estrutura principal

```text
app/
├── Actions/       # Casos de uso, incluindo autenticação e times
├── Http/          # Middleware, respostas e configurações HTTP
├── Livewire/      # Componentes Livewire reutilizáveis
├── Models/        # Modelos Eloquent
├── Notifications/ # Notificações
└── Policies/      # Autorização

database/
├── factories/     # Factories para testes e seeders
├── migrations/    # Estrutura do banco de dados
└── seeders/       # Dados de demonstração

resources/views/
├── pages/         # Páginas Livewire/Volt
├── components/    # Componentes Blade
└── layouts/       # Layouts da aplicação
```

## Rotas principais

As páginas autenticadas usam o slug do time atual na URL:

- `/dashboard`
- `/accounts`
- `/categories`
- `/schedules`
- `/transactions`
- `/imports`
