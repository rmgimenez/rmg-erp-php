<?php
require_once __DIR__ . '/../app/controllers/LoginController.php';
require_once __DIR__ . '/../app/controllers/ContaReceberController.php';
require_once __DIR__ . '/../app/controllers/ClienteController.php';

$loginController = new LoginController();
$loginController->verificarLogado();

$contaController = new ContaReceberController();
$clienteController = new ClienteController();

$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        if ($_POST['acao'] === 'salvar') {
            $resultado = $contaController->salvar($_POST);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['sucesso'] ? 'success' : 'danger';
        } elseif ($_POST['acao'] === 'excluir') {
            $id = $_POST['id_conta_receber'] ?? 0;
            $resultado = $contaController->excluir($id);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['sucesso'] ? 'success' : 'danger';
        } elseif ($_POST['acao'] === 'receber') {
            $resultado = $contaController->registrarRecebimento($_POST);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['sucesso'] ? 'success' : 'danger';
        }
    }
}

$contas = $contaController->listarContas();
$clientes = $clienteController->listarClientes();

$usuarioNome = $_SESSION['usuario_nome'] ?? 'Usuário';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contas a Receber - RMG ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <?php include __DIR__ . '/includes/menu.php'; ?>

    <div class="container mt-4">
        
        <div class="row mb-3">
            <div class="col-md-6">
                <h2><i class="fas fa-hand-holding-usd me-2"></i> Contas a Receber</h2>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-success" onclick="abrirModal()">
                    <i class="fas fa-plus me-1"></i> Nova Conta
                </button>
            </div>
        </div>

        <?php if ($mensagem): ?>
        <div class="alert alert-<?php echo $tipoMensagem; ?> alert-dismissible fade show" role="alert">
            <?php echo $mensagem; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Filtros Rapidos -->
        <div class="card shadow-sm mb-3">
            <div class="card-body py-2">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <span class="me-3 fw-bold text-secondary"><i class="fas fa-filter"></i> Filtros:</span>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="chkOcultarRecebidas" checked>
                            <label class="form-check-label" for="chkOcultarRecebidas">Ocultar Recebidas</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="chkApenasVencidas">
                            <label class="form-check-label text-danger" for="chkApenasVencidas">Apenas Vencidas</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tabelaContas" class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Descrição</th>
                                <th>Cliente</th>
                                <th>Vencimento</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th width="15%" class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contas as $c): ?>
                            <tr>
                                <td><?php echo $c->getIdContaReceber(); ?></td>
                                <td><?php echo htmlspecialchars($c->getDescricao()); ?></td>
                                <td><?php echo htmlspecialchars($c->getNomeCliente()); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($c->getDataVencimento())); ?></td>
                                <td>R$ <?php echo number_format($c->getValor(), 2, ',', '.'); ?></td>
                                <td>
                                    <?php if ($c->getStatus() == 'recebida'): ?>
                                        <span class="badge bg-success">Recebida</span>
                                    <?php else: ?>
                                        <?php 
                                            $hoje = date('Y-m-d');
                                            $vencimento = $c->getDataVencimento();
                                            $classeBadge = ($vencimento < $hoje) ? 'bg-danger' : 'bg-warning text-dark';
                                            $texto = ($vencimento < $hoje) ? 'Vencida' : 'Pendente';
                                        ?>
                                        <span class="badge <?php echo $classeBadge; ?>"><?php echo $texto; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($c->getStatus() !== 'recebida'): ?>
                                    <button class="btn btn-sm btn-success me-1" 
                                            onclick='receberConta(<?php echo json_encode([
                                                "id_conta_receber" => $c->getIdContaReceber(),
                                                "valor" => $c->getValor()
                                            ]); ?>)' title="Registrar Recebimento">
                                        <i class="fas fa-hand-holding-usd"></i>
                                    </button>
                                    <?php endif; ?>

                                    <button class="btn btn-sm btn-warning me-1" 
                                            onclick='editarConta(<?php echo json_encode([
                                                "id_conta_receber" => $c->getIdContaReceber(),
                                                "cliente_id" => $c->getClienteId(),
                                                "descricao" => $c->getDescricao(),
                                                "valor" => $c->getValor(),
                                                "data_vencimento" => $c->getDataVencimento(),
                                                "status" => $c->getStatus(),
                                                "observacoes" => $c->getObservacoes()
                                            ]); ?>)' title="Editar" <?php echo ($c->getStatus() == 'recebida') ? 'disabled' : ''; ?>>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="confirmarExclusao(<?php echo $c->getIdContaReceber(); ?>)" title="Excluir">
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
    <div class="modal fade" id="modalConta" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitulo">Nova Conta a Receber</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="contas_receber.php">
                    <div class="modal-body">
                        <input type="hidden" name="acao" value="salvar">
                        <input type="hidden" name="id_conta_receber" id="id_conta_receber">
                        
                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição *</label>
                            <input type="text" class="form-control" id="descricao" name="descricao" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cliente_id" class="form-label">Cliente *</label>
                                <select class="form-select" id="cliente_id" name="cliente_id" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($clientes as $c): ?>
                                    <option value="<?php echo $c->getIdCliente(); ?>">
                                        <?php echo htmlspecialchars($c->getNome()); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="pendente">Pendente</option>
                                    <option value="recebida">Recebida</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="data_vencimento" class="form-label">Data de Vencimento *</label>
                                <input type="date" class="form-control" id="data_vencimento" name="data_vencimento" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="valor" class="form-label">Valor (R$) *</label>
                                <input type="number" step="0.01" class="form-control" id="valor" name="valor" required>
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

    <!-- Modal Recebimento -->
    <div class="modal fade" id="modalRecebimento" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Registrar Recebimento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="contas_receber.php">
                    <div class="modal-body">
                        <input type="hidden" name="acao" value="receber">
                        <input type="hidden" name="id_conta_receber" id="id_conta_receber_recebimento">
                        
                        <div class="mb-3">
                            <label for="data_recebimento" class="form-label">Data do Recebimento *</label>
                            <input type="date" class="form-control" id="data_recebimento" name="data_recebimento" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="valor_recebido" class="form-label">Valor Recebido (R$) *</label>
                            <input type="number" step="0.01" class="form-control" id="valor_recebido" name="valor_recebido" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Confirmar Recebimento</button>
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
                    <p>Tem certeza que deseja excluir esta conta?</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" action="contas_receber.php">
                        <input type="hidden" name="acao" value="excluir">
                        <input type="hidden" name="id_conta_receber" id="id_conta_receber_exclusao">
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
        // Custom filtering function which will search data in column 5 (Status)
        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex) {
                var status = data[5]; // Status column is index 5
                
                // Get checkbox states
                var ocultarRecebidas = $('#chkOcultarRecebidas').is(':checked');
                var apenasVencidas = $('#chkApenasVencidas').is(':checked');

                // Logic for "Ocultar Recebidas" (Contains 'Recebida')
                if (ocultarRecebidas && status.includes('Recebida')) {
                    return false;
                }

                // Logic for "Apenas Vencidas" (Contains 'Vencida')
                if (apenasVencidas && !status.includes('Vencida')) {
                    return false;
                }

                return true;
            }
        );

        $(document).ready(function() {
            var table = $('#tabelaContas').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
                },
                order: [[3, 'asc']] // Order by Vencimento
            });

            // Event listeners for checkboxes to redraw table
            $('#chkOcultarRecebidas, #chkApenasVencidas').change(function() {
                table.draw();
            });
        });

        function abrirModal() {
            $('#modalTitulo').text('Nova Conta a Receber');
            $('#id_conta_receber').val('');
            $('#descricao').val('');
            $('#cliente_id').val('');
            $('#data_vencimento').val('');
            $('#valor').val('');
            $('#status').val('pendente');
            $('#observacoes').val('');
            $('#modalConta').modal('show');
        }

        function editarConta(c) {
            $('#modalTitulo').text('Editar Conta a Receber');
            $('#id_conta_receber').val(c.id_conta_receber);
            $('#descricao').val(c.descricao);
            $('#cliente_id').val(c.cliente_id);
            $('#data_vencimento').val(c.data_vencimento);
            $('#valor').val(c.valor);
            $('#status').val(c.status);
            $('#observacoes').val(c.observacoes);
            $('#modalConta').modal('show');
        }

        function receberConta(c) {
            $('#id_conta_receber_recebimento').val(c.id_conta_receber);
            $('#valor_recebido').val(c.valor);
            $('#modalRecebimento').modal('show');
        }

        function confirmarExclusao(id) {
            $('#id_conta_receber_exclusao').val(id);
            $('#modalExclusao').modal('show');
        }
    </script>
</body>
</html>