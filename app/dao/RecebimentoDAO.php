<?php
require_once __DIR__ . '/../models/Recebimento.php';
require_once __DIR__ . '/Conexao.php';

class RecebimentoDAO {
    private $conexao;

    public function __construct() {
        $this->conexao = Conexao::getInstance();
    }

    public function salvar(Recebimento $r) {
        try {
            $this->conexao->beginTransaction();

            // 1. Registrar o Recebimento
            $sql = "INSERT INTO rmg_recebimento (conta_receber_id, data_recebimento, valor_recebido) 
                    VALUES (:conta_receber_id, :data_recebimento, :valor_recebido)";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':conta_receber_id', $r->getContaReceberId());
            $stmt->bindValue(':data_recebimento', $r->getDataRecebimento());
            $stmt->bindValue(':valor_recebido', $r->getValorRecebido());
            $stmt->execute();

            // 2. Atualizar o Status da Conta a Receber p/ 'recebida'
            $sqlUpdate = "UPDATE rmg_conta_receber SET status = 'recebida' WHERE id_conta_receber = :id";
            $stmtUpdate = $this->conexao->prepare($sqlUpdate);
            $stmtUpdate->bindValue(':id', $r->getContaReceberId());
            $stmtUpdate->execute();

            $this->conexao->commit();
            return true;
        } catch (PDOException $e) {
            $this->conexao->rollBack();
            return false;
        }
    }
}
