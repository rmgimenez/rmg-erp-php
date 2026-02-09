<?php
// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'erp');
define('DB_USER', 'root');
define('DB_PASS', '');

// Texto do rodapé do sistema — altere aqui para personalizar o rodapé da aplicação
// Pode conter texto simples; para HTML avançado, adapte a exibição em `footer.php`
define('FOOTER_TEXT', 'Desenvolvido por Ricardo Moura Gimenez para ...');

// Nome da plataforma SaaS (exibido na tela de login e painel admin)
define('PLATFORM_NAME', 'RMG ERP SaaS');

// O nome da empresa agora é obtido da tabela rmg_empresa no banco de dados.
// Cada empresa cadastrada possui seu próprio nome (razao_social / nome_fantasia).
// A constante COMPANY_NAME não é mais utilizada.

// Configurações de Menu - Controle de visibilidade dos itens do menu
define('SHOW_CONTAS_PAGAR', true);
define('SHOW_CONTAS_RECEBER', true);
define('SHOW_BENS', true);
define('SHOW_CLIENTES', true);
define('SHOW_FORNECEDORES', true);
