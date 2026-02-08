<?php
require_once __DIR__ . '/../models/Cliente.php';
require_once __DIR__ . '/Conexao.php';

class ClienteDAO
{
    private $conexao;

    public function __construct()
    {
        $this->conexao = Conexao::getInstance();
    }

    public function salvar(Cliente $cliente)
    {
        try {
            $sql = "INSERT INTO rmg_cliente (nome, cpf_cnpj, telefone, email, observacoes) VALUES (:nome, :cpf_cnpj, :telefone, :email, :observacoes)";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':nome', $cliente->getNome());
            $stmt->bindValue(':cpf_cnpj', $cliente->getCpfCnpj());
            $stmt->bindValue(':telefone', $cliente->getTelefone());
            $stmt->bindValue(':email', $cliente->getEmail());
            $stmt->bindValue(':observacoes', $cliente->getObservacoes());
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function atualizar(Cliente $cliente)
    {
        try {
            $sql = "UPDATE rmg_cliente SET nome = :nome, cpf_cnpj = :cpf_cnpj, telefone = :telefone, email = :email, observacoes = :observacoes WHERE id_cliente = :id_cliente";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':nome', $cliente->getNome());
            $stmt->bindValue(':cpf_cnpj', $cliente->getCpfCnpj());
            $stmt->bindValue(':telefone', $cliente->getTelefone());
            $stmt->bindValue(':email', $cliente->getEmail());
            $stmt->bindValue(':observacoes', $cliente->getObservacoes());
            $stmt->bindValue(':id_cliente', $cliente->getIdCliente());
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function excluir($id)
    {
        try {
            $sql = "DELETE FROM rmg_cliente WHERE id_cliente = :id_cliente";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':id_cliente', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function listar()
    {
        try {
            $sql = "SELECT * FROM rmg_cliente ORDER BY nome";
            $stmt = $this->conexao->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $clientes = [];
            foreach ($result as $row) {
                $c = new Cliente();
                $c->setIdCliente($row['id_cliente']);
                $c->setNome($row['nome']);
                $c->setCpfCnpj($row['cpf_cnpj']);
                $c->setTelefone($row['telefone']);
                $c->setEmail($row['email']);
                $c->setObservacoes($row['observacoes']);
                $clientes[] = $c;
            }
            return $clientes;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function buscarPorId($id)
    {
        try {
            $sql = "SELECT * FROM rmg_cliente WHERE id_cliente = :id_cliente";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':id_cliente', $id);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $c = new Cliente();
                $c->setIdCliente($row['id_cliente']);
                $c->setNome($row['nome']);
                $c->setCpfCnpj($row['cpf_cnpj']);
                $c->setTelefone($row['telefone']);
                $c->setEmail($row['email']);
                $c->setObservacoes($row['observacoes']);
                return $c;
            }
            return null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function temVinculos($id)
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM rmg_conta_receber WHERE cliente_id = :cliente_id";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':cliente_id', $id);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['total'] > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}
