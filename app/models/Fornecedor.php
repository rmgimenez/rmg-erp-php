<?php

class Fornecedor {
    private $id_fornecedor;
    private $nome;
    private $cnpj;
    private $telefone;
    private $email;
    private $observacoes;

    public function getIdFornecedor() {
        return $this->id_fornecedor;
    }

    public function setIdFornecedor($id_fornecedor) {
        $this->id_fornecedor = $id_fornecedor;
    }

    public function getNome() {
        return $this->nome;
    }

    public function setNome($nome) {
        $this->nome = $nome;
    }

    public function getCnpj() {
        return $this->cnpj;
    }

    public function setCnpj($cnpj) {
        $this->cnpj = $cnpj;
    }

    public function getTelefone() {
        return $this->telefone;
    }

    public function setTelefone($telefone) {
        $this->telefone = $telefone;
    }

    public function getEmail() {
        return $this->email;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function getObservacoes() {
        return $this->observacoes;
    }

    public function setObservacoes($observacoes) {
        $this->observacoes = $observacoes;
    }
}