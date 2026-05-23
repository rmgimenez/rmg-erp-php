<?php
/**
 * Gerenciamento de Usuários (Apenas Gerentes)
 * Permite que gerentes cadastrem outros gerentes ou operadores.
 */

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';

use CantinaFinanceiro\Database;
use CantinaFinanceiro\Auth;

Auth::checkAuth();
Auth::restrictTo(['gerente']);

$usuarioIdLogado = $_SESSION['user_id'];
$db = Database::getConnection();

$sucessoMsg = '';
$erroMsg = '';

// ----------------------------------------------------
// Processamento de Ações do Formulário (POST)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // 1. Cadastrar Usuário
    if ($acao === 'cadastrar') {
        $nome = trim($_POST['nome'] ?? '');
        $usuario = trim($_POST['usuario'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $nivel = $_POST['nivel'] ?? '';

        if (!empty($nome) && !empty($usuario) && !empty($senha) && !empty($nivel)) {
            // Gerente só pode criar usuários com nível 'gerente' ou 'operador'
            if (!in_array($nivel, ['gerente', 'operador'])) {
                $erroMsg = "Nível de acesso inválido.";
            } else {
                try {
                    // Verifica se o usuário já existe
                    $stmtCheck = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE usuario = ?");
                    $stmtCheck->execute([$usuario]);
                    if ($stmtCheck->fetchColumn() > 0) {
                        $erroMsg = "Este nome de usuário já está cadastrado.";
                    } else {
                        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                        $stmtInsert = $db->prepare("INSERT INTO usuarios (nome, usuario, email, senha, nivel) VALUES (?, ?, ?, ?, ?)");
                        if ($stmtInsert->execute([$nome, $usuario, $email, $senhaHash, $nivel])) {
                            $sucessoMsg = "Usuário '{$usuario}' cadastrado com sucesso.";
                            Auth::logAction($usuarioIdLogado, 'USER_CADASTRAR', "Usuário '{$usuario}' com nível '{$nivel}' criado.");
                        }
                    }
                } catch (\Exception $e) {
                    $erroMsg = "Erro técnico ao cadastrar usuário: " . $e->getMessage();
                }
            }
        } else {
            $erroMsg = "Por favor, preencha todos os campos obrigatórios.";
        }
    }

    // 2. Editar Usuário
    elseif ($acao === 'editar') {
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $usuario = trim($_POST['usuario'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $nivel = $_POST['nivel'] ?? '';

        if ($id > 0 && !empty($nome) && !empty($usuario) && !empty($nivel)) {
            if (!in_array($nivel, ['gerente', 'operador'])) {
                $erroMsg = "Nível de acesso inválido.";
            } else {
                try {
                    // Valida se usuário não colide com outro ID
                    $stmtCheck = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE usuario = ? AND id != ?");
                    $stmtCheck->execute([$usuario, $id]);
                    if ($stmtCheck->fetchColumn() > 0) {
                        $erroMsg = "Este nome de usuário já está sendo usado por outra pessoa.";
                    } else {
                        // Se senha foi informada, atualiza-a, senão mantém a antiga
                        if (!empty($senha)) {
                            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                            $stmtUpdate = $db->prepare("UPDATE usuarios SET nome = ?, usuario = ?, email = ?, senha = ?, nivel = ? WHERE id = ?");
                            $exec = $stmtUpdate->execute([$nome, $usuario, $email, $senhaHash, $nivel, $id]);
                        } else {
                            $stmtUpdate = $db->prepare("UPDATE usuarios SET nome = ?, usuario = ?, email = ?, nivel = ? WHERE id = ?");
                            $exec = $stmtUpdate->execute([$nome, $usuario, $email, $nivel, $id]);
                        }

                        if ($exec) {
                            $sucessoMsg = "Usuário atualizado com sucesso.";
                            Auth::logAction($usuarioIdLogado, 'USER_EDITAR', "Usuário #{$id} ({$usuario}) atualizado.");
                            
                            // Se editou a si mesmo, atualiza os dados da sessão
                            if ($id === $usuarioIdLogado) {
                                $_SESSION['user_nome'] = $nome;
                                $_SESSION['user_usuario'] = $usuario;
                                $_SESSION['user_nivel'] = $nivel;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $erroMsg = "Erro técnico ao atualizar usuário.";
                }
            }
        }
    }

    // 3. Excluir Usuário
    elseif ($acao === 'excluir') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            if ($id === $usuarioIdLogado) {
                $erroMsg = "Você não pode excluir sua própria conta.";
            } else {
                try {
                    // Busca dados para o log
                    $stmtUser = $db->prepare("SELECT usuario FROM usuarios WHERE id = ?");
                    $stmtUser->execute([$id]);
                    $userName = $stmtUser->fetchColumn();

                    $stmtDel = $db->prepare("DELETE FROM usuarios WHERE id = ? AND nivel != 'admin'");
                    if ($stmtDel->execute([$id])) {
                        $sucessoMsg = "Usuário excluído com sucesso.";
                        Auth::logAction($usuarioIdLogado, 'USER_EXCLUIR', "Usuário #{$id} ({$userName}) excluído.");
                    }
                } catch (\Exception $e) {
                    $erroMsg = "Erro ao excluir usuário.";
                }
            }
        }
    }
}

// ----------------------------------------------------
// Carrega Lista de Usuários (Exclui os da Informática / Admin)
// ----------------------------------------------------
$stmtList = $db->prepare("SELECT id, nome, usuario, email, nivel, criado_em FROM usuarios WHERE nivel != 'admin' ORDER BY nome ASC");
$stmtList->execute();
$usuarios = $stmtList->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários - Cantina Sant'Anna</title>
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
                <a href="patrimonio.php" class="nav-link">
                    <i class="fa-solid fa-screwdriver-wrench me-2"></i> Patrimônio
                </a>
                <a href="usuarios.php" class="nav-link active">
                    <i class="fa-solid fa-users me-2"></i> Usuários
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
                    <h1 class="h3 fw-bold mb-0">Controle de Usuários</h1>
                    <p class="text-muted small mb-0">Cadastre e configure gerentes e operadores para operar o financeiro.</p>
                </div>
                <button class="btn btn-premium" data-bs-toggle="modal" data-bs-target="#modalCadastrarUsuario">
                    <i class="fa-solid fa-user-plus me-1"></i> Criar Usuário
                </button>
            </div>

            <!-- Users Grid / Table -->
            <div class="card card-glass p-3">
                <div class="table-responsive">
                    <table class="table table-premium mb-0">
                        <thead>
                            <tr>
                                <th>Nome Completo</th>
                                <th>Nome de Usuário</th>
                                <th>E-mail</th>
                                <th>Nível</th>
                                <th>Criado Em</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $u): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($u['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><code class="text-primary fs-6"><?= htmlspecialchars($u['usuario'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                    <td class="text-muted"><?= htmlspecialchars($u['email'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <?php if ($u['nivel'] === 'gerente'): ?>
                                            <span class="custom-badge badge-pago"><i class="fa-solid fa-user-tie"></i> Gerente</span>
                                        <?php else: ?>
                                            <span class="custom-badge badge-cancelado"><i class="fa-solid fa-cash-register"></i> Operador</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($u['criado_em'])) ?></td>
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-1">
                                            <!-- Edit User Button -->
                                            <button class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalEditarUsuario"
                                                    data-id="<?= $u['id'] ?>"
                                                    data-nome="<?= htmlspecialchars($u['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-usuario="<?= htmlspecialchars($u['usuario'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-email="<?= htmlspecialchars($u['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                    data-nivel="<?= $u['nivel'] ?>">
                                                <i class="fa-solid fa-user-pen"></i>
                                            </button>

                                            <!-- Delete User Button -->
                                            <?php if ($u['id'] !== $usuarioIdLogado): ?>
                                                <form method="POST" action="usuarios.php" onsubmit="return confirm('Deseja realmente excluir este usuário?');" style="display:inline;">
                                                    <input type="hidden" name="acao" value="excluir">
                                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1" title="Excluir Usuário"><i class="fa-solid fa-trash-can"></i></button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==================================================== -->
<!-- MODAL: CADASTRAR USUÁRIO -->
<!-- ==================================================== -->
<div class="modal fade" id="modalCadastrarUsuario" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-glass">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Cadastrar Novo Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="usuarios.php">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="cadastrar">
                    
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Nome Completo *</label>
                        <input type="text" name="nome" class="form-control form-control-premium" placeholder="Ex: Maria Souza" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Nome de Usuário (Login) *</label>
                            <input type="text" name="usuario" class="form-control form-control-premium" placeholder="Ex: maria.souza" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Nível de Acesso *</label>
                            <select name="nivel" class="form-select form-select-premium" required>
                                <option value="operador">Operador (Caixa)</option>
                                <option value="gerente">Gerente (Financeiro)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">E-mail</label>
                        <input type="email" name="email" class="form-control form-control-premium" placeholder="Ex: maria@email.com">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Senha Inicial *</label>
                        <input type="password" name="senha" class="form-control form-control-premium" placeholder="Defina a senha" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-premium px-4">Criar Conta</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================================================== -->
<!-- MODAL: EDITAR USUÁRIO -->
<!-- ==================================================== -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-glass">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Editar Cadastro de Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="usuarios.php">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="editar">
                    <input type="hidden" name="id" id="edit-user-id">
                    
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Nome Completo *</label>
                        <input type="text" name="nome" id="edit-user-nome" class="form-control form-control-premium" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Nome de Usuário (Login) *</label>
                            <input type="text" name="usuario" id="edit-user-usuario" class="form-control form-control-premium" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Nível de Acesso *</label>
                            <select name="nivel" id="edit-user-nivel" class="form-select form-select-premium" required>
                                <option value="operador">Operador (Caixa)</option>
                                <option value="gerente">Gerente (Financeiro)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">E-mail</label>
                        <input type="email" name="email" id="edit-user-email" class="form-control form-control-premium">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Nova Senha (Deixe em branco para não alterar)</label>
                        <input type="password" name="senha" class="form-control form-control-premium" placeholder="Senha do usuário">
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

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Carrega dados no modal de edição
    const modalEditarUsuario = document.getElementById('modalEditarUsuario');
    if (modalEditarUsuario) {
        modalEditarUsuario.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            
            document.getElementById('edit-user-id').value = button.getAttribute('data-id');
            document.getElementById('edit-user-nome').value = button.getAttribute('data-nome');
            document.getElementById('edit-user-usuario').value = button.getAttribute('data-usuario');
            document.getElementById('edit-user-email').value = button.getAttribute('data-email');
            document.getElementById('edit-user-nivel').value = button.getAttribute('data-nivel');
        });
    }
</script>
</body>
</html>
