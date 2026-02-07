<?php

class Manutencao {
    private $id_manutencao;
    private $bem_id;
    private $descricao_bem; // Para exibição
    private $data_manutencao;
    private $descricao;
    private $custo;
    private $observacoes;

    public function getIdManutencao() {
        return $this->id_manutencao;
    }

    public function setIdManutencao($id_manutencao) {
        $this->id_manutencao = $id_manutencao;
    }

    public function getBemId() {
        return $this->bem_id;
    }

    public function setBemId($bem_id) {
        $this->bem_id = $bem_id;
    }

    public function getDescricaoBem() {
        return $this->descricao_bem;
    }

    public function setDescricaoBem($descricao_bem) {
        $this->descricao_bem = $descricao_bem;
    }

    public function getDataManutencao() {
        return $this->data_manutencao;
    }

    public function setDataManutencao($data_manutencao) {
        $this->data_manutencao = $data_manutencao;
    }

    public function getDescricao() {
        return $this->descricao;
    }

    public function setDescricao($descricao) {
        $this->descricao = $descricao;
    }

    public function getCusto() {
        return $this->custo;
    }

    public function setCusto($custo) {
        $this->custo = $custo;
    }

    public function getObservacoes() {
        return $this->observacoes;
    }

    public function setObservacoes($observacoes) {
        $this->observacoes = $observacoes;
    }
}