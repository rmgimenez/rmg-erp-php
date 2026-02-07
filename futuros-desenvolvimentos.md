# Futuros desenvolvimentos

## Gráfico de total gasto por setor

Gráfico de barras mostrando o total gasto por setor, permitindo uma comparação visual rápida entre os diferentes setores.

O gráfico ficará no dashboard e deverá levar em conta os últimos 12 meses de dados para mostrar uma visão atualizada dos gastos por setor.

**Prompt para IA:** "Desenvolva um gráfico de barras no dashboard do sistema de gestão financeira para mostrar o total gasto por setor nos últimos 12 meses, utilizando Chart.js e dados de manutenções e contas a pagar vinculadas aos setores. Siga a arquitetura MVC existente, criando métodos no RelatorioService e atualizando o index.php."

## Dashboard aprimorado com métricas visuais

Expandir o dashboard com gráficos adicionais, como:

- Gráfico de linha para fluxo de caixa mensal (entradas vs saídas)
- Gráfico de pizza para distribuição de gastos por categoria (manutenções, fornecedores)
- Indicadores visuais para alertas financeiros

**Prompt para IA:** "Aprimore o dashboard (index.php) do sistema de gestão financeira adicionando gráficos visuais usando Chart.js: gráfico de linha para fluxo de caixa mensal, gráfico de pizza para gastos por categoria, e indicadores visuais para alertas. Integre com os DAOs existentes para obter os dados."

## Sistema de notificações por email

Implementar notificações automáticas por email para:

- Contas a vencer nos próximos 3 dias
- Manutenções programadas
- Relatórios semanais de resumo financeiro

**Prompt para IA:** "Implemente um sistema de notificações por email no sistema de gestão financeira, enviando alertas para contas a vencer em 3 dias, manutenções programadas e relatórios semanais. Use PHPMailer ou similar, crie um novo service EmailService, e integre com os controllers existentes."

## Exportação de relatórios para PDF e Excel

Permitir exportar relatórios financeiros e patrimoniais em formatos PDF e Excel, facilitando compartilhamento e arquivamento.

**Prompt para IA:** "Adicione funcionalidade de exportação para relatórios no sistema de gestão financeira, permitindo gerar PDFs e arquivos Excel para relatórios de contas pagar/receber, manutenções e resumos financeiros. Use bibliotecas como FPDF para PDF e PhpSpreadsheet para Excel, atualizando as páginas de relatórios."

## Controle de inventário para bens

Adicionar campos de quantidade e localização física aos bens, permitindo controle de inventário básico.

**Prompt para IA:** "Expanda o modelo de Bem no sistema de gestão financeira adicionando campos para quantidade e localização física. Atualize o banco de dados (banco-dados.sql), o model Bem, o BemDAO, o BemController e a interface bens.php para gerenciar inventário básico."

## Histórico de auditoria (logs de alterações)

Implementar logs de auditoria para rastrear alterações em registros importantes, como pagamentos, manutenções e cadastros.

**Prompt para IA:** "Implemente um sistema de auditoria no sistema de gestão financeira, criando uma tabela rmg_auditoria para logs de alterações em pagamentos, manutenções e cadastros. Atualize os DAOs para registrar logs automaticamente e crie uma interface para visualizar o histórico."

## Backup automático do banco de dados

Sistema de backup automático diário do banco de dados, com opção de download manual.

**Prompt para IA:** "Desenvolva um sistema de backup automático para o banco de dados MySQL do sistema de gestão financeira, executando backups diários e permitindo download manual. Crie um script PHP para gerar dumps SQL e integre com um cron job ou agendamento."

## API REST completa

Expandir a API backend para cobrir todas as entidades, permitindo integrações externas e futuras expansões.

**Prompt para IA:** "Expanda a API REST do sistema de gestão financeira para incluir endpoints completos para todas as entidades (usuários, setores, fornecedores, clientes, bens, manutenções, contas pagar/receber, pagamentos, recebimentos). Use roteamento baseado em URL e retorne JSON, seguindo a arquitetura existente."

## Suporte a múltiplas empresas

Adicionar suporte para múltiplas empresas no mesmo sistema, com isolamento de dados por empresa.

**Prompt para IA:** "Implemente suporte a múltiplas empresas no sistema de gestão financeira, adicionando uma tabela rmg_empresa e vinculando todas as entidades a uma empresa. Atualize autenticação, DAOs e interfaces para filtrar dados por empresa do usuário logado."

## Melhorias na interface do usuário

Melhorar a responsividade, adicionar temas escuros/claros, e otimizar a experiência do usuário com animações e feedback visual.

**Prompt para IA:** "Melhore a interface do usuário do sistema de gestão financeira adicionando temas escuros/claros, animações CSS, melhor responsividade e feedback visual aprimorado. Atualize os arquivos CSS e JavaScript existentes."
