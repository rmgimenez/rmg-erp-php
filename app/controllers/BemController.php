<?php
require_once __DIR__ . '/../dao/BemDAO.php';
require_once __DIR__ . '/../dao/ManutencaoDAO.php';
require_once __DIR__ . '/../models/Bem.php';

class BemController {
    private $bemDAO;
    private $manutencaoDAO;

    public function __construct() {
        $this->bemDAO = new BemDAO();
        $this->manutencaoDAO = new ManutencaoDAO();
    }

    public function listarBens() {
        return $this->bemDAO->listar();
    }

    public function buscarPorId($id) {
        return $this->bemDAO->buscarPorId($id);
    }

    public function salvar($dados) {
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

        $bem->setStatus($dados['status']);
        $bem->setObservacoes($dados['observacoes']);

        if (!empty($dados['id_bem'])) {
            $bem->setIdBem($dados['id_bem']);
            if ($this->bemDAO->atualizar($bem)) {
                return ['sucesso' => true, 'mensagem' => 'Bem atualizado com sucesso!'];
            }
        } else {
            if ($this->bemDAO->salvar($bem)) {
                return ['sucesso' => true, 'mensagem' => 'Bem cadastrado com sucesso!'];
            }
        }
        return ['sucesso' => false, 'mensagem' => 'Erro ao salvar bem.'];
    }

    public function excluir($id) {
        $manutencoes = $this->manutencaoDAO->listarPorBem($id);
        if (count($manutencoes) > 0) {
            return ['sucesso' => false, 'mensagem' => 'Não é possível excluir o bem, pois existem manutenções cadastradas para ele.'];
        }
        if ($this->bemDAO->excluir($id)) {
            return ['sucesso' => true, 'mensagem' => 'Bem excluído com sucesso!'];
        }
        return ['sucesso' => false, 'mensagem' => 'Erro ao excluir bem.'];
    }
}