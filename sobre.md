# SISTEMA DE GESTÃO FINANCEIRA E CONTROLE DE BENS — SaaS Multi-Tenant

---

## 1. VISÃO GERAL

O **RMG ERP SaaS** é um Sistema de Gestão Financeira e Controle de Bens multi-tenant (multi-empresa), que tem como objetivo centralizar e organizar o controle financeiro e patrimonial de **múltiplas empresas** em uma única instalação, permitindo a gestão eficiente de contas a pagar, contas a receber, bens/equipamentos e suas manutenções, com **isolamento total de dados entre empresas**.

O público-alvo do sistema são pequenas e médias empresas que necessitam de controle financeiro detalhado, histórico de manutenções de equipamentos e relatórios gerenciais para tomada de decisão, como avaliar se um bem deve ser reparado novamente ou substituído por outro.

### Modelo SaaS Multi-Tenant

O sistema opera no modelo **SaaS (Software as a Service)** com multi-tenancy por **empresa_id**:

- Cada empresa (tenant) possui seus dados completamente isolados
- Um **Super Administrador** gerencia a plataforma, cadastra empresas e cria usuários gerentes
- Cada empresa possui seus próprios gerentes e operadores
- O login requer **Código da Empresa + Login + Senha**
- O Super Admin acessa com código "ADMIN" ou campo vazio

Os principais objetivos do sistema são:

- Controlar fluxo de caixa (pagamentos e recebimentos) **por empresa**
- Gerenciar bens e seus custos de manutenção **por empresa**
- Fornecer relatórios financeiros e patrimoniais confiáveis **por empresa**
- Facilitar a visualização de compromissos financeiros futuros
- Garantir controle de acesso por tipo de usuário e por empresa
- Permitir gestão centralizada de múltiplas empresas pelo Super Admin

---

## 2. ESCOPO DO SISTEMA

### Incluído no escopo:

- Autenticação de usuários com login multi-tenant (código empresa + login + senha)
- Cadastro e gerenciamento de empresas (tenants)
- Cadastro e gerenciamento de usuários (com vínculo a empresa)
- Controle de permissões por tipo de usuário (super_admin, gerente, operador)
- Área administrativa exclusiva para Super Admin (/admin/)
- Cadastro de setores (por empresa)
- Cadastro de fornecedores (por empresa)
- Cadastro de clientes (por empresa)
- Cadastro e controle de bens/equipamentos (por empresa)
- Registro e histórico de manutenções dos bens (por empresa)
- Contas a pagar (por empresa)
- Contas a receber (por empresa)
- Registro de pagamentos (por empresa)
- Registro de recebimentos (por empresa)
- Calendário financeiro – contas a pagar e receber (por empresa)
- Relatórios financeiros e patrimoniais por período (por empresa)
- Isolamento total de dados entre empresas

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
- Chart.js (via CDN) para gráficos e dashboards
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

- usar o prefixo `rmg_` em todas as tabelas do banco de dados
- Todo o script de criação do banco deve estar no arquivo:  
  `banco-dados.sql`
- Todo o script de exclusão (DROP de tabelas, views, etc.) deve estar no arquivo:  
  `banco-dados-drop.sql`
- sempre que criar uma nova tabela, view ou outro objeto no banco de dados, o script correspondente deve ser adicionado ao arquivo `banco-dados.sql` e o script de exclusão correspondente deve ser adicionado ao arquivo `banco-dados-drop.sql`.

### Autenticação

- Autenticação baseada em sessão PHP com login multi-tenant
- Login requer: Código da Empresa + Login + Senha
- Super Admin acessa com código "ADMIN" ou campo vazio
- Senhas armazenadas com hash seguro (password_hash/password_verify)
- Controle de acesso baseado em tipo de usuário e empresa
- **Super Administrador (super_admin)**: Acesso à área administrativa (/admin/). Gerencia empresas e cria gerentes. Não acessa dados operacionais das empresas diretamente.
- **Gerente**: Acesso a todas as funcionalidades da sua empresa, incluindo gerenciar operadores. Não pode acessar dados de outras empresas.
- **Operador**: Acesso restrito a funcionalidades de visualização e registro de pagamentos/recebimentos dentro da sua empresa.

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

### Empresa (Tenant)

- id_empresa (int, PK, auto_increment)
- codigo (varchar, único) — código para login
- razao_social (varchar)
- nome_fantasia (varchar)
- cnpj (varchar)
- telefone (varchar)
- email (varchar)
- ativa (boolean, default 1)
- observacoes (text)
- data_criacao (datetime, default CURRENT_TIMESTAMP)

### Usuário

- id_usuario (int, PK, auto_increment)
- empresa_id (int, FK → rmg_empresa, NULL para super_admin)
- nome (varchar)
- usuario (varchar)
- senha (varchar)
- tipo_usuario (enum: super_admin, gerente, operador)
- ativo (boolean)
- data_criacao (datetime)

