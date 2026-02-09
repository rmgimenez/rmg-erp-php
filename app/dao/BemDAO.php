<?php
require_once __DIR__ . '/../models/Bem.php';
require_once __DIR__ . '/Conexao.php';

class BemDAO
{
    private $conexao;

    public function __construct()
    {
        $this->conexao = Conexao::getInstance();
    }

    public function salvar(Bem $bem)
    {
        try {
            $sql = "INSERT INTO rmg_bem (descricao, setor_id, data_aquisicao, valor_aquisicao, status, observacoes, empresa_id) 
                    VALUES (:descricao, :setor_id, :data_aquisicao, :valor_aquisicao, :status, :observacoes, :empresa_id)";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':descricao', $bem->getDescricao());
            $stmt->bindValue(':setor_id', $bem->getSetorId());
            $stmt->bindValue(':data_aquisicao', $bem->getDataAquisicao());
            $stmt->bindValue(':valor_aquisicao', $bem->getValorAquisicao());
            $stmt->bindValue(':status', $bem->getStatus());
            $stmt->bindValue(':observacoes', $bem->getObservacoes());
            $stmt->bindValue(':empresa_id', $bem->getEmpresaId());
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function atualizar(Bem $bem)
    {
        try {
            $sql = "UPDATE rmg_bem SET descricao = :descricao, setor_id = :setor_id, data_aquisicao = :data_aquisicao, 
                    valor_aquisicao = :valor_aquisicao, status = :status, observacoes = :observacoes 
                    WHERE id_bem = :id_bem";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':descricao', $bem->getDescricao());
            $stmt->bindValue(':setor_id', $bem->getSetorId());
            $stmt->bindValue(':data_aquisicao', $bem->getDataAquisicao());
            $stmt->bindValue(':valor_aquisicao', $bem->getValorAquisicao());
            $stmt->bindValue(':status', $bem->getStatus());
            $stmt->bindValue(':observacoes', $bem->getObservacoes());
            $stmt->bindValue(':id_bem', $bem->getIdBem());
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function excluir($id)
    {
        try {
            // Verificar manutenções antes? O banco deve ter FKs, então vai estourar erro se tiver manutenção ou o controller trata
            $sql = "DELETE FROM rmg_bem WHERE id_bem = :id_bem";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':id_bem', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function listar($empresaId)
    {
        try {
            $sql = "SELECT b.*, s.nome as nome_setor,
                    (SELECT COALESCE(SUM(custo), 0) FROM rmg_manutencao WHERE bem_id = b.id_bem) as total_manutencao
                    FROM rmg_bem b
                    LEFT JOIN rmg_setor s ON b.setor_id = s.id_setor
                    WHERE b.empresa_id = :empresa_id
                    ORDER BY b.descricao";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':empresa_id', $empresaId);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $bens = [];
            foreach ($result as $row) {
                $b = new Bem();
                $b->setIdBem($row['id_bem']);
                $b->setDescricao($row['descricao']);
                $b->setSetorId($row['setor_id']);
                $b->setNomeSetor($row['nome_setor']);
                $b->setDataAquisicao($row['data_aquisicao']);
                $b->setValorAquisicao($row['valor_aquisicao']);
                $b->setStatus($row['status']);
                $b->setObservacoes($row['observacoes']);
                $b->setEmpresaId($row['empresa_id']);
                $b->setTotalManutencao($row['total_manutencao']);
                $bens[] = $b;
            }
            return $bens;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function buscarPorId($id)
    {
        try {
            $sql = "SELECT b.*, s.nome as nome_setor,
                    (SELECT COALESCE(SUM(custo), 0) FROM rmg_manutencao WHERE bem_id = b.id_bem) as total_manutencao 
                    FROM rmg_bem b
                    LEFT JOIN rmg_setor s ON b.setor_id = s.id_setor
                    WHERE b.id_bem = :id_bem";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':id_bem', $id);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $b = new Bem();
                $b->setIdBem($row['id_bem']);
                $b->setDescricao($row['descricao']);
                $b->setSetorId($row['setor_id']);
                $b->setNomeSetor($row['nome_setor']);
                $b->setDataAquisicao($row['data_aquisicao']);
                $b->setValorAquisicao($row['valor_aquisicao']);
                $b->setStatus($row['status']);
                $b->setObservacoes($row['observacoes']);
                $b->setEmpresaId($row['empresa_id']);
                $b->setTotalManutencao($row['total_manutencao']);
                return $b;
            }
            return null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function contarPorStatus($empresaId)
    {
        try {
            $sql = "SELECT status, COUNT(*) as qtd FROM rmg_bem WHERE empresa_id = :empresa_id GROUP BY status";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':empresa_id', $empresaId);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stats = ['ativo' => 0, 'baixado' => 0];
            foreach ($result as $row) {
                $stats[$row['status']] = $row['qtd'];
            }
            return $stats;
        } catch (PDOException $e) {
            return ['ativo' => 0, 'baixado' => 0];
        }
    }
}
