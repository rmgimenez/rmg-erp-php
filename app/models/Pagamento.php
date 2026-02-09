<?php

class Pagamento
{
    private $id_pagamento;
    private $empresa_id;
    private $conta_pagar_id;
    private $data_pagamento;
    private $valor_pago;

    public function getIdPagamento()
    {
        return $this->id_pagamento;
    }

    public function setIdPagamento($id_pagamento)
    {
        $this->id_pagamento = $id_pagamento;
    }

    public function getEmpresaId()
    {
        return $this->empresa_id;
    }

    public function setEmpresaId($empresa_id)
    {
        $this->empresa_id = $empresa_id;
    }

    public function getContaPagarId()
    {
        return $this->conta_pagar_id;
    }

    public function setContaPagarId($conta_pagar_id)
    {
        $this->conta_pagar_id = $conta_pagar_id;
    }

    public function getDataPagamento()
    {
        return $this->data_pagamento;
    }

    public function setDataPagamento($data_pagamento)
    {
        $this->data_pagamento = $data_pagamento;
    }

    public function getValorPago()
    {
        return $this->valor_pago;
    }

    public function setValorPago($valor_pago)
    {
        $this->valor_pago = $valor_pago;
    }
}
