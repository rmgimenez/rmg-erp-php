<?php

class Setor {
    private $id_setor;
    private $nome;
    private $descricao;

    public function getIdSetor() {
        return $this->id_setor;
    }

    public function setIdSetor($id_setor) {
        $this->id_setor = $id_setor;
    }

    public function getNome() {
        return $this->nome;
    }

    public function setNome($nome) {
        $this->nome = $nome;
    }

    public function getDescricao() {
        return $this->descricao;
    }

    public function setDescricao($descricao) {
        $this->descricao = $descricao;
    }
}