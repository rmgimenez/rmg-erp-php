<?php
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/MenuModel.php';

use CantinaFinanceiro\Auth;
use CantinaFinanceiro\MenuModel;

Auth::checkAuth();
Auth::restrictTo(['gerente', 'operador', 'admin', 'nutricionista']);

$db = CantinaFinanceiro\Database::getConnection();

$cardapios = MenuModel::getAll();

$idSelecionado = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idSelecionado <= 0 && !empty($cardapios)) {
    $idSelecionado = (int)$cardapios[0]['id'];
}

$cardapioAtivo = null;
if ($idSelecionado > 0) {
    $cardapioAtivo = MenuModel::getById($idSelecionado);
}

$stmtCheckKey = $db->query("SELECT valor FROM configuracoes WHERE chave = 'openrouter_api_key' LIMIT 1");
$apiKeyConfigurada = !empty($stmtCheckKey->fetchColumn());

$diasDisplay = [
    'segunda' => 'Segunda-feira',
    'terca' => 'Terça-feira',
    'quarta' => 'Quarta-feira',
    'quinta' => 'Quinta-feira',
    'sexta' => 'Sexta-feira'
];

function convertMarkdownToList($text) {
    if (empty($text)) return '<span class="text-muted">Não informado</span>';
    $lines = explode("\n", $text);
    $html = '<ul class="meal-list">';
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        $line = preg_replace('/^[-*]\s+/', '', $line);
        $line = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
        $line = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $line);
        $html .= "<li>{$line}</li>";
    }
    $html .= '</ul>';
    return $html;
}

$dataInicioStr = $cardapioAtivo ? date('d/m/Y', strtotime($cardapioAtivo['data_inicio'])) : '';
$dataFimStr = $cardapioAtivo ? date('d/m/Y', strtotime($cardapioAtivo['data_fim'])) : '';
$dataInicioSlug = $cardapioAtivo ? date('Y-m-d', strtotime($cardapioAtivo['data_inicio'])) : '';
$dataFimSlug = $cardapioAtivo ? date('Y-m-d', strtotime($cardapioAtivo['data_fim'])) : '';

$datasDiasJson = '{}';
if ($cardapioAtivo) {
    $datasDias = [
        'segunda' => date('d/m', strtotime($cardapioAtivo['data_inicio'])),
        'terca' => date('d/m', strtotime($cardapioAtivo['data_inicio'] . ' +1 day')),
        'quarta' => date('d/m', strtotime($cardapioAtivo['data_inicio'] . ' +2 day')),
        'quinta' => date('d/m', strtotime($cardapioAtivo['data_inicio'] . ' +3 day')),
        'sexta' => date('d/m', strtotime($cardapioAtivo['data_inicio'] . ' +4 day')),
    ];
    $datasDiasJson = json_encode($datasDias);
}

$pageTitle = 'Cardápio Inteligente IA';
$activePage = 'cardapios';
require_once __DIR__ . '/src/includes/layout_start.php';
?>
<link href="assets/css/cardapios.css" rel="stylesheet">

<?php require_once __DIR__ . '/src/includes/alerts.php'; ?>

<!-- Loader Overlay de IA -->
<div id="ia-loader-overlay">
    <div class="loader-card">
        <div class="ai-pulse-icon">
            <i class="fa-solid fa-brain"></i>
        </div>
        <h4 class="fw-bold mb-2">Nutricionista IA RMG</h4>
        <p class="text-muted small px-3">Por favor, aguarde. Nossa IA está analisando o público estimado, aplicando as regras nutricionais da sua cantina e gerando o melhor cardápio semanal junto com a lista de compras...</p>

        <div class="mt-4">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Processando...</span>
            </div>
            <div class="mt-2 text-primary small fw-semibold">Geralmente leva entre 8 e 15 segundos</div>
        </div>
    </div>
</div>

<!-- Page Header (no-print) -->
<div class="d-none d-md-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h1 class="h3 fw-bold mb-0">Planejamento Nutricional por IA</h1>
        <p class="text-muted small mb-0">Gere cardápios semanais com inteligência artificial de forma rápida, edite e imprima em formato A4.</p>
    </div>
    <div class="text-end">
        <button class="btn btn-premium btn-sm shadow" data-bs-toggle="modal" data-bs-target="#modalGerarCardapio" <?= !$apiKeyConfigurada ? 'disabled' : '' ?>>
            <i class="fa-solid fa-brain me-1"></i> Planejar Nova Semana
        </button>
    </div>
