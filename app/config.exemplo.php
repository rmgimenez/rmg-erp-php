<?php
// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'erp');
define('DB_USER', 'root');
define('DB_PASS', '');

// Texto do rodapé do sistema — altere aqui para personalizar o rodapé da aplicação
// Pode conter texto simples; para HTML avançado, adapte a exibição em `footer.php`
define('FOOTER_TEXT', 'Desenvolvido por Ricardo Moura Gimenez para ...');

// Nome da empresa que utiliza o sistema — altere para o nome da sua organização
// Ex.: define('COMPANY_NAME', 'Minha Empresa LTDA');
define('COMPANY_NAME', 'Nome da Sua Empresa');

// Configurações de Menu - Controle de visibilidade dos itens do menu
define('SHOW_CONTAS_PAGAR', true);
define('SHOW_CONTAS_RECEBER', true);
define('SHOW_BENS', true);
define('SHOW_CLIENTES', true);
define('SHOW_FORNECEDORES', true);
