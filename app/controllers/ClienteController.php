<?php
require_once __DIR__ . '/../dao/ClienteDAO.php';
require_once __DIR__ . '/../models/Cliente.php';

class ClienteController
{
    private $clienteDAO;

    public function __construct()
    {
        $this->clienteDAO = new ClienteDAO();
    }

    public function listarClientes()
    {
        return $this->clienteDAO->listar();
    }

    public function salvar($dados)
    {
        $cliente = new Cliente();
        $cliente->setNome($dados['nome']);
        $cliente->setCpfCnpj($dados['cpf_cnpj']);
        $cliente->setTelefone($dados['telefone']);
        $cliente->setEmail($dados['email']);
        $cliente->setObservacoes($dados['observacoes']);

        if (!empty($dados['id_cliente'])) {
            $cliente->setIdCliente($dados['id_cliente']);
            if ($this->clienteDAO->atualizar($cliente)) {
                return ['sucesso' => true, 'mensagem' => 'Cliente atualizado com sucesso!'];
            }
        } else {
            if ($this->clienteDAO->salvar($cliente)) {
                return ['sucesso' => true, 'mensagem' => 'Cliente cadastrado com sucesso!'];
            }
        }
        return ['sucesso' => false, 'mensagem' => 'Erro ao salvar cliente.'];
    }

    public function excluir($id)
    {
        if ($this->clienteDAO->temVinculos($id)) {
            return ['sucesso' => false, 'mensagem' => 'Não é possível excluir o cliente pois existem contas a receber vinculadas.'];
        }

        if ($this->clienteDAO->excluir($id)) {
            return ['sucesso' => true, 'mensagem' => 'Cliente excluído com sucesso!'];
        }
        return ['sucesso' => false, 'mensagem' => 'Erro ao excluir cliente. Verifique se existem registros vinculados.'];
    }
}
