<?php
require_once __DIR__ . '/../dao/ContaReceberDAO.php';
require_once __DIR__ . '/../dao/RecebimentoDAO.php';
require_once __DIR__ . '/../models/ContaReceber.php';
require_once __DIR__ . '/../models/Recebimento.php';

class ContaReceberController
{
    private $dao;
    private $recebimentoDAO;

    public function __construct()
    {
        $this->dao = new ContaReceberDAO();
        $this->recebimentoDAO = new RecebimentoDAO();
    }

    public function salvar($dados)
    {
        $conta = new ContaReceber();
        $conta->setClienteId($dados['cliente_id']);
        $conta->setDescricao($dados['descricao']);
        $conta->setValor($dados['valor']);
        $conta->setDataVencimento($dados['data_vencimento']);
        $conta->setStatus($dados['status']);
        $conta->setObservacoes($dados['observacoes'] ?? '');
        $conta->setEmpresaId($_SESSION['empresa_id']);

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

    public function excluir($id)
    {
        $conta = $this->buscarPorId($id);

        if ($conta && $conta->getStatus() === 'recebida') {
            return ['sucesso' => false, 'mensagem' => "Não é possível excluir uma conta já recebida."];
        }

        if ($this->dao->excluir($id)) {
            return ['sucesso' => true, 'mensagem' => "Conta a receber excluída com sucesso!"];
        } else {
            return ['sucesso' => false, 'mensagem' => "Erro ao excluir conta a receber."];
        }
    }

    public function registrarRecebimento($dados)
    {
        $idConta = $dados['id_conta_receber'] ?? null;
        $valor = $dados['valor_recebido'] ?? 0;
        $data = $dados['data_recebimento'] ?? date('Y-m-d');

        if (!$idConta || $valor <= 0) {
            return ['sucesso' => false, 'mensagem' => "Dados de recebimento inválidos."];
        }

        $recebimento = new Recebimento();
        $recebimento->setContaReceberId($idConta);
        $recebimento->setValorRecebido($valor);
        $recebimento->setDataRecebimento($data);
        $recebimento->setEmpresaId($_SESSION['empresa_id']);

        if ($this->recebimentoDAO->salvar($recebimento)) {
            return ['sucesso' => true, 'mensagem' => "Recebimento registrado com sucesso!"];
        } else {
            return ['sucesso' => false, 'mensagem' => "Erro ao registrar recebimento."];
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
