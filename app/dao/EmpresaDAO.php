<?php

require_once __DIR__ . '/../models/Empresa.php';
require_once __DIR__ . '/Conexao.php';

class EmpresaDAO
{
    private $conexao;

    public function __construct()
    {
        $this->conexao = Conexao::getInstance();
    }

    public function salvar(Empresa $empresa)
    {
        try {
            $sql = "INSERT INTO rmg_empresa (codigo, razao_social, nome_fantasia, cnpj, telefone, email, ativa, observacoes) 
                    VALUES (:codigo, :razao_social, :nome_fantasia, :cnpj, :telefone, :email, :ativa, :observacoes)";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':codigo', $empresa->getCodigo());
            $stmt->bindValue(':razao_social', $empresa->getRazaoSocial());
            $stmt->bindValue(':nome_fantasia', $empresa->getNomeFantasia());
            $stmt->bindValue(':cnpj', $empresa->getCnpj());
            $stmt->bindValue(':telefone', $empresa->getTelefone());
            $stmt->bindValue(':email', $empresa->getEmail());
            $stmt->bindValue(':ativa', $empresa->getAtiva(), PDO::PARAM_BOOL);
            $stmt->bindValue(':observacoes', $empresa->getObservacoes());
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function atualizar(Empresa $empresa)
    {
        try {
            $sql = "UPDATE rmg_empresa SET codigo = :codigo, razao_social = :razao_social, nome_fantasia = :nome_fantasia, 
                    cnpj = :cnpj, telefone = :telefone, email = :email, ativa = :ativa, observacoes = :observacoes 
                    WHERE id_empresa = :id_empresa";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':codigo', $empresa->getCodigo());
            $stmt->bindValue(':razao_social', $empresa->getRazaoSocial());
            $stmt->bindValue(':nome_fantasia', $empresa->getNomeFantasia());
            $stmt->bindValue(':cnpj', $empresa->getCnpj());
            $stmt->bindValue(':telefone', $empresa->getTelefone());
            $stmt->bindValue(':email', $empresa->getEmail());
            $stmt->bindValue(':ativa', $empresa->getAtiva(), PDO::PARAM_BOOL);
            $stmt->bindValue(':observacoes', $empresa->getObservacoes());
            $stmt->bindValue(':id_empresa', $empresa->getIdEmpresa());
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function excluir($id)
    {
        try {
            $sql = "DELETE FROM rmg_empresa WHERE id_empresa = :id_empresa";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':id_empresa', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function listar()
    {
        try {
            $sql = "SELECT * FROM rmg_empresa ORDER BY razao_social";
            $stmt = $this->conexao->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $empresas = [];
            foreach ($result as $row) {
                $empresas[] = $this->hidratarEmpresa($row);
            }
            return $empresas;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function buscarPorId($id)
    {
        try {
            $sql = "SELECT * FROM rmg_empresa WHERE id_empresa = :id_empresa";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':id_empresa', $id);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return $this->hidratarEmpresa($row);
            }
            return null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function buscarPorCodigo($codigo)
    {
        try {
            $sql = "SELECT * FROM rmg_empresa WHERE codigo = :codigo";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':codigo', $codigo);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return $this->hidratarEmpresa($row);
            }
            return null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function temVinculos($id)
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM rmg_usuario WHERE empresa_id = :empresa_id";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':empresa_id', $id);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['total'] > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function contarEmpresas()
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM rmg_empresa";
            $stmt = $this->conexao->prepare($sql);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($row['total'] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function contarEmpresasAtivas()
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM rmg_empresa WHERE ativa = 1";
            $stmt = $this->conexao->prepare($sql);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($row['total'] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }

    private function hidratarEmpresa($data)
    {
        if (!$data) return null;

        $empresa = new Empresa();
        $empresa->setIdEmpresa($data['id_empresa']);
        $empresa->setCodigo($data['codigo']);
        $empresa->setRazaoSocial($data['razao_social']);
        $empresa->setNomeFantasia($data['nome_fantasia']);
        $empresa->setCnpj($data['cnpj']);
        $empresa->setTelefone($data['telefone']);
        $empresa->setEmail($data['email']);
        $empresa->setAtiva($data['ativa']);
        $empresa->setDataCriacao($data['data_criacao']);
        $empresa->setObservacoes($data['observacoes']);
        return $empresa;
    }
}
