<?php
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';

use CantinaFinanceiro\Auth;
use CantinaFinanceiro\Database;

Auth::checkAuth();

$db = Database::getConnection();
$usuarioId = $_SESSION['user_id'];

$sucessoMsg = '';
$erroMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senhaAtual = $_POST['senha_atual'] ?? '';
    $novaSenha = $_POST['nova_senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';

    if (empty($senhaAtual) || empty($novaSenha) || empty($confirmarSenha)) {
        $erroMsg = 'Preencha todos os campos.';
    } elseif ($novaSenha !== $confirmarSenha) {
        $erroMsg = 'A nova senha e a confirmação não conferem.';
    } elseif (strlen($novaSenha) < 4) {
        $erroMsg = 'A nova senha deve ter no mínimo 4 caracteres.';
    } else {
        $stmt = $db->prepare("SELECT senha FROM usuarios WHERE id = ? LIMIT 1");
        $stmt->execute([$usuarioId]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($senhaAtual, $user['senha'])) {
            $erroMsg = 'Senha atual incorreta.';
        } else {
            $novaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
            $stmtUp = $db->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
            if ($stmtUp->execute([$novaHash, $usuarioId])) {
                $sucessoMsg = 'Senha alterada com sucesso.';
                Auth::logAction($usuarioId, 'SENHA_ALTERAR', 'Senha alterada pelo próprio usuário.');
            } else {
                $erroMsg = 'Erro ao alterar senha. Tente novamente.';
            }
        }
    }
}

$pageTitle = 'Alterar Senha';
$activePage = 'alterar_senha';
require_once __DIR__ . '/src/includes/layout_start.php';
?>
<?php require_once __DIR__ . '/src/includes/alerts.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-0">Alterar Senha</h1>
        <p class="text-muted small mb-0">Mantenha sua senha segura alterando-a periodicamente.</p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card card-glass p-4">
            <form method="POST" action="alterar_senha.php" onsubmit="return validarSenha()">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-semibold">Senha Atual *</label>
                    <input type="password" name="senha_atual" id="senha_atual" class="form-control form-control-premium" placeholder="Digite sua senha atual" required autocomplete="current-password">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-semibold">Nova Senha *</label>
                    <input type="password" name="nova_senha" id="nova_senha" class="form-control form-control-premium" placeholder="Mínimo 4 caracteres" required minlength="4" autocomplete="new-password">
                </div>

                <div class="mb-4">
                    <label class="form-label text-muted small fw-semibold">Confirmar Nova Senha *</label>
                    <input type="password" name="confirmar_senha" id="confirmar_senha" class="form-control form-control-premium" placeholder="Repita a nova senha" required autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn-premium w-100 py-2">
                    <i class="fa-solid fa-key me-1"></i> Alterar Senha
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function validarSenha() {
        var nova = document.getElementById('nova_senha').value;
        var confirmar = document.getElementById('confirmar_senha').value;
        if (nova !== confirmar) {
            setTimeout(function () { exibirToast('A nova senha e a confirmação não conferem.', 'error'); }, 100);
            return false;
        }
        return true;
    }
</script>

<?php require_once __DIR__ . '/src/includes/layout_end.php'; ?>
