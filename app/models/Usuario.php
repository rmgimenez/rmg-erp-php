<?php

class Usuario
{
    private $id_usuario;
    private $empresa_id;
    private $nome_empresa; // Para exibição
    private $nome;
    private $usuario;
    private $senha;
    private $tipo_usuario;
    private $ativo;
    private $data_criacao;

    public function getIdUsuario()
    {
        return $this->id_usuario;
    }

    public function setIdUsuario($id_usuario)
    {
        $this->id_usuario = $id_usuario;
    }

    public function getEmpresaId()
    {
        return $this->empresa_id;
    }

    public function setEmpresaId($empresa_id)
    {
        $this->empresa_id = $empresa_id;
    }

    public function getNomeEmpresa()
    {
        return $this->nome_empresa;
    }

    public function setNomeEmpresa($nome_empresa)
    {
        $this->nome_empresa = $nome_empresa;
    }

    public function getNome()
    {
        return $this->nome;
    }

    public function setNome($nome)
    {
        $this->nome = $nome;
    }

    public function getUsuario()
    {
        return $this->usuario;
    }

    public function setUsuario($usuario)
    {
        $this->usuario = $usuario;
    }

    public function getSenha()
    {
        return $this->senha;
    }

    public function setSenha($senha)
    {
        $this->senha = $senha;
    }

    public function getTipoUsuario()
    {
        return $this->tipo_usuario;
    }

    public function setTipoUsuario($tipo_usuario)
    {
        $this->tipo_usuario = $tipo_usuario;
    }

    public function getAtivo()
    {
        return $this->ativo;
    }

    public function setAtivo($ativo)
    {
        $this->ativo = $ativo;
    }

    public function getDataCriacao()
    {
        return $this->data_criacao;
    }

    public function setDataCriacao($data_criacao)
    {
        $this->data_criacao = $data_criacao;
    }

    /**
     * Verifica se o usuário é super administrador do SaaS
     */
    public function isSuperAdmin()
    {
        return $this->tipo_usuario === 'super_admin';
    }

    /**
     * Verifica se o usuário é gerente de empresa
     */
    public function isGerente()
    {
        return $this->tipo_usuario === 'gerente';
    }

    /**
     * Verifica se o usuário é operador
     */
    public function isOperador()
    {
        return $this->tipo_usuario === 'operador';
    }
}
