<?php
/**
 * Módulo de Patrimônio e Manutenção de Bens
 * CRUD completo de ativos, controle de manutenções, ficha do bem, relatório PDF e integração financeira.
 */

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';

use CantinaFinanceiro\Database;
use CantinaFinanceiro\Auth;

Auth::checkAuth();
Auth::restrictTo(['gerente', 'operador']);

$db = Database::getConnection();
$nivelUsuario = $_SESSION['user_nivel'];
$usuarioId = $_SESSION['user_id'];

$sucessoMsg = '';
$erroMsg = '';

// Converte valor no formato BRL (Ex: "1.250,50") para Centavos (inteiro)
function parseBrlToCents(string $val): int {
    $cleanVal = str_replace('.', '', $val);
    $cleanVal = str_replace(',', '.', $cleanVal);
    return (int)round((float)$cleanVal * 100);
}

// ----------------------------------------------------
// Processamento de Ações do Formulário (POST)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // ====================================================
    // Ações de Bens
    // ====================================================
    if ($acao === 'cadastrar_bem') {
        $nome = trim($_POST['nome'] ?? '');
        $codigo = trim($_POST['codigo_patrimonio'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $setor = trim($_POST['setor_localizacao'] ?? '');
        $data_aquisicao = trim($_POST['data_aquisicao'] ?? '');
        $status = $_POST['status'] ?? 'ativo';
        $data_baixa = trim($_POST['data_baixa'] ?? '');

        if ($status !== 'inativo') {
            $data_baixa = null;
        } elseif (empty($data_baixa)) {
            $data_baixa = date('Y-m-d');
        }

        if (!empty($nome)) {
            try {
                $stmt = $db->prepare("INSERT INTO bens (codigo_patrimonio, nome, descricao, setor_localizacao, data_aquisicao, data_baixa, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    empty($codigo) ? null : $codigo,
                    $nome,
                    empty($descricao) ? null : $descricao,
                    empty($setor) ? null : $setor,
                    empty($data_aquisicao) ? null : $data_aquisicao,
                    $data_baixa,
                    $status
                ]);
                $sucessoMsg = "Bem '{$nome}' cadastrado com sucesso.";
                Auth::logAction($usuarioId, 'BEM_CADASTRAR', "Bem '{$nome}' cadastrado com sucesso.");
            } catch (\PDOException $e) {
                if ($e->getCode() == 23000) {
                    $erroMsg = "Erro: Já existe um bem cadastrado com este Código de Patrimônio.";
                } else {
                    $erroMsg = "Erro ao cadastrar bem: " . $e->getMessage();
                }
            }
        } else {
            $erroMsg = "O nome do bem é obrigatório.";
        }
    }

    elseif ($acao === 'editar_bem') {
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $codigo = trim($_POST['codigo_patrimonio'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $setor = trim($_POST['setor_localizacao'] ?? '');
        $data_aquisicao = trim($_POST['data_aquisicao'] ?? '');
        $status = $_POST['status'] ?? 'ativo';
        $data_baixa = trim($_POST['data_baixa'] ?? '');

        if ($status !== 'inativo') {
            $data_baixa = null;
        } elseif (empty($data_baixa)) {
            $data_baixa = date('Y-m-d');
        }

        if ($id > 0 && !empty($nome)) {
            try {
                $stmt = $db->prepare("UPDATE bens SET codigo_patrimonio = ?, nome = ?, descricao = ?, setor_localizacao = ?, data_aquisicao = ?, data_baixa = ?, status = ? WHERE id = ?");
                $stmt->execute([
                    empty($codigo) ? null : $codigo,
                    $nome,
                    empty($descricao) ? null : $descricao,
                    empty($setor) ? null : $setor,
                    empty($data_aquisicao) ? null : $data_aquisicao,
                    $data_baixa,
                    $status,
                    $id
                ]);
                $sucessoMsg = "Bem atualizado com sucesso.";
                Auth::logAction($usuarioId, 'BEM_EDITAR', "Bem ID #{$id} atualizado.");
            } catch (\PDOException $e) {
                if ($e->getCode() == 23000) {
                    $erroMsg = "Erro: Já existe um bem cadastrado com este Código de Patrimônio.";
                } else {
                    $erroMsg = "Erro ao atualizar bem: " . $e->getMessage();
                }
            }
        } else {
            $erroMsg = "Preencha todos os campos obrigatórios.";
        }
    }

    elseif ($acao === 'excluir_bem') {
        if ($nivelUsuario !== 'gerente') {
            $erroMsg = "Permissão negada. Apenas gerentes podem excluir bens.";
        } else {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                try {
                    $stmt = $db->prepare("DELETE FROM bens WHERE id = ?");
                    $stmt->execute([$id]);
                    $sucessoMsg = "Bem excluído com sucesso. O histórico de manutenções associadas foi removido.";
                    Auth::logAction($usuarioId, 'BEM_EXCLUIR', "Bem ID #{$id} removido.");
                } catch (\Exception $e) {
                    $erroMsg = "Erro ao excluir bem: " . $e->getMessage();
                }
            }
        }
    }

    // ====================================================
    // Ações de Manutenções
    // ====================================================
    elseif ($acao === 'agendar_manutencao') {
        $bem_id = (int)($_POST['bem_id'] ?? 0);
        $descricao = trim($_POST['descricao'] ?? '');
        $tipo = $_POST['tipo'] ?? 'preventiva';
        $custoRaw = $_POST['custo'] ?? '0';
        $data_prog = $_POST['data_programada'] ?? '';
        $tecnico = trim($_POST['tecnico_responsavel'] ?? '');
        $obs = trim($_POST['observacoes'] ?? '');

        if ($bem_id > 0 && !empty($descricao) && !empty($data_prog)) {
            $custoCents = parseBrlToCents($custoRaw);
            try {
                $stmt = $db->prepare("INSERT INTO manutencoes (bem_id, descricao, tipo, custo, data_programada, tecnico_responsavel, observacoes, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'agendada')");
                $stmt->execute([
                    $bem_id,
                    $descricao,
                    $tipo,
                    $custoCents,
                    $data_prog,
                    empty($tecnico) ? null : $tecnico,
                    empty($obs) ? null : $obs
                ]);
                $sucessoMsg = "Manutenção agendada com sucesso.";
                Auth::logAction($usuarioId, 'MANUTENCAO_AGENDAR', "Manutenção agendada para o bem ID #{$bem_id}.");
            } catch (\Exception $e) {
                $erroMsg = "Erro ao agendar manutenção: " . $e->getMessage();
            }
        } else {
            $erroMsg = "Preencha todos os campos obrigatórios.";
        }
    }

    elseif ($acao === 'editar_manutencao') {
        $id = (int)($_POST['id'] ?? 0);
        $bem_id = (int)($_POST['bem_id'] ?? 0);
        $descricao = trim($_POST['descricao'] ?? '');
        $tipo = $_POST['tipo'] ?? 'preventiva';
        $custoRaw = $_POST['custo'] ?? '0';
        $data_prog = $_POST['data_programada'] ?? '';
        $data_real = $_POST['data_realizada'] ?? '';
        $status = $_POST['status'] ?? 'agendada';
        $tecnico = trim($_POST['tecnico_responsavel'] ?? '');
        $obs = trim($_POST['observacoes'] ?? '');

        if ($id > 0 && $bem_id > 0 && !empty($descricao) && !empty($data_prog)) {
            $custoCents = parseBrlToCents($custoRaw);
            try {
                $stmt = $db->prepare("UPDATE manutencoes SET bem_id = ?, descricao = ?, tipo = ?, custo = ?, data_programada = ?, data_realizada = ?, status = ?, tecnico_responsavel = ?, observacoes = ? WHERE id = ?");
                $stmt->execute([
                    $bem_id,
                    $descricao,
                    $tipo,
                    $custoCents,
                    $data_prog,
                    empty($data_real) ? null : $data_real,
                    $status,
                    empty($tecnico) ? null : $tecnico,
                    empty($obs) ? null : $obs,
                    $id
                ]);
                $sucessoMsg = "Manutenção atualizada com sucesso.";
                Auth::logAction($usuarioId, 'MANUTENCAO_EDITAR', "Manutenção ID #{$id} atualizada.");
            } catch (\Exception $e) {
                $erroMsg = "Erro ao atualizar manutenção: " . $e->getMessage();
            }
        } else {
            $erroMsg = "Preencha todos os campos obrigatórios.";
        }
    }

    elseif ($acao === 'concluir_manutencao') {
        $id = (int)($_POST['id'] ?? 0);
        $data_real = $_POST['data_realizada'] ?? date('Y-m-d');
        $custoRaw = $_POST['custo'] ?? '0';
        $obs = trim($_POST['observacoes'] ?? '');
        $gerarFinanceiro = isset($_POST['gerar_financeiro']) && $_POST['gerar_financeiro'] === '1';
        $categoriaId = $_POST['categoria_id'] ?? null;
        $fornecedorId = $_POST['fornecedor_id'] ?? null;

        if ($id > 0) {
            $custoCents = parseBrlToCents($custoRaw);
            try {
                $db->beginTransaction();

                // Busca dados da manutenção e do bem
                $stmtMan = $db->prepare("SELECT m.*, b.nome AS bem_nome FROM manutencoes m JOIN bens b ON m.bem_id = b.id WHERE m.id = ?");
                $stmtMan->execute([$id]);
                $manData = $stmtMan->fetch();

                if (!$manData) {
                    throw new \Exception("Manutenção não encontrada.");
                }

                $contaPagarId = null;

                // Fluxo de Integração Financeira Opcional
                if ($gerarFinanceiro && $custoCents > 0) {
                    $descFinanceiro = "Manutenção: " . $manData['descricao'] . " - Bem: " . $manData['bem_nome'];
                    
                    // Insere conta a pagar
                    $stmtConta = $db->prepare("INSERT INTO contas (descricao, valor, tipo, status, data_vencimento, data_liquidacao, categoria_id, fornecedor_id, observacoes, criado_por) VALUES (?, ?, 'pagar', 'pago', ?, ?, ?, ?, ?, ?)");
                    $stmtConta->execute([
                        $descFinanceiro,
                        $custoCents,
                        $data_real,
                        $data_real,
                        empty($categoriaId) ? null : (int)$categoriaId,
                        empty($fornecedorId) ? null : (int)$fornecedorId,
                        "Lançamento automático via conclusão de manutenção do Bem '{$manData['bem_nome']}'.",
                        $usuarioId
                    ]);

                    $contaPagarId = $db->lastInsertId();
                }

                // Atualiza a manutenção
                $stmtUpdate = $db->prepare("UPDATE manutencoes SET status = 'realizada', data_realizada = ?, custo = ?, observacoes = ?, conta_pagar_id = ? WHERE id = ?");
                $stmtUpdate->execute([
                    $data_real,
                    $custoCents,
                    empty($obs) ? null : $obs,
                    $contaPagarId,
                    $id
                ]);

                // Atualiza o status do bem de volta para 'ativo' se estiver 'manutencao'
                $stmtUpdateBem = $db->prepare("UPDATE bens SET status = 'ativo' WHERE id = ? AND status = 'manutencao'");
                $stmtUpdateBem->execute([$manData['bem_id']]);

                $db->commit();
                $sucessoMsg = "Manutenção concluída com sucesso!";
                if ($contaPagarId) {
                    $sucessoMsg .= " Despesa correspondente criada em Contas a Pagar.";
                }
                Auth::logAction($usuarioId, 'MANUTENCAO_CONCLUIR', "Manutenção ID #{$id} concluída. Financeiro gerado: " . ($contaPagarId ? 'Sim' : 'Não'));
            } catch (\Exception $e) {
                $db->rollBack();
                $erroMsg = "Erro ao concluir manutenção: " . $e->getMessage();
            }
        } else {
            $erroMsg = "Manutenção inválida.";
        }
    }

    elseif ($acao === 'excluir_manutencao') {
        if ($nivelUsuario !== 'gerente') {
            $erroMsg = "Permissão negada. Apenas gerentes podem excluir históricos de manutenção.";
        } else {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                try {
                    $stmt = $db->prepare("DELETE FROM manutencoes WHERE id = ?");
                    $stmt->execute([$id]);
                    $sucessoMsg = "Registro de manutenção excluído com sucesso.";
                    Auth::logAction($usuarioId, 'MANUTENCAO_EXCLUIR', "Manutenção ID #{$id} excluída.");
                } catch (\Exception $e) {
                    $erroMsg = "Erro ao excluir manutenção: " . $e->getMessage();
                }
            }
        }
    }
}

