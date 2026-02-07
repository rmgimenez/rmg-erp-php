<?php
require_once __DIR__ . '/../app/controllers/LoginController.php';
require_once __DIR__ . '/../app/controllers/BemController.php';
require_once __DIR__ . '/../app/controllers/SetorController.php';

$loginController = new LoginController();
$loginController->verificarLogado();

$bemController = new BemController();
$setorController = new SetorController();

$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        if ($_POST['acao'] === 'salvar') {
            $resultado = $bemController->salvar($_POST);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['sucesso'] ? 'success' : 'danger';
        } elseif ($_POST['acao'] === 'excluir') {
            $id = $_POST['id_bem'] ?? 0;
            $resultado = $bemController->excluir($id);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['sucesso'] ? 'success' : 'danger';
        }
    }
}

$bens = $bemController->listarBens();
$setores = $setorController->listarSetores();

$usuarioNome = $_SESSION['usuario_nome'] ?? 'Usuário';
$tipoUsuario = $_SESSION['usuario_tipo'] ?? 'visitante';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Bens - RMG ERP</title>
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
                <h2><i class="fas fa-boxes me-2"></i> Bens e Equipamentos</h2>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-primary" onclick="abrirModal()">
                    <i class="fas fa-plus me-1"></i> Novo Bem
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
                    <table id="tabelaBens" class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th width="5%">ID</th>
                                <th>Descrição</th>
                                <th>Setor</th>
                                <th>Aquisição</th>
                                <th>Valor Aquis.</th>
                                <th>Total Manut.</th>
                                <th>Status</th>
                                <th width="20%" class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bens as $b): ?>
                            <tr>
                                <td><?php echo $b->getIdBem(); ?></td>
                                <td><?php echo htmlspecialchars($b->getDescricao()); ?></td>
                                <td><?php echo htmlspecialchars($b->getNomeSetor()); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($b->getDataAquisicao())); ?></td>
                                <td>R$ <?php echo number_format($b->getValorAquisicao(), 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format($b->getTotalManutencao(), 2, ',', '.'); ?></td>
                                <td>
                                    <?php if ($b->getStatus() == 'ativo'): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Baixado</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="manutencoes.php?bem_id=<?php echo $b->getIdBem(); ?>" class="btn btn-sm btn-info text-white me-1" title="Manutenções">
                                        <i class="fas fa-tools"></i>
                                    </a>
                                    <button class="btn btn-sm btn-warning me-1" 
                                            onclick='editarBem(<?php echo json_encode([
                                                "id_bem" => $b->getIdBem(),
                                                "descricao" => $b->getDescricao(),
                                                "setor_id" => $b->getSetorId(),
                                                "data_aquisicao" => $b->getDataAquisicao(),
                                                "valor_aquisicao" => $b->getValorAquisicao(),
                                                "status" => $b->getStatus(),
                                                "observacoes" => $b->getObservacoes()
                                            ]); ?>)' title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="confirmarExclusao(<?php echo $b->getIdBem(); ?>)" title="Excluir">
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
    <div class="modal fade" id="modalBem" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitulo">Novo Bem</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="bens.php">
                    <div class="modal-body">
                        <input type="hidden" name="acao" value="salvar">
                        <input type="hidden" name="id_bem" id="id_bem">
                        
                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição *</label>
                            <input type="text" class="form-control" id="descricao" name="descricao" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="setor_id" class="form-label">Setor *</label>
                                <select class="form-select" id="setor_id" name="setor_id" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($setores as $s): ?>
                                    <option value="<?php echo $s->getIdSetor(); ?>">
                                        <?php echo htmlspecialchars($s->getNome()); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="ativo">Ativo</option>
                                    <option value="baixado">Baixado (Inativo)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="data_aquisicao" class="form-label">Data de Aquisição *</label>
                                <input type="date" class="form-control" id="data_aquisicao" name="data_aquisicao" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="valor_aquisicao" class="form-label">Valor de Aquisição (R$) *</label>
                                <input type="number" step="0.01" class="form-control" id="valor_aquisicao" name="valor_aquisicao" required>
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
                    <p>Tem certeza que deseja excluir este bem?</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" action="bens.php">
                        <input type="hidden" name="acao" value="excluir">
                        <input type="hidden" name="id_bem" id="id_bem_exclusao">
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
            $('#tabelaBens').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
                }
            });
        });

        function abrirModal() {
            $('#modalTitulo').text('Novo Bem');
            $('#id_bem').val('');
            $('#descricao').val('');
            $('#setor_id').val('');
            $('#data_aquisicao').val('');
            $('#valor_aquisicao').val('');
            $('#status').val('ativo');
            $('#observacoes').val('');
            $('#modalBem').modal('show');
        }

        function editarBem(b) {
            $('#modalTitulo').text('Editar Bem');
            $('#id_bem').val(b.id_bem);
            $('#descricao').val(b.descricao);
            $('#setor_id').val(b.setor_id);
            $('#data_aquisicao').val(b.data_aquisicao);
            $('#valor_aquisicao').val(b.valor_aquisicao);
            $('#status').val(b.status);
            $('#observacoes').val(b.observacoes);
            $('#modalBem').modal('show');
        }

        function confirmarExclusao(id) {
            $('#id_bem_exclusao').val(id);
            $('#modalExclusao').modal('show');
        }
    </script>
</body>
</html>