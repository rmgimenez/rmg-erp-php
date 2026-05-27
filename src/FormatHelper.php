<?php
namespace CantinaFinanceiro;

/**
 * Helper para formatação de dados
 */
class FormatHelper {

    /**
     * Converte valor BRL (ex: "1.250,50") para centavos (125050)
     */
    public static function parseBrlToCents(string $value): int {
        // Remove R$, espaços, pontos (separador de milhar), substitui vírgula por ponto
        $cleaned = preg_replace('/[R$\s\.]/', '', $value);
        $cleaned = str_replace(',', '.', $cleaned);
        return (int) round((float) $cleaned * 100);
    }

    /**
     * Converte centavos (125050) para formato BRL ("1.250,50")
     */
    public static function formatBrl(int $cents): string {
        return number_format($cents / 100, 2, ',', '.');
    }
}