</div>

<!-- Alerta Chave de API Ausente (no-print) -->
<?php if (!$apiKeyConfigurada): ?>
    <div class="alert alert-warning border-0 shadow-sm mb-4 no-print d-flex align-items-center justify-content-between">
        <div>
            <i class="fa-solid fa-triangle-exclamation me-2 fs-5"></i>
            <strong>Atenção:</strong> A chave de API do OpenRouter não foi cadastrada. Por favor, acesse o painel administrativo do sistema para ativá-la.
        </div>
        <?php if ($_SESSION['user_nivel'] === 'admin'): ?>
            <a href="admin.php" class="btn btn-warning btn-sm fw-bold px-3"><i class="fa-solid fa-gears me-1"></i> Configurar Agora</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Alertas dinâmicos de sucesso/erro (no-print) -->
<div id="alert-placeholder" class="no-print"></div>

<?php if ($cardapioAtivo): ?>
<!-- Hidden data container for JS -->
<div id="dados-cardapio"
     data-cardapio-id="<?= $idSelecionado ?>"
     data-data-inicio="<?= $dataInicioStr ?>"
     data-data-fim="<?= $dataFimStr ?>"
     data-data-inicio-slug="<?= $dataInicioSlug ?>"
     data-data-fim-slug="<?= $dataFimSlug ?>"
     data-datas-dias='<?= $datasDiasJson ?>'
     style="display:none"></div>
<?php endif; ?>

