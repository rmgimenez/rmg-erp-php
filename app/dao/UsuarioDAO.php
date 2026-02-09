<?php

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/Conexao.php';

class UsuarioDAO
{
    private $conexao;

    public function __construct()
    {
        $this->conexao = Conexao::getInstance();
    }

    /**
     * Busca usuário por login e empresa (multi-tenant)
     */
    public function buscarPorLoginEEmpresa($login, $empresaId)
    {
        try {
            $sql = "SELECT u.*, COALESCE(NULLIF(e.nome_fantasia, ''), NULLIF(e.razao_social, ''), e.codigo) AS nome_empresa
                    FROM rmg_usuario u
                    LEFT JOIN rmg_empresa e ON u.empresa_id = e.id_empresa
                    WHERE u.usuario = :usuario AND u.empresa_id = :empresa_id";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':usuario', $login);
            $stmt->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
            $stmt->execute();

            $usuarioData = $stmt->fetch(PDO::FETCH_ASSOC);

            return $this->hidratarUsuario($usuarioData);
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Busca super_admin por login (empresa_id IS NULL)
     */
    public function buscarSuperAdminPorLogin($login)
    {
        try {
            $sql = "SELECT u.*, NULL AS nome_empresa
                    FROM rmg_usuario u
                    WHERE u.usuario = :usuario AND u.empresa_id IS NULL AND u.tipo_usuario = 'super_admin'";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':usuario', $login);
            $stmt->execute();

            $usuarioData = $stmt->fetch(PDO::FETCH_ASSOC);

            return $this->hidratarUsuario($usuarioData);
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Salva novo usuário incluindo empresa_id
     */
    public function salvar(Usuario $usuario)
    {
        try {
            $sql = "INSERT INTO rmg_usuario (nome, usuario, senha, tipo_usuario, ativo, empresa_id)
                    VALUES (:nome, :usuario, :senha, :tipo_usuario, :ativo, :empresa_id)";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':nome', $usuario->getNome());
            $stmt->bindValue(':usuario', $usuario->getUsuario());
            $stmt->bindValue(':senha', $usuario->getSenha());
            $stmt->bindValue(':tipo_usuario', $usuario->getTipoUsuario());
            $stmt->bindValue(':ativo', $usuario->getAtivo(), PDO::PARAM_BOOL);

            $empresaId = $usuario->getEmpresaId();
            if ($empresaId === null) {
                $stmt->bindValue(':empresa_id', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
            }

            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Atualiza usuário existente incluindo empresa_id
     */
    public function atualizar(Usuario $usuario)
    {
        try {
            $sql = "UPDATE rmg_usuario SET nome = :nome, usuario = :usuario, tipo_usuario = :tipo_usuario, ativo = :ativo, empresa_id = :empresa_id WHERE id_usuario = :id_usuario";

            // Se a senha foi alterada (não está vazia), atualiza também
            if (!empty($usuario->getSenha())) {
                $sql = "UPDATE rmg_usuario SET nome = :nome, usuario = :usuario, senha = :senha, tipo_usuario = :tipo_usuario, ativo = :ativo, empresa_id = :empresa_id WHERE id_usuario = :id_usuario";
            }

            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':nome', $usuario->getNome());
            $stmt->bindValue(':usuario', $usuario->getUsuario());
            $stmt->bindValue(':tipo_usuario', $usuario->getTipoUsuario());
            $stmt->bindValue(':ativo', $usuario->getAtivo(), PDO::PARAM_BOOL);
            $stmt->bindValue(':id_usuario', $usuario->getIdUsuario(), PDO::PARAM_INT);

            $empresaId = $usuario->getEmpresaId();
            if ($empresaId === null) {
                $stmt->bindValue(':empresa_id', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
            }

            if (!empty($usuario->getSenha())) {
                $stmt->bindValue(':senha', $usuario->getSenha());
            }

            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Exclui usuário por ID
     */
    public function excluir($id)
    {
        try {
            $sql = "DELETE FROM rmg_usuario WHERE id_usuario = :id_usuario";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':id_usuario', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Lista usuários com LEFT JOIN em empresa.
     * Se $empresaId for null, lista TODOS os usuários (visão super_admin).
     * Se $empresaId for informado, lista apenas os usuários daquela empresa.
     */
    public function listar($empresaId = null)
    {
        try {
            $sql = "SELECT u.*, COALESCE(NULLIF(e.nome_fantasia, ''), NULLIF(e.razao_social, ''), e.codigo) AS nome_empresa
                    FROM rmg_usuario u
                    LEFT JOIN rmg_empresa e ON u.empresa_id = e.id_empresa";

            if ($empresaId !== null) {
                $sql .= " WHERE u.empresa_id = :empresa_id";
            }

            $sql .= " ORDER BY u.nome";

            $stmt = $this->conexao->prepare($sql);

            if ($empresaId !== null) {
                $stmt->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
            }

            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $usuarios = [];
            foreach ($result as $row) {
                $user = $this->hidratarUsuario($row);
                if ($user) $usuarios[] = $user;
            }
            return $usuarios;
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Lista usuários de uma empresa específica
     */
    public function listarPorEmpresa($empresaId)
    {
        try {
            $sql = "SELECT u.*, COALESCE(NULLIF(e.nome_fantasia, ''), NULLIF(e.razao_social, ''), e.codigo) AS nome_empresa
                    FROM rmg_usuario u
                    LEFT JOIN rmg_empresa e ON u.empresa_id = e.id_empresa
                    WHERE u.empresa_id = :empresa_id
                    ORDER BY u.nome";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $usuarios = [];
            foreach ($result as $row) {
                $user = $this->hidratarUsuario($row);
                if ($user) $usuarios[] = $user;
            }
            return $usuarios;
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Busca usuário por ID com LEFT JOIN para nome da empresa
     */
    public function buscarPorId($id)
    {
        try {
            $sql = "SELECT u.*, COALESCE(NULLIF(e.nome_fantasia, ''), NULLIF(e.razao_social, ''), e.codigo) AS nome_empresa
                    FROM rmg_usuario u
                    LEFT JOIN rmg_empresa e ON u.empresa_id = e.id_empresa
                    WHERE u.id_usuario = :id_usuario";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':id_usuario', $id, PDO::PARAM_INT);
            $stmt->execute();

            return $this->hidratarUsuario($stmt->fetch(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Conta usuários, opcionalmente filtrados por empresa
     */
    public function contarUsuarios($empresaId = null)
    {
        try {
            $sql = "SELECT COUNT(*) AS total FROM rmg_usuario";

            if ($empresaId !== null) {
                $sql .= " WHERE empresa_id = :empresa_id";
            }

            $stmt = $this->conexao->prepare($sql);

            if ($empresaId !== null) {
                $stmt->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
            }

            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int) $result['total'];
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Hidrata objeto Usuario a partir dos dados do banco
     */
    private function hidratarUsuario($data)
    {
        if (!$data) return null;

        $usuario = new Usuario();
        $usuario->setIdUsuario($data['id_usuario']);
        $usuario->setNome($data['nome']);
        $usuario->setUsuario($data['usuario']);
        $usuario->setSenha($data['senha']);
        $usuario->setTipoUsuario($data['tipo_usuario']);
        $usuario->setAtivo($data['ativo']);
        $usuario->setDataCriacao($data['data_criacao']);
        $usuario->setEmpresaId($data['empresa_id'] ?? null);
        $usuario->setNomeEmpresa($data['nome_empresa'] ?? null);

        return $usuario;
    }
}
