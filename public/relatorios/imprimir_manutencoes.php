<?php
require_once __DIR__ . '/../../app/services/RelatorioService.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario_id'])) {
    die("Acesso negado.");
}

$inicio = $_GET['inicio'] ?? date('Y-m-01');
$fim = $_GET['fim'] ?? date('Y-m-t');

$service = new RelatorioService();
$registros = $service->getManutencoesPeriodo($inicio, $fim);

$total = 0;
foreach ($registros as $r) {
    $total += $r->getCusto();
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Relatório de Manutenções</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            margin: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
        }

        th {
            background-color: #f0f0f0;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .btn-print {
            padding: 10px 20px;
            background: #ffc107;
            color: black;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            font-size: 14px;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                margin: 0;
            }
        }
    </style>
</head>

<body>

    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" class="btn-print">Imprimir / Salvar PDF</button>
        <button onclick="window.close()" class="btn-print" style="background: #6c757d; color: white; margin-left: 10px;">Fechar</button>
    </div>

    <div class="header">
        <h2><?php echo defined('COMPANY_NAME') ? htmlspecialchars(COMPANY_NAME) : 'RMG ERP - Sistema de Gestão'; ?></h2>
        <h3>Relatório de Manutenções Realizadas</h3>
        <p>Período: <?php echo date('d/m/Y', strtotime($inicio)); ?> a <?php echo date('d/m/Y', strtotime($fim)); ?></p>
        <p>Gerado em: <?php echo date('d/m/Y H:i:s'); ?> por <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="12%">Data</th>
                <th width="20%">Bem/Equipamento</th>
                <th width="35%">Descrição do Serviço</th>
                <th width="18%">Observações</th>
                <th width="15%" class="text-right">Custo (R$)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($registros)): ?>
                <tr>
                    <td colspan="5" class="text-center">Nenhum registro encontrado neste período.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($registros as $r): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($r->getDataManutencao())); ?></td>
                        <td><?php echo htmlspecialchars($r->getDescricaoBem() ?? 'Não inf.'); ?></td>
                        <td><?php echo htmlspecialchars($r->getDescricao()); ?></td>
                        <td><?php echo htmlspecialchars(mb_strimwidth($r->getObservacoes() ?? '', 0, 50, "...")); ?></td>
                        <td class="text-right"><?php echo number_format($r->getCusto(), 2, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="4" class="text-right"><strong>CUSTO TOTAL</strong></td>
                    <td class="text-right"><strong>R$ <?php echo number_format($total, 2, ',', '.'); ?></strong></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <p><?php echo defined('COMPANY_NAME') ? htmlspecialchars(COMPANY_NAME) . ' — Controle Financeiro e Patrimonial' : 'RMG ERP - Controle Financeiro e Patrimonial'; ?> | Página 1 de 1</p>
    </div>

</body>

</html>