<?php
require_once __DIR__ . '/../app/controllers/LoginController.php';
require_once __DIR__ . '/../app/controllers/SetorController.php';

$loginController = new LoginController();
$loginController->verificarLogado();

$setorController = new SetorController();

$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        if ($_POST['acao'] === 'salvar') {
            $resultado = $setorController->salvar($_POST);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['sucesso'] ? 'success' : 'danger';
        } elseif ($_POST['acao'] === 'excluir') {
            $id = $_POST['id_setor'] ?? 0;
            $resultado = $setorController->excluir($id);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['sucesso'] ? 'success' : 'danger';
        }
    }
}

$setores = $setorController->listarSetores();
$usuarioNome = $_SESSION['usuario_nome'] ?? 'Usuário';
$tipoUsuario = $_SESSION['usuario_tipo'] ?? 'visitante';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Setores - RMG ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
</head>
<body class="bg-light">

    <!-- Navbar -->
    <?php include __DIR__ . '/includes/menu.php'; ?>

    <!-- Main Content -->
    <div class="container mt-4">
        
        <div class="row mb-3">
            <div class="col-md-6">
                <h2><i class="fas fa-building me-2"></i> Setores</h2>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-primary" onclick="abrirModal()">
                    <i class="fas fa-plus me-1"></i> Novo Setor
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
                    <table id="tabelaSetores" class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th width="10%">ID</th>
                                <th width="30%">Nome</th>
                                <th>Descrição</th>
                                <th width="15%" class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($setores as $setor): ?>
                            <tr>
                                <td><?php echo $setor->getIdSetor(); ?></td>
                                <td><?php echo htmlspecialchars($setor->getNome()); ?></td>
                                <td><?php echo htmlspecialchars($setor->getDescricao()); ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-warning me-1" 
                                            onclick='editarSetor(<?php echo json_encode([
                                                "id_setor" => $setor->getIdSetor(),
                                                "nome" => $setor->getNome(),
                                                "descricao" => $setor->getDescricao()
                                            ]); ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="confirmarExclusao(<?php echo $setor->getIdSetor(); ?>)">
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
    <div class="modal fade" id="modalSetor" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitulo">Novo Setor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="setores.php">
                    <div class="modal-body">
                        <input type="hidden" name="acao" value="salvar">
                        <input type="hidden" name="id_setor" id="id_setor">
                        
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome do Setor *</label>
                            <input type="text" class="form-control" id="nome" name="nome" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
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
                    <p>Tem certeza que deseja excluir este setor?</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" action="setores.php">
                        <input type="hidden" name="acao" value="excluir">
                        <input type="hidden" name="id_setor" id="id_setor_exclusao">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Excluir</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#tabelaSetores').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
                }
            });
        });

        function abrirModal() {
            $('#modalTitulo').text('Novo Setor');
            $('#id_setor').val('');
            $('#nome').val('');
            $('#descricao').val('');
            $('#modalSetor').modal('show');
        }

        function editarSetor(setor) {
            $('#modalTitulo').text('Editar Setor');
            $('#id_setor').val(setor.id_setor);
            $('#nome').val(setor.nome);
            $('#descricao').val(setor.descricao);
            $('#modalSetor').modal('show');
        }

        function confirmarExclusao(id) {
            $('#id_setor_exclusao').val(id);
            $('#modalExclusao').modal('show');
        }
    </script>
</body>
</html>