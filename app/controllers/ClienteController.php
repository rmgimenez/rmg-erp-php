<?php
require_once __DIR__ . '/../dao/ClienteDAO.php';
require_once __DIR__ . '/../dao/LogDAO.php';
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
        return $this->clienteDAO->listar($_SESSION['empresa_id']);
    }

    public function salvar($dados)
    {
        $cliente = new Cliente();
        $cliente->setNome($dados['nome']);
        $cliente->setCpfCnpj($dados['cpf_cnpj']);
        $cliente->setTelefone($dados['telefone']);
        $cliente->setEmail($dados['email']);
        $cliente->setObservacoes($dados['observacoes']);
        $cliente->setEmpresaId($_SESSION['empresa_id']);

        if (!empty($dados['id_cliente'])) {
            $cliente->setIdCliente($dados['id_cliente']);
            if ($this->clienteDAO->atualizar($cliente)) {
                LogDAO::registrar('rmg_cliente', 'UPDATE', 'Cliente atualizado: ' . $cliente->getNome(), $cliente->getIdCliente());
                return ['sucesso' => true, 'mensagem' => 'Cliente atualizado com sucesso!'];
            }
        } else {
            if ($this->clienteDAO->salvar($cliente)) {
                LogDAO::registrar('rmg_cliente', 'INSERT', 'Cliente cadastrado: ' . $cliente->getNome());
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
            LogDAO::registrar('rmg_cliente', 'DELETE', 'Cliente excluído (ID: ' . $id . ')', $id);
            return ['sucesso' => true, 'mensagem' => 'Cliente excluído com sucesso!'];
        }
        return ['sucesso' => false, 'mensagem' => 'Erro ao excluir cliente. Verifique se existem registros vinculados.'];
    }
}
