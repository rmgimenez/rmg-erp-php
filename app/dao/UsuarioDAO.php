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

            if ($usuarioData) {
                $usuario = new Usuario();
                $usuario->setIdUsuario($usuarioData['id_usuario']);
                $usuario->setNome($usuarioData['nome']);
                $usuario->setUsuario($usuarioData['usuario']);
                $usuario->setSenha($usuarioData['senha']);
                $usuario->setTipoUsuario($usuarioData['tipo_usuario']);
                $usuario->setAtivo($usuarioData['ativo']);
                $usuario->setDataCriacao($usuarioData['data_criacao']);
                
                return $usuario;
            }

            return null;
        } catch (PDOException $e) {
            // Em um cenário real, logar o erro
            return null;
        }
    }
}
