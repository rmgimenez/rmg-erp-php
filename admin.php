<?php
/**
 * Painel Administrativo de TI (Apenas Admin)
 * Gerencia configurações, logs, backups e usuários do sistema.
 */

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/BackupService.php';

use CantinaFinanceiro\Database;
use CantinaFinanceiro\Auth;
use CantinaFinanceiro\BackupService;

Auth::checkAuth();
Auth::restrictTo(['admin']);

$usuarioId = $_SESSION['user_id'];
$db = Database::getConnection();

// ----------------------------------------------------
// Processamento de Ações Administrativas (POST) → PRG
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $tab = $_POST['tab'] ?? 'configuracoes';

    // 1. Salvar Configurações MailGrid
    if ($acao === 'salvar_config') {
        $configs = [
            'mailgrid_api_url' => trim($_POST['mailgrid_api_url'] ?? ''),
            'mailgrid_api_key' => trim($_POST['mailgrid_api_key'] ?? ''),
            'email_remetente' => trim($_POST['email_remetente'] ?? ''),
            'nome_remetente' => trim($_POST['nome_remetente'] ?? ''),
            'emails_destinatarios' => trim($_POST['emails_destinatarios'] ?? ''),
            'dias_alerta_vencimento' => (int)($_POST['dias_alerta_vencimento'] ?? 3)
        ];
        try {
            $stmt = $db->prepare("INSERT OR REPLACE INTO configuracoes (chave, valor) VALUES (?, ?)");
            foreach ($configs as $chave => $valor) {
                $stmt->execute([$chave, (string)$valor]);
            }
            Auth::logAction($usuarioId, 'CONFIG_ATUALIZAR', 'Configurações de e-mail MailGrid alteradas.');
            header("Location: admin.php?tab=$tab&toast=success&msg=" . urlencode("Configurações da MailGrid atualizadas com sucesso."));
            exit;
        } catch (\Exception $e) {
            header("Location: admin.php?tab=$tab&toast=error&msg=" . urlencode("Erro ao salvar as configurações: " . $e->getMessage()));
            exit;
        }
    }

    // 1.2 Salvar Configurações de IA (OpenRouter)
    elseif ($acao === 'salvar_ia_config') {
        $configs = [
            'openrouter_api_key' => trim($_POST['openrouter_api_key'] ?? ''),
            'openrouter_model' => trim($_POST['openrouter_model'] ?? 'google/gemini-2.5-flash'),
            'cardapio_pessoas_estimadas' => trim($_POST['cardapio_pessoas_estimadas'] ?? ''),
            'cardapio_contexto_global' => trim($_POST['cardapio_contexto_global'] ?? '')
        ];
        try {
            $stmt = $db->prepare("INSERT OR REPLACE INTO configuracoes (chave, valor) VALUES (?, ?)");
            foreach ($configs as $chave => $valor) {
                $stmt->execute([$chave, (string)$valor]);
            }
            Auth::logAction($usuarioId, 'CONFIG_IA_ATUALIZAR', 'Configurações do OpenRouter e IA do Cardápio atualizadas.');
            header("Location: admin.php?tab=$tab&toast=success&msg=" . urlencode("Configurações do OpenRouter salvas com sucesso."));
            exit;
        } catch (\Exception $e) {
            header("Location: admin.php?tab=$tab&toast=error&msg=" . urlencode("Erro ao salvar as configurações da IA: " . $e->getMessage()));
            exit;
        }
    }

    // 2. Criar Backup Manual
    elseif ($acao === 'gerar_backup') {
        if (BackupService::criarBackup($usuarioId, 'manual')) {
            header("Location: admin.php?tab=$tab&toast=success&msg=" . urlencode("Backup manual gerado com sucesso."));
        } else {
            header("Location: admin.php?tab=$tab&toast=error&msg=" . urlencode("Falha ao gerar o backup do banco."));
        }
        exit;
    }

    // 3. Restaurar Backup
    elseif ($acao === 'restaurar_backup') {
        $arquivo = $_POST['nome_arquivo'] ?? '';
        if (!empty($arquivo)) {
            if (BackupService::restaurarBackup($arquivo, $usuarioId)) {
                header("Location: admin.php?tab=$tab&toast=success&msg=" . urlencode("Backup restaurado com sucesso. O sistema foi redefinido."));
            } else {
                header("Location: admin.php?tab=$tab&toast=error&msg=" . urlencode("Falha ao restaurar o backup selecionado."));
            }
            exit;
        }
    }

    // 4. Excluir Arquivo de Backup
    elseif ($acao === 'excluir_backup') {
        $arquivo = trim($_POST['nome_arquivo'] ?? '');
        $backupPath = realpath(__DIR__ . '/backups/' . $arquivo);

        if ($backupPath === false || strpos($backupPath, realpath(__DIR__ . '/backups')) !== 0) {
            header("Location: admin.php?tab=$tab&toast=error&msg=" . urlencode("Arquivo de backup inválido."));
            exit;
        }

        if (file_exists($backupPath)) {
            if (unlink($backupPath)) {
                $stmt = $db->prepare("UPDATE backups SET status = 'falha' WHERE nome_arquivo = ?");
                $stmt->execute([$arquivo]);
                Auth::logAction($usuarioId, 'BACKUP_DELETAR', "Arquivo de backup {$arquivo} removido do servidor.");
                header("Location: admin.php?tab=$tab&toast=success&msg=" . urlencode("Arquivo de backup removido com sucesso."));
            } else {
                header("Location: admin.php?tab=$tab&toast=error&msg=" . urlencode("Permissão negada ao excluir o arquivo."));
            }
            exit;
        }

        header("Location: admin.php?tab=$tab&toast=error&msg=" . urlencode("Arquivo não encontrado no servidor."));
        exit;
    }

    // 5. Cadastrar Usuário
    elseif ($acao === 'cadastrar') {
        $nome = trim($_POST['nome'] ?? '');
        $usuario = trim($_POST['usuario'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $nivel = $_POST['nivel'] ?? '';

        if (!empty($nome) && !empty($usuario) && !empty($senha) && !empty($nivel)) {
            $niveisValidos = ['admin', 'gerente', 'operador', 'nutricionista'];
            if (!in_array($nivel, $niveisValidos)) {
                header("Location: admin.php?tab=$tab&toast=error&msg=" . urlencode("Nível de acesso inválido."));
                exit;
            }
            try {
                $stmtCheck = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE usuario = ?");
                $stmtCheck->execute([$usuario]);
                if ($stmtCheck->fetchColumn() > 0) {
                    header("Location: admin.php?tab=$tab&toast=error&msg=" . urlencode("Este nome de usuário já está cadastrado."));
                    exit;
                }
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                $stmtInsert = $db->prepare("INSERT INTO usuarios (nome, usuario, email, senha, nivel) VALUES (?, ?, ?, ?, ?)");
                if ($stmtInsert->execute([$nome, $usuario, $email, $senhaHash, $nivel])) {
                    Auth::logAction($usuarioId, 'USER_CADASTRAR', "Usuário '{$usuario}' com nível '{$nivel}' criado pelo admin.");
                    header("Location: admin.php?tab=$tab&toast=success&msg=" . urlencode("Usuário '{$usuario}' cadastrado com sucesso."));
                    exit;
                }
            } catch (\Exception $e) {
                header("Location: admin.php?tab=$tab&toast=error&msg=" . urlencode("Erro ao cadastrar usuário: " . $e->getMessage()));
                exit;
            }
        }
        header("Location: admin.php?tab=$tab&toast=error&msg=" . urlencode("Preencha todos os campos obrigatórios."));
        exit;
    }

    // 6. Editar Usuário
    elseif ($acao === 'editar') {
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $usuario = trim($_POST['usuario'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $nivel = $_POST['nivel'] ?? '';

        if ($id > 0 && !empty($nome) && !empty($usuario) && !empty($nivel)) {
            $niveisValidos = ['admin', 'gerente', 'operador', 'nutricionista'];
            if (!in_array($nivel, $niveisValidos)) {
                header("Location: admin.php?tab=$tab&toast=error&msg=" . urlencode("Nível de acesso inválido."));
                exit;
            }
            try {
                $stmtCheck = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE usuario = ? AND id != ?");
                $stmtCheck->execute([$usuario, $id]);
                if ($stmtCheck->fetchColumn() > 0) {
                    header("Location: admin.php?tab=$tab&toast=error&msg=" . urlencode("Este nome de usuário já está em uso."));
                    exit;
                }
                if (!empty($senha)) {
                    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                    $stmtUpdate = $db->prepare("UPDATE usuarios SET nome = ?, usuario = ?, email = ?, senha = ?, nivel = ? WHERE id = ?");
                    $exec = $stmtUpdate->execute([$nome, $usuario, $email, $senhaHash, $nivel, $id]);
                } else {
                    $stmtUpdate = $db->prepare("UPDATE usuarios SET nome = ?, usuario = ?, email = ?, nivel = ? WHERE id = ?");
                    $exec = $stmtUpdate->execute([$nome, $usuario, $email, $nivel, $id]);
                }
                if ($exec) {
                    Auth::logAction($usuarioId, 'USER_EDITAR', "Usuário #{$id} ({$usuario}) atualizado pelo admin.");
                    if ($id === $usuarioId) {
                        $_SESSION['user_nome'] = $nome;
                        $_SESSION['user_usuario'] = $usuario;
                        $_SESSION['user_nivel'] = $nivel;
                    }
                    header("Location: admin.php?tab=$tab&toast=success&msg=" . urlencode("Usuário atualizado com sucesso."));
                    exit;
                }
            } catch (\Exception $e) {
                header("Location: admin.php?tab=$tab&toast=error&msg=" . urlencode("Erro ao atualizar usuário."));
                exit;
            }
        }
        header("Location: admin.php?tab=$tab&toast=error&msg=" . urlencode("Dados inválidos para edição."));
        exit;
    }

    // 7. Excluir Usuário
    elseif ($acao === 'excluir') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            if ($id === $usuarioId) {
                header("Location: admin.php?tab=$tab&toast=error&msg=" . urlencode("Você não pode excluir sua própria conta."));
                exit;
            }
            try {
                $stmtUser = $db->prepare("SELECT usuario FROM usuarios WHERE id = ?");
                $stmtUser->execute([$id]);
                $userData = $stmtUser->fetch();
                if ($userData) {
                    $stmtDel = $db->prepare("DELETE FROM usuarios WHERE id = ?");
                    if ($stmtDel->execute([$id])) {
                        Auth::logAction($usuarioId, 'USER_EXCLUIR', "Usuário #{$id} ({$userData['usuario']}) excluído pelo admin.");
                        header("Location: admin.php?tab=$tab&toast=success&msg=" . urlencode("Usuário excluído com sucesso."));
                        exit;
                    }
                }
            } catch (\Exception $e) {
                header("Location: admin.php?tab=$tab&toast=error&msg=" . urlencode("Erro ao excluir usuário."));
                exit;
            }
        }
        header("Location: admin.php?tab=$tab&toast=error&msg=" . urlencode("Usuário inválido."));
        exit;
    }
}

// ----------------------------------------------------
// Carrega Dados
// ----------------------------------------------------
$configs = [];
$stmtConfigs = $db->query("SELECT chave, valor FROM configuracoes");
while ($row = $stmtConfigs->fetch()) {
    $configs[$row['chave']] = $row['valor'];
}

$backupsFisicos = BackupService::listarArquivosFisicos();

$stmtLogs = $db->query("SELECT l.*, u.usuario as usuario_nome
                       FROM logs l
                       LEFT JOIN usuarios u ON l.usuario_id = u.id
                       ORDER BY l.criado_em DESC LIMIT 50");
$logsAuditoria = $stmtLogs->fetchAll();

$stmtUsers = $db->prepare("SELECT id, nome, usuario, email, nivel, criado_em FROM usuarios ORDER BY nivel, nome ASC");
$stmtUsers->execute();
$usuarios = $stmtUsers->fetchAll();

$dbPath = __DIR__ . '/db/cantina.sqlite';
$tamanhoDb = file_exists($dbPath) ? round(filesize($dbPath) / 1024, 2) : 0;

$stmtLastBackup = $db->query("SELECT MAX(data_criacao) FROM backups WHERE status = 'sucesso'");
$ultimoBackup = $stmtLastBackup->fetchColumn();

$stmtLastCron = $db->query("SELECT MAX(criado_em) FROM logs WHERE acao = 'CRON_DIARIO' OR acao = 'CRON_DIARIO_FALHA'");
$ultimoCron = $stmtLastCron->fetchColumn();

$activeTab = $_GET['tab'] ?? 'configuracoes';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - Cantina Sant'Anna</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        :root {
            --admin-bg: #0b0d17;
            --admin-card: #131627;
            --admin-border: #1e2340;
            --admin-text: #e2e4f0;
            --admin-text-muted: #7a7f9a;
            --admin-accent: #00d4aa;
            --admin-accent-dim: rgba(0, 212, 170, 0.15);
        }

        body {
            background: var(--admin-bg);
            color: var(--admin-text);
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .admin-header {
            border-bottom: 1px solid var(--admin-border);
            padding: 1.25rem 0;
            margin-bottom: 1.5rem;
        }

        .admin-header h1 {
            font-weight: 700;
            font-size: 1.5rem;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .admin-header h1 i {
            color: var(--admin-accent);
        }

        .admin-header .badge-admin {
            background: var(--admin-accent-dim);
            color: var(--admin-accent);
            border: 1px solid rgba(0, 212, 170, 0.3);
            font-weight: 600;
            font-size: 0.7rem;
            padding: 0.35rem 0.9rem;
            border-radius: 50px;
            letter-spacing: 0.03em;
        }

        .admin-kpi {
            background: var(--admin-card);
            border: 1px solid var(--admin-border);
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            transition: border-color 0.2s;
        }

        .admin-kpi:hover {
            border-color: var(--admin-accent);
        }

        .admin-kpi .kpi-label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--admin-text-muted);
            margin-bottom: 0.35rem;
        }

        .admin-kpi .kpi-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: #fff;
        }

        .admin-kpi .kpi-icon {
            font-size: 1.6rem;
            color: var(--admin-accent);
            opacity: 0.6;
        }

        /* Nav Tabs Dark */
        .admin-tabs {
            border-bottom: 1px solid var(--admin-border);
            margin-bottom: 1.75rem;
            gap: 0.25rem;
            flex-wrap: nowrap;
        }

        .admin-tabs .nav-link {
            border: none !important;
            color: var(--admin-text-muted) !important;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 0.75rem 1.25rem;
            border-radius: 8px 8px 0 0;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            background: transparent !important;
            margin-bottom: 0;
        }

        .admin-tabs .nav-link i {
            font-size: 0.9rem;
        }

        .admin-tabs .nav-link:hover {
            color: var(--admin-text) !important;
            background: rgba(255, 255, 255, 0.03) !important;
        }

        .admin-tabs .nav-link.active {
            color: var(--admin-accent) !important;
            background: rgba(0, 212, 170, 0.08) !important;
            border-bottom: 2px solid var(--admin-accent) !important;
        }

        /* Cards Dark */
        .admin-card {
            background: var(--admin-card);
            border: 1px solid var(--admin-border);
            border-radius: 12px;
            padding: 1.5rem;
        }

        .admin-card h5 {
            font-weight: 700;
            font-size: 0.95rem;
            color: #fff;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .admin-card h5 i {
            color: var(--admin-accent);
        }

        .admin-card .form-label {
            color: var(--admin-text-muted);
            font-weight: 600;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.3rem;
        }

        .admin-card .form-control,
        .admin-card .form-select {
            background: #0d0f1e;
            border: 1px solid var(--admin-border);
            color: var(--admin-text);
            border-radius: 8px;
            padding: 0.6rem 0.9rem;
            font-size: 0.88rem;
            transition: border-color 0.2s;
        }

        .admin-card .form-control:focus,
        .admin-card .form-select:focus {
            background: #0d0f1e;
            border-color: var(--admin-accent);
            box-shadow: 0 0 0 3px var(--admin-accent-dim);
            color: #fff;
        }

        .admin-card .form-control::placeholder {
            color: #3a3f5c;
        }

        .admin-card textarea.form-control {
            resize: vertical;
            min-height: 70px;
        }

        .btn-admin {
            background: var(--admin-accent);
            color: #0b0d17;
            font-weight: 700;
            border: none;
            border-radius: 8px;
            padding: 0.6rem 1.5rem;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .btn-admin:hover {
            background: #00e8ba;
            color: #0b0d17;
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(0, 212, 170, 0.3);
        }

        .btn-admin-outline {
            background: transparent;
            color: var(--admin-accent);
            border: 1px solid var(--admin-accent);
            border-radius: 8px;
            padding: 0.5rem 1.2rem;
            font-size: 0.82rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-admin-outline:hover {
            background: var(--admin-accent-dim);
            color: var(--admin-accent);
        }

        .btn-admin-danger {
            background: transparent;
            color: #f87171;
            border: 1px solid rgba(248, 113, 113, 0.3);
            border-radius: 8px;
            padding: 0.5rem 1.2rem;
            font-size: 0.82rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-admin-danger:hover {
            background: rgba(248, 113, 113, 0.12);
            color: #fca5a5;
            border-color: rgba(248, 113, 113, 0.5);
        }

        .admin-table {
            font-size: 0.85rem;
            margin-bottom: 0;
        }

        .admin-table thead th {
            background: rgba(0, 0, 0, 0.2);
            color: var(--admin-text-muted);
            font-weight: 600;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--admin-border);
            padding: 0.7rem 0.9rem;
        }

        .admin-table tbody td {
            border-bottom: 1px solid rgba(30, 35, 64, 0.5);
            padding: 0.7rem 0.9rem;
            vertical-align: middle;
        }

        .admin-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .admin-table tbody tr:last-child td {
            border-bottom: none;
        }

        .admin-badge {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.25rem 0.7rem;
            border-radius: 50px;
            letter-spacing: 0.02em;
        }

        .admin-badge-admin {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
        }

        .admin-badge-gerente {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
        }

        .admin-badge-operador {
            background: rgba(234, 179, 8, 0.15);
            color: #fbbf24;
        }

        .admin-badge-nutricionista {
            background: rgba(0, 212, 170, 0.15);
            color: #34d399;
        }

        .admin-form-text {
            font-size: 0.75rem;
            color: var(--admin-text-muted);
            margin-top: 0.25rem;
        }

        .admin-scroll {
            max-height: 420px;
            overflow-y: auto;
        }

        .admin-scroll::-webkit-scrollbar {
            width: 4px;
        }

        .admin-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .admin-scroll::-webkit-scrollbar-thumb {
            background: var(--admin-border);
            border-radius: 4px;
        }

        .admin-scroll::-webkit-scrollbar-thumb:hover {
            background: #2a2f50;
        }

        .admin-log-entry {
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(30, 35, 64, 0.4);
            font-size: 0.82rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .admin-log-entry:last-child {
            border-bottom: none;
        }

        .admin-log-time {
            color: var(--admin-text-muted);
            font-size: 0.72rem;
            white-space: nowrap;
            min-width: 130px;
        }

        .admin-log-badge {
            font-size: 0.65rem;
            font-weight: 600;
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            min-width: 70px;
            text-align: center;
        }

        .admin-log-detail {
            color: rgba(255, 255, 255, 0.7);
            word-break: break-word;
        }

        .admin-backup-actions {
            display: flex;
            gap: 0.35rem;
            justify-content: flex-end;
        }

        .admin-backup-actions button,
        .admin-backup-actions a {
            width: 30px;
            height: 30px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            font-size: 0.75rem;
        }

        .modal-admin .modal-content {
            background: var(--admin-card);
            border: 1px solid var(--admin-border);
            border-radius: 14px;
        }

        .modal-admin .modal-header {
            border-bottom: 1px solid var(--admin-border);
            padding: 1.25rem 1.5rem;
        }

        .modal-admin .modal-header .modal-title {
            font-weight: 700;
            font-size: 1rem;
            color: #fff;
        }

        .modal-admin .modal-header .btn-close {
            filter: invert(0.6);
        }

        .modal-admin .modal-body {
            padding: 1.5rem;
        }

        .modal-admin .modal-footer {
            border-top: 1px solid var(--admin-border);
            padding: 1rem 1.5rem;
        }

        .modal-admin .btn-close {
            background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%237a7f9a'%3e%3cpath d='M.293.293a1 1 0 011.414 0L8 6.586 14.293.293a1 1 0 111.414 1.414L9.414 8l6.293 6.293a1 1 0 01-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 01-1.414-1.414L6.586 8 .293 1.707a1 1 0 010-1.414z'/%3e%3c/svg%3e") center/0.8em auto no-repeat;
        }

        .btn-admin-sm {
            padding: 0.35rem 0.9rem;
            font-size: 0.78rem;
            border-radius: 6px;
        }
    </style>
</head>
<body>

<div class="container-fluid px-4 py-3">

    <!-- Header -->
    <div class="admin-header d-flex justify-content-between align-items-center">
        <h1>
            <i class="fa-solid fa-terminal"></i>
            Console Admin
            <span class="badge-admin">TI</span>
        </h1>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge-admin d-flex align-items-center gap-1">
                <i class="fa-solid fa-shield-hover"></i> admin
            </span>
            <a href="logout.php" class="btn-admin-outline btn-admin-sm">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </div>
    </div>

    <!-- KPI Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="admin-kpi d-flex align-items-center justify-content-between">
                <div>
                    <div class="kpi-label">Banco SQLite</div>
                    <div class="kpi-value"><?= $tamanhoDb ?> KB</div>
                </div>
                <i class="fa-solid fa-database kpi-icon"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="admin-kpi d-flex align-items-center justify-content-between">
                <div>
                    <div class="kpi-label">Último Backup</div>
                    <div class="kpi-value" style="font-size:1.15rem;">
                        <?= $ultimoBackup ? date('d/m/Y H:i', strtotime($ultimoBackup)) : 'Nenhum' ?>
                    </div>
                </div>
                <i class="fa-solid fa-floppy-disk kpi-icon"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="admin-kpi d-flex align-items-center justify-content-between">
                <div>
                    <div class="kpi-label">Último Cron</div>
                    <div class="kpi-value" style="font-size:1.15rem;">
                        <?= $ultimoCron ? date('d/m/Y H:i', strtotime($ultimoCron)) : 'Nenhum' ?>
                    </div>
                </div>
                <i class="fa-solid fa-clock kpi-icon"></i>
            </div>
        </div>
    </div>

    <!-- Toast notifications are rendered via JS on page load -->

    <!-- Nav Tabs -->
    <ul class="nav admin-tabs" id="adminTab" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link <?= $activeTab === 'configuracoes' ? 'active' : '' ?>"
               data-tab="configuracoes" href="#pane-configuracoes" role="tab">
                <i class="fa-solid fa-sliders"></i> Configurações
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link <?= $activeTab === 'backups' ? 'active' : '' ?>"
               data-tab="backups" href="#pane-backups" role="tab">
                <i class="fa-solid fa-hard-drive"></i> Backups
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link <?= $activeTab === 'usuarios' ? 'active' : '' ?>"
               data-tab="usuarios" href="#pane-usuarios" role="tab">
                <i class="fa-solid fa-users-gear"></i> Usuários
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link <?= $activeTab === 'auditoria' ? 'active' : '' ?>"
               data-tab="auditoria" href="#pane-auditoria" role="tab">
                <i class="fa-solid fa-list-check"></i> Auditoria
            </a>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">

        <!-- ============================================================ -->
        <!-- TAB: CONFIGURAÇÕES -->
        <!-- ============================================================ -->
        <div class="tab-pane fade <?= $activeTab === 'configuracoes' ? 'show active' : '' ?>" id="pane-configuracoes" role="tabpanel">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="admin-card">
                        <h5><i class="fa-solid fa-envelope"></i> MailGrid</h5>
                        <form method="POST" action="admin.php">
                            <input type="hidden" name="acao" value="salvar_config">
                            <input type="hidden" name="tab" value="configuracoes">

                            <div class="mb-3">
                                <label class="form-label">Endpoint da API</label>
                                <input type="url" name="mailgrid_api_url" class="form-control"
                                       value="<?= htmlspecialchars($configs['mailgrid_api_url'] ?? 'https://www.mailgrid.com.br/api', ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">API Key</label>
                                <input type="password" name="mailgrid_api_key" class="form-control"
                                       value="<?= htmlspecialchars($configs['mailgrid_api_key'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">E-mail Remetente</label>
                                    <input type="email" name="email_remetente" class="form-control"
                                           value="<?= htmlspecialchars($configs['email_remetente'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nome Remetente</label>
                                    <input type="text" name="nome_remetente" class="form-control"
                                           value="<?= htmlspecialchars($configs['nome_remetente'] ?? "Financeiro Cantina Sant'Anna", ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Destinatários (vírgulas)</label>
                                <input type="text" name="emails_destinatarios" class="form-control"
                                       value="<?= htmlspecialchars($configs['emails_destinatarios'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Dias de alerta</label>
                                <input type="number" name="dias_alerta_vencimento" class="form-control" min="1" max="30"
                                       value="<?= htmlspecialchars($configs['dias_alerta_vencimento'] ?? '3', ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>

                            <button type="submit" class="btn btn-admin w-100">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Salvar MailGrid
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="admin-card">
                        <h5><i class="fa-solid fa-robot"></i> OpenRouter / IA</h5>
                        <form method="POST" action="admin.php">
                            <input type="hidden" name="acao" value="salvar_ia_config">
                            <input type="hidden" name="tab" value="configuracoes">

                            <div class="mb-3">
                                <label class="form-label">API Key OpenRouter</label>
                                <input type="password" name="openrouter_api_key" class="form-control"
                                       value="<?= htmlspecialchars($configs['openrouter_api_key'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                                <div class="admin-form-text">sk-or-v1-... Obtenha em openrouter.ai</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Modelo</label>
                                <input type="text" name="openrouter_model" class="form-control"
                                       value="<?= htmlspecialchars($configs['openrouter_model'] ?? 'google/gemini-2.5-flash', ENT_QUOTES, 'UTF-8') ?>" required>
                                <div class="admin-form-text">Ex: google/gemini-2.5-flash</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Público Estimado</label>
                                <input type="text" name="cardapio_pessoas_estimadas" class="form-control"
                                       value="<?= htmlspecialchars($configs['cardapio_pessoas_estimadas'] ?? '130 alunos do ensino médio, 30 funcionários', ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Contexto Global do Cardápio</label>
                                <textarea name="cardapio_contexto_global" class="form-control" rows="3" required><?= htmlspecialchars($configs['cardapio_contexto_global'] ?? 'Cantina escolar. Refeições saudáveis, saborosas e balanceadas.', ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-admin w-100">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Salvar IA
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- TAB: BACKUPS -->
        <!-- ============================================================ -->
        <div class="tab-pane fade <?= $activeTab === 'backups' ? 'show active' : '' ?>" id="pane-backups" role="tabpanel">
            <div class="admin-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="fa-solid fa-hard-drive"></i> Snapshots do Banco</h5>
                    <form method="POST" action="admin.php">
                        <input type="hidden" name="acao" value="gerar_backup">
                        <input type="hidden" name="tab" value="backups">
                        <button type="submit" class="btn btn-admin btn-admin-sm">
                            <i class="fa-solid fa-plus me-1"></i> Gerar Backup
                        </button>
                    </form>
                </div>

                <div class="admin-scroll">
                    <table class="admin-table w-100">
                        <thead>
                            <tr>
                                <th>Arquivo</th>
                                <th>Tamanho</th>
                                <th>Data</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($backupsFisicos)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4" style="color:var(--admin-text-muted);">
                                        <i class="fa-solid fa-database me-1"></i> Nenhum backup encontrado
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($backupsFisicos as $bf): ?>
                                    <tr>
                                        <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                            <span title="<?= htmlspecialchars($bf['nome'], ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars($bf['nome'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td style="color:var(--admin-text-muted);"><?= round($bf['tamanho'] / 1024, 2) ?> KB</td>
                                        <td style="color:var(--admin-text-muted);"><?= date('d/m/Y H:i', $bf['data']) ?></td>
                                        <td>
                                            <div class="admin-backup-actions">
                                                <a href="backups/<?= htmlspecialchars($bf['nome'], ENT_QUOTES, 'UTF-8') ?>" download
                                                   class="btn-admin-outline" style="width:30px;height:30px;padding:0;display:flex;align-items:center;justify-content:center;font-size:0.75rem;"
                                                   title="Download"><i class="fa-solid fa-download"></i></a>

                                                <form method="POST" action="admin.php"
                                                      onsubmit="return confirm('Restaurar este backup substituirá todo o banco atual. Confirma?');" style="display:inline;">
                                                    <input type="hidden" name="acao" value="restaurar_backup">
                                                    <input type="hidden" name="tab" value="backups">
                                                    <input type="hidden" name="nome_arquivo" value="<?= htmlspecialchars($bf['nome'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <button type="submit" class="btn-admin-outline" style="width:30px;height:30px;padding:0;display:flex;align-items:center;justify-content:center;font-size:0.75rem;"
                                                            title="Restaurar"><i class="fa-solid fa-clock-rotate-left"></i></button>
                                                </form>

                                                <form method="POST" action="admin.php"
                                                      onsubmit="return confirm('Excluir permanentemente este arquivo?');" style="display:inline;">
                                                    <input type="hidden" name="acao" value="excluir_backup">
                                                    <input type="hidden" name="tab" value="backups">
                                                    <input type="hidden" name="nome_arquivo" value="<?= htmlspecialchars($bf['nome'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <button type="submit" class="btn-admin-danger" style="width:30px;height:30px;padding:0;display:flex;align-items:center;justify-content:center;font-size:0.75rem;"
                                                            title="Excluir"><i class="fa-solid fa-trash"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- TAB: USUÁRIOS -->
        <!-- ============================================================ -->
        <div class="tab-pane fade <?= $activeTab === 'usuarios' ? 'show active' : '' ?>" id="pane-usuarios" role="tabpanel">
            <div class="admin-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="fa-solid fa-users-gear"></i> Todos os Usuários</h5>
                    <button class="btn btn-admin btn-admin-sm" data-bs-toggle="modal" data-bs-target="#modalCriarUsuario">
                        <i class="fa-solid fa-user-plus me-1"></i> Novo Usuário
                    </button>
                </div>

                <div class="admin-scroll">
                    <table class="admin-table w-100">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Login</th>
                                <th>Email</th>
                                <th>Nível</th>
                                <th>Criado em</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $u): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($u['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><code style="color:var(--admin-accent);background:rgba(0,212,170,0.08);padding:0.1rem 0.4rem;border-radius:4px;font-size:0.82rem;"><?= htmlspecialchars($u['usuario'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                    <td style="color:var(--admin-text-muted);"><?= htmlspecialchars($u['email'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="admin-badge admin-badge-<?= $u['nivel'] ?>">
                                            <?php if ($u['nivel'] === 'admin'): ?><i class="fa-solid fa-shield-hover me-1"></i>
                                            <?php elseif ($u['nivel'] === 'gerente'): ?><i class="fa-solid fa-user-tie me-1"></i>
                                            <?php elseif ($u['nivel'] === 'nutricionista'): ?><i class="fa-solid fa-apple-alt me-1"></i>
                                            <?php else: ?><i class="fa-solid fa-cash-register me-1"></i>
                                            <?php endif; ?>
                                            <?= ucfirst($u['nivel']) ?>
                                        </span>
                                    </td>
                                    <td style="color:var(--admin-text-muted);font-size:0.8rem;"><?= date('d/m/Y H:i', strtotime($u['criado_em'])) ?></td>
                                    <td>
                                        <div class="admin-backup-actions">
                                            <button class="btn-admin-outline" style="width:30px;height:30px;padding:0;display:flex;align-items:center;justify-content:center;font-size:0.75rem;"
                                                    data-bs-toggle="modal" data-bs-target="#modalEditarUsuario"
                                                    data-id="<?= $u['id'] ?>"
                                                    data-nome="<?= htmlspecialchars($u['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-usuario="<?= htmlspecialchars($u['usuario'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-email="<?= htmlspecialchars($u['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                    data-nivel="<?= $u['nivel'] ?>"
                                                    title="Editar"><i class="fa-solid fa-user-pen"></i></button>
                                            <?php if ($u['id'] !== $usuarioId): ?>
                                                <form method="POST" action="admin.php"
                                                      onsubmit="return confirm('Excluir permanentemente este usuário?');" style="display:inline;">
                                                    <input type="hidden" name="acao" value="excluir">
                                                    <input type="hidden" name="tab" value="usuarios">
                                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                    <button type="submit" class="btn-admin-danger" style="width:30px;height:30px;padding:0;display:flex;align-items:center;justify-content:center;font-size:0.75rem;"
                                                            title="Excluir"><i class="fa-solid fa-trash-can"></i></button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- TAB: AUDITORIA -->
        <!-- ============================================================ -->
        <div class="tab-pane fade <?= $activeTab === 'auditoria' ? 'show active' : '' ?>" id="pane-auditoria" role="tabpanel">
            <div class="admin-card">
                <h5><i class="fa-solid fa-list-check"></i> Logs de Auditoria (últimos 50)</h5>
                <div class="admin-scroll" style="max-height:500px;">
                    <?php if (empty($logsAuditoria)): ?>
                        <div style="text-align:center;padding:2rem 0;color:var(--admin-text-muted);">
                            <i class="fa-solid fa-inbox fs-4 mb-2 d-block"></i>
                            Nenhum evento registrado
                        </div>
                    <?php else: ?>
                        <?php foreach ($logsAuditoria as $l): ?>
                            <div class="admin-log-entry">
                                <span class="admin-log-time"><?= date('d/m/Y H:i:s', strtotime($l['criado_em'])) ?></span>
                                <span class="admin-log-badge" style="background:rgba(255,255,255,0.05);color:var(--admin-text-muted);">
                                    <?= htmlspecialchars($l['acao'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <span style="color:var(--admin-text-muted);font-size:0.78rem;min-width:100px;">
                                    <?= htmlspecialchars($l['usuario_nome'] ?: 'Sistema', ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <span class="admin-log-detail"><?= htmlspecialchars($l['detalhes'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div><!-- /.tab-content -->
</div><!-- /.container-fluid -->

<!-- ============================================================ -->
<!-- MODAL: CRIAR USUÁRIO -->
<!-- ============================================================ -->
<div class="modal fade modal-admin" id="modalCriarUsuario" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-user-plus" style="color:var(--admin-accent);"></i> Novo Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="admin.php">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="cadastrar">
                    <input type="hidden" name="tab" value="usuarios">

                    <div class="mb-3">
                        <label class="form-label">Nome Completo *</label>
                        <input type="text" name="nome" class="form-control" placeholder="Ex: Carlos Admin" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Login *</label>
                            <input type="text" name="usuario" class="form-control" placeholder="carlos.admin" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nível *</label>
                            <select name="nivel" class="form-select" required>
                                <option value="operador">Operador (Caixa)</option>
                                <option value="gerente">Gerente (Financeiro)</option>
                                <option value="nutricionista">Nutricionista (Cardápio)</option>
                                <option value="admin">Administrador (TI)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">E-mail</label>
                        <input type="email" name="email" class="form-control" placeholder="carlos@escola.com">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Senha Inicial *</label>
                        <input type="password" name="senha" class="form-control" placeholder="Mínimo 4 caracteres" required minlength="4">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-admin-outline btn-admin-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-admin btn-admin-sm">Criar Conta</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODAL: EDITAR USUÁRIO -->
<!-- ============================================================ -->
<div class="modal fade modal-admin" id="modalEditarUsuario" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-user-pen" style="color:var(--admin-accent);"></i> Editar Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="admin.php">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="editar">
                    <input type="hidden" name="tab" value="usuarios">
                    <input type="hidden" name="id" id="edit-user-id">

                    <div class="mb-3">
                        <label class="form-label">Nome Completo *</label>
                        <input type="text" name="nome" id="edit-user-nome" class="form-control" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Login *</label>
                            <input type="text" name="usuario" id="edit-user-usuario" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nível *</label>
                            <select name="nivel" id="edit-user-nivel" class="form-select" required>
                                <option value="operador">Operador (Caixa)</option>
                                <option value="gerente">Gerente (Financeiro)</option>
                                <option value="nutricionista">Nutricionista (Cardápio)</option>
                                <option value="admin">Administrador (TI)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">E-mail</label>
                        <input type="email" name="email" id="edit-user-email" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nova Senha <span style="color:var(--admin-text-muted);font-weight:400;font-size:0.75rem;">(deixe em branco para manter)</span></label>
                        <input type="password" name="senha" id="edit-user-senha" class="form-control" placeholder="Nova senha do usuário" minlength="4">
                    </div>

                    <div style="background:rgba(0,212,170,0.06);border:1px solid rgba(0,212,170,0.15);border-radius:8px;padding:0.75rem 1rem;font-size:0.78rem;color:var(--admin-text-muted);">
                        <i class="fa-solid fa-info-circle me-1" style="color:var(--admin-accent);"></i>
                        Se desejar apenas alterar a senha, preencha somente o campo "Nova Senha".
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-admin-outline btn-admin-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-admin btn-admin-sm">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/toast.js"></script>
<script>
    // Carrega dados no modal de edição
    const modalEditarUsuario = document.getElementById('modalEditarUsuario');
    if (modalEditarUsuario) {
        modalEditarUsuario.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            document.getElementById('edit-user-id').value = button.getAttribute('data-id');
            document.getElementById('edit-user-nome').value = button.getAttribute('data-nome');
            document.getElementById('edit-user-usuario').value = button.getAttribute('data-usuario');
            document.getElementById('edit-user-email').value = button.getAttribute('data-email');
            document.getElementById('edit-user-nivel').value = button.getAttribute('data-nivel');
            document.getElementById('edit-user-senha').value = '';
        });
    }

    // Controle de abas (JS puro, sem dependência do Bootstrap)
    document.querySelectorAll('.admin-tabs .nav-link').forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            var targetId = this.getAttribute('href');
            if (!targetId) return;

            // Remove active de todas as abas e painéis
            document.querySelectorAll('.admin-tabs .nav-link').forEach(function(t) {
                t.classList.remove('active');
            });
            document.querySelectorAll('.tab-pane').forEach(function(p) {
                p.classList.remove('show', 'active');
            });

            // Ativa aba clicada
            this.classList.add('active');

            // Ativa painel correspondente
            var pane = document.querySelector(targetId);
            if (pane) {
                pane.classList.add('show', 'active');
            }

            // Atualiza URL sem recarregar
            var tabName = this.getAttribute('data-tab');
            if (tabName) {
                var url = new URL(window.location);
                url.searchParams.set('tab', tabName);
                window.history.replaceState({}, '', url);
            }
        });
    });

    // Exibe toast se houver parâmetros na URL (PRG)
    (function() {
        var params = new URLSearchParams(window.location.search);
        var toastType = params.get('toast');
        var toastMsg = params.get('msg');
        if (toastType && toastMsg) {
            exibirToast(decodeURIComponent(toastMsg), toastType === 'success' ? 'success' : 'error');
            var url = new URL(window.location);
            url.searchParams.delete('toast');
            url.searchParams.delete('msg');
            window.history.replaceState({}, '', url);
        }
    })();
</script>
</body>
</html>
