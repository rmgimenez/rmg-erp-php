<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['empresa_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['pagar' => [], 'receber' => [], 'count_pagar' => 0, 'count_receber' => 0]);
    exit;
}

require_once __DIR__ . '/../../app/dao/ContaPagarDAO.php';
require_once __DIR__ . '/../../app/dao/ContaReceberDAO.php';

$empresaId = $_SESSION['empresa_id'];
$pagarDAO = new ContaPagarDAO();
$receberDAO = new ContaReceberDAO();

// janela (dias) usada para buscar vencimentos — manter em variável para consistência
$dias = 10;
// Busca contas vencidas e a vencer nos próximos $dias dias
$contasPagar = $pagarDAO->buscarVencidasEProximas($dias, $empresaId);
$contasReceber = $receberDAO->buscarVencidasEProximas($dias, $empresaId);

// mapeia e marca itens vencidos (para destaque no front)
$pagar_arr = array_map(function ($c) {
    $vencimento = $c->getDataVencimento();
    $vencida = (strtotime($vencimento) < strtotime(date('Y-m-d')));
    $dias_atraso = $vencida ? (int) floor((strtotime(date('Y-m-d')) - strtotime($vencimento)) / 86400) : 0;
    return [
        'descricao' => $c->getDescricao(),
        'entidade' => $c->getNomeFornecedor(),
        'valor' => $c->getValor(),
        'vencimento' => $vencimento,
        'vencida' => $vencida,
        'dias_atraso' => $dias_atraso
    ];
}, $contasPagar);

$receber_arr = array_map(function ($c) {
    $vencimento = $c->getDataVencimento();
    $vencida = (strtotime($vencimento) < strtotime(date('Y-m-d')));
    $dias_atraso = $vencida ? (int) floor((strtotime(date('Y-m-d')) - strtotime($vencimento)) / 86400) : 0;
    return [
        'descricao' => $c->getDescricao(),
        'entidade' => $c->getNomeCliente(),
        'valor' => $c->getValor(),
        'vencimento' => $vencimento,
        'vencida' => $vencida,
        'dias_atraso' => $dias_atraso
    ];
}, $contasReceber);

// garantia extra: ordenar vencidas antes (defensivo)
usort($pagar_arr, function ($a, $b) {
    if ($a['vencida'] && !$b['vencida']) return -1;
    if (!$a['vencida'] && $b['vencida']) return 1;
    return strcmp($a['vencimento'], $b['vencimento']);
});

usort($receber_arr, function ($a, $b) {
    if ($a['vencida'] && !$b['vencida']) return -1;
    if (!$a['vencida'] && $b['vencida']) return 1;
    return strcmp($a['vencimento'], $b['vencimento']);
});

header('Content-Type: application/json');
echo json_encode([
    'dias' => $dias,
    'count_pagar' => count($contasPagar),
    'count_receber' => count($contasReceber),
    'count_pagar_vencidas' => count(array_filter($pagar_arr, function ($i) {
        return $i['vencida'];
    })),
    'count_receber_vencidas' => count(array_filter($receber_arr, function ($i) {
        return $i['vencida'];
    })),
    'pagar' => $pagar_arr,
    'receber' => $receber_arr
]);
