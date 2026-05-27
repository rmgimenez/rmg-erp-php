# Planejamento do Módulo de Cardápio Semanal com IA

Este documento registra as especificações técnicas, decisões arquiteturais e o design do módulo de geração de cardápio semanal com inteligência artificial para o sistema da Cantina Sant'Anna.

---

## 1. Resumo do Entendimento

*   **O que está sendo construído:** Um módulo completo de planejamento, geração e impressão de cardápio semanal de Segunda a Sexta alimentado por Inteligência Artificial (via API do OpenRouter), com exibição elegante, cadastro de semanas e geração automatizada de listas de compras de ingredientes.
*   **Por que existe:** Para simplificar e acelerar o processo de planejamento de refeições saudáveis e o cálculo de insumos necessários com base no público estimado da cantina.
*   **Quem utilizará:** 
    *   **Administrador de TI (`admin`):** Configura a API Key do OpenRouter, seleciona o modelo de IA e define as diretrizes do prompt básico da cantina.
    *   **Gerente (`gerente`) e Operador (`operador`):** Cadastram semanas, disparam a geração da IA, revisam os textos gerados, editam o cardápio ou ingredientes se necessário e imprimem.
*   **Principais Restrições:**
    *   Banco de dados local SQLite (`cantina.sqlite`).
    *   Estilo de impressão otimizado para A4 (Paisagem para o Cardápio, Retrato para a Lista de Compras), sem cabeçalhos e rodapés do navegador.
    *   Arquitetura limpa utilizando Bootstrap 5 + Vanilla CSS e JS já existentes, sem frameworks externos pesados.

---

## 2. Premissas e Requisitos Não-Funcionais

*   **Performance:** A chamada de IA do OpenRouter pode levar de 5 a 15 segundos. O backend fará o processamento de forma síncrona com timeout de 35 segundos para evitar travamento do PHP, enquanto o frontend exibirá um loader elegante em tela inteira.
*   **Segurança:** A chave de API do OpenRouter e os prompts básicos ficam armazenados de forma privada na tabela `configuracoes` do SQLite e só podem ser manipulados por usuários autenticados com nível `admin`.
*   **Flexibilidade:** O público estimado da semana é definido por um campo livre de texto (ex: *"130 alunos do ensino médio, 30 funcionários"*), garantindo adaptabilidade para o prompt da IA.

---

## 3. Registro de Decisões (Decision Log)

### DEC-001: Estrutura do Banco de Dados
*   **Decisão:** Tabela Principal (`cardapios_semanais`) + Subtabela de Dias (`cardapios_dias`).
*   **Alternativas Consideradas:** Armazenar toda a semana em formato JSON em um campo único da tabela principal.
*   **Por que escolhido:** A separação em tabelas relacionais facilita buscas por dias específicos no futuro, relatórios e permite a edição granular em linha de um dia sem precisar reescrever ou corromper uma estrutura JSON complexa.

### DEC-002: Integração com Provedor de IA
*   **Decisão:** API do **OpenRouter** com endpoint compatível com a biblioteca/especificação OpenAI.
*   **Alternativas Consideradas:** Google Gemini API direta ou OpenAI API direta.
*   **Por que escolhido:** Flexibilidade extrema. O OpenRouter permite trocar o modelo de IA a qualquer momento (escolhendo entre Gemini 1.5/2.5 Flash, Llama 3, Claude, GPT-4o-mini) alterando apenas o nome do modelo nas configurações do sistema, sem necessidade de reescrever código PHP.

### DEC-003: Dias da Semana e Refeições
*   **Decisão:** Segunda a Sexta (5 dias úteis) com divisões fixas: Almoço (Refeição Principal) e Lanche.
*   **Alternativas Consideradas:** Segunda a Domingo (7 dias) ou entrada de texto livre para qualquer dia.
*   **Por que escolhido:** A cantina opera em dias úteis escolares. A estrutura de 5 colunas se encaixa perfeitamente e fica visualmente deslumbrante em uma folha A4 em orientação Paisagem.

### DEC-004: Controle de Acesso
*   **Decisão:** Edição de chaves e prompt restrita a `admin`. Geração, visualização, edição fina de cardápios e impressão liberadas para `gerente` e `operador`.
*   **Alternativas Consideradas:** Apenas `admin` gerenciar tudo ou liberação irrestrita.
*   **Por que escolhido:** Mantém a segurança do sistema ao não expor tokens de API e prompts do sistema para operadores normais, mas permite que o setor de cozinha e gerência realize o planejamento diário de cardápios.

---

## 4. Design Detalhado

### 4.1. Estrutura de Banco de Dados (SQLite)

```sql
-- Tabela de Cardápios Semanais
CREATE TABLE IF NOT EXISTS cardapios_semanais (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    data_inicio DATE NOT NULL,
    data_fim DATE NOT NULL,
    observacoes_geracao TEXT,
    lista_compras TEXT NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Itens Diários do Cardápio
CREATE TABLE IF NOT EXISTS cardapios_dias (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cardapio_semanal_id INTEGER NOT NULL,
    dia_semana TEXT CHECK(dia_semana IN ('segunda', 'terca', 'quarta', 'quinta', 'sexta')) NOT NULL,
    refeicao_principal TEXT NOT NULL,
    lanche TEXT NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cardapio_semanal_id) REFERENCES cardapios_semanais(id) ON DELETE CASCADE
);
```

### 4.2. Fluxo da API e prompt da IA
O backend enviará uma requisição HTTP POST para `https://openrouter.ai/api/v1/chat/completions` com o prompt estruturado. O prompt exigirá estritamente um JSON de saída no formato:

```json
{
  "segunda": { "refeicao_principal": "...", "lanche": "..." },
  "terca": { "refeicao_principal": "...", "lanche": "..." },
  "quarta": { "refeicao_principal": "...", "lanche": "..." },
  "quinta": { "refeicao_principal": "...", "lanche": "..." },
  "sexta": { "refeicao_principal": "...", "lanche": "..." },
  "lista_compras": "Texto formatado em Markdown da lista de compras categorizada..."
}
```

### 4.3. Interface do Usuário (`cardapios.php`)
1.  **Dashboard:** Lista as semanas cadastradas em cards premium translúcidos.
2.  **Visualizador Interativo:** Carrega o cardápio da semana selecionada em 5 colunas alinhadas lado a lado e uma aba ao lado com a lista de compras.
3.  **Edição rápida:** Inputs e botões de salvar direto na tela para pequenos ajustes após a geração.
4.  **Botões de Impressão:**
    *   **Paisagem A4 (Cardápio):** Formata a tabela em 5 colunas ocupando a folha inteira na horizontal.
    *   **Retrato A4 (Lista de Compras):** Formata a lista de compras como documento de texto limpo para o comprador.
