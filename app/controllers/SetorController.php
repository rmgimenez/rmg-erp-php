<?php
require_once __DIR__ . '/../dao/SetorDAO.php';
require_once __DIR__ . '/../models/Setor.php';

class SetorController
{
    private $setorDAO;

    public function __construct()
    {
        $this->setorDAO = new SetorDAO();
    }

    public function listarSetores()
    {
        return $this->setorDAO->listar($_SESSION['empresa_id']);
    }

    public function buscarPorId($id)
    {
        return $this->setorDAO->buscarPorId($id);
    }

    public function salvar($dados)
    {
        $setor = new Setor();
        $setor->setNome($dados['nome']);
        $setor->setDescricao($dados['descricao']);
        $setor->setEmpresaId($_SESSION['empresa_id']);

        if (!empty($dados['id_setor'])) {
            $setor->setIdSetor($dados['id_setor']);
            if ($this->setorDAO->atualizar($setor)) {
                return ['sucesso' => true, 'mensagem' => 'Setor atualizado com sucesso!'];
            }
        } else {
            if ($this->setorDAO->salvar($setor)) {
                return ['sucesso' => true, 'mensagem' => 'Setor cadastrado com sucesso!'];
            }
        }
        return ['sucesso' => false, 'mensagem' => 'Erro ao salvar setor.'];
    }

    public function excluir($id)
    {
        if ($this->setorDAO->temVinculos($id)) {
            return ['sucesso' => false, 'mensagem' => 'Não é possível excluir o setor pois existem bens vinculados a ele.'];
        }

        if ($this->setorDAO->excluir($id)) {
            return ['sucesso' => true, 'mensagem' => 'Setor excluído com sucesso!'];
        }
        return ['sucesso' => false, 'mensagem' => 'Erro ao excluir setor. Verifique se não há bens vinculados.'];
    }
}
