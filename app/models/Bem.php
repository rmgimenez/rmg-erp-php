<?php

class Bem {
    private $id_bem;
    private $descricao;
    private $setor_id;
    private $nome_setor; // Para exibição
    private $data_aquisicao;
    private $valor_aquisicao;
    private $status;
    private $observacoes;
    private $total_manutencao; // Novo campo

    public function getIdBem() {
        return $this->id_bem;
    }

    public function setIdBem($id_bem) {
        $this->id_bem = $id_bem;
    }

    public function getDescricao() {
        return $this->descricao;
    }

    public function setDescricao($descricao) {
        $this->descricao = $descricao;
    }

    public function getSetorId() {
        return $this->setor_id;
    }

    public function setSetorId($setor_id) {
        $this->setor_id = $setor_id;
    }

    public function getNomeSetor() {
        return $this->nome_setor;
    }

    public function setNomeSetor($nome_setor) {
        $this->nome_setor = $nome_setor;
    }

    public function getDataAquisicao() {
        return $this->data_aquisicao;
    }

    public function setDataAquisicao($data_aquisicao) {
        $this->data_aquisicao = $data_aquisicao;
    }

    public function getValorAquisicao() {
        return $this->valor_aquisicao;
    }

    public function setValorAquisicao($valor_aquisicao) {
        $this->valor_aquisicao = $valor_aquisicao;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setStatus($status) {
        $this->status = $status;
    }

    public function getObservacoes() {
        return $this->observacoes;
    }

    public function setObservacoes($observacoes) {
        $this->observacoes = $observacoes;
    }

    public function getTotalManutencao() {
        return $this->total_manutencao;
    }

    public function setTotalManutencao($total_manutencao) {
        $this->total_manutencao = $total_manutencao;
    }
}