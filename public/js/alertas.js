function carregarAlertas(forcarExibicao = false) {
  $.ajax({
    url: "ajax/alertas_financeiros.php",
    method: "GET",
    dataType: "json",
    success: function (data) {
      let htmlPagar = "";
      let htmlReceber = "";

      if (data.pagar.length > 0) {
        data.pagar.forEach((c) => {
          let dataVenc = new Date(
            c.vencimento + "T00:00:00",
          ).toLocaleDateString("pt-BR");
          htmlPagar += `<tr>
                        <td>${c.descricao}</td>
                        <td>${c.entidade}</td>
                        <td>${dataVenc}</td>
                        <td>R$ ${parseFloat(c.valor).toFixed(2).replace(".", ",")}</td>
                    </tr>`;
        });
      } else {
        htmlPagar =
          '<tr><td colspan="4" class="text-center text-muted">Nenhuma conta vencida ou vencendo em breve.</td></tr>';
      }

      if (data.receber.length > 0) {
        data.receber.forEach((c) => {
          let dataVenc = new Date(
            c.vencimento + "T00:00:00",
          ).toLocaleDateString("pt-BR");
          htmlReceber += `<tr>
                        <td>${c.descricao}</td>
                        <td>${c.entidade}</td>
                        <td>${dataVenc}</td>
                        <td>R$ ${parseFloat(c.valor).toFixed(2).replace(".", ",")}</td>
                    </tr>`;
        });
      } else {
        htmlReceber =
          '<tr><td colspan="4" class="text-center text-muted">Nenhuma conta vencida ou vencendo em breve.</td></tr>';
      }

      $("#tabelaAlertasPagar tbody").html(htmlPagar);
      $("#tabelaAlertasReceber tbody").html(htmlReceber);

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

      // atualiza contadores nas tabs do modal (se existirem)
      try {
        const $modalPagar = document.getElementById("modal-count-pagar");
        const $modalReceber = document.getElementById("modal-count-receber");
        if ($modalPagar) {
          $modalPagar.textContent = pagarCount || "";
          $modalPagar.classList.toggle("d-none", pagarCount === 0);
        }
        if ($modalReceber) {
          $modalReceber.textContent = receberCount || "";
          $modalReceber.classList.toggle("d-none", receberCount === 0);
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
