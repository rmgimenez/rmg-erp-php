<?php
require_once __DIR__ . '/../../app/controllers/LoginController.php';
require_once __DIR__ . '/../../app/controllers/BemController.php';
require_once __DIR__ . '/../../app/controllers/ManutencaoController.php';

$loginController = new LoginController();
$loginController->verificarLogado();
$loginController->verificarAcessoEmpresa();

$bemController = new BemController();
$manutencaoController = new ManutencaoController();

$id_bem = $_GET['id'] ?? null;

if (!$id_bem) {
    echo "ID do bem não informado.";
    exit;
}

$bem = $bemController->buscarPorId($id_bem);

if (!$bem) {
    echo "Bem não encontrado.";
    exit;
}

$manutencoes = $manutencaoController->listarPorBem($id_bem);

$totalManutencoes = 0;
foreach ($manutencoes as $m) {
    $totalManutencoes += $m->getCusto();
}

$currentDate = date('d/m/Y');
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório do Bem - <?php echo htmlspecialchars($bem->getDescricao()); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none;
            }

            .card {
                border: none;
                box-shadow: none;
            }
        }
    </style>
</head>

<body class="bg-light">

    <div class="container mt-4 mb-4">

        <div class="no-print mb-3">
            <a href="javascript:window.history.back()" class="btn btn-secondary">Voltar</a>
            <button onclick="window.print()" class="btn btn-primary">Imprimir</button>
        </div>

        <div class="card p-4">
            <div class="text-center mb-4">
                <h4 class="text-secondary"><?php echo htmlspecialchars($_SESSION['empresa_nome'] ?? 'RMG ERP - Sistema de Gestão'); ?></h4>
                <h2>Relatório Detalhado do Bem</h2>
                <h4 class="text-muted"><?php echo htmlspecialchars($bem->getDescricao()); ?></h4>
                <p class="text-muted">Gerado em: <?php echo $currentDate; ?></p>
            </div>

            <hr>

            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Informações Gerais</h5>
                    <table class="table table-borderless">
                        <tr>
                            <th width="40%">ID:</th>
                            <td><?php echo $bem->getIdBem(); ?></td>
                        </tr>
                        <tr>
                            <th>Setor:</th>
                            <td><?php echo htmlspecialchars($bem->getNomeSetor()); ?></td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <?php if ($bem->getStatus() == 'ativo'): ?>
                                    <span class="badge bg-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Baixado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Observações:</th>
                            <td><?php echo nl2br(htmlspecialchars($bem->getObservacoes() ?? '')); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5>Informações Financeiras</h5>
                    <table class="table table-borderless">
                        <tr>
                            <th width="40%">Data de Aquisição:</th>
                            <td><?php echo date('d/m/Y', strtotime($bem->getDataAquisicao())); ?></td>
                        </tr>
                        <tr>
                            <th>Valor de Aquisição:</th>
                            <td>R$ <?php echo number_format($bem->getValorAquisicao(), 2, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <th>Total em Manutenções:</th>
                            <td class="fw-bold text-danger">R$ <?php echo number_format($totalManutencoes, 2, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <th>Custo Total (Aquisição + Manutenções):</th>
                            <td class="fw-bold">R$ <?php echo number_format($bem->getValorAquisicao() + $totalManutencoes, 2, ',', '.'); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <hr>

            <div class="mb-4">
                <h5>Histórico de Manutenções</h5>
                <?php if (count($manutencoes) > 0): ?>
                    <table class="table table-striped table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Data</th>
                                <th>Descrição da Manutenção</th>
                                <th>Observações</th>
                                <th class="text-end">Custo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($manutencoes as $m): ?>
                                <tr>
                                    <td width="15%"><?php echo date('d/m/Y', strtotime($m->getDataManutencao())); ?></td>
                                    <td width="30%"><?php echo htmlspecialchars($m->getDescricao()); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($m->getObservacoes() ?? '')); ?></td>
                                    <td width="15%" class="text-end">R$ <?php echo number_format($m->getCusto(), 2, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Total Geral</th>
                                <th class="text-end">R$ <?php echo number_format($totalManutencoes, 2, ',', '.'); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                <?php else: ?>
                    <p class="text-muted text-center">Nenhuma manutenção registrada para este bem.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>

</body>

</html>