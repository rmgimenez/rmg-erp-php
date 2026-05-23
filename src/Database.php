<?php
namespace CantinaFinanceiro;

use PDO;
use PDOException;

/**
 * Classe Database
 * Gerencia a conexão com o banco de dados SQLite e realiza a criação automática de tabelas.
 */
class Database {
    private static ?PDO $instance = null;

    /**
     * Retorna a instância única do PDO (Singleton)
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                $dbPath = __DIR__ . '/../db/cantina.sqlite';
                
                // Garante que o diretório db existe
                if (!file_exists(dirname($dbPath))) {
                    mkdir(dirname($dbPath), 0755, true);
                }

                self::$instance = new PDO("sqlite:" . $dbPath);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                
                // Habilita suporte a chaves estrangeiras no SQLite
                self::$instance->exec("PRAGMA foreign_keys = ON;");
                
                // Inicializa o banco de dados
                self::initializeSchema();
            } catch (PDOException $e) {
                die("Erro crítico na conexão com o banco de dados: " . $e->getMessage());
            }
        }
        return self::$instance;
    }

    /**
     * Inicializa o esquema de tabelas e registros padrões
     */
    private static function initializeSchema(): void {
        $db = self::$instance;

        // Tabela de Usuários
        $db->exec("CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            usuario TEXT NOT NULL UNIQUE,
            email TEXT,
            senha TEXT NOT NULL,
            nivel TEXT CHECK(nivel IN ('admin', 'gerente', 'operador')) NOT NULL,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
        );");

        // Tabela de Contas (Pagar/Receber)
        $db->exec("CREATE TABLE IF NOT EXISTS contas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            descricao TEXT NOT NULL,
            valor INTEGER NOT NULL, -- em centavos
            tipo TEXT CHECK(tipo IN ('pagar', 'receber')) NOT NULL,
            status TEXT CHECK(status IN ('pendente', 'pago', 'cancelado')) DEFAULT 'pendente',
            data_vencimento DATE NOT NULL,
            data_liquidacao DATE,
            categoria TEXT NOT NULL,
            observacoes TEXT,
            criado_por INTEGER,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
        );");

        // Tabela de Configurações
        $db->exec("CREATE TABLE IF NOT EXISTS configuracoes (
            chave TEXT PRIMARY KEY,
            valor TEXT NOT NULL
        );");

        // Tabela de Backups
        $db->exec("CREATE TABLE IF NOT EXISTS backups (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome_arquivo TEXT NOT NULL,
            tamanho_bytes INTEGER NOT NULL,
            tipo TEXT CHECK(tipo IN ('automatico', 'manual')) NOT NULL,
            status TEXT CHECK(status IN ('sucesso', 'falha')) NOT NULL,
            criado_por INTEGER,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
        );");

        // Tabela de Logs (Auditoria)
        $db->exec("CREATE TABLE IF NOT EXISTS logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER,
            acao TEXT NOT NULL,
            detalhes TEXT,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
        );");

        // Insere o usuário Admin padrão se a tabela de usuários estiver vazia
        $stmt = $db->query("SELECT COUNT(*) FROM usuarios WHERE nivel = 'admin'");
        if ($stmt->fetchColumn() == 0) {
            $senhaPadrao = password_hash("admin123", PASSWORD_DEFAULT);
            $stmtInsert = $db->prepare("INSERT INTO usuarios (nome, usuario, email, senha, nivel) VALUES (?, ?, ?, ?, 'admin')");
            $stmtInsert->execute(['Administrador de TI', 'admin', 'ti@santanna.com.br', $senhaPadrao]);
        }

        // Insere um usuário Gerente padrão inicial para fins de testes e uso imediato do financeiro
        $stmtGerente = $db->query("SELECT COUNT(*) FROM usuarios WHERE nivel = 'gerente'");
        if ($stmtGerente->fetchColumn() == 0) {
            $senhaGerente = password_hash("gerente123", PASSWORD_DEFAULT);
            $stmtInsertG = $db->prepare("INSERT INTO usuarios (nome, usuario, email, senha, nivel) VALUES (?, ?, ?, ?, 'gerente')");
            $stmtInsertG->execute(["Gerente Financeiro Cantina Sant'Anna", 'gerente', 'financeiro@santanna.com.br', $senhaGerente]);
        }

        // Insere as configurações padrão se não existirem
        $defaultConfigs = [
            'mailgrid_api_url' => 'https://www.mailgrid.com.br/api',
            'mailgrid_api_key' => '',
            'email_remetente' => 'financeiro@santanna.com.br',
            'nome_remetente' => "Financeiro Cantina Sant'Anna",
            'emails_destinatarios' => 'direcao@santanna.com.br',
            'dias_alerta_vencimento' => '3'
        ];

        foreach ($defaultConfigs as $chave => $valor) {
            $stmtConfig = $db->prepare("INSERT OR IGNORE INTO configuracoes (chave, valor) VALUES (?, ?)");
            $stmtConfig->execute([$chave, $valor]);
        }
    }
}
