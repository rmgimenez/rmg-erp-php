<?php
require_once __DIR__ . '/../dao/ContaPagarDAO.php';
require_once __DIR__ . '/../dao/ContaReceberDAO.php';
require_once __DIR__ . '/../dao/ManutencaoDAO.php';

class RelatorioService {
    private $contaPagarDAO;
    private $contaReceberDAO;
    private $manutencaoDAO;

    public function __construct() {
        $this->contaPagarDAO = new ContaPagarDAO();
        $this->contaReceberDAO = new ContaReceberDAO();
        $this->manutencaoDAO = new ManutencaoDAO();
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
}