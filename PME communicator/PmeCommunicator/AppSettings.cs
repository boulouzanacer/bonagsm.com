using Microsoft.Win32;
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
    public bool LaunchAtStartup { get; set; }

    public bool HasDatabasePath() => !string.IsNullOrWhiteSpace(DatabasePath);
    public bool HasDepotSelection() => !string.IsNullOrWhiteSpace(DepotCode);
    public bool HasWebSyncConfiguration() =>
        !string.IsNullOrWhiteSpace(WebEndpoint) &&
        !string.IsNullOrWhiteSpace(WebApiToken);

    public AppSettings Clone()
    {
        return new AppSettings
        {
            DatabasePath = DatabasePath,
            Server = Server,
            Port = Port,
            Username = Username,
            Password = Password,
            Charset = Charset,
            DepotCode = DepotCode,
            DepotName = DepotName,
            WebEndpoint = WebEndpoint,
            WebApiToken = WebApiToken,
            AutoSyncEnabled = AutoSyncEnabled,
            SyncIntervalSeconds = SyncIntervalSeconds,
            LaunchAtStartup = LaunchAtStartup,
        };
    }
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
            return new AppSettings
            {
                LaunchAtStartup = WindowsStartupManager.IsEnabled(),
            };
        }

        try
        {
            var json = File.ReadAllText(path);
            var settings = JsonSerializer.Deserialize<AppSettings>(json, JsonOptions) ?? new AppSettings();
            settings.LaunchAtStartup = WindowsStartupManager.IsEnabled();
            return settings;
        }
        catch
        {
            return new AppSettings
            {
                LaunchAtStartup = WindowsStartupManager.IsEnabled(),
            };
        }
    }

    public static void Save(AppSettings settings)
    {
        Directory.CreateDirectory(GetSettingsDirectory());
        var json = JsonSerializer.Serialize(settings, JsonOptions);
        File.WriteAllText(GetSettingsPath(), json);
    }
}

public static class WindowsStartupManager
{
    private const string RunKeyPath = @"Software\Microsoft\Windows\CurrentVersion\Run";
    private const string ValueName = "PME Communicator";
    private const string StartMinimizedArgument = "--tray";

    public static bool IsEnabled()
    {
        using var key = Registry.CurrentUser.OpenSubKey(RunKeyPath, writable: false);
        var value = key?.GetValue(ValueName) as string;
        return !string.IsNullOrWhiteSpace(value);
    }

    public static void Apply(bool enabled)
    {
        using var key = Registry.CurrentUser.OpenSubKey(RunKeyPath, writable: true) ??
                        Registry.CurrentUser.CreateSubKey(RunKeyPath);

        if (key is null)
        {
            throw new InvalidOperationException("Impossible d'acceder au registre Windows pour configurer le demarrage automatique.");
        }

        if (!enabled)
        {
            key.DeleteValue(ValueName, throwOnMissingValue: false);
            return;
        }

        var executablePath = Application.ExecutablePath;
        var command = $"\"{executablePath}\" {StartMinimizedArgument}";
        key.SetValue(ValueName, command);
    }
}
