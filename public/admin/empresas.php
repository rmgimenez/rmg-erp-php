<?php
require_once __DIR__ . '/../../app/controllers/LoginController.php';
require_once __DIR__ . '/../../app/controllers/EmpresaController.php';

$loginController = new LoginController();
$loginController->verificarSuperAdmin();

$empresaController = new EmpresaController();

$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        if ($_POST['acao'] === 'salvar') {
            $resultado = $empresaController->salvar($_POST);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['sucesso'] ? 'success' : 'danger';
        } elseif ($_POST['acao'] === 'excluir') {
            $id = $_POST['id_empresa'] ?? 0;
            $resultado = $empresaController->excluir($id);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['sucesso'] ? 'success' : 'danger';
        }
    }
}

$empresas = $empresaController->listarEmpresas();

$usuarioNome = $_SESSION['usuario_nome'] ?? 'Admin';
$tipoUsuario = $_SESSION['usuario_tipo'] ?? 'super_admin';

$pageTitle = 'Gerenciar Empresas - RMG ERP SaaS';
$extraCss = '<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">';
include __DIR__ . '/../includes/header.php';
?>

<!-- Main Content -->
<div class="container mt-4">

    <div class="row mb-3">
        <div class="col-md-6">
            <h2><i class="fas fa-building me-2"></i> Empresas</h2>
        </div>
        <div class="col-md-6 text-end">
            <button type="button" class="btn btn-primary" onclick="abrirModal()">
                <i class="fas fa-plus me-1"></i> Nova Empresa
            </button>
        </div>
    </div>

    <?php if ($mensagem): ?>
        <div class="alert alert-<?php echo $tipoMensagem; ?> alert-dismissible fade show" role="alert">
            <?php echo $mensagem; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelaEmpresas" class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th width="5%">ID</th>
                            <th width="10%">Código</th>
                            <th>Razão Social</th>
                            <th>Nome Fantasia</th>
                            <th>CNPJ</th>
                            <th>Telefone</th>
                            <th width="8%">Status</th>
                            <th width="15%" class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empresas as $emp): ?>
                            <tr>
                                <td><?php echo $emp->getIdEmpresa(); ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($emp->getCodigo()); ?></span></td>
                                <td><?php echo htmlspecialchars($emp->getRazaoSocial()); ?></td>
                                <td><?php echo htmlspecialchars($emp->getNomeFantasia()); ?></td>
                                <td><?php echo htmlspecialchars($emp->getCnpj()); ?></td>
                                <td><?php echo htmlspecialchars($emp->getTelefone()); ?></td>
                                <td>
                                    <?php if ($emp->getAtiva()): ?>
                                        <span class="badge bg-success">Ativa</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativa</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-warning me-1"
                                        onclick='editarEmpresa(<?php echo json_encode([
                                                                    "id_empresa" => $emp->getIdEmpresa(),
                                                                    "codigo" => $emp->getCodigo(),
                                                                    "razao_social" => $emp->getRazaoSocial(),
                                                                    "nome_fantasia" => $emp->getNomeFantasia(),
                                                                    "cnpj" => $emp->getCnpj(),
                                                                    "telefone" => $emp->getTelefone(),
                                                                    "email" => $emp->getEmail(),
                                                                    "ativa" => $emp->getAtiva(),
                                                                    "observacoes" => $emp->getObservacoes()
                                                                ]); ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger"
                                        onclick="confirmarExclusao(<?php echo $emp->getIdEmpresa(); ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
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
<div class="modal fade" id="modalEmpresa" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitulo">Nova Empresa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="empresas.php">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="salvar">
                    <input type="hidden" name="id_empresa" id="id_empresa">

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="codigo" class="form-label">Código da Empresa *</label>
                            <input type="text" class="form-control" id="codigo" name="codigo" required
                                maxlength="20" style="text-transform: uppercase;"
                                placeholder="Ex: EMP001">
                            <small class="text-muted">Código único para login dos usuários</small>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label for="razao_social" class="form-label">Razão Social *</label>
                            <input type="text" class="form-control" id="razao_social" name="razao_social" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nome_fantasia" class="form-label">Nome Fantasia</label>
                            <input type="text" class="form-control" id="nome_fantasia" name="nome_fantasia">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="cnpj" class="form-label">CNPJ</label>
                            <input type="text" class="form-control" id="cnpj" name="cnpj">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="telefone" class="form-label">Telefone</label>
                            <input type="text" class="form-control" id="telefone" name="telefone">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" id="ativa" name="ativa" value="1" checked>
                                <label class="form-check-label" for="ativa">Empresa Ativa</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="observacoes" class="form-label">Observações</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="3"></textarea>
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
                <p>Tem certeza que deseja excluir esta empresa?</p>
                <p class="text-danger small"><i class="fas fa-exclamation-triangle me-1"></i>Esta ação não pode ser desfeita. A empresa só pode ser excluída se não possuir usuários vinculados.</p>
            </div>
            <div class="modal-footer">
                <form method="POST" action="empresas.php">
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="id_empresa" id="id_empresa_exclusao">
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

<script>
    $(document).ready(function() {
        $('#tabelaEmpresas').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
            }
        });
    });

    function abrirModal() {
        $('#modalTitulo').text('Nova Empresa');
        $('#id_empresa').val('');
        $('#codigo').val('').prop('readonly', false);
        $('#razao_social').val('');
        $('#nome_fantasia').val('');
        $('#cnpj').val('');
        $('#telefone').val('');
        $('#email').val('');
        $('#ativa').prop('checked', true);
        $('#observacoes').val('');
        $('#modalEmpresa').one('shown.bs.modal', function() {
            $('#codigo').focus();
        });
        $('#modalEmpresa').modal('show');
    }

    function editarEmpresa(e) {
        $('#modalTitulo').text('Editar Empresa');
        $('#id_empresa').val(e.id_empresa);
        $('#codigo').val(e.codigo).prop('readonly', true); // Código não pode ser alterado
        $('#razao_social').val(e.razao_social);
        $('#nome_fantasia').val(e.nome_fantasia);
        $('#cnpj').val(e.cnpj);
        $('#telefone').val(e.telefone);
        $('#email').val(e.email);
        $('#ativa').prop('checked', e.ativa == 1);
        $('#observacoes').val(e.observacoes);
        $('#modalEmpresa').one('shown.bs.modal', function() {
            $('#razao_social').focus();
        });
        $('#modalEmpresa').modal('show');
    }

    function confirmarExclusao(id) {
        $('#id_empresa_exclusao').val(id);
        $('#modalExclusao').modal('show');
    }
</script>
</body>

</html>