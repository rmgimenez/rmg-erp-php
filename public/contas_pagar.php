<?php
require_once __DIR__ . '/../app/controllers/LoginController.php';
require_once __DIR__ . '/../app/controllers/ContaPagarController.php';
require_once __DIR__ . '/../app/controllers/FornecedorController.php';

$loginController = new LoginController();
$loginController->verificarLogado();

$contaController = new ContaPagarController();
$fornecedorController = new FornecedorController();

$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        if ($_POST['acao'] === 'salvar') {
            $resultado = $contaController->salvar($_POST);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['sucesso'] ? 'success' : 'danger';
        } elseif ($_POST['acao'] === 'excluir') {
            $id = $_POST['id_conta_pagar'] ?? 0;
            $resultado = $contaController->excluir($id);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['sucesso'] ? 'success' : 'danger';
        } elseif ($_POST['acao'] === 'pagar') {
            $resultado = $contaController->registrarPagamento($_POST);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['sucesso'] ? 'success' : 'danger';
        }
    }
}

$contas = $contaController->listarContas();
$fornecedores = $fornecedorController->listarFornecedores();

$usuarioNome = $_SESSION['usuario_nome'] ?? 'Usuário';

$pageTitle = 'Contas a Pagar - RMG ERP';
$extraCss = '<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">';
include __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">

    <div class="row mb-3">
        <div class="col-md-6">
            <h2><i class="fas fa-file-invoice-dollar me-2"></i> Contas a Pagar</h2>
        </div>
        <div class="col-md-6 text-end">
            <button type="button" class="btn btn-danger" onclick="abrirModal()">
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
                        <input class="form-check-input" type="checkbox" id="chkOcultarPagas" checked>
                        <label class="form-check-label" for="chkOcultarPagas">Ocultar Pagas</label>
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
                            <th>Fornecedor</th>
                            <th>Vencimento</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th width="15%" class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contas as $c): ?>
                            <?php
                            // cálculo de vencimento/atraso (somente para exibição)
                            $hoje = date('Y-m-d');
                            $vencimento = $c->getDataVencimento();
                            $isVencida = ($vencimento < $hoje) && $c->getStatus() !== 'paga';
                            $diasAtraso = 0;
                            if ($isVencida) {
                                $diasAtraso = (int) floor((strtotime($hoje) - strtotime($vencimento)) / 86400);
                            }
                            $rowClass = $isVencida ? 'linha-vencida' : '';
                            ?>
                            <tr class="<?php echo $rowClass; ?>">
                                <td><?php echo $c->getIdContaPagar(); ?></td>
                                <td><?php echo htmlspecialchars($c->getDescricao()); ?></td>
                                <td><?php echo htmlspecialchars($c->getNomeFornecedor()); ?></td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($c->getDataVencimento())); ?>
                                    <?php if ($isVencida): ?>
                                        <div class="small text-danger">Vencida há <?php echo $diasAtraso; ?> dia(s)</div>
                                    <?php endif; ?>
                                </td>
                                <td>R$ <?php echo number_format($c->getValor(), 2, ',', '.'); ?></td>
                                <td>
                                    <?php if ($c->getStatus() == 'paga'): ?>
                                        <span class="badge bg-success" title="Paga">Paga</span>
                                    <?php else: ?>
                                        <?php
                                        $classeBadge = ($isVencida) ? 'bg-danger' : 'bg-warning text-dark';
                                        $texto = ($isVencida) ? 'Vencida' : 'Pendente';
                                        $tituloBadge = $isVencida ? sprintf('Vencida há %d dia(s)', $diasAtraso) : $texto;
                                        ?>
                                        <span class="badge <?php echo $classeBadge; ?>" title="<?php echo $tituloBadge; ?>"><?php echo $texto; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($c->getStatus() !== 'paga'): ?>
                                        <button class="btn btn-sm btn-success me-1"
                                            onclick='pagarConta(<?php echo json_encode([
                                                                    "id_conta_pagar" => $c->getIdContaPagar(),
                                                                    "valor" => $c->getValor()
                                                                ]); ?>)' title="Registrar Pagamento">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    <?php endif; ?>

                                    <button class="btn btn-sm btn-warning me-1"
                                        onclick='editarConta(<?php echo json_encode([
                                                                    "id_conta_pagar" => $c->getIdContaPagar(),
                                                                    "fornecedor_id" => $c->getFornecedorId(),
                                                                    "descricao" => $c->getDescricao(),
                                                                    "valor" => $c->getValor(),
                                                                    "data_vencimento" => $c->getDataVencimento(),
                                                                    "status" => $c->getStatus(),
                                                                    "observacoes" => $c->getObservacoes()
                                                                ]); ?>)' title="Editar" <?php echo ($c->getStatus() == 'paga') ? 'disabled' : ''; ?>>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger"
                                        onclick="confirmarExclusao(<?php echo $c->getIdContaPagar(); ?>)" title="Excluir">
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
                <h5 class="modal-title" id="modalTitulo">Nova Conta a Pagar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="contas_pagar.php">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="salvar">
                    <input type="hidden" name="id_conta_pagar" id="id_conta_pagar">

                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição *</label>
                        <input type="text" class="form-control" id="descricao" name="descricao" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fornecedor_id" class="form-label">Fornecedor *</label>
                            <select class="form-select" id="fornecedor_id" name="fornecedor_id" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($fornecedores as $f): ?>
                                    <option value="<?php echo $f->getIdFornecedor(); ?>">
                                        <?php echo htmlspecialchars($f->getNome()); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status *</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="pendente">Pendente</option>
                                <option value="paga">Paga</option>
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

