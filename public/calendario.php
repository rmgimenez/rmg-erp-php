<?php
require_once __DIR__ . '/../app/controllers/LoginController.php';
$loginController = new LoginController();
$loginController->verificarLogado();

$usuarioNome = $_SESSION['usuario_nome'] ?? 'Usuário';

$pageTitle = 'Calendário Financeiro - RMG ERP';
$extraCss = "
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    <style>
        .fc-event {
            cursor: pointer;
        }
    </style>
";
include __DIR__ . '/includes/header.php';
?>

    <div class="container mt-4">
        
        <div class="row mb-3">
            <div class="col-md-12">
                <h2><i class="fas fa-calendar-alt me-2"></i> Calendário Financeiro</h2>
                <p class="text-muted">Visualize os vencimentos de contas a pagar e receber.</p>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-12 text-center">
                        <span class="badge bg-danger me-2">● A Pagar (Pendente)</span>
                        <span class="badge bg-primary me-2">● A Receber (Pendente)</span>
                        <span class="badge bg-success">● Concluído (Pago/Recebido)</span>
                    </div>
                </div>
                <div id='calendar'></div>
            </div>
        </div>

    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'pt-br',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,listMonth'
                },
                buttonText: {
                    today: 'Hoje',
                    month: 'Mês',
                    list: 'Lista'
                },
                events: 'ajax/calendario_eventos.php',
                eventClick: function(info) {
                    // Optional: Open a modal with details? 
                    // For now, let the URL filtering work or prevent default if needed
                    // if (info.event.url) {
                    //    window.open(info.event.url);
                    //    info.jsEvent.preventDefault();
                    // }
                },
                eventContent: function(arg) {
                    let italicEl = document.createElement('div');
                    
                    if (arg.event.extendedProps.status === 'paga' || arg.event.extendedProps.status === 'recebida') {
                         italicEl.innerHTML = arg.event.title; 
                         // Optional: Add icons or strikethrough logic here if title doesn't cover it
                    } else {
                        italicEl.innerHTML = arg.event.title;
                    }
                    
                    let arrayOfDomNodes = [ italicEl ]
                    return { domNodes: arrayOfDomNodes }
                }
            });
            calendar.render();
        });
    </script>
</body>
</html>