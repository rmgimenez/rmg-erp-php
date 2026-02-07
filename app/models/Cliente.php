<?php

class Cliente {
    private $id_cliente;
    private $nome;
    private $cpf_cnpj;
    private $telefone;
    private $email;
    private $observacoes;

    public function getIdCliente() {
        return $this->id_cliente;
    }

    public function setIdCliente($id_cliente) {
        $this->id_cliente = $id_cliente;
    }

    public function getNome() {
        return $this->nome;
    }

    public function setNome($nome) {
        $this->nome = $nome;
    }

    public function getCpfCnpj() {
        return $this->cpf_cnpj;
    }

    public function setCpfCnpj($cpf_cnpj) {
        $this->cpf_cnpj = $cpf_cnpj;
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