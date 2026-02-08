<?php
require_once __DIR__ . '/../dao/FornecedorDAO.php';
require_once __DIR__ . '/../models/Fornecedor.php';

class FornecedorController
{
    private $fornecedorDAO;

    public function __construct()
    {
        $this->fornecedorDAO = new FornecedorDAO();
    }

    public function listarFornecedores()
    {
        return $this->fornecedorDAO->listar();
    }

    public function salvar($dados)
    {
        $fornecedor = new Fornecedor();
        $fornecedor->setNome($dados['nome']);
        $fornecedor->setCnpj($dados['cnpj']);
        $fornecedor->setTelefone($dados['telefone']);
        $fornecedor->setEmail($dados['email']);
        $fornecedor->setObservacoes($dados['observacoes']);

        if (!empty($dados['id_fornecedor'])) {
            $fornecedor->setIdFornecedor($dados['id_fornecedor']);
            if ($this->fornecedorDAO->atualizar($fornecedor)) {
                return ['sucesso' => true, 'mensagem' => 'Fornecedor atualizado com sucesso!'];
            }
        } else {
            if ($this->fornecedorDAO->salvar($fornecedor)) {
                return ['sucesso' => true, 'mensagem' => 'Fornecedor cadastrado com sucesso!'];
            }
        }
        return ['sucesso' => false, 'mensagem' => 'Erro ao salvar fornecedor.'];
    }

    public function excluir($id)
    {
        if ($this->fornecedorDAO->temVinculos($id)) {
            return ['sucesso' => false, 'mensagem' => 'Não é possível excluir o fornecedor pois existem contas a pagar vinculadas.'];
        }

        if ($this->fornecedorDAO->excluir($id)) {
            return ['sucesso' => true, 'mensagem' => 'Fornecedor excluído com sucesso!'];
        }
        return ['sucesso' => false, 'mensagem' => 'Erro ao excluir fornecedor. Verifique se existem registros vinculados.'];
    }
}
