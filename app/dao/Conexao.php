<?php

class Conexao {
    private static $instance = null;

    private function __construct() {
        // O construtor é privado para impedir que a classe seja instanciada via new
    }

    public static function getInstance() {
        if (self::$instance === null) {
            try {
                // Carrega as configurações se as constantes não estiverem definidas
                if (!defined('DB_HOST')) {
                    $configFile = __DIR__ . '/../config.php';
                    if (file_exists($configFile)) {
                        require_once $configFile;
                    } else {
                        throw new Exception("Arquivo de configuração não encontrado.");
                    }
                }

                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];

                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
                
            } catch (PDOException $e) {
                // Em produção, não exibir detalhes do erro
                throw new Exception("Erro na conexão com o banco de dados: " . $e->getMessage());
            }
        }

        return self::$instance;
    }
}
