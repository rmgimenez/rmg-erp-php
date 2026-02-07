<?php
require_once __DIR__ . '/../app/controllers/LoginController.php';
require_once __DIR__ . '/../app/controllers/FornecedorController.php';

$loginController = new LoginController();
$loginController->verificarLogado();

$fornecedorController = new FornecedorController();

$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        if ($_POST['acao'] === 'salvar') {
            $resultado = $fornecedorController->salvar($_POST);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['sucesso'] ? 'success' : 'danger';
        } elseif ($_POST['acao'] === 'excluir') {
            $id = $_POST['id_fornecedor'] ?? 0;
            $resultado = $fornecedorController->excluir($id);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['sucesso'] ? 'success' : 'danger';
        }
    }
}

$fornecedores = $fornecedorController->listarFornecedores();
$usuarioNome = $_SESSION['usuario_nome'] ?? 'Usuário';
$tipoUsuario = $_SESSION['usuario_tipo'] ?? 'visitante';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Fornecedores - RMG ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <!-- Navbar -->
    <?php include __DIR__ . '/includes/menu.php'; ?>

    <!-- Main Content -->
    <div class="container mt-4">
        
        <div class="row mb-3">
            <div class="col-md-6">
                <h2><i class="fas fa-truck me-2"></i> Fornecedores</h2>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-primary" onclick="abrirModal()">
                    <i class="fas fa-plus me-1"></i> Novo Fornecedor
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
                    <table id="tabelaFornecedores" class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th width="5%">ID</th>
                                <th>Nome</th>
                                <th>CNPJ</th>
                                <th>Telefone</th>
                                <th>Email</th>
                                <th width="15%" class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fornecedores as $f): ?>
                            <tr>
                                <td><?php echo $f->getIdFornecedor(); ?></td>
                                <td><?php echo htmlspecialchars($f->getNome()); ?></td>
                                <td><?php echo htmlspecialchars($f->getCnpj()); ?></td>
                                <td><?php echo htmlspecialchars($f->getTelefone()); ?></td>
                                <td><?php echo htmlspecialchars($f->getEmail()); ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-warning me-1" 
                                            onclick='editarFornecedor(<?php echo json_encode([
                                                "id_fornecedor" => $f->getIdFornecedor(),
                                                "nome" => $f->getNome(),
                                                "cnpj" => $f->getCnpj(),
                                                "telefone" => $f->getTelefone(),
                                                "email" => $f->getEmail(),
                                                "observacoes" => $f->getObservacoes()
                                            ]); ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="confirmarExclusao(<?php echo $f->getIdFornecedor(); ?>)">
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
    <div class="modal fade" id="modalFornecedor" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitulo">Novo Fornecedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="fornecedores.php">
                    <div class="modal-body">
                        <input type="hidden" name="acao" value="salvar">
                        <input type="hidden" name="id_fornecedor" id="id_fornecedor">
                        
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome *</label>
                            <input type="text" class="form-control" id="nome" name="nome" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cnpj" class="form-label">CNPJ</label>
                                <input type="text" class="form-control" id="cnpj" name="cnpj">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="telefone" class="form-label">Telefone</label>
                                <input type="text" class="form-control" id="telefone" name="telefone">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
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
                    <p>Tem certeza que deseja excluir este fornecedor?</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" action="fornecedores.php">
                        <input type="hidden" name="acao" value="excluir">
                        <input type="hidden" name="id_fornecedor" id="id_fornecedor_exclusao">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Excluir</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#tabelaFornecedores').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
                }
            });
        });

        function abrirModal() {
            $('#modalTitulo').text('Novo Fornecedor');
            $('#id_fornecedor').val('');
            $('#nome').val('');
            $('#cnpj').val('');
            $('#telefone').val('');
            $('#email').val('');
            $('#observacoes').val('');
            $('#modalFornecedor').modal('show');
        }

        function editarFornecedor(f) {
            $('#modalTitulo').text('Editar Fornecedor');
            $('#id_fornecedor').val(f.id_fornecedor);
            $('#nome').val(f.nome);
            $('#cnpj').val(f.cnpj);
            $('#telefone').val(f.telefone);
            $('#email').val(f.email);
            $('#observacoes').val(f.observacoes);
            $('#modalFornecedor').modal('show');
        }

        function confirmarExclusao(id) {
            $('#id_fornecedor_exclusao').val(id);
            $('#modalExclusao').modal('show');
        }
    </script>
</body>
</html>