<?php

class Fornecedor
{
    private $id_fornecedor;
    private $empresa_id;
    private $nome;
    private $cnpj;
    private $telefone;
    private $email;
    private $observacoes;

    public function getIdFornecedor()
    {
        return $this->id_fornecedor;
    }

    public function setIdFornecedor($id_fornecedor)
    {
        $this->id_fornecedor = $id_fornecedor;
    }

    public function getEmpresaId()
    {
        return $this->empresa_id;
    }

    public function setEmpresaId($empresa_id)
    {
        $this->empresa_id = $empresa_id;
    }

    public function getNome()
    {
        return $this->nome;
    }

    public function setNome($nome)
    {
        $this->nome = $nome;
    }

    public function getCnpj()
    {
        return $this->cnpj;
    }

    public function setCnpj($cnpj)
    {
        $this->cnpj = $cnpj;
    }

    public function getTelefone()
    {
        return $this->telefone;
    }

    public function setTelefone($telefone)
    {
        $this->telefone = $telefone;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function getObservacoes()
    {
        return $this->observacoes;
    }

    public function setObservacoes($observacoes)
    {
        $this->observacoes = $observacoes;
    }
}
