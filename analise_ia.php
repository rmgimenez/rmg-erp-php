<?php
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/AccountModel.php';
require_once __DIR__ . '/src/AnaliseModel.php';

use CantinaFinanceiro\Auth;
use CantinaFinanceiro\AnaliseModel;

Auth::checkAuth();
Auth::restrictTo(['gerente', 'admin']);

$db = CantinaFinanceiro\Database::getConnection();

$stmtCheckKey = $db->query("SELECT valor FROM configuracoes WHERE chave = 'openrouter_api_key' LIMIT 1");
$apiKeyConfigurada = !empty($stmtCheckKey->fetchColumn());

$pageTitle = 'Análise Inteligente';
$activePage = 'analise_ia';
require_once __DIR__ . '/src/includes/layout_start.php';
?>
<?php require_once __DIR__ . '/src/includes/alerts.php'; ?>

<!-- Loader Overlay de IA -->
<div id="ia-loader-overlay">
    <div class="loader-card">
        <div class="ai-pulse-icon">
            <i class="fa-solid fa-chart-simple"></i>
        </div>
        <h4 class="fw-bold mb-2">Analisando Dados Financeiros</h4>
        <p class="text-muted small px-3" id="ia-loader-text">Por favor, aguarde. Nossa IA está processando os dados financeiros e gerando a análise...</p>
        <div class="mt-4">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Processando...</span>
            </div>
            <div class="mt-2 text-primary small fw-semibold">Geralmente leva entre 8 e 15 segundos</div>
        </div>
    </div>
</div>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h1 class="h3 fw-bold mb-0">Análise Inteligente</h1>
        <p class="text-muted small mb-0">Insights financeiros gerados por inteligência artificial.</p>
    </div>
    <div class="text-end">
        <a href="admin.php" class="btn btn-outline-secondary btn-sm shadow-sm">
            <i class="fa-solid fa-gears me-1"></i> Configurar IA
        </a>
    </div>
</div>

<?php if (!$apiKeyConfigurada): ?>
    <div class="alert alert-warning border-0 shadow-sm mb-4 no-print d-flex align-items-center justify-content-between">
        <div>
            <i class="fa-solid fa-triangle-exclamation me-2 fs-5"></i>
            <strong>Atenção:</strong> A chave de API do OpenRouter não foi cadastrada. Acesse o painel administrativo para ativar as análises por IA.
        </div>
        <a href="admin.php" class="btn btn-warning btn-sm fw-bold px-3"><i class="fa-solid fa-gears me-1"></i> Configurar Agora</a>
    </div>
<?php endif; ?>

<div id="alert-placeholder" class="no-print"></div>

<!-- Tabs de Navegação -->
<ul class="nav premium-nav-tabs mb-3 border-bottom no-print" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-resumo-btn" data-bs-toggle="tab" data-bs-target="#tab-resumo" type="button" role="tab">
            <i class="fa-solid fa-file-lines me-1"></i> Resumo Executivo
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-tendencia-btn" data-bs-toggle="tab" data-bs-target="#tab-tendencia" type="button" role="tab">
            <i class="fa-solid fa-chart-line me-1"></i> Tendências e Previsões
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-perguntas-btn" data-bs-toggle="tab" data-bs-target="#tab-perguntas" type="button" role="tab">
            <i class="fa-solid fa-message me-1"></i> Perguntas
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- ABA 1: RESUMO EXECUTIVO -->
    <div class="tab-pane fade show active" id="tab-resumo" role="tabpanel">
        <div class="card card-glass p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-file-lines me-2"></i> Resumo Financeiro</h5>
                <button class="btn btn-premium btn-sm shadow" onclick="gerarResumo()" <?= !$apiKeyConfigurada ? 'disabled' : '' ?>>
                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Gerar Resumo com IA
                </button>
            </div>
            <div id="resumo-content" class="analise-ia-content">
                <div class="text-center text-muted py-5">
                    <i class="fa-solid fa-file-lines fs-1 text-primary opacity-25 mb-3 d-block"></i>
                    <p class="small">Clique em "Gerar Resumo com IA" para obter uma análise narrativa dos dados financeiros.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ABA 2: TENDÊNCIAS E PREVISÕES -->
    <div class="tab-pane fade" id="tab-tendencia" role="tabpanel">
        <div class="card card-glass p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-chart-line me-2"></i> Projeções e Alertas</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary" onclick="copiarAnalise()" disabled id="btn-copiar-tendencia" title="Copiar análise">
                        <i class="fa-solid fa-copy me-1"></i> Copiar
                    </button>
                    <button class="btn btn-premium btn-sm shadow" onclick="gerarTendencia()" <?= !$apiKeyConfigurada ? 'disabled' : '' ?>>
                        <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Gerar Projeção
                    </button>
                </div>
            </div>
            <div id="tendencia-content" class="analise-ia-content">
                <div class="text-center text-muted py-5">
                    <i class="fa-solid fa-chart-line fs-1 text-primary opacity-25 mb-3 d-block"></i>
                    <p class="small">Clique em "Gerar Projeção" para obter previsões e recomendações baseadas nos dados históricos.</p>
                </div>
            </div>
            <div class="mt-3 text-muted small fst-italic" id="tendencia-disclaimer" style="display:none;">
                <i class="fa-solid fa-info-circle me-1"></i> Análise gerada por IA com base em dados históricos. Não substitui aconselhamento profissional.
            </div>
        </div>
    </div>

    <!-- ABA 3: PERGUNTAS -->
    <div class="tab-pane fade" id="tab-perguntas" role="tabpanel">
        <div class="card card-glass p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-message me-2"></i> Faça uma Pergunta</h5>
                <button class="btn btn-outline-danger btn-sm" onclick="limparConversa()" id="btn-limpar-conversa" style="display:none;">
                    <i class="fa-solid fa-eraser me-1"></i> Limpar Conversa
                </button>
            </div>
            <div id="perguntas-chat" class="analise-ia-chat mb-3">
                <div class="text-center text-muted py-5" id="perguntas-placeholder">
                    <i class="fa-solid fa-message fs-1 text-primary opacity-25 mb-3 d-block"></i>
                    <p class="small">Faça uma pergunta sobre seus dados financeiros em linguagem natural.</p>
                    <p class="small text-muted">Ex: "Quanto gastei com alimentos nos últimos 3 meses?" ou "Qual categoria teve maior despesa?"</p>
                </div>
            </div>
            <div class="input-group">
                <input type="text" id="pergunta-input" class="form-control form-control-premium"
                       placeholder="Digite sua pergunta aqui..." <?= !$apiKeyConfigurada ? 'disabled' : '' ?>>
                <button class="btn btn-premium" type="button" onclick="enviarPergunta()" <?= !$apiKeyConfigurada ? 'disabled' : '' ?>>
                    <i class="fa-solid fa-paper-plane"></i> Perguntar
                </button>
            </div>
            <div class="form-text small text-muted">Pressione Enter para enviar. Histórico limitado às últimas 10 perguntas.</div>
        </div>
    </div>
</div>

        </div>
    </div>
</div>

<!-- Scripts -->
<script src="assets/js/utils.js"></script>
<script src="assets/js/analise_ia.js"></script>
<?php require_once __DIR__ . '/src/includes/scripts_footer.php'; ?>
</body>
</html>
