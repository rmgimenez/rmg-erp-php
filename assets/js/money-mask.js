/**
 * Máscara de moeda BRL para inputs
 * Uso: adicionar classe "money-mask" nos inputs
 */

function initMoneyMask() {
    document.querySelectorAll('.money-mask').forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, "");
            value = (value / 100).toFixed(2) + "";
            value = value.replace(".", ",");
            value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");
            e.target.value = value;
        });
    });
}

/**
 * Converte valor BRL formatado para centavos
 * @param {string} value - Valor no formato "1.250,50"
 * @returns {number} Valor em centavos (125050)
 */
function parseBrlToCents(value) {
    return parseInt(value.replace(/[R$\s\.]/g, '').replace(',', '.')) * 100 || 0;
}

/**
 * Converte centavos para formato BRL
 * @param {number} cents - Valor em centavos (125050)
 * @returns {string} Valor formatado ("1.250,50")
 */
function formatBrl(cents) {
    return (cents / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Inicializa automaticamente quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', initMoneyMask);
