# SISTEMA DE GESTÃO FINANCEIRA E CONTROLE DE BENS

---

## 1. VISÃO GERAL

O Sistema de Gestão Financeira e Controle de Bens tem como objetivo centralizar e organizar o controle financeiro e patrimonial de uma empresa, permitindo a gestão eficiente de contas a pagar, contas a receber, bens/equipamentos e suas manutenções.

O público-alvo do sistema são pequenas e médias empresas que necessitam de controle financeiro detalhado, histórico de manutenções de equipamentos e relatórios gerenciais para tomada de decisão, como avaliar se um bem deve ser reparado novamente ou substituído por outro.

Os principais objetivos do sistema são:

- Controlar fluxo de caixa (pagamentos e recebimentos)
- Gerenciar bens e seus custos de manutenção
- Fornecer relatórios financeiros e patrimoniais confiáveis
- Facilitar a visualização de compromissos financeiros futuros
- Garantir controle de acesso por tipo de usuário

---

## 2. ESCOPO DO SISTEMA

### Incluído no escopo:

- Autenticação de usuários
- Cadastro e gerenciamento de usuários
- Controle de permissões por tipo de usuário
- Cadastro de setores
- Cadastro de fornecedores
- Cadastro de clientes
- Cadastro e controle de bens/equipamentos
- Registro e histórico de manutenções dos bens
- Contas a pagar
- Contas a receber
- Registro de pagamentos
- Registro de recebimentos
- Calendário financeiro (contas a pagar e receber)
- Relatórios financeiros e patrimoniais por período

### Fora do escopo:

- Emissão de notas fiscais
- Integração com bancos ou sistemas de pagamento online
- Aplicativo mobile
- Controle contábil/fiscal avançado

---

## 3. ARQUITETURA GERAL

### Frontend

- HTML5
- CSS3
- Bootstrap para layout responsivo
- JavaScript com jQuery
- DataTables para listagens
- Select2 para campos de seleção avançada
- FontAwesome para ícones

### Backend

- PHP puro (sem framework)
- Arquitetura em camadas
- Uso de Programação Orientada a Objetos (POO)
- Separação clara entre regras de negócio, acesso a dados e apresentação

### Banco de Dados

- MySQL
- Scripts SQL organizados em arquivos específicos

**Obrigatório:**

- Todo o script de criação do banco deve estar no arquivo:  
  `banco-dados.sql`
- Todo o script de exclusão (DROP de tabelas, views, etc.) deve estar no arquivo:  
  `banco-dados-drop.sql`

### Autenticação

- Autenticação baseada em sessão PHP
- Senhas armazenadas com hash seguro
- Controle de acesso baseado em tipo de usuário

### Integrações Externas

- Não há integrações externas previstas

---

## 4. STACK TECNOLÓGICA

- Backend: PHP 8.x (sem framework)
- Frontend: HTML5, CSS3, JavaScript
- Bibliotecas JS:
  - jQuery
  - DataTables
  - Select2
- UI:
  - Bootstrap
  - FontAwesome
- Banco de Dados: MySQL
- Arquitetura:
  - MVC simplificado
  - POO
  - DAO (Data Access Object)

---

## 5. MODELO DE DADOS

### Usuário

- id_usuario (int)
- nome (varchar)
- usuario (varchar)
- senha (varchar)
- tipo_usuario (enum: administrador, gerente, operador)
- ativo (boolean)
- data_criacao (datetime)

### Setor

- id_setor (int)
- nome (varchar)
- descricao (text)

### Fornecedor

- id_fornecedor (int)
- nome (varchar)
- cnpj (varchar)
- telefone (varchar)
- email (varchar)
- observacoes (text)

### Cliente

- id_cliente (int)
- nome (varchar)
- cpf_cnpj (varchar)
- telefone (varchar)
- email (varchar)
- observacoes (text)

### Bem

- id_bem (int)
- descricao (varchar)
- setor_id (int)
- data_aquisicao (date)
- valor_aquisicao (decimal)
- status (enum: ativo, baixado)
- observacoes (text)

### Manutencao

- id_manutencao (int)
- bem_id (int)
- data_manutencao (date)
- descricao (text)
- custo (decimal)
- observacoes (text)

### ContaPagar

