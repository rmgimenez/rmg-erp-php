<?php

/**
 * AJAX Endpoint - Dados adicionais para o Dashboard
 * Retorna JSON com dados para tabelas e gráficos extras
 */
require_once __DIR__ . '/../../app/controllers/LoginController.php';
require_once __DIR__ . '/../../app/dao/ContaPagarDAO.php';
require_once __DIR__ . '/../../app/dao/ContaReceberDAO.php';
require_once __DIR__ . '/../../app/dao/PagamentoDAO.php';
require_once __DIR__ . '/../../app/dao/RecebimentoDAO.php';
require_once __DIR__ . '/../../app/dao/BemDAO.php';
require_once __DIR__ . '/../../app/dao/ManutencaoDAO.php';
require_once __DIR__ . '/../../app/dao/FornecedorDAO.php';
require_once __DIR__ . '/../../app/dao/SetorDAO.php';

header('Content-Type: application/json; charset=utf-8');

$loginController = new LoginController();
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$empresaId = $_SESSION['empresa_id'] ?? null;
if (!$empresaId) {
    echo json_encode(['error' => 'Empresa não identificada']);
    exit;
}

$tipo = $_GET['tipo'] ?? 'all';

$contaPagarDAO = new ContaPagarDAO();
$contaReceberDAO = new ContaReceberDAO();
$pagamentoDAO = new PagamentoDAO();
$recebimentoDAO = new RecebimentoDAO();
$bemDAO = new BemDAO();
$manutencaoDAO = new ManutencaoDAO();

$response = [];

// Próximas contas a pagar (vencidas + próximos 7 dias)
if ($tipo === 'all' || $tipo === 'proximas_pagar') {
    $response['proximas_pagar'] = $contaPagarDAO->buscarProximasVencer($empresaId, 7, 10);
}

// Próximas contas a receber
if ($tipo === 'all' || $tipo === 'proximas_receber') {
    $response['proximas_receber'] = $contaReceberDAO->buscarProximasVencer($empresaId, 7, 10);
}

// Top fornecedores
if ($tipo === 'all' || $tipo === 'top_fornecedores') {
    $response['top_fornecedores'] = $pagamentoDAO->obterTopFornecedores($empresaId, 5);
}

// Top bens manutenção
if ($tipo === 'all' || $tipo === 'top_bens') {
    $response['top_bens'] = $bemDAO->obterTopManutencao($empresaId, 5);
}

// Bens por setor
if ($tipo === 'all' || $tipo === 'bens_setor') {
    $response['bens_setor'] = $bemDAO->contarPorSetor($empresaId);
}

// Últimas manutenções
if ($tipo === 'all' || $tipo === 'ultimas_manutencoes') {
    $response['ultimas_manutencoes'] = $manutencaoDAO->buscarUltimas($empresaId, 5);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
