<?php
require_once __DIR__ . '/../dao/ManutencaoDAO.php';
require_once __DIR__ . '/../models/Manutencao.php';

class ManutencaoController {
    private $manutencaoDAO;

    public function __construct() {
        $this->manutencaoDAO = new ManutencaoDAO();
    }

    public function listarPorBem($bemId) {
        return $this->manutencaoDAO->listarPorBem($bemId);
    }
    
    public function listarTodas() {
        return $this->manutencaoDAO->listarTodas();
    }

    public function salvar($dados) {
        $m = new Manutencao();
        $m->setBemId($dados['bem_id']);
        $m->setDataManutencao($dados['data_manutencao']);
        $m->setDescricao($dados['descricao']);
        $m->setCusto($dados['custo']);
        $m->setObservacoes($dados['observacoes']);

        if (!empty($dados['id_manutencao'])) {
            $m->setIdManutencao($dados['id_manutencao']);
            if ($this->manutencaoDAO->atualizar($m)) {
                return ['sucesso' => true, 'mensagem' => 'Manutenção atualizada com sucesso!'];
            }
        } else {
            if ($this->manutencaoDAO->salvar($m)) {
                return ['sucesso' => true, 'mensagem' => 'Manutenção registrada com sucesso!'];
            }
        }
        return ['sucesso' => false, 'mensagem' => 'Erro ao salvar manutenção.'];
    }

    public function excluir($id) {
        if ($this->manutencaoDAO->excluir($id)) {
            return ['sucesso' => true, 'mensagem' => 'Manutenção excluída com sucesso!'];
        }
        return ['sucesso' => false, 'mensagem' => 'Erro ao excluir manutenção.'];
    }
}