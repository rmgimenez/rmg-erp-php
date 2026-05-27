<?php
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';

use CantinaFinanceiro\Database;
use CantinaFinanceiro\Auth;

header('Content-Type: application/json; charset=utf-8');

try {
    Auth::checkAuth();
    Auth::restrictTo(['admin']);
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'erro' => 'Acesso negado.']);
    exit;
}

$usuarioId = $_SESSION['user_id'];
$acao = $_GET['acao'] ?? '';

switch ($acao) {

    case 'buscar_custos_ia':
        $db = Database::getConnection();

        $stmtKey = $db->query("SELECT valor FROM configuracoes WHERE chave = 'openrouter_api_key' LIMIT 1");
        $apiKey = $stmtKey->fetchColumn();

        if (empty($apiKey)) {
            echo json_encode(['sucesso' => false, 'erro' => 'API key não configurada.']);
            exit;
        }

        $stmtPendentes = $db->query("SELECT id, generation_id FROM ai_usage_log WHERE cost_usd IS NULL AND generation_id != '' AND status = 'sucesso' LIMIT 50");
        $pendentes = $stmtPendentes->fetchAll();

        $atualizados = 0;
        foreach ($pendentes as $row) {
            $genId = $row['generation_id'];
            $ch = curl_init("https://openrouter.ai/api/v1/generation?id=" . urlencode($genId));
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer " . $apiKey,
                    "Content-Type: application/json"
                ],
                CURLOPT_TIMEOUT => 15,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            $resp = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $genData = json_decode($resp, true);
                $cost = null;
                if (isset($genData['data']['total_cost'])) {
                    $cost = (float)$genData['data']['total_cost'];
                } elseif (isset($genData['total_cost'])) {
                    $cost = (float)$genData['total_cost'];
                }
                if ($cost !== null) {
                    $stmtUp = $db->prepare("UPDATE ai_usage_log SET cost_usd = ? WHERE id = ?");
                    $stmtUp->execute([$cost, $row['id']]);
                    $atualizados++;
                }
            }
        }

        $stmtStats = $db->query("SELECT
            COUNT(*) as total_chamadas,
            SUM(CASE WHEN status = 'sucesso' THEN 1 ELSE 0 END) as chamadas_sucesso,
            SUM(CASE WHEN status = 'falha' THEN 1 ELSE 0 END) as chamadas_falha,
            COALESCE(SUM(cost_usd), 0) as custo_total,
            COALESCE(SUM(CASE WHEN criado_em >= datetime('now', '-30 days') THEN cost_usd ELSE 0 END), 0) as custo_30dias
            FROM ai_usage_log");
        $stats = $stmtStats->fetch();

        $stmtMensal = $db->query("SELECT
            strftime('%Y-%m', criado_em) as mes,
            COUNT(*) as chamadas,
            COALESCE(SUM(cost_usd), 0) as custo
            FROM ai_usage_log
            WHERE criado_em >= datetime('now', '-6 months')
            GROUP BY mes ORDER BY mes ASC");
        $mensal = $stmtMensal->fetchAll();

        $stmtModelos = $db->query("SELECT
            model,
            COUNT(*) as chamadas,
            COALESCE(SUM(cost_usd), 0) as custo
            FROM ai_usage_log
            GROUP BY model ORDER BY custo DESC");
        $modelos = $stmtModelos->fetchAll();

        echo json_encode([
            'sucesso' => true,
            'atualizados' => $atualizados,
            'stats' => $stats,
            'mensal' => $mensal,
            'modelos' => $modelos
        ]);
        exit;

    default:
        echo json_encode(['sucesso' => false, 'erro' => 'Ação inválida.']);
        exit;
}
