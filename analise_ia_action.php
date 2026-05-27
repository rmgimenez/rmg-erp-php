<?php
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/AccountModel.php';
require_once __DIR__ . '/src/AnaliseModel.php';

use CantinaFinanceiro\Auth;
use CantinaFinanceiro\AnaliseModel;

header('Content-Type: application/json; charset=utf-8');
@set_time_limit(120);

try {
    Auth::checkAuth();
    Auth::restrictTo(['gerente', 'admin']);
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'erro' => 'Sessão expirada ou acesso negado.']);
    exit;
}

$usuarioId = $_SESSION['user_id'];
$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'erro' => 'Método não permitido.']);
    exit;
}

try {
    switch ($acao) {
        case 'resumo_executivo':
            $dados = AnaliseModel::getDadosConsolidados();
            $resumo = AnaliseModel::gerarResumo($dados, $usuarioId);
            Auth::logAction($usuarioId, 'IA_RESUMO', 'Resumo executivo financeiro gerado via IA.');
            echo json_encode(['sucesso' => true, 'conteudo' => $resumo]);
            break;

        case 'tendencia':
            $dados = AnaliseModel::getDadosConsolidados();
            $tendencia = AnaliseModel::gerarTendencia($dados, $usuarioId);
            Auth::logAction($usuarioId, 'IA_TENDENCIA', 'Análise de tendências financeiras gerada via IA.');
            echo json_encode(['sucesso' => true, 'conteudo' => $tendencia]);
            break;

        case 'pergunta':
            $pergunta = trim($_POST['pergunta'] ?? '');
            if (empty($pergunta)) {
                echo json_encode(['sucesso' => false, 'erro' => 'Digite uma pergunta.']);
                exit;
            }
            $historico = $_SESSION['ia_chat_history'] ?? [];
            $dados = AnaliseModel::getDadosConsolidados();
            $resposta = AnaliseModel::perguntar($dados, $pergunta, $historico, $usuarioId);
            $historico[] = ['pergunta' => $pergunta, 'resposta' => $resposta];
            if (count($historico) > 10) {
                array_shift($historico);
            }
            $_SESSION['ia_chat_history'] = $historico;
            Auth::logAction($usuarioId, 'IA_PERGUNTA', "Pergunta financeira via IA: '" . substr($pergunta, 0, 100) . "'");
            echo json_encode(['sucesso' => true, 'conteudo' => $resposta, 'pergunta' => $pergunta]);
            break;

        default:
            echo json_encode(['sucesso' => false, 'erro' => 'Ação inválida.']);
    }
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}
