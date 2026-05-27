<?php
namespace CantinaFinanceiro;

use PDO;
use Exception;

class AnaliseModel {

    public static function getDadosConsolidados(): array {
        $db = Database::getConnection();

        $dados = [];

        $dados['kpis'] = AccountModel::getKPIs();

        $stmt = $db->query("
            SELECT strftime('%Y-%m', data_vencimento) as mes,
                   SUM(CASE WHEN tipo = 'receber' AND status = 'pago' THEN valor ELSE 0 END) as receitas,
                   SUM(CASE WHEN tipo = 'pagar' AND status = 'pago' THEN valor ELSE 0 END) as despesas
            FROM contas
            WHERE data_vencimento >= date('now', '-12 months')
            GROUP BY mes
            ORDER BY mes ASC
        ");
        $dados['mensal'] = $stmt->fetchAll();

        $stmt = $db->query("
            SELECT cat.nome as categoria, SUM(c.valor) as total
            FROM contas c
            LEFT JOIN categorias cat ON c.categoria_id = cat.id
            WHERE c.status = 'pago'
            GROUP BY cat.nome
            ORDER BY total DESC
            LIMIT 5
        ");
        $dados['top_categorias'] = $stmt->fetchAll();

        $stmt = $db->query("
            SELECT forn.nome as fornecedor, SUM(c.valor) as total
            FROM contas c
            LEFT JOIN fornecedores forn ON c.fornecedor_id = forn.id
            WHERE c.status = 'pago' AND forn.nome IS NOT NULL
            GROUP BY forn.nome
            ORDER BY total DESC
            LIMIT 5
        ");
        $dados['top_fornecedores'] = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(valor) as total_valor
                              FROM contas WHERE status = 'pendente' AND data_vencimento < date('now')");
        $stmt->execute();
        $dados['vencidas'] = $stmt->fetch();

        $totalContas = $db->query("SELECT COUNT(*) FROM contas")->fetchColumn();
        $dados['total_contas'] = (int)$totalContas;

        return $dados;
    }

    public static function gerarResumo(array $dados): string {
        return self::callOpenRouter('resumo_executivo', $dados);
    }

    public static function gerarTendencia(array $dados): string {
        return self::callOpenRouter('tendencia', $dados);
    }

    public static function perguntar(array $dados, string $pergunta, array $historico): string {
        return self::callOpenRouter('pergunta', $dados, $pergunta, $historico);
    }

    private static function callOpenRouter(string $acao, array $dados, string $pergunta = '', array $historico = []): string {
        $db = Database::getConnection();

        $stmt = $db->query("SELECT chave, valor FROM configuracoes WHERE chave IN ('openrouter_api_key', 'openrouter_model')");
        $configs = [];
        while ($row = $stmt->fetch()) {
            $configs[$row['chave']] = $row['valor'];
        }

        $apiKey = $configs['openrouter_api_key'] ?? '';
        $model = !empty($configs['openrouter_model']) ? $configs['openrouter_model'] : 'google/gemini-2.5-flash';

        if (empty($apiKey)) {
            throw new Exception("Chave de API do OpenRouter não configurada. Acesse o Painel Admin.");
        }

        $kpis = $dados['kpis'];
        $mensal = $dados['mensal'];
        $topCats = $dados['top_categorias'];
        $topForns = $dados['top_fornecedores'];
        $vencidas = $dados['vencidas'];
        $totalContas = $dados['total_contas'];

        $ctxMensal = '';
        foreach ($mensal as $m) {
            $ctxMensal .= "- {$m['mes']}: Receitas R$ " . number_format((int)$m['receitas'] / 100, 2, ',', '.') . ", Despesas R$ " . number_format((int)$m['despesas'] / 100, 2, ',', '.') . "\n";
        }

        $ctxCats = '';
        foreach ($topCats as $c) {
            $ctxCats .= "- " . ($c['categoria'] ?? 'Sem categoria') . ": R$ " . number_format((int)$c['total'] / 100, 2, ',', '.') . "\n";
        }

        $ctxForns = '';
        foreach ($topForns as $f) {
            $ctxForns .= "- {$f['fornecedor']}: R$ " . number_format((int)$f['total'] / 100, 2, ',', '.') . "\n";
        }

        $contextoFinanceiro = "
DADOS FINANCEIROS CONSOLIDADOS:
- Total de contas registradas: {$totalContas}
- Total recebido (histórico): R$ " . number_format((int)$kpis['total_recebido'] / 100, 2, ',', '.') . "
- Total pago (histórico): R$ " . number_format((int)$kpis['total_pago'] / 100, 2, ',', '.') . "
- Pendente a receber: R$ " . number_format((int)$kpis['pendente_receber'] / 100, 2, ',', '.') . "
- Pendente a pagar: R$ " . number_format((int)$kpis['pendente_pagar'] / 100, 2, ',', '.') . "
- Contas vencidas: {$vencidas['total']} (total R$ " . number_format((int)$vencidas['total_valor'] / 100, 2, ',', '.') . ")
- Saldo efetivo: R$ " . number_format((int)$kpis['saldo_efetivo'] / 100, 2, ',', '.') . "
- Saldo previsto: R$ " . number_format((int)$kpis['saldo_previsto'] / 100, 2, ',', '.') . "

EVOLUÇÃO MENSAL (últimos 12 meses):
{$ctxMensal}
TOP 5 CATEGORIAS:
{$ctxCats}
TOP 5 FORNECEDORES:
{$ctxForns}";

        switch ($acao) {
            case 'resumo_executivo':
                $systemPrompt = "Você é um analista financeiro sênior especializado em gestão de cantinas escolares. Com base nos dados financeiros fornecidos, gere um resumo executivo conciso em português brasileiro com 3 a 5 parágrafos. Destaque: desempenho geral, categorias que mais impactaram, situação de contas vencidas, e saldo. Seja objetivo e direto. Use linguagem profissional mas acessível.";
                $userPrompt = $contextoFinanceiro . "\n\nCom base nos dados acima, gere o resumo executivo financeiro.";
                break;

            case 'tendencia':
                $systemPrompt = "Você é um analista financeiro sênior especializado em projeções e tendências. Com base nos dados financeiros históricos fornecidos, gere uma análise em português brasileiro com 3 seções claramente separadas por ##. Seções obrigatórias: ## Projeção para os Próximos 3 Meses (estimativa de receitas e despesas), ## Alertas de Sazonalidade (padrões identificados nos dados históricos), ## Recomendações (ações sugeridas). Inclua o disclaimer no final: 'Análise gerada por IA com base em dados históricos. Não substitui aconselhamento profissional.'";
                $userPrompt = $contextoFinanceiro . "\n\nCom base nos dados acima, gere a análise de tendências e projeções.";
                break;

            case 'pergunta':
                $systemPrompt = "Você é um analista financeiro sênior especializado em gestão de cantinas. Responda à pergunta do usuário em português brasileiro com base exclusivamente nos dados financeiros fornecidos. Seja objetivo e direto. Se não for possível responder com os dados disponíveis, diga claramente: 'Não foi possível responder com os dados disponíveis. Tente reformular.'";
                $userPrompt = $contextoFinanceiro . "\n\nPergunta do usuário: {$pergunta}\n\nResponda à pergunta com base nos dados fornecidos.";
                break;

            default:
                throw new Exception("Ação inválida.");
        }

        $messages = [
            ["role" => "system", "content" => $systemPrompt]
        ];

        if ($acao === 'pergunta' && !empty($historico)) {
            foreach ($historico as $h) {
                $messages[] = ["role" => "user", "content" => $h['pergunta']];
                $messages[] = ["role" => "assistant", "content" => $h['resposta']];
            }
        }

        $messages[] = ["role" => "user", "content" => $userPrompt];

        $ch = curl_init("https://openrouter.ai/api/v1/chat/completions");

        $postData = [
            "model" => $model,
            "messages" => $messages
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $apiKey,
                "Content-Type: application/json",
                "HTTP-Referer: https://github.com/rmgimenez/rmg-erp-php",
                "X-Title: Cantina ERP"
            ],
            CURLOPT_TIMEOUT => 80,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new Exception("Falha na comunicação com a IA: " . $curlError);
        }

        if ($httpCode !== 200) {
            $errData = json_decode($response, true);
            $msg = $errData['error']['message'] ?? "Código HTTP {$httpCode}";
            throw new Exception("Erro do OpenRouter: " . $msg);
        }

        $resData = json_decode($response, true);
        $content = $resData['choices'][0]['message']['content'] ?? '';

        if (empty($content)) {
            throw new Exception("Retorno vazio da inteligência artificial.");
        }

        return $content;
    }
}
