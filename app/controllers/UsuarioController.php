<?php
require_once __DIR__ . '/../dao/UsuarioDAO.php';
require_once __DIR__ . '/../models/Usuario.php';

class UsuarioController
{
    private $usuarioDAO;

    public function __construct()
    {
        $this->usuarioDAO = new UsuarioDAO();
    }

    public function listarUsuarios()
    {
        return $this->usuarioDAO->listar();
    }

    public function salvar($dados)
    {
        // Valida se o usuário que está tentando cadastrar é admin
        if ($_SESSION['usuario_tipo'] !== 'administrador') {
            return ['sucesso' => false, 'mensagem' => 'Acesso negado. Apenas administradores podem gerenciar usuários.'];
        }

        $usuario = new Usuario();
        $usuario->setNome($dados['nome']);
        $usuario->setUsuario($dados['usuario']);
        $usuario->setTipoUsuario($dados['tipo_usuario']);
        // Se a checkbox 'ativo' vier marcada, é 1, senão 0
        $ativo = isset($dados['ativo']) ? 1 : 0;
        $usuario->setAtivo($ativo);

        // Se estiver editando
        if (!empty($dados['id_usuario'])) {
            $usuario->setIdUsuario($dados['id_usuario']);

            // Se digitou senha, atualiza. Se vazio, o DAO ignora a senha na atualização
            if (!empty($dados['senha'])) {
                $usuario->setSenha(password_hash($dados['senha'], PASSWORD_DEFAULT));
            }

            if ($this->usuarioDAO->atualizar($usuario)) {
                return ['sucesso' => true, 'mensagem' => 'Usuário atualizado com sucesso!'];
            }
        } else {
            // Novo cadastro
            if (empty($dados['senha'])) {
                return ['sucesso' => false, 'mensagem' => 'A senha é obrigatória para novos usuários.'];
            }

            // Verificar se usuário já existe (opcional, mas bom)
            if ($this->usuarioDAO->buscarPorLogin($dados['usuario'])) {
                return ['sucesso' => false, 'mensagem' => 'Login de usuário já existe.'];
            }

            $usuario->setSenha(password_hash($dados['senha'], PASSWORD_DEFAULT));

            if ($this->usuarioDAO->salvar($usuario)) {
                return ['sucesso' => true, 'mensagem' => 'Usuário cadastrado com sucesso!'];
            }
        }
        return ['sucesso' => false, 'mensagem' => 'Erro ao salvar usuário.'];
    }

    public function excluir($id)
    {
        if ($_SESSION['usuario_tipo'] !== 'administrador') {
            return ['sucesso' => false, 'mensagem' => 'Acesso negado.'];
        }

        // Não permitir excluir o próprio usuário logado
        if ($id == $_SESSION['usuario_id']) {
            return ['sucesso' => false, 'mensagem' => 'Você não pode excluir seu próprio usuário.'];
        }

        if ($this->usuarioDAO->excluir($id)) {
            return ['sucesso' => true, 'mensagem' => 'Usuário excluído com sucesso!'];
        }
        return ['sucesso' => false, 'mensagem' => 'Erro ao excluir usuário.'];
    }

    public function alterarSenhaPropria($idUsuario, $senhaAtual, $novaSenha)
    {
        $usuario = $this->usuarioDAO->buscarPorId($idUsuario);

        if (!$usuario) {
            return ['sucesso' => false, 'mensagem' => 'Usuário não encontrado.'];
        }

        if (!password_verify($senhaAtual, $usuario->getSenha())) {
            return ['sucesso' => false, 'mensagem' => 'Senha atual incorreta.'];
        }

        $usuario->setSenha(password_hash($novaSenha, PASSWORD_DEFAULT));

        if ($this->usuarioDAO->atualizar($usuario)) {
            return ['sucesso' => true, 'mensagem' => 'Senha alterada com sucesso!'];
        }

        return ['sucesso' => false, 'mensagem' => 'Erro ao alterar a senha.'];
    }
}
