<?php
require_once __DIR__ . '/../app/controllers/LoginController.php';
require_once __DIR__ . '/../app/controllers/ManutencaoController.php';
require_once __DIR__ . '/../app/controllers/BemController.php';

$loginController = new LoginController();
$loginController->verificarLogado();
$loginController->verificarAcessoEmpresa();

$manutencaoController = new ManutencaoController();
$bemController = new BemController();

$bemId = isset($_GET['bem_id']) ? $_GET['bem_id'] : null;
$bemAtual = null;

if ($bemId) {
    $bemAtual = $bemController->buscarPorId($bemId);
    $manutencoes = $manutencaoController->listarPorBem($bemId);
} else {
    $manutencoes = $manutencaoController->listarTodas();
}

// Para o dropdown de bens no modal (apenas caso estejamos listando tudo ou editando)
$todosBens = $bemController->listarBens();

$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        if ($_POST['acao'] === 'salvar') {
            $resultado = $manutencaoController->salvar($_POST);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['sucesso'] ? 'success' : 'danger';

            // Recarrega lista
            if ($bemId) {
                $manutencoes = $manutencaoController->listarPorBem($bemId);
            } else {
                $manutencoes = $manutencaoController->listarTodas();
            }
        } elseif ($_POST['acao'] === 'excluir') {
            $id = $_POST['id_manutencao'] ?? 0;
            $resultado = $manutencaoController->excluir($id);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['sucesso'] ? 'success' : 'danger';

            // Recarrega lista
            if ($bemId) {
                $manutencoes = $manutencaoController->listarPorBem($bemId);
            } else {
                $manutencoes = $manutencaoController->listarTodas();
            }
        }
    }
}

$usuarioNome = $_SESSION['usuario_nome'] ?? 'Usuário';
$tipoUsuario = $_SESSION['usuario_tipo'] ?? 'visitante';

$pageTitle = 'Gerenciar Manutenções - RMG ERP';
$extraCss = '<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">';
include __DIR__ . '/includes/header.php';
?>

