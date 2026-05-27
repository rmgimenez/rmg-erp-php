<?php
/**
 * Manutenção de Cadastros - Categorias e Fornecedores
 * CRUD completo, informações adicionais de fornecedores e mesclagem de duplicados em cascata.
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

// ----------------------------------------------------
// Processamento de Ações do Formulário (POST)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // Ações de Categoria
    if ($acao === 'cadastrar_categoria') {
        $nome = trim($_POST['nome'] ?? '');
        if (!empty($nome)) {
            try {
                $stmt = $db->prepare("INSERT INTO categorias (nome) VALUES (?)");
                $stmt->execute([$nome]);
                $sucessoMsg = "Categoria '{$nome}' cadastrada com sucesso.";
                Auth::logAction($usuarioId, 'CATEGORIA_CADASTRAR', "Categoria '{$nome}' cadastrada.");
            } catch (\PDOException $e) {
                if ($e->getCode() == 23000) {
                    $erroMsg = "Erro: Já existe uma categoria com este nome.";
                } else {
                    $erroMsg = "Erro ao cadastrar categoria: " . $e->getMessage();
                }
            }
        } else {
            $erroMsg = "O nome da categoria não pode ser vazio.";
        }
    }

    elseif ($acao === 'editar_categoria') {
        if ($nivelUsuario !== 'gerente') {
            $erroMsg = "Permissão negada. Apenas gerentes podem editar cadastros.";
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $nome = trim($_POST['nome'] ?? '');
            if ($id > 0 && !empty($nome)) {
                try {
                    $stmt = $db->prepare("UPDATE categorias SET nome = ? WHERE id = ?");
                    $stmt->execute([$nome, $id]);
                    $sucessoMsg = "Categoria atualizada com sucesso.";
                    Auth::logAction($usuarioId, 'CATEGORIA_EDITAR', "Categoria ID #{$id} alterada para '{$nome}'.");
                } catch (\PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $erroMsg = "Erro: Já existe uma categoria com este nome.";
                    } else {
                        $erroMsg = "Erro ao atualizar categoria: " . $e->getMessage();
                    }
                }
            } else {
                $erroMsg = "Preencha todos os campos obrigatórios.";
            }
        }
    }

    elseif ($acao === 'excluir_categoria') {
        if ($nivelUsuario !== 'gerente') {
            $erroMsg = "Permissão negada. Apenas gerentes podem excluir cadastros.";
        } else {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $db->prepare("DELETE FROM categorias WHERE id = ?");
                $stmt->execute([$id]);
                $sucessoMsg = "Categoria excluída com sucesso. Lançamentos associados agora aparecem como 'Não Informado'.";
                Auth::logAction($usuarioId, 'CATEGORIA_EXCLUIR', "Categoria ID #{$id} excluída.");
            }
        }
    }

    elseif ($acao === 'mesclar_categorias') {
        if ($nivelUsuario !== 'gerente') {
            $erroMsg = "Permissão negada. Apenas gerentes podem mesclar cadastros.";
        } else {
            $origemId = (int)($_POST['origem_id'] ?? 0);
            $destinoId = (int)($_POST['destino_id'] ?? 0);

            if ($origemId > 0 && $destinoId > 0 && $origemId !== $destinoId) {
                try {
                    $db->beginTransaction();

                    // Atualiza as contas associadas
                    $stmtUpdate = $db->prepare("UPDATE contas SET categoria_id = ? WHERE categoria_id = ?");
                    $stmtUpdate->execute([$destinoId, $origemId]);
                    $contasAfetadas = $stmtUpdate->rowCount();

                    // Busca os nomes para o log amigável
                    $nomeOrigem = $db->query("SELECT nome FROM categorias WHERE id = $origemId")->fetchColumn();
                    $nomeDestino = $db->query("SELECT nome FROM categorias WHERE id = $destinoId")->fetchColumn();

                    // Deleta a categoria de origem (duplicada)
                    $stmtDelete = $db->prepare("DELETE FROM categorias WHERE id = ?");
                    $stmtDelete->execute([$origemId]);

                    $db->commit();
                    $sucessoMsg = "Mesclagem concluída! A categoria '{$nomeOrigem}' foi fundida em '{$nomeDestino}'. {$contasAfetadas} lançamento(s) atualizado(s).";
                    Auth::logAction($usuarioId, 'CATEGORIA_MESCLAR', "Categoria '{$nomeOrigem}' mesclada em '{$nomeDestino}'. {$contasAfetadas} conta(s) afetada(s).");
                } catch (\Exception $e) {
                    $db->rollBack();
                    $erroMsg = "Erro na mesclagem de categorias: " . $e->getMessage();
                }
            } else {
                $erroMsg = "Selecione categorias válidas e diferentes para mesclar.";
            }
        }
    }

    // Ações de Fornecedores
    elseif ($acao === 'cadastrar_fornecedor') {
        $nome = trim($_POST['nome'] ?? '');
        $contato = trim($_POST['contato'] ?? '');
        $obs = trim($_POST['observacoes'] ?? '');

        if (!empty($nome)) {
            try {
                $stmt = $db->prepare("INSERT INTO fornecedores (nome, contato, observacoes) VALUES (?, ?, ?)");
                $stmt->execute([$nome, $contato, $obs]);
                $sucessoMsg = "Fornecedor '{$nome}' cadastrado com sucesso.";
                Auth::logAction($usuarioId, 'FORNECEDOR_CADASTRAR', "Fornecedor '{$nome}' cadastrado.");
            } catch (\PDOException $e) {
                if ($e->getCode() == 23000) {
                    $erroMsg = "Erro: Já existe um fornecedor com este nome.";
                } else {
                    $erroMsg = "Erro ao cadastrar fornecedor: " . $e->getMessage();
                }
            }
        } else {
            $erroMsg = "O nome do fornecedor não pode ser vazio.";
        }
    }

    elseif ($acao === 'editar_fornecedor') {
        if ($nivelUsuario !== 'gerente') {
            $erroMsg = "Permissão negada. Apenas gerentes podem editar cadastros.";
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $nome = trim($_POST['nome'] ?? '');
            $contato = trim($_POST['contato'] ?? '');
            $obs = trim($_POST['observacoes'] ?? '');

            if ($id > 0 && !empty($nome)) {
                try {
                    $stmt = $db->prepare("UPDATE fornecedores SET nome = ?, contato = ?, observacoes = ? WHERE id = ?");
                    $stmt->execute([$nome, $contato, $obs, $id]);
                    $sucessoMsg = "Fornecedor atualizado com sucesso.";
                    Auth::logAction($usuarioId, 'FORNECEDOR_EDITAR', "Fornecedor ID #{$id} alterado para '{$nome}'.");
                } catch (\PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $erroMsg = "Erro: Já existe um fornecedor com este nome.";
                    } else {
                        $erroMsg = "Erro ao atualizar fornecedor: " . $e->getMessage();
                    }
                }
            } else {
                $erroMsg = "Preencha todos os campos obrigatórios.";
            }
        }
    }

    elseif ($acao === 'excluir_fornecedor') {
        if ($nivelUsuario !== 'gerente') {
            $erroMsg = "Permissão negada. Apenas gerentes podem excluir cadastros.";
        } else {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $db->prepare("DELETE FROM fornecedores WHERE id = ?");
                $stmt->execute([$id]);
                $sucessoMsg = "Fornecedor excluído com sucesso. Lançamentos associados agora aparecem como 'Não Informado'.";
                Auth::logAction($usuarioId, 'FORNECEDOR_EXCLUIR', "Fornecedor ID #{$id} excluído.");
            }
        }
    }

    elseif ($acao === 'mesclar_fornecedores') {
        if ($nivelUsuario !== 'gerente') {
            $erroMsg = "Permissão negada. Apenas gerentes podem mesclar cadastros.";
        } else {
            $origemId = (int)($_POST['origem_id'] ?? 0);
            $destinoId = (int)($_POST['destino_id'] ?? 0);

            if ($origemId > 0 && $destinoId > 0 && $origemId !== $destinoId) {
                try {
                    $db->beginTransaction();

                    // Atualiza as contas associadas
                    $stmtUpdate = $db->prepare("UPDATE contas SET fornecedor_id = ? WHERE fornecedor_id = ?");
                    $stmtUpdate->execute([$destinoId, $origemId]);
                    $contasAfetadas = $stmtUpdate->rowCount();

                    // Busca os nomes para o log amigável
                    $nomeOrigem = $db->query("SELECT nome FROM fornecedores WHERE id = $origemId")->fetchColumn();
                    $nomeDestino = $db->query("SELECT nome FROM fornecedores WHERE id = $destinoId")->fetchColumn();

                    // Deleta o fornecedor de origem (duplicado)
                    $stmtDelete = $db->prepare("DELETE FROM fornecedores WHERE id = ?");
                    $stmtDelete->execute([$origemId]);

                    $db->commit();
                    $sucessoMsg = "Mesclagem concluída! O fornecedor '{$nomeOrigem}' foi fundido em '{$nomeDestino}'. {$contasAfetadas} lançamento(s) atualizado(s).";
                    Auth::logAction($usuarioId, 'FORNECEDOR_MESCLAR', "Fornecedor '{$nomeOrigem}' mesclado em '{$nomeDestino}'. {$contasAfetadas} conta(s) afetada(s).");
                } catch (\Exception $e) {
                    $db->rollBack();
                    $erroMsg = "Erro na mesclagem de fornecedores: " . $e->getMessage();
                }
            } else {
                $erroMsg = "Selecione fornecedores válidos e diferentes para mesclar.";
            }
        }
    }
}

// ----------------------------------------------------
// Consulta dados para renderização
// ----------------------------------------------------
$stmtCats = $db->query("SELECT * FROM categorias ORDER BY nome ASC");
$listaCategorias = $stmtCats->fetchAll();

$stmtForns = $db->query("SELECT * FROM fornecedores ORDER BY nome ASC");
$listaFornecedores = $stmtForns->fetchAll();

$pageTitle = 'Cadastros';
$activePage = 'cadastros';
?>
<?php require_once __DIR__ . '/src/includes/layout_start.php'; ?>

<!-- Alertas -->
<?php require_once __DIR__ . '/src/includes/alerts.php'; ?>

            <!-- Page Header -->
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-0">Manutenção de Cadastros</h1>
                    <p class="text-muted small mb-0">Gerencie as categorias e fornecedores da base, ou mescle duplicados para higienizar os relatórios.</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalCadastrarCategoria">
                        <i class="fa-solid fa-plus me-1"></i> Nova Categoria
                    </button>
                    <button class="btn btn-premium" data-bs-toggle="modal" data-bs-target="#modalCadastrarFornecedor">
                        <i class="fa-solid fa-plus me-1"></i> Novo Fornecedor
                    </button>
                </div>
            </div>

            <!-- Nav tabs -->
            <ul class="nav nav-pills mb-4 gap-2" id="cadastrosTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="btn btn-outline-primary active px-4" id="categories-tab" data-bs-toggle="pill" data-bs-target="#categories-panel" type="button" role="tab"><i class="fa-solid fa-tags me-1"></i> Categorias</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="btn btn-outline-primary px-4" id="suppliers-tab" data-bs-toggle="pill" data-bs-target="#suppliers-panel" type="button" role="tab"><i class="fa-solid fa-truck-field me-1"></i> Fornecedores / Clientes</button>
                </li>
            </ul>

            <!-- Tab content -->
            <div class="tab-content" id="cadastrosTabContent">
                <!-- PANEL: CATEGORIAS -->
                <div class="tab-pane fade show active" id="categories-panel" role="tabpanel">
                    <div class="card card-glass p-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0 text-muted small"><i class="fa-solid fa-tags text-primary me-2"></i> Categorias Ativas</h5>
                            <?php if ($nivelUsuario === 'gerente'): ?>
                                <button class="btn btn-sm btn-outline-warning rounded-pill" data-bs-toggle="modal" data-bs-target="#modalMesclarCategorias"><i class="fa-solid fa-code-merge me-1"></i> Mesclar Duplicados</button>
                            <?php endif; ?>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-premium mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome da Categoria</th>
                                        <th>Criado em</th>
                                        <?php if ($nivelUsuario === 'gerente'): ?>
                                            <th class="text-center">Ações</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($listaCategorias)): ?>
                                        <tr><td colspan="4" class="text-center text-muted py-4">Nenhuma categoria cadastrada.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($listaCategorias as $cat): ?>
                                            <tr>
                                                <td>#<?= $cat['id'] ?></td>
                                                <td class="fw-bold"><?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="text-muted"><?= date('d/m/Y H:i', strtotime($cat['criado_em'])) ?></td>
                                                <?php if ($nivelUsuario === 'gerente'): ?>
                                                    <td class="text-center">
                                                        <div class="d-inline-flex gap-1">
                                                            <button class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1" data-bs-toggle="modal" data-bs-target="#modalEditarCategoria" data-id="<?= $cat['id'] ?>" data-nome="<?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-pen"></i></button>
                                                            <form method="POST" action="cadastros.php" onsubmit="return confirm('Excluir esta categoria? Os lançamentos vinculados ficarão sem categoria (Não Informado).');" style="display:inline;">
                                                                <input type="hidden" name="acao" value="excluir_categoria">
                                                                <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1"><i class="fa-solid fa-trash"></i></button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- PANEL: FORNECEDORES -->
                <div class="tab-pane fade" id="suppliers-panel" role="tabpanel">
                    <div class="card card-glass p-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0 text-muted small"><i class="fa-solid fa-truck-field text-primary me-2"></i> Fornecedores e Contatos</h5>
                            <?php if ($nivelUsuario === 'gerente'): ?>
                                <button class="btn btn-sm btn-outline-warning rounded-pill" data-bs-toggle="modal" data-bs-target="#modalMesclarFornecedores"><i class="fa-solid fa-code-merge me-1"></i> Mesclar Duplicados</button>
                            <?php endif; ?>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-premium mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome / Razão</th>
                                        <th>Contato</th>
                                        <th>Observações</th>
                                        <th>Criado em</th>
                                        <?php if ($nivelUsuario === 'gerente'): ?>
                                            <th class="text-center">Ações</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($listaFornecedores)): ?>
                                        <tr><td colspan="6" class="text-center text-muted py-4">Nenhum fornecedor cadastrado.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($listaFornecedores as $forn): ?>
                                            <tr>
                                                <td>#<?= $forn['id'] ?></td>
                                                <td class="fw-bold"><?= htmlspecialchars($forn['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td>
                                                    <?php if ($forn['contato']): ?>
                                                        <span class="badge bg-light text-secondary"><i class="fa-solid fa-address-card me-1 text-primary"></i> <?= htmlspecialchars($forn['contato'], ENT_QUOTES, 'UTF-8') ?></span>
                                                    <?php else: ?>
                                                        <span class="opacity-50 small">Não informado</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($forn['observacoes']): ?>
                                                        <small class="text-muted d-block text-truncate" style="max-width:250px;" title="<?= htmlspecialchars($forn['observacoes'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($forn['observacoes'], ENT_QUOTES, 'UTF-8') ?></small>
                                                    <?php else: ?>
                                                        <span class="opacity-50 small">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-muted"><?= date('d/m/Y H:i', strtotime($forn['criado_em'])) ?></td>
                                                <?php if ($nivelUsuario === 'gerente'): ?>
                                                    <td class="text-center">
                                                        <div class="d-inline-flex gap-1">
                                                            <button class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#modalEditarFornecedor" 
                                                                    data-id="<?= $forn['id'] ?>" 
                                                                    data-nome="<?= htmlspecialchars($forn['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                                                    data-contato="<?= htmlspecialchars($forn['contato'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                                    data-obs="<?= htmlspecialchars($forn['observacoes'] ?? '', ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-pen"></i></button>
                                                            <form method="POST" action="cadastros.php" onsubmit="return confirm('Excluir este fornecedor? Os lançamentos vinculados ficarão sem fornecedor (Não Informado).');" style="display:inline;">
                                                                <input type="hidden" name="acao" value="excluir_fornecedor">
                                                                <input type="hidden" name="id" value="<?= $forn['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1"><i class="fa-solid fa-trash"></i></button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                <?php endif; ?>
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
// MODAIS DE CATEGORIA
<!-- ==================================================== -->
<!-- Modal: Cadastrar Categoria -->
<div class="modal fade" id="modalCadastrarCategoria" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-glass">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Cadastrar Categoria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="cadastros.php">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="cadastrar_categoria">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Nome da Categoria *</label>
                        <input type="text" name="nome" class="form-control form-control-premium" placeholder="Ex: Higiene, Congelados" required>
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

<!-- Modal: Editar Categoria -->
<div class="modal fade" id="modalEditarCategoria" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-glass">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Editar Categoria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="cadastros.php">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="editar_categoria">
                    <input type="hidden" name="id" id="edit-cat-id">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Nome da Categoria *</label>
                        <input type="text" name="nome" id="edit-cat-nome" class="form-control form-control-premium" required>
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

<!-- Modal: Mesclar Categorias -->
<?php if ($nivelUsuario === 'gerente'): ?>
<div class="modal fade" id="modalMesclarCategorias" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-glass">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Mesclar Categorias Duplicadas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="cadastros.php">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="mesclar_categorias">
                    
                    <div class="alert alert-warning small border-0 shadow-sm" role="alert">
                        <i class="fa-solid fa-circle-exclamation me-1"></i>
                        Esta operação irá mover **todas as contas** da categoria duplicada para a categoria principal selecionada, e depois **excluirá** o cadastro duplicado.
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Categoria Duplicada (Será Apagada) *</label>
                        <select name="origem_id" class="form-select form-select-premium" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($listaCategorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Categoria Principal (Será Mantida) *</label>
                        <select name="destino_id" class="form-select form-select-premium" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($listaCategorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-premium px-4"><i class="fa-solid fa-code-merge me-1"></i> Mesclar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ==================================================== -->
// MODAIS DE FORNECEDORES
<!-- ==================================================== -->
<!-- Modal: Cadastrar Fornecedor -->
<div class="modal fade" id="modalCadastrarFornecedor" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-glass">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Cadastrar Fornecedor / Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="cadastros.php">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="cadastrar_fornecedor">
                    
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Nome / Razão Social *</label>
                        <input type="text" name="nome" class="form-control form-control-premium" placeholder="Ex: Ambev S.A." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Informações de Contato</label>
                        <input type="text" name="contato" class="form-control form-control-premium" placeholder="Telefone, E-mail ou Representante">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Observações</label>
                        <textarea name="observacoes" class="form-control form-control-premium" rows="3" placeholder="Prazos, dias de entrega ou notas operacionais..."></textarea>
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

<!-- Modal: Editar Fornecedor -->
<div class="modal fade" id="modalEditarFornecedor" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-glass">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Editar Fornecedor / Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="cadastros.php">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="editar_fornecedor">
                    <input type="hidden" name="id" id="edit-forn-id">
                    
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Nome / Razão Social *</label>
                        <input type="text" name="nome" id="edit-forn-nome" class="form-control form-control-premium" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Informações de Contato</label>
                        <input type="text" name="contato" id="edit-forn-contato" class="form-control form-control-premium">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Observações</label>
                        <textarea name="observacoes" id="edit-forn-obs" class="form-control form-control-premium" rows="3"></textarea>
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

<!-- Modal: Mesclar Fornecedores -->
<?php if ($nivelUsuario === 'gerente'): ?>
<div class="modal fade" id="modalMesclarFornecedores" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-glass">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Mesclar Fornecedores Duplicados</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="cadastros.php">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="mesclar_fornecedores">
                    
                    <div class="alert alert-warning small border-0 shadow-sm" role="alert">
                        <i class="fa-solid fa-circle-exclamation me-1"></i>
                        Esta operação irá mover **todas as contas** do fornecedor duplicado para o fornecedor principal selecionado, e depois **excluirá** o cadastro duplicado.
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Fornecedor Duplicado (Será Apagado) *</label>
                        <select name="origem_id" class="form-select form-select-premium" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($listaFornecedores as $forn): ?>
                                <option value="<?= $forn['id'] ?>"><?= htmlspecialchars($forn['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Fornecedor Principal (Será Mantido) *</label>
                        <select name="destino_id" class="form-select form-select-premium" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($listaFornecedores as $forn): ?>
                                <option value="<?= $forn['id'] ?>"><?= htmlspecialchars($forn['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-premium px-4"><i class="fa-solid fa-code-merge me-1"></i> Mesclar</button>
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
    // Carregamento de dados nos modais de edição
    // ----------------------------------------------------
    const modalEditarCategoria = document.getElementById('modalEditarCategoria');
    if (modalEditarCategoria) {
        modalEditarCategoria.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            document.getElementById('edit-cat-id').value = button.getAttribute('data-id');
            document.getElementById('edit-cat-nome').value = button.getAttribute('data-nome');
        });
    }

    const modalEditarFornecedor = document.getElementById('modalEditarFornecedor');
    if (modalEditarFornecedor) {
        modalEditarFornecedor.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            document.getElementById('edit-forn-id').value = button.getAttribute('data-id');
            document.getElementById('edit-forn-nome').value = button.getAttribute('data-nome');
            document.getElementById('edit-forn-contato').value = button.getAttribute('data-contato');
            document.getElementById('edit-forn-obs').value = button.getAttribute('data-obs');
        });
    }
</script>
<?php require_once __DIR__ . '/src/includes/layout_end.php'; ?>
