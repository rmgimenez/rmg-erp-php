<?php
namespace CantinaFinanceiro;

use PDO;
use Exception;

/**
 * Classe AccountModel
 * Gerencia as operações CRUD e agregações de dados financeiros da tabela 'contas'.
 */
class AccountModel {
    /**
     * Cadastra um novo lançamento de conta a pagar ou receber
     */
    public static function create(array $data, int $criadoPor): bool {
        $db = Database::getConnection();

        $stmt = $db->prepare("INSERT INTO contas 
            (descricao, valor, tipo, status, data_vencimento, data_liquidacao, categoria, observacoes, criado_por) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $success = $stmt->execute([
            $data['descricao'],
            (int)$data['valor'], // deve vir em centavos do controller
            $data['tipo'],
            $data['status'] ?? 'pendente',
            $data['data_vencimento'],
            ($data['status'] ?? 'pendente') === 'pago' ? ($data['data_liquidacao'] ?? date('Y-m-d')) : null,
            $data['categoria'],
            $data['observacoes'] ?? null,
            $criadoPor
        ]);

        if ($success) {
            $lastId = $db->lastInsertId();
            $valorFormatado = "R$ " . number_format($data['valor'] / 100, 2, ',', '.');
            Auth::logAction($criadoPor, 'CONTA_CADASTRAR', "Conta #{$lastId} ({$data['tipo']}) de valor {$valorFormatado} cadastrada.");
        }

        return $success;
    }

    /**
     * Atualiza dados de um lançamento existente
     */
    public static function update(int $id, array $data, int $usuarioId): bool {
        $db = Database::getConnection();

        $stmt = $db->prepare("UPDATE contas SET 
            descricao = ?, 
            valor = ?, 
            status = ?, 
            data_vencimento = ?, 
            data_liquidacao = ?, 
            categoria = ?, 
            observacoes = ? 
            WHERE id = ?");

        $dataLiquidacao = $data['status'] === 'pago' ? ($data['data_liquidacao'] ?? date('Y-m-d')) : null;

        $success = $stmt->execute([
            $data['descricao'],
            (int)$data['valor'],
            $data['status'],
            $data['data_vencimento'],
            $dataLiquidacao,
            $data['categoria'],
            $data['observacoes'] ?? null,
            $id
        ]);

        if ($success) {
            $valorFormatado = "R$ " . number_format($data['valor'] / 100, 2, ',', '.');
            Auth::logAction($usuarioId, 'CONTA_EDITAR', "Conta #{$id} atualizada. Novo valor: {$valorFormatado}. Status: {$data['status']}.");
        }

        return $success;
    }

    /**
     * Altera o status de liquidação (pagamento/recebimento) de uma conta
     */
    public static function setStatus(int $id, string $status, ?string $dataLiquidacao, int $usuarioId): bool {
        $db = Database::getConnection();

        $liquidacao = $status === 'pago' ? ($dataLiquidacao ?? date('Y-m-d')) : null;

        $stmt = $db->prepare("UPDATE contas SET status = ?, data_liquidacao = ? WHERE id = ?");
        $success = $stmt->execute([$status, $liquidacao, $id]);

        if ($success) {
            Auth::logAction($usuarioId, 'CONTA_STATUS', "Status da conta #{$id} alterado para '{$status}' (Liquidação: " . ($liquidacao ?? 'N/A') . ").");
        }

        return $success;
    }

    /**
     * Remove um lançamento do banco
     */
    public static function delete(int $id, int $usuarioId): bool {
        $db = Database::getConnection();

        // Busca dados para registrar o log
        $conta = self::getById($id);
        if (!$conta) return false;

        $stmt = $db->prepare("DELETE FROM contas WHERE id = ?");
        $success = $stmt->execute([$id]);

        if ($success) {
            $valorFormatado = "R$ " . number_format($conta['valor'] / 100, 2, ',', '.');
            Auth::logAction($usuarioId, 'CONTA_EXCLUIR', "Conta #{$id} ({$conta['tipo']}) de {$valorFormatado} excluída.");
        }

        return $success;
    }

    /**
     * Retorna uma conta por ID
     */
    public static function getById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM contas WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $conta = $stmt->fetch();
        return $conta ?: null;
    }

    /**
     * Retorna lista de contas com filtros opcionais (com suporte a generators para memória otimizada em relatórios)
     */
    public static function getAll(array $filters = []): array {
        $db = Database::getConnection();
        
        $sql = "SELECT c.*, u.nome as cadastrado_por_nome 
                FROM contas c 
                LEFT JOIN usuarios u ON c.criado_por = u.id 
                WHERE 1=1";
        $params = [];

        if (!empty($filters['tipo'])) {
            $sql .= " AND c.tipo = ?";
            $params[] = $filters['tipo'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND c.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['categoria'])) {
            $sql .= " AND c.categoria = ?";
            $params[] = $filters['categoria'];
        }

        if (!empty($filters['data_inicio'])) {
            $sql .= " AND c.data_vencimento >= ?";
            $params[] = $filters['data_inicio'];
        }

        if (!empty($filters['data_fim'])) {
            $sql .= " AND c.data_vencimento <= ?";
            $params[] = $filters['data_fim'];
        }

        if (!empty($filters['busca'])) {
            $sql .= " AND c.descricao LIKE ?";
            $params[] = '%' . $filters['busca'] . '%';
        }

        // Ordenação padrão
        $sql .= " ORDER BY c.data_vencimento ASC, c.id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Consolida e-mails e gera array gerador de memória otimizada
     */
    public static function yieldAll(array $filters = []): \Generator {
        $db = Database::getConnection();
        
        $sql = "SELECT * FROM contas WHERE 1=1";
        $params = [];

        if (!empty($filters['tipo'])) {
            $sql .= " AND tipo = ?";
            $params[] = $filters['tipo'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        $sql .= " ORDER BY data_vencimento ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        while ($row = $stmt->fetch()) {
            yield $row;
        }
    }

    /**
     * Calcula métricas agregadas do caixa (KPIs)
     */
    public static function getKPIs(): array {
        $db = Database::getConnection();
        $hoje = date('Y-m-d');

        $kpis = [
            'total_recebido' => 0,
            'total_pago' => 0,
            'pendente_receber' => 0,
            'pendente_pagar' => 0,
            'vencidas' => 0,
            'saldo_efetivo' => 0,
            'saldo_previsto' => 0
        ];

        // Total Recebido
        $stmt = $db->query("SELECT SUM(valor) FROM contas WHERE tipo = 'receber' AND status = 'pago'");
        $kpis['total_recebido'] = (int)$stmt->fetchColumn();

        // Total Pago
        $stmt = $db->query("SELECT SUM(valor) FROM contas WHERE tipo = 'pagar' AND status = 'pago'");
        $kpis['total_pago'] = (int)$stmt->fetchColumn();

        // Pendente a Receber
        $stmt = $db->query("SELECT SUM(valor) FROM contas WHERE tipo = 'receber' AND status = 'pendente'");
        $kpis['pendente_receber'] = (int)$stmt->fetchColumn();

        // Pendente a Pagar
        $stmt = $db->query("SELECT SUM(valor) FROM contas WHERE tipo = 'pagar' AND status = 'pendente'");
        $kpis['pendente_pagar'] = (int)$stmt->fetchColumn();

        // Vencidas e Pendentes
        $stmt = $db->prepare("SELECT SUM(valor) FROM contas WHERE status = 'pendente' AND data_vencimento < ?");
        $stmt->execute([$hoje]);
        $kpis['vencidas'] = (int)$stmt->fetchColumn();

        // Balanços
        $kpis['saldo_efetivo'] = $kpis['total_recebido'] - $kpis['total_pago'];
        $kpis['saldo_previsto'] = ($kpis['total_recebido'] + $kpis['pendente_receber']) - ($kpis['total_pago'] + $kpis['pendente_pagar']);

        return $kpis;
    }
}
