<?php
require_once __DIR__ . '/../app/controllers/LoginController.php';
require_once __DIR__ . '/../app/controllers/UsuarioController.php';

$loginController = new LoginController();
$loginController->verificarLogado();

// Super admin tem sua própria página de usuários
if ($_SESSION['usuario_tipo'] === 'super_admin') {
    header('Location: admin/usuarios.php');
    exit;
}

// Apenas gerentes podem acessar esta página
if ($_SESSION['usuario_tipo'] !== 'gerente') {
    header('Location: index.php');
    exit;
}

$usuarioController = new UsuarioController();

$mensagem = '';
$tipoMensagem = '';

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

$usuarios = $usuarioController->listarUsuarios();
// Variáveis para o menu
$usuarioNome = $_SESSION['usuario_nome'] ?? 'Usuário';
$tipoUsuario = $_SESSION['usuario_tipo'] ?? 'visitante';

$pageTitle = 'Gerenciar Usuários - RMG ERP';
$extraCss = '<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">';
include __DIR__ . '/includes/header.php';
?>

<!-- Main Content -->
<div class="container mt-4">

    <div class="row mb-3">
        <div class="col-md-6">
            <h2><i class="fas fa-users me-2"></i> Usuários</h2>
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

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelaUsuarios" class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th width="5%">ID</th>
                            <th>Nome</th>
                            <th>Login</th>
                            <th>Tipo</th>
                            <th width="10%">Status</th>
                            <th width="15%" class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><?php echo $u->getIdUsuario(); ?></td>
                                <td><?php echo htmlspecialchars($u->getNome()); ?></td>
                                <td><?php echo htmlspecialchars($u->getUsuario()); ?></td>
                                <td>
                                    <?php
                                    $badges = [
                                        'super_admin' => 'bg-danger',
                                        'gerente' => 'bg-warning text-dark',
                                        'operador' => 'bg-info text-dark'
                                    ];
                                    $badgeClass = $badges[$u->getTipoUsuario()] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <?php echo ucfirst($u->getTipoUsuario()); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($u->getAtivo()): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($u->getUsuario() === 'admin'): ?>
                                        <button class="btn btn-sm btn-warning me-1" disabled title='O usuário "admin" não pode ser alterado.'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-secondary" disabled title='O usuário "admin" não pode ser excluído.'>
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-warning me-1"
                                            onclick='editarUsuario(<?php echo json_encode([
                                                                        "id_usuario" => $u->getIdUsuario(),
                                                                        "nome" => $u->getNome(),
                                                                        "usuario" => $u->getUsuario(),
                                                                        "tipo_usuario" => $u->getTipoUsuario(),
                                                                        "ativo" => $u->getAtivo()
                                                                    ]); ?>)'>
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <?php if ($u->getIdUsuario() != $_SESSION['usuario_id']): ?>
                                            <button class="btn btn-sm btn-danger"
                                                onclick="confirmarExclusao(<?php echo $u->getIdUsuario(); ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-secondary" disabled>
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitulo">Novo Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="usuarios.php">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="salvar">
                    <input type="hidden" name="id_usuario" id="id_usuario">

                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome Completo *</label>
                        <input type="text" class="form-control" id="nome" name="nome" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="usuario" class="form-label">Login (Usuário) *</label>
                            <input type="text" class="form-control" id="usuario" name="usuario" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="senha" class="form-label">Senha</label>
                            <input type="password" class="form-control" id="senha" name="senha" placeholder="Deixe vazio para manter">
                            <small class="text-muted d-none" id="avisoSenha">Preencha apenas se quiser alterar.</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tipo_usuario" class="form-label">Tipo de Usuário *</label>
                            <select class="form-select" id="tipo_usuario" name="tipo_usuario" required>
                                <option value="operador">Operador</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3 pt-4">
                            <div class="form-check form-switch">
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

<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        $('#tabelaUsuarios').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
            }
        });
    });

    function abrirModal() {
        $('#modalTitulo').text('Novo Usuário');
        $('#id_usuario').val('');
        $('#nome').val('');
        $('#usuario').val('');
        $('#senha').val('');
        $('#senha').prop('required', true); // Senha obrigatória no cadastro
        $('#avisoSenha').addClass('d-none');
        $('#tipo_usuario').val('operador');
        $('#ativo').prop('checked', true);
        $('#modalUsuario').modal('show');
    }

    function editarUsuario(u) {
        if (u.usuario === 'admin') {
            alert('O usuário "admin" não pode ser alterado.');
            return;
        }
        $('#modalTitulo').text('Editar Usuário');
        $('#id_usuario').val(u.id_usuario);
        $('#nome').val(u.nome);
        $('#usuario').val(u.usuario);
        $('#senha').val('');
        $('#senha').prop('required', false); // Senha opcional na edição
        $('#avisoSenha').removeClass('d-none');
        $('#tipo_usuario').val(u.tipo_usuario);
        $('#ativo').prop('checked', u.ativo == 1);
        $('#modalUsuario').modal('show');
    }

    function confirmarExclusao(id) {
        $('#id_usuario_exclusao').val(id);
        $('#modalExclusao').modal('show');
    }
</script>
</body>

</html>