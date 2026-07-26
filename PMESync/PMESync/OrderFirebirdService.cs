using FirebirdSql.Data.FirebirdClient;
using System.Globalization;

namespace PMESync;

internal static class OrderFirebirdService
{
    private const string WebReferencePrefix = "WEB-CMD #";
    private const string DefaultUser = "BOUTIQUE";

    public static async Task<OrderImportResult> ImportValidatedOrderAsync(
        AppSettings settings,
        SiteOrder order,
        CancellationToken cancellationToken = default)
    {
        if (!settings.HasDatabasePath())
        {
            throw new InvalidOperationException("La base Firebird n'est pas configuree.");
        }

        if (!settings.HasDepotSelection())
        {
            throw new InvalidOperationException("Selectionnez d'abord un depot actif dans les parametres.");
        }

        if (order.Lignes.Count == 0)
        {
            throw new InvalidOperationException("La commande web ne contient aucune ligne.");
        }

        await using var connection = new FbConnection(DatabaseSettingsForm.BuildConnectionString(settings));
        await connection.OpenAsync(cancellationToken).ConfigureAwait(false);
        await using var transaction = await connection.BeginTransactionAsync(cancellationToken).ConfigureAwait(false);

        try
        {
            var depot = await LoadDepotAsync(connection, transaction, settings.DepotCode, cancellationToken).ConfigureAwait(false);
            var reference = BuildWebReference(order.Id);
            var existingNumBon = await FindExistingImportedOrderAsync(connection, transaction, reference, cancellationToken).ConfigureAwait(false);
            if (!string.IsNullOrWhiteSpace(existingNumBon))
            {
                await transaction.CommitAsync(cancellationToken).ConfigureAwait(false);
                return new OrderImportResult(existingNumBon, false);
            }

            var clientCode = await EnsureClientAsync(connection, transaction, settings, depot, order, cancellationToken).ConfigureAwait(false);
            var recordId = await GetNextGeneratorValueAsync(connection, transaction, "GEN_BCC1_ID", cancellationToken).ConfigureAwait(false);
            var numBon = recordId.ToString("D6", CultureInfo.InvariantCulture);
            var parsedDate = ParseOrderDate(order.DateCommande);
            var totalQuantity = order.Lignes.Sum(line => Math.Max(0, line.Quantite));

            await InsertBcc1Async(connection, transaction, new Bcc1InsertModel(
                RecordId: recordId,
                NumBon: numBon,
                DateBon: parsedDate,
                Heure: parsedDate.ToString("HH:mm:ss", CultureInfo.InvariantCulture),
                CodeClient: clientCode,
                NombreProduits: order.Lignes.Count,
                TotalQuantite: totalQuantity,
                ModeTarif: NormalizeModeTarif(order.ModeTarif),
                CodeDepot: settings.DepotCode,
                CodeCaisse: depot.CodeCaisse,
                CodeVendeur: depot.CodeVendeur,
                AdresseLivraison: order.AdresseLivraison,
                WilayaLivraison: order.WilayaNom,
                CommuneLivraison: order.CommuneNom,
                ShippingTotal: order.FraisLivraison,
                CompanyName: null,
                ReferenceBon: reference), cancellationToken).ConfigureAwait(false);

            foreach (var line in order.Lignes)
            {
                var product = await FindProductAsync(connection, transaction, line, cancellationToken).ConfigureAwait(false);
                var lineRecordId = await GetNextGeneratorValueAsync(connection, transaction, "GEN_BCC2_ID", cancellationToken).ConfigureAwait(false);
                var quantite = Convert.ToDecimal(Math.Max(0, line.Quantite), CultureInfo.InvariantCulture);
                var colissage = product.Colissage <= 0m ? 0m : product.Colissage;
                var nombreColis = colissage > 0m ? decimal.Round(quantite / colissage, 6, MidpointRounding.AwayFromZero) : 0m;

                await InsertBcc2Async(connection, transaction, new Bcc2InsertModel(
                    RecordId: lineRecordId,
                    NumBon: numBon,
                    CodeBarre: product.CodeBarre,
                    Produit: product.Designation,
                    NombreColis: nombreColis,
                    Colissage: colissage,
                    Quantite: quantite,
                    PrixVenteHt: line.PrixUnitaire,
                    Tva: product.Tva,
                    PrixAchatHt: product.PrixAchatHt,
                    CodeDepot: settings.DepotCode), cancellationToken).ConfigureAwait(false);
            }

            await transaction.CommitAsync(cancellationToken).ConfigureAwait(false);
            return new OrderImportResult(numBon, true);
        }
        catch
        {
            await transaction.RollbackAsync(cancellationToken).ConfigureAwait(false);
            throw;
        }
    }

