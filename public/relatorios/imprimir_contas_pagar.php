<?php
require_once __DIR__ . '/../../app/services/RelatorioService.php';
require_once __DIR__ . '/../../app/controllers/LoginController.php';

$loginController = new LoginController();
$loginController->verificarLogado();
$loginController->verificarAcessoEmpresa();

$empresaId = $_SESSION['empresa_id'];

$inicio = $_GET['inicio'] ?? date('Y-m-01');
$fim = $_GET['fim'] ?? date('Y-m-t');

$service = new RelatorioService();
$contas = $service->getContasPagarPeriodo($inicio, $fim, $empresaId);

$total = 0;
foreach ($contas as $c) {
    $total += $c->getValor();
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Relatório Contas a Pagar</title>
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
            background: #007bff;
            color: white;
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
        <button onclick="window.close()" class="btn-print" style="background: #6c757d; margin-left: 10px;">Fechar</button>
    </div>

    <div class="header">
        <h2><?php echo htmlspecialchars($_SESSION['empresa_nome'] ?? 'RMG ERP - Sistema de Gestão'); ?></h2>
        <h3>Relatório de Contas a Pagar</h3>
        <p>Período: <?php echo date('d/m/Y', strtotime($inicio)); ?> a <?php echo date('d/m/Y', strtotime($fim)); ?></p>
        <p>Gerado em: <?php echo date('d/m/Y H:i:s'); ?> por <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="10%">Vencimento</th>
                <th width="25%">Fornecedor</th>
                <th width="30%">Descrição</th>
                <th width="15%">Status</th>
                <th width="20%" class="text-right">Valor (R$)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($contas)): ?>
                <tr>
                    <td colspan="5" class="text-center">Nenhum registro encontrado neste período.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($contas as $c): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($c->getDataVencimento())); ?></td>
                        <td><?php echo htmlspecialchars($c->getNomeFornecedor() ?? 'Não inf.'); ?></td>
                        <td><?php echo htmlspecialchars($c->getDescricao()); ?></td>
                        <td class="text-center"><?php echo ucfirst($c->getStatus()); ?></td>
                        <td class="text-right"><?php echo number_format($c->getValor(), 2, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="4" class="text-right"><strong>TOTAL</strong></td>
                    <td class="text-right"><strong>R$ <?php echo number_format($total, 2, ',', '.'); ?></strong></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <p><?php echo htmlspecialchars(($_SESSION['empresa_nome'] ?? 'RMG ERP') . ' — Controle Financeiro e Patrimonial'); ?> | Página 1 de 1</p>
    </div>

</body>

</html>