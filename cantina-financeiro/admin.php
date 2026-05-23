<?php
/**
 * Painel Administrativo de TI (Apenas Admin)
 * Gerencia configurações de envio MailGrid, visualização de logs técnicos e rotinas de backups SQLite.
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

$sucessoMsg = '';
$erroMsg = '';

// ----------------------------------------------------
// Processamento de Ações Administrativas (POST)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

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
            $sucessoMsg = "Configurações da MailGrid atualizadas com sucesso.";
            Auth::logAction($usuarioId, 'CONFIG_ATUALIZAR', 'Configurações de e-mail MailGrid alteradas.');
        } catch (\Exception $e) {
            $erroMsg = "Erro ao salvar as configurações: " . $e->getMessage();
        }
    }

    // 2. Criar Backup Manual
    elseif ($acao === 'gerar_backup') {
        if (BackupService::criarBackup($usuarioId, 'manual')) {
            $sucessoMsg = "Backup manual gerado com sucesso.";
        } else {
            $erroMsg = "Falha ao gerar o backup do banco.";
        }
    }

    // 3. Restaurar Backup
    elseif ($acao === 'restaurar_backup') {
        $arquivo = $_POST['nome_arquivo'] ?? '';
        if (!empty($arquivo)) {
            // Fecha a conexão PDO ativamente antes da restauração do banco SQLite
            // No PHP/SQLite, restaurar o arquivo físico ativo requer a reinicialização de conexões.
            // Executamos a restauração e encerramos para forçar um boot limpo no próximo acesso.
            if (BackupService::restaurarBackup($arquivo, $usuarioId)) {
                $sucessoMsg = "Backup restaurado com sucesso. O sistema foi redefinido para este snapshot.";
                // Redireciona com flag de sucesso
                header("Location: admin.php?sucesso=restauracao_completa");
                exit;
            } else {
                $erroMsg = "Falha ao restaurar o backup selecionado.";
            }
        }
    }

    // 4. Excluir Arquivo de Backup
    elseif ($acao === 'excluir_backup') {
        $arquivo = $_POST['nome_arquivo'] ?? '';
        $backupPath = __DIR__ . '/backups/' . $arquivo;
        if (!empty($arquivo) && file_exists($backupPath)) {
            // Segurança básica contra Directory Traversal
            if (strpos($arquivo, 'backup_') === 0 && substr($arquivo, -4) === '.zip') {
                unlink($backupPath);
                
                // Atualiza o registro no banco para deletado
                $stmt = $db->prepare("UPDATE backups SET status = 'falha' WHERE nome_arquivo = ?");
                $stmt->execute([$arquivo]);
                
                $sucessoMsg = "Arquivo de backup removido com sucesso.";
                Auth::logAction($usuarioId, 'BACKUP_DELETAR', "Arquivo de backup {$arquivo} removido do servidor.");
            }
        }
    }
}

// ----------------------------------------------------
// Carrega Configurações Atuais
// ----------------------------------------------------
$configs = [];
$stmtConfigs = $db->query("SELECT chave, valor FROM configuracoes");
while ($row = $stmtConfigs->fetch()) {
    $configs[$row['chave']] = $row['valor'];
}

// ----------------------------------------------------
// Carrega Histórico de Backups e Arquivos Físicos
// ----------------------------------------------------
$backupsFisicos = BackupService::listarArquivosFisicos();

// ----------------------------------------------------
// Carrega Últimos Logs de Auditoria (Limite de 50 registros)
// ----------------------------------------------------
$stmtLogs = $db->query("SELECT l.*, u.usuario as usuario_nome 
                       FROM logs l 
                       LEFT JOIN usuarios u ON l.usuario_id = u.id 
                       ORDER BY l.criado_em DESC LIMIT 50");
$logsAuditoria = $stmtLogs->fetchAll();

// ----------------------------------------------------
// Informações de Infraestrutura de TI para o Dashboard do Admin
// ----------------------------------------------------
$dbPath = __DIR__ . '/db/cantina.sqlite';
$tamanhoDb = file_exists($dbPath) ? round(filesize($dbPath) / 1024, 2) : 0;

// Último backup bem sucedido
$stmtLastBackup = $db->query("SELECT MAX(data_criacao) FROM backups WHERE status = 'sucesso'");
$ultimoBackup = $stmtLastBackup->fetchColumn();

// Último cron diário
$stmtLastCron = $db->query("SELECT MAX(criado_em) FROM logs WHERE acao = 'CRON_DIARIO' OR acao = 'CRON_DIARIO_FALHA'");
$ultimoCron = $stmtLastCron->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin TI - Cantina Financeiro</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="container-fluid py-4 px-md-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-0">Console do Administrador de TI</h1>
            <p class="text-muted small mb-0">Gerenciamento de infraestrutura, logs de auditoria e rotinas de backups da Cantina.</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-danger px-3 py-2 rounded-pill"><i class="fa-solid fa-server me-1"></i> TI Admin</span>
            <a href="logout.php" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
        </div>
    </div>

    <!-- GET messages -->
    <?php if (isset($_GET['sucesso']) && $_GET['sucesso'] === 'restauracao_completa'): ?>
        <div class="alert alert-success border-0 shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> O banco de dados foi restaurado com total sucesso! Todas as tabelas foram revertidas para o ponto selecionado.
        </div>
    <?php endif; ?>

    <!-- Alert Banners -->
    <?php if ($sucessoMsg): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> <?= htmlspecialchars($sucessoMsg, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($erroMsg): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($erroMsg, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- TI Metrics Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-4 col-sm-6">
            <div class="card card-glass p-3 kpi-border-info bg-white">
                <div class="kpi-title">Tamanho do Banco SQLite</div>
                <div class="kpi-value text-info"><?= $tamanhoDb ?> KB</div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="card card-glass p-3 kpi-border-success bg-white">
                <div class="kpi-title">Último Backup Automatizado</div>
                <div class="kpi-value text-success" style="font-size: 1.3rem; padding-top: 5px;">
                    <?= $ultimoBackup ? date('d/m/Y H:i', strtotime($ultimoBackup)) : 'Nenhum' ?>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-12">
            <div class="card card-glass p-3 kpi-border-warning bg-white">
                <div class="kpi-title">Última Execução do Cron Diário</div>
                <div class="kpi-value text-warning" style="font-size: 1.3rem; padding-top: 5px;">
                    <?= $ultimoCron ? date('d/m/Y H:i', strtotime($ultimoCron)) : 'Nenhuma' ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Admin Layout Grid -->
    <div class="row g-4 mb-4">
        <!-- MailGrid SMTP Configurations -->
        <div class="col-lg-6">
            <div class="card card-glass p-4 h-100">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-envelope-open-text text-primary me-2"></i> Configurações MailGrid</h5>
                <form method="POST" action="admin.php">
                    <input type="hidden" name="acao" value="salvar_config">
                    
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Endpoint da API MailGrid *</label>
                        <input type="url" name="mailgrid_api_url" class="form-control form-control-premium" value="<?= htmlspecialchars($configs['mailgrid_api_url'] ?? 'https://www.mailgrid.com.br/api', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Token de API da MailGrid (API Key) *</label>
                        <input type="password" name="mailgrid_api_key" class="form-control form-control-premium" placeholder="Digite seu Token de envio" value="<?= htmlspecialchars($configs['mailgrid_api_key'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">E-mail Remetente *</label>
                            <input type="email" name="email_remetente" class="form-control form-control-premium" value="<?= htmlspecialchars($configs['email_remetente'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Nome Remetente</label>
                            <input type="text" name="nome_remetente" class="form-control form-control-premium" value="<?= htmlspecialchars($configs['nome_remetente'] ?? 'Financeiro Cantina', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Destinatários de Alerta (Separe por vírgulas) *</label>
                        <input type="text" name="emails_destinatarios" class="form-control form-control-premium" placeholder="Ex: diretor@escola.com, ti@escola.com" value="<?= htmlspecialchars($configs['emails_destinatarios'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-muted small fw-semibold">Dias de Antecedência para Notificação Próxima *</label>
                        <input type="number" name="dias_alerta_vencimento" class="form-control form-control-premium" min="1" max="30" value="<?= htmlspecialchars($configs['dias_alerta_vencimento'] ?? '3', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <button type="submit" class="btn btn-premium w-100"><i class="fa-solid fa-floppy-disk me-1"></i> Salvar Parâmetros</button>
                </form>
            </div>
        </div>

        <!-- Backups Control Center -->
        <div class="col-lg-6">
            <div class="card card-glass p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="fa-solid fa-file-zipper text-primary me-2"></i> Backups do Banco SQLite</h5>
                    <form method="POST" action="admin.php">
                        <input type="hidden" name="acao" value="gerar_backup">
                        <button type="submit" class="btn btn-sm btn-premium"><i class="fa-solid fa-plus me-1"></i> Gerar Manual</button>
                    </form>
                </div>

                <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                    <table class="table table-premium mb-0" style="font-size: 0.85rem;">
                        <thead>
                            <tr>
                                <th>Nome do Arquivo</th>
                                <th>Tamanho</th>
                                <th>Data Criação</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($backupsFisicos)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Nenhum snapshot de backup encontrado em backups/</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($backupsFisicos as $bf): ?>
                                    <tr>
                                        <td>
                                            <span class="fw-bold text-truncate d-inline-block" style="max-width: 170px;" title="<?= htmlspecialchars($bf['nome'], ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars($bf['nome'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td class="text-muted"><?= round($bf['tamanho'] / 1024, 2) ?> KB</td>
                                        <td class="text-muted"><?= date('d/m/Y H:i', $bf['data']) ?></td>
                                        <td class="text-center">
                                            <div class="d-inline-flex gap-1">
                                                <!-- Download Backup -->
                                                <a href="backups/<?= htmlspecialchars($bf['nome'], ENT_QUOTES, 'UTF-8') ?>" download class="btn btn-sm btn-outline-success px-2 py-1" title="Download"><i class="fa-solid fa-download"></i></a>

                                                <!-- Restore Backup -->
                                                <form method="POST" action="admin.php" onsubmit="return confirm('ATENÇÃO: Restaurar o backup substituirá o banco de dados inteiro pelo snapshot antigo. Confirma?');" style="display:inline;">
                                                    <input type="hidden" name="acao" value="restaurar_backup">
                                                    <input type="hidden" name="nome_arquivo" value="<?= htmlspecialchars($bf['nome'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-warning px-2 py-1" title="Restaurar Banco"><i class="fa-solid fa-clock-rotate-left"></i></button>
                                                </form>

                                                <!-- Delete Physical Backup File -->
                                                <form method="POST" action="admin.php" onsubmit="return confirm('Excluir permanentemente o backup do disco?');" style="display:inline;">
                                                    <input type="hidden" name="acao" value="excluir_backup">
                                                    <input type="hidden" name="nome_arquivo" value="<?= htmlspecialchars($bf['nome'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1" title="Excluir físico"><i class="fa-solid fa-trash"></i></button>
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
    </div>

    <!-- Audit Logs Section -->
    <div class="row">
        <div class="col-12">
            <div class="card card-glass p-4">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-clipboard-list text-primary me-2"></i> Histórico de Logs de TI (Auditoria e Cron)</h5>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-premium mb-0">
                        <thead>
                            <tr>
                                <th>Data e Hora</th>
                                <th>Operador/Admin</th>
                                <th>Ação</th>
                                <th>Detalhes do Evento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logsAuditoria)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">Nenhum evento registrado no log de auditoria.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logsAuditoria as $l): ?>
                                    <tr>
                                        <td class="text-muted small" style="white-space: nowrap;"><?= date('d/m/Y H:i:s', strtotime($l['criado_em'])) ?></td>
                                        <td><span class="badge bg-light text-dark fw-semibold"><?= htmlspecialchars($l['usuario_nome'] ?: 'Cron/Autônomo', ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td>
                                            <?php 
                                            $badgeClass = 'bg-secondary';
                                            if (strpos($l['acao'], 'FALHA') !== false) {
                                                $badgeClass = 'bg-danger';
                                            } elseif ($l['acao'] === 'LOGIN') {
                                                $badgeClass = 'bg-success';
                                            } elseif ($l['acao'] === 'BACKUP' || $l['acao'] === 'RESTORE') {
                                                $badgeClass = 'bg-info';
                                            }
                                            ?>
                                            <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($l['acao'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </td>
                                        <td class="text-muted fs-6" style="font-size: 0.88rem;"><?= htmlspecialchars($l['detalhes'], ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
