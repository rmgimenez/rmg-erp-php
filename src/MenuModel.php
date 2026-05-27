<?php
namespace CantinaFinanceiro;

use PDO;
use Exception;

/**
 * Classe MenuModel
 * Gerencia as operações de persistência e comunicação com o OpenRouter para geração de cardápios semanais.
 */
class MenuModel {

    /**
     * Retorna a lista de todos os cardápios cadastrados, ordenados por data de início descendente
     */
    public static function getAll(): array {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM cardapios_semanais ORDER BY data_inicio DESC");
        return $stmt->fetchAll();
    }

    /**
     * Retorna os detalhes de um cardápio semanal e os pratos diários correspondentes
     */
    public static function getById(int $id): ?array {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("SELECT * FROM cardapios_semanais WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $semanal = $stmt->fetch();
        
        if (!$semanal) {
            return null;
        }

        $stmtDias = $db->prepare("SELECT * FROM cardapios_dias WHERE cardapio_semanal_id = ? ORDER BY 
            CASE dia_semana 
                WHEN 'segunda' THEN 1
                WHEN 'terca' THEN 2
                WHEN 'quarta' THEN 3
                WHEN 'quinta' THEN 4
                WHEN 'sexta' THEN 5
            END");
        $stmtDias->execute([$id]);
        $semanal['dias'] = $stmtDias->fetchAll();

        return $semanal;
    }

    /**
     * Atualiza um prato diário específico
     */
    public static function updateDay(int $cardapioSemanalId, string $dia, string $refeicaoPrincipal, string $lanche, int $usuarioId): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE cardapios_dias SET refeicao_principal = ?, lanche = ? WHERE cardapio_semanal_id = ? AND dia_semana = ?");
        $success = $stmt->execute([$refeicaoPrincipal, $lanche, $cardapioSemanalId, $dia]);
        if ($success) {
            Auth::logAction($usuarioId, 'CARDAPIO_EDITAR_DIA', "Dia '{$dia}' do cardápio #{$cardapioSemanalId} foi alterado manualmente.");
        }
        return $success;
    }

    /**
     * Atualiza a lista de compras de um cardápio
     */
    public static function updateShoppingList(int $id, string $listaCompras, int $usuarioId): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE cardapios_semanais SET lista_compras = ? WHERE id = ?");
        $success = $stmt->execute([$listaCompras, $id]);
        if ($success) {
            Auth::logAction($usuarioId, 'CARDAPIO_EDITAR_LISTA', "Lista de compras do cardápio #{$id} alterada manualmente.");
        }
        return $success;
    }

