<?php
/**
 * Início do layout compartilhado
 * Uso: require_once __DIR__ . '/includes/layout_start.php';
 * Variáveis esperadas: $pageTitle, $activePage
 */
$pageTitle = $pageTitle ?? 'Página';
$activePage = $activePage ?? '';
?>
<?php require_once __DIR__ . '/head.php'; ?>
<body>
<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/sidebar.php'; ?>

        <!-- Main Content Area -->
        <div class="col-md-9 col-lg-10 py-4 px-md-4">
            <?php require_once __DIR__ . '/mobile_header.php'; ?>
