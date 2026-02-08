<?php

require_once __DIR__ . '/../models/Setor.php';
require_once __DIR__ . '/Conexao.php';

class SetorDAO
{
    private $conexao;

    public function __construct()
    {
        $this->conexao = Conexao::getInstance();
    }

    public function salvar(Setor $setor)
    {
        try {
            $sql = "INSERT INTO rmg_setor (nome, descricao) VALUES (:nome, :descricao)";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':nome', $setor->getNome());
            $stmt->bindValue(':descricao', $setor->getDescricao());
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function atualizar(Setor $setor)
    {
        try {
            $sql = "UPDATE rmg_setor SET nome = :nome, descricao = :descricao WHERE id_setor = :id_setor";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':nome', $setor->getNome());
            $stmt->bindValue(':descricao', $setor->getDescricao());
            $stmt->bindValue(':id_setor', $setor->getIdSetor());
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function excluir($id)
    {
        try {
            $sql = "DELETE FROM rmg_setor WHERE id_setor = :id_setor";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':id_setor', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function listar()
    {
        try {
            $sql = "SELECT * FROM rmg_setor ORDER BY nome";
            $stmt = $this->conexao->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $setores = [];
            foreach ($result as $row) {
                $setor = new Setor();
                $setor->setIdSetor($row['id_setor']);
                $setor->setNome($row['nome']);
                $setor->setDescricao($row['descricao']);
                $setores[] = $setor;
            }
            return $setores;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function buscarPorId($id)
    {
        try {
            $sql = "SELECT * FROM rmg_setor WHERE id_setor = :id_setor";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':id_setor', $id);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $setor = new Setor();
                $setor->setIdSetor($row['id_setor']);
                $setor->setNome($row['nome']);
                $setor->setDescricao($row['descricao']);
                return $setor;
            }
            return null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function temVinculos($id)
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM rmg_bem WHERE setor_id = :setor_id";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':setor_id', $id);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['total'] > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}
