# 📝 Instruções de Uso e Credenciais de Acesso
## Sistema Financeiro - Cantina Escolar

Este arquivo contém as instruções rápidas de inicialização, credenciais padrões de testes e guias operacionais para o funcionamento do sistema financeiro.

---

## 🔑 Credenciais Padrões (Primeiro Acesso)

O sistema foi programado para criar automaticamente dois perfis iniciais na primeira execução do banco de dados SQLite (`/db/cantina.sqlite`).

### 💼 1. Perfil Gerente (Acesso Financeiro Completo)
Responsável pelo fluxo de caixa da cantina, lançamentos de contas e gestão de usuários.
* **Nome de Usuário:** `gerente`
* **Senha padrão:** `gerente123`
* **Páginas de Acesso:** Dashboard (`index.php`), Contas (`contas.php`), Usuários (`usuarios.php`).

### 🛠️ 2. Perfil Administrador (Infraestrutura de TI)
Responsável pelo setup técnico, backups, chaves de API e logs de segurança.
* **Nome de Usuário:** `admin`
* **Senha padrão:** `admin123`
* **Páginas de Acesso:** Console Administrativo (`admin.php`), Logs de Auditoria, SMTP/MailGrid e Backups.

> [!WARNING]
> Por questões de segurança, altere as senhas padrões de ambos os perfis no primeiro acesso!

---

## 🚀 Como Executar Localmente (Laragon / Apache)

1. Mantenha os arquivos na raiz do seu servidor web no Laragon (pasta `www/rmg-erp-php`).
2. Inicie os serviços no painel do Laragon.
3. Acesse a URL no seu navegador: `http://localhost/rmg-erp-php/` (o sistema irá redirecionar automaticamente para a página de login).
4. Insira as credenciais acima para navegar pelos painéis.

---

## 🕐 Alertas Diários e Backups Automáticos (Cron)

O script diário consolida as contas em atraso, vencendo hoje e futuras, enviando alertas por e-mail e fazendo backup diário automatizado em ZIP (ou fallback em `.sqlite` se a extensão zip não estiver ativa).

* **URL de execução:** `http://localhost/rmg-erp-php/cron_daily_notifications_4fb9e2.php`
* **Execução Programada:** Configure um agendador de tarefas (Crontab no Linux ou Task Scheduler no Windows) para disparar uma requisição HTTP ou rodar o script PHP local diariamente.

---

## 📁 Estrutura de Diretórios Protegidos

* `/db/`: Contém o banco SQLite (`cantina.sqlite`). Protegido por `.htaccess` contra downloads via web.
* `/backups/`: Armazena os históricos de cópias de segurança compactadas ou diretas do SQLite. Protegido por `.htaccess`.
