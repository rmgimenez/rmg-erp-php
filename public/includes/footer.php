<footer class="bg-dark text-light text-center py-3 mt-auto">
    <div class="container">
        <?php
        // Usa o texto definido em app/config.php (com fallback seguro).
        if (!defined('FOOTER_TEXT')) {
            $configPath = __DIR__ . '/../../app/config.php';
            if (file_exists($configPath)) {
                include_once $configPath;
            }
        }
        $footer = defined('FOOTER_TEXT') ? FOOTER_TEXT : 'Desenvolvido por Ricardo Moura Gimenez para Cantina Santanna';
        echo '<p class="mb-0">' . htmlspecialchars($footer, ENT_QUOTES, 'UTF-8') . '</p>';
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