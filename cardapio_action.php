<?php
/**
 * Controlador de Ações AJAX para o Módulo de Cardápios IA
 */

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/MenuModel.php';

use CantinaFinanceiro\Auth;
use CantinaFinanceiro\MenuModel;

header('Content-Type: application/json; charset=utf-8');
@set_time_limit(120); // Garante que a requisição AJAX não sofra timeout de PHP

// Garante que o usuário está logado
try {
    Auth::checkAuth();
    Auth::restrictTo(['gerente', 'operador', 'admin']);
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'erro' => 'Sessão expirada ou acesso negado.']);
    exit;
}

$usuarioId = $_SESSION['user_id'];
$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. GERAR CARDÁPIO VIA IA
    if ($acao === 'gerar') {
        $dataInicio = $_POST['data_inicio'] ?? '';
        $dataFim = $_POST['data_fim'] ?? '';
        $observacoes = trim($_POST['observacoes'] ?? '');
        $tipoRefeicao = trim($_POST['tipo_refeicao'] ?? 'ambos');

        if (empty($dataInicio) || empty($dataFim)) {
            echo json_encode(['sucesso' => false, 'erro' => 'Por favor, selecione as datas de início e fim.']);
            exit;
        }

        if (!in_array($tipoRefeicao, ['ambos', 'almoco', 'lanche'])) {
            $tipoRefeicao = 'ambos';
        }

        try {
            // Chama a geração síncrona do OpenRouter
            $idCardapio = MenuModel::generateWeeklyMenu($dataInicio, $dataFim, $observacoes, $usuarioId, $tipoRefeicao);
            echo json_encode(['sucesso' => true, 'id' => $idCardapio]);
        } catch (Exception $e) {
            echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
        }
        exit;
    }

    // 2. ATUALIZAR ITEM DIÁRIO
    elseif ($acao === 'editar_dia') {
        $cardapioSemanalId = (int)($_POST['id'] ?? 0);
        $dia = trim($_POST['dia'] ?? '');
        $refeicaoPrincipal = trim($_POST['refeicao_principal'] ?? '');
        $lanche = trim($_POST['lanche'] ?? '');

        if ($cardapioSemanalId <= 0 || empty($dia) || empty($refeicaoPrincipal) || empty($lanche)) {
            echo json_encode(['sucesso' => false, 'erro' => 'Dados incompletos para atualização.']);
            exit;
        }

        $diasValidos = ['segunda', 'terca', 'quarta', 'quinta', 'sexta'];
        if (!in_array($dia, $diasValidos)) {
            echo json_encode(['sucesso' => false, 'erro' => 'Dia da semana inválido.']);
            exit;
        }

        try {
            $sucesso = MenuModel::updateDay($cardapioSemanalId, $dia, $refeicaoPrincipal, $lanche, $usuarioId);
            echo json_encode(['sucesso' => $sucesso]);
        } catch (Exception $e) {
            echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
        }
        exit;
    }

    // 3. ATUALIZAR LISTA DE COMPRAS
    elseif ($acao === 'editar_lista') {
        $id = (int)($_POST['id'] ?? 0);
        $listaCompras = trim($_POST['lista_compras'] ?? '');

        if ($id <= 0 || empty($listaCompras)) {
            echo json_encode(['sucesso' => false, 'erro' => 'Dados incompletos para atualização.']);
            exit;
        }

        try {
            $sucesso = MenuModel::updateShoppingList($id, $listaCompras, $usuarioId);
            echo json_encode(['sucesso' => $sucesso]);
        } catch (Exception $e) {
            echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
        }
        exit;
    }

    // 4. ATUALIZAR CARDÁPIO EM MARKDOWN
    elseif ($acao === 'editar_cardapio_markdown') {
        $id = (int)($_POST['id'] ?? 0);
        $cardapioMarkdown = trim($_POST['cardapio_markdown'] ?? '');

        if ($id <= 0 || empty($cardapioMarkdown)) {
            echo json_encode(['sucesso' => false, 'erro' => 'Dados incompletos para atualização.']);
            exit;
        }

        try {
            $sucesso = MenuModel::updateCardapioMarkdown($id, $cardapioMarkdown, $usuarioId);
            echo json_encode(['sucesso' => $sucesso]);
        } catch (Exception $e) {
            echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
        }
        exit;
    }

    // 6. SALVAR OBSERVAÇÃO DE IMPRESSÃO
    elseif ($acao === 'salvar_observacao_impressao') {
        $id = (int)($_POST['id'] ?? 0);
        $observacao = trim($_POST['observacao'] ?? '');

        if ($id <= 0) {
            echo json_encode(['sucesso' => false, 'erro' => 'ID do cardápio inválido.']);
            exit;
        }

        try {
            $sucesso = MenuModel::updateObservacaoImpressao($id, $observacao, $usuarioId);
            echo json_encode(['sucesso' => $sucesso]);
        } catch (Exception $e) {
            echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
        }
        exit;
    }

    // 5. EXCLUIR CARDÁPIO
    elseif ($acao === 'excluir') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['sucesso' => false, 'erro' => 'ID do cardápio inválido.']);
            exit;
        }

        try {
            $sucesso = MenuModel::delete($id, $usuarioId);
            echo json_encode(['sucesso' => $sucesso]);
        } catch (Exception $e) {
            echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
        }
        exit;
    }
}

// Se nenhuma ação válida foi chamada
echo json_encode(['sucesso' => false, 'erro' => 'Requisição inválida.']);
exit;
