<?php
require_once __DIR__ . '/../models/ContaReceber.php';
require_once __DIR__ . '/Conexao.php';

class ContaReceberDAO
{
    private $conexao;

    public function __construct()
    {
        $this->conexao = Conexao::getInstance();
    }

    public function salvar(ContaReceber $conta)
    {
        try {
            $sql = "INSERT INTO rmg_conta_receber (empresa_id, cliente_id, descricao, valor, data_vencimento, status, observacoes) 
                    VALUES (:empresa_id, :cliente_id, :descricao, :valor, :data_vencimento, :status, :observacoes)";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':empresa_id', $conta->getEmpresaId());
            $stmt->bindValue(':cliente_id', $conta->getClienteId());
            $stmt->bindValue(':descricao', $conta->getDescricao());
            $stmt->bindValue(':valor', $conta->getValor());
            $stmt->bindValue(':data_vencimento', $conta->getDataVencimento());
            $stmt->bindValue(':status', $conta->getStatus());
            $stmt->bindValue(':observacoes', $conta->getObservacoes());
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function atualizar(ContaReceber $conta)
    {
        try {
            $sql = "UPDATE rmg_conta_receber SET cliente_id = :cliente_id, descricao = :descricao, 
                    valor = :valor, data_vencimento = :data_vencimento, status = :status, observacoes = :observacoes 
                    WHERE id_conta_receber = :id_conta_receber";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':cliente_id', $conta->getClienteId());
            $stmt->bindValue(':descricao', $conta->getDescricao());
            $stmt->bindValue(':valor', $conta->getValor());
            $stmt->bindValue(':data_vencimento', $conta->getDataVencimento());
            $stmt->bindValue(':status', $conta->getStatus());
            $stmt->bindValue(':observacoes', $conta->getObservacoes());
            $stmt->bindValue(':id_conta_receber', $conta->getIdContaReceber());
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function excluir($id)
    {
        try {
            $sql = "DELETE FROM rmg_conta_receber WHERE id_conta_receber = :id_conta_receber";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':id_conta_receber', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function listar($empresaId)
    {
        try {
            $sql = "SELECT c.*, cli.nome as nome_cliente 
                    FROM rmg_conta_receber c
                    LEFT JOIN rmg_cliente cli ON c.cliente_id = cli.id_cliente
                    WHERE c.empresa_id = :empresa_id
                    ORDER BY c.data_vencimento ASC";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':empresa_id', $empresaId);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $contas = [];
            foreach ($result as $row) {
                $c = new ContaReceber();
                $c->setIdContaReceber($row['id_conta_receber']);
                $c->setEmpresaId($row['empresa_id']);
                $c->setClienteId($row['cliente_id']);
                $c->setNomeCliente($row['nome_cliente']);
                $c->setDescricao($row['descricao']);
                $c->setValor($row['valor']);
                $c->setDataVencimento($row['data_vencimento']);
                $c->setStatus($row['status']);
                $c->setObservacoes($row['observacoes']);
                $contas[] = $c;
            }
            return $contas;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function buscarPorId($id)
    {
        try {
            $sql = "SELECT c.*, cli.nome as nome_cliente 
                    FROM rmg_conta_receber c
                    LEFT JOIN rmg_cliente cli ON c.cliente_id = cli.id_cliente
                    WHERE c.id_conta_receber = :id_conta_receber";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':id_conta_receber', $id);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $c = new ContaReceber();
                $c->setIdContaReceber($row['id_conta_receber']);
                $c->setEmpresaId($row['empresa_id']);
                $c->setClienteId($row['cliente_id']);
                $c->setNomeCliente($row['nome_cliente']);
                $c->setDescricao($row['descricao']);
                $c->setValor($row['valor']);
                $c->setDataVencimento($row['data_vencimento']);
                $c->setStatus($row['status']);
                $c->setObservacoes($row['observacoes']);
                return $c;
            }
            return null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function buscarVencidasEProximas($empresaId, $dias = 10)
    {
        try {
            // Seleciona Pendentes que estão Vencidas (qualquer data passada) OU Vencem nos próximos X dias
            $sql = "SELECT c.*, cli.nome as nome_cliente 
                    FROM rmg_conta_receber c
                    LEFT JOIN rmg_cliente cli ON c.cliente_id = cli.id_cliente
                    WHERE c.status != 'recebida' 
                    AND c.empresa_id = :empresa_id
                    AND c.data_vencimento <= DATE_ADD(CURDATE(), INTERVAL :dias DAY)
                    ORDER BY (c.data_vencimento < CURDATE()) DESC, c.data_vencimento ASC";

            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':empresa_id', $empresaId);
            $stmt->bindValue(':dias', $dias, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $contas = [];
            foreach ($result as $row) {
                $c = new ContaReceber();
                $c->setIdContaReceber($row['id_conta_receber']);
                $c->setEmpresaId($row['empresa_id']);
                $c->setClienteId($row['cliente_id']);
                $c->setNomeCliente($row['nome_cliente']);
                $c->setDescricao($row['descricao']);
                $c->setValor($row['valor']);
                $c->setDataVencimento($row['data_vencimento']);
                $c->setStatus($row['status']);
                $c->setObservacoes($row['observacoes']);
                $contas[] = $c;
            }
            return $contas;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function buscarPorPeriodo($inicio, $fim, $empresaId)
    {
        try {
            $sql = "SELECT c.*, cli.nome as nome_cliente 
                    FROM rmg_conta_receber c
                    LEFT JOIN rmg_cliente cli ON c.cliente_id = cli.id_cliente
                    WHERE c.data_vencimento BETWEEN :inicio AND :fim
                    AND c.empresa_id = :empresa_id
                    ORDER BY c.data_vencimento ASC";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':inicio', $inicio);
            $stmt->bindValue(':fim', $fim);
            $stmt->bindValue(':empresa_id', $empresaId);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $contas = [];
            foreach ($result as $row) {
                $c = new ContaReceber();
                $c->setIdContaReceber($row['id_conta_receber']);
                $c->setEmpresaId($row['empresa_id']);
                $c->setClienteId($row['cliente_id']);
                $c->setNomeCliente($row['nome_cliente']);
                $c->setDescricao($row['descricao']);
                $c->setValor($row['valor']);
                $c->setDataVencimento($row['data_vencimento']);
                $c->setStatus($row['status']);
                $c->setObservacoes($row['observacoes']);
                $contas[] = $c;
            }
            return $contas;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function obterTotais($empresaId)
    {
        try {
            $sql = "SELECT status, SUM(valor) as total FROM rmg_conta_receber WHERE empresa_id = :empresa_id GROUP BY status";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':empresa_id', $empresaId);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stats = ['pendente' => 0, 'recebida' => 0];
            foreach ($result as $row) {
                $stats[$row['status']] = $row['total'];
            }
            return $stats;
        } catch (PDOException $e) {
            return ['pendente' => 0, 'recebida' => 0];
        }
    }

    /**
     * Conta quantas contas a receber estão vencidas (pendentes com data < hoje)
     */
    public function contarVencidas($empresaId)
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM rmg_conta_receber 
                    WHERE status = 'pendente' AND data_vencimento < CURDATE() AND empresa_id = :empresa_id";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':empresa_id', $empresaId);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($row['total'] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Soma valor das contas vencidas a receber
     */
    public function somaVencidas($empresaId)
    {
        try {
            $sql = "SELECT COALESCE(SUM(valor), 0) as total FROM rmg_conta_receber 
                    WHERE status = 'pendente' AND data_vencimento < CURDATE() AND empresa_id = :empresa_id";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':empresa_id', $empresaId);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (float)($row['total'] ?? 0);
        } catch (PDOException $e) {
            return 0.0;
        }
    }

    /**
     * Busca contas pendentes vencidas ou que vencem nos próximos N dias
     */
    public function buscarProximasVencer($empresaId, $dias = 7, $limite = 10)
    {
        try {
            $sql = "SELECT c.id_conta_receber, c.descricao, c.valor, c.data_vencimento, c.status,
                           cli.nome as nome_cliente,
                           DATEDIFF(c.data_vencimento, CURDATE()) as dias_restantes
                    FROM rmg_conta_receber c
                    LEFT JOIN rmg_cliente cli ON c.cliente_id = cli.id_cliente
                    WHERE c.status = 'pendente' AND c.empresa_id = :empresa_id
                    AND c.data_vencimento <= DATE_ADD(CURDATE(), INTERVAL :dias DAY)
                    ORDER BY c.data_vencimento ASC
                    LIMIT :limite";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':empresa_id', $empresaId);
            $stmt->bindValue(':dias', $dias, PDO::PARAM_INT);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Soma valor que vence no mês atual
     */
    public function somaVencimentoMesAtual($empresaId)
    {
        try {
            $sql = "SELECT COALESCE(SUM(valor), 0) as total FROM rmg_conta_receber 
                    WHERE status = 'pendente' 
                    AND YEAR(data_vencimento) = YEAR(CURDATE()) 
                    AND MONTH(data_vencimento) = MONTH(CURDATE())
                    AND empresa_id = :empresa_id";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':empresa_id', $empresaId);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (float)($row['total'] ?? 0);
        } catch (PDOException $e) {
            return 0.0;
        }
    }
}
