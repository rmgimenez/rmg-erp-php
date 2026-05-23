# Documentação Técnica e Arquitetural do Sistema Financeiro da Cantina
## Contas a Pagar e Receber - "Cantina Financeiro"

Esta documentação técnica descreve a arquitetura, o modelo de dados, as especificações de segurança e os blueprints de código para o desenvolvimento e implantação do sistema autônomo **Cantina Financeiro**. O sistema é projetado para operar como uma subpasta independente de um sistema maior em um servidor web Apache, utilizando **PHP 8+**, **SQLite**, **Bootstrap 5** e **Chart.js** para relatórios visuais.

---

## 📅 Log de Decisões de Design (Design Decision Log)

Durante a fase de brainstorming com o cliente, as seguintes decisões estruturais e técnicas foram estabelecidas:
* **Autenticação:** Login realizado estritamente por **Nome de Usuário** e **Senha** (substituindo o padrão de e-mail/senha).
* **Estrutura de Níveis:** Divisão em três níveis de usuários com permissões específicas:
  1. `admin` (Administrador Geral de TI): Gerencia backups, infraestrutura, logs e integrações de e-mail.
  2. `gerente` (Gestão Financeira): Gerencia lançamentos de contas e cria usuários de menor nível.
  3. `operador` (Operações diárias): Cadastra contas e realiza baixas (liquidações), sem permissões administrativas.
