/**
 * Page-specific JavaScript for cardapios.php
 * IA generation, manual editing, markdown preview, PDF export
 */

function ajustarSextaFeira(dataInicioVal) {
    if (!dataInicioVal) return;

    let data = new Date(dataInicioVal + 'T00:00:00');

    let diaSemana = data.getDay();
    if (diaSemana !== 1) {
        alert('Atenção: Para manter o cardápio no padrão semanal, selecione uma segunda-feira como data de início.');
    }

    data.setDate(data.getDate() + 4);

    let ano = data.getFullYear();
    let mes = String(data.getMonth() + 1).padStart(2, '0');
    let dia = String(data.getDate()).padStart(2, '0');

    document.getElementById('modal_data_fim').value = `${ano}-${mes}-${dia}`;
}

function gerarCardapioIA(event) {
    event.preventDefault();

    const loader = document.getElementById('ia-loader-overlay');
    const modalEl = document.getElementById('modalGerarCardapio');
    const modal = bootstrap.Modal.getInstance(modalEl);

    modal.hide();
    loader.classList.add('active');

    const formData = new FormData(document.getElementById('formGerarCardapio'));
    formData.append('acao', 'gerar');

    fetch('cardapio_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        loader.classList.remove('active');
        if (data.sucesso) {
            window.location.href = `cardapios.php?id=${data.id}`;
        } else {
            exibirToast(data.erro || 'Falha desconhecida na geração.', 'error');
        }
    })
    .catch(error => {
        loader.classList.remove('active');
        exibirToast('Erro de rede ou timeout do servidor ao tentar chamar a IA.', 'error');
        console.error(error);
    });
}

function gerarListaComprasIA(cardapioId) {
    if (!confirm('Regenerar a lista de compras via IA?\n\nA lista atual será substituída por uma nova versão baseada no cardápio atual.')) return;

    const loader = document.getElementById('ia-loader-overlay');
    loader.classList.add('active');

    var formData = new FormData();
    formData.append('acao', 'regenerar_lista');
    formData.append('id', cardapioId);

    fetch('cardapio_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        loader.classList.remove('active');
        if (data.sucesso) {
            exibirToast('Lista de compras regenerada com sucesso!', 'success');
            document.getElementById('raw-lista-compras').innerText = data.lista_compras;
            document.getElementById('lista-compras-exibicao').innerHTML = parseMarkdownFullJS(data.lista_compras);
        } else {
            exibirToast(data.erro || 'Falha ao regenerar lista de compras.', 'error');
        }
    })
    .catch(error => {
        loader.classList.remove('active');
        exibirToast('Erro de rede ou timeout ao regenerar lista.', 'error');
        console.error(error);
    });
}

