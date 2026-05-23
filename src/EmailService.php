<?php
namespace CantinaFinanceiro;

/**
 * Classe EmailService
 * Efetua o disparo de e-mails em formato HTML integrando-se nativamente com a API da MailGrid.
 */
class EmailService {
    /**
     * Envia um e-mail transacional via API oficial da MailGrid
     *
     * @param string $assunto Assunto do e-mail
     * @param string $corpoHtml Conteúdo da mensagem em formato HTML
     * @return bool True em caso de sucesso, False se houver falha
     */
    public static function enviarRelatorio(string $assunto, string $corpoHtml): bool {
        $db = Database::getConnection();

        // Busca todas as configurações do sistema
        $configs = [];
        $stmt = $db->query("SELECT chave, valor FROM configuracoes");
        while ($row = $stmt->fetch()) {
            $configs[$row['chave']] = $row['valor'];
        }

        $apiUrl = $configs['mailgrid_api_url'] ?? 'https://www.mailgrid.com.br/api';
        $apiKey = $configs['mailgrid_api_key'] ?? '';
        $remetenteEmail = $configs['email_remetente'] ?? '';
        $remetenteNome = $configs['nome_remetente'] ?? "Financeiro Cantina Sant'Anna";
        $destinatariosRaw = $configs['emails_destinatarios'] ?? '';

        // Valida se as credenciais mínimas foram cadastradas no sistema
        if (empty($apiKey) || empty($remetenteEmail) || empty($destinatariosRaw)) {
            error_log("Erro no Envio de E-mail: API Key da MailGrid, Remetente ou Destinatários não estão configurados.");
            return false;
        }

        // Trata destinatários múltiplos separados por vírgula
        $emailsDestino = array_map('trim', explode(',', $destinatariosRaw));
        $destinatariosFormatados = [];
        foreach ($emailsDestino as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $destinatariosFormatados[] = ['email' => $email];
            }
        }

        if (empty($destinatariosFormatados)) {
            error_log("Erro no Envio de E-mail: Nenhum destinatário válido configurado.");
            return false;
        }

        // Montagem do Payload para envio à API do MailGrid
        $payload = [
            'sender' => [
                'name' => $remetenteNome,
                'email' => $remetenteEmail
            ],
            'recipients' => $destinatariosFormatados,
            'subject' => $assunto,
            'html' => $corpoHtml
        ];

        $jsonPayload = json_encode($payload);

        // Inicializa cURL nativo
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            error_log("Erro cURL MailGrid: " . $err);
            return false;
        }

        // Verifica o código HTTP retornado pela MailGrid (2xx indica sucesso)
        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        } else {
            error_log("Falha de envio via API MailGrid. HTTP Code: {$httpCode}. Resposta do servidor: " . $response);
            return false;
        }
    }
}
