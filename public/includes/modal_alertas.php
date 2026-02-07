<!-- Modal Alertas -->
<div class="modal fade" id="modalAlertas" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-dark"><i class="fas fa-exclamation-triangle me-2"></i> Alertas Financeiros</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="modal-alertas-intervalo" class="text-muted small mb-2">Mostrando contas vencidas ou com vencimento nos próximos <strong><span id="modal-alertas-dias">10</span> dias</strong>.</p>
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