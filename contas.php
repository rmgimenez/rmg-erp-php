<?php
/**
 * Gerenciamento de Contas (Pagar e Receber)
 * CRUD completo e controle de liquidações (marcar como pago/recebido).
 */

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/AccountModel.php';

use CantinaFinanceiro\Auth;
use CantinaFinanceiro\AccountModel;

Auth::checkAuth();
Auth::restrictTo(['gerente', 'operador']);

$usuarioId = $_SESSION['user_id'];
$nivelUsuario = $_SESSION['user_nivel'];

$sucessoMsg = '';
$erroMsg = '';

// ----------------------------------------------------
// Processamento de Ações do Formulário (POST)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // Converte valor no formato BRL (Ex: "1.250,50") para Centavos (inteiro)
    function parseBrlToCents(string $val): int {
        $cleanVal = str_replace('.', '', $val);
        $cleanVal = str_replace(',', '.', $cleanVal);
        return (int)round((float)$cleanVal * 100);
    }

    // 1. Cadastrar Conta
    if ($acao === 'cadastrar') {
        $descricao = trim($_POST['descricao'] ?? '');
        $valorRaw = $_POST['valor'] ?? '0';
        $tipo = $_POST['tipo'] ?? '';
        $dataVenc = $_POST['data_vencimento'] ?? '';
        $categoria = trim($_POST['categoria'] ?? '');
        $fornecedor = trim($_POST['fornecedor'] ?? '');
        $status = $_POST['status'] ?? 'pendente';
        $obs = trim($_POST['observacoes'] ?? '');

        if (!empty($descricao) && !empty($valorRaw) && !empty($tipo) && !empty($dataVenc)) {
            $valorCents = parseBrlToCents($valorRaw);
            $dados = [
                'descricao' => $descricao,
                'valor' => $valorCents,
                'tipo' => $tipo,
                'status' => $status,
                'data_vencimento' => $dataVenc,
                'categoria' => $categoria,
                'fornecedor' => $fornecedor,
                'observacoes' => $obs
            ];

            if (AccountModel::create($dados, $usuarioId)) {
                $sucessoMsg = "Conta cadastrada com sucesso.";
            } else {
                $erroMsg = "Erro ao cadastrar a conta.";
            }
        } else {
            $erroMsg = "Preencha todos os campos obrigatórios.";
        }
    }

    // 2. Editar Conta (Gerente apenas)
    elseif ($acao === 'editar') {
        if ($nivelUsuario !== 'gerente') {
            $erroMsg = "Permissão negada. Apenas gerentes podem editar contas.";
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $descricao = trim($_POST['descricao'] ?? '');
            $valorRaw = $_POST['valor'] ?? '0';
            $dataVenc = $_POST['data_vencimento'] ?? '';
            $categoria = trim($_POST['categoria'] ?? '');
            $fornecedor = trim($_POST['fornecedor'] ?? '');
            $status = $_POST['status'] ?? 'pendente';
            $obs = trim($_POST['observacoes'] ?? '');
            $dataLiquidacao = $_POST['data_liquidacao'] ?? null;

            if ($id > 0 && !empty($descricao) && !empty($valorRaw) && !empty($dataVenc)) {
                $valorCents = parseBrlToCents($valorRaw);
                $dados = [
                    'descricao' => $descricao,
                    'valor' => $valorCents,
                    'status' => $status,
                    'data_vencimento' => $dataVenc,
                    'data_liquidacao' => $dataLiquidacao,
                    'categoria' => $categoria,
                    'fornecedor' => $fornecedor,
                    'observacoes' => $obs
                ];

                if (AccountModel::update($id, $dados, $usuarioId)) {
                    $sucessoMsg = "Conta #{$id} atualizada com sucesso.";
                } else {
                    $erroMsg = "Erro ao atualizar a conta.";
                }
            } else {
                $erroMsg = "Preencha todos os campos obrigatórios.";
            }
        }
    }

    // 3. Liquidação Rápida (Alterar status para Pago ou Pendente)
    elseif ($acao === 'liquidar') {
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $dataLiquidacao = $_POST['data_liquidacao'] ?? date('Y-m-d');

        if ($id > 0 && in_array($status, ['pago', 'pendente', 'cancelado'])) {
            if (AccountModel::setStatus($id, $status, $dataLiquidacao, $usuarioId)) {
                $sucessoMsg = "Status da conta atualizado.";
            } else {
                $erroMsg = "Erro ao atualizar o status da conta.";
            }
        }
    }

    // 4. Excluir Conta (Gerente apenas)
    elseif ($acao === 'excluir') {
        if ($nivelUsuario !== 'gerente') {
            $erroMsg = "Permissão negada. Apenas gerentes podem excluir contas.";
        } else {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                if (AccountModel::delete($id, $usuarioId)) {
                    $sucessoMsg = "Conta excluída com sucesso.";
                } else {
                    $erroMsg = "Erro ao excluir a conta.";
                }
            }
        }
    }
}

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

