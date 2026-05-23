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
     * Gera um novo backup contendo o snapshot atual do SQLite (compacta em .zip se ZipArchive estiver habilitado,
     * caso contrário, gera uma cópia direta .sqlite como fallback).
     */
    public static function criarBackup(int $usuarioId, string $tipo = 'manual'): bool {
        $db = Database::getConnection();
        $dbFile = __DIR__ . '/../db/cantina.sqlite';
        $backupDir = __DIR__ . '/../backups/';

        // Cria o diretório de backups caso não exista
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        if (!file_exists($dbFile)) {
            return false;
        }

        $useZip = class_exists('\ZipArchive');
        $dateStr = date('Y-m-d_H-i-s');
        
        if ($useZip) {
            $fileName = 'backup_' . $tipo . '_' . $dateStr . '.zip';
            $zipPath = $backupDir . $fileName;
            
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
                $tempCopy = $dbFile . '.tmp';
                if (copy($dbFile, $tempCopy)) {
                    $zip->addFile($tempCopy, 'cantina.sqlite');
                    $zip->close();
                    unlink($tempCopy);
                    
                    $tamanho = filesize($zipPath);
                    $stmt = $db->prepare("INSERT INTO backups (nome_arquivo, tamanho_bytes, tipo, status, criado_por) VALUES (?, ?, ?, 'sucesso', ?)");
                    $stmt->execute([$fileName, $tamanho, $tipo, $usuarioId]);
                    Auth::logAction($usuarioId, 'BACKUP', "Backup ZIP {$tipo} gerado: {$fileName} (" . round($tamanho / 1024, 2) . " KB)");
                    return true;
                }
            }
        } else {
            // Fallback: cópia direta do SQLite
            $fileName = 'backup_' . $tipo . '_' . $dateStr . '.sqlite';
            $destPath = $backupDir . $fileName;
            
            if (copy($dbFile, $destPath)) {
                $tamanho = filesize($destPath);
                $stmt = $db->prepare("INSERT INTO backups (nome_arquivo, tamanho_bytes, tipo, status, criado_por) VALUES (?, ?, ?, 'sucesso', ?)");
                $stmt->execute([$fileName, $tamanho, $tipo, $usuarioId]);
                Auth::logAction($usuarioId, 'BACKUP', "Backup SQLite (Cópia Direta - Falta Extensão ZIP) {$tipo} gerado: {$fileName} (" . round($tamanho / 1024, 2) . " KB)");
                return true;
            }
        }

        // Registra a falha do backup no banco
        $failFileName = 'backup_' . $tipo . '_' . $dateStr . ($useZip ? '.zip' : '.sqlite');
        $stmt = $db->prepare("INSERT INTO backups (nome_arquivo, tamanho_bytes, tipo, status, criado_por) VALUES (?, 0, ?, 'falha', ?)");
        $stmt->execute([$failFileName, $tipo, $usuarioId]);
        Auth::logAction($usuarioId, 'BACKUP_FALHA', "Falha ao gerar backup {$tipo} do banco.");
        return false;
    }

    /**
     * Restaura o banco de dados principal a partir de um snapshot compactado ZIP ou cópia direta SQLite
     */
    public static function restaurarBackup(string $fileName, int $usuarioId): bool {
        $dbFile = __DIR__ . '/../db/cantina.sqlite';
        $backupPath = __DIR__ . '/../backups/' . $fileName;

        if (!file_exists($backupPath)) {
            return false;
        }

        // Valida se o arquivo é realmente um backup
        if (strpos($fileName, 'backup_') !== 0) {
            return false;
        }

        $extension = substr($fileName, -4);

        if ($extension === '.zip') {
            if (!class_exists('\ZipArchive')) {
                throw new Exception("A extensão PHP 'zip' não está habilitada neste servidor. Ative-a para poder restaurar arquivos ZIP.");
            }
            $zip = new ZipArchive();
            if ($zip->open($backupPath) === true) {
                $zip->extractTo(__DIR__ . '/../db/', 'cantina.sqlite');
                $zip->close();
                Auth::logAction($usuarioId, 'RESTORE', "Banco de dados restaurado (ZIP) a partir de: {$fileName}");
                return true;
            }
        } elseif ($extension === '.db' || substr($fileName, -7) === '.sqlite') { // .sqlite ou .db
            if (copy($backupPath, $dbFile)) {
                Auth::logAction($usuarioId, 'RESTORE', "Banco de dados restaurado (Cópia SQLite Direta) a partir de: {$fileName}");
                return true;
            }
        }

        return false;
    }

    /**
     * Lista todos os backups armazenados fisicamente na pasta backups/ (.zip e .sqlite)
     */
    public static function listarArquivosFisicos(): array {
        $backupDir = __DIR__ . '/../backups/';
        $arquivos = [];
        
        if (file_exists($backupDir)) {
            $files = scandir($backupDir);
            foreach ($files as $file) {
                if (strpos($file, 'backup_') === 0 && (substr($file, -4) === '.zip' || substr($file, -7) === '.sqlite')) {
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
