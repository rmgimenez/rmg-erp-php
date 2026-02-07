<?php

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/Conexao.php';

class UsuarioDAO {
    private $conexao;

    public function __construct() {
        $this->conexao = Conexao::getInstance();
    }

    public function buscarPorLogin($login) {
        try {
            $sql = "SELECT * FROM rmg_usuario WHERE usuario = :usuario";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':usuario', $login);
            $stmt->execute();

            $usuarioData = $stmt->fetch();

            return $this->hidratarUsuario($usuarioData);
        } catch (PDOException $e) {
            return null;
        }
    }

    public function salvar(Usuario $usuario) {
        try {
            $sql = "INSERT INTO rmg_usuario (nome, usuario, senha, tipo_usuario, ativo) VALUES (:nome, :usuario, :senha, :tipo_usuario, :ativo)";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':nome', $usuario->getNome());
            $stmt->bindValue(':usuario', $usuario->getUsuario());
            $stmt->bindValue(':senha', $usuario->getSenha());
            $stmt->bindValue(':tipo_usuario', $usuario->getTipoUsuario());
            $stmt->bindValue(':ativo', $usuario->getAtivo(), PDO::PARAM_BOOL);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function atualizar(Usuario $usuario) {
        try {
            $sql = "UPDATE rmg_usuario SET nome = :nome, usuario = :usuario, tipo_usuario = :tipo_usuario, ativo = :ativo WHERE id_usuario = :id_usuario";
            
            // Se a senha foi alterada (não está vazia), atualiza também
            if (!empty($usuario->getSenha())) {
                $sql = "UPDATE rmg_usuario SET nome = :nome, usuario = :usuario, senha = :senha, tipo_usuario = :tipo_usuario, ativo = :ativo WHERE id_usuario = :id_usuario";
            }
            
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':nome', $usuario->getNome());
            $stmt->bindValue(':usuario', $usuario->getUsuario());
            $stmt->bindValue(':tipo_usuario', $usuario->getTipoUsuario());
            $stmt->bindValue(':ativo', $usuario->getAtivo(), PDO::PARAM_BOOL);
            $stmt->bindValue(':id_usuario', $usuario->getIdUsuario());
            
            if (!empty($usuario->getSenha())) {
                $stmt->bindValue(':senha', $usuario->getSenha());
            }

            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function excluir($id) {
        try {
            $sql = "DELETE FROM rmg_usuario WHERE id_usuario = :id_usuario";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':id_usuario', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function listar() {
        try {
            $sql = "SELECT * FROM rmg_usuario ORDER BY nome";
            $stmt = $this->conexao->prepare($sql);
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

    public function buscarPorId($id) {
        try {
            $sql = "SELECT * FROM rmg_usuario WHERE id_usuario = :id_usuario";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':id_usuario', $id);
            $stmt->execute();
            
            return $this->hidratarUsuario($stmt->fetch(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            return null;
        }
    }

    private function hidratarUsuario($data) {
        if (!$data) return null;
        
        $usuario = new Usuario();
        $usuario->setIdUsuario($data['id_usuario']);
        $usuario->setNome($data['nome']);
        $usuario->setUsuario($data['usuario']);
        $usuario->setSenha($data['senha']);
        $usuario->setTipoUsuario($data['tipo_usuario']);
        $usuario->setAtivo($data['ativo']);
        $usuario->setDataCriacao($data['data_criacao']);
        
        return $usuario;
    }
}
