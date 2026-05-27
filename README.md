# Cantina Sant'Anna ERP

Sistema de gerenciamento financeiro e operacional para cantinas escolares. PHP puro com SQLite, sem dependências externas (Composer/npm). Inclui dashboard com gráficos, cadastro de contas a pagar/receber, calendário financeiro, relatórios com PDF, gestão de patrimônio, planejamento de cardápios via IA e notificações por email.

## Funcionalidades

- **Dashboard** — KPIs em tempo real, gráfico de fluxo de caixa (6 meses), gráfico de despesas por categoria, alertas de contas vencidas
- **Contas a Pagar/Receber** — CRUD completo com filtros avançados, toggle rápido de status (pago/pendente), máscara monetária BRL
- **Calendário Financeiro** — Visualização mensal com navegação entre meses, eventos coloridos (vermelho=pagar, verde=receber)
- **Relatórios** — Filtros completos, KPIs resumidos, tabelas detalhadas, exportação PDF (A4 paisagem)
- **Cadastros** — Categorias e fornecedores com funcionalidade de mesclagem de duplicatas
- **Patrimônio** — Gestão de bens e manutenções, integração financeira ao concluir manutenção, ficha técnica com PDF
- **Gestão de Usuários** — CRUD de usuários com níveis de acesso (admin, gerente, operador)
- **Cardápio IA** — Geração automática de cardápio semanal via OpenRouter (Gemini), edição manual, impressão PDF em A4 paisagem
- **Painel Admin** — Configuração de APIs (MailGrid, OpenRouter), backups, logs de auditoria
- **Notificações por Email** — Relatório diário automático de contas vencidas, a vencer e manutenções programadas

## Tech Stack

| Camada | Tecnologia |
|--------|------------|
| **Linguagem** | PHP 8.0+ (namespaces, typed properties, union types) |
| **Framework** | Nenhum — PHP vanilla com namespace `CantinaFinanceiro` |
| **Banco de Dados** | SQLite 3 via PDO (`db/cantina.sqlite`) |
| **Servidor Web** | Apache (Laragon no Windows; qualquer LAMP) |
| **CSS** | Bootstrap 5.3.0, Font Awesome 6.4.0, Google Fonts "Outfit", CSS customizado (glassmorphism, tema Warm Luxury) |
| **JavaScript** | Bootstrap 5.3.0 Bundle, Chart.js, html2pdf.js 0.10.1, vanilla JS com módulos por página |
| **IA** | OpenRouter API (modelo padrão: `google/gemini-2.5-flash`) |
| **Email** | MailGrid API (API brasileira) |

## Pré-requisitos

- PHP 8.0 ou superior
- Extensões PHP: PDO SQLite, cURL, ZipArchive
- Servidor Apache com `mod_rewrite` (Laragon recomendado no Windows)
- Navegador web moderno

## Instalação

### 1. Clonar o Repositório

```bash
git clone https://github.com/rmgimenez/rmg-erp-php.git
cd rmg-erp-php
```

### 2. Configurar o Servidor Web

