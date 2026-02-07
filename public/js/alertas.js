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

      const totalAlertas = data.pagar.length + data.receber.length;
      const hasAlertas = totalAlertas > 0;

      // atualiza ícone, badge e destaque do menu (acessibilidade + estado inicial sem JS é tratado pelo servidor)
      const $icone = $("#menu-alertas-icone");
      const $badge = $("#menu-alertas-badge");
      const $link = $("#menu-alertas-vencimentos");

      // badge (contagem) — atualiza texto e visibilidade
      $badge.text(totalAlertas || "");
      $badge.toggleClass("d-none", totalAlertas === 0);
      $badge.attr("aria-hidden", totalAlertas === 0 ? "true" : "false");

      // ícone — cor + pulso
      $icone.toggleClass("menu-alerta-pulse", hasAlertas);
      $icone.toggleClass("text-danger", hasAlertas);
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
