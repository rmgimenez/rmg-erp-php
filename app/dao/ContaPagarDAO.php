<?php
require_once __DIR__ . '/../models/ContaPagar.php';
require_once __DIR__ . '/Conexao.php';

class ContaPagarDAO {
    private $conexao;

    public function __construct() {
        $this->conexao = Conexao::getInstance();
    }

    public function salvar(ContaPagar $conta) {
        try {
            $sql = "INSERT INTO rmg_conta_pagar (fornecedor_id, descricao, valor, data_vencimento, status, observacoes) 
                    VALUES (:fornecedor_id, :descricao, :valor, :data_vencimento, :status, :observacoes)";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':fornecedor_id', $conta->getFornecedorId());
            $stmt->bindValue(':descricao', $conta->getDescricao());
            $stmt->bindValue(':valor', $conta->getValor());
            $stmt->bindValue(':data_vencimento', $conta->getDataVencimento());
            $stmt->bindValue(':status', $conta->getStatus());
            $stmt->bindValue(':observacoes', $conta->getObservacoes());
            return $stmt->execute();
        } catch (PDOException $e) {
            // echo $e->getMessage();
            return false;
        }
    }

    public function atualizar(ContaPagar $conta) {
        try {
            $sql = "UPDATE rmg_conta_pagar SET fornecedor_id = :fornecedor_id, descricao = :descricao, 
                    valor = :valor, data_vencimento = :data_vencimento, status = :status, observacoes = :observacoes 
                    WHERE id_conta_pagar = :id_conta_pagar";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':fornecedor_id', $conta->getFornecedorId());
            $stmt->bindValue(':descricao', $conta->getDescricao());
            $stmt->bindValue(':valor', $conta->getValor());
            $stmt->bindValue(':data_vencimento', $conta->getDataVencimento());
            $stmt->bindValue(':status', $conta->getStatus());
            $stmt->bindValue(':observacoes', $conta->getObservacoes());
            $stmt->bindValue(':id_conta_pagar', $conta->getIdContaPagar());
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function excluir($id) {
        try {
            $sql = "DELETE FROM rmg_conta_pagar WHERE id_conta_pagar = :id_conta_pagar";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':id_conta_pagar', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function listar() {
        try {
            $sql = "SELECT c.*, f.nome as nome_fornecedor 
                    FROM rmg_conta_pagar c
                    LEFT JOIN rmg_fornecedor f ON c.fornecedor_id = f.id_fornecedor
                    ORDER BY c.data_vencimento ASC";
            $stmt = $this->conexao->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $contas = [];
            foreach ($result as $row) {
                $c = new ContaPagar();
                $c->setIdContaPagar($row['id_conta_pagar']);
                $c->setFornecedorId($row['fornecedor_id']);
                $c->setNomeFornecedor($row['nome_fornecedor']);
                $c->setDescricao($row['descricao']);
                $c->setValor($row['valor']);
                $c->setDataVencimento($row['data_vencimento']);
                $c->setStatus($row['status']);
                $c->setObservacoes($row['observacoes']);
                $contas[] = $c;
            }
            return $contas;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function buscarPorId($id) {
        try {
            $sql = "SELECT c.*, f.nome as nome_fornecedor 
                    FROM rmg_conta_pagar c
                    LEFT JOIN rmg_fornecedor f ON c.fornecedor_id = f.id_fornecedor
                    WHERE c.id_conta_pagar = :id_conta_pagar";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':id_conta_pagar', $id);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $c = new ContaPagar();
                $c->setIdContaPagar($row['id_conta_pagar']);
                $c->setFornecedorId($row['fornecedor_id']);
                $c->setNomeFornecedor($row['nome_fornecedor']);
                $c->setDescricao($row['descricao']);
                $c->setValor($row['valor']);
                $c->setDataVencimento($row['data_vencimento']);
                $c->setStatus($row['status']);
                $c->setObservacoes($row['observacoes']);
                return $c;
            }
            return null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function obterTotais() {
        try {
            $sql = "SELECT status, SUM(valor) as total FROM rmg_conta_pagar GROUP BY status";
            $stmt = $this->conexao->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stats = ['pendente' => 0, 'paga' => 0];
            foreach ($result as $row) {
                $stats[$row['status']] = $row['total'];
            }
            return $stats;
        } catch (PDOException $e) {
            return ['pendente' => 0, 'paga' => 0];
        }
    }
}