Se usando **Laragon**:
- Coloque a pasta do projeto em `D:\laragon\www\`
- Inicie o Apache pelo painel do Laragon

Se usando outro servidor Apache:
- Configure um VirtualHost apontando para a pasta do projeto
- Certifique-se que `mod_rewrite` está habilitado

### 3. Acessar o Sistema

```
http://localhost/rmg-erp-php/
```

O sistema **cria automaticamente** o banco de dados `db/cantina.sqlite` com todas as tabelas e usuários padrão no primeiro acesso.

### 4. Credenciais Padrão

| Perfil | Usuário | Senha | Acessa |
|--------|---------|-------|--------|
| Admin | `admin` | `admin123` | `admin.php` |
| Gerente | `gerente` | `gerente123` | `index.php` |
| Nutricionista | `nutricionista` | `nutricionista123` | `cardapios.php` |

> **Altere as senhas imediatamente** após o primeiro login via `usuarios.php`.

### 5. Configurar APIs (Opcional)

Acesse `admin.php` (somente admin) para configurar:

- **MailGrid** — Chave de API para envio de emails de notificação
- **OpenRouter** — Chave de API para geração de cardápios via IA

## Estrutura do Projeto

```
rmg-erp-php/
├── assets/
│   ├── css/
│   │   ├── cardapios.css           # CSS específico do módulo de cardápios IA
│   │   └── style.css              # CSS customizado (Warm Luxury, glassmorphism, sidebar, KPIs)
│   └── js/
│       ├── dashboard.js           # Gráficos Chart.js (fluxo de caixa + categorias)
│       ├── cardapios.js           # Lógica JS do módulo de cardápios IA (14 funções)
│       ├── toast.js               # Utilitário compartilhado de notificações toast
│       └── utils.js               # Utilitários JS compartilhados
├── backups/                        # Backups do banco (protegidos por .htaccess)
│   └── .htaccess                   # Negar acesso HTTP
├── db/
│   ├── .htaccess                   # Negar acesso HTTP
│   └── cantina.sqlite              # Banco SQLite (auto-criado)
├── docs/
│   ├── INSTRUCOES.md               # Instruções rápidas
│   ├── documentacao_sistema_financeiro.md  # Documentação técnica completa
│   └── planejamento_cardapio_ia.md # Design do módulo de cardápio IA
├── src/
│   ├── includes/
│   │   ├── layout_start.php        # Layout wrapper (abertura HTML, CSS/JS, sidebar, mobile header)
│   │   ├── sidebar.php             # Menu lateral Warm Luxury (dark com acentos dourados)
│   │   ├── mobile_header.php       # Cabeçalho mobile escuro
│   │   ├── alerts.php              # Alertas/flash messages compartilhados
│   │   └── scripts_footer.php      # Scripts JS compartilhados no final do body
│   ├── Database.php                # Conexão PDO Singleton, schema, seeds, migrações
│   ├── Auth.php                    # Sessões, login/logout, RBAC, auditoria
│   ├── AccountModel.php            # CRUD de contas, KPIs, generator para relatórios
│   ├── EmailService.php            # Envio de emails via MailGrid API
│   ├── BackupService.php           # Backup/restore SQLite (ZIP ou cópia direta)
│   └── MenuModel.php               # CRUD de cardápios, integração OpenRouter IA
├── index.php                       # Dashboard (KPIs, gráficos, alertas)
├── login.php                       # Página de login
├── logout.php                      # Destruir sessão
├── admin.php                       # Painel admin (config, backups, logs)
├── contas.php                      # Contas a pagar/receber
├── calendario.php                  # Calendário financeiro
├── relatorios.php                  # Relatórios com PDF
├── cadastros.php                   # Categorias e fornecedores
├── patrimonio.php                  # Bens e manutenções
├── usuarios.php                    # Gestão de usuários
├── cardapios.php                   # Planejamento de cardápio IA
├── cardapio_action.php             # Controller AJAX para cardápios
└── cron_daily_notifications_4fb9e2.php  # Cron diário (backup + email)
```

## Arquitetura

### Padrões Utilizados

- **Namespace:** `CantinaFinanceiro` para todas as classes fonte
- **Singleton:** `Database::getConnection()` garante uma única instância PDO
- **Métodos Estáticos:** Todos os modelos usam métodos estáticos (sem injeção de dependência)
- **Shared Includes:** Toda página inclui `layout_start.php` que carrega sidebar, mobile header, alerts e scripts — eliminando duplicação de HTML
- **Page Controllers:** Cada arquivo PHP é um controller autônomo (lógica + HTML)
- **AJAX:** Módulo de cardápios usa `fetch()` com respostas JSON via `cardapio_action.php`
- **JS Modular:** Código JS específico de página em arquivos separados (`assets/js/pagina.js`), com dados PHP passados via atributos `data-*`

### Ciclo de Requisição

```
1. Usuário acessa uma página (ex: contas.php)
2. Page controller faz `require_once` das classes necessárias
3. Verifica autenticação e nível de acesso (Auth::checkAuth / Auth::restrictTo)
4. Inclui `layout_start.php` que renderiza <head>, CSS/JS, sidebar e mobile header
5. Executa queries via Database::getConnection()
6. Renderiza HTML com dados do banco entre sidebar e scripts
7. Inclui `scripts_footer.php` para JS compartilhados no final do body
```

### Fluxo de Dados

```
Navegador → PHP (page controller) → Database (PDO/SQLite) → PHP renderiza HTML → Navegador
                                        ↑
                              Model classes (Auth, AccountModel, etc.)