    private static async Task<DepotInfo> LoadDepotAsync(
        FbConnection connection,
        FbTransaction transaction,
        string depotCode,
        CancellationToken cancellationToken)
    {
        const string sql = """
            SELECT FIRST 1
                COALESCE(CODE_DEPOT, '') AS CODE_DEPOT,
                COALESCE(CODE_CAISSE, '') AS CODE_CAISSE,
                COALESCE(CODE_VENDEUR, '') AS CODE_VENDEUR
            FROM DEPOT1
            WHERE CODE_DEPOT = @code
            """;

        await using var command = new FbCommand(sql, connection, transaction);
        command.Parameters.AddWithValue("@code", depotCode);
        await using var reader = await command.ExecuteReaderAsync(cancellationToken).ConfigureAwait(false);
        if (!await reader.ReadAsync(cancellationToken).ConfigureAwait(false))
        {
            throw new InvalidOperationException($"Depot introuvable dans Firebird : {depotCode}.");
        }

        return new DepotInfo(
            Convert.ToString(reader["CODE_DEPOT"], CultureInfo.InvariantCulture)?.Trim() ?? string.Empty,
            Convert.ToString(reader["CODE_CAISSE"], CultureInfo.InvariantCulture)?.Trim() ?? string.Empty,
            Convert.ToString(reader["CODE_VENDEUR"], CultureInfo.InvariantCulture)?.Trim() ?? string.Empty);
    }

    private static async Task<string?> FindExistingImportedOrderAsync(
        FbConnection connection,
        FbTransaction transaction,
        string reference,
        CancellationToken cancellationToken)
    {
        const string sql = """
            SELECT FIRST 1 NUM_BON
            FROM BCC1
            WHERE REF_BON = @reference
            """;

        await using var command = new FbCommand(sql, connection, transaction);
        command.Parameters.AddWithValue("@reference", reference);
        var value = await command.ExecuteScalarAsync(cancellationToken).ConfigureAwait(false);
        return value is null or DBNull ? null : Convert.ToString(value, CultureInfo.InvariantCulture)?.Trim();
    }

    private static async Task<string> EnsureClientAsync(
        FbConnection connection,
        FbTransaction transaction,
        AppSettings settings,
        DepotInfo depot,
        SiteOrder order,
        CancellationToken cancellationToken)
    {
        var requestedCode = string.IsNullOrWhiteSpace(order.CodeClient)
            ? $"WEB{order.ClientId:D6}"
            : TrimToLength(order.CodeClient, 20);

        const string selectSql = """
            SELECT FIRST 1 CODE_CLIENT
            FROM CLIENTS
            WHERE CODE_CLIENT = @code
            """;

        await using (var select = new FbCommand(selectSql, connection, transaction))
        {
            select.Parameters.AddWithValue("@code", requestedCode);
            var existing = await select.ExecuteScalarAsync(cancellationToken).ConfigureAwait(false);
            if (existing is not null and not DBNull)
            {
                return Convert.ToString(existing, CultureInfo.InvariantCulture)?.Trim() ?? requestedCode;
            }
        }

        const string insertSql = """
            INSERT INTO CLIENTS (
                CODE_CLIENT,
                CLIENT,
                ADRESSE,
                COMMUNE,
                WILAYA,
                TEL,
                EMAIL,
                MODE_TARIF,
                CODE_DEPOT,
                CODE_VENDEUR,
                UTILISATEUR,
                NOM_ORD,
                JRNL,
                SUP
            ) VALUES (
                @code_client,
                @client,
                @adresse,
                @commune,
                @wilaya,
                @tel,
                @email,
                @mode_tarif,
                @code_depot,
                @code_vendeur,
                @utilisateur,
                @nom_ord,
                @jrnl,
                @sup
            )
            """;

        await using var insert = new FbCommand(insertSql, connection, transaction);
        AddStringParameter(insert, "@code_client", requestedCode, 20);
        AddStringParameter(insert, "@client", string.IsNullOrWhiteSpace(order.ClientNom) ? $"Client web {order.ClientId}" : order.ClientNom, 100);
        AddNullableStringParameter(insert, "@adresse", order.AdresseLivraison, 100);
        AddNullableStringParameter(insert, "@commune", order.CommuneNom, 50);
        AddNullableStringParameter(insert, "@wilaya", order.WilayaNom, 25);
        AddNullableStringParameter(insert, "@tel", order.TelephoneClient, 50);
        AddNullableStringParameter(insert, "@email", null, 50);
        AddStringParameter(insert, "@mode_tarif", NormalizeModeTarif(order.ModeTarif), 1);
        AddNullableStringParameter(insert, "@code_depot", settings.DepotCode, 6);
        AddNullableStringParameter(insert, "@code_vendeur", depot.CodeVendeur, 20);
        AddStringParameter(insert, "@utilisateur", DefaultUser, 25);
        AddStringParameter(insert, "@nom_ord", Environment.MachineName, 25);
        insert.Parameters.Add("@jrnl", FbDbType.SmallInt).Value = 0;
        insert.Parameters.Add("@sup", FbDbType.SmallInt).Value = 0;
        await insert.ExecuteNonQueryAsync(cancellationToken).ConfigureAwait(false);

        return requestedCode;
    }