<!-- Main Content -->
<div class="container mt-4">

    <div class="row mb-3 align-items-center">
        <div class="col-md-8">
            <?php if ($bemAtual): ?>
                <h2><i class="fas fa-tools me-2"></i> Manutenções: <small class="text-muted"><?php echo htmlspecialchars($bemAtual->getDescricao()); ?></small></h2>
                <a href="bens.php" class="btn btn-sm btn-outline-secondary mb-2"><i class="fas fa-arrow-left"></i> Voltar para Bens</a>
            <?php else: ?>
                <h2><i class="fas fa-tools me-2"></i> Todas as Manutenções</h2>
            <?php endif; ?>
        </div>
        <div class="col-md-4 text-end">
            <button type="button" class="btn btn-primary" onclick="abrirModal()">
                <i class="fas fa-plus me-1"></i> Nova Manutenção
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
                <table id="tabelaManutencoes" class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th width="5%">ID</th>
                            <?php if (!$bemAtual): ?>
                                <th>Bem/Equipamento</th>
                            <?php endif; ?>
                            <th>Data</th>
                            <th>Descrição da Manutenção</th>
                            <th>Custo</th>
                            <th width="15%" class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($manutencoes as $m): ?>
                            <tr>
                                <td><?php echo $m->getIdManutencao(); ?></td>
                                <?php if (!$bemAtual): ?>
                                    <td><?php echo htmlspecialchars($m->getDescricaoBem()); ?></td>
                                <?php endif; ?>
                                <td><?php echo date('d/m/Y', strtotime($m->getDataManutencao())); ?></td>
                                <td><?php echo htmlspecialchars($m->getDescricao()); ?></td>
                                <td>R$ <?php echo number_format($m->getCusto(), 2, ',', '.'); ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-warning me-1"
                                        onclick='editarManutencao(<?php echo json_encode([
                                                                        "id_manutencao" => $m->getIdManutencao(),
                                                                        "bem_id" => $m->getBemId(),
                                                                        "data_manutencao" => $m->getDataManutencao(),
                                                                        "descricao" => $m->getDescricao(),
                                                                        "custo" => $m->getCusto(),
                                                                        "observacoes" => $m->getObservacoes()
                                                                    ]); ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger"
                                        onclick="confirmarExclusao(<?php echo $m->getIdManutencao(); ?>)">
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
<div class="modal fade" id="modalManutencao" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitulo">Nova Manutenção</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="manutencoes.php<?php echo $bemId ? '?bem_id=' . $bemId : ''; ?>">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="salvar">
                    <input type="hidden" name="id_manutencao" id="id_manutencao">

                    <div class="mb-3">
                        <label for="bem_id" class="form-label">Bem/Equipamento *</label>
                        <select class="form-select" id="bem_id" name="bem_id" required <?php echo $bemId ? 'readonly' : ''; ?>>
                            <option value="">Selecione...</option>
                            <?php foreach ($todosBens as $b): ?>
                                <option value="<?php echo $b->getIdBem(); ?>" <?php echo ($bemId == $b->getIdBem()) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($b->getDescricao()); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <!-- Se readonly não funcionar como esperado em selects (alguns browsers bloqueiam), 
                                 podemos forçar via hidden field se bemId existir, mas vamos confiar no UX por enquanto -->
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="data_manutencao" class="form-label">Data *</label>
                            <input type="date" class="form-control" id="data_manutencao" name="data_manutencao" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="custo" class="form-label">Custo (R$) *</label>
                            <input type="number" step="0.01" class="form-control" id="custo" name="custo" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição do Serviço *</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="observacoes" class="form-label">Observações</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="2"></textarea>
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
                <p>Tem certeza que deseja excluir esta manutenção?</p>
            </div>
            <div class="modal-footer">
                <form method="POST" action="manutencoes.php<?php echo $bemId ? '?bem_id=' . $bemId : ''; ?>">
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="id_manutencao" id="id_manutencao_exclusao">
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
        $('#tabelaManutencoes').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
            }
        });

        // Hack para select "readonly"
        <?php if ($bemId): ?>
            $('#bem_id').on('mousedown', function(e) {
                e.preventDefault();
                this.blur();
                window.focus();
            });
        <?php endif; ?>
    });

    function abrirModal() {
        $('#modalTitulo').text('Nova Manutenção');
        $('#id_manutencao').val('');

        // Se bem_id veio na URL, mantem selecionado
        <?php if ($bemId): ?>
            $('#bem_id').val('<?php echo $bemId; ?>');
        <?php else: ?>
            $('#bem_id').val('');
        <?php endif; ?>

        $('#data_manutencao').val('');
        $('#custo').val('');
        $('#descricao').val('');
        $('#observacoes').val('');
        $('#modalManutencao').one('shown.bs.modal', function() {
            <?php if ($bemId): ?>
                $('#data_manutencao').focus();
            <?php else: ?>
                $('#bem_id').focus();
            <?php endif; ?>
        });
        $('#modalManutencao').modal('show');
    }

    function editarManutencao(m) {
        $('#modalTitulo').text('Editar Manutenção');
        $('#id_manutencao').val(m.id_manutencao);
        $('#bem_id').val(m.bem_id);
        $('#data_manutencao').val(m.data_manutencao);
        $('#custo').val(m.custo);
        $('#descricao').val(m.descricao);
        $('#observacoes').val(m.observacoes);
        $('#modalManutencao').one('shown.bs.modal', function() {
            $('#data_manutencao').focus();
        });
        $('#modalManutencao').modal('show');
    }

    function confirmarExclusao(id) {
        $('#id_manutencao_exclusao').val(id);
        $('#modalExclusao').modal('show');
    }
</script>
</body>

</html>