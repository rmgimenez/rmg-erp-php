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

    public function obterTotalPagoPorMesUltimos12Meses() {
        try {
            $sql = "SELECT DATE_FORMAT(data_pagamento, '%Y-%m') as mes, SUM(valor_pago) as total 
                    FROM rmg_pagamento 
                    WHERE data_pagamento >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                    GROUP BY DATE_FORMAT(data_pagamento, '%Y-%m') 
                    ORDER BY mes ASC";
            $stmt = $this->conexao->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}
