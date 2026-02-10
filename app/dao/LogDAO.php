<?php
require_once __DIR__ . '/../models/Log.php';
require_once __DIR__ . '/Conexao.php';

class LogDAO
{
    private $conexao;

    public function __construct()
    {
        $this->conexao = Conexao::getInstance();
    }

    /**
     * Registra um log no sistema.
     * Método estático de conveniência para uso rápido nos controllers.
     */
    public static function registrar($tabela, $acao, $descricao, $registroId = null, $dadosAnteriores = null, $dadosNovos = null)
    {
        try {
            $conexao = Conexao::getInstance();
            $sql = "INSERT INTO rmg_log (empresa_id, usuario_id, usuario_nome, tabela, acao, registro_id, descricao, dados_anteriores, dados_novos, ip)
                    VALUES (:empresa_id, :usuario_id, :usuario_nome, :tabela, :acao, :registro_id, :descricao, :dados_anteriores, :dados_novos, :ip)";
            $stmt = $conexao->prepare($sql);
            $stmt->bindValue(':empresa_id', $_SESSION['empresa_id'] ?? null);
            $stmt->bindValue(':usuario_id', $_SESSION['usuario_id'] ?? null);
            $stmt->bindValue(':usuario_nome', $_SESSION['usuario_nome'] ?? 'Sistema');
            $stmt->bindValue(':tabela', $tabela);
            $stmt->bindValue(':acao', $acao);
            $stmt->bindValue(':registro_id', $registroId);
            $stmt->bindValue(':descricao', $descricao);
            $stmt->bindValue(':dados_anteriores', $dadosAnteriores);
            $stmt->bindValue(':dados_novos', $dadosNovos);
            $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? '');
            return $stmt->execute();
        } catch (PDOException $e) {
            // Falha no log não deve impedir a operação principal
            return false;
        }
    }

    /**
     * Lista logs de uma empresa específica com filtros opcionais.
     */
    public function listarPorEmpresa($empresaId, $filtros = [])
    {
        try {
            $where = "WHERE l.empresa_id = :empresa_id";
            $params = [':empresa_id' => $empresaId];

            $where .= $this->montarFiltros($filtros, $params);

            $sql = "SELECT l.*, e.codigo as empresa_codigo, e.nome_fantasia as empresa_nome
                    FROM rmg_log l
                    LEFT JOIN rmg_empresa e ON l.empresa_id = e.id_empresa
                    $where
                    ORDER BY l.data_hora DESC
                    LIMIT 500";
            $stmt = $this->conexao->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Lista todos os logs (para super_admin) com filtros opcionais.
     */
    public function listarTodos($filtros = [])
    {
        try {
            $where = "WHERE 1=1";
            $params = [];

            if (!empty($filtros['empresa_id'])) {
                $where .= " AND l.empresa_id = :empresa_id";
                $params[':empresa_id'] = $filtros['empresa_id'];
            }

            $where .= $this->montarFiltros($filtros, $params);

            $sql = "SELECT l.*, e.codigo as empresa_codigo, e.nome_fantasia as empresa_nome
                    FROM rmg_log l
                    LEFT JOIN rmg_empresa e ON l.empresa_id = e.id_empresa
                    $where
                    ORDER BY l.data_hora DESC
                    LIMIT 1000";
            $stmt = $this->conexao->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Monta cláusulas WHERE adicionais com base nos filtros.
     */
    private function montarFiltros($filtros, &$params)
    {
        $where = '';

        if (!empty($filtros['tabela'])) {
            $where .= " AND l.tabela = :tabela";
            $params[':tabela'] = $filtros['tabela'];
        }

        if (!empty($filtros['acao'])) {
            $where .= " AND l.acao = :acao";
            $params[':acao'] = $filtros['acao'];
        }

        if (!empty($filtros['usuario_nome'])) {
            $where .= " AND l.usuario_nome LIKE :usuario_nome";
            $params[':usuario_nome'] = '%' . $filtros['usuario_nome'] . '%';
        }

        if (!empty($filtros['data_inicio'])) {
            $where .= " AND l.data_hora >= :data_inicio";
            $params[':data_inicio'] = $filtros['data_inicio'] . ' 00:00:00';
        }

        if (!empty($filtros['data_fim'])) {
            $where .= " AND l.data_hora <= :data_fim";
            $params[':data_fim'] = $filtros['data_fim'] . ' 23:59:59';
        }

        return $where;
    }

    /**
     * Conta total de logs por empresa.
     */
    public function contarPorEmpresa($empresaId)
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM rmg_log WHERE empresa_id = :empresa_id";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':empresa_id', $empresaId);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['total'];
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Conta total de logs no sistema.
     */
    public function contarTodos()
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM rmg_log";
            $stmt = $this->conexao->prepare($sql);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['total'];
        } catch (PDOException $e) {
            return 0;
        }
    }
}