// ----------------------------------------------------
// Carregamento de Dados das Listas & KPIs
// ----------------------------------------------------
// KPIs
$kpiAtivos = $db->query("SELECT COUNT(*) FROM bens WHERE status = 'ativo'")->fetchColumn();
$kpiAgendadas = $db->query("SELECT COUNT(*) FROM manutencoes WHERE status = 'agendada'")->fetchColumn();
$kpiInvestido = $db->query("SELECT SUM(custo) FROM manutencoes WHERE status = 'realizada'")->fetchColumn() ?: 0;
$kpiEmManutencao = $db->query("SELECT COUNT(*) FROM bens WHERE status = 'manutencao'")->fetchColumn();

// Listagem de Bens
$stmtBens = $db->query("SELECT * FROM bens ORDER BY nome ASC");
$listaBens = $stmtBens->fetchAll();

// Listagem de Manutenções
$stmtMan = $db->query("SELECT m.*, b.nome AS bem_nome, b.codigo_patrimonio AS bem_codigo 
                       FROM manutencoes m 
                       JOIN bens b ON m.bem_id = b.id 
                       ORDER BY m.data_programada DESC");
$listaManutencoes = $stmtMan->fetchAll();

// Categorias e Fornecedores para o Modal Financeiro
$listaCategorias = $db->query("SELECT * FROM categorias ORDER BY nome ASC")->fetchAll();
$listaFornecedores = $db->query("SELECT * FROM fornecedores ORDER BY nome ASC")->fetchAll();

// Estruturação do JSON para o modal de Ficha do Bem em Javascript
$bensComManutencoes = [];
foreach ($listaBens as $b) {
    $stmtM = $db->prepare("SELECT * FROM manutencoes WHERE bem_id = ? ORDER BY data_programada DESC");
    $stmtM->execute([$b['id']]);
    $b['manutencoes'] = $stmtM->fetchAll();
    $bensComManutencoes[$b['id']] = $b;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Patrimônio - Cantina Sant'Anna</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-md-3 col-lg-2 px-0 sidebar-panel d-none d-md-block">
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
                <a href="patrimonio.php" class="nav-link active">
                    <i class="fa-solid fa-screwdriver-wrench me-2"></i> Patrimônio
                </a>
                <?php if ($_SESSION['user_nivel'] === 'gerente'): ?>
                    <a href="usuarios.php" class="nav-link">
                        <i class="fa-solid fa-users me-2"></i> Usuários
                    </a>
                <?php endif; ?>
                <!-- Novo link adicionado -->
                <a href="cardapios.php" class="nav-link">
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
            <!-- Mobile Header -->
            <div class="d-md-none d-flex justify-content-between align-items-center mb-4 p-3 bg-white rounded-3 shadow-sm">
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
                        <li><a class="dropdown-menu-item nav-link p-2" href="cardapios.php"><i class="fa-solid fa-utensils me-2"></i> Cardápio IA</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-menu-item nav-link p-2 text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Sair</a></li>
                    </ul>
                </div>
            </div>

            <!-- Banner alerts -->
            <?php if ($sucessoMsg): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i> <?= htmlspecialchars($sucessoMsg, ENT_QUOTES, 'UTF-8') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($erroMsg): ?>
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($erroMsg, ENT_QUOTES, 'UTF-8') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-0">Controle de Patrimônio e Manutenções</h1>
                    <p class="text-muted small mb-0">Cadastre e acompanhe os bens da Cantina Sant'Anna e programe manutenções preventivas e corretivas.</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalCadastrarBem">
                        <i class="fa-solid fa-cube me-1"></i> Novo Bem (Ativo)
                    </button>
                    <button class="btn btn-premium" data-bs-toggle="modal" data-bs-target="#modalAgendarManutencao">
                        <i class="fa-solid fa-calendar-plus me-1"></i> Programar Manutenção
                    </button>
                </div>
            </div>

            <!-- KPI Cards Row -->
            <div class="row g-3 mb-4">
                <!-- Bens Ativos -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-glass p-3 kpi-border-success">
                        <div class="kpi-title">Bens Ativos no Inventário</div>
                        <div class="kpi-value text-success"><?= $kpiAtivos ?></div>
                    </div>
                </div>
                <!-- Manutenções Próximas -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-glass p-3 kpi-border-info">
                        <div class="kpi-title">Manutenções Agendadas</div>
                        <div class="kpi-value text-info"><?= $kpiAgendadas ?></div>
                    </div>
                </div>
                <!-- Gasto Acumulado -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-glass p-3 kpi-border-warning">
                        <div class="kpi-title">Total Gasto em Manutenções</div>
                        <div class="kpi-value text-warning">R$ <?= number_format($kpiInvestido / 100, 2, ',', '.') ?></div>
                    </div>
                </div>
                <!-- Em Manutenção -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-glass p-3 kpi-border-danger">
                        <div class="kpi-title">Bens em Manutenção Ativa</div>
                        <div class="kpi-value text-danger"><?= $kpiEmManutencao ?></div>
                    </div>
                </div>
            </div>

            <!-- Nav tabs -->
            <ul class="nav nav-pills mb-4 gap-2" id="patrimonioTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="btn btn-outline-primary active px-4" id="inventory-tab" data-bs-toggle="pill" data-bs-target="#inventory-panel" type="button" role="tab"><i class="fa-solid fa-boxes-stacked me-1"></i> Inventário de Bens</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="btn btn-outline-primary px-4" id="schedule-tab" data-bs-toggle="pill" data-bs-target="#schedule-panel" type="button" role="tab"><i class="fa-solid fa-clock-rotate-left me-1"></i> Cronograma de Manutenções</button>
                </li>
            </ul>

            <!-- Tab content -->
            <div class="tab-content" id="patrimonioTabContent">
                <!-- PANEL 1: INVENTÁRIO DE BENS -->
                <div class="tab-pane fade show active" id="inventory-panel" role="tabpanel">
                    <div class="card card-glass p-3">
                        <h5 class="fw-bold mb-3 text-muted small"><i class="fa-solid fa-warehouse text-primary me-2"></i> Bens Cadastrados</h5>
                        <div class="table-responsive">
                            <table class="table table-premium mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>Patrimônio</th>
                                        <th>Nome do Bem</th>
                                        <th>Setor / Localização</th>
                                        <th>Data Aquisição</th>
                                        <th>Status</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($listaBens)): ?>
                                        <tr><td colspan="6" class="text-center text-muted py-4">Nenhum bem cadastrado no inventário.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($listaBens as $b): ?>
                                            <tr>
                                                <td class="fw-bold">
                                                    <?php if ($b['codigo_patrimonio']): ?>
                                                        <span class="badge bg-dark px-2 py-1"><i class="fa-solid fa-hashtag me-1 small"></i> <?= htmlspecialchars($b['codigo_patrimonio'], ENT_QUOTES, 'UTF-8') ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted small font-monospace">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="fw-bold"><?= htmlspecialchars($b['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td>
                                                    <?php if ($b['setor_localizacao']): ?>
                                                        <span class="text-secondary small"><i class="fa-solid fa-location-dot me-1 text-primary"></i> <?= htmlspecialchars($b['setor_localizacao'], ENT_QUOTES, 'UTF-8') ?></span>
                                                    <?php else: ?>
                                                        <span class="opacity-50 small">Não informado</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-muted">
                                                    <?= $b['data_aquisicao'] ? date('d/m/Y', strtotime($b['data_aquisicao'])) : '-' ?>
                                                </td>
                                                <td>
                                                    <?php if ($b['status'] === 'ativo'): ?>
                                                        <span class="custom-badge badge-pago"><i class="fa-solid fa-circle-check"></i> Ativo</span>
                                                    <?php elseif ($b['status'] === 'manutencao'): ?>
                                                        <span class="custom-badge badge-pulse-danger"><i class="fa-solid fa-triangle-exclamation"></i> Em Manutenção</span>
                                                    <?php else: ?>
                                                        <span class="custom-badge bg-secondary text-white" title="Baixado em: <?= $b['data_baixa'] ? date('d/m/Y', strtotime($b['data_baixa'])) : '' ?>"><i class="fa-solid fa-circle-minus"></i> Baixado <?= $b['data_baixa'] ? '('.date('d/m/Y', strtotime($b['data_baixa'])).')' : '' ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-inline-flex gap-1">
                                                        <!-- Ficha / Histórico -->
                                                        <button class="btn btn-sm btn-outline-info rounded-pill px-3 py-1" onclick="abrirFichaBem(<?= $b['id'] ?>)"><i class="fa-solid fa-file-invoice me-1"></i> Ficha & Histórico</button>
                                                        <!-- Agendar rápido -->
                                                        <button class="btn btn-sm btn-outline-success rounded-pill px-2 py-1" data-bs-toggle="modal" data-bs-target="#modalAgendarManutencao" data-bem-id="<?= $b['id'] ?>" title="Programar Manutenção"><i class="fa-solid fa-calendar-plus"></i></button>
                                                        <!-- Editar -->
                                                        <button class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#modalEditarBem" 
                                                                data-id="<?= $b['id'] ?>" 
                                                                data-nome="<?= htmlspecialchars($b['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                                                data-codigo="<?= htmlspecialchars($b['codigo_patrimonio'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                                data-desc="<?= htmlspecialchars($b['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                                data-setor="<?= htmlspecialchars($b['setor_localizacao'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                                data-aquisicao="<?= $b['data_aquisicao'] ?? '' ?>"
                                                                data-databaixa="<?= $b['data_baixa'] ?? '' ?>"
                                                                data-status="<?= $b['status'] ?>"><i class="fa-solid fa-pen"></i></button>
                                                        <!-- Excluir -->
                                                        <?php if ($nivelUsuario === 'gerente'): ?>
                                                            <form method="POST" action="patrimonio.php" onsubmit="return confirm('Excluir este bem? Todo o histórico de manutenções associadas será removido permanentemente.');" style="display:inline;">
                                                                <input type="hidden" name="acao" value="excluir_bem">
                                                                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1"><i class="fa-solid fa-trash"></i></button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- PANEL 2: CRONOGRAMA DE MANUTENÇÕES -->
                <div class="tab-pane fade" id="schedule-panel" role="tabpanel">
                    <div class="card card-glass p-3">
                        <h5 class="fw-bold mb-3 text-muted small"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Cronograma de Serviços</h5>
                        <div class="table-responsive">
                            <table class="table table-premium mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>Bem / Ativo</th>
                                        <th>Serviço</th>
                                        <th>Tipo</th>
                                        <th>Vencimento / Programada</th>
                                        <th>Realizado em</th>
                                        <th>Custo</th>
                                        <th>Técnico</th>
                                        <th>Status</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($listaManutencoes)): ?>
                                        <tr><td colspan="9" class="text-center text-muted py-4">Nenhuma manutenção agendada ou realizada.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($listaManutencoes as $man): ?>
                                            <tr>
                                                <td>
                                                    <span class="fw-bold d-block"><?= htmlspecialchars($man['bem_nome'], ENT_QUOTES, 'UTF-8') ?></span>
                                                    <?php if ($man['bem_codigo']): ?>
                                                        <span class="badge bg-light text-secondary font-monospace" style="font-size:0.7rem;">#<?= htmlspecialchars($man['bem_codigo'], ENT_QUOTES, 'UTF-8') ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="fw-medium text-dark"><?= htmlspecialchars($man['descricao'], ENT_QUOTES, 'UTF-8') ?></span>
                                                    <?php if ($man['observacoes']): ?>
                                                        <small class="text-muted d-block text-truncate" style="max-width:180px;" title="<?= htmlspecialchars($man['observacoes'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($man['observacoes'], ENT_QUOTES, 'UTF-8') ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($man['tipo'] === 'preventiva'): ?>
                                                        <span class="badge bg-light text-info"><i class="fa-solid fa-shield-halved me-1"></i> Preventiva</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-warning"><i class="fa-solid fa-toolbox me-1"></i> Corretiva</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-muted fw-bold">
                                                    <?= date('d/m/Y', strtotime($man['data_programada'])) ?>
                                                </td>
                                                <td class="text-muted">
                                                    <?= $man['data_realizada'] ? date('d/m/Y', strtotime($man['data_realizada'])) : '-' ?>
                                                </td>
                                                <td class="fw-bold text-end">
                                                    R$ <?= number_format($man['custo'] / 100, 2, ',', '.') ?>
                                                </td>
                                                <td class="small">
                                                    <?= $man['tecnico_responsavel'] ? htmlspecialchars($man['tecnico_responsavel'], ENT_QUOTES, 'UTF-8') : '<span class="opacity-50">-</span>' ?>
                                                </td>
                                                <td>
                                                    <?php if ($man['status'] === 'agendada'): ?>
                                                        <span class="custom-badge badge-pendente"><i class="fa-solid fa-hourglass-half"></i> Agendada</span>
                                                    <?php elseif ($man['status'] === 'realizada'): ?>
                                                        <span class="custom-badge badge-pago"><i class="fa-solid fa-circle-check"></i> Realizada</span>
                                                    <?php else: ?>
                                                        <span class="custom-badge bg-secondary text-white"><i class="fa-solid fa-ban"></i> Cancelada</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-inline-flex gap-1">
                                                        <?php if ($man['status'] === 'agendada'): ?>
                                                            <!-- Concluir rápido -->
                                                            <button class="btn btn-sm btn-outline-success rounded-pill px-3 py-1" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#modalConcluirManutencao" 
                                                                    data-id="<?= $man['id'] ?>" 
                                                                    data-desc="<?= htmlspecialchars($man['descricao'], ENT_QUOTES, 'UTF-8') ?>"
                                                                    data-bem="<?= htmlspecialchars($man['bem_nome'], ENT_QUOTES, 'UTF-8') ?>"
                                                                    data-custo="R$ <?= number_format($man['custo'] / 100, 2, ',', '.') ?>"><i class="fa-solid fa-circle-check me-1"></i> Concluir</button>
                                                        <?php endif; ?>
                                                        
                                                        <!-- Editar -->
                                                        <button class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#modalEditarManutencao" 
                                                                data-id="<?= $man['id'] ?>" 
                                                                data-bem-id="<?= $man['bem_id'] ?>"
                                                                data-desc="<?= htmlspecialchars($man['descricao'], ENT_QUOTES, 'UTF-8') ?>"
                                                                data-tipo="<?= $man['tipo'] ?>"
                                                                data-custo="R$ <?= number_format($man['custo'] / 100, 2, ',', '.') ?>"
                                                                data-data-prog="<?= $man['data_programada'] ?>"
                                                                data-data-real="<?= $man['data_realizada'] ?? '' ?>"
                                                                data-status="<?= $man['status'] ?>"
                                                                data-tecnico="<?= htmlspecialchars($man['tecnico_responsavel'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                                data-obs="<?= htmlspecialchars($man['observacoes'] ?? '', ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-pen"></i></button>
                                                        
                                                        <!-- Excluir -->
                                                        <?php if ($nivelUsuario === 'gerente'): ?>
                                                            <form method="POST" action="patrimonio.php" onsubmit="return confirm('Excluir este registro de manutenção?');" style="display:inline;">
                                                                <input type="hidden" name="acao" value="excluir_manutencao">
                                                                <input type="hidden" name="id" value="<?= $man['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1"><i class="fa-solid fa-trash"></i></button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==================================================== -->
<!-- MODAIS DE BENS (ATIVOS) -->
<!-- ==================================================== -->
<!-- Modal: Cadastrar Bem -->
<div class="modal fade" id="modalCadastrarBem" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-glass">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Cadastrar Novo Bem</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="patrimonio.php">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="cadastrar_bem">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Código de Patrimônio</label>
                            <input type="text" name="codigo_patrimonio" class="form-control form-control-premium" placeholder="Ex: PAT-010">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Nome do Bem *</label>
                            <input type="text" name="nome" class="form-control form-control-premium" placeholder="Ex: Microondas 30L" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Setor / Localização</label>
                            <input type="text" name="setor_localizacao" class="form-control form-control-premium" placeholder="Ex: Cozinha, Salão">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Data de Aquisição</label>
                            <input type="date" name="data_aquisicao" class="form-control form-control-premium">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Status Inicial</label>
                            <select name="status" id="cadastrar-bem-status" class="form-select form-select-premium" onchange="toggleDataBaixa('cadastrar')">
                                <option value="ativo">Ativo / Em Operação</option>
                                <option value="manutencao">Em Manutenção</option>
                                <option value="inativo">Inativo / Baixado</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="div-cadastrar-databaixa" style="display: none;">
                            <label class="form-label text-muted small fw-semibold">Data de Baixa / Descarte</label>
                            <input type="date" name="data_baixa" class="form-control form-control-premium" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted small fw-semibold">Descrição / Notas Técnicas</label>
                            <textarea name="descricao" class="form-control form-control-premium" rows="2" placeholder="Informações de garantia, marca, potência ou voltagem..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-premium px-4">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Editar Bem -->
<div class="modal fade" id="modalEditarBem" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-glass">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Editar Bem / Ativo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="patrimonio.php">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="editar_bem">
                    <input type="hidden" name="id" id="edit-bem-id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Código de Patrimônio</label>
                            <input type="text" name="codigo_patrimonio" id="edit-bem-codigo" class="form-control form-control-premium">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Nome do Bem *</label>
                            <input type="text" name="nome" id="edit-bem-nome" class="form-control form-control-premium" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Setor / Localização</label>
                            <input type="text" name="setor_localizacao" id="edit-bem-setor" class="form-control form-control-premium">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Data de Aquisição</label>
                            <input type="date" name="data_aquisicao" id="edit-bem-aquisicao" class="form-control form-control-premium">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Status</label>
                            <select name="status" id="edit-bem-status" class="form-select form-select-premium" onchange="toggleDataBaixa('editar')">
                                <option value="ativo">Ativo / Em Operação</option>
                                <option value="manutencao">Em Manutenção</option>
                                <option value="inativo">Inativo / Baixado</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="div-edit-databaixa" style="display: none;">
                            <label class="form-label text-muted small fw-semibold">Data de Baixa / Descarte</label>
                            <input type="date" name="data_baixa" id="edit-bem-databaixa" class="form-control form-control-premium">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted small fw-semibold">Descrição / Notas Técnicas</label>
                            <textarea name="descricao" id="edit-bem-desc" class="form-control form-control-premium" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-premium px-4">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================================================== -->
<!-- MODAIS DE MANUTENÇÕES -->
<!-- ==================================================== -->
<!-- Modal: Agendar Manutenção -->
<div class="modal fade" id="modalAgendarManutencao" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-glass">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Programar Manutenção</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="patrimonio.php">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="agendar_manutencao">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label text-muted small fw-semibold">Selecione o Bem *</label>
                            <select name="bem_id" id="agendar-bem-id" class="form-select form-select-premium" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($listaBens as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['nome'], ENT_QUOTES, 'UTF-8') ?> <?= $b['codigo_patrimonio'] ? "(#{$b['codigo_patrimonio']})" : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted small fw-semibold">Descrição do Serviço *</label>
                            <input type="text" name="descricao" class="form-control form-control-premium" placeholder="Ex: Limpeza técnica do condensador" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Tipo</label>
                            <select name="tipo" class="form-select form-select-premium">
                                <option value="preventiva">Preventiva</option>
                                <option value="corretiva">Corretiva</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Custo Estimado (R$)</label>
                            <input type="text" name="custo" class="form-control form-control-premium money-mask" placeholder="0,00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Data Programada *</label>
                            <input type="date" name="data_programada" class="form-control form-control-premium" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Técnico / Responsável</label>
                            <input type="text" name="tecnico_responsavel" class="form-control form-control-premium" placeholder="Ex: Refrigeração Sant'Anna">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted small fw-semibold">Observações / Notas</label>
                            <textarea name="observacoes" class="form-control form-control-premium" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-premium px-4">Programar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Editar Manutenção -->
<div class="modal fade" id="modalEditarManutencao" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-glass">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Editar Registro de Manutenção</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="patrimonio.php">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="editar_manutencao">
                    <input type="hidden" name="id" id="edit-man-id">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label text-muted small fw-semibold">Selecione o Bem *</label>
                            <select name="bem_id" id="edit-man-bem" class="form-select form-select-premium" required>
                                <?php foreach ($listaBens as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted small fw-semibold">Descrição do Serviço *</label>
                            <input type="text" name="descricao" id="edit-man-desc" class="form-control form-control-premium" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Tipo</label>
                            <select name="tipo" id="edit-man-tipo" class="form-select form-select-premium">
                                <option value="preventiva">Preventiva</option>
                                <option value="corretiva">Corretiva</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Custo (R$)</label>
                            <input type="text" name="custo" id="edit-man-custo" class="form-control form-control-premium money-mask">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Data Programada *</label>
                            <input type="date" name="data_programada" id="edit-man-dataprog" class="form-control form-control-premium" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Data Realizada</label>
                            <input type="date" name="data_realizada" id="edit-man-datareal" class="form-control form-control-premium">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Técnico / Responsável</label>
                            <input type="text" name="tecnico_responsavel" id="edit-man-tecnico" class="form-control form-control-premium">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Status</label>
                            <select name="status" id="edit-man-status" class="form-select form-select-premium">
                                <option value="agendada">Agendada</option>
                                <option value="realizada">Realizada</option>
                                <option value="cancelada">Cancelada</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted small fw-semibold">Observações / Notas</label>
                            <textarea name="observacoes" id="edit-man-obs" class="form-control form-control-premium" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-premium px-4">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Concluir Manutenção (Com Integração Financeira) -->
<div class="modal fade" id="modalConcluirManutencao" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-glass">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-success"><i class="fa-solid fa-circle-check me-1"></i> Concluir Manutenção</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="patrimonio.php">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="concluir_manutencao">
                    <input type="hidden" name="id" id="concluir-man-id">
                    
                    <div class="mb-3 bg-light p-3 rounded-3">
                        <div class="small text-muted mb-1">Manutenção do Bem: <strong class="text-dark" id="concluir-man-bemname"></strong></div>
                        <div class="fw-bold" id="concluir-man-descname"></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Data da Realização *</label>
                            <input type="date" name="data_realizada" class="form-control form-control-premium" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Custo Final Real (R$) *</label>
                            <input type="text" name="custo" id="concluir-man-custoval" class="form-control form-control-premium money-mask" required>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-check form-switch p-2 bg-light border border-secondary border-opacity-10 rounded-3 px-5">
                                <input class="form-check-input ms-0 me-2 shadow-none" type="checkbox" name="gerar_financeiro" id="checkGerarFinanceiro" value="1" checked onchange="toggleFormFinanceiro()">
                                <label class="form-check-label fw-bold small text-dark" for="checkGerarFinanceiro">Lançar custo em Contas a Pagar</label>
                            </div>
                        </div>

                        <!-- Campos Financeiros Condicionais -->
                        <div id="form-financeiro-campos" class="col-12">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-semibold">Categoria Financeira</label>
                                    <select name="categoria_id" class="form-select form-select-premium">
                                        <option value="">Não Informado</option>
                                        <?php foreach ($listaCategorias as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" <?= $cat['nome'] === 'Infraestrutura' || $cat['nome'] === 'Serviços' ? 'selected' : '' ?>><?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-semibold">Fornecedor / Credor</label>
                                    <select name="fornecedor_id" class="form-select form-select-premium">
                                        <option value="">Não Informado</option>
                                        <?php foreach ($listaFornecedores as $forn): ?>
                                            <option value="<?= $forn['id'] ?>"><?= htmlspecialchars($forn['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label text-muted small fw-semibold">Observações / Relato do Serviço</label>
                            <textarea name="observacoes" class="form-control form-control-premium" rows="2" placeholder="Descreva observações, peças trocadas ou recomendações técnicas..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-success px-4">Concluir & Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================================================== -->
<!-- MODAL: FICHA DO BEM & HISTÓRICO (PREMIUM PDF) -->
<!-- ==================================================== -->
<div class="modal fade" id="modalFichaBem" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content modal-content-glass">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-cube text-primary me-2"></i> Ficha Técnica de Patrimônio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                
                <!-- Área de Impressão do Relatório (Exportada pelo html2pdf.js) -->
                <div id="ficha-bem-impressao" class="p-3 bg-white text-dark rounded-3" style="font-family: Arial, sans-serif;">
                    
                    <!-- Cabeçalho Institucional (Visível apenas no PDF) -->
                    <div id="ficha-pdf-header" style="display: none; border-bottom: 2px solid #1e1b4b; padding-bottom: 12px; margin-bottom: 20px;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td>
                                    <div style="font-size: 1.8rem; font-weight: bold; color: #1e1b4b; margin: 0;">Cantina Sant'Anna</div>
                                    <div style="font-size: 0.8rem; color: #6b7280; text-transform: uppercase; letter-spacing: 1px;">Relatório de Ficha Técnica de Patrimônio</div>
                                </td>
                                <td style="text-align: right; font-size: 0.8rem; color: #4b5563;">
                                    Gerado em: <strong><?= date('d/m/Y H:i') ?></strong><br>
                                    Nível de Acesso: <strong>Interno / Auditoria</strong>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Dados Cadastrais em Grid -->
                    <h6 class="fw-bold text-primary mb-3 border-bottom pb-1 uppercase small letter-spacing-1"><i class="fa-solid fa-info me-1"></i> Especificações do Ativo</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <span class="d-block text-muted small">Nome do Ativo</span>
                            <strong id="ficha-bem-nome" class="text-dark fs-5"></strong>
                        </div>
                        <div class="col-md-4">
                            <span class="d-block text-muted small">Código de Patrimônio</span>
                            <strong id="ficha-bem-codigo" class="badge bg-dark fs-6 px-3"></strong>
                        </div>
                        <div class="col-md-4">
                            <span class="d-block text-muted small">Localização / Setor</span>
                            <strong id="ficha-bem-setor" class="text-dark"></strong>
                        </div>
                        <div class="col-md-4">
                            <span class="d-block text-muted small">Data de Aquisição</span>
                            <strong id="ficha-bem-aquisicao" class="text-dark"></strong>
                        </div>
                        <div class="col-md-4">
                            <span class="d-block text-muted small">Status Operacional</span>
                            <span id="ficha-bem-status"></span>
                        </div>
                        <div class="col-md-4" id="div-ficha-databaixa" style="display: none;">
                            <span class="d-block text-muted small">Data de Baixa / Descarte</span>
                            <strong id="ficha-bem-databaixa" class="text-danger"></strong>
                        </div>
                        <div class="col-md-4">
                            <span class="d-block text-muted small">Investimento Acumulado</span>
                            <strong id="ficha-bem-investido" class="text-success fs-5"></strong>
                        </div>
                        <div class="col-12">
                            <span class="d-block text-muted small">Descrição Geral / Observações</span>
                            <p id="ficha-bem-descricao" class="text-muted mb-0 bg-light p-2 rounded border border-light"></p>
                        </div>
                    </div>

                    <!-- Tabela de Histórico de Manutenção -->
                    <h6 class="fw-bold text-primary mb-3 border-bottom pb-1 uppercase small letter-spacing-1"><i class="fa-solid fa-clock-rotate-left me-1"></i> Histórico de Intervenções e Manutenções</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle" style="font-size: 0.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Descrição do Serviço</th>
                                    <th>Tipo</th>
                                    <th class="text-center">Data Prog.</th>
                                    <th class="text-center">Data Realiz.</th>
                                    <th>Técnico Responsável</th>
                                    <th class="text-end">Custo</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody id="ficha-bem-historico-tabela">
                                <!-- Preenchido dinamicamente via JS -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Rodapé Institucional (Visível apenas no PDF) -->
                    <div id="ficha-pdf-footer" style="display: none; border-top: 1px solid #e5e7eb; margin-top: 40px; padding-top: 15px; font-size: 0.75rem; text-align: center; color: #6b7280;">
                        Este documento atesta o histórico operacional do ativo imobilizado registrado na base de dados da Cantina Sant'Anna.<br>
                        © <?= date('Y') ?> Cantina Sant'Anna. Todos os direitos reservados.
                    </div>
                </div>

            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-premium px-4" id="btnExportarFichaPDF"><i class="fa-solid fa-file-pdf me-1"></i> Gerar PDF</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- html2pdf.js Bundle CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
    // DADOS TÉCNICOS PRE-SEMEADOS VIA PHP (Econômico e ultra rápido!)
    const BENS_DATA = <?= json_encode($bensComManutencoes) ?>;
    let BEM_ATIVO_PDF = null;

    // Toggle para exibição dinâmica do campo de data de baixa/descarte
    function toggleDataBaixa(modo) {
        if (modo === 'cadastrar') {
            const status = document.getElementById('cadastrar-bem-status').value;
            const divBaixa = document.getElementById('div-cadastrar-databaixa');
            divBaixa.style.display = (status === 'inativo') ? 'block' : 'none';
        } else if (modo === 'editar') {
            const status = document.getElementById('edit-bem-status').value;
            const divBaixa = document.getElementById('div-edit-databaixa');
            divBaixa.style.display = (status === 'inativo') ? 'block' : 'none';
        }
    }

    // ----------------------------------------------------
    // Carregamento de dados nos modais de edição
    // ----------------------------------------------------
    const modalEditarBem = document.getElementById('modalEditarBem');
    if (modalEditarBem) {
        modalEditarBem.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            document.getElementById('edit-bem-id').value = button.getAttribute('data-id');
            document.getElementById('edit-bem-nome').value = button.getAttribute('data-nome');
            document.getElementById('edit-bem-codigo').value = button.getAttribute('data-codigo');
            document.getElementById('edit-bem-desc').value = button.getAttribute('data-desc');
            document.getElementById('edit-bem-setor').value = button.getAttribute('data-setor');
            document.getElementById('edit-bem-aquisicao').value = button.getAttribute('data-aquisicao');
            document.getElementById('edit-bem-status').value = button.getAttribute('data-status');
            document.getElementById('edit-bem-databaixa').value = button.getAttribute('data-databaixa') || '';
            toggleDataBaixa('editar');
        });
    }

    const modalEditarManutencao = document.getElementById('modalEditarManutencao');
    if (modalEditarManutencao) {
        modalEditarManutencao.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            document.getElementById('edit-man-id').value = button.getAttribute('data-id');
            document.getElementById('edit-man-bem').value = button.getAttribute('data-bem-id');
            document.getElementById('edit-man-desc').value = button.getAttribute('data-desc');
            document.getElementById('edit-man-tipo').value = button.getAttribute('data-tipo');
            document.getElementById('edit-man-custo').value = button.getAttribute('data-custo');
            document.getElementById('edit-man-dataprog').value = button.getAttribute('data-data-prog');
            document.getElementById('edit-man-datareal').value = button.getAttribute('data-data-real');
            document.getElementById('edit-man-status').value = button.getAttribute('data-status');
            document.getElementById('edit-man-tecnico').value = button.getAttribute('data-tecnico');
            document.getElementById('edit-man-obs').value = button.getAttribute('data-obs');
        });
    }

    const modalConcluirManutencao = document.getElementById('modalConcluirManutencao');
    if (modalConcluirManutencao) {
        modalConcluirManutencao.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            document.getElementById('concluir-man-id').value = button.getAttribute('data-id');
            document.getElementById('concluir-man-bemname').innerText = button.getAttribute('data-bem');
            document.getElementById('concluir-man-descname').innerText = button.getAttribute('data-desc');
            document.getElementById('concluir-man-custoval').value = button.getAttribute('data-custo');
            toggleFormFinanceiro();
        });
    }

    // Toggle para exibição condicional de campos financeiros ao concluir manutenção
    function toggleFormFinanceiro() {
        const checked = document.getElementById('checkGerarFinanceiro').checked;
        const divFinanceiro = document.getElementById('form-financeiro-campos');
        if (checked) {
            divFinanceiro.style.display = 'block';
        } else {
            divFinanceiro.style.display = 'none';
        }
    }

    // ----------------------------------------------------
    // Exibição de Ficha e Histórico
    // ----------------------------------------------------
    function abrirFichaBem(bemId) {
        const bem = BENS_DATA[bemId];
        if (!bem) return;
        
        BEM_ATIVO_PDF = bem;

        // Limpa e popula dados no modal
        document.getElementById('ficha-bem-nome').innerText = bem.nome;
        document.getElementById('ficha-bem-codigo').innerText = bem.codigo_patrimonio ? bem.codigo_patrimonio : 'SEM PATRIMÔNIO';
        document.getElementById('ficha-bem-setor').innerText = bem.setor_localizacao ? bem.setor_localizacao : 'Não Informado';
        document.getElementById('ficha-bem-aquisicao').innerText = bem.data_aquisicao ? formatarData(bem.data_aquisicao) : '-';
        document.getElementById('ficha-bem-descricao').innerText = bem.descricao ? bem.descricao : 'Nenhuma observação técnica cadastrada.';

        // Data de Baixa (se for inativo)
        const divFichaBaixa = document.getElementById('div-ficha-databaixa');
        if (bem.status === 'inativo') {
            document.getElementById('ficha-bem-databaixa').innerText = bem.data_baixa ? formatarData(bem.data_baixa) : '-';
            divFichaBaixa.style.display = 'block';
        } else {
            divFichaBaixa.style.display = 'none';
        }

        // Status Badge
        const statusSpan = document.getElementById('ficha-bem-status');
        if (bem.status === 'ativo') {
            statusSpan.innerHTML = '<span class="badge bg-success px-3 py-2"><i class="fa-solid fa-circle-check me-1"></i> Ativo</span>';
        } else if (bem.status === 'manutencao') {
            statusSpan.innerHTML = '<span class="badge bg-danger px-3 py-2"><i class="fa-solid fa-triangle-exclamation me-1"></i> Em Manutenção</span>';
        } else {
            statusSpan.innerHTML = '<span class="badge bg-secondary px-3 py-2"><i class="fa-solid fa-circle-minus me-1"></i> Inativo</span>';
        }

        // Renderiza Histórico na Tabela
        const tbody = document.getElementById('ficha-bem-historico-tabela');
        tbody.innerHTML = '';
        
        let totalInvestido = 0;

        if (bem.manutencoes && bem.manutencoes.length > 0) {
            bem.manutencoes.forEach(m => {
                const custoNum = m.custo / 100;
                if (m.status === 'realizada') {
                    totalInvestido += custoNum;
                }

                const custoFmt = "R$ " + custoNum.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                const dataProgFmt = formatarData(m.data_programada);
                const dataRealFmt = m.data_realizada ? formatarData(m.data_realizada) : '-';
                
                // Tipo badge
                const tipoBadge = m.tipo === 'preventiva' 
                    ? '<span class="text-info small"><i class="fa-solid fa-shield-halved"></i> Prev</span>'
                    : '<span class="text-warning small"><i class="fa-solid fa-toolbox"></i> Corr</span>';

                // Status Badge
                let statusBadge = '';
                if (m.status === 'agendada') {
                    statusBadge = '<span class="badge bg-light text-warning border">Agendada</span>';
                } else if (m.status === 'realizada') {
                    statusBadge = '<span class="badge bg-success-subtle text-success border border-success">Realizada</span>';
                } else {
                    statusBadge = '<span class="badge bg-secondary text-white">Cancelada</span>';
                }

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="fw-bold">${escapeHtml(m.descricao)}</td>
                    <td class="text-center">${tipoBadge}</td>
                    <td class="text-center text-muted font-monospace">${dataProgFmt}</td>
                    <td class="text-center text-muted font-monospace">${dataRealFmt}</td>
                    <td class="small">${m.tecnico_responsavel ? escapeHtml(m.tecnico_responsavel) : '-'}</td>
                    <td class="text-end fw-bold text-dark">${custoFmt}</td>
                    <td class="text-center">${statusBadge}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-3">Nenhuma manutenção agendada ou realizada para este bem.</td></tr>';
        }

        // KPI Investido na Ficha
        document.getElementById('ficha-bem-investido').innerText = "R$ " + totalInvestido.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, ".");

        // Abre o modal
        const modal = new bootstrap.Modal(document.getElementById('modalFichaBem'));
        modal.show();
    }

    // Função auxiliar para formatar datas (YYYY-MM-DD -> DD/MM/YYYY)
    function formatarData(dataStr) {
        if (!dataStr) return '';
        const partes = dataStr.split('-');
        if (partes.length !== 3) return dataStr;
        return `${partes[2]}/${partes[1]}/${partes[0]}`;
    }

    // Auxiliar contra XSS no populate
    function escapeHtml(text) {
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // ----------------------------------------------------
    // Exportação do Relatório PDF (html2pdf.js)
    // ----------------------------------------------------
    document.getElementById('btnExportarFichaPDF').addEventListener('click', function() {
        if (!BEM_ATIVO_PDF) return;

        const element = document.getElementById('ficha-bem-impressao');
        const header = document.getElementById('ficha-pdf-header');
        const footer = document.getElementById('ficha-pdf-footer');

        // Exibe cabeçalho e rodapé institucionais do PDF
        header.style.display = 'block';
        footer.style.display = 'block';

        const opt = {
            margin:       12,
            filename:     'ficha_tecnica_' + BEM_ATIVO_PDF.nome.toLowerCase().replace(/\s+/g, '_') + '_' + (BEM_ATIVO_PDF.codigo_patrimonio || 'sem_pat').toLowerCase() + '.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' } // Formato retrato para Ficha Técnica
        };

        // Exportação
        html2pdf().set(opt).from(element).save().then(() => {
            // Oculta novamente na visualização do modal
            header.style.display = 'none';
            footer.style.display = 'none';
        }).catch(err => {
            console.error("Erro ao gerar PDF da Ficha Técnica: ", err);
            header.style.display = 'none';
            footer.style.display = 'none';
        });
    });

    // ----------------------------------------------------
    // Máscaras de valor BRL dinâmicas
    // ----------------------------------------------------
    document.querySelectorAll('.money-mask').forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value === '') {
                e.target.value = '';
                return;
            }
            value = (parseFloat(value) / 100).toFixed(2);
            e.target.value = 'R$ ' + value.replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        });
        
        // Remove máscara R$ no submit do formulário para evitar erros de parse
        input.closest('form').addEventListener('submit', function() {
            let rawValue = input.value.replace('R$', '').replace(/\s/g, '');
            input.value = rawValue;
        });
    });

    // Gatilho para carregar bem_id pré-selecionado se vier de um botão de ação rápida
    const modalAgendarManutencao = document.getElementById('modalAgendarManutencao');
    if (modalAgendarManutencao) {
        modalAgendarManutencao.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const bemId = button.getAttribute('data-bem-id');
            if (bemId) {
                document.getElementById('agendar-bem-id').value = bemId;
            } else {
                document.getElementById('agendar-bem-id').value = '';
            }
        });
    }
</script>
</body>
</html>