    /**
     * Remove um cardápio e todos os seus itens diários associados (ON DELETE CASCADE)
     */
    public static function delete(int $id, int $usuarioId): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM cardapios_semanais WHERE id = ?");
        $success = $stmt->execute([$id]);
        if ($success) {
            Auth::logAction($usuarioId, 'CARDAPIO_EXCLUIR', "Cardápio semanal #{$id} foi removido com sucesso.");
        }
        return $success;
    }

    /**
     * Solicita geração de cardápio via API do OpenRouter e persiste no SQLite
     */
    public static function generateWeeklyMenu(string $dataInicio, string $dataFim, ?string $observacoes, int $usuarioId): int {
        $db = Database::getConnection();

        // 1. Carrega as configurações da API do banco
        $configs = [];
        $stmtConfigs = $db->query("SELECT chave, valor FROM configuracoes WHERE chave IN (
            'openrouter_api_key', 'openrouter_model', 'cardapio_pessoas_estimadas', 'cardapio_contexto_global'
        )");
        while ($row = $stmtConfigs->fetch()) {
            $configs[$row['chave']] = $row['valor'];
        }

        $apiKey = $configs['openrouter_api_key'] ?? '';
        $model = !empty($configs['openrouter_model']) ? $configs['openrouter_model'] : 'google/gemini-2.5-flash';
        $pessoas = $configs['cardapio_pessoas_estimadas'] ?? '130 alunos, 30 funcionários';
        $contexto = $configs['cardapio_contexto_global'] ?? 'Cantina escolar.';

        if (empty($apiKey)) {
            throw new Exception("Configuração ausente: Por favor, configure a chave API do OpenRouter no Painel Admin.");
        }

        // 2. Constrói a mensagem / prompt
        $prompt = "Você é um nutricionista profissional planejando o cardápio semanal para a seguinte cantina:
Contexto Geral da Cantina: {$contexto}
Público Estimado (para o cálculo da lista de compras): {$pessoas}

Por favor, elabore um cardápio saudável, balanceado e atrativo para a semana de Segunda a Sexta:
- Data de início (Segunda-feira): " . date('d/m/Y', strtotime($dataInicio)) . "
- Data de fim (Sexta-feira): " . date('d/m/Y', strtotime($dataFim)) . "
" . (!empty($observacoes) ? "Observações e restrições específicas para esta semana: {$observacoes}\n" : "") . "
Para cada dia de Segunda a Sexta (segunda, terca, quarta, quinta, sexta), você deve definir:
1. refeicao_principal: Prato principal nutritivo e saboroso (ex: arroz, feijão, proteína, acompanhamento).
2. lanche: Um lanche ou sobremesa saudável (fruta, bolo caseiro integral, suco natural, pão, etc.).

Também elabore uma lista_compras detalhada em formato de Markdown, categorizada (ex: Hortifrúti, Secos, Carnes/Proteínas, Laticínios) contendo as quantidades estimadas de ingredientes necessárias para abastecer o público de '{$pessoas}' durante a semana.

ATENÇÃO: Você DEVE retornar estritamente um objeto JSON válido. Não inclua blocos de markdown adicionais como ```json ... ``` ou explicações antes ou depois. Responda apenas com o JSON estruturado desta forma:
{
  \"segunda\": {
    \"refeicao_principal\": \"...\",
    \"lanche\": \"...\"
  },
  \"terca\": {
    \"refeicao_principal\": \"...\",
    \"lanche\": \"...\"
  },
  \"quarta\": {
    \"refeicao_principal\": \"...\",
    \"lanche\": \"...\"
  },
  \"quinta\": {
    \"refeicao_principal\": \"...\",
    \"lanche\": \"...\"
  },
  \"sexta\": {
    \"refeicao_principal\": \"...\",
    \"lanche\": \"...\"
  },
  \"lista_compras\": \"Lista de compras em Markdown com quantidades...\"
}";

        // 3. Faz a requisição HTTP via cURL ao OpenRouter
        $ch = curl_init("https://openrouter.ai/api/v1/chat/completions");
        
        $postData = [
            "model" => $model,
            "messages" => [
                ["role" => "user", "content" => $prompt]
            ],
            "response_format" => ["type" => "json_object"]
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $apiKey,
                "Content-Type: application/json",
                "HTTP-Referer: https://github.com/rmgimenez/rmg-erp-php", // Opcional para OpenRouter
                "X-Title: Cantina ERP"
            ],
            CURLOPT_TIMEOUT => 40,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false // Evita falhas locais de certificado
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

        // 4. Decodifica o retorno do OpenRouter
        $resData = json_decode($response, true);
        $content = $resData['choices'][0]['message']['content'] ?? '';
        
        if (empty($content)) {
            throw new Exception("Retorno vazio da inteligência artificial.");
        }

        // Caso a IA coloque delimitadores de Markdown ```json
        $contentCleaned = trim($content);
        if (strpos($contentCleaned, '```json') === 0) {
            $contentCleaned = substr($contentCleaned, 7);
            if (substr($contentCleaned, -3) === '```') {
                $contentCleaned = substr($contentCleaned, 0, -3);
            }
            $contentCleaned = trim($contentCleaned);
        } elseif (strpos($contentCleaned, '```') === 0) {
            $contentCleaned = substr($contentCleaned, 3);
            if (substr($contentCleaned, -3) === '```') {
                $contentCleaned = substr($contentCleaned, 0, -3);
            }
            $contentCleaned = trim($contentCleaned);
        }

        $menuJson = json_decode($contentCleaned, true);

        if (!$menuJson || !isset($menuJson['segunda']) || !isset($menuJson['lista_compras'])) {
            throw new Exception("A IA retornou um formato inválido. Tente novamente.\nResposta bruta:\n" . substr($content, 0, 300));
        }

        // 5. Salva os dados no banco usando Transação
        $db->beginTransaction();
        try {
            // Insere cardápio semanal
            $stmtSemanal = $db->prepare("INSERT INTO cardapios_semanais (data_inicio, data_fim, observacoes_geracao, lista_compras) VALUES (?, ?, ?, ?)");
            $stmtSemanal->execute([
                $dataInicio,
                $dataFim,
                $observacoes,
                $menuJson['lista_compras']
            ]);
            $semanalId = (int)$db->lastInsertId();

            // Insere cada dia
            $diasSemana = ['segunda', 'terca', 'quarta', 'quinta', 'sexta'];
            $stmtDia = $db->prepare("INSERT INTO cardapios_dias (cardapio_semanal_id, dia_semana, refeicao_principal, lanche) VALUES (?, ?, ?, ?)");
            
            foreach ($diasSemana as $d) {
                if (isset($menuJson[$d])) {
                    $ref = $menuJson[$d]['refeicao_principal'] ?? 'Não informado';
                    $lan = $menuJson[$d]['lanche'] ?? 'Não informado';
                    $stmtDia->execute([$semanalId, $d, $ref, $lan]);
                }
            }

            $db->commit();
            
            Auth::logAction($usuarioId, 'CARDAPIO_GERAR', "Cardápio semanal #{$semanalId} gerado com sucesso via IA de " . date('d/m/Y', strtotime($dataInicio)) . " a " . date('d/m/Y', strtotime($dataFim)));
            
            return $semanalId;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
