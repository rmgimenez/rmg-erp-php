<?php
require_once __DIR__ . '/../dao/ManutencaoDAO.php';
require_once __DIR__ . '/../dao/LogDAO.php';
require_once __DIR__ . '/../models/Manutencao.php';

class ManutencaoController
{
    private $manutencaoDAO;

    public function __construct()
    {
        $this->manutencaoDAO = new ManutencaoDAO();
    }

    public function listarPorBem($bemId)
    {
        return $this->manutencaoDAO->listarPorBem($bemId);
    }

    public function listarTodas()
    {
        return $this->manutencaoDAO->listarTodas($_SESSION['empresa_id']);
    }

    public function salvar($dados)
    {
        $m = new Manutencao();
        $m->setBemId($dados['bem_id']);
        $m->setDataManutencao($dados['data_manutencao']);
        $m->setDescricao($dados['descricao']);
        $m->setCusto($dados['custo']);
        $m->setObservacoes($dados['observacoes']);
        $m->setEmpresaId($_SESSION['empresa_id']);

        if (!empty($dados['id_manutencao'])) {
            $m->setIdManutencao($dados['id_manutencao']);
            if ($this->manutencaoDAO->atualizar($m)) {
                LogDAO::registrar('rmg_manutencao', 'UPDATE', 'Manutenção atualizada (Bem ID: ' . $m->getBemId() . ', Custo: R$ ' . number_format($m->getCusto(), 2, ',', '.') . ')', $m->getIdManutencao());
                return ['sucesso' => true, 'mensagem' => 'Manutenção atualizada com sucesso!'];
            }
        } else {
            if ($this->manutencaoDAO->salvar($m)) {
                LogDAO::registrar('rmg_manutencao', 'INSERT', 'Manutenção registrada (Bem ID: ' . $m->getBemId() . ', Custo: R$ ' . number_format($m->getCusto(), 2, ',', '.') . ')');
                return ['sucesso' => true, 'mensagem' => 'Manutenção registrada com sucesso!'];
            }
        }
        return ['sucesso' => false, 'mensagem' => 'Erro ao salvar manutenção.'];
    }

    public function excluir($id)
    {
        if ($this->manutencaoDAO->excluir($id)) {
            LogDAO::registrar('rmg_manutencao', 'DELETE', 'Manutenção excluída (ID: ' . $id . ')', $id);
            return ['sucesso' => true, 'mensagem' => 'Manutenção excluída com sucesso!'];
        }
        return ['sucesso' => false, 'mensagem' => 'Erro ao excluir manutenção.'];
    }
}