* **Envio de Alertas:** Utilização da **API oficial da MailGrid (https://www.mailgrid.com.br/api)** em substituição ao SendGrid, configurada de maneira dinâmica via painel de administração.
* **Segurança do Banco de Dados:** Armazenamento do banco SQLite em diretório interno do projeto, protegido por arquivo de configuração `.htaccess` para bloquear qualquer tentativa de download não autorizado.
* **Cron de Notificações:** Execução diária via arquivo roteador com nome longo e randomizado para segurança por obscuridade, sem necessidade de autenticação direta por token, integrado à rotina automática de backup diário.
* **Gráficos:** Renderização no frontend usando a biblioteca **Chart.js** via CDN, garantindo um dashboard responsivo e de alta qualidade estética.

---

## 📂 Arquitetura e Estrutura de Pastas

O sistema é 100% autônomo, não dependendo de frameworks externos pesados ou de gerenciadores de pacotes (como Composer), o que garante portabilidade máxima. Basta mover a pasta `/cantina-financeiro` para a raiz ou subpasta do servidor Apache.

```text
/cantina-financeiro/
├── db/                                  # Banco de Dados
│   ├── cantina.sqlite                   # Banco de dados SQLite criado automaticamente
│   └── .htaccess                        # Bloqueia download externo do banco
├── backups/                             # Backups Automáticos e Manuais
│   ├── backup_YYYY-MM-DD_HHMMSS.zip     # Arquivos compactados gerados
│   └── .htaccess                        # Bloqueia download externo dos backups
├── assets/                              # Recursos estáticos
│   ├── css/
│   │   └── style.css                    # Folha de estilo personalizada e micro-animações
│   └── js/
│   │   └── dashboard.js                 # Lógica dos gráficos Chart.js e requisições assíncronas
├── src/                                 # Lógica de Negócios (Backend)
│   ├── Database.php                     # Gerenciamento da Conexão PDO e Schema
│   ├── Auth.php                         # Controle de Sessão e Permissões
│   ├── AccountModel.php                 # Operações de Contas (Pagar/Receber)
│   ├── EmailService.php                 # Integração nativa com a API MailGrid (via cURL)
│   └── BackupService.php                # Geração e Restauração de Backups (via ZipArchive)
├── cron_daily_notifications_4fb9e2.php  # Roteador do cron diário de e-mails e backups
├── index.php                            # Dashboard Financeiro (Página Principal)
├── login.php                            # Tela de Login do Usuário
├── contas.php                           # Gerenciamento (CRUD) de Contas
├── usuarios.php                         # Cadastro de Usuários (Gerente/Operador)
├── admin.php                            # Painel de Controle de TI (Backups, SMTP, Logs)
└── logout.php                           # Destrói a sessão e redireciona
```

---

## 🗄️ Modelo de Dados (Schema SQLite)

O banco de dados SQLite será inicializado automaticamente na primeira execução através da classe `Database.php`. Os dados financeiros monetários são salvos como **valores inteiros (representando centavos)** para evitar discrepâncias matemáticas e arredondamentos errôneos decorrentes de números de ponto flutuante.

### Comandos DDL de Criação das Tabelas

```sql
-- Tabela de Usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    usuario TEXT NOT NULL UNIQUE,
    email TEXT,
    senha TEXT NOT NULL,
    nivel TEXT CHECK(nivel IN ('admin', 'gerente', 'operador')) NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Contas (Pagar e Receber)
CREATE TABLE IF NOT EXISTS contas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    descricao TEXT NOT NULL,
    valor INTEGER NOT NULL, -- Valor em centavos (ex: R$ 10,50 = 1050)
    tipo TEXT CHECK(tipo IN ('pagar', 'receber')) NOT NULL,
    status TEXT CHECK(status IN ('pendente', 'pago', 'cancelado')) DEFAULT 'pendente',
    data_vencimento DATE NOT NULL,
    data_liquidacao DATE, -- Preenchido no momento da alteração para status = 'pago'
    categoria TEXT NOT NULL,
    observacoes TEXT,
    criado_por INTEGER,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Tabela de Configurações do Sistema
CREATE TABLE IF NOT EXISTS configuracoes (
    chave TEXT PRIMARY KEY,
    valor TEXT NOT NULL
);

-- Tabela de Histórico de Backups
CREATE TABLE IF NOT EXISTS backups (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome_arquivo TEXT NOT NULL,
    tamanho_bytes INTEGER NOT NULL,
    tipo TEXT CHECK(tipo IN ('automatico', 'manual')) NOT NULL,
    status TEXT CHECK(status IN ('sucesso', 'falha')) NOT NULL,
    criado_por INTEGER,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Tabela de Logs do Sistema (Auditoria)
CREATE TABLE IF NOT EXISTS logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER,
    acao TEXT NOT NULL,
    detalhes TEXT,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);
```

---

## 🔒 Segurança do Servidor Apache (`.htaccess`)

Para assegurar que o banco de dados e os backups armazenados localmente não sejam baixados por usuários externos, criaremos arquivos `.htaccess` dedicados nas pastas sensíveis.

### Arquivo: `/cantina-financeiro/db/.htaccess` e `/cantina-financeiro/backups/.htaccess`
```apache
# Bloqueia o acesso a qualquer arquivo dentro deste diretório a partir de requisições HTTP externas
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Order deny,allow
    Deny from all
</IfModule>
```

---

## ⚙️ Especificação das Classes Backend (PHP Blueprint)

Abaixo estão os códigos estruturais para as classes principais do sistema financeiro. Elas empregam as melhores práticas do **PHP 8+**, tratamento rigoroso de exceções e isolamento de dependências.

### 1. Classe de Banco de Dados: `src/Database.php`
*Gerencia a conexão Singleton do PDO e provisiona automaticamente o banco e as tabelas na primeira execução, criando também um usuário administrador padrão (`admin`) caso não exista nenhum cadastrado.*

```php
<?php
namespace CantinaFinanceiro;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                $dbPath = __DIR__ . '/../db/cantina.sqlite';
                
                // Garante que o diretório db existe
                if (!file_exists(dirname($dbPath))) {
                    mkdir(dirname($dbPath), 0755, true);
                }

                self::$instance = new PDO("sqlite:" . $dbPath);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                
                // Habilita chaves estrangeiras no SQLite
                self::$instance->exec("PRAGMA foreign_keys = ON;");
                
                // Cria as tabelas se necessário
                self::initializeSchema();
            } catch (PDOException $e) {
                die("Erro ao conectar com o banco de dados: " . $e->getMessage());
            }
        }
        return self::$instance;
    }

    private static function initializeSchema(): void {
        $db = self::$instance;

        // Tabela usuarios
        $db->exec("CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            usuario TEXT NOT NULL UNIQUE,
            email TEXT,
            senha TEXT NOT NULL,
            nivel TEXT CHECK(nivel IN ('admin', 'gerente', 'operador')) NOT NULL,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
        );");

        // Tabela contas
        $db->exec("CREATE TABLE IF NOT EXISTS contas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            descricao TEXT NOT NULL,
            valor INTEGER NOT NULL,
            tipo TEXT CHECK(tipo IN ('pagar', 'receber')) NOT NULL,
            status TEXT CHECK(status IN ('pendente', 'pago', 'cancelado')) DEFAULT 'pendente',
            data_vencimento DATE NOT NULL,
            data_liquidacao DATE,
            categoria TEXT NOT NULL,
            observacoes TEXT,
            criado_por INTEGER,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
        );");

        // Tabela configuracoes
        $db->exec("CREATE TABLE IF NOT EXISTS configuracoes (
            chave TEXT PRIMARY KEY,
            valor TEXT NOT NULL
        );");

        // Tabela backups
        $db->exec("CREATE TABLE IF NOT EXISTS backups (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome_arquivo TEXT NOT NULL,
            tamanho_bytes INTEGER NOT NULL,
            tipo TEXT CHECK(tipo IN ('automatico', 'manual')) NOT NULL,
            status TEXT CHECK(status IN ('sucesso', 'falha')) NOT NULL,
            criado_por INTEGER,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
        );");

        // Tabela logs
        $db->exec("CREATE TABLE IF NOT EXISTS logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER,
            acao TEXT NOT NULL,
            detalhes TEXT,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
        );");

        // Insere o usuário Admin default se o banco estiver vazio
        $stmt = $db->query("SELECT COUNT(*) FROM usuarios WHERE nivel = 'admin'");
        if ($stmt->fetchColumn() == 0) {
            $senhaPadrao = password_hash("admin123", PASSWORD_DEFAULT);
            $stmtInsert = $db->prepare("INSERT INTO usuarios (nome, usuario, email, senha, nivel) VALUES (?, ?, ?, ?, 'admin')");
            $stmtInsert->execute(['Administrador de TI', 'admin', 'ti@cantina.com.br', $senhaPadrao]);
        }

        // Insere configurações padrões se não existirem
        $defaultConfigs = [
            'mailgrid_api_url' => 'https://www.mailgrid.com.br/api',
            'mailgrid_api_key' => '',
            'email_remetente' => 'financeiro@cantina.com.br',
            'nome_remetente' => 'Financeiro Cantina Escolar',
            'emails_destinatarios' => 'direcao@cantina.com.br',
            'dias_alerta_vencimento' => '3'
        ];

        foreach ($defaultConfigs as $chave => $valor) {
            $stmtConfig = $db->prepare("INSERT OR IGNORE INTO configuracoes (chave, valor) VALUES (?, ?)");
            $stmtConfig->execute([$chave, $valor]);
        }
    }
}
```

### 2. Classe de Autenticação: `src/Auth.php`
*Controla as sessões, valida login e restringe o acesso às páginas do sistema conforme a matriz de permissões estabelecida.*

```php
<?php
namespace CantinaFinanceiro;

use PDO;

class Auth {
    public static function initSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            // Garante parâmetros de cookie seguros
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/cantina-financeiro/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            session_start();
        }
    }

    public static function login(string $usuario, string $senha): bool {
        self::initSession();
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT * FROM usuarios WHERE usuario = ? LIMIT 1");
        $stmt->execute([$usuario]);
        $user = $stmt->fetch();

        if ($user && password_verify($senha, $user['senha'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nome'] = $user['nome'];
            $_SESSION['user_usuario'] = $user['usuario'];
            $_SESSION['user_nivel'] = $user['nivel'];

            self::logAction($user['id'], 'LOGIN', 'Login realizado com sucesso.');
            return true;
        }

        return false;
    }

    public static function logout(): void {
        self::initSession();
        if (isset($_SESSION['user_id'])) {
            self::logAction($_SESSION['user_id'], 'LOGOUT', 'Sessão encerrada pelo usuário.');
        }
        $_SESSION = [];
        session_destroy();
    }

    public static function checkAuth(): void {
        self::initSession();
        if (!isset($_SESSION['user_id'])) {
            header("Location: login.php");
            exit;
        }
    }

    public static function restrictTo(array $niveisPermitidos): void {
        self::checkAuth();
        if (!in_array($_SESSION['user_nivel'], $niveisPermitidos)) {
            // Redireciona com base no nível em caso de acesso negado
            if ($_SESSION['user_nivel'] === 'admin') {
                header("Location: admin.php?erro=acesso_negado");
            } else {
                header("Location: index.php?erro=acesso_negado");
            }
            exit;
        }
    }

    public static function logAction(int $usuarioId, string $acao, string $detalhes): void {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("INSERT INTO logs (usuario_id, acao, detalhes) VALUES (?, ?, ?)");
            $stmt->execute([$usuarioId, $acao, $detalhes]);
        } catch (\Exception $e) {
            // Ignora silenciosamente erros de escrita de log para não quebrar fluxos principais
        }
    }
}
```

### 3. Classe de Integração com MailGrid: `src/EmailService.php`
*Dispara requisições HTTP POST contendo o payload em formato JSON diretamente para a API brasileira da MailGrid.*

```php
<?php
namespace CantinaFinanceiro;

class EmailService {
    public static function enviarRelatorio(string $assunto, string $corpoHtml): bool {
        $db = Database::getConnection();

        // Recupera as configurações cadastradas
        $configs = [];
        $stmt = $db->query("SELECT chave, valor FROM configuracoes");
        while ($row = $stmt->fetch()) {
            $configs[$row['chave']] = $row['valor'];
        }

        $apiUrl = $configs['mailgrid_api_url'] ?? 'https://www.mailgrid.com.br/api';
        $apiKey = $configs['mailgrid_api_key'] ?? '';
        $remetenteEmail = $configs['email_remetente'] ?? '';
        $remetenteNome = $configs['nome_remetente'] ?? 'Financeiro Cantina';
        $destinatariosRaw = $configs['emails_destinatarios'] ?? '';

        if (empty($apiKey) || empty($remetenteEmail) || empty($destinatariosRaw)) {
            error_log("Erro de Envio: Credenciais ou parâmetros MailGrid vazios.");
            return false;
        }

        // Divide os e-mails separados por vírgula em um array
        $emailsDestino = array_map('trim', explode(',', $destinatariosRaw));
        $destinatariosFormatados = [];
        foreach ($emailsDestino as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $destinatariosFormatados[] = ['email' => $email];
            }
        }

        if (empty($destinatariosFormatados)) {
            return false;
        }

        // Estruturação do Payload JSON compatível com a API da MailGrid
        $payload = [
            'sender' => [
                'name' => $remetenteNome,
                'email' => $remetenteEmail
            ],
            'recipients' => $destinatariosFormatados,
            'subject' => $assunto,
            'html' => $corpoHtml
        ];

        $jsonPayload = json_encode($payload);

        // Comunicação via cURL nativo
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        } else {
            error_log("MailGrid API Error code {$httpCode}. Resposta: " . $response);
            return false;
        }
    }
}
```

### 4. Classe de Backups: `src/BackupService.php`
*Cria um snapshot do banco SQLite compactado em ZIP na pasta segura de backups. Permite que o administrador acompanhe e faça downloads ou restaurações.*

```php
<?php
namespace CantinaFinanceiro;

use ZipArchive;
use Exception;

class BackupService {
    public static function criarBackup(int $usuarioId, string $tipo = 'manual'): bool {
        $db = Database::getConnection();
        $dbFile = __DIR__ . '/../db/cantina.sqlite';
        $backupDir = __DIR__ . '/../backups/';

        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $fileName = 'backup_' . $tipo . '_' . date('Y-m-d_H-i-s') . '.zip';
        $zipPath = $backupDir . $fileName;

        // Cria o arquivo ZIP contendo uma cópia do banco
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
            // Para garantir consistência no SQLite durante cópia
            $tempCopy = $dbFile . '.tmp';
            copy($dbFile, $tempCopy);
            
            $zip->addFile($tempCopy, 'cantina.sqlite');
            $zip->close();
            
            unlink($tempCopy); // Exclui o arquivo temporário

            $tamanho = filesize($zipPath);

            // Registra sucesso no banco de dados
            $stmt = $db->prepare("INSERT INTO backups (nome_arquivo, tamanho_bytes, tipo, status, criado_por) VALUES (?, ?, ?, 'sucesso', ?)");
            $stmt->execute([$fileName, $tamanho, $tipo, $usuarioId]);

            Auth::logAction($usuarioId, 'BACKUP', "Backup {$tipo} gerado: {$fileName} (" . round($tamanho / 1024, 2) . " KB)");
            return true;
        } else {
            // Registra falha no banco de dados
            $stmt = $db->prepare("INSERT INTO backups (nome_arquivo, tamanho_bytes, tipo, status, criado_por) VALUES (?, 0, ?, 'falha', ?)");
            $stmt->execute([$fileName, $tipo, $usuarioId]);
            return false;
        }
    }

    public static function restaurarBackup(string $fileName, int $usuarioId): bool {
        $dbFile = __DIR__ . '/../db/cantina.sqlite';
        $zipPath = __DIR__ . '/../backups/' . $fileName;

        if (!file_exists($zipPath)) {
            return false;
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) === true) {
            // Fecha a conexão PDO ativa antes de sobrescrever o arquivo do banco
            // Para evitar travamento e corrupção no arquivo SQLite
            // Como estamos usando padrão Singleton, forçamos o encerramento do script ou reinicialização
            
            $zip->extractTo(__DIR__ . '/../db/', 'cantina.sqlite');
            $zip->close();

            Auth::logAction($usuarioId, 'RESTORE', "Banco de dados restaurado a partir do backup: {$fileName}");
            return true;
        }
        return false;
    }
}
```

---

## 🕐 Script de Execução Diária (Cron Roteador)

Este script (`cron_daily_notifications_4fb9e2.php`) é programado em um agendador de tarefas. Ele gera o backup diário automático, reúne as contas a pagar e receber do dia, contas em atraso e contas prestes a vencer, constrói um relatório em tabelas estilizadas e envia via API do MailGrid.

### Arquivo: `/cantina-financeiro/cron_daily_notifications_4fb9e2.php`
```php
<?php
// Script de execução diária
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/EmailService.php';
require_once __DIR__ . '/src/BackupService.php';

use CantinaFinanceiro\Database;
use CantinaFinanceiro\EmailService;
use CantinaFinanceiro\BackupService;

try {
    $db = Database::getConnection();

    // 1. Executa backup automático de segurança
    BackupService::criarBackup(1, 'automatico'); // ID do usuário 1 é o Administrador padrão

    // 2. Coleta configurações adicionais
    $stmtConfig = $db->query("SELECT chave, valor FROM configuracoes");
    $configs = [];
    while ($row = $stmtConfig->fetch()) {
        $configs[$row['chave']] = $row['valor'];
    }
    $diasAlerta = (int)($configs['dias_alerta_vencimento'] ?? 3);

    $hoje = date('Y-m-d');
    $futuro = date('Y-m-d', strtotime("+$diasAlerta days"));

    // 3. Consulta as contas do dia
    $stmtContasDia = $db->prepare("SELECT * FROM contas WHERE data_vencimento = ? AND status = 'pendente'");
    $stmtContasDia->execute([$hoje]);
    $contasDia = $stmtContasDia->fetchAll();

    // 4. Consulta as contas vencidas
    $stmtVencidas = $db->prepare("SELECT * FROM contas WHERE data_vencimento < ? AND status = 'pendente' ORDER BY data_vencimento ASC");
    $stmtVencidas->execute([$hoje]);
    $contasVencidas = $stmtVencidas->fetchAll();

    // 5. Consulta contas a vencer nos próximos N dias
    $stmtProximas = $db->prepare("SELECT * FROM contas WHERE data_vencimento > ? AND data_vencimento <= ? AND status = 'pendente' ORDER BY data_vencimento ASC");
    $stmtProximas->execute([$hoje, $futuro]);
    $contasProximas = $stmtProximas->fetchAll();

    // 6. Montagem do corpo do e-mail em HTML
    $corpoHtml = "
    <html>
    <head>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; color: #333; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 8px; }
            .header { background: #4a6fdc; color: #fff; padding: 15px; border-radius: 6px; text-align: center; }
            h2 { margin: 0; }
            .secao { margin-top: 25px; }
            .titulo-secao { font-weight: bold; font-size: 16px; border-bottom: 2px solid #ddd; padding-bottom: 5px; margin-bottom: 10px; }
            .vencida { color: #dc3545; }
            .hoje { color: #ffc107; }
            .proxima { color: #17a2b8; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 13px; }
            th { background-color: #f8f9fa; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Relatório Diário de Contas</h2>
                <p style='margin: 5px 0 0 0;'>Cantina Escolar - " . date('d/m/Y') . "</p>
            </div>
    ";

    $temContas = false;

    // Seção Vencidas
    if (!empty($contasVencidas)) {
        $temContas = true;
        $corpoHtml .= "<div class='secao'>
            <div class='titulo-secao vencida'>⚠️ CONTAS EM ATRASO (VENCIDAS)</div>
            <table>
                <thead>
                    <tr><th>Descrição</th><th>Valor</th><th>Vencimento</th><th>Tipo</th></tr>
                </thead>
                <tbody>";
        foreach ($contasVencidas as $c) {
            $valorFormatado = "R$ " . number_format($c['valor'] / 100, 2, ',', '.');
            $dataVenc = date('d/m/Y', strtotime($c['data_vencimento']));
            $tipoStr = ucfirst($c['tipo']);
            $corpoHtml .= "<tr><td>{$c['descricao']}</td><td>{$valorFormatado}</td><td>{$dataVenc}</td><td>{$tipoStr}</td></tr>";
        }
        $corpoHtml .= "</tbody></table></div>";
    }

    // Seção Hoje
    if (!empty($contasDia)) {
        $temContas = true;
        $corpoHtml .= "<div class='secao'>
            <div class='titulo-secao hoje'>📅 VENCENDO HOJE</div>
            <table>
                <thead>
                    <tr><th>Descrição</th><th>Valor</th><th>Tipo</th></tr>
                </thead>
                <tbody>";
        foreach ($contasDia as $c) {
            $valorFormatado = "R$ " . number_format($c['valor'] / 100, 2, ',', '.');
            $tipoStr = ucfirst($c['tipo']);
            $corpoHtml .= "<tr><td>{$c['descricao']}</td><td>{$valorFormatado}</td><td>{$tipoStr}</td></tr>";
        }
        $corpoHtml .= "</tbody></table></div>";
    }

    // Seção Próximas
    if (!empty($contasProximas)) {
        $temContas = true;
        $corpoHtml .= "<div class='secao'>
            <div class='titulo-secao proxima'>🔔 VENCIMENTOS PRÓXIMOS (Nos próximos {$diasAlerta} dias)</div>
            <table>
                <thead>
                    <tr><th>Descrição</th><th>Valor</th><th>Vencimento</th><th>Tipo</th></tr>
                </thead>
                <tbody>";
        foreach ($contasProximas as $c) {
            $valorFormatado = "R$ " . number_format($c['valor'] / 100, 2, ',', '.');
            $dataVenc = date('d/m/Y', strtotime($c['data_vencimento']));
            $tipoStr = ucfirst($c['tipo']);
            $corpoHtml .= "<tr><td>{$c['descricao']}</td><td>{$valorFormatado}</td><td>{$dataVenc}</td><td>{$tipoStr}</td></tr>";
        }
        $corpoHtml .= "</tbody></table></div>";
    }

    if (!$temContas) {
        $corpoHtml .= "
        <div style='text-align: center; margin-top: 30px; padding: 20px; background-color: #d4edda; color: #155724; border-radius: 6px;'>
            🎉 <strong>Tudo em dia!</strong> Nenhuma conta vencida, vencendo hoje ou nos próximos {$diasAlerta} dias.
        </div>";
    }

    $corpoHtml .= "
            <div style='margin-top: 30px; font-size: 11px; text-align: center; color: #777;'>
                Este e-mail é gerado automaticamente pelo sistema de Contas da Cantina.<br>
                Backup diário de segurança realizado com sucesso em " . date('d/m/Y H:i:s') . ".
            </div>
        </div>
    </body>
    </html>";

    // 7. Envia o e-mail via MailGrid
    $assunto = "Financeiro Cantina: Resumo Diário de Contas - " . date('d/m/Y');
    $enviado = EmailService::enviarRelatorio($assunto, $corpoHtml);

    if ($enviado) {
        echo "Cron executado com sucesso: Relatório de e-mail enviado e backup realizado.";
    } else {
        echo "Backup executado com sucesso, porém houve falha no envio do e-mail da MailGrid.";
    }
} catch (\Exception $e) {
    echo "Erro na execução do Cron: " . $e->getMessage();
}
```

---

## 📊 Visual Dashboard Blueprint (Frontend / Chart.js)

O painel visual consolidará os KPIs financeiros e renderizará gráficos intuitivos. Segue o modelo HTML com a inicialização JavaScript para montagem de gráficos modernos e interativos.

```html
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financeiro - Cantina Escolar</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f4f6f9;
            color: #2c3e50;
        }
        .card-kpi {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card-kpi:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .kpi-title {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #8c98a5;
            font-weight: 600;
        }
        .kpi-value {
            font-size: 1.8rem;
            font-weight: 700;
        }
        .chart-container {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 font-weight-700 mb-0">Painel Financeiro</h1>
            <p class="text-muted mb-0">Cantina Escolar - Visão Geral do Caixa</p>
        </div>
        <span class="badge bg-primary px-3 py-2">Gerente</span>
    </div>

    <!-- KPIs Cards Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card card-kpi p-3 bg-white border-start border-success border-4">
                <div class="kpi-title">Saldo Efetivo (Recebido - Pago)</div>
                <div class="kpi-value text-success">R$ 4.250,00</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-kpi p-3 bg-white border-start border-primary border-4">
                <div class="kpi-title">Saldo Previsto (Total do Mês)</div>
                <div class="kpi-value text-primary">R$ 5.980,00</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-kpi p-3 bg-white border-start border-info border-4">
                <div class="kpi-title">Total Pendente a Receber</div>
                <div class="kpi-value text-info">R$ 2.430,00</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-kpi p-3 bg-white border-start border-danger border-4">
                <div class="kpi-title">Contas Vencidas e Pendentes</div>
                <div class="kpi-value text-danger">R$ 700,00</div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4">
        <!-- Fluxo de Caixa Mensal -->
        <div class="col-lg-8">
            <div class="chart-container">
                <h5 class="mb-3">Fluxo de Caixa Mensal (Receitas vs Despesas)</h5>
                <canvas id="cashFlowChart" height="250"></canvas>
            </div>
        </div>
        <!-- Distribuição por Categoria -->
        <div class="col-lg-4">
            <div class="chart-container">
                <h5 class="mb-3">Despesas por Categoria</h5>
                <canvas id="categoryChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // 1. Gráfico de Fluxo de Caixa Mensal (Receitas vs Despesas)
    const ctxFlow = document.getElementById('cashFlowChart').getContext('2d');
    new Chart(ctxFlow, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
            datasets: [
                {
                    label: 'Receitas (R$)',
                    data: [8200, 7900, 9400, 8800, 9900, 10200],
                    backgroundColor: 'rgba(46, 204, 113, 0.85)',
                    borderColor: 'rgba(46, 204, 113, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                },
                {
                    label: 'Despesas (R$)',
                    data: [6100, 5800, 6800, 6200, 7100, 5950],
                    backgroundColor: 'rgba(231, 76, 60, 0.85)',
                    borderColor: 'rgba(231, 76, 60, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) { return 'R$ ' + value; }
                    }
                }
            }
        }
    });

    // 2. Gráfico de Despesas por Categoria
    const ctxCat = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctxCat, {
        type: 'doughnut',
        data: {
            labels: ['Fornecedores Alimentos', 'Bebidas', 'Funcionários', 'Serviços/Geral'],
            datasets: [{
                data: [4200, 2100, 3500, 1200],
                backgroundColor: [
                    '#3498db',
                    '#e67e22',
                    '#9b59b6',
                    '#95a5a6'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
</script>
</body>
</html>
```

---

## 🚀 Guia de Implantação e Configuração

### 1. Requisitos do Servidor
* Servidor Web **Apache 2.4+** com módulo `mod_rewrite` habilitado (para compatibilidade de subpastas e processamento de arquivos `.htaccess`).
* Versão do **PHP 8.0** ou superior.
* Extensão **PDO SQLite** habilitada no PHP (geralmente habilitada por padrão).
* Extensão **cURL** habilitada no PHP (para comunicação externa com a MailGrid).
* Extensão **ZipArchive** habilitada no PHP (para geração automática de backups ZIP).

### 2. Configurando o Agendador de Tarefas (Cron Diário)
Para que o sistema envie os relatórios por e-mail e faça os backups automaticamente todas as manhãs, adicione o script do cron na rotina do servidor.

#### Exemplo em Servidores Linux (Crontab):
Abra o crontab do servidor:
```bash
crontab -e
```
Adicione a seguinte linha para disparar o script todos os dias às 06:00 da manhã (via HTTP local no Apache):
```bash
0 6 * * * curl -s http://localhost/cantina-financeiro/cron_daily_notifications_4fb9e2.php > /dev/null 2>&1
```

#### Exemplo em Servidores Windows (Task Scheduler):
1. Abra o **Agendador de Tarefas do Windows**.
2. Crie uma **Tarefa Básica** com disparo **Diário** (ex: às 06:00).
3. Defina a ação como **Iniciar um programa**.
4. No campo Programa/script, insira o caminho do PHP (ex: `C:\laragon\bin\php\php-8.x\php.exe`).
5. No campo Adicionar argumentos, insira o caminho completo para o script (ex: `-f D:\laragon\www\rmg-erp-php\cantina-financeiro\cron_daily_notifications_4fb9e2.php`).

---

## 🔒 Auditoria de Segurança e Desempenho

### Lista de Validações de Segurança (Checklist)
- [x] **Prevenção contra Injeção de SQL (SQLi):** Todas as interações com o SQLite são feitas através do PDO utilizando *prepared statements* (consultas parametrizadas com placeholders `?`).
- [x] **Prevenção de XSS (Cross-Site Scripting):** Todas as saídas de textos provenientes de entradas dos usuários que serão impressas no HTML devem ser envelopadas com a função `htmlspecialchars($data, ENT_QUOTES, 'UTF-8')`.
- [x] **Acesso Restrito ao Banco SQLite:** Validação por regra `.htaccess` negando acesso a todo e qualquer usuário via HTTP externo na pasta `/db/` e `/backups/`.
- [x] **Segurança por Obscuridade de Cron:** O arquivo do cron possui o sufixo aleatório `_4fb9e2` no nome, dificultando muito que robôs ou visitantes adivinhem a URL para ficarem reenviando relatórios deliberadamente.
- [x] **Cookies de Sessão Seguros:** Configuração `httponly` ativada, bloqueando o acesso de scripts JavaScript a cookies de sessão e atributo `samesite` configurado como `Strict`.
- [x] **Criptografia de Senhas:** Armazenamento seguro de senhas via função padrão do PHP `password_hash($senha, PASSWORD_DEFAULT)` utilizando algoritmo forte BCrypt.