    private static async Task<ProductInfo> FindProductAsync(
        FbConnection connection,
        FbTransaction transaction,
        SiteOrderLine line,
        CancellationToken cancellationToken)
    {
        var reference = string.IsNullOrWhiteSpace(line.Reference)
            ? throw new InvalidOperationException("Une ligne de commande n'a pas de reference produit.")
            : line.Reference.Trim();

        const string sql = """
            SELECT FIRST 1
                COALESCE(CODE_BARRE, '') AS CODE_BARRE,
                COALESCE(PRODUIT, '') AS PRODUIT,
                COALESCE(COLISSAGE, 0) AS COLISSAGE,
                COALESCE(PP1_HT, COALESCE(PA_HT, 0)) AS PRIX_ACHAT_HT,
                COALESCE(TVA, 0) AS TVA
            FROM PRODUIT
            WHERE COALESCE(SUP, 0) = 0
              AND (REF_PRODUIT = @reference OR CODE_BARRE = @reference)
            ORDER BY CASE WHEN REF_PRODUIT = @reference THEN 0 ELSE 1 END
            """;

        await using var command = new FbCommand(sql, connection, transaction);
        command.Parameters.AddWithValue("@reference", reference);
        await using var reader = await command.ExecuteReaderAsync(cancellationToken).ConfigureAwait(false);
        if (!await reader.ReadAsync(cancellationToken).ConfigureAwait(false))
        {
            throw new InvalidOperationException($"Produit introuvable dans Firebird pour la reference '{reference}'.");
        }

        return new ProductInfo(
            Convert.ToString(reader["CODE_BARRE"], CultureInfo.InvariantCulture)?.Trim() ?? string.Empty,
            Convert.ToString(reader["PRODUIT"], CultureInfo.InvariantCulture)?.Trim() ?? string.Empty,
            Convert.ToDecimal(reader["COLISSAGE"], CultureInfo.InvariantCulture),
            Convert.ToDecimal(reader["PRIX_ACHAT_HT"], CultureInfo.InvariantCulture),
            Convert.ToDecimal(reader["TVA"], CultureInfo.InvariantCulture));
    }

