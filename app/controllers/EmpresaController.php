<?php
require_once __DIR__ . '/../dao/EmpresaDAO.php';
require_once __DIR__ . '/../dao/LogDAO.php';
require_once __DIR__ . '/../models/Empresa.php';

class EmpresaController
{
    private $empresaDAO;

    public function __construct()
    {
        $this->empresaDAO = new EmpresaDAO();
    }

    public function listarEmpresas()
    {
        return $this->empresaDAO->listar();
    }

    public function buscarPorId($id)
    {
        return $this->empresaDAO->buscarPorId($id);
    }

    public function buscarPorCodigo($codigo)
    {
        return $this->empresaDAO->buscarPorCodigo($codigo);
    }

    public function salvar($dados)
    {
        // Apenas super_admin pode gerenciar empresas
        if ($_SESSION['usuario_tipo'] !== 'super_admin') {
            return ['sucesso' => false, 'mensagem' => 'Acesso negado. Apenas o super administrador pode gerenciar empresas.'];
        }

        $empresa = new Empresa();
        $empresa->setCodigo(strtoupper(trim($dados['codigo'])));
        $empresa->setRazaoSocial($dados['razao_social']);
        $empresa->setNomeFantasia($dados['nome_fantasia'] ?? '');
        $empresa->setCnpj($dados['cnpj'] ?? '');
        $empresa->setTelefone($dados['telefone'] ?? '');
        $empresa->setEmail($dados['email'] ?? '');
        $empresa->setAtiva(isset($dados['ativa']) ? 1 : 0);
        $empresa->setObservacoes($dados['observacoes'] ?? '');

        if (!empty($dados['id_empresa'])) {
            // Edição
            $empresa->setIdEmpresa($dados['id_empresa']);

            if ($this->empresaDAO->atualizar($empresa)) {
                LogDAO::registrar('rmg_empresa', 'UPDATE', 'Empresa atualizada: ' . $empresa->getRazaoSocial() . ' (' . $empresa->getCodigo() . ')', $empresa->getIdEmpresa());
                return ['sucesso' => true, 'mensagem' => 'Empresa atualizada com sucesso!'];
            }
        } else {
            // Novo cadastro - verificar se código já existe
            $existente = $this->empresaDAO->buscarPorCodigo($empresa->getCodigo());
            if ($existente) {
                return ['sucesso' => false, 'mensagem' => 'Já existe uma empresa com este código.'];
            }

            if ($this->empresaDAO->salvar($empresa)) {
                LogDAO::registrar('rmg_empresa', 'INSERT', 'Empresa cadastrada: ' . $empresa->getRazaoSocial() . ' (' . $empresa->getCodigo() . ')');
                return ['sucesso' => true, 'mensagem' => 'Empresa cadastrada com sucesso!'];
            }
        }

        return ['sucesso' => false, 'mensagem' => 'Erro ao salvar empresa.'];
    }

    public function excluir($id)
    {
        if ($_SESSION['usuario_tipo'] !== 'super_admin') {
            return ['sucesso' => false, 'mensagem' => 'Acesso negado.'];
        }

        if ($this->empresaDAO->temVinculos($id)) {
            return ['sucesso' => false, 'mensagem' => 'Não é possível excluir a empresa pois existem usuários vinculados a ela.'];
        }

        if ($this->empresaDAO->excluir($id)) {
            LogDAO::registrar('rmg_empresa', 'DELETE', 'Empresa excluída (ID: ' . $id . ')', $id);
            return ['sucesso' => true, 'mensagem' => 'Empresa excluída com sucesso!'];
        }

        return ['sucesso' => false, 'mensagem' => 'Erro ao excluir empresa.'];
    }

    public function obterEstatisticas()
    {
        return [
            'total' => $this->empresaDAO->contarEmpresas(),
            'ativas' => $this->empresaDAO->contarEmpresasAtivas()
        ];
    }
}
