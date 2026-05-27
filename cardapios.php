<?php
/**
 * Interface de Gerenciamento de Cardápios IA (Visualização e Geração)
 */

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/MenuModel.php';

use CantinaFinanceiro\Auth;
use CantinaFinanceiro\MenuModel;

Auth::checkAuth();
Auth::restrictTo(['gerente', 'operador', 'admin']);

$db = CantinaFinanceiro\Database::getConnection();

// Helper para converter Markdown simples em HTML com segurança
function parseMarkdownToHtml($text) {
    if (empty($text)) return '';
    $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    
    // Converte negritos **texto** em <strong>texto</strong>
    $escaped = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $escaped);
    // Converte itálicos *texto* em <em>texto</em>
    $escaped = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $escaped);
    // Converte marcadores em li
    $escaped = preg_replace('/^\* (.*?)$/m', '<li>$1</li>', $escaped);
    $escaped = preg_replace('/^- (.*?)$/m', '<li>$1</li>', $escaped);
    
    return nl2br($escaped);
}

// Carrega os cardápios semanais do banco
$cardapios = MenuModel::getAll();

// Obtém o cardápio ativo/selecionado
$idSelecionado = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idSelecionado <= 0 && !empty($cardapios)) {
    $idSelecionado = (int)$cardapios[0]['id'];
}

$cardapioAtivo = null;
if ($idSelecionado > 0) {
    $cardapioAtivo = MenuModel::getById($idSelecionado);
}

// Verifica se a API Key do OpenRouter está configurada para exibir alerta se necessário
$stmtCheckKey = $db->query("SELECT valor FROM configuracoes WHERE chave = 'openrouter_api_key' LIMIT 1");
$apiKeyConfigurada = !empty($stmtCheckKey->fetchColumn());
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cardápio Inteligente IA - Cantina Sant'Anna</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    
    <style>
        /* Estilos e Variáveis Premium Adicionais */
        :root {
            --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #818cf8 100%);
            --accent-gradient: linear-gradient(135deg, #ec4899 0%, #f43f5e 100%);
        }

        .premium-badge {
            background: var(--primary-gradient);
            color: white;
            font-weight: 600;
            font-size: 0.75rem;
            padding: 4px 10px;
            border-radius: 50px;
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2);
        }

        /* Abas de Navegação Premium */
        .premium-nav-tabs .nav-link {
            border: none;
            color: var(--text-muted);
            font-weight: 600;
            border-bottom: 3px solid transparent;
            padding: 10px 20px;
            transition: all 0.2s ease;
        }

        .premium-nav-tabs .nav-link.active {
            color: var(--primary);
            border-bottom: 3px solid var(--primary);
            background: transparent;
        }

        .premium-nav-tabs .nav-link:hover {
            color: var(--primary-hover);
            border-bottom: 3px solid rgba(79, 70, 229, 0.3);
        }

        /* Overlay de Carregamento da IA */
        #ia-loader-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(8px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.4s ease;
        }

        #ia-loader-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .loader-card {
            background: white;
            border-radius: 24px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            max-width: 450px;
            width: 90%;
        }

        .ai-pulse-icon {
            width: 80px;
            height: 80px;
            background: var(--primary-gradient);
            color: white;
            font-size: 2.2rem;
            line-height: 80px;
            border-radius: 50%;
            margin: 0 auto 20px;
            animation: pulse-ai 2s infinite;
        }

        @keyframes pulse-ai {
            0% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.5); }
            70% { box-shadow: 0 0 0 20px rgba(79, 70, 229, 0); }
            100% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0); }
        }

        /* Animações Toast */
        @keyframes toastIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes toastOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(20px); }
        }

        /* Tabela do Cardápio */
        .table-cardapio {
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 12px;
            overflow: hidden;
            background: white;
        }

        .table-cardapio thead th {
            background: #f8fafc;
            border-bottom: 2px solid var(--border-color);
            font-weight: 700;
            color: #1e1b4b;
            text-transform: uppercase;
            font-size: 0.78rem;
            letter-spacing: 0.5px;
            padding: 12px 16px;
        }

        .table-cardapio tbody td {
            padding: 12px 16px;
            vertical-align: top;
            border-bottom: 1px solid rgba(226, 232, 240, 0.5);
        }

        .table-cardapio tbody tr:last-child td {
            border-bottom: none;
        }

        .table-cardapio tbody tr:hover {
            background-color: rgba(79, 70, 229, 0.02);
        }

        .meal-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .meal-list li {
            padding: 3px 0;
            font-size: 0.88rem;
            color: #334155;
        }

        .meal-list li::before {
            content: "•";
            color: var(--primary);
            font-weight: bold;
            margin-right: 8px;
        }

        /* Lista de Compras */
        .lista-compras-content {
            padding: 16px;
            background: #fafafa;
            border-radius: 8px;
            min-height: 200px;
        }

        .lista-compras-content h3,
        .lista-compras-content h4,
        .lista-compras-content h5 {
            color: #1e1b4b;
        }

        .lista-compras-content li {
            padding: 4px 0;
            font-size: 0.9rem;
            color: #334155;
            list-style: none;
        }

        .lista-compras-content li::before {
            content: "•";
            color: var(--primary);
            font-weight: bold;
            margin-right: 8px;
        }

        /* Editor Markdown com Preview */
        .markdown-editor-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            min-height: 300px;
        }

        .markdown-editor-pane,
        .markdown-preview-pane {
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
        }

        .markdown-editor-pane .pane-header,
        .markdown-preview-pane .pane-header {
            background: #f8fafc;
            padding: 8px 16px;
            font-weight: 600;
            font-size: 0.82rem;
            color: #64748b;
            border-bottom: 1px solid var(--border-color);
        }

        .markdown-editor-pane textarea {
            width: 100%;
            min-height: 250px;
            border: none;
            padding: 16px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            resize: vertical;
            outline: none;
        }

        .markdown-preview-pane .preview-content {
            padding: 16px;
            min-height: 250px;
            overflow-y: auto;
        }

        /* ==================================================== */
        /* Estilos para Impressão A4 Paisagem */
        /* ==================================================== */
        @media print {
            body {
                background: white !important;
                color: black !important;
                font-size: 10pt !important;
            }

            .sidebar-panel, 
            .no-print, 
            .d-md-none, 
            .btn, 
            .nav-tabs,
            .modal,
            .alert,
            header,
            footer {
                display: none !important;
            }

            .col-md-9, .col-lg-10, .col-12 {
                width: 100% !important;
                max-width: 100% !important;
                flex: 0 0 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .print-cardapio-container {
                display: block !important;
                opacity: 1 !important;
                visibility: visible !important;
                width: 100% !important;
            }

            .print-header {
                display: flex !important;
                justify-content: space-between;
                align-items: center;
                border-bottom: 2px solid #000000;
                padding-bottom: 8px;
                margin-bottom: 12px;
            }

            .table-cardapio {
                border: 1px solid #000 !important;
                font-size: 9pt !important;
            }

            .table-cardapio thead th {
                background: #f0f0f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .table-cardapio tbody td {
                padding: 8px 10px !important;
            }

            .meal-list li {
                font-size: 9pt !important;
                padding: 2px 0;
            }
        }

        /* Estilos para html2pdf.js */
        #cardapio-pdf-content {
            font-family: 'Arial', sans-serif;
            padding: 20px;
            background: white;
        }

        #cardapio-pdf-content .pdf-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        #cardapio-pdf-content .pdf-title {
            font-size: 18pt;
            font-weight: 700;
            color: #1e1b4b;
            margin: 0;
        }

        #cardapio-pdf-content .pdf-subtitle {
            font-size: 10pt;
            color: #64748b;
            margin: 3px 0 0 0;
        }

        #cardapio-pdf-content .pdf-date {
            text-align: right;
            font-size: 11pt;
            font-weight: 600;
        }

        #cardapio-pdf-content .pdf-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }

        #cardapio-pdf-content .pdf-table th {
            background: #f0f0f0;
            border: 1px solid #000;
            padding: 8px 10px;
            text-align: left;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 8pt;
        }

        #cardapio-pdf-content .pdf-table td {
            border: 1px solid #000;
            padding: 8px 10px;
            vertical-align: top;
        }

        #cardapio-pdf-content .pdf-table ul {
            margin: 0;
            padding-left: 16px;
        }

        #cardapio-pdf-content .pdf-table li {
            margin-bottom: 2px;
        }

        #cardapio-pdf-content .pdf-footer {
            margin-top: 15px;
            padding-top: 8px;
            border-top: 1px solid #ccc;
            font-size: 8pt;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>
