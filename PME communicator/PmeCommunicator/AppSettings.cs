using System.Text.Json;

namespace PmeCommunicator;

public sealed class AppSettings
{
    public string DatabasePath { get; set; } = string.Empty;
    public string Server { get; set; } = "localhost";
    public int Port { get; set; } = 3050;
    public string Username { get; set; } = "SYSDBA";
    public string Password { get; set; } = "masterkey";
    public string Charset { get; set; } = "UTF8";
    public string DepotCode { get; set; } = string.Empty;
    public string DepotName { get; set; } = string.Empty;
    public string WebEndpoint { get; set; } = string.Empty;
    public string WebApiToken { get; set; } = string.Empty;
    public bool AutoSyncEnabled { get; set; }
    public int SyncIntervalSeconds { get; set; } = 60;

    public bool HasDatabasePath() => !string.IsNullOrWhiteSpace(DatabasePath);
    public bool HasDepotSelection() => !string.IsNullOrWhiteSpace(DepotCode);
    public bool HasWebSyncConfiguration() =>
        !string.IsNullOrWhiteSpace(WebEndpoint) &&
        !string.IsNullOrWhiteSpace(WebApiToken);
}

public static class AppSettingsStore
{
    private static readonly JsonSerializerOptions JsonOptions = new()
    {
        PropertyNamingPolicy = JsonNamingPolicy.CamelCase,
        WriteIndented = true,
    };

    public static string GetSettingsDirectory()
    {
        return Path.Combine(
            Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
            "PME Communicator");
    }

    public static string GetSettingsPath()
    {
        return Path.Combine(GetSettingsDirectory(), "settings.json");
    }

    public static AppSettings Load()
    {
        var path = GetSettingsPath();
        if (!File.Exists(path))
        {
            return new AppSettings();
        }

        try
        {
            var json = File.ReadAllText(path);
            return JsonSerializer.Deserialize<AppSettings>(json, JsonOptions) ?? new AppSettings();
        }
        catch
        {
            return new AppSettings();
        }
    }

    public static void Save(AppSettings settings)
    {
        Directory.CreateDirectory(GetSettingsDirectory());
        var json = JsonSerializer.Serialize(settings, JsonOptions);
        File.WriteAllText(GetSettingsPath(), json);
    }
}
