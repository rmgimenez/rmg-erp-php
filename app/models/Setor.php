<?php

class Setor
{
    private $id_setor;
    private $empresa_id;
    private $nome;
    private $descricao;

    public function getIdSetor()
    {
        return $this->id_setor;
    }

    public function setIdSetor($id_setor)
    {
        $this->id_setor = $id_setor;
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

    public function getDescricao()
    {
        return $this->descricao;
    }

    public function setDescricao($descricao)
    {
        $this->descricao = $descricao;
    }
}
