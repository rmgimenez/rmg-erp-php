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

    <!-- Gráfico (Chart.js) -->
    <div style="max-width:920px; margin: 0 auto 18px;">
        <canvas id="resumoMensalChart" height="280" style="width:100%; max-height:380px; display:block;"></canvas>
        <p class="small text-muted" style="text-align:center; margin-top:6px;">Gráfico: Receitas (azul), Despesas (vermelho) e Saldo (verde) — últimos 12 meses.</p>
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

    <!-- Chart.js (CDN) + inicialização do gráfico -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        (function(){
            const raw = <?php echo json_encode(array_values($rows), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>;
            if (!raw || !raw.length) return;

            const labels = raw.map(r => {
                // r.mes esperado no formato YYYY-MM
                const m = (r.mes || '').toString();
                const parts = m.split('-');
                return parts.length === 2 ? (parts[1] + '/' + parts[0]) : m;
            });

            const receb = raw.map(r => Number(r.total_recebido || 0));
            const pago = raw.map(r => Number(r.total_pago || 0));
            const saldo = raw.map(r => Number(r.saldo || 0));

            const ctx = document.getElementById('resumoMensalChart').getContext('2d');
            const chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Recebido',
                            data: receb,
                            backgroundColor: 'rgba(54, 162, 235, 0.6)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            yAxisID: 'y'
                        },
                        {
                            label: 'Pago',
                            data: pago,
                            backgroundColor: 'rgba(255, 99, 132, 0.6)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            yAxisID: 'y'
                        },
                        {
                            type: 'line',
                            label: 'Saldo',
                            data: saldo,
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 2,
                            tension: 0.25,
                            pointRadius: 3,
                            yAxisID: 'y'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    stacked: false,
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: { mode: 'index', intersect: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { callback: value => value.toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2}) }
                        }
                    }
                }
            });

            // Garantir que o canvas imprima com boa resolução
            function fixPrint() {
                const canvas = document.getElementById('resumoMensalChart');
                if (!canvas) return;
                // for printing, increase size slightly
                canvas.style.maxHeight = '420px';
            }
            window.matchMedia('print').addEventListener('change', e => { if (e.matches) fixPrint(); });
        })();
    </script>

    <style>
        /* garantir que o canvas apareça corretamente ao imprimir */
        @media print {
            canvas { page-break-inside: avoid; max-width:100% !important; height: auto !important; }
        }
    </style>

</body>
</html>