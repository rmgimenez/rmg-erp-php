<?php
/**
 * Script de Logout
 * Limpa a sessão segura do usuário e redireciona para a tela de autenticação.
 */

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';

use CantinaFinanceiro\Auth;

Auth::logout();

// Redireciona para o login
header("Location: login.php?mensagem=logout_sucesso");
exit;