// Listagem de Categorias e Fornecedores cadastrados para filtros e datalists
$db = \CantinaFinanceiro\Database::getConnection();
$stmtCategorias = $db->query("SELECT * FROM categorias ORDER BY nome ASC");
$categoriasDisponiveis = $stmtCategorias->fetchAll();

$stmtFornecedores = $db->query("SELECT * FROM fornecedores ORDER BY nome ASC");
$fornecedoresDisponiveis = $stmtFornecedores->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Contas - Cantina Sant'Anna</title>
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
                <a href="contas.php" class="nav-link active">
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
            <!-- Alert banners -->
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-0">Contas a Pagar e Receber</h1>
                    <p class="text-muted small mb-0">Gerencie todos os lançamentos financeiros da Cantina Sant'Anna.</p>
                </div>
                <button class="btn btn-premium" data-bs-toggle="modal" data-bs-target="#modalCadastrar">
                    <i class="fa-solid fa-plus me-1"></i> Novo Lançamento
                </button>
            </div>

            <!-- Filters Panel -->
            <div class="card card-glass p-3 mb-4">
                <form method="GET" action="contas.php" class="row g-2 align-items-end">
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label text-muted small fw-semibold">Busca Rápida</label>
                        <input type="text" name="busca" class="form-control form-control-premium form-control-sm" placeholder="Buscar..." value="<?= htmlspecialchars($filtros['busca'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-1.5 col-sm-6">
                        <label class="form-label text-muted small fw-semibold">Fluxo</label>
                        <select name="tipo" class="form-select form-select-premium form-select-sm">
                            <option value="">Todos</option>
                            <option value="pagar" <?= $filtros['tipo'] === 'pagar' ? 'selected' : '' ?>>A Pagar</option>
                            <option value="receber" <?= $filtros['tipo'] === 'receber' ? 'selected' : '' ?>>A Receber</option>
                        </select>
                    </div>
                    <div class="col-md-1.5 col-sm-6">
                        <label class="form-label text-muted small fw-semibold">Status</label>
                        <select name="status" class="form-select form-select-premium form-select-sm">
                            <option value="">Todos</option>
                            <option value="pendente" <?= $filtros['status'] === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                            <option value="pago" <?= $filtros['status'] === 'pago' ? 'selected' : '' ?>>Pago</option>
                            <option value="cancelado" <?= $filtros['status'] === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label text-muted small fw-semibold">Categoria</label>
                        <select name="categoria_id" class="form-select form-select-premium form-select-sm">
                            <option value="">Todas</option>
                            <?php foreach ($categoriasDisponiveis as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= (string)$filtros['categoria_id'] === (string)$cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label text-muted small fw-semibold">Fornecedor</label>
                        <select name="fornecedor_id" class="form-select form-select-premium form-select-sm">
                            <option value="">Todos</option>
                            <?php foreach ($fornecedoresDisponiveis as $forn): ?>
                                <option value="<?= $forn['id'] ?>" <?= (string)$filtros['fornecedor_id'] === (string)$forn['id'] ? 'selected' : '' ?>><?= htmlspecialchars($forn['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-1.5 col-sm-6">
                        <label class="form-label text-muted small fw-semibold">Início</label>
                        <input type="date" name="data_inicio" class="form-control form-control-premium form-control-sm" value="<?= htmlspecialchars($filtros['data_inicio'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-1.5 col-sm-6">
                        <label class="form-label text-muted small fw-semibold">Fim</label>
                        <input type="date" name="data_fim" class="form-control form-control-premium form-control-sm" value="<?= htmlspecialchars($filtros['data_fim'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-2 col-sm-12 d-flex gap-2">
                        <button type="submit" class="btn btn-premium btn-sm w-100"><i class="fa-solid fa-filter"></i> Filtrar</button>
                        <a href="contas.php" class="btn btn-outline-secondary btn-sm w-100"><i class="fa-solid fa-rotate-left"></i> Limpar</a>
                    </div>
                </form>
            </div>

            <!-- Accounts Table -->
            <div class="card card-glass p-3">
                <div class="table-responsive">
                    <table class="table table-premium mb-0">
                        <thead>
                            <tr>
                                <th>Descrição</th>
                                <th>Fluxo</th>
                                <th>Categoria</th>
                                <th>Vencimento</th>
                                <th>Status</th>
                                <th class="text-end">Valor</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($listaContas)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Nenhuma conta encontrada com os filtros selecionados.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($listaContas as $c): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($c['descricao'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <div class="small text-muted-premium d-flex gap-2 flex-wrap mt-1" style="font-size: 0.75rem;">
                                                <?php if (!empty($c['fornecedor_nome'])): ?>
                                                    <span><i class="fa-solid fa-truck-field me-1"></i> <?= htmlspecialchars($c['fornecedor_nome'], ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php else: ?>
                                                    <span class="opacity-50"><i class="fa-solid fa-truck-field me-1"></i> Não Informado</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($c['observacoes']): ?>
                                                <small class="text-muted text-truncate d-block mt-1" style="max-width: 250px;"><?= htmlspecialchars($c['observacoes'], ENT_QUOTES, 'UTF-8') ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($c['tipo'] === 'pagar'): ?>
                                                <span class="custom-badge badge-cancelado px-2 py-1"><i class="fa-solid fa-arrow-down text-danger"></i> A Pagar</span>
                                            <?php else: ?>
                                                <span class="custom-badge badge-pago px-2 py-1"><i class="fa-solid fa-arrow-up text-success"></i> A Receber</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($c['categoria_nome'])): ?>
                                                <span class="badge bg-light text-secondary"><?= htmlspecialchars($c['categoria_nome'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-secondary text-opacity-50">Não Informado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted"><?= date('d/m/Y', strtotime($c['data_vencimento'])) ?></td>
                                        <td>
                                            <?php if ($c['status'] === 'pago'): ?>
                                                <span class="custom-badge badge-pago"><i class="fa-solid fa-circle-check"></i> Pago</span>
                                            <?php elseif ($c['status'] === 'cancelado'): ?>
                                                <span class="custom-badge badge-cancelado"><i class="fa-solid fa-ban"></i> Cancelado</span>
                                            <?php else: ?>
                                                <?php if ($c['data_vencimento'] < date('Y-m-d')): ?>
                                                    <span class="custom-badge badge-pulse-danger"><i class="fa-solid fa-triangle-exclamation"></i> Vencida</span>
                                                <?php else: ?>
                                                    <span class="custom-badge badge-pendente"><i class="fa-solid fa-clock"></i> Pendente</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end fw-bold">
                                            R$ <?= number_format($c['valor'] / 100, 2, ',', '.') ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-inline-flex gap-1">
                                                <!-- Quick Mark as Paid/Pendente Button -->
                                                <form method="POST" action="contas.php" style="display:inline;">
                                                    <input type="hidden" name="acao" value="liquidar">
                                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                    <?php if ($c['status'] === 'pendente'): ?>
                                                        <input type="hidden" name="status" value="pago">
                                                        <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-2 py-1" title="Marcar como Pago"><i class="fa-solid fa-check"></i></button>
                                                    <?php else: ?>
                                                        <input type="hidden" name="status" value="pendente">
                                                        <button type="submit" class="btn btn-sm btn-outline-warning rounded-pill px-2 py-1" title="Reverter para Pendente"><i class="fa-solid fa-rotate-left"></i></button>
                                                    <?php endif; ?>
                                                </form>

                                                <?php if ($nivelUsuario === 'gerente'): ?>
                                                    <!-- Edit Button -->
                                                    <button class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#modalEditar"
                                                            data-id="<?= $c['id'] ?>"
                                                            data-descricao="<?= htmlspecialchars($c['descricao'], ENT_QUOTES, 'UTF-8') ?>"
                                                            data-valor="<?= number_format($c['valor'] / 100, 2, ',', '.') ?>"
                                                            data-status="<?= $c['status'] ?>"
                                                            data-vencimento="<?= $c['data_vencimento'] ?>"
                                                            data-liquidacao="<?= $c['data_liquidacao'] ?>"
                                                            data-categoria="<?= htmlspecialchars($c['categoria_nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                            data-fornecedor="<?= htmlspecialchars($c['fornecedor_nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                            data-observacoes="<?= htmlspecialchars($c['observacoes'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </button>

                                                    <!-- Delete Button -->
                                                    <form method="POST" action="contas.php" onsubmit="return confirm('Deseja realmente excluir esta conta?');" style="display:inline;">
                                                        <input type="hidden" name="acao" value="excluir">
                                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1" title="Excluir Lançamento"><i class="fa-solid fa-trash"></i></button>
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

<!-- ==================================================== -->
<!-- MODAL: CADASTRAR CONTA -->
<!-- ==================================================== -->
<div class="modal fade" id="modalCadastrar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-glass">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Adicionar Lançamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="contas.php">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="cadastrar">
                    
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Descrição *</label>
                        <input type="text" name="descricao" class="form-control form-control-premium" placeholder="Ex: Conta de Luz cantina" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Valor (R$) *</label>
                            <input type="text" name="valor" class="form-control form-control-premium money-mask" placeholder="0,00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Fluxo (Tipo) *</label>
                            <select name="tipo" class="form-select form-select-premium" required>
                                <option value="pagar">A Pagar (Despesa)</option>
                                <option value="receber">A Receber (Receita)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Data Vencimento *</label>
                            <input type="date" name="data_vencimento" class="form-control form-control-premium" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Status Inicial</label>
                            <select name="status" class="form-select form-select-premium">
                                <option value="pendente">Pendente</option>
                                <option value="pago">Pago / Recebido</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Categoria</label>
                            <input type="text" name="categoria" class="form-control form-control-premium" placeholder="Ex: Alimentos, Serviços" list="datalist-categorias">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Fornecedor / Cliente</label>
                            <input type="text" name="fornecedor" class="form-control form-control-premium" placeholder="Ex: Coca-Cola, Prefeitura" list="datalist-fornecedores">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Observações</label>
                        <textarea name="observacoes" class="form-control form-control-premium" rows="2" placeholder="Observações opcionais..."></textarea>
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

<!-- ==================================================== -->
<!-- MODAL: EDITAR CONTA (GERENTE APENAS) -->
<!-- ==================================================== -->
<?php if ($nivelUsuario === 'gerente'): ?>
<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-glass">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Editar Lançamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="contas.php">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="editar">
                    <input type="hidden" name="id" id="edit-id">
                    
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Descrição *</label>
                        <input type="text" name="descricao" id="edit-descricao" class="form-control form-control-premium" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Valor (R$) *</label>
                            <input type="text" name="valor" id="edit-valor" class="form-control form-control-premium money-mask" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Data Liquidação</label>
                            <input type="date" name="data_liquidacao" id="edit-liquidacao" class="form-control form-control-premium">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Categoria</label>
                            <input type="text" name="categoria" id="edit-categoria" class="form-control form-control-premium" placeholder="Ex: Alimentos, Serviços" list="datalist-categorias">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Fornecedor / Cliente</label>
                            <input type="text" name="fornecedor" id="edit-fornecedor" class="form-control form-control-premium" placeholder="Ex: Coca-Cola, Prefeitura" list="datalist-fornecedores">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-12">
                            <label class="form-label text-muted small fw-semibold">Data Vencimento *</label>
                            <input type="date" name="data_vencimento" id="edit-vencimento" class="form-control form-control-premium" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Status</label>
                        <select name="status" id="edit-status" class="form-select form-select-premium">
                            <option value="pendente">Pendente</option>
                            <option value="pago">Pago / Recebido</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Observações</label>
                        <textarea name="observacoes" id="edit-observacoes" class="form-control form-control-premium" rows="2"></textarea>
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
<?php endif; ?>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ----------------------------------------------------
    // Máscara Simples para Moeda (BRL)
    // ----------------------------------------------------
    document.querySelectorAll('.money-mask').forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, "");
            value = (value / 100).toFixed(2) + "";
            value = value.replace(".", ",");
            value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");
            e.target.value = value;
        });
    });

    // ----------------------------------------------------
    // Carregamento de dados no modal de edição
    // ----------------------------------------------------
    const modalEditar = document.getElementById('modalEditar');
    if (modalEditar) {
        modalEditar.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            
            document.getElementById('edit-id').value = button.getAttribute('data-id');
            document.getElementById('edit-descricao').value = button.getAttribute('data-descricao');
            document.getElementById('edit-valor').value = button.getAttribute('data-valor');
            document.getElementById('edit-categoria').value = button.getAttribute('data-categoria');
            document.getElementById('edit-fornecedor').value = button.getAttribute('data-fornecedor');
            document.getElementById('edit-vencimento').value = button.getAttribute('data-vencimento');
            document.getElementById('edit-liquidacao').value = button.getAttribute('data-liquidacao');
            document.getElementById('edit-status').value = button.getAttribute('data-status');
            document.getElementById('edit-observacoes').value = button.getAttribute('data-observacoes');
        });
    }
</script>

<datalist id="datalist-categorias">
    <?php foreach ($categoriasDisponiveis as $cat): ?>
        <option value="<?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>
</datalist>

<datalist id="datalist-fornecedores">
    <?php foreach ($fornecedoresDisponiveis as $forn): ?>
        <option value="<?= htmlspecialchars($forn['nome'], ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>
</datalist>

</body>
</html>
