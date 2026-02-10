<?php
require_once __DIR__ . '/../dao/LogDAO.php';

class LogController
{
    private $logDAO;

    public function __construct()
    {
        $this->logDAO = new LogDAO();
    }

    /**
     * Lista logs da empresa do usuário logado (para gerentes).
     */
    public function listarLogsDaEmpresa($filtros = [])
    {
        $empresaId = $_SESSION['empresa_id'] ?? null;
        if (!$empresaId) {
            return [];
        }
        return $this->logDAO->listarPorEmpresa($empresaId, $filtros);
    }

    /**
     * Lista todos os logs do sistema (para super_admin).
     */
    public function listarTodosLogs($filtros = [])
    {
        return $this->logDAO->listarTodos($filtros);
    }

    /**
     * Retorna os nomes amigáveis das tabelas para exibição.
     */
    public static function nomesTabelas()
    {
        return [
            'rmg_empresa'       => 'Empresa',
            'rmg_usuario'       => 'Usuário',
            'rmg_setor'         => 'Setor',
            'rmg_fornecedor'    => 'Fornecedor',
            'rmg_cliente'       => 'Cliente',
            'rmg_bem'           => 'Bem/Equipamento',
            'rmg_manutencao'    => 'Manutenção',
            'rmg_conta_pagar'   => 'Conta a Pagar',
            'rmg_conta_receber' => 'Conta a Receber',
            'rmg_pagamento'     => 'Pagamento',
            'rmg_recebimento'   => 'Recebimento',
        ];
    }

    /**
     * Retorna o nome amigável de uma tabela.
     */
    public static function nomeTabela($tabela)
    {
        $nomes = self::nomesTabelas();
        return $nomes[$tabela] ?? $tabela;
    }

    /**
     * Retorna a classe CSS do badge conforme a ação.
     */
    public static function badgeAcao($acao)
    {
        switch ($acao) {
            case 'INSERT':
                return 'bg-success';
            case 'UPDATE':
                return 'bg-warning text-dark';
            case 'DELETE':
                return 'bg-danger';
            default:
                return 'bg-secondary';
        }
    }

    /**
     * Retorna o ícone FontAwesome conforme a ação.
     */
    public static function iconeAcao($acao)
    {
        switch ($acao) {
            case 'INSERT':
                return 'fa-plus-circle';
            case 'UPDATE':
                return 'fa-edit';
            case 'DELETE':
                return 'fa-trash-alt';
            default:
                return 'fa-info-circle';
        }
    }
}
