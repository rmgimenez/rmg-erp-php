<?php
require_once __DIR__ . '/../dao/ContaReceberDAO.php';
require_once __DIR__ . '/../models/ContaReceber.php';

class ContaReceberController {
    private $dao;

    public function __construct() {
        $this->dao = new ContaReceberDAO();
    }

    public function salvar($dados) {
        $conta = new ContaReceber();
        $conta->setClienteId($dados['cliente_id']);
        $conta->setDescricao($dados['descricao']);
        $conta->setValor($dados['valor']);
        $conta->setDataVencimento($dados['data_vencimento']);
        $conta->setStatus($dados['status']);
        $conta->setObservacoes($dados['observacoes'] ?? '');

        if (!empty($dados['id_conta_receber'])) {
            $conta->setIdContaReceber($dados['id_conta_receber']);
            $resultado = $this->dao->atualizar($conta);
            $msg = 'atualizada';
        } else {
            $resultado = $this->dao->salvar($conta);
            $msg = 'cadastrada';
        }

        if ($resultado) {
            return ['sucesso' => true, 'mensagem' => "Conta a receber $msg com sucesso!"];
        } else {
            return ['sucesso' => false, 'mensagem' => "Erro ao salvar conta a receber."];
        }
    }

    public function excluir($id) {
        if ($this->dao->excluir($id)) {
            return ['sucesso' => true, 'mensagem' => "Conta a receber excluída com sucesso!"];
        } else {
            return ['sucesso' => false, 'mensagem' => "Erro ao excluir conta a receber."];
        }
    }

    public function listarContas() {
        return $this->dao->listar();
    }

    public function buscarPorId($id) {
        return $this->dao->buscarPorId($id);
    }
}
