<?php
require_once __DIR__ . '/../dao/UsuarioDAO.php';
require_once __DIR__ . '/../dao/LogDAO.php';
require_once __DIR__ . '/../models/Usuario.php';

class UsuarioController
{
    private $usuarioDAO;

    public function __construct()
    {
        $this->usuarioDAO = new UsuarioDAO();
    }

    /**
     * Lista usuários conforme o tipo de usuário logado:
     * - super_admin: lista todos ou por empresa específica
     * - gerente: lista apenas da própria empresa
     */
    public function listarUsuarios($empresaId = null)
    {
        if ($_SESSION['usuario_tipo'] === 'super_admin') {
            return $this->usuarioDAO->listar($empresaId);
        }
        // Gerente: lista apenas os usuários da sua empresa
        return $this->usuarioDAO->listarPorEmpresa($_SESSION['empresa_id']);
    }

    public function salvar($dados)
    {
        $tipoLogado = $_SESSION['usuario_tipo'];

        // Verificar permissões: apenas super_admin e gerente podem gerenciar usuários
        if (!in_array($tipoLogado, ['super_admin', 'gerente'])) {
            return ['sucesso' => false, 'mensagem' => 'Acesso negado. Você não tem permissão para gerenciar usuários.'];
        }

        $usuario = new Usuario();
        $usuario->setNome($dados['nome']);
        $usuario->setUsuario($dados['usuario']);
        $usuario->setTipoUsuario($dados['tipo_usuario']);
        $ativo = isset($dados['ativo']) ? 1 : 0;
        $usuario->setAtivo($ativo);

        // Definir empresa_id conforme regras:
        if ($tipoLogado === 'super_admin') {
            // Super admin pode criar gerentes e operadores para qualquer empresa
            $empresa_id = $dados['empresa_id'] ?? null;
            $usuario->setEmpresaId($empresa_id);

            // Super admin só pode criar gerentes e operadores (não outro super_admin)
            if ($dados['tipo_usuario'] === 'super_admin') {
                return ['sucesso' => false, 'mensagem' => 'Não é possível criar outro super administrador.'];
            }

            // Validar que empresa_id é obrigatório para gerente/operador
            if (empty($empresa_id)) {
                return ['sucesso' => false, 'mensagem' => 'É necessário selecionar uma empresa para este usuário.'];
            }
        } elseif ($tipoLogado === 'gerente') {
            // Gerente só pode criar operadores na sua própria empresa
            if ($dados['tipo_usuario'] !== 'operador') {
                return ['sucesso' => false, 'mensagem' => 'Gerentes só podem criar usuários do tipo operador.'];
            }
            $usuario->setEmpresaId($_SESSION['empresa_id']);
        }

        // Impedir alterações no usuário de login 'admin'
        if (!empty($dados['id_usuario'])) {
            // Edição
            $existente = $this->usuarioDAO->buscarPorId($dados['id_usuario']);
            if ($existente && $existente->getUsuario() === 'admin') {
                return ['sucesso' => false, 'mensagem' => 'O usuário "admin" não pode ser alterado.'];
            }

            // Gerente só pode editar usuários da própria empresa
            if ($tipoLogado === 'gerente') {
                if ($existente && $existente->getEmpresaId() != $_SESSION['empresa_id']) {
                    return ['sucesso' => false, 'mensagem' => 'Você não tem permissão para editar este usuário.'];
                }
                // Gerente não pode alterar tipo para algo diferente de operador
                if ($dados['tipo_usuario'] !== 'operador') {
                    return ['sucesso' => false, 'mensagem' => 'Gerentes só podem ter usuários do tipo operador.'];
                }
            }

            $usuario->setIdUsuario($dados['id_usuario']);

            if (!empty($dados['senha'])) {
                $usuario->setSenha(password_hash($dados['senha'], PASSWORD_DEFAULT));
            }

            if ($this->usuarioDAO->atualizar($usuario)) {
                LogDAO::registrar('rmg_usuario', 'UPDATE', 'Usuário atualizado: ' . $usuario->getNome() . ' (' . $usuario->getUsuario() . ')', $usuario->getIdUsuario());
                return ['sucesso' => true, 'mensagem' => 'Usuário atualizado com sucesso!'];
            }
        } else {
            // Novo cadastro
            if (empty($dados['senha'])) {
                return ['sucesso' => false, 'mensagem' => 'A senha é obrigatória para novos usuários.'];
            }

            $usuario->setSenha(password_hash($dados['senha'], PASSWORD_DEFAULT));

            if ($this->usuarioDAO->salvar($usuario)) {
                LogDAO::registrar('rmg_usuario', 'INSERT', 'Usuário cadastrado: ' . $usuario->getNome() . ' (' . $usuario->getUsuario() . ', tipo: ' . $usuario->getTipoUsuario() . ')');
                return ['sucesso' => true, 'mensagem' => 'Usuário cadastrado com sucesso!'];
            }
        }
        return ['sucesso' => false, 'mensagem' => 'Erro ao salvar usuário.'];
    }

    public function excluir($id)
    {
        $tipoLogado = $_SESSION['usuario_tipo'];

        if (!in_array($tipoLogado, ['super_admin', 'gerente'])) {
            return ['sucesso' => false, 'mensagem' => 'Acesso negado.'];
        }

        $usuarioAlvo = $this->usuarioDAO->buscarPorId($id);

        // Não permitir excluir o usuário 'admin'
        if ($usuarioAlvo && $usuarioAlvo->getUsuario() === 'admin') {
            return ['sucesso' => false, 'mensagem' => 'O usuário "admin" não pode ser excluído.'];
        }

        // Não permitir excluir o próprio usuário logado
        if ($id == $_SESSION['usuario_id']) {
            return ['sucesso' => false, 'mensagem' => 'Você não pode excluir seu próprio usuário.'];
        }

        // Gerente só pode excluir operadores da própria empresa
        if ($tipoLogado === 'gerente') {
            if ($usuarioAlvo && $usuarioAlvo->getEmpresaId() != $_SESSION['empresa_id']) {
                return ['sucesso' => false, 'mensagem' => 'Você não tem permissão para excluir este usuário.'];
            }
            if ($usuarioAlvo && $usuarioAlvo->getTipoUsuario() !== 'operador') {
                return ['sucesso' => false, 'mensagem' => 'Gerentes só podem excluir operadores.'];
            }
        }

        if ($this->usuarioDAO->excluir($id)) {
            LogDAO::registrar('rmg_usuario', 'DELETE', 'Usuário excluído: ' . ($usuarioAlvo ? $usuarioAlvo->getNome() : 'ID ' . $id), $id);
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
            LogDAO::registrar('rmg_usuario', 'UPDATE', 'Senha alterada pelo próprio usuário: ' . $usuario->getNome(), $usuario->getIdUsuario());
            return ['sucesso' => true, 'mensagem' => 'Senha alterada com sucesso!'];
        }

        return ['sucesso' => false, 'mensagem' => 'Erro ao alterar a senha.'];
    }
}
