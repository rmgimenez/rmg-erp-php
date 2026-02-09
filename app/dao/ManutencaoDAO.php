<?php
require_once __DIR__ . '/../models/Manutencao.php';
require_once __DIR__ . '/Conexao.php';

class ManutencaoDAO
{
    private $conexao;

    public function __construct()
    {
        $this->conexao = Conexao::getInstance();
    }

    public function salvar(Manutencao $m)
    {
        try {
            $sql = "INSERT INTO rmg_manutencao (bem_id, data_manutencao, descricao, custo, observacoes, empresa_id) 
                    VALUES (:bem_id, :data_manutencao, :descricao, :custo, :observacoes, :empresa_id)";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':bem_id', $m->getBemId());
            $stmt->bindValue(':data_manutencao', $m->getDataManutencao());
            $stmt->bindValue(':descricao', $m->getDescricao());
            $stmt->bindValue(':custo', $m->getCusto());
            $stmt->bindValue(':observacoes', $m->getObservacoes());
            $stmt->bindValue(':empresa_id', $m->getEmpresaId());
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function atualizar(Manutencao $m)
    {
        try {
            $sql = "UPDATE rmg_manutencao SET bem_id = :bem_id, data_manutencao = :data_manutencao, 
                    descricao = :descricao, custo = :custo, observacoes = :observacoes 
                    WHERE id_manutencao = :id_manutencao";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':bem_id', $m->getBemId());
            $stmt->bindValue(':data_manutencao', $m->getDataManutencao());
            $stmt->bindValue(':descricao', $m->getDescricao());
            $stmt->bindValue(':custo', $m->getCusto());
            $stmt->bindValue(':observacoes', $m->getObservacoes());
            $stmt->bindValue(':id_manutencao', $m->getIdManutencao());
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function excluir($id)
    {
        try {
            $sql = "DELETE FROM rmg_manutencao WHERE id_manutencao = :id_manutencao";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':id_manutencao', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function listarPorBem($bemId)
    {
        try {
            $sql = "SELECT m.*, b.descricao as descricao_bem
                    FROM rmg_manutencao m
                    JOIN rmg_bem b ON m.bem_id = b.id_bem
                    WHERE m.bem_id = :bem_id
                    ORDER BY m.data_manutencao DESC";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':bem_id', $bemId);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $lista = [];
            foreach ($result as $row) {
                $m = new Manutencao();
                $m->setIdManutencao($row['id_manutencao']);
                $m->setBemId($row['bem_id']);
                $m->setDescricaoBem($row['descricao_bem']);
                $m->setDataManutencao($row['data_manutencao']);
                $m->setDescricao($row['descricao']);
                $m->setCusto($row['custo']);
                $m->setObservacoes($row['observacoes']);
                $m->setEmpresaId($row['empresa_id']);
                $lista[] = $m;
            }
            return $lista;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function buscarPorPeriodo($inicio, $fim, $empresaId)
    {
        try {
            $sql = "SELECT m.*, b.descricao as descricao_bem
                    FROM rmg_manutencao m
                    JOIN rmg_bem b ON m.bem_id = b.id_bem
                    WHERE m.data_manutencao BETWEEN :inicio AND :fim
                    AND m.empresa_id = :empresa_id
                    ORDER BY m.data_manutencao DESC";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':inicio', $inicio);
            $stmt->bindValue(':fim', $fim);
            $stmt->bindValue(':empresa_id', $empresaId);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $lista = [];
            foreach ($result as $row) {
                $m = new Manutencao();
                $m->setIdManutencao($row['id_manutencao']);
                $m->setBemId($row['bem_id']);
                $m->setDescricaoBem($row['descricao_bem']);
                $m->setDataManutencao($row['data_manutencao']);
                $m->setDescricao($row['descricao']);
                $m->setCusto($row['custo']);
                $m->setObservacoes($row['observacoes']);
                $m->setEmpresaId($row['empresa_id']);
                $lista[] = $m;
            }
            return $lista;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function listarTodas($empresaId)
    {
        try {
            $sql = "SELECT m.*, b.descricao as descricao_bem
                    FROM rmg_manutencao m
                    JOIN rmg_bem b ON m.bem_id = b.id_bem
                    WHERE m.empresa_id = :empresa_id
                    ORDER BY m.data_manutencao DESC";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':empresa_id', $empresaId);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $lista = [];
            foreach ($result as $row) {
                $m = new Manutencao();
                $m->setIdManutencao($row['id_manutencao']);
                $m->setBemId($row['bem_id']);
                $m->setDescricaoBem($row['descricao_bem']);
                $m->setDataManutencao($row['data_manutencao']);
                $m->setDescricao($row['descricao']);
                $m->setCusto($row['custo']);
                $m->setObservacoes($row['observacoes']);
                $m->setEmpresaId($row['empresa_id']);
                $lista[] = $m;
            }
            return $lista;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function contarTotal($empresaId)
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM rmg_manutencao WHERE empresa_id = :empresa_id";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':empresa_id', $empresaId);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['total'] ?? 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function obterCustoPorMesUltimos12Meses($empresaId)
    {
        try {
            $sql = "SELECT DATE_FORMAT(data_manutencao, '%Y-%m') as mes, SUM(custo) as total 
                    FROM rmg_manutencao 
                    WHERE data_manutencao >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                    AND empresa_id = :empresa_id
                    GROUP BY DATE_FORMAT(data_manutencao, '%Y-%m') 
                    ORDER BY mes ASC";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':empresa_id', $empresaId);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Retorna o total gasto em manutenções nos últimos 30 dias (decimal)
    public function somaCustoUltimos30Dias($empresaId)
    {
        try {
            $sql = "SELECT COALESCE(SUM(custo), 0) as total FROM rmg_manutencao 
                    WHERE data_manutencao >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    AND empresa_id = :empresa_id";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':empresa_id', $empresaId);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (float) ($row['total'] ?? 0);
        } catch (PDOException $e) {
            return 0.0;
        }
    }

    // Retorna o total gasto em manutenções nos últimos 12 meses (decimal)
    public function somaCustoUltimos12Meses($empresaId)
    {
        try {
            $sql = "SELECT COALESCE(SUM(custo), 0) as total FROM rmg_manutencao 
                    WHERE data_manutencao >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                    AND empresa_id = :empresa_id";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':empresa_id', $empresaId);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (float) ($row['total'] ?? 0);
        } catch (PDOException $e) {
            return 0.0;
        }
    }

    /**
     * Últimas N manutenções realizadas (para listagem recente)
     */
    public function buscarUltimas($empresaId, $limite = 5)
    {
        try {
            $sql = "SELECT m.id_manutencao, m.data_manutencao, m.descricao, m.custo,
                           b.descricao as descricao_bem
                    FROM rmg_manutencao m
                    JOIN rmg_bem b ON m.bem_id = b.id_bem
                    WHERE m.empresa_id = :empresa_id
                    ORDER BY m.data_manutencao DESC
                    LIMIT :limite";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':empresa_id', $empresaId);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}
