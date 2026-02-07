<?php
require_once __DIR__ . '/../models/ContaReceber.php';
require_once __DIR__ . '/Conexao.php';

class ContaReceberDAO {
    private $conexao;

    public function __construct() {
        $this->conexao = Conexao::getInstance();
    }

    public function salvar(ContaReceber $conta) {
        try {
            $sql = "INSERT INTO rmg_conta_receber (cliente_id, descricao, valor, data_vencimento, status, observacoes) 
                    VALUES (:cliente_id, :descricao, :valor, :data_vencimento, :status, :observacoes)";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':cliente_id', $conta->getClienteId());
            $stmt->bindValue(':descricao', $conta->getDescricao());
            $stmt->bindValue(':valor', $conta->getValor());
            $stmt->bindValue(':data_vencimento', $conta->getDataVencimento());
            $stmt->bindValue(':status', $conta->getStatus());
            $stmt->bindValue(':observacoes', $conta->getObservacoes());
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function atualizar(ContaReceber $conta) {
        try {
            $sql = "UPDATE rmg_conta_receber SET cliente_id = :cliente_id, descricao = :descricao, 
                    valor = :valor, data_vencimento = :data_vencimento, status = :status, observacoes = :observacoes 
                    WHERE id_conta_receber = :id_conta_receber";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':cliente_id', $conta->getClienteId());
            $stmt->bindValue(':descricao', $conta->getDescricao());
            $stmt->bindValue(':valor', $conta->getValor());
            $stmt->bindValue(':data_vencimento', $conta->getDataVencimento());
            $stmt->bindValue(':status', $conta->getStatus());
            $stmt->bindValue(':observacoes', $conta->getObservacoes());
            $stmt->bindValue(':id_conta_receber', $conta->getIdContaReceber());
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function excluir($id) {
        try {
            $sql = "DELETE FROM rmg_conta_receber WHERE id_conta_receber = :id_conta_receber";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':id_conta_receber', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function listar() {
        try {
            $sql = "SELECT c.*, cli.nome as nome_cliente 
                    FROM rmg_conta_receber c
                    LEFT JOIN rmg_cliente cli ON c.cliente_id = cli.id_cliente
                    ORDER BY c.data_vencimento ASC";
            $stmt = $this->conexao->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $contas = [];
            foreach ($result as $row) {
                $c = new ContaReceber();
                $c->setIdContaReceber($row['id_conta_receber']);
                $c->setClienteId($row['cliente_id']);
                $c->setNomeCliente($row['nome_cliente']);
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
            $sql = "SELECT c.*, cli.nome as nome_cliente 
                    FROM rmg_conta_receber c
                    LEFT JOIN rmg_cliente cli ON c.cliente_id = cli.id_cliente
                    WHERE c.id_conta_receber = :id_conta_receber";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':id_conta_receber', $id);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $c = new ContaReceber();
                $c->setIdContaReceber($row['id_conta_receber']);
                $c->setClienteId($row['cliente_id']);
                $c->setNomeCliente($row['nome_cliente']);
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

    public function buscarPorPeriodo($inicio, $fim) {
        try {
            $sql = "SELECT c.*, cli.nome as nome_cliente 
                    FROM rmg_conta_receber c
                    LEFT JOIN rmg_cliente cli ON c.cliente_id = cli.id_cliente
                    WHERE c.data_vencimento BETWEEN :inicio AND :fim
                    ORDER BY c.data_vencimento ASC";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':inicio', $inicio);
            $stmt->bindValue(':fim', $fim);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $contas = [];
            foreach ($result as $row) {
                $c = new ContaReceber();
                $c->setIdContaReceber($row['id_conta_receber']);
                $c->setClienteId($row['cliente_id']);
                $c->setNomeCliente($row['nome_cliente']);
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

    public function obterTotais() {
        try {
            $sql = "SELECT status, SUM(valor) as total FROM rmg_conta_receber GROUP BY status";
            $stmt = $this->conexao->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stats = ['pendente' => 0, 'recebida' => 0];
            foreach ($result as $row) {
                $stats[$row['status']] = $row['total'];
            }
            return $stats;
        } catch (PDOException $e) {
            return ['pendente' => 0, 'recebida' => 0];
        }
    }
}
