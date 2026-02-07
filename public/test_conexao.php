<?php
require_once '../app/dao/Conexao.php';

try {
    $conexao = Conexao::getInstance();
    echo "Conexão com o banco de dados bem-sucedida!";
} catch (Exception $e) {
    echo "Erro na conexão: " . $e->getMessage();
}
?>