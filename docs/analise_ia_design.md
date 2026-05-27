# Design: Análise Financeira com IA

## Sumário

Página dedicada `analise_ia.php` com 3 abas de inteligência artificial sobre os dados financeiros do sistema. Gerente e administrador podem gerar resumos executivos, projeções de tendências e fazer perguntas em linguagem natural.

## Entendimento

- **O que está sendo construído**: Integração de IA no módulo de relatórios financeiros com página dedicada, 3 abas (Resumo Executivo, Tendências/Previsões, Perguntas)
- **Por que existe**: Gerente e administrador querem insights rápidos em linguagem natural sem analisar manualmente tabelas e KPIs
- **Para quem**: Papéis `gerente` e `admin`
- **Como funciona hoje**: Relatórios têm filtros, KPIs numéricos, tabela detalhada, resumo por categoria/fornecedor, exportação PDF — IA adicionará camada narrativa sobre os mesmos dados
- **Mecanismo**: Requisição síncrona ao OpenRouter (modelo configurável em `admin.php`), sob demanda com loader, dados reais enviados

## Premissas

1. Geração sob demanda (botão "Gerar"), nunca automática
2. Tempo de resposta aceitável: 5–15 segundos com loader
3. Dados reais enviados ao OpenRouter sem anonimização
4. Prompt recebe os dados consolidados do filtro ativo (ou todo histórico)
5. Chave OpenRouter reutilizada (mesma do cardápio IA)
6. Admin pode desabilitar a funcionalidade via config

## Estrutura da Página

```
analise_ia.php
├── Header: "Análise Inteligente" + link config admin.php
├── 3 Abas (Bootstrap tabs)
│   ├── Resumo Executivo
│   ├── Tendências e Previsões
│   └── Perguntas
└── Loader overlay (mesmo estilo do cardápio IA)
```

## Aba 1 — Resumo Executivo

- Botão "Gerar Resumo com IA"
- Envia para `analise_ia_action.php?acao=resumo_executivo`
- Dados: receitas/despesas por mês (12 meses), top 5 categorias, top 5 fornecedores, saldo líquido, contas vencidas
- Retorno: texto 3–5 parágrafos
- Exibição: card estilizado com ícone de IA
- Botão "Regenerar"
- Não persistido no banco

## Aba 2 — Tendências e Previsões

- Botão "Gerar Projeção"
- Envia para `analise_ia_action.php?acao=tendencia`
- Dados: série mensal 12 meses, sazonalidade, contas recorrentes
- Retorno estruturado: projeção 3 meses, alertas sazonais, recomendações
- Exibição: 3 cards (Projeção, Alertas, Recomendações)
- Disclaimer obrigatório: *"Análise gerada por IA com base em dados históricos. Não substitui aconselhamento profissional."*
- Botão "Copiar análise"

## Aba 3 — Perguntas

- Campo de texto livre
- Botão "Perguntar"
- Envia para `analise_ia_action.php?acao=pergunta`
- Dados: pergunta + contexto completo (mesmo do resumo)
- Histórico da conversa: `$_SESSION['ia_chat_history']` — últimas 10 trocas
- Botão "Limpar Conversa"
- Fallback: "Não foi possível responder com os dados disponíveis. Tente reformular."

## Controller

Novo arquivo `analise_ia_action.php` (mesmo padrão de `cardapio_action.php`).

Ações:
- `resumo_executivo` — texto contínuo
- `tendencia` — texto com seções
- `pergunta` — resposta + atualiza histórico na sessão

## Arquivos a criar/modificar

### Criar
- `analise_ia.php` — página principal com 3 abas
- `analise_ia_action.php` — controller AJAX
- `assets/js/analise_ia.js` — JS específico da página

### Modificar
- `src/includes/sidebar.php` — adicionar link (seção "Inteligência" ou dentro de "Gestão")
- `src/includes/mobile_header.php` — mesmo link
- `src/Database.php` — nada (reutiliza modelos existentes)
- `src/Auth.php` — nada (usa `restrictTo` existente)

## Riscos

1. Previsões financeiras com IA são imprecisas — mitigado por disclaimer
2. Conversas longas consomem tokens — mitigado por limite de 10 trocas
3. IA pode não seguir formato estruturado na aba Perguntas — fallback textual

## Decision Log

| Decisão | Alternativas | Motivo |
|---|---|---|
| Abordagem C (página dedicada) | A (inline), B (JSON) | Múltiplos casos de uso exigem página própria |
| 3 abas separadas | Aba única | Cobre resumo + tendência + perguntas |
| Controller separado | Reutilizar cardapio_action.php | Dados e ações diferentes |
| Histórico na sessão (10) | Banco, localStorage | Volátil, privado, limita custo |
| Disclaimer em previsões | Nenhum | Risco legal |
| Dados reais sem anonimizar | Dados agregados | Usuário autorizou |