<!-- Modal Pagamento -->
<div class="modal fade" id="modalPagamento" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Pagamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="contas_pagar.php">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="pagar">
                    <input type="hidden" name="id_conta_pagar" id="id_conta_pagar_pagamento">

                    <div class="mb-3">
                        <label for="data_pagamento" class="form-label">Data do Pagamento *</label>
                        <input type="date" class="form-control" id="data_pagamento" name="data_pagamento" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="valor_pago" class="form-label">Valor Pago (R$) *</label>
                        <input type="number" step="0.01" class="form-control" id="valor_pago" name="valor_pago" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Confirmar Pagamento</button>
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
                <form method="POST" action="contas_pagar.php">
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="id_conta_pagar" id="id_conta_pagar_exclusao">
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
    // Custom filtering function which will search data in column 5 (Status)
    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
            var status = data[5]; // Status column is index 5

            // Get checkbox states
            var ocultarPagas = $('#chkOcultarPagas').is(':checked');
            var apenasVencidas = $('#chkApenasVencidas').is(':checked');

            // Logic for "Ocultar Pagas" (Contains 'Paga')
            if (ocultarPagas && status.includes('Paga')) {
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
            order: [
                [3, 'asc']
            ] // Order by Vencimento
        });

        // Event listeners for checkboxes to redraw table
        $('#chkOcultarPagas, #chkApenasVencidas').change(function() {
            table.draw();
        });
    });

    function abrirModal() {
        $('#modalTitulo').text('Nova Conta a Pagar');
        $('#id_conta_pagar').val('');
        $('#descricao').val('');
        $('#fornecedor_id').val('');
        $('#data_vencimento').val('');
        $('#valor').val('');
        $('#status').val('pendente');
        $('#observacoes').val('');
        $('#modalConta').modal('show');
    }

    function editarConta(c) {
        $('#modalTitulo').text('Editar Conta a Pagar');
        $('#id_conta_pagar').val(c.id_conta_pagar);
        $('#descricao').val(c.descricao);
        $('#fornecedor_id').val(c.fornecedor_id);
        $('#data_vencimento').val(c.data_vencimento);
        $('#valor').val(c.valor);
        $('#status').val(c.status);
        $('#observacoes').val(c.observacoes);
        $('#modalConta').modal('show');
    }

    function pagarConta(c) {
        $('#id_conta_pagar_pagamento').val(c.id_conta_pagar);
        $('#valor_pago').val(c.valor);
        $('#modalPagamento').modal('show');
    }

    function confirmarExclusao(id) {
        $('#id_conta_pagar_exclusao').val(id);
        $('#modalExclusao').modal('show');
    }
</script>
</body>

</html>