```

## Banco de Dados

### Tabelas

| Tabela | Descrição |
|--------|-----------|
| `usuarios` | Usuários do sistema (admin, gerente, operador) |
| `categorias` | Categorias de despesas/receitas |
| `fornecedores` | Fornecedores/credores |
| `contas` | Contas a pagar e receber (valores em centavos) |
| `bens` | Bens/patrimônio da cantina |
| `manutencoes` | Manutenções programadas/realizadas |
| `configuracoes` | Configurações do sistema (chave-valor) |
| `backups` | Histórico de backups |
| `logs` | Auditoria de ações |
| `cardapios_semanais` | Cardápios semanais gerados |
| `cardapios_dias` | Itens diários de cada cardápio |

### Valores Monetários

Todos os valores monetários são armazenados como **INTEGER (centavos)** para evitar erros de ponto flutuante:

- Entrada do usuário: `R$ 1.250,50`
- Conversão para centavos: `125050`
- Exibição: `number_format($valor / 100, 2, ',', '.')`

### Migrações Dinâmicas

O sistema executa `ALTER TABLE` silenciosamente no primeiro acesso para adicionar colunas novas:

```php
@$db->exec("ALTER TABLE bens ADD COLUMN data_baixa DATE;");
@$db->exec("ALTER TABLE cardapios_semanais ADD COLUMN cardapio_markdown TEXT;");
@$db->exec("ALTER TABLE cardapios_semanais ADD COLUMN observacao_impressao TEXT;");
```

## Controle de Acesso (RBAC)

| Página/Ação | admin | gerente | operador | nutricionista |
|-------------|-------|---------|----------|---------------|
| Login | Sim | Sim | Sim | Sim |
| Dashboard | Não | Sim | Sim | Não |
| Contas - Visualizar | Não | Sim | Sim | Não |
| Contas - Criar | Não | Sim | Sim | Não |
| Contas - Editar/Excluir | Não | Sim | Não | Não |
| Calendário | Não | Sim | Sim | Não |
| Relatórios | Não | Sim | Sim | Não |
| Cadastros - Visualizar | Não | Sim | Sim | Não |
| Cadastros - Editar/Excluir/Mesclar | Não | Sim | Não | Não |
| Patrimônio - Visualizar/Criar/Editar | Não | Sim | Sim | Não |
| Patrimônio - Excluir | Não | Sim | Não | Não |
| Usuários | Não | Sim | Não | Não |
| Cardápio IA | Sim | Sim | Sim | Sim |
| Painel Admin | Sim | Não | Não | Não |

## Cardápio IA

### Geração

1. Clique em "Planejar Nova Semana"
2. Selecione a data de início (segunda-feira)
3. Escolha o tipo de refeição: Almoço, Lanche ou Ambos
4. Adicione observações opcionais
5. Clique em "Gerar via IA"
6. Aguarde 8-15 segundos para a IA processar

### Edição

- **Editar Dia:** Clique no botão "Editar" de cada dia na tabela
- **Editar Lista de Compras:** Clique em "Editar Markdown" → aba "Lista"
- **Editar Markdown Completo:** Clique em "Editar Markdown" para visão split-pane

### Impressão

- **Imprimir Cardápio:** Gera PDF A4 paisagem com dias nas colunas e datas
- **Imprimir Lista:** Gera PDF A4 paisagem da lista de compras
- **Observação para Impressão:** Campo de texto que aparece no PDF

## Segurança

### Sessões
- Cookies `httponly` (inacessíveis ao JavaScript)
- `samesite=Strict` (previne CSRF)
- `secure` quando HTTPS detectado

### Senhas
- Hash com `password_hash(PASSWORD_DEFAULT)` (BCrypt)
- Verificação com `password_verify()`

### SQL Injection
- Todas as queries usam prepared statements com placeholders (`?`)

### XSS
- Todo output escapado com `htmlspecialchars($data, ENT_QUOTES, 'UTF-8')`

### Proteção de Diretórios
- `db/.htaccess`: Negar acesso HTTP ao banco SQLite
- `backups/.htaccess`: Negar acesso HTTP aos backups

## Cron Job (Notificações Diárias)

### Configuração no Windows (Task Scheduler)

1. Abra o Agendador de Tarefas
2. Crie uma nova tarefa
3. Configurações:
   - **Nome:** Cantina ERP - Notificações Diárias
   - **Gatilho:** Diariamente às 06:00
   - **Ação:** Iniciar programa
   - **Programa:** `C:\laragon\bin\php\php-8.x\php.exe`
   - **Argumentos:** `-f D:\laragon\www\rmg-erp-php\cron_daily_notifications_4fb9e2.php`

### Configuração no Linux (crontab)

```bash
0 6 * * * curl -s http://localhost/rmg-erp-php/cron_daily_notifications_4fb9e2.php > /dev/null 2>&1
```

### O que o Cron faz

1. Cria backup automático do banco
2. Consulta contas vencidas, a vencer hoje e próximas
3. Consulta manutenções programadas
4. Monta email HTML estilizado
5. Envia via MailGrid para destinatários configurados
6. Registra resultado na auditoria

## Backup

### Backup Manual

Acesse `admin.php` → Seção "Backups" → Clique em "Criar Backup Manual"

### Backup Automático

Executado diariamente pelo cron job. Armazenado em `backups/`.

### Restauração

Acesse `admin.php` → Seção "Backups" → Clique em "Restaurar" no backup desejado

### Download

Acesse `admin.php` → Seção "Backups" → Clique em "Download" para baixar o arquivo ZIP/SQLite

## Variáveis de Configuração

Todas as configurações são armazenadas na tabela `configuracoes` (banco SQLite) e podem ser editadas via `admin.php`.

| Chave | Valor Padrão | Descrição |
|-------|-------------|-----------|
| `mailgrid_api_url` | `https://www.mailgrid.com.br/api` | Endpoint da API MailGrid |
| `mailgrid_api_key` | *(vazio)* | Chave de API MailGrid |
| `email_remetente` | `financeiro@santanna.com.br` | Email do remetente |
| `nome_remetente` | `Financeiro Cantina Sant'Anna` | Nome do remetente |
| `emails_destinatarios` | `direcao@santanna.com.br` | Emails destinatários (separados por vírgula) |
| `dias_alerta_vencimento` | `3` | Dias de antecedência para alertas |
| `openrouter_api_key` | *(vazio)* | Chave de API OpenRouter |
| `openrouter_model` | `google/gemini-2.5-flash` | Modelo de IA |
| `cardapio_pessoas_estimadas` | `130 alunos do ensino medio, 30 funcionarios` | Público estimado |
| `cardapio_contexto_global` | `Cantina escolar...` | Contexto global para prompt da IA |

