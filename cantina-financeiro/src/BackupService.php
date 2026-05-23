<?php
namespace CantinaFinanceiro;

use ZipArchive;
use Exception;

/**
 * Classe BackupService
 * Gerencia a compactação do banco de dados SQLite, listagem de backups e restaurações do sistema.
 */
class BackupService {
    /**
     * Gera um novo backup compactado (.zip) contendo o snapshot atual do SQLite
     */
    public static function criarBackup(int $usuarioId, string $tipo = 'manual'): bool {
        $db = Database::getConnection();
        $dbFile = __DIR__ . '/../db/cantina.sqlite';
        $backupDir = __DIR__ . '/../backups/';

        // Cria o diretório de backups caso não exista
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $fileName = 'backup_' . $tipo . '_' . date('Y-m-d_H-i-s') . '.zip';
        $zipPath = $backupDir . $fileName;

        if (!file_exists($dbFile)) {
            return false;
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
            // Cria uma cópia temporária do banco para evitar travamento de leitura/escrita ativo
            $tempCopy = $dbFile . '.tmp';
            if (copy($dbFile, $tempCopy)) {
                $zip->addFile($tempCopy, 'cantina.sqlite');
                $zip->close();
                unlink($tempCopy); // Exclui arquivo temporário

                $tamanho = filesize($zipPath);

                // Registra o sucesso do backup no banco
                $stmt = $db->prepare("INSERT INTO backups (nome_arquivo, tamanho_bytes, tipo, status, criado_por) VALUES (?, ?, ?, 'sucesso', ?)");
                $stmt->execute([$fileName, $tamanho, $tipo, $usuarioId]);

                Auth::logAction($usuarioId, 'BACKUP', "Backup {$tipo} gerado: {$fileName} (" . round($tamanho / 1024, 2) . " KB)");
                return true;
            }
        }

        // Registra a falha do backup no banco
        $stmt = $db->prepare("INSERT INTO backups (nome_arquivo, tamanho_bytes, tipo, status, criado_por) VALUES (?, 0, ?, 'falha', ?)");
        $stmt->execute([$fileName, $tipo, $usuarioId]);
        
        Auth::logAction($usuarioId, 'BACKUP_FALHA', "Falha ao gerar backup {$tipo} do banco.");
        return false;
    }

    /**
     * Restaura o banco de dados principal a partir de um snapshot compactado ZIP
     */
    public static function restaurarBackup(string $fileName, int $usuarioId): bool {
        $dbFile = __DIR__ . '/../db/cantina.sqlite';
        $zipPath = __DIR__ . '/../backups/' . $fileName;

        if (!file_exists($zipPath)) {
            return false;
        }

        // Valida se o arquivo é realmente um backup
        if (strpos($fileName, 'backup_') !== 0 || substr($fileName, -4) !== '.zip') {
            return false;
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) === true) {
            // Para poder restaurar de forma estável, é fundamental remover conexões PDO
            // No SQLite, restaurar o arquivo principal com conexões abertas pode falhar
            // Para garantir que o arquivo extraído substitua o original perfeitamente, realizamos a extração direta
            $zip->extractTo(__DIR__ . '/../db/', 'cantina.sqlite');
            $zip->close();

            Auth::logAction($usuarioId, 'RESTORE', "Banco de dados restaurado com sucesso a partir do snapshot: {$fileName}");
            return true;
        }

        return false;
    }

    /**
     * Lista todos os backups armazenados fisicamente na pasta backups/
     */
    public static function listarArquivosFisicos(): array {
        $backupDir = __DIR__ . '/../backups/';
        $arquivos = [];
        
        if (file_exists($backupDir)) {
            $files = scandir($backupDir);
            foreach ($files as $file) {
                if (strpos($file, 'backup_') === 0 && substr($file, -4) === '.zip') {
                    $arquivos[] = [
                        'nome' => $file,
                        'tamanho' => filesize($backupDir . $file),
                        'data' => filemtime($backupDir . $file)
                    ];
                }
            }
            
            // Ordena pelo mais recente
            usort($arquivos, function($a, $b) {
                return $b['data'] <=> $a['data'];
            });
        }
        
        return $arquivos;
    }
}
