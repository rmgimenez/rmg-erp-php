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

        .card-menu-day {
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 12px;
            background: white;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            height: 100%;
        }

        .card-menu-day:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(79, 70, 229, 0.08);
            border-color: rgba(79, 70, 229, 0.2);
        }

        .card-menu-day .day-header {
            background: #f8fafc;
            border-radius: 12px 12px 0 0;
            font-weight: 700;
            color: #1e1b4b;
            text-transform: uppercase;
            font-size: 0.82rem;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
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

        /* ==================================================== */
        /* Estilos Específicos para Impressão A4 */
        /* ==================================================== */
        @media print {
            body {
                background: white !important;
                color: black !important;
                font-size: 11pt !important;
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

            /* Ocultações dinâmicas conforme a ação de impressão */
            body.print-only-cardapio .print-portrait-container {
                display: none !important;
            }
            body.print-only-lista .print-landscape-container {
                display: none !important;
            }

            /* Configuração do Cardápio em A4 Paisagem */
            body.print-only-cardapio .print-landscape-container {
                display: block !important;
                width: 100% !important;
                page-break-after: avoid;
            }

            .print-grid-5 {
                display: grid !important;
                grid-template-columns: repeat(5, 1fr) !important;
                gap: 12px !important;
                width: 100% !important;
            }

            .card-menu-day {
                box-shadow: none !important;
                border: 1px solid #000000 !important;
                page-break-inside: avoid;
                height: auto !important;
            }

            .card-menu-day .day-header {
                background: #f1f5f9 !important;
                color: black !important;
                border-bottom: 1px solid #000000 !important;
                padding: 8px !important;
                font-size: 11pt !important;
            }

            .card-menu-day .card-body {
                padding: 10px !important;
            }

            .print-header {
                display: flex !important;
                justify-content: space-between;
                align-items: center;
                border-bottom: 2px solid #000000;
                padding-bottom: 10px;
                margin-bottom: 20px;
            }

            /* Configuração da Lista de Compras em A4 Retrato */
            body.print-only-lista .print-portrait-container {
                display: block !important;
                width: 100% !important;
            }

            .print-markdown-list {
                font-size: 11pt !important;
                line-height: 1.6;
            }

            .print-markdown-list h1, 
            .print-markdown-list h2, 
            .print-markdown-list h3 {
                color: black !important;
                border-bottom: 1px solid #ccc;
                padding-bottom: 4px;
                margin-top: 15px;
                margin-bottom: 10px;
                font-size: 13pt !important;
            }
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
                                <p style="margin: 3px 0 0 0; font-size: 10pt; color: #444;">Planejamento Nutricional Semanal e Controle de Insumos</p>
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
                                    <button class="btn btn-sm btn-outline-primary" onclick="imprimirCardapio()">
                                        <i class="fa-solid fa-utensils me-1"></i> Imprimir Cardápio
                                    </button>
                                    <button class="btn btn-sm btn-outline-success" onclick="imprimirLista()">
                                        <i class="fa-solid fa-basket-shopping me-1"></i> Imprimir Lista
                                    </button>
                                </div>
                            </div>

                            <?php if (!empty($cardapioAtivo['observacoes_geracao'])): ?>
                                <div class="alert alert-light border border-opacity-50 mt-3 mb-0 small rounded-3 text-secondary">
                                    <i class="fa-solid fa-comment-dots text-primary me-1"></i>
                                    <strong>Observações da Semana:</strong> <?= htmlspecialchars($cardapioAtivo['observacoes_geracao'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Tabs do Cardápio vs Lista no-print -->
                        <ul class="nav premium-nav-tabs mb-3 border-bottom no-print" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="tab-cardapio-btn" data-bs-toggle="tab" data-bs-target="#tab-cardapio" type="button" role="tab"><i class="fa-solid fa-utensils me-1"></i> O Cardápio</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab-lista-btn" data-bs-toggle="tab" data-bs-target="#tab-lista" type="button" role="tab"><i class="fa-solid fa-basket-shopping me-1"></i> Lista de Ingredientes</button>
                            </li>
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content">
                            
                            <!-- ABA 1: CARDÁPIO (A4 PAISAGEM NA IMPRESSÃO) -->
                            <div class="tab-pane fade show active print-landscape-container" id="tab-cardapio" role="tabpanel">
                                <div class="print-grid-5 row g-3">
                                    <?php 
                                    $diasDisplay = [
                                        'segunda' => 'Segunda-feira',
                                        'terca' => 'Terça-feira',
                                        'quarta' => 'Quarta-feira',
                                        'quinta' => 'Quinta-feira',
                                        'sexta' => 'Sexta-feira'
                                    ];
                                    ?>
                                    <?php foreach ($cardapioAtivo['dias'] as $d): ?>
                                        <div class="col-lg col-md-6">
                                            <div class="card-menu-day h-100">
                                                <div class="day-header p-3 d-flex justify-content-between align-items-center">
                                                    <span><?= $diasDisplay[$d['dia_semana']] ?></span>
                                                    <button class="btn btn-sm btn-outline-primary px-2 py-0 border-0 no-print" 
                                                            title="Editar Dia"
                                                            onclick="abrirModalEdicaoDia('<?= $d['dia_semana'] ?>', <?= htmlspecialchars(json_encode($d['refeicao_principal']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($d['lanche']), ENT_QUOTES, 'UTF-8') ?>)">
                                                        <i class="fa-solid fa-pencil"></i>
                                                    </button>
                                                </div>
                                                <div class="card-body p-3">
                                                    <div class="mb-3">
                                                        <h6 class="text-primary fw-bold small text-uppercase mb-1"><i class="fa-solid fa-bowl-food me-1"></i> Almoço</h6>
                                                        <p class="text-dark small mb-0 lh-base" id="lbl-principal-<?= $d['dia_semana'] ?>">
                                                            <?= htmlspecialchars($d['refeicao_principal'], ENT_QUOTES, 'UTF-8') ?>
                                                        </p>
                                                    </div>
                                                    <hr class="text-muted border-dashed border-opacity-20 my-2">
                                                    <div>
                                                        <h6 class="text-warning fw-bold small text-uppercase mb-1"><i class="fa-solid fa-cookie-bite me-1"></i> Lanche</h6>
                                                        <p class="text-dark small mb-0 lh-base" id="lbl-lanche-<?= $d['dia_semana'] ?>">
                                                            <?= htmlspecialchars($d['lanche'], ENT_QUOTES, 'UTF-8') ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- ABA 2: LISTA DE COMPRAS (A4 RETRATO NA IMPRESSÃO) -->
                            <div class="tab-pane fade force-portrait-page print-portrait-container" id="tab-lista" role="tabpanel">
                                <div class="d-none print-header">
                                    <div>
                                        <h3 style="margin: 0; font-weight: 700;">Cantina Colégio Sant'Anna</h3>
                                    </div>
                                    <div style="text-align: right;">
                                        <h4 style="margin: 0; font-weight: 600;">LISTA DE INGREDIENTES PARA COMPRA</h4>
                                        <p style="margin: 3px 0 0 0; font-size: 9pt;">Semana: <strong><?= date('d/m/Y', strtotime($cardapioAtivo['data_inicio'])) ?></strong> a <strong><?= date('d/m/Y', strtotime($cardapioAtivo['data_fim'])) ?></strong></p>
                                    </div>
                                </div>

                                <div class="card card-glass p-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
                                        <h5 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-basket-shopping me-2"></i> Insumos Estimados</h5>
                                        <button class="btn btn-sm btn-outline-primary" onclick="abrirModalEdicaoLista(<?= htmlspecialchars(json_encode($cardapioAtivo['lista_compras']), ENT_QUOTES, 'UTF-8') ?>)">
                                            <i class="fa-solid fa-pencil me-1"></i> Ajustar Lista
                                        </button>
                                    </div>

                                    <div class="print-markdown-list" id="lista-compras-exibicao">
                                        <!-- Converter Markdown simples de forma limpa no PHP -->
                                        <?php 
                                        $textoMarkdown = $cardapioAtivo['lista_compras'];
                                        // Converte títulos # em <h4>
                                        $textoMarkdown = preg_replace('/### (.*?)\n/', '<h5 class="text-indigo-950 fw-bold mt-3 mb-2 border-bottom pb-1">$1</h5>', $textoMarkdown);
                                        $textoMarkdown = preg_replace('/## (.*?)\n/', '<h4 class="text-indigo-950 fw-bold mt-4 mb-2 border-bottom pb-2">$1</h4>', $textoMarkdown);
                                        // Converte listas - em <ul><li>
                                        $textoMarkdown = preg_replace('/- (.*?)\n/', '<li class="small mb-1">$1</li>', $textoMarkdown);
                                        // Ajusta quebras de linha normais
                                        $textoMarkdown = nl2br($textoMarkdown);
                                        echo $textoMarkdown;
                                        ?>
                                    </div>
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

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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

    // EDICÃO MANUAL DO DIA - ABRE MODAL
    function abrirModalEdicaoDia(dia, principal, lanche) {
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
                // Atualiza visualmente na tela de forma instantânea sem recarregar a página!
                document.getElementById(`lbl-principal-${dia}`).innerText = principalVal;
                document.getElementById(`lbl-lanche-${dia}`).innerText = lancheVal;
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
    function abrirModalEdicaoLista(listaMarkdown) {
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
                // Atualiza visualmente a lista convertida de forma simples
                let converted = listaComprasVal
                    .replace(/### (.*?)\n/g, '<h5 class="text-indigo-950 fw-bold mt-3 mb-2 border-bottom pb-1">$1</h5>')
                    .replace(/## (.*?)\n/g, '<h4 class="text-indigo-950 fw-bold mt-4 mb-2 border-bottom pb-2">$1</h4>')
                    .replace(/- (.*?)\n/g, '<li class="small mb-1">$1</li>')
                    .replace(/\n/g, '<br>');
                
                document.getElementById('lista-compras-exibicao').innerHTML = converted;
                
                // Recarrega o botão com o novo valor
                const btnAjustar = document.querySelector('[onclick^="abrirModalEdicaoLista"]');
                btnAjustar.setAttribute('onclick', `abrirModalEdicaoLista(${JSON.stringify(listaComprasVal)})`);

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

    // IMPRIMIR APENAS O CARDÁPIO (A4 PAISAGEM)
    function imprimirCardapio() {
        let style = document.createElement('style');
        style.id = 'print-page-orientation';
        style.innerHTML = '@page { size: A4 landscape; margin: 0.8cm; }';
        document.head.appendChild(style);

        document.body.classList.add('print-only-cardapio');
        document.body.classList.remove('print-only-lista');
        
        window.print();
        
        setTimeout(() => {
            style.remove();
            document.body.classList.remove('print-only-cardapio');
        }, 1000);
    }

    // IMPRIMIR APENAS A LISTA DE COMPRAS (A4 RETRATO)
    function imprimirLista() {
        let style = document.createElement('style');
        style.id = 'print-page-orientation';
        style.innerHTML = '@page { size: A4 portrait; margin: 1.2cm; }';
        document.head.appendChild(style);

        document.body.classList.add('print-only-lista');
        document.body.classList.remove('print-only-cardapio');
        
        window.print();
        
        setTimeout(() => {
            style.remove();
            document.body.classList.remove('print-only-lista');
        }, 1000);
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
</script>
</body>
</html>
