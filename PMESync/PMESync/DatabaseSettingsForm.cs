using FirebirdSql.Data.FirebirdClient;

namespace PMESync;

public sealed class DatabaseSettingsForm : Form
{
    private readonly TextBox txtDatabasePath = new() { Width = 380 };
    private readonly TextBox txtServer = new() { Width = 180 };
    private readonly NumericUpDown numPort = new() { Width = 100, Minimum = 1, Maximum = 65535, Value = 3050 };
    private readonly TextBox txtUsername = new() { Width = 180 };
    private readonly TextBox txtPassword = new() { Width = 180, UseSystemPasswordChar = true };
    private readonly TextBox txtCharset = new() { Width = 120 };
    private readonly ComboBox cmbDepot = new() { Width = 380, DropDownStyle = ComboBoxStyle.DropDownList };
    private readonly TextBox txtWebEndpoint = new() { Width = 380 };
    private readonly TextBox txtWebApiToken = new() { Width = 380, UseSystemPasswordChar = true };
    private readonly CheckBox chkAutoSync = new() { AutoSize = true, Text = "Synchronisation automatique" };
    private readonly CheckBox chkLaunchAtStartup = new() { AutoSize = true, Text = "Lancer au demarrage de Windows (mode minimise)" };
    private readonly ComboBox cmbSyncInterval = new() { Width = 220, DropDownStyle = ComboBoxStyle.DropDownList };
    private readonly Panel pnlConnectionLamp = new() { Width = 16, Height = 16, BackColor = Color.Firebrick, Margin = new Padding(0, 3, 8, 0) };
    private readonly Label lblConnectionState = new() { AutoSize = true, Text = "Connexion non testee" };
    private readonly Button btnBrowse = new() { Text = "Parcourir..." };
    private readonly Button btnTest = new() { Text = "Tester la connexion" };
    private readonly Button btnSave = new() { Text = "Enregistrer" };
    private readonly Button btnCancel = new() { Text = "Annuler" };
    private readonly Label lblStatus = new() { AutoSize = true };

    public AppSettings Settings { get; private set; }
    private bool connectionValidated;
    private bool autoConnectionAttempted;

    public DatabaseSettingsForm(AppSettings currentSettings)
    {
        Settings = CloneSettings(currentSettings);

        Text = "Parametres de connexion";
        Icon = AppIconProvider.GetApplicationIcon();
        StartPosition = FormStartPosition.CenterParent;
        FormBorderStyle = FormBorderStyle.FixedDialog;
        MaximizeBox = false;
        MinimizeBox = false;
        ShowInTaskbar = false;
        ClientSize = new Size(760, 600);

        BuildLayout();
        FillFields();
        WireEvents();
    }

    private void BuildLayout()
    {
        var notes = new Label
        {
            AutoSize = true,
            MaximumSize = new Size(690, 0),
            Text = "Renseignez la connexion Firebird. Si la connexion reussit, la liste des depots se charge automatiquement. Vous pouvez aussi configurer l'endpoint PME du site web, le token API, le mode manuel ou automatique, l'intervalle de synchronisation et le lancement automatique avec Windows.",
        };

        var table = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            Padding = new Padding(12),
            ColumnCount = 3,
            RowCount = 14,
        };