### Setor

- id_setor (int, PK)
- empresa_id (int, FK → rmg_empresa)
- nome (varchar)
- descricao (text)

### Fornecedor

- id_fornecedor (int, PK)
- empresa_id (int, FK → rmg_empresa)
- nome (varchar)
- cnpj (varchar)
- telefone (varchar)
- email (varchar)
- observacoes (text)

### Cliente

- id_cliente (int, PK)
- empresa_id (int, FK → rmg_empresa)
- nome (varchar)
- cpf_cnpj (varchar)
- telefone (varchar)
- email (varchar)
- observacoes (text)

### Bem

- id_bem (int, PK)
- empresa_id (int, FK → rmg_empresa)
- descricao (varchar)
- setor_id (int, FK → rmg_setor)
- data_aquisicao (date)
- valor_aquisicao (decimal)
- status (enum: ativo, baixado)
- observacoes (text)

### Manutencao

- id_manutencao (int, PK)
- empresa_id (int, FK → rmg_empresa)
- bem_id (int, FK → rmg_bem)
- data_manutencao (date)
- descricao (text)
- custo (decimal)
- observacoes (text)

### ContaPagar

- id_conta_pagar (int, PK)
- empresa_id (int, FK → rmg_empresa)
- fornecedor_id (int, FK → rmg_fornecedor)
- descricao (varchar)
- valor (decimal)
- data_vencimento (date)
- status (enum: pendente, paga)
- observacoes (text)

### ContaReceber

- id_conta_receber (int, PK)
- empresa_id (int, FK → rmg_empresa)
- cliente_id (int, FK → rmg_cliente)
- descricao (varchar)
- valor (decimal)
- data_vencimento (date)
- status (enum: pendente, recebida)
- observacoes (text)

### Pagamento

- id_pagamento (int, PK)
- empresa_id (int, FK → rmg_empresa)
- conta_pagar_id (int, FK → rmg_conta_pagar)
- data_pagamento (date)
- valor_pago (decimal)

### Recebimento

- id_recebimento (int, PK)
- empresa_id (int, FK → rmg_empresa)
- conta_receber_id (int, FK → rmg_conta_receber)
- data_recebimento (date)
- valor_recebido (decimal)

---

## 6. REQUISITOS FUNCIONAIS

RF-001 – Permitir login de usuários com autenticação multi-tenant (código empresa + login + senha)  
RF-002 – Permitir cadastro, edição, listagem e exclusão de usuários (por empresa)  
RF-003 – Controlar acesso por tipo de usuário (super_admin, gerente, operador)  
RF-004 – Permitir cadastro e manutenção de setores (por empresa)  
RF-005 – Permitir cadastro e manutenção de fornecedores (por empresa)  
RF-006 – Permitir cadastro e manutenção de clientes (por empresa)  
RF-007 – Permitir cadastro e controle de bens (por empresa)  
RF-008 – Registrar manutenções dos bens (por empresa)  
RF-009 – Manter histórico completo de manutenções (por empresa)  
RF-010 – Permitir cadastro de contas a pagar (por empresa)  
RF-011 – Permitir cadastro de contas a receber (por empresa)  
RF-012 – Registrar pagamentos de contas a pagar (por empresa)  
RF-013 – Registrar recebimentos de contas a receber (por empresa)  
RF-014 – Exibir calendário financeiro de contas a pagar e receber (por empresa)  
RF-015 – Gerar relatório de total pago em um período (por empresa)  
RF-016 – Gerar relatório de total a pagar no próximo mês (por empresa)  
RF-017 – Listar contas que vencem no dia atual (por empresa)  
RF-018 – Gerar relatório de custo total de manutenção por bem (por empresa)  
RF-019 – Permitir cadastro e gerenciamento de empresas (tenants) pelo Super Admin  
RF-020 – Fornecer área administrativa exclusiva para Super Admin (/admin/)  
RF-021 – Garantir isolamento total de dados entre empresas  
RF-022 – Super Admin pode criar gerentes para qualquer empresa  
RF-023 – Gerentes podem criar operadores apenas para sua própria empresa

---

## 7. REQUISITOS NÃO FUNCIONAIS

RNF-001 – O sistema deve responder às operações em até 2 segundos  
RNF-002 – Senhas devem ser armazenadas utilizando hash seguro (password_hash/password_verify)  
RNF-003 – O sistema deve suportar múltiplos usuários simultâneos de diferentes empresas  
RNF-004 – Interface responsiva e amigável  
RNF-005 – Dados financeiros não podem ser perdidos em falhas comuns  
RNF-006 – Isolamento completo de dados entre empresas (multi-tenant por empresa_id)  
RNF-007 – Todas as consultas ao banco devem filtrar por empresa_id do usuário logado

