<?php
require_once __DIR__ . '/../../app/dao/ContaPagarDAO.php';
require_once __DIR__ . '/../../app/dao/ContaReceberDAO.php';

header('Content-Type: application/json');

$inicio = $_GET['start'] ?? date('Y-m-d');
$fim = $_GET['end'] ?? date('Y-m-d');

// Truncate timestamps if they come in ISO format (FullCalendar sends 2023-10-01T00:00:00-03:00)
$inicio = substr($inicio, 0, 10);
$fim = substr($fim, 0, 10);

$pagarDAO = new ContaPagarDAO();
$receberDAO = new ContaReceberDAO();

$pagar = $pagarDAO->buscarPorPeriodo($inicio, $fim);
$receber = $receberDAO->buscarPorPeriodo($inicio, $fim);

$eventos = [];

foreach ($pagar as $c) {
    $color = ($c->getStatus() == 'paga') ? '#198754' : '#dc3545'; // Green if paid, Red if pending
    if ($c->getStatus() == 'paga') {
        $title = "✔ PAGO: " . $c->getDescricao();
    } else {
        $title = "A PAGAR: " . $c->getDescricao();
    }
    
    $eventos[] = [
        'title' => $title . ' (R$ ' . number_format($c->getValor(), 2, ',', '.') . ')',
        'start' => $c->getDataVencimento(),
        'color' => $color,
        'url' => 'contas_pagar.php', // Link to the list
        'extendedProps' => [
            'tipo' => 'pagar',
            'valor' => $c->getValor(),
            'status' => $c->getStatus(),
            'fornecedor' => $c->getNomeFornecedor()
        ]
    ];
}

foreach ($receber as $c) {
    $color = ($c->getStatus() == 'recebida') ? '#198754' : '#0d6efd'; // Green if received, Blue if pending
    if ($c->getStatus() == 'recebida') {
        $title = "✔ RECEBIDO: " . $c->getDescricao();
    } else {
        $title = "A RECEBER: " . $c->getDescricao();
    }

    $eventos[] = [
        'title' => $title . ' (R$ ' . number_format($c->getValor(), 2, ',', '.') . ')',
        'start' => $c->getDataVencimento(),
        'color' => $color,
        'url' => 'contas_receber.php',
        'extendedProps' => [
            'tipo' => 'receber',
            'valor' => $c->getValor(),
            'status' => $c->getStatus(),
            'cliente' => $c->getNomeCliente()
        ]
    ];
}

echo json_encode($eventos);