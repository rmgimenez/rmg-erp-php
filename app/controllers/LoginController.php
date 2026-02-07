<?php

require_once __DIR__ . '/../dao/UsuarioDAO.php';

class LoginController {
    
    public function logar($usuario, $senha) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($usuario) || empty($senha)) {
            return ['sucesso' => false, 'mensagem' => 'Preencha todos os campos.'];
        }

        $usuarioDAO = new UsuarioDAO();
        $userObj = $usuarioDAO->buscarPorLogin($usuario);

        if (!$userObj) {
            return ['sucesso' => false, 'mensagem' => 'Usuário ou senha inválidos.'];
        }

        if (!$userObj->getAtivo()) {
            return ['sucesso' => false, 'mensagem' => 'Este usuário está inativo.']; 
        }

        if (password_verify($senha, $userObj->getSenha())) {
            // Login bem sucedido
            $_SESSION['usuario_id'] = $userObj->getIdUsuario();
            $_SESSION['usuario_nome'] = $userObj->getNome();
            $_SESSION['usuario_login'] = $userObj->getUsuario();
            $_SESSION['usuario_tipo'] = $userObj->getTipoUsuario();
            $_SESSION['mostrar_alertas'] = true; // Flag para exibir o modal de alertas
            
            return ['sucesso' => true];
        } else {
            return ['sucesso' => false, 'mensagem' => 'Usuário ou senha inválidos.'];
        }
    }

    public function verificarLogado() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: login.php');
            exit;
        }
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        header('Location: login.php');
        exit;
    }
}