---

## 8. FLUXOS DO SISTEMA

### Fluxo de Login

1. Usuário acessa tela de login
2. Informa Código da Empresa + Login + Senha
3. Se código for "ADMIN" ou vazio, busca super_admin sem empresa
4. Se código for de uma empresa, busca usuário vinculado a ela
5. Sistema valida credenciais com password_verify
6. Super Admin é redirecionado para /admin/ (painel administrativo)
7. Gerentes e Operadores são redirecionados para /index.php (dashboard da empresa)

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

RB-001 – Super Admin gerencia empresas e cria gerentes para qualquer empresa  
RB-002 – Gerentes gerenciam operadores apenas dentro da sua própria empresa  
RB-003 – Operadores não podem gerenciar usuários  
RB-004 – Contas pagas não podem ser editadas  
RB-005 – Manutenções devem estar vinculadas a um bem existente da mesma empresa  
RB-006 – Um bem pode ser avaliado por custo acumulado de manutenção  
RB-007 – Todas as entidades pertencem a uma empresa e são isoladas por empresa_id  
RB-008 – Super Admin não acessa dados operacionais das empresas diretamente  
RB-009 – O usuário 'admin' (super_admin) não pode ser editado nem excluído

---

## 10. API (BACKEND)

### POST /login

Entrada: codigo_empresa, usuario, senha  
Saída: sessão iniciada (com empresa_id, empresa_nome, empresa_codigo) ou erro

### POST /contas-pagar

Entrada: fornecedor_id, valor, vencimento (empresa_id via sessão)  
Saída: conta criada para a empresa do usuário logado

### POST /pagamentos

Entrada: conta_pagar_id, valor (empresa_id via sessão)  
Saída: pagamento registrado para a empresa do usuário logado

### Área Administrativa (/admin/)

- GET /admin/ — Dashboard do Super Admin (KPIs de empresas e usuários)
- GET /admin/empresas.php — CRUD de empresas (tenants)
- GET /admin/usuarios.php — Gerenciamento de usuários de todas as empresas

---

## 11. CRITÉRIOS DE ACEITE

- RF-001: Usuário válido consegue acessar o sistema
- RF-010: Conta a pagar deve aparecer no calendário
- RF-015: Relatório deve somar corretamente pagamentos no período
- RF-018: Sistema deve exibir total gasto por bem corretamente

---

## ORGANIZAÇÃO DE PASTAS

```
/app
  /controllers
    EmpresaController.php
    LoginController.php
    UsuarioController.php
    BemController.php
    ClienteController.php
    ContaPagarController.php
    ContaReceberController.php
    FornecedorController.php
    ManutencaoController.php
    SetorController.php
  /models
    Empresa.php
    Usuario.php
    Bem.php
    Cliente.php
    ContaPagar.php
    ContaReceber.php
    Fornecedor.php
    Manutencao.php
    Pagamento.php
    Recebimento.php
    Setor.php
  /dao
    Conexao.php
    EmpresaDAO.php
    UsuarioDAO.php
    BemDAO.php
    ClienteDAO.php
    ContaPagarDAO.php
    ContaReceberDAO.php
    FornecedorDAO.php
    ManutencaoDAO.php
    PagamentoDAO.php
    RecebimentoDAO.php
    SetorDAO.php
  /services
    RelatorioService.php
  config.php
/public
  /admin           ← Área exclusiva do Super Admin
    index.php        (Dashboard admin)
    empresas.php     (CRUD de empresas)
    usuarios.php     (Gerenciamento cross-empresa)
  /ajax
  /css
  /js
  /includes
  /relatorios
  index.php          (Dashboard da empresa)
  login.php
  logout.php
  (demais CRUDs...)
/sql
  banco-dados.sql
  banco-dados-drop.sql
```

---

## ESTRUTURA DE CLASSES

### Models
- Empresa (tenant)
- Usuario (com empresa_id e nome_empresa)
- Bem, Cliente, ContaPagar, ContaReceber, Fornecedor
- Manutencao, Pagamento, Recebimento, Setor

### DAOs (todos filtram por empresa_id)
- EmpresaDAO
- UsuarioDAO
- BemDAO, ClienteDAO, ContaPagarDAO, ContaReceberDAO
- FornecedorDAO, ManutencaoDAO, PagamentoDAO, RecebimentoDAO, SetorDAO

### Controllers
- LoginController (login multi-tenant, verificarSuperAdmin, verificarAcessoEmpresa)
- EmpresaController (CRUD de empresas, apenas super_admin)
- UsuarioController (CRUD de usuários, regras por tipo)
- Demais controllers (passam empresa_id para DAOs via sessão)

### Services
- RelatorioService (todos os métodos recebem $empresaId)
