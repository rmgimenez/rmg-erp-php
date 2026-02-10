<?php
require_once __DIR__ . '/../../app/controllers/LoginController.php';
require_once __DIR__ . '/../../app/controllers/LogController.php';
require_once __DIR__ . '/../../app/controllers/EmpresaController.php';

$loginController = new LoginController();
$loginController->verificarSuperAdmin();

$logController = new LogController();
$empresaController = new EmpresaController();

// Filtros
$filtros = [];
if (!empty($_GET['empresa_id'])) {
    $filtros['empresa_id'] = $_GET['empresa_id'];
}
if (!empty($_GET['tabela'])) {
    $filtros['tabela'] = $_GET['tabela'];
}
if (!empty($_GET['acao'])) {
    $filtros['acao'] = $_GET['acao'];
}
if (!empty($_GET['usuario_nome'])) {
    $filtros['usuario_nome'] = $_GET['usuario_nome'];
}
if (!empty($_GET['data_inicio'])) {
    $filtros['data_inicio'] = $_GET['data_inicio'];
}
if (!empty($_GET['data_fim'])) {
    $filtros['data_fim'] = $_GET['data_fim'];
}

$logs = $logController->listarTodosLogs($filtros);
$empresas = $empresaController->listarEmpresas();
$nomesTabelas = LogController::nomesTabelas();

$pageTitle = 'Logs do Sistema - Painel Admin - RMG ERP';
$extraCss = '
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
';
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-history me-2"></i>Logs do Sistema</h2>
        <span class="badge bg-secondary fs-6"><?php echo count($logs); ?> registro(s)</span>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="logs.php" class="row g-3">
                <div class="col-md-2">
                    <label for="empresa_id" class="form-label">Empresa</label>
                    <select name="empresa_id" id="empresa_id" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <?php foreach ($empresas as $emp): ?>
                            <option value="<?php echo $emp->getIdEmpresa(); ?>" <?php echo (isset($_GET['empresa_id']) && $_GET['empresa_id'] == $emp->getIdEmpresa()) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($emp->getCodigo() . ' - ' . $emp->getRazaoSocial()); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="tabela" class="form-label">Módulo</label>
                    <select name="tabela" id="tabela" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($nomesTabelas as $chave => $nome): ?>
                            <option value="<?php echo $chave; ?>" <?php echo (isset($_GET['tabela']) && $_GET['tabela'] === $chave) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($nome); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <label for="acao" class="form-label">Ação</label>
                    <select name="acao" id="acao" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <option value="INSERT" <?php echo (isset($_GET['acao']) && $_GET['acao'] === 'INSERT') ? 'selected' : ''; ?>>Inserção</option>
                        <option value="UPDATE" <?php echo (isset($_GET['acao']) && $_GET['acao'] === 'UPDATE') ? 'selected' : ''; ?>>Alteração</option>
                        <option value="DELETE" <?php echo (isset($_GET['acao']) && $_GET['acao'] === 'DELETE') ? 'selected' : ''; ?>>Exclusão</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="usuario_nome" class="form-label">Usuário</label>
                    <input type="text" name="usuario_nome" id="usuario_nome" class="form-control form-control-sm"
                        value="<?php echo htmlspecialchars($_GET['usuario_nome'] ?? ''); ?>" placeholder="Nome do usuário">
                </div>
                <div class="col-md-2">
                    <label for="data_inicio" class="form-label">Data Início</label>
                    <input type="date" name="data_inicio" id="data_inicio" class="form-control form-control-sm"
                        value="<?php echo htmlspecialchars($_GET['data_inicio'] ?? ''); ?>">
                </div>
                <div class="col-md-1">
                    <label for="data_fim" class="form-label">Data Fim</label>
                    <input type="date" name="data_fim" id="data_fim" class="form-control form-control-sm"
                        value="<?php echo htmlspecialchars($_GET['data_fim'] ?? ''); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>Filtrar</button>
                    <a href="logs.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-times me-1"></i>Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela de Logs -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm" id="tabelaLogs">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 150px;">Data/Hora</th>
                            <th style="width: 100px;">Empresa</th>
                            <th style="width: 120px;">Usuário</th>
                            <th style="width: 120px;">Módulo</th>
                            <th style="width: 90px;">Ação</th>
                            <th>Descrição</th>
                            <th style="width: 80px;">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($logs) === 0): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    Nenhum log encontrado para os filtros selecionados.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <small><?php echo date('d/m/Y H:i:s', strtotime($log['data_hora'])); ?></small>
                                    </td>
                                    <td>
                                        <small>
                                            <?php if ($log['empresa_codigo']): ?>
                                                <span class="badge bg-info text-dark"><?php echo htmlspecialchars($log['empresa_codigo']); ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">ADMIN</span>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars($log['usuario_nome'] ?? 'Sistema'); ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars(LogController::nomeTabela($log['tabela'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo LogController::badgeAcao($log['acao']); ?>">
                                            <i class="fas <?php echo LogController::iconeAcao($log['acao']); ?> me-1"></i>
                                            <?php echo $log['acao']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars($log['descricao']); ?></small>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo htmlspecialchars($log['ip'] ?? ''); ?></small>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#tabelaLogs').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
            },
            order: [
                [0, 'desc']
            ],
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            responsive: true
        });
    });
</script>
</body>

</html>