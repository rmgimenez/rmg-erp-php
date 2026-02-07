<?php
require_once __DIR__ . '/../../app/dao/ContaPagarDAO.php';
require_once __DIR__ . '/../../app/dao/ContaReceberDAO.php';

$pagarDAO = new ContaPagarDAO();
$receberDAO = new ContaReceberDAO();

// Busca contas vencidas e a vencer nos próximos 10 dias
$contasPagar = $pagarDAO->buscarVencidasEProximas(10);
$contasReceber = $receberDAO->buscarVencidasEProximas(10);

header('Content-Type: application/json');
echo json_encode([
    'pagar' => array_map(function($c) {
        return [
            'descricao' => $c->getDescricao(),
            'entidade' => $c->getNomeFornecedor(),
            'valor' => $c->getValor(),
            'vencimento' => $c->getDataVencimento()
        ];
    }, $contasPagar),
    'receber' => array_map(function($c) {
        return [
            'descricao' => $c->getDescricao(),
            'entidade' => $c->getNomeCliente(),
            'valor' => $c->getValor(),
            'vencimento' => $c->getDataVencimento()
        ];
    }, $contasReceber)
]);