<?php
require_once __DIR__ . '/../models/Fornecedor.php';
require_once __DIR__ . '/Conexao.php';

class FornecedorDAO
{
    private $conexao;

    public function __construct()
    {
        $this->conexao = Conexao::getInstance();
    }

    public function salvar(Fornecedor $fornecedor)
    {
        try {
            $sql = "INSERT INTO rmg_fornecedor (nome, cnpj, telefone, email, observacoes) VALUES (:nome, :cnpj, :telefone, :email, :observacoes)";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':nome', $fornecedor->getNome());
            $stmt->bindValue(':cnpj', $fornecedor->getCnpj());
            $stmt->bindValue(':telefone', $fornecedor->getTelefone());
            $stmt->bindValue(':email', $fornecedor->getEmail());
            $stmt->bindValue(':observacoes', $fornecedor->getObservacoes());
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function atualizar(Fornecedor $fornecedor)
    {
        try {
            $sql = "UPDATE rmg_fornecedor SET nome = :nome, cnpj = :cnpj, telefone = :telefone, email = :email, observacoes = :observacoes WHERE id_fornecedor = :id_fornecedor";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':nome', $fornecedor->getNome());
            $stmt->bindValue(':cnpj', $fornecedor->getCnpj());
            $stmt->bindValue(':telefone', $fornecedor->getTelefone());
            $stmt->bindValue(':email', $fornecedor->getEmail());
            $stmt->bindValue(':observacoes', $fornecedor->getObservacoes());
            $stmt->bindValue(':id_fornecedor', $fornecedor->getIdFornecedor());
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function excluir($id)
    {
        try {
            $sql = "DELETE FROM rmg_fornecedor WHERE id_fornecedor = :id_fornecedor";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':id_fornecedor', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function listar()
    {
        try {
            $sql = "SELECT * FROM rmg_fornecedor ORDER BY nome";
            $stmt = $this->conexao->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $fornecedores = [];
            foreach ($result as $row) {
                $f = new Fornecedor();
                $f->setIdFornecedor($row['id_fornecedor']);
                $f->setNome($row['nome']);
                $f->setCnpj($row['cnpj']);
                $f->setTelefone($row['telefone']);
                $f->setEmail($row['email']);
                $f->setObservacoes($row['observacoes']);
                $fornecedores[] = $f;
            }
            return $fornecedores;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function buscarPorId($id)
    {
        try {
            $sql = "SELECT * FROM rmg_fornecedor WHERE id_fornecedor = :id_fornecedor";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':id_fornecedor', $id);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $f = new Fornecedor();
                $f->setIdFornecedor($row['id_fornecedor']);
                $f->setNome($row['nome']);
                $f->setCnpj($row['cnpj']);
                $f->setTelefone($row['telefone']);
                $f->setEmail($row['email']);
                $f->setObservacoes($row['observacoes']);
                return $f;
            }
            return null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function temVinculos($id)
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM rmg_conta_pagar WHERE fornecedor_id = :fornecedor_id";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':fornecedor_id', $id);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['total'] > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}
