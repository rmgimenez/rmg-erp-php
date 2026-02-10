-- Script de exclusão do banco de dados (DROP)
-- Atenção: Executar este script apagará todos os dados!

-- Remover índices adicionados pelo script de criação (se existirem)
DROP INDEX IF EXISTS idx_usuario_empresa_id ON rmg_usuario;
DROP INDEX IF EXISTS idx_usuario_tipo_empresa ON rmg_usuario;
DROP INDEX IF EXISTS idx_setor_empresa_nome ON rmg_setor;
DROP INDEX IF EXISTS idx_fornecedor_empresa_nome ON rmg_fornecedor;
DROP INDEX IF EXISTS idx_cliente_empresa_nome ON rmg_cliente;
DROP INDEX IF EXISTS idx_bem_empresa_setor_status ON rmg_bem;
DROP INDEX IF EXISTS idx_bem_empresa_descricao ON rmg_bem;
DROP INDEX IF EXISTS idx_manutencao_empresa_bem_date ON rmg_manutencao;
DROP INDEX IF EXISTS idx_manutencao_empresa_date ON rmg_manutencao;
DROP INDEX IF EXISTS idx_contapagar_empresa_vencimento_status ON rmg_conta_pagar;
DROP INDEX IF EXISTS idx_contapagar_empresa_fornecedor ON rmg_conta_pagar;
DROP INDEX IF EXISTS idx_contareceber_empresa_vencimento_status ON rmg_conta_receber;
DROP INDEX IF EXISTS idx_contareceber_empresa_cliente ON rmg_conta_receber;
DROP INDEX IF EXISTS idx_pagamento_empresa_data ON rmg_pagamento;
DROP INDEX IF EXISTS idx_pagamento_conta ON rmg_pagamento;
DROP INDEX IF EXISTS idx_recebimento_empresa_data ON rmg_recebimento;
DROP INDEX IF EXISTS idx_recebimento_conta ON rmg_recebimento;
DROP INDEX IF EXISTS idx_log_empresa_datahora ON rmg_log;

DROP TABLE IF EXISTS rmg_log;
DROP TABLE IF EXISTS rmg_recebimento;
DROP TABLE IF EXISTS rmg_pagamento;
DROP TABLE IF EXISTS rmg_conta_receber;
DROP TABLE IF EXISTS rmg_conta_pagar;
DROP TABLE IF EXISTS rmg_manutencao;
DROP TABLE IF EXISTS rmg_bem;
DROP TABLE IF EXISTS rmg_cliente;
DROP TABLE IF EXISTS rmg_fornecedor;
DROP TABLE IF EXISTS rmg_setor;
DROP TABLE IF EXISTS rmg_usuario;
DROP TABLE IF EXISTS rmg_empresa;
