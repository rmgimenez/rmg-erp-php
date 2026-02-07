<?php
require_once __DIR__ . '/../models/Manutencao.php';
require_once __DIR__ . '/Conexao.php';

class ManutencaoDAO {
    private $conexao;

    public function __construct() {
        $this->conexao = Conexao::getInstance();
    }

    public function salvar(Manutencao $m) {
        try {
            $sql = "INSERT INTO rmg_manutencao (bem_id, data_manutencao, descricao, custo, observacoes) 
                    VALUES (:bem_id, :data_manutencao, :descricao, :custo, :observacoes)";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':bem_id', $m->getBemId());
            $stmt->bindValue(':data_manutencao', $m->getDataManutencao());
            $stmt->bindValue(':descricao', $m->getDescricao());
            $stmt->bindValue(':custo', $m->getCusto());
            $stmt->bindValue(':observacoes', $m->getObservacoes());
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function atualizar(Manutencao $m) {
        try {
            $sql = "UPDATE rmg_manutencao SET bem_id = :bem_id, data_manutencao = :data_manutencao, 
                    descricao = :descricao, custo = :custo, observacoes = :observacoes 
                    WHERE id_manutencao = :id_manutencao";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':bem_id', $m->getBemId());
            $stmt->bindValue(':data_manutencao', $m->getDataManutencao());
            $stmt->bindValue(':descricao', $m->getDescricao());
            $stmt->bindValue(':custo', $m->getCusto());
            $stmt->bindValue(':observacoes', $m->getObservacoes());
            $stmt->bindValue(':id_manutencao', $m->getIdManutencao());
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function excluir($id) {
        try {
            $sql = "DELETE FROM rmg_manutencao WHERE id_manutencao = :id_manutencao";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':id_manutencao', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function listarPorBem($bemId) {
        try {
            $sql = "SELECT m.*, b.descricao as descricao_bem
                    FROM rmg_manutencao m
                    JOIN rmg_bem b ON m.bem_id = b.id_bem
                    WHERE m.bem_id = :bem_id
                    ORDER BY m.data_manutencao DESC";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':bem_id', $bemId);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $lista = [];
            foreach ($result as $row) {
                $m = new Manutencao();
                $m->setIdManutencao($row['id_manutencao']);
                $m->setBemId($row['bem_id']);
                $m->setDescricaoBem($row['descricao_bem']);
                $m->setDataManutencao($row['data_manutencao']);
                $m->setDescricao($row['descricao']);
                $m->setCusto($row['custo']);
                $m->setObservacoes($row['observacoes']);
                $lista[] = $m;
            }
            return $lista;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function buscarPorPeriodo($inicio, $fim) {
        try {
            $sql = "SELECT m.*, b.descricao as descricao_bem
                    FROM rmg_manutencao m
                    JOIN rmg_bem b ON m.bem_id = b.id_bem
                    WHERE m.data_manutencao BETWEEN :inicio AND :fim
                    ORDER BY m.data_manutencao DESC";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':inicio', $inicio);
            $stmt->bindValue(':fim', $fim);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $lista = [];
            foreach ($result as $row) {
                $m = new Manutencao();
                $m->setIdManutencao($row['id_manutencao']);
                $m->setBemId($row['bem_id']);
                $m->setDescricaoBem($row['descricao_bem']);
                $m->setDataManutencao($row['data_manutencao']);
                $m->setDescricao($row['descricao']);
                $m->setCusto($row['custo']);
                $m->setObservacoes($row['observacoes']);
                $lista[] = $m;
            }
            return $lista;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function listarTodas() {
        try {
            $sql = "SELECT m.*, b.descricao as descricao_bem
                    FROM rmg_manutencao m
                    JOIN rmg_bem b ON m.bem_id = b.id_bem
                    ORDER BY m.data_manutencao DESC";
            $stmt = $this->conexao->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $lista = [];
            foreach ($result as $row) {
                $m = new Manutencao();
                $m->setIdManutencao($row['id_manutencao']);
                $m->setBemId($row['bem_id']);
                $m->setDescricaoBem($row['descricao_bem']);
                $m->setDataManutencao($row['data_manutencao']);
                $m->setDescricao($row['descricao']);
                $m->setCusto($row['custo']);
                $m->setObservacoes($row['observacoes']);
                $lista[] = $m;
            }
            return $lista;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function contarTotal() {
        try {
            $sql = "SELECT COUNT(*) as total FROM rmg_manutencao";
            $stmt = $this->conexao->prepare($sql);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['total'] ?? 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function obterCustoPorMesUltimos12Meses() {
        try {
            $sql = "SELECT DATE_FORMAT(data_manutencao, '%Y-%m') as mes, SUM(custo) as total 
                    FROM rmg_manutencao 
                    WHERE data_manutencao >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                    GROUP BY DATE_FORMAT(data_manutencao, '%Y-%m') 
                    ORDER BY mes ASC";
            $stmt = $this->conexao->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}