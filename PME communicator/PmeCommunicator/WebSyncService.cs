using System.Net.Http.Headers;
using System.Net.Http.Json;
using System.Text.Json;
using System.Text.Json.Serialization;

namespace PmeCommunicator;

public static class WebSyncService
{
    private static readonly JsonSerializerOptions JsonOptions = new(JsonSerializerDefaults.Web)
    {
        PropertyNameCaseInsensitive = true,
    };

    public static async Task<SyncProductsResult> SyncProductsAsync(
        AppSettings settings,
        IReadOnlyList<WebSyncProduct> products,
        CancellationToken cancellationToken = default)
    {
        if (products.Count == 0)
        {
            return new SyncProductsResult(0, 0, "Aucun produit a synchroniser.");
        }

        using var client = CreateClient(settings);
        using var response = await client.PostAsJsonAsync(
            "sync-produits",
            new { produits = products },
            JsonOptions,
            cancellationToken);

        var payload = await ReadEnvelopeAsync<SyncProductsResponse>(response, cancellationToken);
        var data = payload.Data ?? new SyncProductsResponse();

        return new SyncProductsResult(
            data.NbInseres,
            data.NbMisAJour,
            payload.Message ?? "Synchronisation produits terminee.");
    }

    public static async Task<IReadOnlyList<SiteOrder>> FetchOrdersAsync(
        AppSettings settings,
        CancellationToken cancellationToken = default)
    {
        using var client = CreateClient(settings);
        using var response = await client.GetAsync("commandes?synced=0", cancellationToken);

        var payload = await ReadEnvelopeAsync<List<SiteOrder>>(response, cancellationToken);
        return payload.Data ?? [];
    }

    private static HttpClient CreateClient(AppSettings settings)
    {
        if (!settings.HasWebSyncConfiguration())
        {
            throw new InvalidOperationException("Configurez l'endpoint PME et le token API dans les parametres.");
        }

        var endpoint = settings.WebEndpoint.Trim().TrimEnd('/') + "/";
        var client = new HttpClient
        {
            BaseAddress = new Uri(endpoint, UriKind.Absolute),
            Timeout = TimeSpan.FromSeconds(60),
        };

        client.DefaultRequestHeaders.Authorization = new AuthenticationHeaderValue("Bearer", settings.WebApiToken.Trim());
        client.DefaultRequestHeaders.Accept.Add(new MediaTypeWithQualityHeaderValue("application/json"));
        return client;
    }

    private static async Task<ApiEnvelope<T>> ReadEnvelopeAsync<T>(HttpResponseMessage response, CancellationToken cancellationToken)
    {
        var body = await response.Content.ReadAsStringAsync(cancellationToken);

        if (!response.IsSuccessStatusCode)
        {
            throw new InvalidOperationException(ExtractMessage(body, $"Erreur HTTP {(int)response.StatusCode}."));
        }

        if (string.IsNullOrWhiteSpace(body))
        {
            return new ApiEnvelope<T>();
        }

        var payload = JsonSerializer.Deserialize<ApiEnvelope<T>>(body, JsonOptions);
        return payload ?? new ApiEnvelope<T>();
    }

    private static string ExtractMessage(string body, string fallback)
    {
        if (string.IsNullOrWhiteSpace(body))
        {
            return fallback;
        }

        try
        {
            using var document = JsonDocument.Parse(body);
            if (document.RootElement.TryGetProperty("message", out var messageElement))
            {
                var message = messageElement.GetString();
                if (!string.IsNullOrWhiteSpace(message))
                {
                    return message;
                }
            }
        }
        catch
        {
        }

        return fallback;
    }
}

public sealed class WebSyncProduct
{
    [JsonPropertyName("reference")]
    public string Reference { get; set; } = string.Empty;

    [JsonPropertyName("designation")]
    public string Designation { get; set; } = string.Empty;

    [JsonPropertyName("prix")]
    public decimal Prix { get; set; }

    [JsonPropertyName("pv_1")]
    public decimal Pv1 { get; set; }

    [JsonPropertyName("pv_2")]
    public decimal Pv2 { get; set; }

    [JsonPropertyName("pv_3")]
    public decimal Pv3 { get; set; }

    [JsonPropertyName("stock")]
    public int Stock { get; set; }

    [JsonPropertyName("categorie")]
    public string Categorie { get; set; } = string.Empty;

    [JsonPropertyName("sous_categorie")]
    public string SousCategorie { get; set; } = string.Empty;

    [JsonPropertyName("marque")]
    public string Marque { get; set; } = string.Empty;

    [JsonPropertyName("abonne_only")]
    public bool AbonneOnly { get; set; }
}

public sealed record SyncProductsResult(int InsertedCount, int UpdatedCount, string Message);

public sealed class SiteOrder
{
    [JsonPropertyName("id")]
    public int Id { get; set; }

    [JsonPropertyName("id_client")]
    public int ClientId { get; set; }

    [JsonPropertyName("date_cmd")]
    public string DateCommande { get; set; } = string.Empty;

    [JsonPropertyName("statut")]
    public string Statut { get; set; } = string.Empty;

    [JsonPropertyName("montant_total")]
    public decimal MontantTotal { get; set; }

    [JsonPropertyName("adresse_livraison")]
    public string AdresseLivraison { get; set; } = string.Empty;

    [JsonPropertyName("id_wilaya")]
    public int WilayaId { get; set; }

    [JsonPropertyName("id_commune")]
    public int CommuneId { get; set; }

    [JsonPropertyName("notes")]
    public string? Notes { get; set; }

    [JsonPropertyName("synced_pme")]
    public int SyncedPme { get; set; }

    [JsonPropertyName("lignes")]
    public List<SiteOrderLine> Lignes { get; set; } = [];
}

public sealed class SiteOrderLine
{
    [JsonPropertyName("id_produit")]
    public int ProduitId { get; set; }

    [JsonPropertyName("reference")]
    public string? Reference { get; set; }

    [JsonPropertyName("designation")]
    public string? Designation { get; set; }

    [JsonPropertyName("quantite")]
    public int Quantite { get; set; }

    [JsonPropertyName("prix_unitaire")]
    public decimal PrixUnitaire { get; set; }

    [JsonPropertyName("sous_total")]
    public decimal SousTotal { get; set; }
}

public sealed class ApiEnvelope<T>
{
    [JsonPropertyName("message")]
    public string? Message { get; set; }

    [JsonPropertyName("data")]
    public T? Data { get; set; }
}

public sealed class SyncProductsResponse
{
    [JsonPropertyName("nb_inseres")]
    public int NbInseres { get; set; }

    [JsonPropertyName("nb_mis_a_jour")]
    public int NbMisAJour { get; set; }
}
