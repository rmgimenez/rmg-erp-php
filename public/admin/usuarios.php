<?php
require_once __DIR__ . '/../../app/controllers/LoginController.php';
require_once __DIR__ . '/../../app/controllers/UsuarioController.php';
require_once __DIR__ . '/../../app/controllers/EmpresaController.php';

$loginController = new LoginController();
$loginController->verificarSuperAdmin();

$usuarioController = new UsuarioController();
$empresaController = new EmpresaController();

$mensagem = '';
$tipoMensagem = '';

// Filtro por empresa
$filtroEmpresaId = isset($_GET['empresa_id']) && $_GET['empresa_id'] !== '' ? (int)$_GET['empresa_id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        if ($_POST['acao'] === 'salvar') {
            $resultado = $usuarioController->salvar($_POST);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['sucesso'] ? 'success' : 'danger';
        } elseif ($_POST['acao'] === 'excluir') {
            $id = $_POST['id_usuario'] ?? 0;
            $resultado = $usuarioController->excluir($id);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['sucesso'] ? 'success' : 'danger';
        }
    }
}

// Listar todos os usuários (super_admin vê todos) ou filtrados por empresa
$usuarios = $usuarioController->listarUsuarios($filtroEmpresaId);

// Listar empresas para filtro e dropdown
$empresas = $empresaController->listarEmpresas();

$usuarioNome = $_SESSION['usuario_nome'] ?? 'Admin';
$tipoUsuario = $_SESSION['usuario_tipo'] ?? 'super_admin';

$pageTitle = 'Gerenciar Usuários - RMG ERP SaaS';
$extraCss = '<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">';
include __DIR__ . '/../includes/header.php';
?>

<!-- Main Content -->
<div class="container mt-4">

    <div class="row mb-3">
        <div class="col-md-6">
            <h2><i class="fas fa-users-cog me-2"></i> Usuários (Todas as Empresas)</h2>
        </div>
        <div class="col-md-6 text-end">
            <button type="button" class="btn btn-primary" onclick="abrirModal()">
                <i class="fas fa-plus me-1"></i> Novo Usuário
            </button>
        </div>
    </div>

    <?php if ($mensagem): ?>
        <div class="alert alert-<?php echo $tipoMensagem; ?> alert-dismissible fade show" role="alert">
            <?php echo $mensagem; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Filtro por Empresa -->
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="usuarios.php" class="row align-items-end">
                <div class="col-md-6 mb-2 mb-md-0">
                    <label for="filtroEmpresa" class="form-label">Filtrar por Empresa</label>
                    <select class="form-select" id="filtroEmpresa" name="empresa_id">
                        <option value="">Todas as Empresas</option>
                        <?php foreach ($empresas as $emp): ?>
                            <option value="<?php echo $emp->getIdEmpresa(); ?>"
                                <?php echo ($filtroEmpresaId == $emp->getIdEmpresa()) ? 'selected' : ''; ?>>
                                [<?php echo htmlspecialchars($emp->getCodigo()); ?>] <?php echo htmlspecialchars($emp->getRazaoSocial()); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="fas fa-filter me-1"></i> Filtrar
                    </button>
                    <a href="usuarios.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i> Limpar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelaUsuarios" class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th width="5%">ID</th>
                            <th>Nome</th>
                            <th>Login</th>
                            <th>Empresa</th>
                            <th width="10%">Tipo</th>
                            <th width="8%">Status</th>
                            <th width="15%" class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usu): ?>
                            <tr>
                                <td><?php echo $usu->getIdUsuario(); ?></td>
                                <td><?php echo htmlspecialchars($usu->getNome()); ?></td>
                                <td><code><?php echo htmlspecialchars($usu->getUsuario()); ?></code></td>
                                <td>
                                    <?php if ($usu->getEmpresaId()): ?>
                                        <?php echo htmlspecialchars($usu->getNomeEmpresa()); ?>
                                    <?php else: ?>
                                        <span class="text-muted">— Plataforma —</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $badgeClass = 'bg-info';
                                    $badgeLabel = 'Operador';
                                    if ($usu->getTipoUsuario() === 'super_admin') {
                                        $badgeClass = 'bg-danger';
                                        $badgeLabel = 'Super Admin';
                                    } elseif ($usu->getTipoUsuario() === 'gerente') {
                                        $badgeClass = 'bg-warning text-dark';
                                        $badgeLabel = 'Gerente';
                                    }
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo $badgeLabel; ?></span>
                                </td>
                                <td>
                                    <?php if ($usu->getAtivo()): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($usu->getUsuario() !== 'admin'): ?>
                                        <button class="btn btn-sm btn-warning me-1"
                                            onclick='editarUsuario(<?php echo json_encode([
                                                                        "id_usuario" => $usu->getIdUsuario(),
                                                                        "nome" => $usu->getNome(),
                                                                        "usuario" => $usu->getUsuario(),
                                                                        "tipo_usuario" => $usu->getTipoUsuario(),
                                                                        "ativo" => $usu->getAtivo(),
                                                                        "empresa_id" => $usu->getEmpresaId()
                                                                    ]); ?>)'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($usu->getIdUsuario() != $_SESSION['usuario_id']): ?>
                                            <button class="btn btn-sm btn-danger"
                                                onclick="confirmarExclusao(<?php echo $usu->getIdUsuario(); ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">Protegido</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cadastro/Edição -->
