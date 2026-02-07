<?php
require_once __DIR__ . '/../app/controllers/LoginController.php';

$loginController = new LoginController();
$loginController->logout();
