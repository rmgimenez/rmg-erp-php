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
            nivel TEXT CHECK(nivel IN ('admin', 'gerente', 'operador', 'nutricionista')) NOT NULL,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
        );");

        // Migração: adiciona 'nutricionista' ao CHECK constraint em bancos existentes
        $stmtSchema = $db->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='usuarios'");
        $sqlAtual = $stmtSchema->fetchColumn();
        if ($sqlAtual && strpos($sqlAtual, 'nutricionista') === false) {
            $db->beginTransaction();
            try {
                $db->exec("CREATE TABLE usuarios_novo (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    nome TEXT NOT NULL,
                    usuario TEXT NOT NULL UNIQUE,
                    email TEXT,
                    senha TEXT NOT NULL,
                    nivel TEXT CHECK(nivel IN ('admin', 'gerente', 'operador', 'nutricionista')) NOT NULL,
                    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
                )");
                $db->exec("INSERT INTO usuarios_novo (id, nome, usuario, email, senha, nivel, criado_em) SELECT id, nome, usuario, email, senha, nivel, criado_em FROM usuarios");
                $db->exec("DROP TABLE usuarios");
                $db->exec("ALTER TABLE usuarios_novo RENAME TO usuarios");
                $db->commit();
            } catch (\Exception $e2) {
                $db->rollBack();
                @$db->exec("DROP TABLE IF EXISTS usuarios_novo");
            }
        }

        // Tabela de Categorias
        $db->exec("CREATE TABLE IF NOT EXISTS categorias (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL UNIQUE,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
        );");

        // Tabela de Fornecedores
        $db->exec("CREATE TABLE IF NOT EXISTS fornecedores (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL UNIQUE,
            contato TEXT,
            observacoes TEXT,
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
            categoria_id INTEGER,
            fornecedor_id INTEGER,
            observacoes TEXT,
            criado_por INTEGER,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
            FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id) ON DELETE SET NULL,
            FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
        );");

        // Tabela de Bens (Ativos)
        $db->exec("CREATE TABLE IF NOT EXISTS bens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            codigo_patrimonio TEXT UNIQUE,
            nome TEXT NOT NULL,
            descricao TEXT,
            setor_localizacao TEXT,
            data_aquisicao DATE,
            data_baixa DATE,
            status TEXT CHECK(status IN ('ativo', 'manutencao', 'inativo')) DEFAULT 'ativo',
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
        );");

        // Tabela de Manutenções
        $db->exec("CREATE TABLE IF NOT EXISTS manutencoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            bem_id INTEGER NOT NULL,
            descricao TEXT NOT NULL,
            tipo TEXT CHECK(tipo IN ('preventiva', 'corretiva')) DEFAULT 'preventiva',
            custo INTEGER DEFAULT 0, -- em centavos
            data_programada DATE NOT NULL,
            data_realizada DATE,
            status TEXT CHECK(status IN ('agendada', 'realizada', 'cancelada')) DEFAULT 'agendada',
            tecnico_responsavel TEXT,
            observacoes TEXT,
            conta_pagar_id INTEGER,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (bem_id) REFERENCES bens(id) ON DELETE CASCADE,
            FOREIGN KEY (conta_pagar_id) REFERENCES contas(id) ON DELETE SET NULL
        );");

        // Executa migrações dinâmicas para adicionar campos novos, se necessário (SQLite)
        try {
            @$db->exec("ALTER TABLE bens ADD COLUMN data_baixa DATE;");
        } catch (\PDOException $e) {
            // Ignora se o campo já existir
        }

        try {
            @$db->exec("ALTER TABLE cardapios_semanais ADD COLUMN cardapio_markdown TEXT;");
        } catch (\PDOException $e) {
            // Ignora se o campo já existir
        }

        try {
            @$db->exec("ALTER TABLE cardapios_semanais ADD COLUMN observacao_impressao TEXT;");
        } catch (\PDOException $e) {
            // Ignora se o campo já existir
        }

        // Insere categorias padrão se a tabela estiver vazia
        $stmtCats = $db->query("SELECT COUNT(*) FROM categorias");
        if ($stmtCats->fetchColumn() == 0) {
            $defaultCategories = ["Alimentos", "Bebidas", "Serviços", "Funcionários", "Infraestrutura", "Outros"];
            $stmtInsertCat = $db->prepare("INSERT INTO categorias (nome) VALUES (?)");
            foreach ($defaultCategories as $catName) {
                $stmtInsertCat->execute([$catName]);
            }
        }

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

        // Tabela de Cardápios Semanais (IA)
        $db->exec("CREATE TABLE IF NOT EXISTS cardapios_semanais (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            data_inicio DATE NOT NULL,
            data_fim DATE NOT NULL,
            observacoes_geracao TEXT,
            lista_compras TEXT NOT NULL,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
        );");

        // Tabela de Itens Diários do Cardápio
        $db->exec("CREATE TABLE IF NOT EXISTS cardapios_dias (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            cardapio_semanal_id INTEGER NOT NULL,
            dia_semana TEXT CHECK(dia_semana IN ('segunda', 'terca', 'quarta', 'quinta', 'sexta')) NOT NULL,
            refeicao_principal TEXT NOT NULL,
            lanche TEXT NOT NULL,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (cardapio_semanal_id) REFERENCES cardapios_semanais(id) ON DELETE CASCADE
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

        // Insere um usuário Nutricionista padrão
        $stmtNutri = $db->query("SELECT COUNT(*) FROM usuarios WHERE nivel = 'nutricionista'");
        if ($stmtNutri->fetchColumn() == 0) {
            $senhaNutri = password_hash("nutricionista123", PASSWORD_DEFAULT);
            $stmtInsertN = $db->prepare("INSERT INTO usuarios (nome, usuario, email, senha, nivel) VALUES (?, ?, ?, ?, 'nutricionista')");
            $stmtInsertN->execute(["Nutricionista", 'nutricionista', 'nutricao@santanna.com.br', $senhaNutri]);
        }

        // Insere as configurações padrão se não existirem
        $defaultConfigs = [
            'mailgrid_api_url' => 'https://www.mailgrid.com.br/api',
            'mailgrid_api_key' => '',
            'email_remetente' => 'financeiro@santanna.com.br',
            'nome_remetente' => "Financeiro Cantina Sant'Anna",
            'emails_destinatarios' => 'direcao@santanna.com.br',
            'dias_alerta_vencimento' => '3',
            'openrouter_api_key' => '',
            'openrouter_model' => 'google/gemini-2.5-flash',
            'cardapio_pessoas_estimadas' => '130 alunos do ensino médio, 30 funcionários',
            'cardapio_contexto_global' => 'Cantina escolar. Refeições saudáveis, saborosas e balanceadas.'
        ];

        foreach ($defaultConfigs as $chave => $valor) {
            $stmtConfig = $db->prepare("INSERT OR IGNORE INTO configuracoes (chave, valor) VALUES (?, ?)");
            $stmtConfig->execute([$chave, $valor]);
        }
    }
}
