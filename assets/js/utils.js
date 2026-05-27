/**
 * Funções utilitárias compartilhadas
 */

/**
 * Escapa caracteres HTML para prevenir XSS
 * @param {string} text - Texto para escapar
 * @returns {string} Texto escapado
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Formata data para padrão brasileiro
 * @param {string} dateStr - Data no formato YYYY-MM-DD
 * @returns {string} Data formatada (DD/MM/YYYY)
 */
function formatarData(dateStr) {
    if (!dateStr) return '';
    const [year, month, day] = dateStr.split('-');
    return `${day}/${month}/${year}`;
}

/**
 * Converte markdown simples para HTML
 * @param {string} text - Texto em markdown
 * @returns {string} HTML convertido
 */
function parseMarkdownToHtml(text) {
    if (!text) return '';
    let html = escapeHtml(text);
    // Negrito
    html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    // Itálico
    html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');
    return html.replace(/\n/g, '<br>');
}

/**
 * Converte markdown para lista HTML formatada
 * @param {string} text - Texto em markdown com marcadores
 * @returns {string} HTML com lista formatada
 */
function convertMarkdownToList(text) {
    if (!text) return '<span class="text-muted">Não informado</span>';
    
    const lines = text.split('\n');
    let html = '<ul class="meal-list">';
    
    lines.forEach(line => {
        line = line.trim();
        if (!line) return;
        // Remove marcadores markdown
        line = line.replace(/^[-*]\s+/, '');
        line = escapeHtml(line);
        // Converte negrito
        line = line.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        html += `<li>${line}</li>`;
    });
    
    html += '</ul>';
    return html;
}