function parseMarkdownFullJS(text) {
    if (!text) return '';

    let html = text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");

    html = html.replace(/^### (.*?)$/gm, '<h5 class="text-indigo-950 fw-bold mt-3 mb-2 border-bottom pb-1">$1</h5>');
    html = html.replace(/^## (.*?)$/gm, '<h4 class="text-indigo-950 fw-bold mt-4 mb-2 border-bottom pb-2">$1</h4>');
    html = html.replace(/^# (.*?)$/gm, '<h3 class="text-indigo-950 fw-bold mt-4 mb-2">$1</h3>');

    html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');

    html = html.replace(/^\* (.*?)$/gm, '<li class="small mb-1">$1</li>');
    html = html.replace(/^- (.*?)$/gm, '<li class="small mb-1">$1</li>');

    html = html.replace(/\n/g, '<br>');

    return html;
}

function abrirModalEdicaoDia(dia) {
    const principal = document.getElementById(`raw-principal-${dia}`).innerText.trim();
    const lanche = document.getElementById(`raw-lanche-${dia}`).innerText.trim();

    document.getElementById('modal_edit_dia').value = dia;
    document.getElementById('modal_edit_principal').value = principal;
    document.getElementById('modal_edit_lanche').value = lanche;

    const modal = new bootstrap.Modal(document.getElementById('modalEditarDia'));
    modal.show();
}

function salvarDiaManual(event) {
    event.preventDefault();

    const modalEl = document.getElementById('modalEditarDia');
    const modal = bootstrap.Modal.getInstance(modalEl);

    const dia = document.getElementById('modal_edit_dia').value;
    const principalVal = document.getElementById('modal_edit_principal').value;
    const lancheVal = document.getElementById('modal_edit_lanche').value;

    const formData = new FormData();
    formData.append('acao', 'editar_dia');
    formData.append('id', document.getElementById('formEditarDia').dataset.cardapioId);
    formData.append('dia', dia);
    formData.append('refeicao_principal', principalVal);
    formData.append('lanche', lancheVal);

    fetch('cardapio_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        modal.hide();
        if (data.sucesso) {
            document.getElementById(`raw-principal-${dia}`).innerText = principalVal;
            document.getElementById(`raw-lanche-${dia}`).innerText = lancheVal;

            document.getElementById(`lbl-principal-${dia}`).innerHTML = convertMarkdownToList(principalVal);
            document.getElementById(`lbl-lanche-${dia}`).innerHTML = convertMarkdownToList(lancheVal);

            exibirToast('Dia do cardápio atualizado com sucesso.', 'success');
        } else {
            exibirToast(data.erro || 'Erro ao atualizar.', 'error');
        }
    })
    .catch(error => {
        modal.hide();
        exibirToast('Erro de conexão com o servidor.', 'error');
        console.error(error);
    });
}

function abrirModalEdicaoLista() {
    const listaMarkdown = document.getElementById('raw-lista-compras').innerText.trim();
    document.getElementById('modal_edit_lista_texto').value = listaMarkdown;

    const modal = new bootstrap.Modal(document.getElementById('modalEditarLista'));
    modal.show();
}

function salvarListaManual(event) {
    event.preventDefault();

    const modalEl = document.getElementById('modalEditarLista');
    const modal = bootstrap.Modal.getInstance(modalEl);

    const listaComprasVal = document.getElementById('modal_edit_lista_texto').value;

    const formData = new FormData();
    formData.append('acao', 'editar_lista');
    formData.append('id', document.getElementById('formEditarLista').dataset.cardapioId);
    formData.append('lista_compras', listaComprasVal);

    fetch('cardapio_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        modal.hide();
        if (data.sucesso) {
            document.getElementById('raw-lista-compras').innerText = listaComprasVal;

            let converted = listaComprasVal
                .replace(/### (.*?)\n/g, '<h5 class="text-indigo-950 fw-bold mt-3 mb-2 border-bottom pb-1">$1</h5>')
                .replace(/## (.*?)\n/g, '<h4 class="text-indigo-950 fw-bold mt-4 mb-2 border-bottom pb-2">$1</h4>')
                .replace(/# (.*?)\n/g, '<h3 class="text-indigo-950 fw-bold mt-4 mb-2">$1</h3>')
                .replace(/- (.*?)\n/g, '<li class="small mb-1">$1</li>')
                .replace(/\n/g, '<br>');

            document.getElementById('lista-compras-exibicao').innerHTML = converted;

            exibirToast('Lista de compras atualizada com sucesso.', 'success');
        } else {
            exibirToast(data.erro || 'Erro ao atualizar a lista.', 'error');
        }
    })
    .catch(error => {
        modal.hide();
        exibirToast('Erro de conexão com o servidor.', 'error');
        console.error(error);
    });
}

function abrirModalEdicaoMarkdown() {
    const dias = ['segunda', 'terca', 'quarta', 'quinta', 'sexta'];
    const diasNomes = {
        'segunda': 'Segunda-feira',
        'terca': 'Terça-feira',
        'quarta': 'Quarta-feira',
        'quinta': 'Quinta-feira',
        'sexta': 'Sexta-feira'
    };

    const dataInicio = document.getElementById('dados-cardapio').dataset.dataInicio;
    const dataFim = document.getElementById('dados-cardapio').dataset.dataFim;

    let markdown = "# Cardápio Semanal\n\n";
    markdown += `**Período:** ${dataInicio} a ${dataFim}\n\n`;

    dias.forEach(dia => {
        const principalEl = document.getElementById(`raw-principal-${dia}`);
        const lancheEl = document.getElementById(`raw-lanche-${dia}`);

        if (principalEl && lancheEl) {
            const principal = principalEl.innerText.trim();
            const lanche = lancheEl.innerText.trim();

            markdown += `## ${diasNomes[dia]}\n\n`;
            markdown += `### Almoço\n\n${principal}\n\n`;
            markdown += `### Lanche\n\n${lanche}\n\n`;
        }
    });

    const textarea = document.getElementById('modal_edit_markdown_texto');
    const preview = document.getElementById('markdown-preview');

    if (textarea) {
        textarea.value = markdown;
    }

    if (preview) {
        preview.innerHTML = parseMarkdownFullJS(markdown);
    }

    const modalEl = document.getElementById('modalEditarMarkdown');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function atualizarPreviewMarkdown() {
    const markdown = document.getElementById('modal_edit_markdown_texto').value;
    document.getElementById('markdown-preview').innerHTML = parseMarkdownFullJS(markdown);
}

function salvarMarkdownManual(event) {
    event.preventDefault();

    const modalEl = document.getElementById('modalEditarMarkdown');
    const modal = bootstrap.Modal.getInstance(modalEl);

    const markdownVal = document.getElementById('modal_edit_markdown_texto').value;

    const formData = new FormData();
    formData.append('acao', 'editar_cardapio_markdown');
    formData.append('id', document.getElementById('formEditarMarkdown').dataset.cardapioId);
    formData.append('cardapio_markdown', markdownVal);

    fetch('cardapio_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        modal.hide();
        if (data.sucesso) {
            exibirToast('Cardápio em Markdown atualizado com sucesso.', 'success');
        } else {
            exibirToast(data.erro || 'Erro ao atualizar o markdown.', 'error');
        }
    })
    .catch(error => {
        modal.hide();
        exibirToast('Erro de conexão com o servidor.', 'error');
        console.error(error);
    });
}

function imprimirCardapioPDF() {
    const element = document.createElement('div');
    element.id = 'cardapio-pdf-content';

    const dados = document.getElementById('dados-cardapio');
    const datasDias = JSON.parse(dados.dataset.datasDias || '{}');
    const dataInicio = dados.dataset.dataInicio;
    const dataFim = dados.dataset.dataFim;

    const dias = ['segunda', 'terca', 'quarta', 'quinta', 'sexta'];
    const diasNomes = {
        'segunda': 'Segunda',
        'terca': 'Terça',
        'quarta': 'Quarta',
        'quinta': 'Quinta',
        'sexta': 'Sexta'
    };

    let html = `
            <div style="padding: 15px 20px; font-family: Arial, sans-serif;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px;">
                    <div>
                        <h3 style="margin: 0; font-weight: 700; font-size: 16pt;">Cantina Colégio Sant'Anna</h3>
                        <p style="margin: 2px 0 0 0; font-size: 9pt; color: #666;">Planejamento Nutricional Semanal</p>
                    </div>
                    <div style="text-align: right;">
                        <h4 style="margin: 0; font-weight: 600; font-size: 12pt;">CARDÁPIO SEMANAL</h4>
                        <p style="margin: 2px 0 0 0; font-size: 9pt; color: #666;">Semana de <strong>${dataInicio}</strong> a <strong>${dataFim}</strong></p>
                    </div>
                </div>
        `;

    html += `
            <table style="width: 100%; border-collapse: collapse; font-size: 8.5pt;">
                <thead>
                    <tr>
        `;

    html += `<th style="width: 10%; background: #f0f0f0; border: 1px solid #000; padding: 6px 4px; text-align: center; font-weight: 700; font-size: 8pt;"></th>`;

    dias.forEach(dia => {
        html += `<th style="width: 18%; background: #1e1b4b; color: white; border: 1px solid #000; padding: 6px 4px; text-align: center; font-weight: 700; font-size: 9pt;">
                ${diasNomes[dia]}<br><span style="font-size: 7.5pt; font-weight: 400;">${datasDias[dia] || ''}</span>
            </th>`;
    });

    html += `</tr></thead><tbody>`;

    html += `<tr>
            <td style="background: #f0f0f0; border: 1px solid #000; padding: 6px 4px; font-weight: 700; text-align: center; vertical-align: top; font-size: 8pt;">
                ALMOÇO
            </td>`;

    dias.forEach(dia => {
        const principal = document.getElementById(`raw-principal-${dia}`).innerText.trim();
        const principalList = principal.split('\n')
            .filter(line => line.trim())
            .map(line => line.replace(/^[-*]\s+/, '').replace(/\*\*/g, '').trim())
            .map(line => `<li style="margin-bottom: 2px; line-height: 1.3;">${line}</li>`)
            .join('');

        html += `<td style="border: 1px solid #000; padding: 6px 4px; vertical-align: top;">
                <ul style="margin: 0; padding-left: 12px;">${principalList}</ul>
            </td>`;
    });

    html += `</tr>`;

    html += `<tr>
            <td style="background: #f0f0f0; border: 1px solid #000; padding: 6px 4px; font-weight: 700; text-align: center; vertical-align: top; font-size: 8pt;">
                LANCHE
            </td>`;

    dias.forEach(dia => {
        const lanche = document.getElementById(`raw-lanche-${dia}`).innerText.trim();
        const lancheList = lanche.split('\n')
            .filter(line => line.trim())
            .map(line => line.replace(/^[-*]\s+/, '').replace(/\*\*/g, '').trim())
            .map(line => `<li style="margin-bottom: 2px; line-height: 1.3;">${line}</li>`)
            .join('');

        html += `<td style="border: 1px solid #000; padding: 6px 4px; vertical-align: top;">
                <ul style="margin: 0; padding-left: 12px;">${lancheList}</ul>
            </td>`;
    });

    html += `</tr></tbody></table>`;

    const observacaoEl = document.getElementById('observacao-impressao');
    const observacao = observacaoEl ? observacaoEl.value.trim() : '';

    if (observacao) {
        html += `
                <div style="margin-top: 10px; padding: 8px 10px; background: #fef3c7; border: 1px solid #f59e0b; border-radius: 4px; font-size: 8.5pt;">
                    <strong style="color: #92400e;">Observação:</strong> <span style="color: #78350f;">${observacao}</span>
                </div>
            `;
    }

    const now = new Date();
    const dataStr = now.toLocaleDateString('pt-BR') + ' às ' + now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

    html += `
            <div style="margin-top: 10px; padding-top: 6px; border-top: 1px solid #ccc; font-size: 7.5pt; color: #666; text-align: center;">
                Documento gerado automaticamente pelo Sistema de Cantina em ${dataStr}
            </div>
        </div>`;

    element.innerHTML = html;
    document.body.appendChild(element);

    const opt = {
        margin: [8, 8, 8, 8],
        filename: `cardapio_semanal_${dados.dataset.dataInicioSlug}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
    };

    html2pdf().set(opt).from(element).save().then(() => {
        element.remove();
        exibirToast('PDF gerado com sucesso!', 'success');
    }).catch(error => {
        element.remove();
        exibirToast('Erro ao gerar PDF.', 'error');
        console.error(error);
    });
}

function imprimirListaPDF() {
    const element = document.createElement('div');
    element.id = 'lista-pdf-content';

    const dados = document.getElementById('dados-cardapio');
    const dataInicio = dados.dataset.dataInicio;
    const dataFim = dados.dataset.dataFim;

    const listaCompras = document.getElementById('raw-lista-compras').innerText;

    let listaHtml = listaCompras
        .replace(/### (.*?)\n/g, '<h5 style="color: #1e1b4b; font-weight: 700; margin: 12px 0 6px 0; border-bottom: 1px solid #ccc; padding-bottom: 4px;">$1</h5>')
        .replace(/## (.*?)\n/g, '<h4 style="color: #1e1b4b; font-weight: 700; margin: 16px 0 8px 0; border-bottom: 2px solid #000; padding-bottom: 6px;">$1</h4>')
        .replace(/# (.*?)\n/g, '<h3 style="color: #1e1b4b; font-weight: 700; margin: 20px 0 10px 0;">$1</h3>')
        .replace(/- (.*?)\n/g, '<li style="margin-bottom: 4px;">$1</li>')
        .replace(/\n/g, '<br>');

    const now = new Date();
    const dataStr = now.toLocaleDateString('pt-BR') + ' às ' + now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

    let html = `
            <div style="padding: 20px; font-family: Arial, sans-serif;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px;">
                    <div>
                        <h3 style="margin: 0; font-weight: 700; font-size: 18pt;">Cantina Colégio Sant'Anna</h3>
                        <p style="margin: 3px 0 0 0; font-size: 10pt; color: #666;">Lista de Ingredientes para Compra</p>
                    </div>
                    <div style="text-align: right;">
                        <h4 style="margin: 0; font-weight: 600;">LISTA DE COMPRAS</h4>
                        <p style="margin: 3px 0 0 0; font-size: 10pt; color: #666;">Semana: <strong>${dataInicio}</strong> a <strong>${dataFim}</strong></p>
                    </div>
                </div>
                <div style="font-size: 10pt; line-height: 1.6;">
                    ${listaHtml}
                </div>
                <div style="margin-top: 20px; padding-top: 8px; border-top: 1px solid #ccc; font-size: 8pt; color: #666; text-align: center;">
                    Documento gerado automaticamente pelo Sistema de Cantina em ${dataStr}
                </div>
            </div>
        `;

    element.innerHTML = html;
    document.body.appendChild(element);

    const opt = {
        margin: 10,
        filename: `lista_compras_${dados.dataset.dataInicioSlug}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
    };

    html2pdf().set(opt).from(element).save().then(() => {
        element.remove();
        exibirToast('Lista de compras gerada com sucesso!', 'success');
    }).catch(error => {
        element.remove();
        exibirToast('Erro ao gerar PDF da lista.', 'error');
        console.error(error);
    });
}

function salvarObservacaoImpressao() {
    const observacao = document.getElementById('observacao-impressao').value;

    const formData = new FormData();
    formData.append('acao', 'salvar_observacao_impressao');
    formData.append('id', document.getElementById('dados-cardapio').dataset.cardapioId);
    formData.append('observacao', observacao);

    fetch('cardapio_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            exibirToast('Observação salva com sucesso.', 'success');
        } else {
            exibirToast(data.erro || 'Erro ao salvar observação.', 'error');
        }
    })
    .catch(error => {
        exibirToast('Erro de conexão com o servidor.', 'error');
        console.error(error);
    });
}

function confirmarExclusao(id) {
    if (!confirm('Deseja realmente excluir permanentemente este planejamento de cardápio semanal?')) {
        return;
    }

    const formData = new FormData();
    formData.append('acao', 'excluir');
    formData.append('id', id);

    fetch('cardapio_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            window.location.href = 'cardapios.php';
        } else {
            exibirToast(data.erro || 'Erro ao excluir.', 'error');
        }
    })
    .catch(error => {
        exibirToast('Erro de conexão com o servidor.', 'error');
        console.error(error);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const markdownTextarea = document.getElementById('modal_edit_markdown_texto');
    const previewContent = document.getElementById('markdown-preview');

    if (markdownTextarea && previewContent) {
        markdownTextarea.addEventListener('input', function() {
            previewContent.innerHTML = parseMarkdownFullJS(this.value);
        });

        markdownTextarea.addEventListener('scroll', function() {
            const scrollPercent = this.scrollTop / (this.scrollHeight - this.clientHeight);
            const previewScrollMax = previewContent.scrollHeight - previewContent.clientHeight;
            previewContent.scrollTop = scrollPercent * previewScrollMax;
        });
    }
});