## Scripts Disponíveis

| Comando | Descrição |
|---------|-----------|
| Acessar `http://localhost/rmg-erp-php/` | Dashboard principal |
| Acessar `http://localhost/rmg-erp-php/admin.php` | Painel administrativo |
| Acessar `http://localhost/rmg-erp-php/cardapios.php` | Planejamento de cardápio IA |
| Acessar `http://localhost/rmg-erp-php/cron_daily_notifications_4fb9e2.php` | Executar cron manualmente |

## Solução de Problemas

### Erro de Conexão com Banco

**Erro:** `SQLSTATE[HY000] unable to open database file`

**Solução:**
1. Verifique se a pasta `db/` existe e tem permissão de escrita
2. No Windows, verifique se o Apache tem permissão para gravar em `D:\laragon\www\rmg-erp-php\db\`

### Tabelas Não Criadas

**Erro:** `no such table: contas`

**Solução:**
1. Delete o arquivo `db/cantina.sqlite`
2. Acesse qualquer página do sistema
3. O banco será recriado automaticamente com todas as tabelas

### Login Não Funciona

**Erro:** Credenciais padrão não funcionam

**Solução:**
1. Delete `db/cantina.sqlite` para recriar com os usuários padrão
2. Ou acesse `admin.php` (se tiver acesso) e crie um novo usuário

### IA Não Gera Cardápio

**Erro:** `Configuração ausente: Por favor, configure a chave API do OpenRouter`

**Solução:**
1. Acesse `admin.php`
2. Na seção "Configuração da IA", insira sua chave de API do OpenRouter
3. Obtenha em: https://openrouter.ai/keys

### Email Não Envia

**Erro:** Notificações diárias não chegam

**Solução:**
1. Verifique se a chave da MailGrid está configurada em `admin.php`
2. Execute o cron manualmente: acesse `cron_daily_notifications_4fb9e2.php` no navegador
3. Verifique os logs de auditoria em `admin.php`

## Licença

Este é um projeto privado. Todos os direitos reservados.
