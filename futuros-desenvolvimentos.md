# Futuros desenvolvimentos

Este documento reúne ideias de evolução do RMG ERP SaaS para aumentar valor de negócio, produtividade operacional e capacidade analítica, mantendo o modelo multi-tenant com isolamento por empresa.

---

## 1) Inteligência no Dashboard e BI

### Gráfico de total gasto por setor

Gráfico de barras comparando o total gasto por setor nos últimos 12 meses, consolidando custos de manutenções e contas a pagar relacionadas aos setores.

**Valor de negócio:** identificar setores com maior pressão de custos e apoiar decisões de redução de despesas.

**Prompt para IA:** "Desenvolva um gráfico de barras no dashboard para mostrar o total gasto por setor nos últimos 12 meses, usando Chart.js e dados de manutenções + contas a pagar vinculadas aos setores. Siga a arquitetura MVC existente, criando métodos no RelatorioService e atualizando o index.php."

### Dashboard aprimorado com métricas visuais

Adicionar novos blocos visuais no dashboard:

- Gráfico de linha para fluxo de caixa mensal (entradas x saídas)
- Gráfico de pizza para distribuição de gastos por categoria
- Indicadores de alertas financeiros (vencimentos próximos, inadimplência, despesas acima da média)

**Valor de negócio:** leitura rápida da saúde financeira da empresa sem navegar por múltiplas telas.

**Prompt para IA:** "Aprimore o dashboard (index.php) com Chart.js: gráfico de linha para fluxo de caixa mensal, gráfico de pizza para gastos por categoria e cards de alertas financeiros. Integre com DAOs/Services já existentes."

### Centro de indicadores (KPIs) configuráveis

Permitir que cada empresa escolha quais KPIs exibir no dashboard (ex.: custo de manutenção por bem, taxa de contas pagas no prazo, ticket médio de recebimento).

**Valor de negócio:** dashboard personalizado por perfil de gestão.

**Prompt para IA:** "Crie um módulo de KPIs configuráveis por empresa, salvando preferências por usuário e exibindo no dashboard apenas os indicadores selecionados."

---

## 2) Automação financeira e produtividade

### Sistema de notificações por email

Implementar envio automático de emails para:

- Contas a vencer nos próximos 3 dias
- Manutenções programadas
- Resumo financeiro semanal

**Valor de negócio:** reduzir atrasos e melhorar previsibilidade de caixa.

**Prompt para IA:** "Implemente notificações automáticas por email com PHPMailer para contas próximas do vencimento, manutenções programadas e resumo semanal. Crie EmailService e integre com controllers/rotinas agendadas."

### Regras de recorrência para contas

Permitir criação automática de contas a pagar/receber recorrentes (mensal, trimestral, anual), com opção de término por data ou número de repetições.

**Valor de negócio:** reduzir cadastro manual e erros operacionais.

**Prompt para IA:** "Adicione recorrência em contas a pagar/receber com periodicidade configurável, geração automática de parcelas futuras e controle de status por ocorrência."

### Conciliação interna simplificada

Criar tela para comparar lançamentos previstos x realizados (pagamentos e recebimentos), destacando divergências de valor e atraso.

**Valor de negócio:** melhorar controle financeiro sem integração bancária.

**Prompt para IA:** "Implemente uma visão de conciliação interna que compare valores previstos e realizados de contas, exibindo diferenças, atrasos e percentual de aderência mensal."

---

## 3) Patrimônio, manutenção e inventário

### Controle de inventário para bens

Adicionar aos bens os campos:

- quantidade
- localização_fisica
- numero_serie (opcional)
- vida_util_meses (opcional)

**Valor de negócio:** aumentar rastreabilidade e planejamento de reposição.

**Prompt para IA:** "Expanda o modelo de Bem com quantidade, localização física, número de série e vida útil em meses. Atualize banco-dados.sql, banco-dados-drop.sql, model, DAO, controller e bens.php."

### Score de criticidade do bem

Calcular automaticamente um score por bem baseado em frequência de manutenção, custo acumulado e tempo de indisponibilidade.

**Valor de negócio:** priorizar substituição de ativos com pior desempenho.

**Prompt para IA:** "Crie um score de criticidade de bens usando histórico de manutenções (quantidade, custo e dias indisponíveis), exibindo ranking no módulo de bens e relatórios."

### Plano de manutenção preventiva

Cadastrar periodicidade de manutenção preventiva por bem e gerar alertas de execução.

**Valor de negócio:** reduzir manutenção corretiva e paradas inesperadas.

**Prompt para IA:** "Implemente manutenção preventiva por bem com periodicidade configurável, alertas de próxima execução e histórico de cumprimento."

---

## 4) Governança, segurança e auditoria

### Histórico de auditoria (logs de alterações)

Registrar trilha completa de alterações em entidades críticas (contas, pagamentos, recebimentos, bens e manutenções), contendo:

- usuário
- empresa
- entidade
- ação (insert/update/delete)
- valores antes/depois
- data/hora

**Valor de negócio:** rastreabilidade para compliance e investigação de inconsistências.

