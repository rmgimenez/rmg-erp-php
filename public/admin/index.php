<?php
require_once __DIR__ . '/../../app/controllers/LoginController.php';
require_once __DIR__ . '/../../app/controllers/EmpresaController.php';
require_once __DIR__ . '/../../app/dao/UsuarioDAO.php';

$loginController = new LoginController();
$loginController->verificarSuperAdmin();

$empresaController = new EmpresaController();
$usuarioDAO = new UsuarioDAO();

$statsEmpresas = $empresaController->obterEstatisticas();
$totalUsuarios = $usuarioDAO->contarUsuarios();

$usuarioNome = $_SESSION['usuario_nome'] ?? 'Admin';
$tipoUsuario = $_SESSION['usuario_tipo'] ?? 'super_admin';

$pageTitle = 'Painel Administrativo - RMG ERP SaaS';
include __DIR__ . '/../includes/header.php';
?>

<!-- Main Content -->
<div class="container mt-4">
    <!-- Welcome Banner -->
    <div class="dashboard-welcome mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4><i class="fas fa-shield-alt me-2" style="opacity:0.8"></i>Painel Administrativo SaaS</h4>
                <p>Bem-vindo, <strong><?php echo htmlspecialchars($usuarioNome); ?></strong> — Super Administrador &mdash; <?php echo date('d/m/Y'); ?></p>
            </div>
            <div class="d-none d-md-block">
                <i class="fas fa-server" style="font-size: 2.5rem; opacity: 0.15;"></i>
            </div>
        </div>
    </div>

    <!-- KPI Widgets -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card text-white bg-primary shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Empresas Cadastradas</h6>
                            <p class="card-text h3 mt-2 mb-0"><?php echo $statsEmpresas['total']; ?></p>
                        </div>
                        <i class="fas fa-building fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card text-white bg-success shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Empresas Ativas</h6>
                            <p class="card-text h3 mt-2 mb-0"><?php echo $statsEmpresas['ativas']; ?></p>
                        </div>
                        <i class="fas fa-check-circle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card text-white bg-info shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Total de Usuários</h6>
                            <p class="card-text h3 mt-2 mb-0"><?php echo $totalUsuarios; ?></p>
                        </div>
                        <i class="fas fa-users fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card text-white bg-warning shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Empresas Inativas</h6>
                            <p class="card-text h3 mt-2 mb-0"><?php echo $statsEmpresas['total'] - $statsEmpresas['ativas']; ?></p>
                        </div>
                        <i class="fas fa-ban fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2" style="color:var(--rmg-warning)"></i>Ações Rápidas</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="empresas.php" class="btn btn-outline-primary">
                            <i class="fas fa-building me-2"></i>Gerenciar Empresas
                        </a>
                        <a href="usuarios.php" class="btn btn-outline-info">
                            <i class="fas fa-users-cog me-2"></i>Gerenciar Usuários
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2" style="color:var(--rmg-info)"></i>Sobre o Sistema</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Plataforma:</strong> <?php echo defined('PLATFORM_NAME') ? htmlspecialchars(PLATFORM_NAME) : 'RMG ERP SaaS'; ?></p>
                    <p class="mb-2"><strong>Arquitetura:</strong> Multi-tenant (separação por empresa_id)</p>
                    <p class="mb-2"><strong>Versão:</strong> 2.0 SaaS</p>
                    <p class="mb-0"><strong>Tecnologia:</strong> PHP 8.x + MySQL + Bootstrap 5</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Empresas -->
    <?php
    $empresas = $empresaController->listarEmpresas();
    $empresasRecentes = array_slice($empresas, 0, 5);
    ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-building me-2" style="color:var(--rmg-primary)"></i>Empresas Recentes</h5>
            <a href="empresas.php" class="btn btn-sm btn-outline-primary">Ver Todas</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Código</th>
                            <th>Razão Social</th>
                            <th>Nome Fantasia</th>
                            <th>CNPJ</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($empresasRecentes)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">Nenhuma empresa cadastrada ainda.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($empresasRecentes as $emp): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($emp->getCodigo()); ?></span></td>
                                    <td><?php echo htmlspecialchars($emp->getRazaoSocial()); ?></td>
                                    <td><?php echo htmlspecialchars($emp->getNomeFantasia()); ?></td>
                                    <td><?php echo htmlspecialchars($emp->getCnpj()); ?></td>
                                    <td>
                                        <?php if ($emp->getAtiva()): ?>
                                            <span class="badge bg-success">Ativa</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inativa</span>
                                        <?php endif; ?>
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
</body>

</html>