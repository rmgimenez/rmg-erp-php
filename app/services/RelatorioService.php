<?php
require_once __DIR__ . '/../dao/ContaPagarDAO.php';
require_once __DIR__ . '/../dao/ContaReceberDAO.php';
require_once __DIR__ . '/../dao/ManutencaoDAO.php';
require_once __DIR__ . '/../dao/PagamentoDAO.php';
require_once __DIR__ . '/../dao/RecebimentoDAO.php';

class RelatorioService {
    private $contaPagarDAO;
    private $contaReceberDAO;
    private $manutencaoDAO;
    private $pagamentoDAO;
    private $recebimentoDAO;

    public function __construct() {
        $this->contaPagarDAO = new ContaPagarDAO();
        $this->contaReceberDAO = new ContaReceberDAO();
        $this->manutencaoDAO = new ManutencaoDAO();
        $this->pagamentoDAO = new PagamentoDAO();
        $this->recebimentoDAO = new RecebimentoDAO();
    }

    public function getContasPagarPeriodo($inicio, $fim) {
        return $this->contaPagarDAO->buscarPorPeriodo($inicio, $fim);
    }

    public function getContasReceberPeriodo($inicio, $fim) {
        return $this->contaReceberDAO->buscarPorPeriodo($inicio, $fim);
    }

    public function getManutencoesPeriodo($inicio, $fim) {
        return $this->manutencaoDAO->buscarPorPeriodo($inicio, $fim);
    }

    public function getFluxoPrevisto($inicio, $fim) {
        $pagar = $this->contaPagarDAO->buscarPorPeriodo($inicio, $fim);
        $receber = $this->contaReceberDAO->buscarPorPeriodo($inicio, $fim);

        $totalPagar = 0;
        $totalReceber = 0;

        foreach ($pagar as $c) {
            $totalPagar += $c->getValor();
        }
        foreach ($receber as $c) {
            $totalReceber += $c->getValor();
        }

        return [
            'pagar' => $pagar,
            'receber' => $receber,
            'total_pagar' => $totalPagar,
            'total_receber' => $totalReceber,
            'saldo' => $totalReceber - $totalPagar
        ];
    }

    /**
     * Retorna gastos agregados por fornecedor em um período (usando pagamentos efetivados)
     * Cada registro: id_fornecedor, fornecedor, qtd_pagamentos, total_pago
     */
    public function getGastosPorFornecedorPeriodo($inicio, $fim) {
        return $this->pagamentoDAO->obterTotalPagoPorFornecedorPeriodo($inicio, $fim);
    }

    /**
     * Resumo mensal de Receitas x Despesas para os últimos 12 meses
     * Retorna array ordenado por mês (YYYY-MM) com keys: mes, total_recebido, total_pago, saldo
     */
    public function getResumoMensalUltimos12Meses() {
        $pagos = $this->pagamentoDAO->obterTotalPagoPorMesUltimos12Meses();
        $recebidos = $this->recebimentoDAO->obterTotalRecebidoPorMesUltimos12Meses();

        $map = [];
        foreach ($pagos as $p) {
            $map[$p['mes']] = [
                'mes' => $p['mes'],
                'total_pago' => (float) $p['total'],
                'total_recebido' => 0.0
            ];
        }
        foreach ($recebidos as $r) {
            if (!isset($map[$r['mes']])) {
                $map[$r['mes']] = [
                    'mes' => $r['mes'],
                    'total_pago' => 0.0,
                    'total_recebido' => (float) $r['total']
                ];
            } else {
                $map[$r['mes']]['total_recebido'] = (float) $r['total'];
            }
        }

        // Garantir meses ordenados e calcular saldo
        ksort($map);
        $out = [];
        foreach ($map as $m) {
            $m['saldo'] = $m['total_recebido'] - $m['total_pago'];
            $out[] = $m;
        }
        return $out;
    }
}
