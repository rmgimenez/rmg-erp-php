<?php
require_once __DIR__ . '/../app/controllers/LoginController.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigoEmpresa = $_POST['codigo_empresa'] ?? '';
    $usuario = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';

    $loginController = new LoginController();
    $resultado = $loginController->logar($codigoEmpresa, $usuario, $senha);

    if ($resultado['sucesso']) {
        // Super admin vai para o painel admin, demais para o dashboard
        if (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'super_admin') {
            header('Location: admin/index.php');
        } else {
            header('Location: index.php');
        }
        exit;
    } else {
        $erro = $resultado['mensagem'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gestão Financeira RMG</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/login.css" rel="stylesheet">
</head>

<body>

    <!-- Animated background shapes -->
    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>

    <div class="login-wrapper">
        <!-- Left: Branding Panel -->
        <div class="login-brand">
            <div class="brand-content">
                <div class="brand-icon">
                    <i class="fas fa-cube"></i>
                </div>
                <h1 class="brand-title">RMG ERP</h1>
                <p class="brand-tagline">Sistema de Gestão Financeira<br>e Controle de Bens — SaaS</p>
                <div class="brand-features">
                    <div class="feature-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Controle Financeiro</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-tools"></i>
                        <span>Gestão de Bens</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span>Relatórios Gerenciais</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Login Form -->
        <div class="login-form-panel">
            <div class="login-form-inner">
                <!-- Mobile brand (visible only on small screens) -->
                <div class="mobile-brand">
                    <div class="brand-icon-sm"><i class="fas fa-cube"></i></div>
                    <h2>RMG ERP</h2>
                </div>

                <div class="form-header">
                    <h2>Bem-vindo de volta</h2>
                    <p>Acesse sua conta para continuar</p>
                </div>

                <?php if (!empty($erro)): ?>
                    <div class="alert-custom alert-error" role="alert">
                        <div class="alert-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="alert-content">
                            <span><?php echo htmlspecialchars($erro); ?></span>
                        </div>
                        <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" id="loginForm" novalidate>
                    <div class="form-floating-group">
                        <div class="input-wrapper">
                            <i class="fas fa-building input-icon"></i>
                            <input type="text" class="form-input" id="codigo_empresa" name="codigo_empresa"
                                placeholder=" " autofocus
                                value="<?php echo isset($_POST['codigo_empresa']) ? htmlspecialchars($_POST['codigo_empresa']) : ''; ?>"
                                style="text-transform: uppercase;">
                            <label for="codigo_empresa" class="form-label-float">Código da Empresa</label>
                        </div>
                        <small class="text-muted d-block mt-1" style="font-size: 0.7rem; opacity: 0.6; padding-left: 2.5rem;">Deixe vazio ou digite ADMIN para acesso administrativo</small>
                    </div>

                    <div class="form-floating-group">
                        <div class="input-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" class="form-input" id="usuario" name="usuario"
                                placeholder=" " required
                                value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>">
                            <label for="usuario" class="form-label-float">Usuário</label>
                        </div>
                    </div>

                    <div class="form-floating-group">
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" class="form-input" id="senha" name="senha"
                                placeholder=" " required>
                            <label for="senha" class="form-label-float">Senha</label>
                            <button type="button" class="toggle-password" id="togglePassword" tabindex="-1" title="Mostrar/ocultar senha">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login" id="btnLogin">
                        <span class="btn-text">Entrar</span>
                        <span class="btn-loader" style="display:none;">
                            <i class="fas fa-circle-notch fa-spin"></i>
                        </span>
                        <i class="fas fa-arrow-right btn-arrow"></i>
                    </button>
                </form>

                <div class="login-footer">
                    <p>Desenvolvido por <strong>Ricardo Moura Gimenez</strong></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const senhaInput = document.getElementById('senha');
            const icon = this.querySelector('i');
            if (senhaInput.type === 'password') {
                senhaInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                senhaInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Submit button loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const usuario = document.getElementById('usuario').value.trim();
            const senha = document.getElementById('senha').value.trim();
            if (!usuario || !senha) {
                e.preventDefault();
                return;
            }
            // Forçar uppercase no código da empresa antes de enviar
            const codigoEmpresa = document.getElementById('codigo_empresa');
            codigoEmpresa.value = codigoEmpresa.value.toUpperCase().trim();

            const btn = document.getElementById('btnLogin');
            btn.classList.add('loading');
            btn.querySelector('.btn-text').style.display = 'none';
            btn.querySelector('.btn-arrow').style.display = 'none';
            btn.querySelector('.btn-loader').style.display = 'inline-block';
            btn.disabled = true;
        });

        // Add focus animation to inputs
        document.querySelectorAll('.form-input').forEach(function(input) {
            input.addEventListener('focus', function() {
                this.closest('.input-wrapper').classList.add('focused');
            });
            input.addEventListener('blur', function() {
                this.closest('.input-wrapper').classList.remove('focused');
            });
        });
    </script>
</body>

</html>