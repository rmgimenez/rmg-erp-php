<?php

class Log
{
    private $id_log;
    private $empresa_id;
    private $usuario_id;
    private $usuario_nome;
    private $tabela;
    private $acao;
    private $registro_id;
    private $descricao;
    private $dados_anteriores;
    private $dados_novos;
    private $ip;
    private $data_hora;

    // Campos auxiliares para exibição
    private $empresa_codigo;
    private $empresa_nome;

    public function getIdLog()
    {
        return $this->id_log;
    }

    public function setIdLog($id_log)
    {
        $this->id_log = $id_log;
    }

    public function getEmpresaId()
    {
        return $this->empresa_id;
    }

    public function setEmpresaId($empresa_id)
    {
        $this->empresa_id = $empresa_id;
    }

    public function getUsuarioId()
    {
        return $this->usuario_id;
    }

    public function setUsuarioId($usuario_id)
    {
        $this->usuario_id = $usuario_id;
    }

    public function getUsuarioNome()
    {
        return $this->usuario_nome;
    }

    public function setUsuarioNome($usuario_nome)
    {
        $this->usuario_nome = $usuario_nome;
    }

    public function getTabela()
    {
        return $this->tabela;
    }

    public function setTabela($tabela)
    {
        $this->tabela = $tabela;
    }

    public function getAcao()
    {
        return $this->acao;
    }

    public function setAcao($acao)
    {
        $this->acao = $acao;
    }

    public function getRegistroId()
    {
        return $this->registro_id;
    }

    public function setRegistroId($registro_id)
    {
        $this->registro_id = $registro_id;
    }

    public function getDescricao()
    {
        return $this->descricao;
    }

    public function setDescricao($descricao)
    {
        $this->descricao = $descricao;
    }

    public function getDadosAnteriores()
    {
        return $this->dados_anteriores;
    }

    public function setDadosAnteriores($dados_anteriores)
    {
        $this->dados_anteriores = $dados_anteriores;
    }

    public function getDadosNovos()
    {
        return $this->dados_novos;
    }

    public function setDadosNovos($dados_novos)
    {
        $this->dados_novos = $dados_novos;
    }

    public function getIp()
    {
        return $this->ip;
    }

    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    public function getDataHora()
    {
        return $this->data_hora;
    }

    public function setDataHora($data_hora)
    {
        $this->data_hora = $data_hora;
    }

    public function getEmpresaCodigo()
    {
        return $this->empresa_codigo;
    }

    public function setEmpresaCodigo($empresa_codigo)
    {
        $this->empresa_codigo = $empresa_codigo;
    }

    public function getEmpresaNome()
    {
        return $this->empresa_nome;
    }

    public function setEmpresaNome($empresa_nome)
    {
        $this->empresa_nome = $empresa_nome;
    }
}
