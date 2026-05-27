/**
 * Sistema de notificações (Alertas e Toasts)
 */

/**
 * Exibe alerta na parte superior da página (com scroll)
 * @param {string} tipo - 'success' ou 'danger'
 * @param {string} mensagem - Mensagem a exibir
 */
function exibirAlerta(tipo, mensagem) {
    const placeholder = document.getElementById('alert-placeholder');
    if (!placeholder) return;
    
    const icon = tipo === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
    
    placeholder.innerHTML = `
        <div class="alert alert-${tipo} alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fa-solid ${icon} me-2"></i> ${mensagem}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/**
 * Exibe toast discreto no canto inferior direito (sem scroll)
 * @param {string} mensagem - Mensagem a exibir
 * @param {string} tipo - 'success' ou 'error'
 */
function exibirToast(mensagem, tipo = 'success') {
    // Remove toast anterior se existir
    const toastAnterior = document.getElementById('toast-notificacao');
    if (toastAnterior) toastAnterior.remove();
    
    const icon = tipo === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
    const bgColor = tipo === 'success' ? '#10b981' : '#ef4444';
    
    const toast = document.createElement('div');
    toast.id = 'toast-notificacao';
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: ${bgColor};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 9999;
        font-size: 0.9rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
        animation: toastIn 0.3s ease;
    `;
    toast.innerHTML = `<i class="fa-solid ${icon}"></i> ${mensagem}`;
    document.body.appendChild(toast);
    
    // Remove após 3 segundos
    setTimeout(() => {
        toast.style.animation = 'toastOut 0.3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
