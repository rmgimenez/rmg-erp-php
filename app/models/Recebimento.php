<?php

class Recebimento {
    private $id_recebimento;
    private $conta_receber_id;
    private $data_recebimento;
    private $valor_recebido;

    public function getIdRecebimento() {
        return $this->id_recebimento;
    }

    public function setIdRecebimento($id_recebimento) {
        $this->id_recebimento = $id_recebimento;
    }

    public function getContaReceberId() {
        return $this->conta_receber_id;
    }

    public function setContaReceberId($conta_receber_id) {
        $this->conta_receber_id = $conta_receber_id;
    }

    public function getDataRecebimento() {
        return $this->data_recebimento;
    }

    public function setDataRecebimento($data_recebimento) {
        $this->data_recebimento = $data_recebimento;
    }

    public function getValorRecebido() {
        return $this->valor_recebido;
    }

    public function setValorRecebido($valor_recebido) {
        $this->valor_recebido = $valor_recebido;
    }
}