<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitulo">Novo Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="usuarios.php">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="salvar">
                    <input type="hidden" name="id_usuario" id="id_usuario">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nome" class="form-label">Nome Completo *</label>
                            <input type="text" class="form-control" id="nome" name="nome" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="usuario" class="form-label">Login *</label>
                            <input type="text" class="form-control" id="usuario" name="usuario" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="senha" class="form-label">Senha <span id="senhaObrigatorio">*</span></label>
                            <input type="password" class="form-control" id="senha" name="senha">
                            <small class="text-muted" id="senhaDica" style="display:none;">Deixe em branco para manter a senha atual.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="tipo_usuario" class="form-label">Tipo de Usuário *</label>
                            <select class="form-select" id="tipo_usuario" name="tipo_usuario" required>
                                <option value="gerente">Gerente</option>
                                <option value="operador">Operador</option>
                            </select>
                            <small class="text-muted">Super admin só pode criar gerentes e operadores.</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="empresa_id" class="form-label">Empresa *</label>
                            <select class="form-select" id="empresa_id" name="empresa_id" required>
                                <option value="">Selecione uma empresa...</option>
                                <?php foreach ($empresas as $emp): ?>
                                    <?php if ($emp->getAtiva()): ?>
                                        <option value="<?php echo $emp->getIdEmpresa(); ?>">
                                            [<?php echo htmlspecialchars($emp->getCodigo()); ?>] <?php echo htmlspecialchars($emp->getRazaoSocial()); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1" checked>
                                <label class="form-check-label" for="ativo">Usuário Ativo</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Exclusão -->
<div class="modal fade" id="modalExclusao" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir este usuário?</p>
                <p class="text-danger small"><i class="fas fa-exclamation-triangle me-1"></i>Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <form method="POST" action="usuarios.php">
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="id_usuario" id="id_usuario_exclusao">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        $('#tabelaUsuarios').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
            }
        });

        $('#empresa_id').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#modalUsuario'),
            placeholder: 'Selecione uma empresa...',
            allowClear: true,
            width: '100%'
        });

        $('#filtroEmpresa').select2({
            theme: 'bootstrap-5',
            placeholder: 'Todas as Empresas',
            allowClear: true,
            width: '100%'
        });
    });

    function abrirModal() {
        $('#modalTitulo').text('Novo Usuário');
        $('#id_usuario').val('');
        $('#nome').val('');
        $('#usuario').val('').prop('readonly', false);
        $('#senha').val('').attr('required', true);
        $('#senhaObrigatorio').show();
        $('#senhaDica').hide();
        $('#tipo_usuario').val('gerente');
        $('#empresa_id').val('').trigger('change');
        $('#ativo').prop('checked', true);
        $('#modalUsuario').one('shown.bs.modal', function() {
            $('#nome').focus();
        });
        $('#modalUsuario').modal('show');
    }

    function editarUsuario(u) {
        $('#modalTitulo').text('Editar Usuário');
        $('#id_usuario').val(u.id_usuario);
        $('#nome').val(u.nome);
        $('#usuario').val(u.usuario).prop('readonly', true);
        $('#senha').val('').removeAttr('required');
        $('#senhaObrigatorio').hide();
        $('#senhaDica').show();
        $('#tipo_usuario').val(u.tipo_usuario);
        $('#empresa_id').val(u.empresa_id).trigger('change');
        $('#ativo').prop('checked', u.ativo == 1);
        $('#modalUsuario').one('shown.bs.modal', function() {
            $('#nome').focus();
        });
        $('#modalUsuario').modal('show');
    }

    function confirmarExclusao(id) {
        $('#id_usuario_exclusao').val(id);
        $('#modalExclusao').modal('show');
    }
</script>
</body>

</html>