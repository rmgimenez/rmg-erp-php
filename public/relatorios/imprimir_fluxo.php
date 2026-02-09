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
$fluxo = $service->getFluxoPrevisto($inicio, $fim, $empresaId);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Relatório de Fluxo de Caixa Previsto</title>
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
            background: #0dcaf0;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            font-size: 14px;
        }

        .resumo-box {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
        }

        .text-success {
            color: green;
        }

        .text-danger {
            color: red;
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
        <h3>Demonstrativo de Fluxo Previsto (Vencimentos)</h3>
        <p>Período: <?php echo date('d/m/Y', strtotime($inicio)); ?> a <?php echo date('d/m/Y', strtotime($fim)); ?></p>
        <p>Gerado em: <?php echo date('d/m/Y H:i:s'); ?> por <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></p>
    </div>

    <div class="resumo-box">
        <h3>Resumo do Período</h3>
        <p>Total de Entradas (Previstas): <strong class="text-success">R$ <?php echo number_format($fluxo['total_receber'], 2, ',', '.'); ?></strong></p>
        <p>Total de Saídas (Previstas): <strong class="text-danger">R$ <?php echo number_format($fluxo['total_pagar'], 2, ',', '.'); ?></strong></p>
        <hr>
        <p>Saldo Previsto: <strong>R$ <?php echo number_format($fluxo['saldo'], 2, ',', '.'); ?></strong></p>
    </div>

    <h4>Detalhe das Entradas (Contas a Receber)</h4>
    <table>
        <thead>
            <tr>
                <th width="15%">Vencimento</th>
                <th width="30%">Cliente</th>
                <th width="40%">Descrição</th>
                <th width="15%" class="text-right">Valor</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($fluxo['receber'] as $r): ?>
                <tr>
                    <td><?php echo date('d/m/Y', strtotime($r->getDataVencimento())); ?></td>
                    <td><?php echo htmlspecialchars($r->getNomeCliente() ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($r->getDescricao()); ?></td>
                    <td class="text-right">R$ <?php echo number_format($r->getValor(), 2, ',', '.'); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h4 style="margin-top: 20px;">Detalhe das Saídas (Contas a Pagar)</h4>
    <table>
        <thead>
            <tr>
                <th width="15%">Vencimento</th>
                <th width="30%">Fornecedor</th>
                <th width="40%">Descrição</th>
                <th width="15%" class="text-right">Valor</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($fluxo['pagar'] as $p): ?>
                <tr>
                    <td><?php echo date('d/m/Y', strtotime($p->getDataVencimento())); ?></td>
                    <td><?php echo htmlspecialchars($p->getNomeFornecedor() ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($p->getDescricao()); ?></td>
                    <td class="text-right">R$ <?php echo number_format($p->getValor(), 2, ',', '.'); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        <p><?php echo htmlspecialchars(($_SESSION['empresa_nome'] ?? 'RMG ERP') . ' — Controle Financeiro e Patrimonial'); ?> | Página 1 de 1</p>
    </div>

</body>

</html>