<?php
require_once __DIR__ . '/../../app/controllers/LoginController.php';
require_once __DIR__ . '/../../app/dao/ContaPagarDAO.php';
require_once __DIR__ . '/../../app/dao/PagamentoDAO.php';

$loginController = new LoginController();
$loginController->verificarLogado();

header('Content-Type: application/json');

$id = $_GET['id'] ?? 0;

if (!$id) {
    echo json_encode(['erro' => 'ID não informado']);
    exit;
}

$contaDAO = new ContaPagarDAO();
$pagamentoDAO = new PagamentoDAO();

$conta = $contaDAO->buscarPorId($id);

if (!$conta) {
    echo json_encode(['erro' => 'Conta não encontrada']);
    exit;
}

$dados = [
    'id' => $conta->getIdContaPagar(),
    'descricao' => $conta->getDescricao(),
    'fornecedor' => $conta->getNomeFornecedor(),
    'valor' => number_format($conta->getValor(), 2, ',', '.'),
    'vencimento' => date('d/m/Y', strtotime($conta->getDataVencimento())),
    'status' => ucfirst($conta->getStatus()),
    'observacoes' => nl2br(htmlspecialchars($conta->getObservacoes() ?? ''))
];

if ($conta->getStatus() === 'paga') {
    $pagamento = $pagamentoDAO->buscarPorContaPagarId($id);
    if ($pagamento) {
        $dados['pagamento'] = [
            'data_pagamento' => date('d/m/Y', strtotime($pagamento->getDataPagamento())),
            'valor_pago' => number_format($pagamento->getValorPago(), 2, ',', '.')
        ];
    }
}

echo json_encode($dados);
