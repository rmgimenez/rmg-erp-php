<?php
/**
 * Relatórios Financeiros com Exportação para PDF
 * Permite filtrar contas e exportá-las diretamente em PDF usando a biblioteca html2pdf.js.
 */

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/AccountModel.php';

use CantinaFinanceiro\Database;
use CantinaFinanceiro\Auth;
use CantinaFinanceiro\AccountModel;

Auth::checkAuth();
Auth::restrictTo(['gerente', 'operador']);

$db = Database::getConnection();

// ----------------------------------------------------
// Processamento de Filtros (GET)
// ----------------------------------------------------
$filtros = [
    'tipo' => $_GET['tipo'] ?? '',
    'status' => $_GET['status'] ?? '',
    'categoria_id' => $_GET['categoria_id'] ?? '',
    'fornecedor_id' => $_GET['fornecedor_id'] ?? '',
    'data_inicio' => $_GET['data_inicio'] ?? '',
    'data_fim' => $_GET['data_fim'] ?? '',
    'busca' => $_GET['busca'] ?? ''
];

$listaContas = AccountModel::getAll($filtros);

// ----------------------------------------------------
// Consolidação de Cálculos e Métricas do Filtro Ativo
// ----------------------------------------------------
$totalReceitas = 0;
$totalDespesas = 0;
$totalPendentesPagar = 0;
$totalPendentesReceber = 0;

$resumoCategorias = [];
$resumoFornecedores = [];

foreach ($listaContas as $c) {
    if ($c['tipo'] === 'receber') {
        if ($c['status'] === 'pago') {
            $totalReceitas += $c['valor'];
        } else {
            $totalPendentesReceber += $c['valor'];
        }
    } elseif ($c['tipo'] === 'pagar') {
        if ($c['status'] === 'pago') {
            $totalDespesas += $c['valor'];
        } else {
            $totalPendentesPagar += $c['valor'];
        }
    }

    // Calcula consolidados para resumo do relatório
    $catName = $c['categoria_nome'] ?? 'Não Informado';
    $fornName = $c['fornecedor_nome'] ?? 'Não Informado';

    if (!isset($resumoCategorias[$catName])) {
        $resumoCategorias[$catName] = 0;
    }
    $resumoCategorias[$catName] += $c['valor'];

    if (!empty($c['fornecedor_nome'])) {
        if (!isset($resumoFornecedores[$fornName])) {
            $resumoFornecedores[$fornName] = 0;
        }
        $resumoFornecedores[$fornName] += $c['valor'];
    }
}

arsort($resumoCategorias);
arsort($resumoFornecedores);

$saldoLiquidoEfetivo = $totalReceitas - $totalDespesas;
$saldoLiquidoPrevisto = ($totalReceitas + $totalPendentesReceber) - ($totalDespesas + $totalPendentesPagar);

// Carrega categorias e fornecedores do banco
$stmtCat = $db->query("SELECT id, nome FROM categorias ORDER BY nome ASC");
$categorias = $stmtCat->fetchAll();

$stmtForn = $db->query("SELECT id, nome FROM fornecedores ORDER BY nome ASC");
$fornecedores = $stmtForn->fetchAll();

$pageTitle = 'Relatórios';
$activePage = 'relatorios';
?>
<?php require_once __DIR__ . '/src/includes/layout_start.php'; ?>