- id_conta_pagar (int)
- fornecedor_id (int)
- descricao (varchar)
- valor (decimal)
- data_vencimento (date)
- status (enum: pendente, paga)
- observacoes (text)

### ContaReceber

- id_conta_receber (int)
- cliente_id (int)
- descricao (varchar)
- valor (decimal)
- data_vencimento (date)
- status (enum: pendente, recebida)
- observacoes (text)

### Pagamento

- id_pagamento (int)
- conta_pagar_id (int)
- data_pagamento (date)
- valor_pago (decimal)

### Recebimento

- id_recebimento (int)
- conta_receber_id (int)
- data_recebimento (date)
- valor_recebido (decimal)

---

## 6. REQUISITOS FUNCIONAIS

RF-001 – Permitir login de usuários com autenticação segura  
RF-002 – Permitir cadastro, edição, listagem e exclusão de usuários  
RF-003 – Controlar acesso por tipo de usuário  
RF-004 – Permitir cadastro e manutenção de setores  
RF-005 – Permitir cadastro e manutenção de fornecedores  
RF-006 – Permitir cadastro e manutenção de clientes  
RF-007 – Permitir cadastro e controle de bens  
RF-008 – Registrar manutenções dos bens  
RF-009 – Manter histórico completo de manutenções  
RF-010 – Permitir cadastro de contas a pagar  
RF-011 – Permitir cadastro de contas a receber  
RF-012 – Registrar pagamentos de contas a pagar  
RF-013 – Registrar recebimentos de contas a receber  
RF-014 – Exibir calendário financeiro de contas a pagar e receber  
RF-015 – Gerar relatório de total pago em um período  
RF-016 – Gerar relatório de total a pagar no próximo mês  
RF-017 – Listar contas que vencem no dia atual  
RF-018 – Gerar relatório de custo total de manutenção por bem

---

## 7. REQUISITOS NÃO FUNCIONAIS

RNF-001 – O sistema deve responder às operações em até 2 segundos  
RNF-002 – Senhas devem ser armazenadas utilizando hash seguro  
RNF-003 – O sistema deve suportar múltiplos usuários simultâneos  
RNF-004 – Interface responsiva e amigável  
RNF-005 – Dados financeiros não podem ser perdidos em falhas comuns

---

## 8. FLUXOS DO SISTEMA

### Fluxo de Login

1. Usuário acessa tela de login
2. Informa e-mail e senha
3. Sistema valida credenciais
4. Redireciona para dashboard conforme tipo de usuário

### Fluxo de Manutenção de Bem

1. Usuário seleciona um bem
2. Registra nova manutenção
3. Sistema salva custo e data
4. Atualiza histórico do bem

### Fluxo de Pagamento

1. Usuário acessa contas a pagar
2. Seleciona conta pendente
3. Registra pagamento
4. Sistema atualiza status para “paga”

---

## 9. REGRAS DE NEGÓCIO

RB-001 – Apenas administradores podem gerenciar usuários  
RB-002 – Contas pagas não podem ser editadas  
RB-003 – Manutenções devem estar vinculadas a um bem existente  
RB-004 – Um bem pode ser avaliado por custo acumulado de manutenção

---

## 10. API (BACKEND)

### POST /login

Entrada: email, senha  
Saída: sessão iniciada ou erro

### POST /contas-pagar

Entrada: fornecedor_id, valor, vencimento  
Saída: conta criada

### POST /pagamentos

Entrada: conta_pagar_id, valor  
Saída: pagamento registrado

---

## 11. CRITÉRIOS DE ACEITE

- RF-001: Usuário válido consegue acessar o sistema
- RF-010: Conta a pagar deve aparecer no calendário
- RF-015: Relatório deve somar corretamente pagamentos no período
- RF-018: Sistema deve exibir total gasto por bem corretamente

---

## ORGANIZAÇÃO DE PASTAS

/app
/controllers
/models
/dao
/services
/public
/css
/js
/img
/views
/sql
banco-dados.sql
banco-dados-drop.sql

---

## ESTRUTURA DE CLASSES (EXEMPLO)

- Usuario
- UsuarioDAO
- ContaPagar
- ContaPagarDAO
- Bem
- Manutencao
- RelatorioService
