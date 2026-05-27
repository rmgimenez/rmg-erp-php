<?php
namespace CantinaFinanceiro;

use PDO;

/**
 * Classe Auth
 * Gerencia autenticação de usuários, controle de sessão e privilégios de acesso.
 */
class Auth {
    /**
     * Inicializa a sessão PHP de forma segura
     */
    public static function initSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            session_start();
        }
    }

    /**
     * Tenta autenticar um usuário e senha
     */
    public static function login(string $usuario, string $senha): bool {
        self::initSession();
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT * FROM usuarios WHERE usuario = ? LIMIT 1");
        $stmt->execute([$usuario]);
        $user = $stmt->fetch();

        if ($user && password_verify($senha, $user['senha'])) {
            // Preenche variáveis de sessão
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_nome'] = $user['nome'];
            $_SESSION['user_usuario'] = $user['usuario'];
            $_SESSION['user_nivel'] = $user['nivel'];

            self::logAction((int)$user['id'], 'LOGIN', 'Login efetuado com sucesso.');
            return true;
        }

        return false;
    }

    /**
     * Finaliza a sessão ativa do usuário
     */
    public static function logout(): void {
        self::initSession();
        if (isset($_SESSION['user_id'])) {
            self::logAction((int)$_SESSION['user_id'], 'LOGOUT', 'Sessão encerrada pelo usuário.');
        }
        $_SESSION = [];
        
        // Destrói cookies de sessão se aplicável
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), 
                '', 
                time() - 42000,
                $params["path"], 
                $params["domain"],
                $params["secure"], 
                $params["httponly"]
            );
        }
        
        session_destroy();
    }

    /**
     * Valida se há um usuário autenticado, redirecionando para login se não houver
     */
    public static function checkAuth(): void {
        self::initSession();
        if (!isset($_SESSION['user_id'])) {
            header("Location: login.php");
            exit;
        }
    }

    /**
     * Restringe o acesso às páginas baseado no nível do usuário logado
     */
    public static function restrictTo(array $niveisPermitidos): void {
        self::checkAuth();
        if (!in_array($_SESSION['user_nivel'], $niveisPermitidos)) {
            if ($_SESSION['user_nivel'] === 'admin') {
                header("Location: admin.php?erro=acesso_negado");
            } elseif ($_SESSION['user_nivel'] === 'nutricionista') {
                header("Location: cardapios.php?erro=acesso_negado");
            } else {
                header("Location: index.php?erro=acesso_negado");
            }
            exit;
        }
    }

    /**
     * Registra ações críticas no banco para fins de auditoria (Logs)
     */
    public static function logAction(?int $usuarioId, string $acao, string $detalhes): void {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("INSERT INTO logs (usuario_id, acao, detalhes) VALUES (?, ?, ?)");
            $stmt->execute([$usuarioId, $acao, $detalhes]);
        } catch (\Exception $e) {
            // Silencia para evitar interrupções de fluxo em caso de falha de gravação de log
        }
    }
}