<!-- Alertas -->
<?php require_once __DIR__ . '/src/includes/alerts.php'; ?>

            <!-- Page Header -->
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-0">Relatórios Gerenciais</h1>
                    <p class="text-muted small mb-0">Gere demonstrativos e exporte relatórios financeiros personalizados em PDF.</p>
                </div>
                <button onclick="gerarRelatorioPDF()" class="btn btn-premium shadow-sm">
                    <i class="fa-solid fa-file-pdf me-1"></i> Exportar para PDF
                </button>
            </div>

            <!-- Filters Card -->
            <div class="card card-glass p-3 mb-4">
                <h6 class="fw-bold text-muted small mb-3"><i class="fa-solid fa-sliders text-primary me-2"></i> Filtros do Demonstrativo</h6>
                <form method="GET" action="relatorios.php" class="row g-2 align-items-end">
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label text-muted small fw-semibold">Busca Rápida</label>
                        <input type="text" name="busca" class="form-control form-control-premium form-control-sm" placeholder="Buscar..." value="<?= htmlspecialchars($filtros['busca'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label text-muted small fw-semibold">Tipo (Fluxo)</label>
                        <select name="tipo" class="form-select form-select-premium form-select-sm">
                            <option value="">Todos</option>
                            <option value="pagar" <?= $filtros['tipo'] === 'pagar' ? 'selected' : '' ?>>A Pagar (Despesas)</option>
                            <option value="receber" <?= $filtros['tipo'] === 'receber' ? 'selected' : '' ?>>A Receber (Receitas)</option>
                        </select>
                    </div>
                    <div class="col-md-1.5 col-sm-6">
                        <label class="form-label text-muted small fw-semibold">Status</label>
                        <select name="status" class="form-select form-select-premium form-select-sm">
                            <option value="">Todos</option>
                            <option value="pendente" <?= $filtros['status'] === 'pendente' ? 'selected' : '' ?>>Pendentes</option>
                            <option value="pago" <?= $filtros['status'] === 'pago' ? 'selected' : '' ?>>Pagos / Liquidados</option>
                            <option value="cancelado" <?= $filtros['status'] === 'cancelado' ? 'selected' : '' ?>>Cancelados</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label text-muted small fw-semibold">Categoria</label>
                        <select name="categoria_id" class="form-select form-select-premium form-select-sm">
                            <option value="">Todas</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= (string)$filtros['categoria_id'] === (string)$cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label text-muted small fw-semibold">Fornecedor</label>
                        <select name="fornecedor_id" class="form-select form-select-premium form-select-sm">
                            <option value="">Todos</option>
                            <?php foreach ($fornecedores as $forn): ?>
                                <option value="<?= $forn['id'] ?>" <?= (string)$filtros['fornecedor_id'] === (string)$forn['id'] ? 'selected' : '' ?>><?= htmlspecialchars($forn['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-1.5 col-sm-6">
                        <label class="form-label text-muted small fw-semibold">De</label>
                        <input type="date" name="data_inicio" class="form-control form-control-premium form-control-sm" value="<?= htmlspecialchars($filtros['data_inicio'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-1.5 col-sm-6">
                        <label class="form-label text-muted small fw-semibold">Até</label>
                        <input type="date" name="data_fim" class="form-control form-control-premium form-control-sm" value="<?= htmlspecialchars($filtros['data_fim'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-2 col-sm-12 d-flex gap-2">
                        <button type="submit" class="btn btn-premium btn-sm w-100"><i class="fa-solid fa-filter"></i> Filtrar</button>
                        <a href="relatorios.php" class="btn btn-outline-secondary btn-sm w-100"><i class="fa-solid fa-rotate-left"></i> Limpar</a>
                    </div>
                </form>
            </div>

            <!-- ==================================================== -->
            <!-- ÁREA EXPORTÁVEL PARA O PDF -->
            <!-- ==================================================== -->
            <div class="card border-0 shadow-sm p-4 bg-white" id="report-area">
                
                <!-- PDF Header (Apenas exibe no PDF impresso) -->
                <div class="pdf-header" id="pdf-header-element">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2>Demonstrativo Financeiro de Contas</h2>
                            <p class="text-muted small mb-0">Cantina Sant'Anna — Gerado em: <?= date('d/m/Y H:i') ?></p>
                        </div>
                        <div style="font-size: 1.5rem; font-weight: 700; color: #1e1b4b;">
                            Sant'Anna<span style="color: #4f46e5;">.</span>
                        </div>
                    </div>
                </div>

                <h5 class="fw-bold mb-3 d-print-none text-muted small"><i class="fa-solid fa-receipt text-primary me-2"></i> Demonstrativo Gerado</h5>

                <!-- KPIs Row inside Printable Area -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="p-3 border rounded-3 bg-light">
                            <span class="text-muted small uppercase fw-semibold d-block" style="font-size:0.75rem;">Total Receitas Recebidas</span>
                            <span class="fw-bold text-success fs-4">R$ <?= number_format($totalReceitas / 100, 2, ',', '.') ?></span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 border rounded-3 bg-light">
                            <span class="text-muted small uppercase fw-semibold d-block" style="font-size:0.75rem;">Total Despesas Pagas</span>
                            <span class="fw-bold text-danger fs-4">R$ <?= number_format($totalDespesas / 100, 2, ',', '.') ?></span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 border rounded-3 bg-light">
                            <span class="text-muted small uppercase fw-semibold d-block" style="font-size:0.75rem;">Saldo Líquido Efetivo</span>
                            <span class="fw-bold <?= $saldoLiquidoEfetivo >= 0 ? 'text-success' : 'text-danger' ?> fs-4">
                                R$ <?= number_format($saldoLiquidoEfetivo / 100, 2, ',', '.') ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 border rounded-3 bg-light">
                            <span class="text-muted small uppercase fw-semibold d-block" style="font-size:0.75rem;">Saldo Líquido Previsto</span>
                            <span class="fw-bold <?= $saldoLiquidoPrevisto >= 0 ? 'text-primary' : 'text-danger' ?> fs-4">
                                R$ <?= number_format($saldoLiquidoPrevisto / 100, 2, ',', '.') ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Detailed Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0 align-middle" style="font-size: 0.85rem;">
                        <thead class="table-dark">
                            <tr>
                                <th>Data Vencimento</th>
                                <th>Descrição / Fornecedor</th>
                                <th>Fluxo</th>
                                <th>Categoria</th>
                                <th>Status</th>
                                <th>Data Liquidação</th>
                                <th class="text-end">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($listaContas)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Nenhum lançamento corresponde aos filtros indicados.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($listaContas as $c): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($c['data_vencimento'])) ?></td>
                                        <td>
                                            <span class="fw-bold"><?= htmlspecialchars($c['descricao'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php if (!empty($c['fornecedor_nome'])): ?>
                                                <br><small class="text-muted fw-normal"><i class="fa-solid fa-truck-field me-1"></i> <?= htmlspecialchars($c['fornecedor_nome'], ENT_QUOTES, 'UTF-8') ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($c['tipo'] === 'pagar'): ?>
                                                <span class="text-danger fw-semibold">Pagar</span>
                                            <?php else: ?>
                                                <span class="text-success fw-semibold">Receber</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($c['categoria_nome'] ?? 'Não Informado', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <?php if ($c['status'] === 'pago'): ?>
                                                Pago
                                            <?php elseif ($c['status'] === 'cancelado'): ?>
                                                Cancelado
                                            <?php else: ?>
                                                Pendente
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted">
                                            <?= $c['data_liquidacao'] ? date('d/m/Y', strtotime($c['data_liquidacao'])) : 'N/A' ?>
                                        </td>
                                        <td class="text-end fw-bold">
                                            R$ <?= number_format($c['valor'] / 100, 2, ',', '.') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Resumos por Categorias e Fornecedores -->
                <?php if (!empty($listaContas)): ?>
                <div class="row g-3 mt-4">
                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 bg-light bg-opacity-75">
                            <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-tags text-primary me-2"></i> Total por Categoria</h6>
                            <table class="table table-sm table-borderless mb-0" style="font-size: 0.8rem;">
                                <thead>
                                    <tr class="border-bottom"><th class="py-1">Categoria</th><th class="text-end py-1">Total</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resumoCategorias as $catName => $totalVal): ?>
                                        <tr>
                                            <td class="py-1 text-muted"><?= htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-end fw-bold py-1">R$ <?= number_format($totalVal / 100, 2, ',', '.') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 bg-light bg-opacity-75">
                            <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-truck-ramp-box text-primary me-2"></i> Total por Fornecedor</h6>
                            <?php if (empty($resumoFornecedores)): ?>
                                <p class="text-muted small mb-0 py-2">Nenhum fornecedor informado na listagem ativa.</p>
                            <?php else: ?>
                                <table class="table table-sm table-borderless mb-0" style="font-size: 0.8rem;">
                                    <thead>
                                        <tr class="border-bottom"><th class="py-1">Fornecedor</th><th class="text-end py-1">Total</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($resumoFornecedores as $fornName => $totalVal): ?>
                                            <tr>
                                                <td class="py-1 text-muted"><?= htmlspecialchars($fornName, ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="text-end fw-bold py-1">R$ <?= number_format($totalVal / 100, 2, ',', '.') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- PDF Footer (Apenas no PDF) -->
                <div class="text-center mt-4 pt-3 border-top d-none text-muted small" id="pdf-footer-element" style="font-size: 10px;">
                    Este relatório consolidado foi gerado de forma segura pelo sistema **Cantina Sant'Anna**.<br>
                    © <?= date('Y') ?> Cantina Sant'Anna. Todos os direitos reservados.
                </div>
            </div>

<!-- Scripts específicos desta página -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
    /**
     * Dispara a exportação da área de relatórios para PDF
     */
    function gerarRelatorioPDF() {
        const element = document.getElementById('report-area');
        
        // Exibe temporariamente os cabeçalhos e rodapés do PDF no DOM
        const pdfHeader = document.getElementById('pdf-header-element');
        const pdfFooter = document.getElementById('pdf-footer-element');
        
        pdfHeader.style.display = 'block';
        pdfFooter.classList.remove('d-none');

        // Configurações do layout do PDF
        const opt = {
            margin:       10,
            filename:     'relatorio_financeiro_' + new Date().toISOString().slice(0, 10) + '.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'landscape' }
        };

        // Roda o html2pdf
        html2pdf().set(opt).from(element).save().then(() => {
            pdfHeader.style.display = 'none';
            pdfFooter.classList.add('d-none');
        }).catch(err => {
            console.error("Erro na geração do PDF: ", err);
            pdfHeader.style.display = 'none';
            pdfFooter.classList.add('d-none');
        });
    }
</script>

<?php require_once __DIR__ . '/src/includes/layout_end.php'; ?>
