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

    /**
     * Retorna total pago por fornecedor dentro de um período
     * Resultado: array de arrays com keys: id_fornecedor, fornecedor, qtd_pagamentos, total_pago
     */
    public function obterTotalPagoPorFornecedorPeriodo($inicio, $fim) {
        try {
            $sql = "SELECT f.id_fornecedor, f.nome as fornecedor, COUNT(p.id_pagamento) as qtd_pagamentos, SUM(p.valor_pago) as total_pago
                    FROM rmg_pagamento p
                    JOIN rmg_conta_pagar cp ON cp.id_conta_pagar = p.conta_pagar_id
                    LEFT JOIN rmg_fornecedor f ON f.id_fornecedor = cp.fornecedor_id
                    WHERE p.data_pagamento BETWEEN :inicio AND :fim
                    GROUP BY f.id_fornecedor, f.nome
                    ORDER BY total_pago DESC";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':inicio', $inicio);
            $stmt->bindValue(':fim', $fim);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}

