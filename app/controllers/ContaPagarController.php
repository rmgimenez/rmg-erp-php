<?php
require_once __DIR__ . '/../dao/ContaPagarDAO.php';
require_once __DIR__ . '/../models/ContaPagar.php';

class ContaPagarController {
    private $dao;

    public function __construct() {
        $this->dao = new ContaPagarDAO();
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
        // Regra de negócio: não deveria excluir se já foi paga? (Se houver pagamentos na outra tabela - mas vamos seguir simples por enquanto ou validar status)
        // Por enquanto, validamos se está paga basedo no status, mas idealmente seria verificar pagamentos existentes.
        
        $conta = $this->buscarPorId($id);
        
        // Exemplo simplificado: não pode excluir se o status for 'paga'
        // if ($conta && $conta->getStatus() === 'paga') {
        //     return ['sucesso' => false, 'mensagem' => "Não é possível excluir uma conta já paga."];
        // }

        if ($this->dao->excluir($id)) {
            return ['sucesso' => true, 'mensagem' => "Conta a pagar excluída com sucesso!"];
        } else {
            return ['sucesso' => false, 'mensagem' => "Erro ao excluir conta a pagar."];
        }
    }

    public function listarContas() {
        return $this->dao->listar();
    }

    public function buscarPorId($id) {
        return $this->dao->buscarPorId($id);
    }
}
