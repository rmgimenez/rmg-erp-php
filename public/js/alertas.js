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

      const hasAlertas = data.pagar.length > 0 || data.receber.length > 0;
      // mostra/oculta ícone no menu (acessibilidade + estado inicial sem JS é tratado pelo servidor)
      const $icone = $("#menu-alertas-icone");
      if (hasAlertas) {
        $icone.removeClass("d-none");
        $icone.attr("aria-hidden", "false");
      } else {
        $icone.addClass("d-none");
        $icone.attr("aria-hidden", "true");
      }

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