**Prompt para IA:** "Implemente auditoria com tabela rmg_auditoria, registrando alterações automáticas nos DAOs e criando interface de consulta com filtros por empresa, usuário, entidade e período."

### Aprovação em duas etapas para lançamentos críticos

Exigir aprovação de gerente para:

- exclusão de contas
- pagamentos acima de um limite configurado
- baixa de bens patrimoniais

**Valor de negócio:** reduzir risco operacional e fraude interna.

**Prompt para IA:** "Implemente fluxo de aprovação em duas etapas para operações críticas, com status pendente/aprovado/rejeitado e trilha de aprovação por usuário."

### Políticas de segurança de senha

Adicionar regras de complexidade mínima, expiração opcional e bloqueio temporário por tentativas inválidas.

**Valor de negócio:** elevar segurança de acesso da plataforma.

**Prompt para IA:** "Aprimore a autenticação com política de senha forte, controle de tentativas de login e bloqueio temporário por usuário/empresa."

---

## 5) Relatórios, exportações e dados

### Exportação de relatórios para PDF e Excel

Permitir exportar relatórios financeiros e patrimoniais em PDF e XLSX.

**Valor de negócio:** facilitar compartilhamento, auditoria e arquivamento.

**Prompt para IA:** "Adicione exportação de relatórios para PDF (FPDF) e Excel (PhpSpreadsheet), cobrindo contas pagar/receber, fluxo de caixa, manutenções e resumo mensal."

### Agendamento de relatórios

Permitir configurar envio automático de relatórios por email (diário, semanal, mensal) para usuários autorizados.

**Valor de negócio:** garantir acompanhamento contínuo sem intervenção manual.

**Prompt para IA:** "Crie agendamento de relatórios com periodicidade configurável e envio automático por email para gerentes da empresa, respeitando permissões."

### Relatório de previsão de caixa

Gerar projeção dos próximos 30/60/90 dias com base em contas a pagar/receber pendentes.

**Valor de negócio:** antecipar riscos de caixa e apoiar planejamento.

**Prompt para IA:** "Implemente relatório de previsão de caixa para 30, 60 e 90 dias, consolidando vencimentos futuros e saldo projetado por período."

---

## 6) Plataforma SaaS e administração

### API REST completa

Expandir API backend para todas as entidades, com autenticação, paginação e filtros por empresa.

**Valor de negócio:** facilitar integrações futuras e automações externas.

**Prompt para IA:** "Expanda a API REST para todas as entidades (usuários, setores, fornecedores, clientes, bens, manutenções, contas, pagamentos e recebimentos), com respostas JSON, paginação e validação de permissões por empresa."

### Backup automático do banco de dados

Implementar rotina de backup diário com retenção configurável e opção de download manual no admin.

**Valor de negócio:** aumentar resiliência e recuperação de desastre.

**Prompt para IA:** "Desenvolva backup automático diário do MySQL com retenção de versões, log de execução e download manual na área administrativa."

### Centro de administração multi-tenant

Ampliar área do Super Admin com:

- métricas de uso por empresa
- gestão de limites (usuários, armazenamento, volume de registros)
- status de saúde da plataforma

**Valor de negócio:** gestão operacional SaaS mais eficiente e escalável.

**Prompt para IA:** "Aprimore a área /admin com métricas por tenant, gestão de limites e painel de saúde da plataforma, mantendo isolamento entre empresas."

---

## 7) Experiência do usuário

### Melhorias de interface

Evoluir UX com foco em produtividade:

- modo escuro/claro
- melhor responsividade em telas menores
- feedback visual para ações concluídas e erros
- atalhos de navegação nas telas de maior uso

**Valor de negócio:** reduzir tempo de operação e aumentar adoção do sistema.

**Prompt para IA:** "Melhore a interface com temas claro/escuro, refinamento responsivo, feedback visual de ações e atalhos de produtividade nas telas principais."

### Onboarding guiado para novos usuários

Adicionar tour inicial com passos de configuração mínima (empresa, usuários, setores, fornecedores/clientes e primeiros lançamentos).

**Valor de negócio:** acelerar implantação em novos clientes.

**Prompt para IA:** "Implemente onboarding guiado para novos usuários com checklist de configuração inicial e indicadores de progresso."

---

## 8) Priorização sugerida (roadmap)

### Curto prazo (alto impacto + baixa/média complexidade)

1. Gráfico de gastos por setor (12 meses)
2. Recorrência de contas a pagar/receber
3. Exportação PDF/Excel
4. Auditoria básica de alterações

### Médio prazo

1. Notificações por email
2. Previsão de caixa 30/60/90 dias
3. Score de criticidade de bens
4. Aprovação em duas etapas

### Longo prazo

1. API REST completa
2. Centro de administração SaaS avançado
3. Plano de manutenção preventiva completo
4. Agendamento inteligente de relatórios

---

## Observações técnicas importantes

- Manter o padrão MVC/DAO/Service já adotado no projeto
- Garantir isolamento multi-tenant em todas as consultas por empresa_id
- Atualizar sempre os arquivos SQL quando houver criação/alteração de estruturas:
  - sql/banco-dados.sql
  - sql/banco-dados-drop.sql
- Respeitar controle de acesso por perfil (super_admin, gerente, operador)