<!-- Main Layout: Sidebar Semanal + Visualizador Ativo -->
<div class="row g-4">

    <!-- Histórico de Semanas (Left Sidebar) (no-print) -->
    <div class="col-lg-3 no-print">
        <div class="card card-glass p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 text-primary">Histórico Semanal</h6>
                <span class="badge bg-light text-secondary"><?= count($cardapios) ?> Semanas</span>
            </div>

            <?php if (empty($cardapios)): ?>
                <div class="text-center py-4 text-muted small">
                    <i class="fa-solid fa-utensils fs-3 mb-2 d-block opacity-25"></i>
                    Nenhum cardápio planejado.
                </div>
            <?php else: ?>
                <div class="list-group list-group-premium border-0" style="max-height: 480px; overflow-y: auto; gap: 6px;">
                    <?php foreach ($cardapios as $c): ?>
                        <?php
                        $ativoClass = ($c['id'] == $idSelecionado) ? 'active shadow-sm border-primary' : 'bg-white';
                        $dataIniFormat = date('d/m', strtotime($c['data_inicio']));
                        $dataFimFormat = date('d/m/Y', strtotime($c['data_fim']));
                        ?>
                        <a href="cardapios.php?id=<?= $c['id'] ?>" class="list-group-item list-group-item-action rounded-3 border p-3 d-flex flex-column gap-1 <?= $ativoClass ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold small <?= ($c['id'] == $idSelecionado) ? 'text-white' : 'text-indigo-950' ?>">
                                    Semana <?= $dataIniFormat ?> a <?= $dataFimFormat ?>
                                </span>
                                <i class="fa-solid fa-chevron-right fs-6 opacity-50 <?= ($c['id'] == $idSelecionado) ? 'text-white' : '' ?>"></i>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Visualizador de Conteúdo do Cardápio Selecionado (Right Container) -->
    <div class="col-lg-9">
        <?php if (!$cardapioAtivo): ?>
            <div class="card card-glass p-5 text-center text-muted no-print">
                <i class="fa-solid fa-brain fs-1 text-primary opacity-25 mb-3 d-block"></i>
                <h5 class="fw-bold">Nenhum Cardápio Ativo</h5>
                <p class="small">Selecione uma semana ao lado ou crie um novo planejamento nutritivo agora mesmo.</p>
                <?php if ($apiKeyConfigurada): ?>
                    <button class="btn btn-premium btn-sm mt-2 px-4 shadow" data-bs-toggle="modal" data-bs-target="#modalGerarCardapio">
                        <i class="fa-solid fa-plus me-1"></i> Planejar Novo Cardápio
                    </button>
                <?php endif; ?>
            </div>
        <?php else: ?>

            <!-- Print Specific Header (apenas para folha de papel) -->
            <div class="d-none print-header">
                <div>
                    <h3 style="margin: 0; font-weight: 700; letter-spacing: -0.5px;">Cantina Colégio Sant'Anna</h3>
                    <p style="margin: 3px 0 0 0; font-size: 10pt; color: #444;">Planejamento Nutricional Semanal</p>
                </div>
                <div style="text-align: right;">
                    <h4 style="margin: 0; font-weight: 600;">CARD&Aacute;PIO SEMANAL</h4>
                    <p style="margin: 3px 0 0 0; font-size: 9pt; color: #444;">Válido de <strong><?= $dataInicioStr ?></strong> até <strong><?= $dataFimStr ?></strong></p>
                </div>
            </div>

            <!-- Card Header no-print -->
            <div class="card card-glass p-4 mb-4 no-print">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <span class="premium-badge mb-1 d-inline-block">Semana Planejada</span>
                        <h4 class="fw-bold mb-0 text-indigo-950">
                            Período de <?= $dataInicioStr ?> a <?= $dataFimStr ?>
                        </h4>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-sm btn-outline-danger" onclick="confirmarExclusao(<?= $idSelecionado ?>)">
                            <i class="fa-solid fa-trash me-1"></i> Excluir
                        </button>
                        <button class="btn btn-sm btn-outline-primary" onclick="imprimirCardapioPDF()">
                            <i class="fa-solid fa-file-pdf me-1"></i> Imprimir Cardápio
                        </button>
                        <button class="btn btn-sm btn-outline-success" onclick="imprimirListaPDF()">
                            <i class="fa-solid fa-basket-shopping me-1"></i> Imprimir Lista
                        </button>
                        <button class="btn btn-sm btn-outline-info" onclick="abrirModalEdicaoMarkdown()">
                            <i class="fa-solid fa-code me-1"></i> Editar Markdown
                        </button>
                    </div>
                </div>

                <div class="mt-3 no-print">
                    <label class="form-label text-muted small fw-semibold"><i class="fa-solid fa-comment-dots me-1"></i> Observações para impressão (opcional)</label>
                    <div class="input-group">
                        <textarea id="observacao-impressao" class="form-control form-control-premium" rows="2"
                                  placeholder="Ex: Semana temática, ingredientes destacados, eventos especiais..."
                                  style="font-size: 0.88rem;"><?= htmlspecialchars($cardapioAtivo['observacao_impressao'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        <button class="btn btn-outline-primary" type="button" onclick="salvarObservacaoImpressao()" title="Salvar observação">
                            <i class="fa-solid fa-check"></i>
                        </button>
                    </div>
                    <div class="form-text small text-muted">Esta observação será impressa no PDF do cardápio.</div>
                </div>

                <?php if (!empty($cardapioAtivo['observacoes_geracao'])): ?>
                    <div class="alert alert-light border border-opacity-50 mt-3 mb-0 small rounded-3 text-secondary">
                        <i class="fa-solid fa-comment-dots text-primary me-1"></i>
                        <strong>Observações da Semana:</strong> <?= htmlspecialchars($cardapioAtivo['observacoes_geracao'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tabs de Navegação -->
            <ul class="nav premium-nav-tabs mb-3 border-bottom no-print" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-cardapio-btn" data-bs-toggle="tab" data-bs-target="#tab-cardapio" type="button" role="tab">
                        <i class="fa-solid fa-utensils me-1"></i> Cardápio
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-lista-btn" data-bs-toggle="tab" data-bs-target="#tab-lista" type="button" role="tab">
                        <i class="fa-solid fa-basket-shopping me-1"></i> Lista de Compras
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- ABA 1: CARDÁPIO -->
                <div class="tab-pane fade show active print-cardapio-container" id="tab-cardapio" role="tabpanel">
                    <div class="card card-glass p-4">
                        <div class="table-responsive">
                            <table class="table table-cardapio mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 15%;">Dia</th>
                                        <th style="width: 42%;">Almoço (Refeição Principal)</th>
                                        <th style="width: 42%;">Lanche</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cardapioAtivo['dias'] as $d): ?>
                                    <tr>
                                        <td>
                                            <strong class="text-indigo-950"><?= $diasDisplay[$d['dia_semana']] ?></strong>
                                            <button class="btn btn-sm btn-outline-primary mt-2 d-block no-print"
                                                    title="Editar Dia"
                                                    onclick="abrirModalEdicaoDia('<?= $d['dia_semana'] ?>')">
                                                <i class="fa-solid fa-pencil"></i> Editar
                                            </button>
                                        </td>
                                        <td>
                                            <div class="d-none" id="raw-principal-<?= $d['dia_semana'] ?>"><?= htmlspecialchars($d['refeicao_principal'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <div id="lbl-principal-<?= $d['dia_semana'] ?>">
                                                <?= convertMarkdownToList($d['refeicao_principal']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-none" id="raw-lanche-<?= $d['dia_semana'] ?>"><?= htmlspecialchars($d['lanche'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <div id="lbl-lanche-<?= $d['dia_semana'] ?>">
                                                <?= convertMarkdownToList($d['lanche']) ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ABA 2: LISTA DE COMPRAS -->
                <div class="tab-pane fade print-portrait-container" id="tab-lista" role="tabpanel">
                    <div class="card card-glass p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3 no-print">
                            <h5 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-basket-shopping me-2"></i> Lista de Compras</h5>
                            <button class="btn btn-sm btn-outline-primary" onclick="abrirModalEdicaoLista()">
                                <i class="fa-solid fa-pencil me-1"></i> Editar Lista
                            </button>
                        </div>

                        <div id="lista-compras-exibicao" class="lista-compras-content">
                            <?php
                            $textoMarkdown = $cardapioAtivo['lista_compras'] ?? '';
                            $textoMarkdown = preg_replace('/### (.*?)\n/', '<h5 class="text-indigo-950 fw-bold mt-3 mb-2 border-bottom pb-1">$1</h5>', $textoMarkdown);
                            $textoMarkdown = preg_replace('/## (.*?)\n/', '<h4 class="text-indigo-950 fw-bold mt-4 mb-2 border-bottom pb-2">$1</h4>', $textoMarkdown);
                            $textoMarkdown = preg_replace('/# (.*?)\n/', '<h3 class="text-indigo-950 fw-bold mt-4 mb-2">$1</h3>', $textoMarkdown);
                            $textoMarkdown = preg_replace('/- (.*?)\n/', '<li class="small mb-1">$1</li>', $textoMarkdown);
                            $textoMarkdown = nl2br($textoMarkdown);
                            echo $textoMarkdown;
                            ?>
                        </div>

                        <div class="d-none" id="raw-lista-compras"><?= htmlspecialchars($cardapioAtivo['lista_compras'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- ==================================================== -->
<!-- MODAIS DO SISTEMA -->
<!-- ==================================================== -->

<!-- Modal: Geração de Cardápio com IA (no-print) -->
<div class="modal fade no-print" id="modalGerarCardapio" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-content-glass border-0">
        <div class="modal-content border-0" style="background: transparent;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-indigo-950"><i class="fa-solid fa-brain text-primary me-2"></i> Planejador Nutricional IA</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formGerarCardapio" onsubmit="gerarCardapioIA(event)">
                <div class="modal-body py-4">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label text-muted small fw-semibold">Segunda-feira (Início) *</label>
                            <input type="date" name="data_inicio" id="modal_data_inicio" class="form-control form-control-premium" required onchange="ajustarSextaFeira(this.value)">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small fw-semibold">Sexta-feira (Fim) *</label>
                            <input type="date" name="data_fim" id="modal_data_fim" class="form-control form-control-premium" required readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Tipo de Refeição a Gerar *</label>
                        <select name="tipo_refeicao" class="form-select form-select-premium" required>
                            <option value="ambos">Almoço e Lanche (Completo)</option>
                            <option value="almoco">Somente Almoço</option>
                            <option value="lanche">Somente Lanche</option>
                        </select>
                        <div class="form-text small text-muted">Escolha as refeições que a inteligência artificial deve planejar.</div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label text-muted small fw-semibold">Diretrizes / Pedidos Especiais da Semana</label>
                        <textarea name="observacoes" class="form-control form-control-premium" rows="4" placeholder="Ex: Semana de frio, prefira sopas e caldos no lanche. Evite carne de porco. Destaque frutas da época."></textarea>
                        <div class="form-text small text-muted">Solicite restrições, ingredientes específicos, temas ou cardápios festivos.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-3" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-premium shadow"><i class="fa-solid fa-wand-magic-sparkles me-1"></i> Gerar via IA</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edição de Dia (no-print) -->
<div class="modal fade no-print" id="modalEditarDia" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-content-glass border-0">
        <div class="modal-content border-0" style="background: transparent;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-indigo-950"><i class="fa-solid fa-pencil text-primary me-2"></i> Editar Pratos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditarDia" onsubmit="salvarDiaManual(event)" data-cardapio-id="<?= $idSelecionado ?>">
                <input type="hidden" name="dia_semana" id="modal_edit_dia">

                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Almoço (Refeição Principal) *</label>
                        <textarea name="refeicao_principal" id="modal_edit_principal" class="form-control form-control-premium" rows="3" required></textarea>
                    </div>
                    <div class="mb-1">
                        <label class="form-label text-muted small fw-semibold">Lanche / Sobremesa *</label>
                        <textarea name="lanche" id="modal_edit_lanche" class="form-control form-control-premium" rows="3" required></textarea>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-premium shadow"><i class="fa-solid fa-check me-1"></i> Salvar Prato</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edição de Lista de Compras (no-print) -->
<div class="modal fade no-print" id="modalEditarLista" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-content-glass modal-lg border-0">
        <div class="modal-content border-0" style="background: transparent;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-indigo-950"><i class="fa-solid fa-basket-shopping text-primary me-2"></i> Ajustar Lista de Compras</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditarLista" onsubmit="salvarListaManual(event)" data-cardapio-id="<?= $idSelecionado ?>">
                <div class="modal-body py-4">
                    <div class="mb-1">
                        <label class="form-label text-muted small fw-semibold">Lista de Compras (Formato de texto / Markdown)</label>
                        <textarea name="lista_compras" id="modal_edit_lista_texto" class="form-control form-control-premium" rows="15" required style="font-family: monospace; font-size: 0.9rem;"></textarea>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-premium shadow"><i class="fa-solid fa-check me-1"></i> Salvar Lista</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edição de Markdown do Cardápio (no-print) -->
<div class="modal fade no-print" id="modalEditarMarkdown" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg border-0">
        <div class="modal-content border-0 shadow-lg" style="background: white; border-radius: 16px;">
            <div class="modal-header border-0 pb-2" style="background: #f8fafc; border-radius: 16px 16px 0 0;">
                <h5 class="modal-title fw-bold text-indigo-950"><i class="fa-solid fa-code text-primary me-2"></i> Editar Cardápio em Markdown</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditarMarkdown" onsubmit="salvarMarkdownManual(event)" data-cardapio-id="<?= $idSelecionado ?>">
                <div class="modal-body py-3">
                    <div class="markdown-editor-container">
                        <div class="markdown-editor-pane">
                            <div class="pane-header">
                                <i class="fa-solid fa-code me-1"></i> Markdown
                            </div>
                            <textarea name="cardapio_markdown" id="modal_edit_markdown_texto" required></textarea>
                        </div>
                        <div class="markdown-preview-pane">
                            <div class="pane-header">
                                <i class="fa-solid fa-eye me-1"></i> Preview
                            </div>
                            <div class="preview-content" id="markdown-preview"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0" style="background: #f8fafc; border-radius: 0 0 16px 16px;">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-premium shadow"><i class="fa-solid fa-check me-1"></i> Salvar Markdown</button>
                </div>
            </form>
        </div>
    </div>
</div>

        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="assets/js/toast.js"></script>
<script src="assets/js/utils.js"></script>
<script src="assets/js/cardapios.js"></script>
<?php require_once __DIR__ . '/src/includes/scripts_footer.php'; ?>
</body>
</html>
