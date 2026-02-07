<!-- Modal Alertas -->
<div class="modal fade" id="modalAlertas" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border-bottom: none;">
                <h5 class="modal-title text-white"><i class="fas fa-bell me-2"></i> Alertas Financeiros <span id="modal-total-vencidas" class="badge bg-danger ms-2 d-none" style="background: rgba(239,68,68,0.9) !important; color: #fff !important;" aria-live="polite"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="modal-alertas-intervalo" class="text-muted small mb-2">Mostrando contas vencidas ou com vencimento nos próximos <strong><span id="modal-alertas-dias">10</span> dias</strong>.</p>
                <div id="modal-alertas-resumo" class="modal-alerta-summary mb-2" aria-live="polite" role="status"></div>
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active text-danger" id="pagar-tab" data-bs-toggle="tab" data-bs-target="#pagar" type="button" role="tab">A Pagar (Vencidos/Próximos) <span id="modal-count-pagar" class="badge bg-danger ms-2 d-none">0</span></button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-primary" id="receber-tab" data-bs-toggle="tab" data-bs-target="#receber" type="button" role="tab">A Receber (Vencidos/Próximos) <span id="modal-count-receber" class="badge bg-primary ms-2 d-none">0</span></button>
                    </li>
                </ul>
                <div class="tab-content mt-3" id="myTabContent">
                    <div class="tab-pane fade show active" id="pagar" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped" id="tabelaAlertasPagar">
                                <thead>
                                    <tr>
                                        <th>Descrição</th>
                                        <th>Fornecedor</th>
                                        <th>Vencimento</th>
                                        <th>Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Preenchido via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="receber" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped" id="tabelaAlertasReceber">
                                <thead>
                                    <tr>
                                        <th>Descrição</th>
                                        <th>Cliente</th>
                                        <th>Vencimento</th>
                                        <th>Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Preenchido via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <a href="calendario.php" class="btn btn-primary">Ver Calendário Completo</a>
            </div>
        </div>
    </div>
</div>