    private static async Task InsertBcc1Async(
        FbConnection connection,
        FbTransaction transaction,
        Bcc1InsertModel model,
        CancellationToken cancellationToken)
    {
        const string sql = """
            INSERT INTO BCC1 (
                RECORDID,
                NUM_BON,
                DATE_BON,
                HEURE,
                CODE_CLIENT,
                NBR_P,
                MODE_RG,
                REF_BON,
                CODE_CAISSE,
                UTILISATEUR,
                MODE_TARIF,
                TOT_QTE,
                CODE_DEPOT,
                CODE_VENDEUR,
                LIVRER,
                ADRESSE_LIV,
                WILAYA_LIV,
                COMMUNE_LIV,
                SHIPPING_TOTAL,
                TYPE_LIVRAISON,
                COMPANY_NAME,
                NOM_ORD,
                JRNL
            ) VALUES (
                @recordid,
                @num_bon,
                @date_bon,
                @heure,
                @code_client,
                @nbr_p,
                @mode_rg,
                @ref_bon,
                @code_caisse,
                @utilisateur,
                @mode_tarif,
                @tot_qte,
                @code_depot,
                @code_vendeur,
                @livrer,
                @adresse_liv,
                @wilaya_liv,
                @commune_liv,
                @shipping_total,
                @type_livraison,
                @company_name,
                @nom_ord,
                @jrnl
            )
            """;

        await using var command = new FbCommand(sql, connection, transaction);
        command.Parameters.Add("@recordid", FbDbType.Integer).Value = model.RecordId;
        AddStringParameter(command, "@num_bon", model.NumBon, 10);
        command.Parameters.Add("@date_bon", FbDbType.Date).Value = model.DateBon.Date;
        AddStringParameter(command, "@heure", model.Heure, 8);
        AddStringParameter(command, "@code_client", model.CodeClient, 20);
        command.Parameters.Add("@nbr_p", FbDbType.Integer).Value = model.NombreProduits;
        AddStringParameter(command, "@mode_rg", "ESPECE", 20);
        AddStringParameter(command, "@ref_bon", model.ReferenceBon, 500);
        AddNullableStringParameter(command, "@code_caisse", model.CodeCaisse, 6);
        AddStringParameter(command, "@utilisateur", DefaultUser, 25);
        AddStringParameter(command, "@mode_tarif", model.ModeTarif, 1);
        command.Parameters.Add("@tot_qte", FbDbType.Double).Value = model.TotalQuantite;
        AddNullableStringParameter(command, "@code_depot", model.CodeDepot, 6);
        AddNullableStringParameter(command, "@code_vendeur", model.CodeVendeur, 20);
        command.Parameters.Add("@livrer", FbDbType.SmallInt).Value = 0;
        AddNullableStringParameter(command, "@adresse_liv", model.AdresseLivraison, 100);
        AddNullableStringParameter(command, "@wilaya_liv", model.WilayaLivraison, 25);
        AddNullableStringParameter(command, "@commune_liv", model.CommuneLivraison, 25);
        command.Parameters.Add("@shipping_total", FbDbType.Double).Value = model.ShippingTotal;
        command.Parameters.Add("@type_livraison", FbDbType.SmallInt).Value = 0;
        AddNullableStringParameter(command, "@company_name", model.CompanyName, 50);
        AddStringParameter(command, "@nom_ord", Environment.MachineName, 25);
        command.Parameters.Add("@jrnl", FbDbType.SmallInt).Value = 0;
        await command.ExecuteNonQueryAsync(cancellationToken).ConfigureAwait(false);
    }

    private static async Task InsertBcc2Async(
        FbConnection connection,
        FbTransaction transaction,
        Bcc2InsertModel model,
        CancellationToken cancellationToken)
    {
        const string sql = """
            INSERT INTO BCC2 (
                RECORDID,
                NUM_BON,
                CODE_BARRE,
                CODE_BARRE_SYN,
                PRODUIT,
                NBRE_COLIS,
                COLISSAGE,
                QTE,
                QTE_GRAT,
                PV_HT,
                TVA,
                PA_HT,
                CODE_DEPOT,
                UTILISATEUR,
                NOM_ORD,
                JRNL
            ) VALUES (
                @recordid,
                @num_bon,
                @code_barre,
                @code_barre_syn,
                @produit,
                @nbre_colis,
                @colissage,
                @qte,
                @qte_grat,
                @pv_ht,
                @tva,
                @pa_ht,
                @code_depot,
                @utilisateur,
                @nom_ord,
                @jrnl
            )
            """;

        await using var command = new FbCommand(sql, connection, transaction);
        command.Parameters.Add("@recordid", FbDbType.Integer).Value = model.RecordId;
        AddStringParameter(command, "@num_bon", model.NumBon, 10);
        AddStringParameter(command, "@code_barre", model.CodeBarre, 20);
        AddStringParameter(command, "@code_barre_syn", model.CodeBarre, 20);
        AddStringParameter(command, "@produit", model.Produit, 100);
        AddNullableDoubleParameter(command, "@nbre_colis", model.NombreColis <= 0m ? null : model.NombreColis);
        AddNullableDoubleParameter(command, "@colissage", model.Colissage <= 0m ? null : model.Colissage);
        command.Parameters.Add("@qte", FbDbType.Double).Value = model.Quantite;
        command.Parameters.Add("@qte_grat", FbDbType.Double).Value = 0m;
        command.Parameters.Add("@pv_ht", FbDbType.Double).Value = model.PrixVenteHt;
        command.Parameters.Add("@tva", FbDbType.Double).Value = model.Tva;
        command.Parameters.Add("@pa_ht", FbDbType.Double).Value = model.PrixAchatHt;
        AddNullableStringParameter(command, "@code_depot", model.CodeDepot, 6);
        AddStringParameter(command, "@utilisateur", DefaultUser, 25);
        AddStringParameter(command, "@nom_ord", Environment.MachineName, 25);
        command.Parameters.Add("@jrnl", FbDbType.SmallInt).Value = 0;
        await command.ExecuteNonQueryAsync(cancellationToken).ConfigureAwait(false);
    }

