<?php
/**
 * Página de Login
 * Responsável por autenticar os usuários com Usuário e Senha.
 */

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';

use CantinaFinanceiro\Database;
use CantinaFinanceiro\Auth;

// Inicializa banco no primeiro boot
Database::getConnection();

Auth::initSession();

// Redireciona se já estiver autenticado
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_nivel'] === 'admin') {
        header("Location: admin.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (!empty($usuario) && !empty($senha)) {
        if (Auth::login($usuario, $senha)) {
            if ($_SESSION['user_nivel'] === 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: index.php");
            }
            exit;
        } else {
            $erro = 'Usuário ou senha incorretos.';
        }
    } else {
        $erro = 'Por favor, preencha todos os campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cantina Financeiro</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e1b4b 0%, #311042 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            padding: 40px 30px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.2);
            animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-4">
        <div style="font-size: 2.2rem; font-weight: 700; color: #1e1b4b; line-height: 1.1;">
            Cantina<span style="color: var(--primary);">.</span>
        </div>
        <p class="text-muted small mt-1">Gestão de Contas a Pagar e Receber</p>
    </div>

    <?php if ($erro): ?>
        <div class="alert alert-danger border-0 rounded-3 text-center py-2 fs-6 mb-3" role="alert">
            <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['mensagem']) && $_GET['mensagem'] === 'logout_sucesso'): ?>
        <div class="alert alert-success border-0 rounded-3 text-center py-2 fs-6 mb-3" role="alert">
            Sessão encerrada com sucesso.
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="mb-3">
            <label for="usuario" class="form-label text-muted small fw-semibold">Nome de Usuário</label>
            <input type="text" 
                   class="form-control form-control-premium" 
                   id="usuario" 
                   name="usuario" 
                   placeholder="Ex: joao.silva" 
                   required 
                   autocomplete="username">
        </div>
        
        <div class="mb-4">
            <label for="senha" class="form-label text-muted small fw-semibold">Senha</label>
            <input type="password" 
                   class="form-control form-control-premium" 
                   id="senha" 
                   name="senha" 
                   placeholder="Digite sua senha" 
                   required 
                   autocomplete="current-password">
        </div>

        <button type="submit" class="btn btn-premium w-100 py-2 fs-6">Entrar no Painel</button>
    </form>
</div>

</body>
</html>
