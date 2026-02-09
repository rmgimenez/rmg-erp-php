<?php

class ContaPagar
{
    private $id_conta_pagar;
    private $empresa_id;
    private $fornecedor_id;
    private $nome_fornecedor; // Para exibição
    private $descricao;
    private $valor;
    private $data_vencimento;
    private $status;
    private $observacoes;

    public function getIdContaPagar()
    {
        return $this->id_conta_pagar;
    }

    public function setIdContaPagar($id_conta_pagar)
    {
        $this->id_conta_pagar = $id_conta_pagar;
    }

    public function getEmpresaId()
    {
        return $this->empresa_id;
    }

    public function setEmpresaId($empresa_id)
    {
        $this->empresa_id = $empresa_id;
    }

    public function getFornecedorId()
    {
        return $this->fornecedor_id;
    }

    public function setFornecedorId($fornecedor_id)
    {
        $this->fornecedor_id = $fornecedor_id;
    }

    public function getNomeFornecedor()
    {
        return $this->nome_fornecedor;
    }

    public function setNomeFornecedor($nome_fornecedor)
    {
        $this->nome_fornecedor = $nome_fornecedor;
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
