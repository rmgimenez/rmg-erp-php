function carregarAlertas(forcarExibicao = false) {
  $.ajax({
    url: "ajax/alertas_financeiros.php",
    method: "GET",
    dataType: "json",
    success: function (data) {
      let htmlPagar = "";
      let htmlReceber = "";

      // garantir vencidas no topo (defensivo) e renderizar com destaque
      const ordenarVencidasPrimeiro = (a, b) => {
        if (a.vencida && !b.vencida) return -1;
        if (!a.vencida && b.vencida) return 1;
        return new Date(a.vencimento) - new Date(b.vencimento);
      };

      data.pagar = (data.pagar || []).slice().sort(ordenarVencidasPrimeiro);
      data.receber = (data.receber || []).slice().sort(ordenarVencidasPrimeiro);

      if (data.pagar.length > 0) {
        data.pagar.forEach((c, idx) => {
          const dataVenc = new Date(
            c.vencimento + "T00:00:00",
          ).toLocaleDateString("pt-BR");
          const rowClass = c.vencida ? "alerta-vencida" : "";
          const atrasoLabel = c.vencida
            ? `<div class="small text-danger">Vencida há ${c.dias_atraso} dia(s)</div>`
            : "";
          const tabindex = c.vencida ? 'tabindex="0"' : "";
          htmlPagar += `<tr class="${rowClass}" ${tabindex}>
                        <td><i class="fas fa-exclamation-circle me-1 text-danger" aria-hidden="true"></i>${c.descricao}</td>
                        <td>${c.entidade}</td>
                        <td>${dataVenc}${atrasoLabel}</td>
                        <td>R$ ${parseFloat(c.valor).toFixed(2).replace(".", ",")}</td>
                    </tr>`;
        });
      } else {
        htmlPagar =
          '<tr><td colspan="4" class="text-center text-muted">Nenhuma conta vencida ou vencendo em breve.</td></tr>';
      }

      if (data.receber.length > 0) {
        data.receber.forEach((c, idx) => {
          const dataVenc = new Date(
            c.vencimento + "T00:00:00",
          ).toLocaleDateString("pt-BR");
          const rowClass = c.vencida ? "alerta-vencida" : "";
          const atrasoLabel = c.vencida
            ? `<div class="small text-danger">Vencida há ${c.dias_atraso} dia(s)</div>`
            : "";
          const tabindex = c.vencida ? 'tabindex="0"' : "";
          htmlReceber += `<tr class="${rowClass}" ${tabindex}>
                        <td><i class="fas fa-exclamation-circle me-1 text-danger" aria-hidden="true"></i>${c.descricao}</td>
                        <td>${c.entidade}</td>
                        <td>${dataVenc}${atrasoLabel}</td>
                        <td>R$ ${parseFloat(c.valor).toFixed(2).replace(".", ",")}</td>
                    </tr>`;
        });
      } else {
        htmlReceber =
          '<tr><td colspan="4" class="text-center text-muted">Nenhuma conta vencida ou vencendo em breve.</td></tr>';
      }

      $("#tabelaAlertasPagar tbody").html(htmlPagar);
      $("#tabelaAlertasReceber tbody").html(htmlReceber);

      // quando modal abrir: focar na primeira conta vencida para maior visibilidade
      $("#modalAlertas")
        .off("shown.bs.modal.alerta")
        .on("shown.bs.modal.alerta", function () {
          const $firstVencida = $(this).find(".alerta-vencida").first();
          if ($firstVencida.length) {
            $firstVencida.attr("aria-live", "polite");
            $firstVencida.focus();
          }
        });

      const pagarCount =
        data.pagar.length ||
        (typeof data.count_pagar === "number" ? data.count_pagar : 0);
      const receberCount =
        data.receber.length ||
        (typeof data.count_receber === "number" ? data.count_receber : 0);
      const totalAlertas = pagarCount + receberCount;
      const hasAlertas = totalAlertas > 0;
      const dias = typeof data.dias === "number" ? data.dias : 10;

      // atualiza texto do modal que mostra a janela (acessibilidade / fallback)
      try {
        document.getElementById("modal-alertas-dias").textContent = dias;
      } catch (e) {
        /* elemento pode não estar presente em views muito antigas — silencioso */
      }

      // atualiza contadores nas tabs do modal (se existirem) e resumo de vencidas
      try {
        const $modalPagar = document.getElementById("modal-count-pagar");
        const $modalReceber = document.getElementById("modal-count-receber");
        const pagarVencidas =
          typeof data.count_pagar_vencidas === "number"
            ? data.count_pagar_vencidas
            : (data.pagar || []).filter((i) => i.vencida).length;
        const receberVencidas =
          typeof data.count_receber_vencidas === "number"
            ? data.count_receber_vencidas
            : (data.receber || []).filter((i) => i.vencida).length;

        if ($modalPagar) {
          $modalPagar.textContent = pagarCount || "";
          $modalPagar.classList.toggle("d-none", pagarCount === 0);
          $modalPagar.setAttribute(
            "title",
            pagarCount
              ? `${pagarCount} conta(s) a pagar — ${pagarVencidas} vencida(s)`
              : "Sem contas a pagar próximas",
          );
          // destaque visual da aba quando houver vencidas
          document
            .getElementById("pagar-tab")
            ?.classList.toggle("tab-vencida", pagarVencidas > 0);
        }
        if ($modalReceber) {
          $modalReceber.textContent = receberCount || "";
          $modalReceber.classList.toggle("d-none", receberCount === 0);
          $modalReceber.setAttribute(
            "title",
            receberCount
              ? `${receberCount} conta(s) a receber — ${receberVencidas} vencida(s)`
              : "Sem contas a receber próximas",
          );
          document
            .getElementById("receber-tab")
            ?.classList.toggle("tab-vencida", receberVencidas > 0);
        }

        // resumo acessível no topo do modal
        const $modalResumo = document.getElementById("modal-alertas-resumo");
        const $modalTotalVenc = document.getElementById("modal-total-vencidas");
        const totalVencidas = pagarVencidas + receberVencidas;
        if ($modalResumo) {
          if (totalVencidas > 0) {
            $modalResumo.textContent = `Priorizadas ${totalVencidas} conta(s) vencida(s). Mostrando também vencimentos nos próximos ${dias} dias.`;
          } else {
            $modalResumo.textContent = "";
          }
        }
        if ($modalTotalVenc) {
          $modalTotalVenc.textContent =
            totalVencidas > 0 ? `${totalVencidas} vencida(s)` : "";
          $modalTotalVenc.classList.toggle("d-none", totalVencidas === 0);
        }
      } catch (e) {
        /* silencioso */
      }

      // atualiza ícone, badges e destaque do menu (acessibilidade + estado inicial sem JS é tratado pelo servidor)
      const $icone = $("#menu-alertas-icone");
      const $badgePagar = $("#menu-alertas-badge-pagar");
      const $badgeReceber = $("#menu-alertas-badge-receber");
      const $link = $("#menu-alertas-vencimentos");

      // badges (contagem) — atualiza texto e visibilidade independentemente
      $badgePagar.text(pagarCount || "");
      $badgePagar.toggleClass("d-none", pagarCount === 0);
      $badgePagar.attr("aria-hidden", pagarCount === 0 ? "true" : "false");
      $badgePagar.attr(
        "title",
        pagarCount
          ? `${pagarCount} conta(s) a pagar`
          : "Sem contas a pagar próximas",
      );

      $badgeReceber.text(receberCount || "");
      $badgeReceber.toggleClass("d-none", receberCount === 0);
      $badgeReceber.attr("aria-hidden", receberCount === 0 ? "true" : "false");
      $badgeReceber.attr(
        "title",
        receberCount
          ? `${receberCount} conta(s) a receber`
          : "Sem contas a receber próximas",
      );

      // ícone — cor + pulso (ativado se qualquer contador > 0)
      $icone.toggleClass("menu-alerta-pulse", hasAlertas);
      $icone.toggleClass("text-danger", hasAlertas && pagarCount > 0);
      $icone.toggleClass(
        "text-primary",
        hasAlertas && pagarCount === 0 && receberCount > 0,
      );
      $icone.toggleClass("text-secondary", !hasAlertas);
      $icone.attr("aria-hidden", hasAlertas ? "false" : "true");

      // link — destaque visual quando houver alertas
      $link.toggleClass("menu-alerta-active", hasAlertas);
      $link.attr("aria-pressed", hasAlertas ? "true" : "false");

      // Abre o modal se tiver dados OU se for solicitado forçadamente (clique do menu)
      if (forcarExibicao || hasAlertas) {
        $("#modalAlertas").modal("show");
      }
    },
    error: function () {
      console.error("Erro ao carregar alertas");
    },
  });
}
