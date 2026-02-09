<?php

require_once __DIR__ . '/../dao/UsuarioDAO.php';
require_once __DIR__ . '/../dao/EmpresaDAO.php';

class LoginController
{

    /**
     * Login SaaS: requer código da empresa + login + senha
     * Para super_admin, o código da empresa deve ser vazio ou 'ADMIN'
     */
    public function logar($codigoEmpresa, $usuario, $senha)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($usuario) || empty($senha)) {
            return ['sucesso' => false, 'mensagem' => 'Preencha todos os campos.'];
        }

        $usuarioDAO = new UsuarioDAO();
        $codigoEmpresa = strtoupper(trim($codigoEmpresa));

        // Se o código da empresa estiver vazio ou for 'ADMIN', tenta login como super_admin
        if (empty($codigoEmpresa) || $codigoEmpresa === 'ADMIN') {
            $userObj = $usuarioDAO->buscarSuperAdminPorLogin($usuario);

            if (!$userObj) {
                return ['sucesso' => false, 'mensagem' => 'Usuário ou senha inválidos.'];
            }

            if (!$userObj->getAtivo()) {
                return ['sucesso' => false, 'mensagem' => 'Este usuário está inativo.'];
            }

            if (password_verify($senha, $userObj->getSenha())) {
                $_SESSION['usuario_id'] = $userObj->getIdUsuario();
                $_SESSION['usuario_nome'] = $userObj->getNome();
                $_SESSION['usuario_login'] = $userObj->getUsuario();
                $_SESSION['usuario_tipo'] = $userObj->getTipoUsuario();
                $_SESSION['empresa_id'] = null;
                $_SESSION['empresa_nome'] = 'Painel Administrativo SaaS';
                $_SESSION['empresa_codigo'] = 'ADMIN';
                $_SESSION['mostrar_alertas'] = false;

                return ['sucesso' => true];
            } else {
                return ['sucesso' => false, 'mensagem' => 'Usuário ou senha inválidos.'];
            }
        }

        // Login de usuário de empresa (gerente ou operador)
        $empresaDAO = new EmpresaDAO();
        $empresa = $empresaDAO->buscarPorCodigo($codigoEmpresa);

        if (!$empresa) {
            return ['sucesso' => false, 'mensagem' => 'Código da empresa não encontrado.'];
        }

        if (!$empresa->getAtiva()) {
            return ['sucesso' => false, 'mensagem' => 'Esta empresa está inativa. Entre em contato com o administrador.'];
        }

        $userObj = $usuarioDAO->buscarPorLoginEEmpresa($usuario, $empresa->getIdEmpresa());

        if (!$userObj) {
            return ['sucesso' => false, 'mensagem' => 'Usuário ou senha inválidos.'];
        }

        if (!$userObj->getAtivo()) {
            return ['sucesso' => false, 'mensagem' => 'Este usuário está inativo.'];
        }

        if (password_verify($senha, $userObj->getSenha())) {
            $_SESSION['usuario_id'] = $userObj->getIdUsuario();
            $_SESSION['usuario_nome'] = $userObj->getNome();
            $_SESSION['usuario_login'] = $userObj->getUsuario();
            $_SESSION['usuario_tipo'] = $userObj->getTipoUsuario();
            $_SESSION['empresa_id'] = $empresa->getIdEmpresa();
            $_SESSION['empresa_nome'] = $empresa->getNomeExibicao();
            $_SESSION['empresa_codigo'] = $empresa->getCodigo();
            $_SESSION['mostrar_alertas'] = true;

            return ['sucesso' => true];
        } else {
            return ['sucesso' => false, 'mensagem' => 'Usuário ou senha inválidos.'];
        }
    }

    public function verificarLogado()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: login.php');
            exit;
        }
    }

    /**
     * Verifica se o usuário logado é super_admin
     */
    public function verificarSuperAdmin()
    {
        $this->verificarLogado();
        if ($_SESSION['usuario_tipo'] !== 'super_admin') {
            header('Location: index.php');
            exit;
        }
    }

    /**
     * Verifica se o usuário logado tem acesso de empresa (não é super_admin sem empresa)
     */
    public function verificarAcessoEmpresa()
    {
        $this->verificarLogado();
        if (empty($_SESSION['empresa_id']) && $_SESSION['usuario_tipo'] === 'super_admin') {
            header('Location: admin/index.php');
            exit;
        }
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        header('Location: login.php');
        exit;
    }
}