    private static async Task<int> GetNextGeneratorValueAsync(
        FbConnection connection,
        FbTransaction transaction,
        string generatorName,
        CancellationToken cancellationToken)
    {
        await using var command = new FbCommand($"SELECT GEN_ID({generatorName}, 1) FROM RDB$DATABASE", connection, transaction);
        var value = await command.ExecuteScalarAsync(cancellationToken).ConfigureAwait(false);
        return Convert.ToInt32(value, CultureInfo.InvariantCulture);
    }

    private static DateTime ParseOrderDate(string rawValue)
    {
        if (DateTime.TryParse(rawValue, CultureInfo.InvariantCulture, DateTimeStyles.AssumeLocal, out var parsed) ||
            DateTime.TryParse(rawValue, CultureInfo.CurrentCulture, DateTimeStyles.AssumeLocal, out parsed))
        {
            return parsed;
        }

        return DateTime.Now;
    }

    private static string NormalizeModeTarif(string value)
    {
        var trimmed = string.IsNullOrWhiteSpace(value) ? "0" : value.Trim();
        return trimmed.Length > 1 ? trimmed[..1] : trimmed;
    }

    private static string BuildWebReference(int orderId) => $"{WebReferencePrefix}{orderId}";

    private static string TrimToLength(string? value, int maxLength)
    {
        var trimmed = value?.Trim() ?? string.Empty;
        return trimmed.Length <= maxLength ? trimmed : trimmed[..maxLength];
    }

    private static void AddStringParameter(FbCommand command, string name, string? value, int maxLength)
    {
        command.Parameters.Add(name, FbDbType.VarChar).Value = TrimToLength(value, maxLength);
    }

    private static void AddNullableStringParameter(FbCommand command, string name, string? value, int maxLength)
    {
        var trimmed = TrimToLength(value, maxLength);
        command.Parameters.Add(name, FbDbType.VarChar).Value = string.IsNullOrWhiteSpace(trimmed) ? DBNull.Value : trimmed;
    }

    private static void AddNullableDoubleParameter(FbCommand command, string name, decimal? value)
    {
        command.Parameters.Add(name, FbDbType.Double).Value = value.HasValue ? value.Value : DBNull.Value;
    }

    internal sealed record OrderImportResult(string NumBon, bool Created);

    private sealed record DepotInfo(string CodeDepot, string CodeCaisse, string CodeVendeur);

    private sealed record ProductInfo(string CodeBarre, string Designation, decimal Colissage, decimal PrixAchatHt, decimal Tva);

    private sealed record Bcc1InsertModel(
        int RecordId,
        string NumBon,
        DateTime DateBon,
        string Heure,
        string CodeClient,
        int NombreProduits,
        int TotalQuantite,
        string ModeTarif,
        string CodeDepot,
        string CodeCaisse,
        string CodeVendeur,
        string AdresseLivraison,
        string WilayaLivraison,
        string CommuneLivraison,
        decimal ShippingTotal,
        string? CompanyName,
        string ReferenceBon);

    private sealed record Bcc2InsertModel(
        int RecordId,
        string NumBon,
        string CodeBarre,
        string Produit,
        decimal NombreColis,
        decimal Colissage,
        decimal Quantite,
        decimal PrixVenteHt,
        decimal Tva,
        decimal PrixAchatHt,
        string CodeDepot);
}
