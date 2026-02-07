<?php
require_once __DIR__ . '/../../app/services/RelatorioService.php';

// Auth check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario_id'])) {
    die("Acesso negado.");
}

$service = new RelatorioService();
$rows = $service->getResumoMensalUltimos12Meses();

$totReceb = 0.0;
$totPago = 0.0;
foreach ($rows as $r) {
    $totReceb += (float) $r['total_recebido'];
    $totPago += (float) $r['total_pago'];
}
$totSaldo = $totReceb - $totPago;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Resumo Mensal - Receitas x Despesas (12 meses)</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 10px; }
        .footer { text-align: center; margin-top: 20px; font-size: 10px; color: #666; border-top: 1px solid #ccc; padding-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { background-color: #f0f0f0; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .btn-print { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; border-radius: 4px; font-size: 14px; }
        @media print { .no-print { display: none; } body { margin: 0; } }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" class="btn-print">Imprimir / Salvar PDF</button>
        <button onclick="window.close()" class="btn-print" style="background: #6c757d; margin-left: 10px;">Fechar</button>
    </div>

    <div class="header">
        <h2>RMG ERP - Sistema de Gestão</h2>
        <h3>Resumo Mensal — Receitas x Despesas (últimos 12 meses)</h3>
        <p>Gerado em: <?php echo date('d/m/Y H:i:s'); ?> por <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="25%">Mês</th>
                <th width="25%" class="text-right">Total Recebido (R$)</th>
                <th width="25%" class="text-right">Total Pago (R$)</th>
                <th width="25%" class="text-right">Saldo (R$)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="4" class="text-center">Nenhum registro encontrado.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $r):
                    $dt = DateTime::createFromFormat('Y-m', $r['mes']);
                    $label = $dt ? $dt->format('m/Y') : $r['mes'];
                ?>
                <tr>
                    <td><?php echo $label; ?></td>
                    <td class="text-right"><?php echo number_format($r['total_recebido'], 2, ',', '.'); ?></td>
                    <td class="text-right"><?php echo number_format($r['total_pago'], 2, ',', '.'); ?></td>
                    <td class="text-right"><?php echo number_format($r['saldo'], 2, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td class="text-right"><strong>TOTAL</strong></td>
                    <td class="text-right"><strong>R$ <?php echo number_format($totReceb, 2, ',', '.'); ?></strong></td>
                    <td class="text-right"><strong>R$ <?php echo number_format($totPago, 2, ',', '.'); ?></strong></td>
                    <td class="text-right"><strong>R$ <?php echo number_format($totSaldo, 2, ',', '.'); ?></strong></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <p>RMG ERP - Controle Financeiro e Patrimonial | Página 1 de 1</p>
    </div>

</body>
</html>