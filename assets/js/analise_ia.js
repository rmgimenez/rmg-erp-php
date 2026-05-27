(function() {
    'use strict';

    const loaderOverlay = document.getElementById('ia-loader-overlay');
    const loaderText = document.getElementById('ia-loader-text');

    function mostrarLoader(texto) {
        if (loaderText) loaderText.textContent = texto || 'Por favor, aguarde. Nossa IA está processando os dados...';
        if (loaderOverlay) loaderOverlay.style.display = 'flex';
    }

    function esconderLoader() {
        if (loaderOverlay) loaderOverlay.style.display = 'none';
    }

    function mostrarErro(mensagem) {
        if (typeof exibirAlerta === 'function') {
            exibirAlerta('danger', mensagem);
        } else {
            alert(mensagem);
        }
    }

    window.gerarResumo = function() {
        mostrarLoader('Analisando dados financeiros e gerando resumo executivo...');
        const btn = document.querySelector('#tab-resumo .btn-premium');
        if (btn) btn.disabled = true;

        fetch('analise_ia_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'acao=resumo_executivo'
        })
        .then(r => r.json())
        .then(data => {
            esconderLoader();
            if (btn) btn.disabled = false;
            const container = document.getElementById('resumo-content');
            if (data.sucesso) {
                container.innerHTML = '<div class="analise-ia-result">' + formatarTextoIA(data.conteudo) + '</div>';
            } else {
                container.innerHTML = '<div class="alert alert-danger border-0">' + (data.erro || 'Erro ao gerar resumo.') + '</div>';
            }
        })
        .catch(err => {
            esconderLoader();
            if (btn) btn.disabled = false;
            mostrarErro('Erro de conexão: ' + err.message);
        });
    };

    window.gerarTendencia = function() {
        mostrarLoader('Analisando tendências e gerando projeções...');
        const btn = document.querySelector('#tab-tendencia .btn-premium');
        if (btn) btn.disabled = true;

        fetch('analise_ia_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'acao=tendencia'
        })
        .then(r => r.json())
        .then(data => {
            esconderLoader();
            if (btn) btn.disabled = false;
            const container = document.getElementById('tendencia-content');
            const disclaimer = document.getElementById('tendencia-disclaimer');
            const copiar = document.getElementById('btn-copiar-tendencia');
            if (data.sucesso) {
                container.innerHTML = '<div class="analise-ia-result">' + formatarTextoIA(data.conteudo) + '</div>';
                disclaimer.style.display = 'block';
                copiar.disabled = false;
            } else {
                container.innerHTML = '<div class="alert alert-danger border-0">' + (data.erro || 'Erro ao gerar projeção.') + '</div>';
                disclaimer.style.display = 'none';
                copiar.disabled = true;
            }
        })
        .catch(err => {
            esconderLoader();
            if (btn) btn.disabled = false;
            mostrarErro('Erro de conexão: ' + err.message);
        });
    };

    window.copiarAnalise = function() {
        const container = document.querySelector('#tendencia-content .analise-ia-result');
        if (!container) return;
        const texto = container.innerText;
        navigator.clipboard.writeText(texto).then(() => {
            if (typeof exibirToast === 'function') {
                exibirToast('Análise copiada para a área de transferência.');
            }
        }).catch(() => {
            mostrarErro('Não foi possível copiar.');
        });
    };

    window.enviarPergunta = function() {
        const input = document.getElementById('pergunta-input');
        const pergunta = input ? input.value.trim() : '';
        if (!pergunta) return;

        input.disabled = true;
        mostrarLoader('Processando sua pergunta...');

        fetch('analise_ia_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'acao=pergunta&pergunta=' + encodeURIComponent(pergunta)
        })
        .then(r => r.json())
        .then(data => {
            esconderLoader();
            input.disabled = false;
            if (data.sucesso) {
                adicionarMensagemChat('user', data.pergunta);
                adicionarMensagemChat('assistant', data.conteudo);
                input.value = '';
                document.getElementById('btn-limpar-conversa').style.display = 'inline-block';
                document.getElementById('perguntas-placeholder').style.display = 'none';
            } else {
                mostrarErro(data.erro || 'Erro ao processar pergunta.');
            }
        })
        .catch(err => {
            esconderLoader();
            input.disabled = false;
            mostrarErro('Erro de conexão: ' + err.message);
        });
    };

    window.limparConversa = function() {
        const chat = document.getElementById('perguntas-chat');
        if (chat) {
            const msgs = chat.querySelectorAll('.chat-message');
            msgs.forEach(m => m.remove());
            document.getElementById('perguntas-placeholder').style.display = 'block';
        }
        document.getElementById('btn-limpar-conversa').style.display = 'none';
        if (typeof exibirToast === 'function') {
            exibirToast('Conversa limpa.');
        }
    };

    function adicionarMensagemChat(tipo, texto) {
        const chat = document.getElementById('perguntas-chat');
        if (!chat) return;

        const div = document.createElement('div');
        div.className = 'chat-message mb-3';
        const isUser = tipo === 'user';
        div.innerHTML = '<div class="d-flex' + (isUser ? ' justify-content-end' : '') + '">' +
            '<div class="' + (isUser ? 'chat-bubble-user' : 'chat-bubble-ai') + '">' +
            '<div class="chat-bubble-header">' +
            '<i class="fa-solid ' + (isUser ? 'fa-user' : 'fa-robot') + ' me-1"></i> ' +
            (isUser ? 'Você' : 'IA') +
            '</div>' +
            '<div class="chat-bubble-text">' + formatarTextoIA(texto) + '</div>' +
            '</div></div>';
        chat.appendChild(div);
        chat.scrollTop = chat.scrollHeight;
    }

    function formatarTextoIA(texto) {
        if (!texto) return '';
        var lines = texto.split('\n');
        var html = '';
        var inList = false;

        for (var i = 0; i < lines.length; i++) {
            var line = lines[i];

            var h3Match = line.match(/^### (.+)/);
            if (h3Match) {
                if (inList) { html += '</ul>'; inList = false; }
                html += '<h6 class="fw-bold mt-3 mb-2 text-indigo-950">' + escaparInline(h3Match[1]) + '</h6>';
                continue;
            }

            var h2Match = line.match(/^## (.+)/);
            if (h2Match) {
                if (inList) { html += '</ul>'; inList = false; }
                html += '<h5 class="fw-bold mt-3 mb-2 text-primary">' + escaparInline(h2Match[1]) + '</h5>';
                continue;
            }

            var liMatch = line.match(/^[-*]\s+(.+)/);
            if (liMatch) {
                if (!inList) { html += '<ul class="mb-2 ps-3">'; inList = true; }
                html += '<li>' + escaparInline(liMatch[1]) + '</li>';
                continue;
            }

            if (inList) { html += '</ul>'; inList = false; }

            if (line.trim() === '') {
                html += '</p><p>';
                continue;
            }

            html += escaparInline(line);
        }

        if (inList) html += '</ul>';

        return '<p>' + html + '</p>';
    }

    function escaparInline(texto) {
        var h = escapeHtml(texto);
        h = h.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        return h;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('pergunta-input');
        if (input) {
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    enviarPergunta();
                }
            });
        }
    });
})();
