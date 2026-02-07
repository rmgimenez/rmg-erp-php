<?php
require_once __DIR__ . '/../models/Pagamento.php';
require_once __DIR__ . '/Conexao.php';

class PagamentoDAO {
    private $conexao;

    public function __construct() {
        $this->conexao = Conexao::getInstance();
    }

    public function salvar(Pagamento $p) {
        try {
            $this->conexao->beginTransaction();

            // 1. Registrar o Pagamento
            $sql = "INSERT INTO rmg_pagamento (conta_pagar_id, data_pagamento, valor_pago) 
                    VALUES (:conta_pagar_id, :data_pagamento, :valor_pago)";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':conta_pagar_id', $p->getContaPagarId());
            $stmt->bindValue(':data_pagamento', $p->getDataPagamento());
            $stmt->bindValue(':valor_pago', $p->getValorPago());
            $stmt->execute();

            // 2. Atualizar o Status da Conta a Pagar p/ 'paga'
            // Opcional: Poderia validar se o valor pago quita a dívida, mas vamos assumir quitação total por enquanto.
            $sqlUpdate = "UPDATE rmg_conta_pagar SET status = 'paga' WHERE id_conta_pagar = :id";
            $stmtUpdate = $this->conexao->prepare($sqlUpdate);
            $stmtUpdate->bindValue(':id', $p->getContaPagarId());
            $stmtUpdate->execute();

            $this->conexao->commit();
            return true;
        } catch (PDOException $e) {
            $this->conexao->rollBack();
            return false;
        }
    }
}
