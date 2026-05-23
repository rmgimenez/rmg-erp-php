<?php
/**
 * Script de Execução Diária (Cron Job)
 * Dispara alertas de e-mail por MailGrid e executa a rotina automática de backup diário de segurança.
 */

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/EmailService.php';
require_once __DIR__ . '/src/BackupService.php';

use CantinaFinanceiro\Database;
use CantinaFinanceiro\EmailService;
use CantinaFinanceiro\BackupService;

try {
    $db = Database::getConnection();

    // 1. Executa a rotina de backup diário de segurança (tipo: automatico, criado pelo ID 1 - admin de TI)
    BackupService::criarBackup(1, 'automatico');

    // 2. Coleta configurações
    $configs = [];
    $stmtConfig = $db->query("SELECT chave, valor FROM configuracoes");
    while ($row = $stmtConfig->fetch()) {
        $configs[$row['chave']] = $row['valor'];
    }
    $diasAlerta = (int)($configs['dias_alerta_vencimento'] ?? 3);

    $hoje = date('Y-m-d');
    $futuro = date('Y-m-d', strtotime("+$diasAlerta days"));

    // 3. Consulta contas vencendo HOJE
    $stmtContasDia = $db->prepare("SELECT * FROM contas WHERE data_vencimento = ? AND status = 'pendente'");
    $stmtContasDia->execute([$hoje]);
    $contasDia = $stmtContasDia->fetchAll();

    // 4. Consulta contas VENCIDAS (atrasadas)
    $stmtVencidas = $db->prepare("SELECT * FROM contas WHERE data_vencimento < ? AND status = 'pendente' ORDER BY data_vencimento ASC");
    $stmtVencidas->execute([$hoje]);
    $contasVencidas = $stmtVencidas->fetchAll();

    // 5. Consulta contas A VENCER próximas (nos próximos N dias)
    $stmtProximas = $db->prepare("SELECT * FROM contas WHERE data_vencimento > ? AND data_vencimento <= ? AND status = 'pendente' ORDER BY data_vencimento ASC");
    $stmtProximas->execute([$hoje, $futuro]);
    $contasProximas = $stmtProximas->fetchAll();

    // 5b. Consulta manutenções de BENS agendadas próximas (nos próximos N dias)
    $stmtBensManutencoes = $db->prepare("
        SELECT m.*, b.nome AS bem_nome, b.codigo_patrimonio AS bem_codigo
        FROM manutencoes m
        JOIN bens b ON m.bem_id = b.id
        WHERE m.data_programada >= ? AND m.data_programada <= ? AND m.status = 'agendada'
        ORDER BY m.data_programada ASC
    ");
    $stmtBensManutencoes->execute([$hoje, $futuro]);
    $manutencoesProximas = $stmtBensManutencoes->fetchAll();

    // 6. Estruturação do e-mail estilizado em HTML (Design Moderno e Responsivo)
    $corpoHtml = "
    <html>
    <head>
        <meta charset='utf-8'>
        <style>
            body { font-family: 'Segoe UI', Helvetica, Arial, sans-serif; color: #2c3e50; line-height: 1.6; margin: 0; padding: 20px; background-color: #f4f6f9; }
            .container { max-width: 600px; margin: 0 auto; padding: 25px; background: #ffffff; border: 1px solid #e1e8ed; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
            .header { background: #3498db; color: #ffffff; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 25px; }
            .header h2 { margin: 0; font-size: 22px; font-weight: 600; }
            .header p { margin: 5px 0 0 0; font-size: 14px; opacity: 0.9; }
            .secao { margin-bottom: 30px; }
            .titulo-secao { font-weight: 700; font-size: 15px; text-transform: uppercase; letter-spacing: 0.8px; border-bottom: 2px solid #eaeded; padding-bottom: 6px; margin-bottom: 12px; }
            .vencida { color: #e74c3c; border-bottom-color: #fadbd8; }
            .hoje { color: #f39c12; border-bottom-color: #fdebd0; }
            .proxima { color: #1abc9c; border-bottom-color: #d1f2eb; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
            th, td { padding: 10px 12px; text-align: left; font-size: 13px; border-bottom: 1px solid #eaeded; }
            th { background-color: #f8f9fa; font-weight: 600; color: #7f8c8d; text-transform: uppercase; font-size: 11px; }
            .valor { font-weight: bold; text-align: right; }
            .tipo { font-size: 11px; padding: 2px 6px; border-radius: 4px; font-weight: bold; text-transform: uppercase; }
            .tipo-pagar { background-color: #fce4d6; color: #c55a11; }
            .tipo-receber { background-color: #e2efda; color: #375623; }
            .limpo { text-align: center; padding: 30px; background-color: #d4edda; color: #155724; border-radius: 8px; font-size: 14px; margin-top: 20px; border: 1px solid #c3e6cb; }
            .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #eaeded; font-size: 11px; text-align: center; color: #95a5a6; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Relatório Financeiro Diário</h2>
                <p>Cantina Sant'Anna — " . date('d/m/Y') . "</p>
            </div>
    ";

    $temPendencias = false;

    // Seção: Vencidas (Em Atraso)
    if (!empty($contasVencidas)) {
        $temPendencias = true;
        $corpoHtml .= "<div class='secao'>
            <div class='titulo-secao vencida'>⚠️ Contas em Atraso (Vencidas)</div>
            <table>
                <thead>
                    <tr><th>Descrição</th><th>Vencimento</th><th>Fluxo</th><th style='text-align: right;'>Valor</th></tr>
                </thead>
                <tbody>";
        foreach ($contasVencidas as $c) {
            $valorFormatado = "R$ " . number_format($c['valor'] / 100, 2, ',', '.');
            $dataVenc = date('d/m/Y', strtotime($c['data_vencimento']));
            $badgeTipo = $c['tipo'] === 'pagar' ? "<span class='tipo tipo-pagar'>Pagar</span>" : "<span class='tipo tipo-receber'>Receber</span>";
            $corpoHtml .= "<tr><td>{$c['descricao']}</td><td>{$dataVenc}</td><td>{$badgeTipo}</td><td class='valor'>{$valorFormatado}</td></tr>";
        }
        $corpoHtml .= "</tbody></table></div>";
    }

    // Seção: Hoje (Vencendo Hoje)
    if (!empty($contasDia)) {
        $temPendencias = true;
        $corpoHtml .= "<div class='secao'>
            <div class='titulo-secao hoje'>📅 Contas Vencendo Hoje</div>
            <table>
                <thead>
                    <tr><th>Descrição</th><th>Fluxo</th><th style='text-align: right;'>Valor</th></tr>
                </thead>
                <tbody>";
        foreach ($contasDia as $c) {
            $valorFormatado = "R$ " . number_format($c['valor'] / 100, 2, ',', '.');
            $badgeTipo = $c['tipo'] === 'pagar' ? "<span class='tipo tipo-pagar'>Pagar</span>" : "<span class='tipo tipo-receber'>Receber</span>";
            $corpoHtml .= "<tr><td>{$c['descricao']}</td><td>{$badgeTipo}</td><td class='valor'>{$valorFormatado}</td></tr>";
        }
        $corpoHtml .= "</tbody></table></div>";
    }

    // Seção: Próximas (A Vencer nos Próximos N Dias)
    if (!empty($contasProximas)) {
        $temPendencias = true;
        $corpoHtml .= "<div class='secao'>
            <div class='titulo-secao proxima'>🔔 Vencimentos Próximos (Próximos {$diasAlerta} dias)</div>
            <table>
                <thead>
                    <tr><th>Descrição</th><th>Vencimento</th><th>Fluxo</th><th style='text-align: right;'>Valor</th></tr>
                </thead>
                <tbody>";
        foreach ($contasProximas as $c) {
            $valorFormatado = "R$ " . number_format($c['valor'] / 100, 2, ',', '.');
            $dataVenc = date('d/m/Y', strtotime($c['data_vencimento']));
            $badgeTipo = $c['tipo'] === 'pagar' ? "<span class='tipo tipo-pagar'>Pagar</span>" : "<span class='tipo tipo-receber'>Receber</span>";
            $corpoHtml .= "<tr><td>{$c['descricao']}</td><td>{$dataVenc}</td><td>{$badgeTipo}</td><td class='valor'>{$valorFormatado}</td></tr>";
        }
        $corpoHtml .= "</tbody></table></div>";
    }

    // Seção: Manutenções de Bens Próximas
    if (!empty($manutencoesProximas)) {
        $temPendencias = true;
        $corpoHtml .= "<div class='secao'>
            <div class='titulo-secao' style='color: #8e44ad; border-bottom: 2px solid #ebdef0; font-weight: 700; font-size: 15px; text-transform: uppercase; letter-spacing: 0.8px; padding-bottom: 6px; margin-bottom: 12px;'>🔧 Alertas de Manutenção de Bens (Próximos {$diasAlerta} dias)</div>
            <table>
                <thead>
                    <tr><th>Ativo / Bem</th><th>Serviço Programado</th><th>Tipo</th><th style='text-align: right;'>Data Prog.</th></tr>
                </thead>
                <tbody>";
        foreach ($manutencoesProximas as $m) {
            $dataProg = date('d/m/Y', strtotime($m['data_programada']));
            $badgeTipo = $m['tipo'] === 'preventiva' ? "<span class='tipo' style='background-color: #e8f8f5; color: #117a65;'>Preventiva</span>" : "<span class='tipo' style='background-color: #fef9e7; color: #b7950b;'>Corretiva</span>";
            $bemIdent = $m['bem_nome'] . ($m['bem_codigo'] ? " (#{$m['bem_codigo']})" : "");
            $corpoHtml .= "<tr><td><strong>" . htmlspecialchars($bemIdent, ENT_QUOTES, 'UTF-8') . "</strong></td><td>" . htmlspecialchars($m['descricao'], ENT_QUOTES, 'UTF-8') . "</td><td>{$badgeTipo}</td><td style='text-align: right; font-weight: bold;'>{$dataProg}</td></tr>";
        }
        $corpoHtml .= "</tbody></table></div>";
    }

    // Tudo em dia
    if (!$temPendencias) {
        $corpoHtml .= "
        <div class='limpo'>
            🎉 <strong>Tudo em dia!</strong> Não há nenhuma conta ou manutenção pendente, vencendo hoje ou a vencer nos próximos {$diasAlerta} dias.
        </div>";
    }

    $corpoHtml .= "
            <div class='footer'>
                Mensagem enviada automaticamente pelo sistema <strong>Cantina Sant'Anna</strong>.<br>
                Rotina diária de backup automatizada executada com sucesso.<br>
                " . date('d/m/Y H:i:s') . "
            </div>
        </div>
    </body>
    </html>";

    // 7. Envio do E-mail
    $assunto = "Financeiro Cantina Sant'Anna: Alertas Diários de Vencimento - " . date('d/m/Y');
    $enviado = EmailService::enviarRelatorio($assunto, $corpoHtml);

    // Registra logs da auditoria
    if ($enviado) {
        echo "Sucesso: Backup automático gerado e e-mail de alerta disparado com sucesso via MailGrid.";
        \CantinaFinanceiro\Auth::logAction(1, 'CRON_DIARIO', 'Cron executado: e-mail disparado e backup gerado com sucesso.');
    } else {
        echo "Aviso: Backup automático gerado com sucesso, porém o envio do e-mail da MailGrid falhou.";
        \CantinaFinanceiro\Auth::logAction(1, 'CRON_DIARIO', 'Cron executado: backup gerado mas falha ao disparar e-mail MailGrid.');
    }
} catch (\Exception $e) {
    echo "Erro Crítico na Execução do Cron Diário: " . $e->getMessage();
}
