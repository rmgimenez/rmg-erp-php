<?php
require_once __DIR__ . '/../dao/ContaPagarDAO.php';
require_once __DIR__ . '/../dao/PagamentoDAO.php';
require_once __DIR__ . '/../models/ContaPagar.php';
require_once __DIR__ . '/../models/Pagamento.php';

class ContaPagarController {
    private $dao;
    private $pagamentoDAO;

    public function __construct() {
        $this->dao = new ContaPagarDAO();
        $this->pagamentoDAO = new PagamentoDAO();
    }

    public function salvar($dados) {
        $conta = new ContaPagar();
        $conta->setFornecedorId($dados['fornecedor_id']);
        $conta->setDescricao($dados['descricao']);
        $conta->setValor($dados['valor']);
        $conta->setDataVencimento($dados['data_vencimento']);
        $conta->setStatus($dados['status']);
        $conta->setObservacoes($dados['observacoes'] ?? '');

        if (!empty($dados['id_conta_pagar'])) {
            $conta->setIdContaPagar($dados['id_conta_pagar']);
            $resultado = $this->dao->atualizar($conta);
            $msg = 'atualizada';
        } else {
            $resultado = $this->dao->salvar($conta);
            $msg = 'cadastrada';
        }

        if ($resultado) {
            return ['sucesso' => true, 'mensagem' => "Conta a pagar $msg com sucesso!"];
        } else {
            return ['sucesso' => false, 'mensagem' => "Erro ao salvar conta a pagar."];
        }
    }

    public function excluir($id) {
        // Regra de negócio: não deveria excluir se já foi paga? 
        $conta = $this->buscarPorId($id);
        
        if ($conta && $conta->getStatus() === 'paga') {
             return ['sucesso' => false, 'mensagem' => "Não é possível excluir uma conta já paga."];
        }

        if ($this->dao->excluir($id)) {
            return ['sucesso' => true, 'mensagem' => "Conta a pagar excluída com sucesso!"];
        } else {
            return ['sucesso' => false, 'mensagem' => "Erro ao excluir conta a pagar."];
        }
    }

    public function registrarPagamento($dados) {
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

        if ($this->pagamentoDAO->salvar($pagamento)) {
            return ['sucesso' => true, 'mensagem' => "Pagamento registrado com sucesso!"];
        } else {
            return ['sucesso' => false, 'mensagem' => "Erro ao registrar pagamento."];
        }
    }

    public function listarContas() {
        return $this->dao->listar();
    }

    public function buscarPorId($id) {
        return $this->dao->buscarPorId($id);
    }
}