        table.ColumnStyles.Add(new ColumnStyle(SizeType.Absolute, 140));
        table.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 100));
        table.ColumnStyles.Add(new ColumnStyle(SizeType.AutoSize));

        table.Controls.Add(notes, 0, 0);
        table.SetColumnSpan(notes, 3);

        table.Controls.Add(new Label { Text = "Base de donnees", AutoSize = true, Anchor = AnchorStyles.Left }, 0, 1);
        table.Controls.Add(txtDatabasePath, 1, 1);
        table.Controls.Add(btnBrowse, 2, 1);

        table.Controls.Add(new Label { Text = "Serveur", AutoSize = true, Anchor = AnchorStyles.Left }, 0, 2);
        table.Controls.Add(txtServer, 1, 2);

        table.Controls.Add(new Label { Text = "Port", AutoSize = true, Anchor = AnchorStyles.Left }, 0, 3);
        table.Controls.Add(numPort, 1, 3);

        table.Controls.Add(new Label { Text = "Utilisateur", AutoSize = true, Anchor = AnchorStyles.Left }, 0, 4);
        table.Controls.Add(txtUsername, 1, 4);

        table.Controls.Add(new Label { Text = "Mot de passe", AutoSize = true, Anchor = AnchorStyles.Left }, 0, 5);
        table.Controls.Add(txtPassword, 1, 5);

        table.Controls.Add(new Label { Text = "Jeu de caracteres", AutoSize = true, Anchor = AnchorStyles.Left }, 0, 6);
        table.Controls.Add(txtCharset, 1, 6);

        table.Controls.Add(new Label { Text = "Depot", AutoSize = true, Anchor = AnchorStyles.Left }, 0, 7);
        table.Controls.Add(cmbDepot, 1, 7);

        table.Controls.Add(new Label { Text = "Endpoint PME", AutoSize = true, Anchor = AnchorStyles.Left }, 0, 8);
        table.Controls.Add(txtWebEndpoint, 1, 8);
        table.SetColumnSpan(txtWebEndpoint, 2);

        table.Controls.Add(new Label { Text = "Token API PME", AutoSize = true, Anchor = AnchorStyles.Left }, 0, 9);
        table.Controls.Add(txtWebApiToken, 1, 9);
        table.SetColumnSpan(txtWebApiToken, 2);

        table.Controls.Add(new Label { Text = "Mode de synchro", AutoSize = true, Anchor = AnchorStyles.Left }, 0, 10);
        table.Controls.Add(chkAutoSync, 1, 10);
        table.SetColumnSpan(chkAutoSync, 2);

        table.Controls.Add(new Label { Text = "Intervalle", AutoSize = true, Anchor = AnchorStyles.Left }, 0, 11);
        table.Controls.Add(cmbSyncInterval, 1, 11);

        table.Controls.Add(new Label { Text = "Demarrage Windows", AutoSize = true, Anchor = AnchorStyles.Left }, 0, 12);
        table.Controls.Add(chkLaunchAtStartup, 1, 12);
        table.SetColumnSpan(chkLaunchAtStartup, 2);

        var connectionStatePanel = new FlowLayoutPanel
        {
            AutoSize = true,
            FlowDirection = FlowDirection.LeftToRight,
            WrapContents = false,
            Margin = new Padding(0),
        };
        connectionStatePanel.Controls.Add(pnlConnectionLamp);
        connectionStatePanel.Controls.Add(lblConnectionState);

        var footerPanel = new FlowLayoutPanel
        {
            AutoSize = true,
            FlowDirection = FlowDirection.TopDown,
            WrapContents = false,
            Margin = new Padding(0),
        };
        footerPanel.Controls.Add(connectionStatePanel);
        footerPanel.Controls.Add(lblStatus);

        lblStatus.ForeColor = Color.DimGray;
        table.Controls.Add(footerPanel, 0, 13);
        table.SetColumnSpan(footerPanel, 3);

        var buttons = new FlowLayoutPanel
        {
            FlowDirection = FlowDirection.RightToLeft,
            Dock = DockStyle.Bottom,
            Height = 46,
            Padding = new Padding(12, 0, 12, 12),
        };

        buttons.Controls.Add(btnSave);
        buttons.Controls.Add(btnCancel);
        buttons.Controls.Add(btnTest);

        Controls.Add(table);
        Controls.Add(buttons);
    }

    private void FillFields()
    {
        txtDatabasePath.Text = Settings.DatabasePath;
        txtServer.Text = Settings.Server;
        numPort.Value = Settings.Port is >= 1 and <= 65535 ? Settings.Port : 3050;
        txtUsername.Text = Settings.Username;
        txtPassword.Text = Settings.Password;
        txtCharset.Text = string.IsNullOrWhiteSpace(Settings.Charset) ? "UTF8" : Settings.Charset;
        txtWebEndpoint.Text = Settings.WebEndpoint;
        txtWebApiToken.Text = Settings.WebApiToken;
        chkAutoSync.Checked = Settings.AutoSyncEnabled;
        chkLaunchAtStartup.Checked = Settings.LaunchAtStartup;
        PopulateSyncIntervals();
        SetConnectionState(false, "Connexion non testee");
        cmbDepot.Items.Clear();
        cmbDepot.Items.Add(new DepotItem(string.Empty, "<Selectionner un depot>"));
        cmbDepot.SelectedIndex = 0;
        cmbDepot.Enabled = false;
        UpdateSyncModeUi();
    }

    private void WireEvents()
    {
        btnBrowse.Click += (_, _) => BrowseDatabasePath();
        btnTest.Click += async (_, _) => await TestConnectionAsync();
        btnSave.Click += async (_, _) => await SaveAndCloseAsync();
        btnCancel.Click += (_, _) => DialogResult = DialogResult.Cancel;
        txtDatabasePath.TextChanged += (_, _) => InvalidateDepots();
        txtServer.TextChanged += (_, _) => InvalidateDepots();
        txtUsername.TextChanged += (_, _) => InvalidateDepots();
        txtPassword.TextChanged += (_, _) => InvalidateDepots();
        txtCharset.TextChanged += (_, _) => InvalidateDepots();
        numPort.ValueChanged += (_, _) => InvalidateDepots();
        cmbDepot.SelectedIndexChanged += (_, _) => SyncSelectedDepotToSettings();
        chkAutoSync.CheckedChanged += (_, _) => UpdateSyncModeUi();
        Shown += async (_, _) => await AutoConnectOnOpenAsync();
    }

    private void PopulateSyncIntervals()
    {
        var options = GetSyncIntervals().ToArray();
        cmbSyncInterval.BeginUpdate();
        cmbSyncInterval.Items.Clear();

        SyncIntervalItem? selected = null;
        foreach (var option in options)
        {
            cmbSyncInterval.Items.Add(option);
            if (option.Seconds == Settings.SyncIntervalSeconds)
            {
                selected = option;
            }
        }

        cmbSyncInterval.SelectedItem = selected ?? options.FirstOrDefault(o => o.Seconds == 60) ?? options[0];
        cmbSyncInterval.EndUpdate();
    }

    private void UpdateSyncModeUi()
    {
        cmbSyncInterval.Enabled = chkAutoSync.Checked;
    }

    private async Task AutoConnectOnOpenAsync()
    {
        if (autoConnectionAttempted)
        {
            return;
        }

        autoConnectionAttempted = true;

        if (string.IsNullOrWhiteSpace(txtDatabasePath.Text))
        {
            return;
        }

        await TestConnectionAsync(showSuccessMessage: false);
    }

    private void BrowseDatabasePath()
    {
        using var dialog = new OpenFileDialog
        {
            Title = "Choisir la base Firebird",
            Filter = "Firebird database (*.fdb;*.gdb)|*.fdb;*.gdb|All files (*.*)|*.*",
            CheckFileExists = true,
        };

        if (dialog.ShowDialog(this) == DialogResult.OK)
        {
            txtDatabasePath.Text = dialog.FileName;
            ResetDepotSelection();
        }
    }

    private void ResetDepotSelection()
    {
        cmbDepot.Items.Clear();
        cmbDepot.Items.Add(new DepotItem(string.Empty, "<Selectionner un depot>"));
        cmbDepot.SelectedIndex = 0;
        Settings.DepotCode = string.Empty;
        Settings.DepotName = string.Empty;
    }

    private void InvalidateDepots()
    {
        connectionValidated = false;
        SetConnectionState(false, "Connexion non testee");
        cmbDepot.Enabled = false;
        autoConnectionAttempted = false;

        if (cmbDepot.Items.Count > 1 || (cmbDepot.SelectedItem as DepotItem)?.CodeDepot is { Length: > 0 })
        {
            ResetDepotSelection();
            SetNeutral("La connexion a change. Le depot sera recharge automatiquement apres un test reussi.");
        }
    }

    private void SyncSelectedDepotToSettings()
    {
        if (cmbDepot.SelectedItem is DepotItem depot)
        {
            Settings.DepotCode = depot.CodeDepot;
            Settings.DepotName = depot.NomDepot;
        }
    }

    private AppSettings ReadFields()
    {
        var depot = cmbDepot.SelectedItem as DepotItem;

        return new AppSettings
        {
            DatabasePath = txtDatabasePath.Text.Trim(),
            Server = string.IsNullOrWhiteSpace(txtServer.Text) ? "localhost" : txtServer.Text.Trim(),
            Port = Decimal.ToInt32(numPort.Value),
            Username = string.IsNullOrWhiteSpace(txtUsername.Text) ? "SYSDBA" : txtUsername.Text.Trim(),
            Password = txtPassword.Text,
            Charset = string.IsNullOrWhiteSpace(txtCharset.Text) ? "UTF8" : txtCharset.Text.Trim(),
            DepotCode = depot?.CodeDepot ?? string.Empty,
            DepotName = depot?.NomDepot ?? string.Empty,
            WebEndpoint = txtWebEndpoint.Text.Trim(),
            WebApiToken = txtWebApiToken.Text.Trim(),
            AutoSyncEnabled = chkAutoSync.Checked,
            SyncIntervalSeconds = (cmbSyncInterval.SelectedItem as SyncIntervalItem)?.Seconds ?? 60,
            LaunchAtStartup = chkLaunchAtStartup.Checked,
        };
    }

    private async Task TestConnectionAsync(bool showSuccessMessage = true)
    {
        var draft = ReadFields();
        var error = ValidateConnectionSettings(draft);
        if (error is not null)
        {
            SetError(error);
            return;
        }

        ToggleBusy(true);
        SetNeutral("Test de connexion en cours...");

        try
        {
            await using var connection = new FbConnection(BuildConnectionString(draft));
            await connection.OpenAsync();
            connectionValidated = true;
            cmbDepot.Enabled = true;
            SetConnectionState(true, "Connexion reussie");
            await LoadDepotsAsync(selectSavedDepot: true, showSuccessMessage: showSuccessMessage);
            if (showSuccessMessage)
            {
                SetSuccess("Connexion reussie.");
            }
        }
        catch (Exception ex)
        {
            connectionValidated = false;
            cmbDepot.Enabled = false;
            ResetDepotSelection();
            SetConnectionState(false, "Connexion echouee");
            SetError(ex.Message);
        }
        finally
        {
            ToggleBusy(false);
        }
    }

    private async Task LoadDepotsAsync(bool selectSavedDepot, bool showSuccessMessage)
    {
        if (!connectionValidated)
        {
            SetError("La connexion doit etre valide avant de charger les depots.");
            return;
        }

        var draft = ReadFields();
        var error = ValidateConnectionSettings(draft);
        if (error is not null)
        {
            SetError(error);
            return;
        }

        ToggleBusy(true);
        SetNeutral("Chargement des depots...");

        try
        {
            var depots = new List<DepotItem>();

            await using var connection = new FbConnection(BuildConnectionString(draft));
            await connection.OpenAsync();

            await using var command = new FbCommand(
                """
                SELECT CODE_DEPOT, NOM_DEPOT
                FROM DEPOT1
                ORDER BY PRINCIPAL DESC, NOM_DEPOT, CODE_DEPOT
                """,
                connection);

            await using var reader = await command.ExecuteReaderAsync();
            while (await reader.ReadAsync())
            {
                depots.Add(new DepotItem(
                    Convert.ToString(reader["CODE_DEPOT"])?.Trim() ?? string.Empty,
                    Convert.ToString(reader["NOM_DEPOT"])?.Trim() ?? string.Empty));
            }

            PopulateDepots(depots, selectSavedDepot ? Settings.DepotCode : draft.DepotCode);
            cmbDepot.Enabled = depots.Count > 0;

            if (depots.Count == 0)
            {
                SetError("Aucun depot trouve dans DEPOT1.");
            }
            else if (showSuccessMessage)
            {
                SetSuccess($"{depots.Count} depot(s) charges.");
            }
            else
            {
                SetNeutral($"{depots.Count} depot(s) disponibles.");
            }
        }
        catch (Exception ex)
        {
            ResetDepotSelection();
            cmbDepot.Enabled = false;
            SetError(ex.Message);
        }
        finally
        {
            ToggleBusy(false);
        }
    }

    private void PopulateDepots(IEnumerable<DepotItem> depots, string selectedCode)
    {
        cmbDepot.BeginUpdate();
        cmbDepot.Items.Clear();
        cmbDepot.Items.Add(new DepotItem(string.Empty, "<Selectionner un depot>"));

        DepotItem? selectedItem = null;
        foreach (var depot in depots)
        {
            cmbDepot.Items.Add(depot);
            if (!string.IsNullOrWhiteSpace(selectedCode) &&
                string.Equals(depot.CodeDepot, selectedCode, StringComparison.OrdinalIgnoreCase))
            {
                selectedItem = depot;
            }
        }

        cmbDepot.SelectedItem = selectedItem ?? cmbDepot.Items[0];
        cmbDepot.EndUpdate();
    }

    private async Task SaveAndCloseAsync()
    {
        var draft = ReadFields();
        var error = ValidateConnectionSettings(draft);
        if (error is not null)
        {
            SetError(error);
            return;
        }

        if (!connectionValidated)
        {
            await TestConnectionAsync(showSuccessMessage: false);
            draft = ReadFields();
        }

        if (cmbDepot.Items.Count <= 1)
        {
            await LoadDepotsAsync(selectSavedDepot: false, showSuccessMessage: false);
            draft = ReadFields();
        }

        error = ValidateSettings(draft);
        if (error is not null)
        {
            SetError(error);
            return;
        }

        Settings = draft;
        DialogResult = DialogResult.OK;
        Close();
    }

    private void ToggleBusy(bool isBusy)
    {
        UseWaitCursor = isBusy;
        btnBrowse.Enabled = !isBusy;
        btnTest.Enabled = !isBusy;
        btnSave.Enabled = !isBusy;
        btnCancel.Enabled = !isBusy;
        cmbDepot.Enabled = !isBusy && connectionValidated;
    }

    private void SetError(string message)
    {
        lblStatus.ForeColor = Color.Firebrick;
        lblStatus.Text = message;
    }

    private void SetSuccess(string message)
    {
        lblStatus.ForeColor = Color.ForestGreen;
        lblStatus.Text = message;
    }

    private void SetNeutral(string message)
    {
        lblStatus.ForeColor = Color.DimGray;
        lblStatus.Text = message;
    }

    private void SetConnectionState(bool isConnected, string text)
    {
        pnlConnectionLamp.BackColor = isConnected ? Color.ForestGreen : Color.Firebrick;
        lblConnectionState.ForeColor = isConnected ? Color.ForestGreen : Color.Firebrick;
        lblConnectionState.Text = text;
    }

    private static string? ValidateConnectionSettings(AppSettings settings)
    {
        if (string.IsNullOrWhiteSpace(settings.DatabasePath))
        {
            return "Le chemin de la base est obligatoire.";
        }

        if (!File.Exists(settings.DatabasePath))
        {
            return "Le fichier de base selectionne est introuvable.";
        }

        return null;
    }

    private static string? ValidateSettings(AppSettings settings)
    {
        var connectionError = ValidateConnectionSettings(settings);
        if (connectionError is not null)
        {
            return connectionError;
        }

        if (string.IsNullOrWhiteSpace(settings.DepotCode))
        {
            return "Vous devez charger et selectionner un depot.";
        }

        var webError = ValidateWebSettings(settings);
        if (webError is not null)
        {
            return webError;
        }

        return null;
    }

    private static string? ValidateWebSettings(AppSettings settings)
    {
        var hasEndpoint = !string.IsNullOrWhiteSpace(settings.WebEndpoint);
        var hasToken = !string.IsNullOrWhiteSpace(settings.WebApiToken);

        if (!hasEndpoint && !hasToken)
        {
            if (settings.AutoSyncEnabled)
            {
                return "Activez la synchro automatique seulement apres configuration de l'endpoint et du token PME.";
            }

            return null;
        }

        if (!hasEndpoint || !hasToken)
        {
            return "L'endpoint PME et le token API PME sont obligatoires pour la synchronisation web.";
        }

        if (!Uri.TryCreate(settings.WebEndpoint, UriKind.Absolute, out var endpoint) ||
            (endpoint.Scheme != Uri.UriSchemeHttp && endpoint.Scheme != Uri.UriSchemeHttps))
        {
            return "L'endpoint PME doit etre une URL http:// ou https:// valide.";
        }

        if (settings.SyncIntervalSeconds <= 0)
        {
            return "Choisissez un intervalle de synchronisation valide.";
        }

        return null;
    }

    public static string BuildConnectionString(AppSettings settings)
    {
        var builder = new FbConnectionStringBuilder
        {
            Database = settings.DatabasePath,
            DataSource = string.IsNullOrWhiteSpace(settings.Server) ? "localhost" : settings.Server,
            Port = settings.Port,
            UserID = string.IsNullOrWhiteSpace(settings.Username) ? "SYSDBA" : settings.Username,
            Password = settings.Password,
            Charset = string.IsNullOrWhiteSpace(settings.Charset) ? "UTF8" : settings.Charset,
            Dialect = 3,
            Pooling = true,
        };

        return builder.ToString();
    }

    public static string BuildEventConnectionString(AppSettings settings)
    {
        var builder = new FbConnectionStringBuilder
        {
            Database = settings.DatabasePath,
            DataSource = string.IsNullOrWhiteSpace(settings.Server) ? "localhost" : settings.Server,
            Port = settings.Port,
            UserID = string.IsNullOrWhiteSpace(settings.Username) ? "SYSDBA" : settings.Username,
            Password = settings.Password,
            Charset = string.IsNullOrWhiteSpace(settings.Charset) ? "UTF8" : settings.Charset,
            Dialect = 3,
            Pooling = false,
        };

        return builder.ToString();
    }

    private static AppSettings CloneSettings(AppSettings source)
    {
        return new AppSettings
        {
            DatabasePath = source.DatabasePath,
            Server = source.Server,
            Port = source.Port,
            Username = source.Username,
            Password = source.Password,
            Charset = source.Charset,
            DepotCode = source.DepotCode,
            DepotName = source.DepotName,
            WebEndpoint = source.WebEndpoint,
            WebApiToken = source.WebApiToken,
            AutoSyncEnabled = source.AutoSyncEnabled,
            SyncIntervalSeconds = source.SyncIntervalSeconds,
            LaunchAtStartup = source.LaunchAtStartup,
        };
    }

    private static IEnumerable<SyncIntervalItem> GetSyncIntervals()
    {
        yield return new SyncIntervalItem(30, "30 secondes");
        yield return new SyncIntervalItem(60, "1 minute");
        yield return new SyncIntervalItem(120, "2 minutes");
        yield return new SyncIntervalItem(180, "3 minutes");
        yield return new SyncIntervalItem(240, "4 minutes");
        yield return new SyncIntervalItem(300, "5 minutes");
        yield return new SyncIntervalItem(600, "10 minutes");
        yield return new SyncIntervalItem(1800, "30 minutes");
        yield return new SyncIntervalItem(3600, "1 heure");
    }

    private sealed class DepotItem(string codeDepot, string nomDepot)
    {
        public string CodeDepot { get; } = codeDepot;
        public string NomDepot { get; } = nomDepot;

        public override string ToString()
        {
            return string.IsNullOrWhiteSpace(CodeDepot)
                ? NomDepot
                : $"{CodeDepot} - {NomDepot}";
        }
    }

    private sealed class SyncIntervalItem(int seconds, string label)
    {
        public int Seconds { get; } = seconds;
        public string Label { get; } = label;

        public override string ToString()
        {
            return Label;
        }
    }
}
