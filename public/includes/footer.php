<footer class="bg-dark text-light text-center py-3 mt-auto">
    <div class="container">
        <?php
        // Carrega config se necessário e resolve valores com fallback seguro (COMPANY_NAME > FOOTER_TEXT > padrão).
        if (!defined('COMPANY_NAME') || !defined('FOOTER_TEXT')) {
            $configPath = __DIR__ . '/../../app/config.php';
            if (file_exists($configPath)) {
                include_once $configPath;
            }
        }

        $company = defined('COMPANY_NAME') ? COMPANY_NAME : null;
        $footer  = defined('FOOTER_TEXT') ? FOOTER_TEXT : 'Desenvolvido por Ricardo Moura Gimenez';

        // Saída: nome da empresa em destaque + texto do rodapé menor (escaping seguro)
        if ($company) {
            echo '<p class="mb-0"><strong>' . htmlspecialchars($company, ENT_QUOTES, 'UTF-8') . '</strong></p>';
            echo '<p class="mb-0 small text-muted"><i class="fas fa-code me-1" style="opacity:0.5;font-size:0.7rem;"></i> ' . htmlspecialchars($footer, ENT_QUOTES, 'UTF-8') . '</p>';
        } else {
            echo '<p class="mb-0"><i class="fas fa-code me-1" style="opacity:0.5;font-size:0.7rem;"></i> ' . htmlspecialchars($footer, ENT_QUOTES, 'UTF-8') . '</p>';
        }
        ?>
    </div>
</footer>

<!-- Modal Alertas (Global) -->
<?php include __DIR__ . '/modal_alertas.php'; ?>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Script Alertas (Global) -->
<script src="js/alertas.js"></script>