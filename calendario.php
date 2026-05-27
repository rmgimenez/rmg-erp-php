<?php
/**
 * Calendário de Contas
 * Permite visualizar todas as contas a pagar e receber do mês de forma intuitiva, com navegação mensal.
 */

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/AccountModel.php';

use CantinaFinanceiro\Database;
use CantinaFinanceiro\Auth;
use CantinaFinanceiro\AccountModel;

Auth::checkAuth();
Auth::restrictTo(['gerente', 'operador']);

$pageTitle = 'Calendário';
$activePage = 'calendario';

$db = Database::getConnection();

// ----------------------------------------------------
// Processamento de Datas e Navegação
// ----------------------------------------------------
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');
$mes = isset($_GET['mes']) ? str_pad((int)$_GET['mes'], 2, '0', STR_PAD_LEFT) : date('m');

// Garante limites aceitáveis para ano
if ($ano < 2000 || $ano > 2100) {
    $ano = (int)date('Y');
}
// Garante limites aceitáveis para mes
if ((int)$mes < 1 || (int)$mes > 12) {
    $mes = date('m');
}

$dataInicioMes = "$ano-$mes-01";
$diasNoMes = (int)date('t', strtotime($dataInicioMes));
$primeiroDiaSemana = (int)date('w', strtotime($dataInicioMes)); // 0 (Dom) a 6 (Sáb)

// Fórmulas de navegação (Anterior e Próximo)
$mesAnt = (int)$mes - 1;
$anoAnt = $ano;
if ($mesAnt === 0) {
    $mesAnt = 12;
    $anoAnt--;
}

$mesProx = (int)$mes + 1;
$anoProx = $ano;
if ($mesProx === 13) {
    $mesProx = 1;
    $anoProx++;
}

$mesesNomesComp = [
    '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril',
    '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto',
    '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
];

