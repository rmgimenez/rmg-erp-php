<?php

class Empresa
{
    private $id_empresa;
    private $codigo;
    private $razao_social;
    private $nome_fantasia;
    private $cnpj;
    private $telefone;
    private $email;
    private $ativa;
    private $data_criacao;
    private $observacoes;

    public function getIdEmpresa()
    {
        return $this->id_empresa;
    }

    public function setIdEmpresa($id_empresa)
    {
        $this->id_empresa = $id_empresa;
    }

    public function getCodigo()
    {
        return $this->codigo;
    }

    public function setCodigo($codigo)
    {
        $this->codigo = $codigo;
    }

    public function getRazaoSocial()
    {
        return $this->razao_social;
    }

    public function setRazaoSocial($razao_social)
    {
        $this->razao_social = $razao_social;
    }

    public function getNomeFantasia()
    {
        return $this->nome_fantasia;
    }

    public function setNomeFantasia($nome_fantasia)
    {
        $this->nome_fantasia = $nome_fantasia;
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

    public function getAtiva()
    {
        return $this->ativa;
    }

    public function setAtiva($ativa)
    {
        $this->ativa = $ativa;
    }

    public function getDataCriacao()
    {
        return $this->data_criacao;
    }

    public function setDataCriacao($data_criacao)
    {
        $this->data_criacao = $data_criacao;
    }

    public function getObservacoes()
    {
        return $this->observacoes;
    }

    public function setObservacoes($observacoes)
    {
        $this->observacoes = $observacoes;
    }

    /**
     * Retorna o nome de exibição da empresa (nome fantasia ou razão social)
     */
    public function getNomeExibicao()
    {
        return !empty($this->nome_fantasia) ? $this->nome_fantasia : $this->razao_social;
    }
}
