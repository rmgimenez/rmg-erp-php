<?php

class ContaReceber
{
    private $id_conta_receber;
    private $empresa_id;
    private $cliente_id;
    private $nome_cliente; // Para exibição
    private $descricao;
    private $valor;
    private $data_vencimento;
    private $status;
    private $observacoes;

    public function getIdContaReceber()
    {
        return $this->id_conta_receber;
    }

    public function setIdContaReceber($id_conta_receber)
    {
        $this->id_conta_receber = $id_conta_receber;
    }

    public function getEmpresaId()
    {
        return $this->empresa_id;
    }

    public function setEmpresaId($empresa_id)
    {
        $this->empresa_id = $empresa_id;
    }

    public function getClienteId()
    {
        return $this->cliente_id;
    }

    public function setClienteId($cliente_id)
    {
        $this->cliente_id = $cliente_id;
    }

    public function getNomeCliente()
    {
        return $this->nome_cliente;
    }

    public function setNomeCliente($nome_cliente)
    {
        $this->nome_cliente = $nome_cliente;
    }

    public function getDescricao()
    {
        return $this->descricao;
    }

    public function setDescricao($descricao)
    {
        $this->descricao = $descricao;
    }

    public function getValor()
    {
        return $this->valor;
    }

    public function setValor($valor)
    {
        $this->valor = $valor;
    }

    public function getDataVencimento()
    {
        return $this->data_vencimento;
    }

    public function setDataVencimento($data_vencimento)
    {
        $this->data_vencimento = $data_vencimento;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
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