<body>

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

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Navigation (no-print) -->
        <div class="col-md-3 col-lg-2 px-0 sidebar-panel d-none d-md-block no-print">
            <div class="py-4 text-center border-bottom border-secondary border-opacity-25">
                <div style="font-size: 1.6rem; font-weight: 700; color: #ffffff; letter-spacing: -0.5px;">
                    Sant'Anna<span style="color: #6366f1;">.</span>
                </div>
                <span class="badge bg-light text-dark text-opacity-75 small px-3 py-1 rounded-pill mt-1">
                    <?= ucfirst($_SESSION['user_nivel']) ?>
                </span>
            </div>
            
            <div class="mt-4">
                <a href="index.php" class="nav-link">
                    <i class="fa-solid fa-chart-line me-2"></i> Dashboard
                </a>
                <a href="contas.php" class="nav-link">
                    <i class="fa-solid fa-file-invoice-dollar me-2"></i> Contas
                </a>
                <a href="calendario.php" class="nav-link">
                    <i class="fa-solid fa-calendar-days me-2"></i> Calendário
                </a>
                <a href="relatorios.php" class="nav-link">
                    <i class="fa-solid fa-file-pdf me-2"></i> Relatórios
                </a>
                <a href="cadastros.php" class="nav-link">
                    <i class="fa-solid fa-tags me-2"></i> Cadastros
                </a>
                <a href="patrimonio.php" class="nav-link">
                    <i class="fa-solid fa-screwdriver-wrench me-2"></i> Patrimônio
                </a>
                <?php if ($_SESSION['user_nivel'] === 'gerente'): ?>
                    <a href="usuarios.php" class="nav-link">
                        <i class="fa-solid fa-users me-2"></i> Usuários
                    </a>
                <?php endif; ?>
                <!-- Novo link ativo -->
                <a href="cardapios.php" class="nav-link active">
                    <i class="fa-solid fa-utensils me-2"></i> Cardápio IA
                </a>
                <div class="border-top border-secondary border-opacity-25 my-4 mx-3"></div>
                <a href="logout.php" class="nav-link text-danger">
                    <i class="fa-solid fa-right-from-bracket me-2"></i> Sair
                </a>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="col-md-9 col-lg-10 py-4 px-md-4">
            
            <!-- Mobile Header (no-print) -->
            <div class="d-md-none d-flex justify-content-between align-items-center mb-4 p-3 bg-white rounded-3 shadow-sm no-print">
                <div style="font-size: 1.3rem; font-weight: 700; color: #1e1b4b;">
                    Sant'Anna<span style="color: var(--primary);">.</span>
                </div>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle btn-sm" type="button" data-bs-toggle="dropdown">
                        Menu
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
                        <li><a class="dropdown-menu-item nav-link p-2" href="index.php"><i class="fa-solid fa-chart-line me-2"></i> Dashboard</a></li>
                        <li><a class="dropdown-menu-item nav-link p-2" href="contas.php"><i class="fa-solid fa-file-invoice-dollar me-2"></i> Contas</a></li>
                        <li><a class="dropdown-menu-item nav-link p-2" href="calendario.php"><i class="fa-solid fa-calendar-days me-2"></i> Calendário</a></li>
                        <li><a class="dropdown-menu-item nav-link p-2" href="relatorios.php"><i class="fa-solid fa-file-pdf me-2"></i> Relatórios</a></li>
                        <li><a class="dropdown-menu-item nav-link p-2" href="cadastros.php"><i class="fa-solid fa-tags me-2"></i> Cadastros</a></li>
                        <li><a class="dropdown-menu-item nav-link p-2" href="patrimonio.php"><i class="fa-solid fa-screwdriver-wrench me-2"></i> Patrimônio</a></li>
                        <?php if ($_SESSION['user_nivel'] === 'gerente'): ?>
                            <li><a class="dropdown-menu-item nav-link p-2" href="usuarios.php"><i class="fa-solid fa-users me-2"></i> Usuários</a></li>
                        <?php endif; ?>
                        <li><a class="dropdown-menu-item nav-link p-2 active" href="cardapios.php"><i class="fa-solid fa-utensils me-2"></i> Cardápio IA</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-menu-item nav-link p-2 text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Sair</a></li>
                    </ul>
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
                        <!-- Bloco Dinâmico Visualizado / Impresso -->
                        
                        <!-- Print Specific Header (apenas para folha de papel) -->
                        <div class="d-none print-header">
                            <div>
                                <h3 style="margin: 0; font-weight: 700; letter-spacing: -0.5px;">Cantina Colégio Sant'Anna</h3>
                                <p style="margin: 3px 0 0 0; font-size: 10pt; color: #444;">Planejamento Nutricional Semanal</p>
                            </div>
                            <div style="text-align: right;">
                                <h4 style="margin: 0; font-weight: 600;">CARDÁPIO SEMANAL</h4>
                                <p style="margin: 3px 0 0 0; font-size: 9pt; color: #444;">Válido de <strong><?= date('d/m/Y', strtotime($cardapioAtivo['data_inicio'])) ?></strong> até <strong><?= date('d/m/Y', strtotime($cardapioAtivo['data_fim'])) ?></strong></p>
                            </div>
                        </div>

                        <!-- Card Header no-print -->
                        <div class="card card-glass p-4 mb-4 no-print">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                <div>
                                    <span class="premium-badge mb-1 d-inline-block">Semana Planejada</span>
                                    <h4 class="fw-bold mb-0 text-indigo-950">
                                        Período de <?= date('d/m/Y', strtotime($cardapioAtivo['data_inicio'])) ?> a <?= date('d/m/Y', strtotime($cardapioAtivo['data_fim'])) ?>
                                    </h4>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmarExclusao(<?= $cardapioAtivo['id'] ?>)">
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

                            <!-- Campo de Observações para Impressão -->
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
                                                <?php 
                                                $diasDisplay = [
                                                    'segunda' => 'Segunda-feira',
                                                    'terca' => 'Terça-feira',
                                                    'quarta' => 'Quarta-feira',
                                                    'quinta' => 'Quinta-feira',
                                                    'sexta' => 'Sexta-feira'
                                                ];
                                                ?>
                                                <?php 
                                                // Converte markdown para lista HTML
                                                function convertMarkdownToList($text) {
                                                    if (empty($text)) return '<span class="text-muted">Não informado</span>';
                                                    $lines = explode("\n", $text);
                                                    $html = '<ul class="meal-list">';
                                                    foreach ($lines as $line) {
                                                        $line = trim($line);
                                                        if (empty($line)) continue;
                                                        // Remove marcadores markdown
                                                        $line = preg_replace('/^[-*]\s+/', '', $line);
                                                        $line = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
                                                        // Converte negrito
                                                        $line = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $line);
                                                        $html .= "<li>{$line}</li>";
                                                    }
                                                    $html .= '</ul>';
                                                    return $html;
                                                }
                                                ?>
                                                <?php foreach ($cardapioAtivo['dias'] as $d): ?>
                                                    <?php 
                                                    $hasAlmoco = ($d['refeicao_principal'] !== 'Não planejado' && !empty($d['refeicao_principal']));
                                                    $hasLanche = ($d['lanche'] !== 'Não planejado' && !empty($d['lanche']));
                                                    ?>
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
                                        // Converte títulos markdown
                                        $textoMarkdown = preg_replace('/### (.*?)\n/', '<h5 class="text-indigo-950 fw-bold mt-3 mb-2 border-bottom pb-1">$1</h5>', $textoMarkdown);
                                        $textoMarkdown = preg_replace('/## (.*?)\n/', '<h4 class="text-indigo-950 fw-bold mt-4 mb-2 border-bottom pb-2">$1</h4>', $textoMarkdown);
                                        $textoMarkdown = preg_replace('/# (.*?)\n/', '<h3 class="text-indigo-950 fw-bold mt-4 mb-2">$1</h3>', $textoMarkdown);
                                        // Converte listas em items
                                        $textoMarkdown = preg_replace('/- (.*?)\n/', '<li class="small mb-1">$1</li>', $textoMarkdown);
                                        // Quebras de linha
                                        $textoMarkdown = nl2br($textoMarkdown);
                                        echo $textoMarkdown;
                                        ?>
                                    </div>

                                    <!-- Elemento oculto com markdown bruto -->
                                    <div class="d-none" id="raw-lista-compras"><?= htmlspecialchars($cardapioAtivo['lista_compras'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        </div>
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
            <form id="formEditarDia" onsubmit="salvarDiaManual(event)">
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
            <form id="formEditarLista" onsubmit="salvarListaManual(event)">
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
    <div class="modal-dialog modal-dialog-centered modal-xl border-0">
        <div class="modal-content border-0" style="background: transparent;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-indigo-950"><i class="fa-solid fa-code text-primary me-2"></i> Editar Cardápio em Markdown</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditarMarkdown" onsubmit="salvarMarkdownManual(event)">
                <div class="modal-body py-4">
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

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-premium shadow"><i class="fa-solid fa-check me-1"></i> Salvar Markdown</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- html2pdf.js para impressão A4 paisagem -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<!-- Scripts JS dinâmicos (AJAX) -->
<script>
    // Ativa automaticamente o calculo da sexta-feira ao selecionar a segunda
    function ajustarSextaFeira(dataInicioVal) {
        if (!dataInicioVal) return;
        
        let data = new Date(dataInicioVal + 'T00:00:00');
        
        // Garante que o usuário selecionou uma segunda-feira. Se não, avisa de forma elegante.
        let diaSemana = data.getDay(); // 1 = Segunda
        if (diaSemana !== 1) {
            alert('Atenção: Para manter o cardápio no padrão semanal, selecione uma segunda-feira como data de início.');
        }

        // Soma 4 dias para chegar na sexta-feira correspondente
        data.setDate(data.getDate() + 4);
        
        let ano = data.getFullYear();
        let mes = String(data.getMonth() + 1).padStart(2, '0');
        let dia = String(data.getDate()).padStart(2, '0');
        
        document.getElementById('modal_data_fim').value = `${ano}-${mes}-${dia}`;
    }

    // DISPARA GERAÇÃO VIA IA
    function gerarCardapioIA(event) {
        event.preventDefault();
        
        const loader = document.getElementById('ia-loader-overlay');
        const modalEl = document.getElementById('modalGerarCardapio');
        const modal = bootstrap.Modal.getInstance(modalEl);
        
        // Fecha o modal e abre o loader dinâmico
        modal.hide();
        loader.classList.add('active');

        const formData = new FormData(document.getElementById('formGerarCardapio'));
        formData.append('acao', 'gerar');

        fetch('cardapio_action.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            loader.classList.remove('active');
            if (data.sucesso) {
                // Redireciona para o cardápio recém-criado
                window.location.href = `cardapios.php?id=${data.id}`;
            } else {
                exibirAlerta('danger', data.erro || 'Falha desconhecida na geração.');
            }
        })
        .catch(error => {
            loader.classList.remove('active');
            exibirAlerta('danger', 'Erro de rede ou timeout do servidor ao tentar chamar a IA.');
            console.error(error);
        });
    }

    // Helper JavaScript para converter Markdown simples em HTML
    function parseMarkdownToHtmlJS(text) {
        if (!text) return '';
        let escaped = text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
        
        // Converte negritos **texto** em <strong>texto</strong>
        escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        // Converte itálicos *texto* em <em>texto</em>
        escaped = escaped.replace(/\*(.*?)\*/g, '<em>$1</em>');
        // Converte marcadores em lista
        escaped = escaped.replace(/^\* (.*?)$/gm, '<li>$1</li>');
        escaped = escaped.replace(/^- (.*?)$/gm, '<li>$1</li>');
        
        return escaped.replace(/\n/g, '<br>');
    }

    // Converte markdown para lista HTML formatada
    function convertMarkdownToListJS(text) {
        if (!text) return '<span class="text-muted">Não informado</span>';
        
        const lines = text.split('\n');
        let html = '<ul class="meal-list">';
        
        lines.forEach(line => {
            line = line.trim();
            if (!line) return;
            // Remove marcadores markdown
            line = line.replace(/^[-*]\s+/, '');
            // Escapa HTML
            line = line.replace(/&/g, "&amp;")
                      .replace(/</g, "&lt;")
                      .replace(/>/g, "&gt;")
                      .replace(/"/g, "&quot;")
                      .replace(/'/g, "&#039;");
            // Converte negrito
            line = line.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            html += `<li>${line}</li>`;
        });
        
        html += '</ul>';
        return html;
    }

    // Converte markdown completo para HTML (para preview)
    function parseMarkdownFullJS(text) {
        if (!text) return '';
        
        let html = text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");
        
        // Headers
        html = html.replace(/^### (.*?)$/gm, '<h5 class="text-indigo-950 fw-bold mt-3 mb-2 border-bottom pb-1">$1</h5>');
        html = html.replace(/^## (.*?)$/gm, '<h4 class="text-indigo-950 fw-bold mt-4 mb-2 border-bottom pb-2">$1</h4>');
        html = html.replace(/^# (.*?)$/gm, '<h3 class="text-indigo-950 fw-bold mt-4 mb-2">$1</h3>');
        
        // Negrito e itálico
        html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');
        
        // Listas
        html = html.replace(/^\* (.*?)$/gm, '<li class="small mb-1">$1</li>');
        html = html.replace(/^- (.*?)$/gm, '<li class="small mb-1">$1</li>');
        
        // Quebras de linha
        html = html.replace(/\n/g, '<br>');
        
        return html;
    }

    // EDIÇÃO MANUAL DO DIA - ABRE MODAL
    function abrirModalEdicaoDia(dia) {
        const principal = document.getElementById(`raw-principal-${dia}`).innerText.trim();
        const lanche = document.getElementById(`raw-lanche-${dia}`).innerText.trim();
        
        document.getElementById('modal_edit_dia').value = dia;
        document.getElementById('modal_edit_principal').value = principal;
        document.getElementById('modal_edit_lanche').value = lanche;
        
        const modal = new bootstrap.Modal(document.getElementById('modalEditarDia'));
        modal.show();
    }

    // SALVA EDIÇÃO MANUAL DO DIA VIA AJAX
    function salvarDiaManual(event) {
        event.preventDefault();
        
        const modalEl = document.getElementById('modalEditarDia');
        const modal = bootstrap.Modal.getInstance(modalEl);
        
        const dia = document.getElementById('modal_edit_dia').value;
        const principalVal = document.getElementById('modal_edit_principal').value;
        const lancheVal = document.getElementById('modal_edit_lanche').value;
        
        const formData = new FormData();
        formData.append('acao', 'editar_dia');
        formData.append('id', '<?= $idSelecionado ?>');
        formData.append('dia', dia);
        formData.append('refeicao_principal', principalVal);
        formData.append('lanche', lancheVal);

        fetch('cardapio_action.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            modal.hide();
            if (data.sucesso) {
                // Atualiza o valor bruto oculto
                document.getElementById(`raw-principal-${dia}`).innerText = principalVal;
                document.getElementById(`raw-lanche-${dia}`).innerText = lancheVal;
                
                // Converte markdown para exibição formatada como lista
                document.getElementById(`lbl-principal-${dia}`).innerHTML = convertMarkdownToListJS(principalVal);
                document.getElementById(`lbl-lanche-${dia}`).innerHTML = convertMarkdownToListJS(lancheVal);
                
                exibirAlerta('success', 'Dia do cardápio atualizado com sucesso.');
            } else {
                exibirAlerta('danger', data.erro || 'Erro ao atualizar.');
            }
        })
        .catch(error => {
            modal.hide();
            exibirAlerta('danger', 'Erro de conexão com o servidor.');
            console.error(error);
        });
    }

    // EDIÇÃO MANUAL DA LISTA DE COMPRAS - ABRE MODAL
    function abrirModalEdicaoLista() {
        // Obtém o markdown atual da lista de compras
        const listaMarkdown = document.getElementById('raw-lista-compras').innerText.trim();
        document.getElementById('modal_edit_lista_texto').value = listaMarkdown;
        
        const modal = new bootstrap.Modal(document.getElementById('modalEditarLista'));
        modal.show();
    }

    // SALVA EDIÇÃO MANUAL DA LISTA VIA AJAX
    function salvarListaManual(event) {
        event.preventDefault();
        
        const modalEl = document.getElementById('modalEditarLista');
        const modal = bootstrap.Modal.getInstance(modalEl);
        
        const listaComprasVal = document.getElementById('modal_edit_lista_texto').value;
        
        const formData = new FormData();
        formData.append('acao', 'editar_lista');
        formData.append('id', '<?= $idSelecionado ?>');
        formData.append('lista_compras', listaComprasVal);

        fetch('cardapio_action.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            modal.hide();
            if (data.sucesso) {
                // Atualiza o markdown bruto oculto
                document.getElementById('raw-lista-compras').innerText = listaComprasVal;
                
                // Atualiza a visualização formatada da lista
                let converted = listaComprasVal
                    .replace(/### (.*?)\n/g, '<h5 class="text-indigo-950 fw-bold mt-3 mb-2 border-bottom pb-1">$1</h5>')
                    .replace(/## (.*?)\n/g, '<h4 class="text-indigo-950 fw-bold mt-4 mb-2 border-bottom pb-2">$1</h4>')
                    .replace(/# (.*?)\n/g, '<h3 class="text-indigo-950 fw-bold mt-4 mb-2">$1</h3>')
                    .replace(/- (.*?)\n/g, '<li class="small mb-1">$1</li>')
                    .replace(/\n/g, '<br>');
                
                document.getElementById('lista-compras-exibicao').innerHTML = converted;
                
                exibirAlerta('success', 'Lista de compras atualizada com sucesso.');
            } else {
                exibirAlerta('danger', data.erro || 'Erro ao atualizar a lista.');
            }
        })
        .catch(error => {
            modal.hide();
            exibirAlerta('danger', 'Erro de conexão com o servidor.');
            console.error(error);
        });
    }

    // EDIÇÃO DO MARKDOWN DO CARDÁPIO - ABRE MODAL
    function abrirModalEdicaoMarkdown() {
        // Obtém o markdown atual do cardápio (armazenado em um elemento oculto ou via AJAX)
        // Por enquanto, vamos usar os dados das linhas da tabela para construir o markdown
        const dias = ['segunda', 'terca', 'quarta', 'quinta', 'sexta'];
        const diasNomes = {
            'segunda': 'Segunda-feira',
            'terca': 'Terça-feira',
            'quarta': 'Quarta-feira',
            'quinta': 'Quinta-feira',
            'sexta': 'Sexta-feira'
        };
        
        let markdown = "# Cardápio Semanal\n\n";
        markdown += "**Período:** <?= date('d/m/Y', strtotime($cardapioAtivo['data_inicio'])) ?> a <?= date('d/m/Y', strtotime($cardapioAtivo['data_fim'])) ?>\n\n";
        
        dias.forEach(dia => {
            const principalEl = document.getElementById(`raw-principal-${dia}`);
            const lancheEl = document.getElementById(`raw-lanche-${dia}`);
            
            if (principalEl && lancheEl) {
                const principal = principalEl.innerText.trim();
                const lanche = lancheEl.innerText.trim();
                
                markdown += `## ${diasNomes[dia]}\n\n`;
                markdown += `### Almoço\n\n${principal}\n\n`;
                markdown += `### Lanche\n\n${lanche}\n\n`;
            }
        });
        
        const textarea = document.getElementById('modal_edit_markdown_texto');
        const preview = document.getElementById('markdown-preview');
        
        if (textarea) {
            textarea.value = markdown;
        }
        
        if (preview) {
            preview.innerHTML = parseMarkdownFullJS(markdown);
        }
        
        const modalEl = document.getElementById('modalEditarMarkdown');
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    }

    // ATUALIZA O PREVIEW DO MARKDOWN
    function atualizarPreviewMarkdown() {
        const markdown = document.getElementById('modal_edit_markdown_texto').value;
        document.getElementById('markdown-preview').innerHTML = parseMarkdownFullJS(markdown);
    }

    // SALVA EDIÇÃO DO MARKDOWN VIA AJAX
    function salvarMarkdownManual(event) {
        event.preventDefault();
        
        const modalEl = document.getElementById('modalEditarMarkdown');
        const modal = bootstrap.Modal.getInstance(modalEl);
        
        const markdownVal = document.getElementById('modal_edit_markdown_texto').value;
        
        const formData = new FormData();
        formData.append('acao', 'editar_cardapio_markdown');
        formData.append('id', '<?= $idSelecionado ?>');
        formData.append('cardapio_markdown', markdownVal);

        fetch('cardapio_action.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            modal.hide();
            if (data.sucesso) {
                exibirAlerta('success', 'Cardápio em Markdown atualizado com sucesso.');
            } else {
                exibirAlerta('danger', data.erro || 'Erro ao atualizar o markdown.');
            }
        })
        .catch(error => {
            modal.hide();
            exibirAlerta('danger', 'Erro de conexão com o servidor.');
            console.error(error);
        });
    }

    // IMPRIMIR CARDÁPIO EM PDF A4 PAISAGEM (DIAS NAS COLUNAS)
    function imprimirCardapioPDF() {
        const element = document.createElement('div');
        element.id = 'cardapio-pdf-content';
        
        // Datas de cada dia da semana
        const datasDias = {
            'segunda': '<?= date('d/m', strtotime($cardapioAtivo['data_inicio'])) ?>',
            'terca': '<?= date('d/m', strtotime($cardapioAtivo['data_inicio'] . ' +1 day')) ?>',
            'quarta': '<?= date('d/m', strtotime($cardapioAtivo['data_inicio'] . ' +2 day')) ?>',
            'quinta': '<?= date('d/m', strtotime($cardapioAtivo['data_inicio'] . ' +3 day')) ?>',
            'sexta': '<?= date('d/m', strtotime($cardapioAtivo['data_inicio'] . ' +4 day')) ?>'
        };
        
        const dias = ['segunda', 'terca', 'quarta', 'quinta', 'sexta'];
        const diasNomes = {
            'segunda': 'Segunda',
            'terca': 'Terça',
            'quarta': 'Quarta',
            'quinta': 'Quinta',
            'sexta': 'Sexta'
        };
        
        // Cabeçalho
        let html = `
            <div style="padding: 15px 20px; font-family: Arial, sans-serif;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px;">
                    <div>
                        <h3 style="margin: 0; font-weight: 700; font-size: 16pt;">Cantina Colégio Sant'Anna</h3>
                        <p style="margin: 2px 0 0 0; font-size: 9pt; color: #666;">Planejamento Nutricional Semanal</p>
                    </div>
                    <div style="text-align: right;">
                        <h4 style="margin: 0; font-weight: 600; font-size: 12pt;">CARDÁPIO SEMANAL</h4>
                        <p style="margin: 2px 0 0 0; font-size: 9pt; color: #666;">Semana de <strong><?= date('d/m/Y', strtotime($cardapioAtivo['data_inicio'])) ?></strong> a <strong><?= date('d/m/Y', strtotime($cardapioAtivo['data_fim'])) ?></strong></p>
                    </div>
                </div>
        `;
        
        // Tabela com dias nas colunas
        html += `
            <table style="width: 100%; border-collapse: collapse; font-size: 8.5pt;">
                <thead>
                    <tr>
        `;
        
        // Cabeçalho: Almoço/Lanche à esquerda + 5 colunas de dias
        html += `<th style="width: 10%; background: #f0f0f0; border: 1px solid #000; padding: 6px 4px; text-align: center; font-weight: 700; font-size: 8pt;"></th>`;
        
        dias.forEach(dia => {
            html += `<th style="width: 18%; background: #1e1b4b; color: white; border: 1px solid #000; padding: 6px 4px; text-align: center; font-weight: 700; font-size: 9pt;">
                ${diasNomes[dia]}<br><span style="font-size: 7.5pt; font-weight: 400;">${datasDias[dia]}</span>
            </th>`;
        });
        
        html += `</tr></thead><tbody>`;
        
        // Linha de Almoço
        html += `<tr>
            <td style="background: #f0f0f0; border: 1px solid #000; padding: 6px 4px; font-weight: 700; text-align: center; vertical-align: top; font-size: 8pt;">
                ALMOÇO
            </td>`;
        
        dias.forEach(dia => {
            const principal = document.getElementById(`raw-principal-${dia}`).innerText.trim();
            const principalList = principal.split('\n')
                .filter(line => line.trim())
                .map(line => line.replace(/^[-*]\s+/, '').replace(/\*\*/g, '').trim())
                .map(line => `<li style="margin-bottom: 2px; line-height: 1.3;">${line}</li>`)
                .join('');
            
            html += `<td style="border: 1px solid #000; padding: 6px 4px; vertical-align: top;">
                <ul style="margin: 0; padding-left: 12px;">${principalList}</ul>
            </td>`;
        });
        
        html += `</tr>`;
        
        // Linha de Lanche
        html += `<tr>
            <td style="background: #f0f0f0; border: 1px solid #000; padding: 6px 4px; font-weight: 700; text-align: center; vertical-align: top; font-size: 8pt;">
                LANCHE
            </td>`;
        
        dias.forEach(dia => {
            const lanche = document.getElementById(`raw-lanche-${dia}`).innerText.trim();
            const lancheList = lanche.split('\n')
                .filter(line => line.trim())
                .map(line => line.replace(/^[-*]\s+/, '').replace(/\*\*/g, '').trim())
                .map(line => `<li style="margin-bottom: 2px; line-height: 1.3;">${line}</li>`)
                .join('');
            
            html += `<td style="border: 1px solid #000; padding: 6px 4px; vertical-align: top;">
                <ul style="margin: 0; padding-left: 12px;">${lancheList}</ul>
            </td>`;
        });
        
        html += `</tr></tbody></table>`;
        
        // Observação de impressão (se houver)
        const observacaoEl = document.getElementById('observacao-impressao');
        const observacao = observacaoEl ? observacaoEl.value.trim() : '';
        
        if (observacao) {
            html += `
                <div style="margin-top: 10px; padding: 8px 10px; background: #fef3c7; border: 1px solid #f59e0b; border-radius: 4px; font-size: 8.5pt;">
                    <strong style="color: #92400e;">Observação:</strong> <span style="color: #78350f;">${observacao}</span>
                </div>
            `;
        }
        
        // Rodapé
        html += `
            <div style="margin-top: 10px; padding-top: 6px; border-top: 1px solid #ccc; font-size: 7.5pt; color: #666; text-align: center;">
                Documento gerado automaticamente pelo Sistema de Cantina em <?= date('d/m/Y \à\s H:i') ?>
            </div>
        </div>`;
        
        element.innerHTML = html;
        document.body.appendChild(element);
        
        // Configuração do html2pdf.js para A4 paisagem
        const opt = {
            margin: [8, 8, 8, 8],
            filename: 'cardapio_semanal_<?= date('Y-m-d', strtotime($cardapioAtivo['data_inicio'])) ?>.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
        };
        
        // Gera o PDF
        html2pdf().set(opt).from(element).save().then(() => {
            element.remove();
            exibirAlerta('success', 'PDF gerado com sucesso!');
        }).catch(error => {
            element.remove();
            exibirAlerta('danger', 'Erro ao gerar PDF.');
            console.error(error);
        });
    }

    // IMPRIMIR LISTA DE COMPRAS EM PDF A4 PAISAGEM
    function imprimirListaPDF() {
        const element = document.createElement('div');
        element.id = 'lista-pdf-content';
        
        // Obtém a lista de compras do cardápio
        const listaCompras = <?= json_encode($cardapioAtivo['lista_compras'] ?? '') ?>;
        
        // Converte markdown para HTML
        let listaHtml = listaCompras
            .replace(/### (.*?)\n/g, '<h5 style="color: #1e1b4b; font-weight: 700; margin: 12px 0 6px 0; border-bottom: 1px solid #ccc; padding-bottom: 4px;">$1</h5>')
            .replace(/## (.*?)\n/g, '<h4 style="color: #1e1b4b; font-weight: 700; margin: 16px 0 8px 0; border-bottom: 2px solid #000; padding-bottom: 6px;">$1</h4>')
            .replace(/# (.*?)\n/g, '<h3 style="color: #1e1b4b; font-weight: 700; margin: 20px 0 10px 0;">$1</h3>')
            .replace(/- (.*?)\n/g, '<li style="margin-bottom: 4px;">$1</li>')
            .replace(/\n/g, '<br>');
        
        let html = `
            <div style="padding: 20px; font-family: Arial, sans-serif;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px;">
                    <div>
                        <h3 style="margin: 0; font-weight: 700; font-size: 18pt;">Cantina Colégio Sant'Anna</h3>
                        <p style="margin: 3px 0 0 0; font-size: 10pt; color: #666;">Lista de Ingredientes para Compra</p>
                    </div>
                    <div style="text-align: right;">
                        <h4 style="margin: 0; font-weight: 600;">LISTA DE COMPRAS</h4>
                        <p style="margin: 3px 0 0 0; font-size: 10pt; color: #666;">Semana: <strong><?= date('d/m/Y', strtotime($cardapioAtivo['data_inicio'])) ?></strong> a <strong><?= date('d/m/Y', strtotime($cardapioAtivo['data_fim'])) ?></strong></p>
                    </div>
                </div>
                <div style="font-size: 10pt; line-height: 1.6;">
                    ${listaHtml}
                </div>
                <div style="margin-top: 20px; padding-top: 8px; border-top: 1px solid #ccc; font-size: 8pt; color: #666; text-align: center;">
                    Documento gerado automaticamente pelo Sistema de Cantina em <?= date('d/m/Y \à\s H:i') ?>
                </div>
            </div>
        `;
        
        element.innerHTML = html;
        document.body.appendChild(element);
        
        // Configuração do html2pdf.js para A4 paisagem
        const opt = {
            margin: 10,
            filename: 'lista_compras_<?= date('Y-m-d', strtotime($cardapioAtivo['data_inicio'])) ?>.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
        };
        
        // Gera o PDF
        html2pdf().set(opt).from(element).save().then(() => {
            element.remove();
            exibirAlerta('success', 'Lista de compras gerada com sucesso!');
        }).catch(error => {
            element.remove();
            exibirAlerta('danger', 'Erro ao gerar PDF da lista.');
            console.error(error);
        });
    }

    // SALVAR OBSERVAÇÃO DE IMPRESSÃO
    function salvarObservacaoImpressao() {
        const observacao = document.getElementById('observacao-impressao').value;
        
        const formData = new FormData();
        formData.append('acao', 'salvar_observacao_impressao');
        formData.append('id', '<?= $idSelecionado ?>');
        formData.append('observacao', observacao);

        fetch('cardapio_action.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                exibirToast('Observação salva com sucesso.', 'success');
            } else {
                exibirToast(data.erro || 'Erro ao salvar observação.', 'error');
            }
        })
        .catch(error => {
            exibirToast('Erro de conexão com o servidor.', 'error');
            console.error(error);
        });
    }

    // CONFIRMAR EXCLUSÃO
    function confirmarExclusao(id) {
        if (!confirm('Deseja realmente excluir permanentemente este planejamento de cardápio semanal?')) {
            return;
        }

        const formData = new FormData();
        formData.append('acao', 'excluir');
        formData.append('id', id);

        fetch('cardapio_action.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                // Redireciona de volta para recarregar sem o cardápio excluído
                window.location.href = 'cardapios.php';
            } else {
                exibirAlerta('danger', data.erro || 'Erro ao excluir.');
            }
        })
        .catch(error => {
            exibirAlerta('danger', 'Erro de conexão com o servidor.');
            console.error(error);
        });
    }

    // FUNÇÃO AUXILIAR PARA EXIBIR ALERTA PREMIUM
    function exibirAlerta(tipo, mensagem) {
        const placeholder = document.getElementById('alert-placeholder');
        const icon = tipo === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
        
        placeholder.innerHTML = `
            <div class="alert alert-${tipo} alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <i class="fa-solid ${icon} me-2"></i> ${mensagem}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        // Auto scroll para o topo do alerta se necessário
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // TOAST - Notificação discreta sem scroll
    function exibirToast(mensagem, tipo = 'success') {
        // Remove toast anterior se existir
        const toastAnterior = document.getElementById('toast-notificacao');
        if (toastAnterior) toastAnterior.remove();
        
        const icon = tipo === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
        const bgColor = tipo === 'success' ? '#10b981' : '#ef4444';
        
        const toast = document.createElement('div');
        toast.id = 'toast-notificacao';
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: ${bgColor};
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 9999;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: toastIn 0.3s ease;
        `;
        toast.innerHTML = `<i class="fa-solid ${icon}"></i> ${mensagem}`;
        document.body.appendChild(toast);
        
        // Remove após 3 segundos
        setTimeout(() => {
            toast.style.animation = 'toastOut 0.3s ease forwards';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // Event listener para atualizar preview do markdown
    document.addEventListener('DOMContentLoaded', function() {
        const markdownTextarea = document.getElementById('modal_edit_markdown_texto');
        if (markdownTextarea) {
            markdownTextarea.addEventListener('input', function() {
                const preview = document.getElementById('markdown-preview');
                if (preview) {
                    preview.innerHTML = parseMarkdownFullJS(this.value);
                }
            });
        }
    });
</script>
</body>
</html>
