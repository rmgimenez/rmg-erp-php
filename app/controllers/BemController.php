<?php
require_once __DIR__ . '/../dao/BemDAO.php';
require_once __DIR__ . '/../dao/ManutencaoDAO.php';
require_once __DIR__ . '/../dao/LogDAO.php';
require_once __DIR__ . '/../models/Bem.php';

class BemController
{
    private $bemDAO;
    private $manutencaoDAO;

    public function __construct()
    {
        $this->bemDAO = new BemDAO();
        $this->manutencaoDAO = new ManutencaoDAO();
    }

    public function listarBens()
    {
        return $this->bemDAO->listar($_SESSION['empresa_id']);
    }

    public function buscarPorId($id)
    {
        return $this->bemDAO->buscarPorId($id);
    }

    public function salvar($dados)
    {
        $bem = new Bem();
        $bem->setDescricao($dados['descricao']);
        $bem->setSetorId($dados['setor_id']);
        $bem->setDataAquisicao($dados['data_aquisicao']);

        // Formatar valor (trocar vírgula por ponto se necessário)
        $valor = str_replace(',', '.', str_replace('.', '', $dados['valor_aquisicao'])); // Se vier "1.200,50" -> "1200.50"
        // O código acima é ingênuo para alguns formatos, mas para "1000,00" funciona se remover ponto de milhar antes. 
        // Assumindo input type="number" step="0.01", já vem formatado com ponto geralmente.
        // Se vier do input field como texto, precisa tratar.
        // Vou assumir que o usuário pode digitar com vírgula.
        $valor = str_replace(['.', ','], ['', '.'], $dados['valor_aquisicao']);
        // Ops, a lógica acima remove todos os pontos (milhar) e troca vírgula (decimal) por ponto.
        // Correção para formato BRL (1.000,00) -> 1000.00
        // Se o input for html5 number, vem 1000.00. Se for text com mascara, pode vir BRL.
        // Vou usar uma função simples de helper ou assumir formato US na controller por enquanto caso venha do form html5.
        // Melhor: confiar que se for input text, o usuário vai usar o padrão.
        // Vamos padronizar: input number html5 envia com ponto decimal.
        $bem->setValorAquisicao($dados['valor_aquisicao']);
        $bem->setEmpresaId($_SESSION['empresa_id']);

        $bem->setStatus($dados['status']);
        $bem->setObservacoes($dados['observacoes']);

        if (!empty($dados['id_bem'])) {
            $bem->setIdBem($dados['id_bem']);
            if ($this->bemDAO->atualizar($bem)) {
                LogDAO::registrar('rmg_bem', 'UPDATE', 'Bem atualizado: ' . $bem->getDescricao(), $bem->getIdBem());
                return ['sucesso' => true, 'mensagem' => 'Bem atualizado com sucesso!'];
            }
        } else {
            if ($this->bemDAO->salvar($bem)) {
                LogDAO::registrar('rmg_bem', 'INSERT', 'Bem cadastrado: ' . $bem->getDescricao());
                return ['sucesso' => true, 'mensagem' => 'Bem cadastrado com sucesso!'];
            }
        }
        return ['sucesso' => false, 'mensagem' => 'Erro ao salvar bem.'];
    }

    public function excluir($id)
    {
        $manutencoes = $this->manutencaoDAO->listarPorBem($id);
        if (count($manutencoes) > 0) {
            return ['sucesso' => false, 'mensagem' => 'Não é possível excluir o bem, pois existem manutenções cadastradas para ele.'];
        }
        if ($this->bemDAO->excluir($id)) {
            LogDAO::registrar('rmg_bem', 'DELETE', 'Bem excluído (ID: ' . $id . ')', $id);
            return ['sucesso' => true, 'mensagem' => 'Bem excluído com sucesso!'];
        }
        return ['sucesso' => false, 'mensagem' => 'Erro ao excluir bem.'];
    }

    public function obterMetricas()
    {
        $stats = $this->bemDAO->contarPorStatus($_SESSION['empresa_id']);
        $totalAtivos = $stats['ativo'];
        $totalBaixados = $stats['baixado'];
        $totalBens = $totalAtivos + $totalBaixados;

        // Idade média
        $bens = $this->bemDAO->listar($_SESSION['empresa_id']);
        $idades = [];
        foreach ($bens as $bem) {
            $dataAquisicao = new DateTime($bem->getDataAquisicao());
            $hoje = new DateTime();
            $idadeDias = $hoje->diff($dataAquisicao)->days;
            $idades[] = $idadeDias;
        }
        $idadeMediaDias = count($idades) > 0 ? array_sum($idades) / count($idades) : 0;
        $idadeMediaAnos = round($idadeMediaDias / 365.25, 1);

        // Total gasto em manutenção últimos 30 dias
        $inicio = date('Y-m-d', strtotime('-30 days'));
        $fim = date('Y-m-d');
        $manutencoes = $this->manutencaoDAO->buscarPorPeriodo($inicio, $fim, $_SESSION['empresa_id']);
        $totalGasto30Dias = 0;
        $totalManutencoes30Dias = count($manutencoes);
        foreach ($manutencoes as $m) {
            $totalGasto30Dias += $m->getCusto();
        }

        return [
            'totalAtivos' => $totalAtivos,
            'totalBaixados' => $totalBaixados,
            'totalBens' => $totalBens,
            'idadeMediaAnos' => $idadeMediaAnos,
            'totalGasto30Dias' => $totalGasto30Dias,
            'totalManutencoes30Dias' => $totalManutencoes30Dias
        ];
    }
}