// ----------------------------------------------------
// Consulta Contas do Mês Selecionado
// ----------------------------------------------------
$dataFimMes = "$ano-$mes-$diasNoMes";
$stmt = $db->prepare("SELECT c.*, cat.nome as categoria_nome, forn.nome as fornecedor_nome 
                      FROM contas c 
                      LEFT JOIN categorias cat ON c.categoria_id = cat.id 
                      LEFT JOIN fornecedores forn ON c.fornecedor_id = forn.id 
                      WHERE c.data_vencimento BETWEEN ? AND ? 
                      ORDER BY c.status ASC, c.tipo ASC");
$stmt->execute([$dataInicioMes, $dataFimMes]);
$contasMes = $stmt->fetchAll();

// Mapeia contas por dia do mês para acesso O(1)
$contasPorDia = [];
foreach ($contasMes as $c) {
    $diaStr = date('d', strtotime($c['data_vencimento']));
    $diaInt = (int)$diaStr;
    $contasPorDia[$diaInt][] = $c;
}

// ----------------------------------------------------
// Estruturação do Grid de Células do Calendário
// ----------------------------------------------------
$celulas = [];

// Adiciona células vazias para dias do mês anterior
for ($i = 0; $i < $primeiroDiaSemana; $i++) {
    $celulas[] = [
        'eh_dia_mes' => false,
        'numero' => ''
    ];
}

// Adiciona os dias do mês atual
for ($dia = 1; $dia <= $diasNoMes; $dia++) {
    $celulas[] = [
        'eh_dia_mes' => true,
        'numero' => $dia,
        'data_completa' => "$ano-$mes-" . str_pad($dia, 2, '0', STR_PAD_LEFT)
    ];
}

// Completa a última semana com células vazias
while (count($celulas) % 7 !== 0) {
    $celulas[] = [
        'eh_dia_mes' => false,
        'numero' => ''
    ];
}
?>
<?php require_once __DIR__ . '/src/includes/layout_start.php'; ?>

            <!-- Header e Navegador de Meses -->
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-0">Calendário de Compromissos</h1>
                    <p class="text-muted small mb-0">Visão mensal consolidada dos vencimentos de receitas e despesas.</p>
                </div>
                
                <!-- Controles do Mês -->
                <div class="d-flex align-items-center gap-2 justify-content-center">
                    <a href="?ano=<?= $anoAnt ?>&mes=<?= $mesAnt ?>" class="btn btn-sm btn-outline-primary rounded-3">
                        <i class="fa-solid fa-chevron-left"></i> Anterior
                    </a>
                    
                    <div class="fw-bold px-3 py-1 bg-white border rounded-3 text-center" style="min-width: 160px; font-size: 0.95rem;">
                        <i class="fa-solid fa-calendar text-primary me-2"></i>
                        <?= $mesesNomesComp[$mes] ?> de <?= $ano ?>
                    </div>
                    
                    <a href="?ano=<?= $anoProx ?>&mes=<?= $mesProx ?>" class="btn btn-sm btn-outline-primary rounded-3">
                        Próximo <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </div>
            </div>

            <!-- Calendário Principal -->
            <div class="calendar-wrapper">
                <!-- Dias da Semana Header -->
                <div class="calendar-grid-header">
                    <div>Dom</div>
                    <div>Seg</div>
                    <div>Ter</div>
                    <div>Qua</div>
                    <div>Qui</div>
                    <div>Sex</div>
                    <div>Sáb</div>
                </div>
                
                <!-- Dias do Mês Grid -->
                <div class="calendar-grid-body">
                    <?php 
                    $hojeStr = date('Y-m-d');
                    foreach ($celulas as $celula): 
                        if ($celula['eh_dia_mes']):
                            $dia = $celula['numero'];
                            $dataCompleta = $celula['data_completa'];
                            $ehHoje = ($dataCompleta === $hojeStr);
                            
                            $classeHoje = $ehHoje ? 'calendar-day-cell-today' : '';
                            $contasDoDia = $contasPorDia[$dia] ?? [];
                    ?>
                            <div class="calendar-day-cell <?= $classeHoje ?>">
                                <div class="calendar-day-number"><?= $dia ?></div>
                                
                                <div class="calendar-events-list">
                                    <?php foreach ($contasDoDia as $c): 
                                        $valorF = "R$ " . number_format($c['valor'] / 100, 0, ',', '.');
                                        $classeTipo = $c['tipo'] === 'pagar' ? 'event-pagar' : 'event-receber';
                                        $classePago = $c['status'] === 'pago' ? 'event-pago' : '';
                                        
                                        $icon = $c['tipo'] === 'pagar' ? '<i class="fa-solid fa-circle-chevron-down event-icon"></i>' : '<i class="fa-solid fa-circle-chevron-up event-icon"></i>';
                                    ?>
                                        <a href="contas.php?busca=<?= urlencode($c['descricao']) ?>" 
                                           class="calendar-event-item <?= $classeTipo ?> <?= $classePago ?>"
                                           title="<?= htmlspecialchars($c['descricao'], ENT_QUOTES, 'UTF-8') ?> [<?= htmlspecialchars($c['categoria_nome'] ?? 'Não Informado', ENT_QUOTES, 'UTF-8') ?>] (<?= $c['status'] ?>) - R$ <?= number_format($c['valor'] / 100, 2, ',', '.') ?>">
                                            <span><?= $icon ?><?= htmlspecialchars($c['descricao'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="ms-1"><?= $valorF ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                    <?php 
                        else: 
                    ?>
                            <div class="calendar-day-cell calendar-day-cell-muted">
                                <div class="calendar-day-number"></div>
                            </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            </div>
            
            <!-- Legenda das Cores do Calendário -->
            <div class="d-flex flex-wrap gap-3 mt-3 justify-content-center small text-muted">
                <span class="d-flex align-items-center gap-1"><i class="fa-solid fa-circle text-success" style="font-size:0.75rem;"></i> Receitas Pendentes</span>
                <span class="d-flex align-items-center gap-1"><i class="fa-solid fa-circle text-danger" style="font-size:0.75rem;"></i> Despesas Pendentes</span>
                <span class="d-flex align-items-center gap-1" style="opacity:0.65;"><i class="fa-solid fa-circle text-muted" style="font-size:0.75rem; text-decoration: line-through;"></i> Contas Liquidadas/Pagas (Riscadas)</span>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/src/includes/layout_end.php'; ?>
