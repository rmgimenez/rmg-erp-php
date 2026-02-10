<?php
require_once __DIR__ . '/../dao/ContaPagarDAO.php';
require_once __DIR__ . '/../dao/PagamentoDAO.php';
require_once __DIR__ . '/../dao/LogDAO.php';
require_once __DIR__ . '/../models/ContaPagar.php';
require_once __DIR__ . '/../models/Pagamento.php';

class ContaPagarController
{
    private $dao;
    private $pagamentoDAO;

    public function __construct()
    {
        $this->dao = new ContaPagarDAO();
        $this->pagamentoDAO = new PagamentoDAO();
    }

    public function salvar($dados)
    {
        $conta = new ContaPagar();
        $conta->setFornecedorId($dados['fornecedor_id']);
        $conta->setDescricao($dados['descricao']);
        $conta->setValor($dados['valor']);
        $conta->setDataVencimento($dados['data_vencimento']);
        $conta->setStatus($dados['status']);
        $conta->setObservacoes($dados['observacoes'] ?? '');
        $conta->setEmpresaId($_SESSION['empresa_id']);

        if (!empty($dados['id_conta_pagar'])) {
            $conta->setIdContaPagar($dados['id_conta_pagar']);
            $resultado = $this->dao->atualizar($conta);
            $msg = 'atualizada';
        } else {
            $resultado = $this->dao->salvar($conta);
            $msg = 'cadastrada';
        }

        if ($resultado) {
            $acao = !empty($dados['id_conta_pagar']) ? 'UPDATE' : 'INSERT';
            $idReg = !empty($dados['id_conta_pagar']) ? $dados['id_conta_pagar'] : null;
            LogDAO::registrar('rmg_conta_pagar', $acao, 'Conta a pagar ' . $msg . ': ' . $conta->getDescricao() . ' (R$ ' . number_format($conta->getValor(), 2, ',', '.') . ')', $idReg);
            return ['sucesso' => true, 'mensagem' => "Conta a pagar $msg com sucesso!"];
        } else {
            return ['sucesso' => false, 'mensagem' => "Erro ao salvar conta a pagar."];
        }
    }

    public function excluir($id)
    {
        // Regra de negócio: não deveria excluir se já foi paga? 
        $conta = $this->buscarPorId($id);

        if ($conta && $conta->getStatus() === 'paga') {
            return ['sucesso' => false, 'mensagem' => "Não é possível excluir uma conta já paga."];
        }

        if ($this->dao->excluir($id)) {
            LogDAO::registrar('rmg_conta_pagar', 'DELETE', 'Conta a pagar excluída (ID: ' . $id . ')', $id);
            return ['sucesso' => true, 'mensagem' => "Conta a pagar excluída com sucesso!"];
        } else {
            return ['sucesso' => false, 'mensagem' => "Erro ao excluir conta a pagar."];
        }
    }

    public function registrarPagamento($dados)
    {
        $idConta = $dados['id_conta_pagar'] ?? null;
        $valor = $dados['valor_pago'] ?? 0;
        $data = $dados['data_pagamento'] ?? date('Y-m-d');

        if (!$idConta || $valor <= 0) {
            return ['sucesso' => false, 'mensagem' => "Dados de pagamento inválidos."];
        }

        $pagamento = new Pagamento();
        $pagamento->setContaPagarId($idConta);
        $pagamento->setValorPago($valor);
        $pagamento->setDataPagamento($data);
        $pagamento->setEmpresaId($_SESSION['empresa_id']);

        if ($this->pagamentoDAO->salvar($pagamento)) {
            LogDAO::registrar('rmg_pagamento', 'INSERT', 'Pagamento registrado para conta ID: ' . $idConta . ' (R$ ' . number_format($valor, 2, ',', '.') . ')', $idConta);
            return ['sucesso' => true, 'mensagem' => "Pagamento registrado com sucesso!"];
        } else {
            return ['sucesso' => false, 'mensagem' => "Erro ao registrar pagamento."];
        }
    }

    public function listarContas()
    {
        return $this->dao->listar($_SESSION['empresa_id']);
    }

    public function buscarPorId($id)
    {
        return $this->dao->buscarPorId($id);
    }
}
