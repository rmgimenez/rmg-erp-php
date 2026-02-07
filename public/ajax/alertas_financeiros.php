<?php
require_once __DIR__ . '/../../app/dao/ContaPagarDAO.php';
require_once __DIR__ . '/../../app/dao/ContaReceberDAO.php';

$pagarDAO = new ContaPagarDAO();
$receberDAO = new ContaReceberDAO();

// janela (dias) usada para buscar vencimentos — manter em variável para consistência
$dias = 10;
// Busca contas vencidas e a vencer nos próximos $dias dias
$contasPagar = $pagarDAO->buscarVencidasEProximas($dias);
$contasReceber = $receberDAO->buscarVencidasEProximas($dias);

header('Content-Type: application/json');
echo json_encode([
    'dias' => $dias,
    'count_pagar' => count($contasPagar),
    'count_receber' => count($contasReceber),
    'pagar' => array_map(function ($c) {
        return [
            'descricao' => $c->getDescricao(),
            'entidade' => $c->getNomeFornecedor(),
            'valor' => $c->getValor(),
            'vencimento' => $c->getDataVencimento()
        ];
    }, $contasPagar),
    'receber' => array_map(function ($c) {
        return [
            'descricao' => $c->getDescricao(),
            'entidade' => $c->getNomeCliente(),
            'valor' => $c->getValor(),
            'vencimento' => $c->getDataVencimento()
        ];
    }, $contasReceber)
]);
