using ClosedXML.Excel;
using FirebirdSql.Data.FirebirdClient;
using System.Data;
using System.Globalization;

namespace PmeCommunicator;

public partial class Form1 : Form
{
    private const string AllOption = "<Tous>";

    private readonly List<DataRow> allRows = [];
    private readonly List<DataRow> visibleRows = [];
    private readonly HashSet<DataRow> selectedRows = [];
    private readonly List<SiteOrder> siteOrders = [];
    private readonly System.Windows.Forms.Timer syncTimer = new();
    private readonly Panel syncCardPanel = new();
    private readonly Button btnSyncNow = new();
    private readonly Button btnViewOrders = new();
    private readonly Label lblSyncModeValue = new();
    private readonly Label lblEndpointValue = new();
    private readonly Label lblLastSyncValue = new();
    private readonly Label lblOrdersValue = new();
    private readonly Label lblSyncState = new();

    private AppSettings settings = new();
    private DataTable? productTable;
    private bool suppressSelectionEvents;
    private bool suppressFilterEvents;
    private bool syncInProgress;
    private DateTime? lastSyncAt;
    private string lastSyncSummary = "Jamais";

    public Form1()
    {
        InitializeComponent();
        BuildSyncPanel();
        ConfigureGrid();
        ConfigureFilterInputs();
        ConfigureSyncTimer();
        WireEvents();
    }

    private void WireEvents()
    {
        Shown += async (_, _) => await HandleFirstRunAsync();
        btnSettings.Click += async (_, _) => await OpenSettingsAsync();
        btnReload.Click += async (_, _) => await LoadProductsAsync();
        btnSelectVisible.Click += (_, _) => SelectVisibleRows();
        btnClearSelection.Click += (_, _) => ClearSelection();
        btnExport.Click += async (_, _) => await ExportSelectedRowsAsync();
        btnResetFilters.Click += (_, _) => ResetFilters();
        btnSyncNow.Click += async (_, _) => await TriggerSyncAsync(true);
        btnViewOrders.Click += async (_, _) => await ShowOrdersAsync();
        txtFilter.TextChanged += (_, _) => ApplyFilter();
        cmbStock.SelectedIndexChanged += FilterChanged;
        cmbFamille.SelectedIndexChanged += FilterChanged;
        cmbSousFamille.SelectedIndexChanged += FilterChanged;
        cmbMarque.SelectedIndexChanged += FilterChanged;
        gridProducts.CurrentCellDirtyStateChanged += GridProducts_CurrentCellDirtyStateChanged;
        gridProducts.CellValueChanged += GridProducts_CellValueChanged;
        gridProducts.CellFormatting += GridProducts_CellFormatting;
    }

    private void BuildSyncPanel()
    {
        syncCardPanel.BackColor = Color.White;
        syncCardPanel.Dock = DockStyle.Fill;
        syncCardPanel.Padding = new Padding(20);

        var title = new Label
        {
            AutoSize = true,
            Font = new Font("Segoe UI", 12F, FontStyle.Bold),
            ForeColor = Color.FromArgb(15, 23, 42),
            Text = "Synchronisation web",
            Location = new Point(20, 20),
        };

        lblSyncState.AutoSize = true;
        lblSyncState.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
        lblSyncState.ForeColor = Color.FromArgb(71, 85, 105);
        lblSyncState.Text = "Non configuree";
        lblSyncState.Location = new Point(20, 56);

        var table = new TableLayoutPanel
        {
            Dock = DockStyle.Top,
            Top = 88,
            AutoSize = true,
            ColumnCount = 1,
            RowCount = 8,
            Location = new Point(20, 88),
            Width = 288,
        };

        table.Controls.Add(CreateInfoBlock("Endpoint", lblEndpointValue), 0, 0);
        table.Controls.Add(CreateInfoBlock("Mode", lblSyncModeValue), 0, 1);
        table.Controls.Add(CreateInfoBlock("Derniere synchro", lblLastSyncValue), 0, 2);
        table.Controls.Add(CreateInfoBlock("Commandes web", lblOrdersValue), 0, 3);

        btnSyncNow.Text = "Synchroniser";
        btnSyncNow.Height = 38;
        btnSyncNow.Dock = DockStyle.Top;
        btnSyncNow.BackColor = Color.FromArgb(37, 99, 235);
        btnSyncNow.ForeColor = Color.White;
        btnSyncNow.FlatStyle = FlatStyle.Flat;
        btnSyncNow.FlatAppearance.BorderSize = 0;
        btnSyncNow.Margin = new Padding(0, 10, 0, 0);

        btnViewOrders.Text = "Voir commandes";
        btnViewOrders.Height = 38;
        btnViewOrders.Dock = DockStyle.Top;
        btnViewOrders.BackColor = Color.FromArgb(248, 250, 252);
        btnViewOrders.ForeColor = Color.FromArgb(51, 65, 85);
        btnViewOrders.FlatStyle = FlatStyle.Flat;
        btnViewOrders.FlatAppearance.BorderColor = Color.FromArgb(203, 213, 225);
        btnViewOrders.Margin = new Padding(0, 10, 0, 0);

        var buttonsPanel = new FlowLayoutPanel
        {
            Dock = DockStyle.Top,
            FlowDirection = FlowDirection.TopDown,
            WrapContents = false,
            AutoSize = true,
            Location = new Point(20, 250),
            Width = 288,
        };
        buttonsPanel.Controls.Add(btnSyncNow);
        buttonsPanel.Controls.Add(btnViewOrders);

        syncCardPanel.Controls.Add(buttonsPanel);
        syncCardPanel.Controls.Add(table);
        syncCardPanel.Controls.Add(lblSyncState);
        syncCardPanel.Controls.Add(title);
        rightPanel.Controls.Add(syncCardPanel);

        UpdateSyncPanel();
    }

    private static Panel CreateInfoBlock(string title, Label valueLabel)
    {
        var panel = new Panel
        {
            Height = 58,
            Dock = DockStyle.Top,
            Margin = new Padding(0, 0, 0, 10),
        };

        var titleLabel = new Label
        {
            AutoSize = true,
            Font = new Font("Segoe UI", 8.5F, FontStyle.Bold),
            ForeColor = Color.FromArgb(100, 116, 139),
            Text = title,
            Location = new Point(0, 0),
        };

        valueLabel.AutoSize = true;
        valueLabel.MaximumSize = new Size(270, 0);
        valueLabel.Font = new Font("Segoe UI", 9.5F, FontStyle.Regular);
        valueLabel.ForeColor = Color.FromArgb(30, 41, 59);
        valueLabel.Text = "-";
        valueLabel.Location = new Point(0, 22);

        panel.Controls.Add(titleLabel);
        panel.Controls.Add(valueLabel);
        return panel;
    }

    private void ConfigureSyncTimer()
    {
        syncTimer.Tick += async (_, _) => await TriggerSyncAsync(false);
    }

    private void ConfigureGrid()
    {
        gridProducts.AutoSizeColumnsMode = DataGridViewAutoSizeColumnsMode.None;
        gridProducts.BorderStyle = BorderStyle.None;
        gridProducts.EnableHeadersVisualStyles = false;
        gridProducts.ColumnHeadersBorderStyle = DataGridViewHeaderBorderStyle.None;
        gridProducts.ColumnHeadersDefaultCellStyle.BackColor = Color.FromArgb(30, 64, 175);
        gridProducts.ColumnHeadersDefaultCellStyle.ForeColor = Color.White;
        gridProducts.ColumnHeadersDefaultCellStyle.Font = new Font("Segoe UI", 9.5F, FontStyle.Bold);
        gridProducts.ColumnHeadersHeight = 40;
        gridProducts.ColumnHeadersHeightSizeMode = DataGridViewColumnHeadersHeightSizeMode.DisableResizing;
        gridProducts.DefaultCellStyle.BackColor = Color.White;
        gridProducts.DefaultCellStyle.ForeColor = Color.FromArgb(31, 41, 55);
        gridProducts.DefaultCellStyle.SelectionBackColor = Color.FromArgb(219, 234, 254);
        gridProducts.DefaultCellStyle.SelectionForeColor = Color.FromArgb(17, 24, 39);
        gridProducts.DefaultCellStyle.Padding = new Padding(4, 0, 4, 0);
        gridProducts.AlternatingRowsDefaultCellStyle.BackColor = Color.FromArgb(248, 250, 252);
        gridProducts.GridColor = Color.FromArgb(226, 232, 240);
        gridProducts.RowTemplate.Height = 34;
        gridProducts.MultiSelect = false;
    }

    private void ConfigureFilterInputs()
    {
        PopulateCombo(cmbStock, ["Positif", "= 0", "Negatif"]);
        PopulateCombo(cmbFamille, []);
        PopulateCombo(cmbSousFamille, []);
        PopulateCombo(cmbMarque, []);
    }

    private async Task HandleFirstRunAsync()
    {
        settings = AppSettingsStore.Load();
        UpdateSyncPanel();
        if (!settings.HasDatabasePath() || !settings.HasDepotSelection())
        {
            var result = ShowSettingsDialog();
            if (!result)
            {
                Close();
                return;
            }
        }

        await LoadProductsAsync();
    }

    private async Task OpenSettingsAsync()
    {
        var previousSettings = settings;
        if (!ShowSettingsDialog())
        {
            return;
        }

        UpdateSyncPanel();

        var shouldReloadProducts =
            !string.Equals(previousSettings.DatabasePath, settings.DatabasePath, StringComparison.OrdinalIgnoreCase) ||
            !string.Equals(previousSettings.DepotCode, settings.DepotCode, StringComparison.OrdinalIgnoreCase);

        if (shouldReloadProducts || productTable is null)
        {
            await LoadProductsAsync();
            return;
        }

        await RefreshWebSyncStateAsync(runAutomaticSync: false);
    }

    private bool ShowSettingsDialog()
    {
        using var dialog = new DatabaseSettingsForm(settings);
        if (dialog.ShowDialog(this) != DialogResult.OK)
        {
            return false;
        }

        settings = dialog.Settings;
        AppSettingsStore.Save(settings);
        SetStatus($"Parametres enregistres dans {AppSettingsStore.GetSettingsPath()}");
        return true;
    }

    private async Task LoadProductsAsync()
    {
        if (!settings.HasDatabasePath() || !settings.HasDepotSelection())
        {
            MessageBox.Show(this, "Veuillez configurer la base et selectionner un depot.", "Parametres de connexion", MessageBoxButtons.OK, MessageBoxIcon.Information);
            if (!ShowSettingsDialog())
            {
                return;
            }
        }

        ToggleBusy(true, $"Chargement des produits du depot {settings.DepotCode}...");

        try
        {
            var table = new DataTable();

            await using var connection = new FbConnection(DatabaseSettingsForm.BuildConnectionString(settings));
            await connection.OpenAsync();

            await using var command = new FbCommand(GetProductQuery(), connection);
            command.Parameters.AddWithValue("@DepotCode", settings.DepotCode);
            using var adapter = new FbDataAdapter(command);
            adapter.Fill(table);

            productTable = table;
            allRows.Clear();
            allRows.AddRange(table.Rows.Cast<DataRow>());
            visibleRows.Clear();
            selectedRows.Clear();

            BuildGridColumns(table);
            PopulateDataFilters();
            ApplyFilter();
            SetStatus($"{allRows.Count} produit(s) charges pour le depot {settings.DepotCode}.");
            try
            {
                await RefreshWebSyncStateAsync(runAutomaticSync: true);
            }
            catch (Exception webEx)
            {
                lastSyncSummary = $"Echec - {DateTime.Now:dd/MM/yyyy HH:mm:ss}";
                UpdateSyncPanel("Connexion web indisponible.");
                lblSyncState.ForeColor = Color.FromArgb(220, 38, 38);
                SetStatus($"Produits charges. Synchro web indisponible : {webEx.Message}");
            }
        }
        catch (Exception ex)
        {
            SetStatus("Echec du chargement.");
            MessageBox.Show(this, ex.Message, "Impossible de charger les produits", MessageBoxButtons.OK, MessageBoxIcon.Error);
            UpdateSyncPanel();
        }
        finally
        {
            ToggleBusy(false);
        }
    }

    private async Task RefreshWebSyncStateAsync(bool runAutomaticSync)
    {
        siteOrders.Clear();
        ConfigureSyncAutomation();

        if (!settings.HasWebSyncConfiguration())
        {
            UpdateSyncPanel("Synchronisation web non configuree.");
            return;
        }

        if (runAutomaticSync && settings.AutoSyncEnabled)
        {
            await TriggerSyncAsync(false);
            return;
        }

        await LoadOrdersFromSiteAsync(showMessage: false, updateStatus: false);
        UpdateSyncPanel("Connexion web prete.");
    }

    private void ConfigureSyncAutomation()
    {
        syncTimer.Stop();

        if (settings.HasWebSyncConfiguration() && settings.AutoSyncEnabled)
        {
            syncTimer.Interval = Math.Max(30, settings.SyncIntervalSeconds) * 1000;
            syncTimer.Start();
        }

        UpdateSyncPanel();
    }

    private void UpdateSyncPanel(string? stateMessage = null)
    {
        lblEndpointValue.Text = settings.HasWebSyncConfiguration()
            ? settings.WebEndpoint
            : "Non configure";
        lblSyncModeValue.Text = settings.AutoSyncEnabled
            ? $"Automatique ({FormatSyncInterval(settings.SyncIntervalSeconds)})"
            : "Manuelle";
        lblLastSyncValue.Text = lastSyncSummary;
        lblOrdersValue.Text = siteOrders.Count == 0
            ? "Aucune commande chargee"
            : $"{siteOrders.Count} commande(s) web";
        lblSyncState.Text = stateMessage ?? GetSyncStateText();
        lblSyncState.ForeColor = syncInProgress
            ? Color.FromArgb(37, 99, 235)
            : settings.HasWebSyncConfiguration()
                ? Color.FromArgb(22, 163, 74)
                : Color.FromArgb(148, 163, 184);

        btnSyncNow.Enabled = settings.HasWebSyncConfiguration() && !syncInProgress;
        btnViewOrders.Enabled = settings.HasWebSyncConfiguration() && !syncInProgress;
    }

    private string GetSyncStateText()
    {
        if (syncInProgress)
        {
            return "Synchronisation en cours...";
        }

        if (!settings.HasWebSyncConfiguration())
        {
            return "Non configuree";
        }

        return settings.AutoSyncEnabled
            ? "Synchronisation automatique active"
            : "Synchronisation manuelle";
    }

    private static string FormatSyncInterval(int seconds)
    {
        return seconds switch
        {
            30 => "30 secondes",
            60 => "1 minute",
            120 => "2 minutes",
            180 => "3 minutes",
            240 => "4 minutes",
            300 => "5 minutes",
            600 => "10 minutes",
            1800 => "30 minutes",
            3600 => "1 heure",
            _ => $"{seconds} s",
        };
    }

    private static string GetProductQuery()
    {
        return """
            SELECT
                PRODUIT.RECORDID,
                PRODUIT.CODE_BARRE,
                PRODUIT.REF_PRODUIT,
                PRODUIT.PRODUIT,
                PRODUIT.PV1_HT,
                PRODUIT.PV2_HT,
                PRODUIT.PV3_HT,
                DEPOT2.STOCK,
                PRODUIT.COLISSAGE,
                PRODUIT.FAMILLE,
                PRODUIT.SOUS_FAMILLE,
                PRODUIT.PROMO,
                PRODUIT.D1,
                PRODUIT.D2,
                PRODUIT.PP1_HT,
                PRODUIT.QTE_PROMO,
                PRODUIT.MARQUE
            FROM PRODUIT
            INNER JOIN DEPOT2 ON DEPOT2.CODE_BARRE = PRODUIT.CODE_BARRE
            WHERE COALESCE(SUP, 0) = 0
              AND COALESCE(MAT_PREM, 0) = 0
              AND DEPOT2.CODE_DEPOT = @DepotCode
            ORDER BY PRODUIT.PRODUIT, PRODUIT.REF_PRODUIT
            """;
    }

    private void BuildGridColumns(DataTable table)
    {
        gridProducts.Columns.Clear();

        var selectedColumn = new DataGridViewCheckBoxColumn
        {
            Name = "Selected",
            HeaderText = "",
            AutoSizeMode = DataGridViewAutoSizeColumnMode.None,
            Width = 44,
            MinimumWidth = 44,
            Frozen = true,
        };
        gridProducts.Columns.Add(selectedColumn);

        foreach (DataColumn column in table.Columns)
        {
            var gridColumn = new DataGridViewTextBoxColumn
            {
                Name = column.ColumnName,
                HeaderText = GetColumnLabel(column.ColumnName),
                AutoSizeMode = DataGridViewAutoSizeColumnMode.None,
                ReadOnly = true,
                SortMode = DataGridViewColumnSortMode.NotSortable,
            };

            ConfigureColumnLayout(gridColumn, column.ColumnName);
            gridProducts.Columns.Add(gridColumn);
        }
    }

    private static string GetColumnLabel(string columnName)
    {
        return columnName switch
        {
            "RECORDID" => "ID produit",
            "CODE_BARRE" => "Code-barres",
            "REF_PRODUIT" => "Reference produit",
            "PRODUIT" => "Designation",
            "PV1_HT" => "Prix vente 1 HT",
            "PV2_HT" => "Prix vente 2 HT",
            "PV3_HT" => "Prix vente 3 HT",
            "STOCK" => "Stock depot",
            "COLISSAGE" => "Colisage",
            "FAMILLE" => "Famille",
            "SOUS_FAMILLE" => "Sous famille",
            "PROMO" => "En promotion",
            "D1" => "Date debut promo",
            "D2" => "Date fin promo",
            "PP1_HT" => "Prix achat HT",
            "QTE_PROMO" => "Quantite promo",
            "MARQUE" => "Marque",
            _ => columnName,
        };
    }

    private static void ConfigureColumnLayout(DataGridViewTextBoxColumn column, string columnName)
    {
        switch (columnName)
        {
            case "PRODUIT":
                column.Width = 320;
                column.MinimumWidth = 240;
                break;
            case "CODE_BARRE":
            case "REF_PRODUIT":
                column.Width = 150;
                column.MinimumWidth = 130;
                break;
            case "FAMILLE":
            case "SOUS_FAMILLE":
            case "MARQUE":
                column.Width = 140;
                column.MinimumWidth = 120;
                break;
            default:
                column.Width = 110;
                column.MinimumWidth = 90;
                break;
        }
    }

    private void PopulateDataFilters()
    {
        suppressFilterEvents = true;

        PopulateCombo(cmbFamille, GetDistinctValues("FAMILLE"));
        PopulateCombo(cmbSousFamille, GetDistinctValues("SOUS_FAMILLE"));
        PopulateCombo(cmbMarque, GetDistinctValues("MARQUE"));

        suppressFilterEvents = false;
    }

    private IEnumerable<string> GetDistinctValues(string columnName)
    {
        return allRows
            .Select(row => ReadText(row, columnName))
            .Where(value => !string.IsNullOrWhiteSpace(value))
            .Distinct(StringComparer.OrdinalIgnoreCase)
            .OrderBy(value => value, StringComparer.CurrentCultureIgnoreCase);
    }

    private void PopulateCombo(ComboBox comboBox, IEnumerable<string> items)
    {
        var previous = comboBox.SelectedItem?.ToString();
        comboBox.BeginUpdate();
        comboBox.Items.Clear();
        comboBox.Items.Add(AllOption);

        foreach (var item in items)
        {
            comboBox.Items.Add(item);
        }

        comboBox.SelectedItem = comboBox.Items.Contains(previous ?? string.Empty) ? previous : AllOption;
        comboBox.EndUpdate();
    }

    private void FilterChanged(object? sender, EventArgs e)
    {
        if (!suppressFilterEvents)
        {
            ApplyFilter();
        }
    }

    private void ResetFilters()
    {
        suppressFilterEvents = true;
        txtFilter.Clear();
        cmbStock.SelectedItem = AllOption;
        cmbFamille.SelectedItem = AllOption;
        cmbSousFamille.SelectedItem = AllOption;
        cmbMarque.SelectedItem = AllOption;
        suppressFilterEvents = false;
        ApplyFilter();
    }

    private void ApplyFilter()
    {
        visibleRows.Clear();

        if (productTable is null)
        {
            RefreshGrid();
            return;
        }

        var search = txtFilter.Text.Trim();
        var stockFilter = cmbStock.SelectedItem?.ToString() ?? AllOption;
        var familleFilter = cmbFamille.SelectedItem?.ToString() ?? AllOption;
        var sousFamilleFilter = cmbSousFamille.SelectedItem?.ToString() ?? AllOption;
        var marqueFilter = cmbMarque.SelectedItem?.ToString() ?? AllOption;

        visibleRows.AddRange(allRows.Where(row =>
            RowMatchesSearch(row, search) &&
            RowMatchesStock(row, stockFilter) &&
            RowMatchesValue(row, "FAMILLE", familleFilter) &&
            RowMatchesValue(row, "SOUS_FAMILLE", sousFamilleFilter) &&
            RowMatchesValue(row, "MARQUE", marqueFilter)));

        RefreshGrid();
    }

    private static bool RowMatchesSearch(DataRow row, string search)
    {
        if (string.IsNullOrWhiteSpace(search))
        {
            return true;
        }

        foreach (var value in row.ItemArray)
        {
            if (value == DBNull.Value || value is null)
            {
                continue;
            }

            if (Convert.ToString(value, CultureInfo.CurrentCulture)?.Contains(search, StringComparison.OrdinalIgnoreCase) == true)
            {
                return true;
            }
        }

        return false;
    }

    private static bool RowMatchesStock(DataRow row, string filter)
    {
        if (filter == AllOption)
        {
            return true;
        }

        var stock = ReadDecimal(row, "STOCK");
        return filter switch
        {
            "Positif" => stock > 0,
            "= 0" => stock == 0,
            "Negatif" => stock < 0,
            _ => true,
        };
    }

    private static bool RowMatchesValue(DataRow row, string columnName, string filter)
    {
        if (filter == AllOption)
        {
            return true;
        }

        return string.Equals(ReadText(row, columnName), filter, StringComparison.OrdinalIgnoreCase);
    }

    private void RefreshGrid()
    {
        suppressSelectionEvents = true;
        gridProducts.SuspendLayout();
        gridProducts.Rows.Clear();

        foreach (var row in visibleRows)
        {
            var cells = new object[row.ItemArray.Length + 1];
            cells[0] = selectedRows.Contains(row);

            for (var i = 0; i < row.ItemArray.Length; i++)
            {
                cells[i + 1] = row[i] == DBNull.Value ? string.Empty : row[i];
            }

            var rowIndex = gridProducts.Rows.Add(cells);
            gridProducts.Rows[rowIndex].Tag = row;
        }

        gridProducts.ResumeLayout();
        suppressSelectionEvents = false;
        UpdateCounts();
    }

    private void SelectVisibleRows()
    {
        foreach (var row in visibleRows)
        {
            selectedRows.Add(row);
        }

        RefreshGrid();
    }

    private void ClearSelection()
    {
        selectedRows.Clear();
        RefreshGrid();
    }

    private async Task TriggerSyncAsync(bool manualTrigger)
    {
        if (syncInProgress)
        {
            return;
        }

        if (!settings.HasWebSyncConfiguration())
        {
            MessageBox.Show(this, "Configurez d'abord l'endpoint PME et le token API dans les parametres.", "Synchronisation web", MessageBoxButtons.OK, MessageBoxIcon.Information);
            return;
        }

        if (productTable is null || allRows.Count == 0)
        {
            MessageBox.Show(this, "Aucun produit charge a synchroniser pour le depot selectionne.", "Synchronisation web", MessageBoxButtons.OK, MessageBoxIcon.Information);
            return;
        }

        syncInProgress = true;
        syncTimer.Stop();
        UpdateSyncPanel(manualTrigger ? "Synchronisation manuelle en cours..." : "Synchronisation automatique en cours...");
        SetStatus("Synchronisation web en cours...");

        try
        {
            var payload = BuildSyncProductsPayload();
            var syncResult = await WebSyncService.SyncProductsAsync(settings, payload);
            lastSyncAt = DateTime.Now;
            lastSyncSummary = $"{lastSyncAt:dd/MM/yyyy HH:mm:ss} - {syncResult.InsertedCount} inseres, {syncResult.UpdatedCount} mis a jour";

            await LoadOrdersFromSiteAsync(showMessage: false, updateStatus: false);

            SetStatus(syncResult.Message);
            UpdateSyncPanel("Synchronisation web terminee.");

            if (manualTrigger)
            {
                MessageBox.Show(
                    this,
                    $"{syncResult.Message}\n\nProduits inseres : {syncResult.InsertedCount}\nProduits mis a jour : {syncResult.UpdatedCount}\nCommandes web disponibles : {siteOrders.Count}",
                    "Synchronisation web",
                    MessageBoxButtons.OK,
                    MessageBoxIcon.Information);
            }
        }
        catch (Exception ex)
        {
            lastSyncSummary = $"Echec - {DateTime.Now:dd/MM/yyyy HH:mm:ss}";
            UpdateSyncPanel("Echec de la synchronisation web.");
            lblSyncState.ForeColor = Color.FromArgb(220, 38, 38);
            SetStatus("Echec de la synchronisation web.");

            if (manualTrigger)
            {
                MessageBox.Show(this, ex.Message, "Synchronisation web", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }
        finally
        {
            syncInProgress = false;
            if (settings.HasWebSyncConfiguration() && settings.AutoSyncEnabled)
            {
                syncTimer.Start();
            }

            UpdateSyncPanel();
        }
    }

    private List<WebSyncProduct> BuildSyncProductsPayload()
    {
        return allRows
            .Select(row => new WebSyncProduct
            {
                Reference = GetPrimaryReference(row),
                Designation = GetPrimaryDesignation(row),
                Prix = ReadDecimal(row, "PV1_HT"),
                Pv1 = ReadDecimal(row, "PV1_HT"),
                Pv2 = ReadDecimal(row, "PV2_HT"),
                Pv3 = ReadDecimal(row, "PV3_HT"),
                Stock = Decimal.ToInt32(decimal.Round(ReadDecimal(row, "STOCK"), 0, MidpointRounding.AwayFromZero)),
                Categorie = GetProductCategory(row),
                SousCategorie = GetProductSubCategory(row),
                Marque = GetProductBrand(row),
                AbonneOnly = false,
            })
            .Where(item => !string.IsNullOrWhiteSpace(item.Reference) && !string.IsNullOrWhiteSpace(item.Designation))
            .ToList();
    }

    private static string GetPrimaryReference(DataRow row)
    {
        var reference = ReadText(row, "REF_PRODUIT");
        if (!string.IsNullOrWhiteSpace(reference))
        {
            return reference;
        }

        reference = ReadText(row, "CODE_BARRE");
        if (!string.IsNullOrWhiteSpace(reference))
        {
            return reference;
        }

        return ReadText(row, "RECORDID");
    }

    private static string GetPrimaryDesignation(DataRow row)
    {
        var designation = ReadText(row, "PRODUIT");
        return string.IsNullOrWhiteSpace(designation) ? "Produit sans designation" : designation;
    }

    private static string GetProductCategory(DataRow row)
    {
        var famille = ReadText(row, "FAMILLE");
        return string.IsNullOrWhiteSpace(famille) ? "Non classe" : famille;
    }

    private static string GetProductSubCategory(DataRow row)
    {
        return ReadText(row, "SOUS_FAMILLE");
    }

    private static string GetProductBrand(DataRow row)
    {
        return ReadText(row, "MARQUE");
    }

    private async Task LoadOrdersFromSiteAsync(bool showMessage, bool updateStatus)
    {
        if (!settings.HasWebSyncConfiguration())
        {
            siteOrders.Clear();
            UpdateSyncPanel();
            return;
        }

        var orders = await WebSyncService.FetchOrdersAsync(settings);
        siteOrders.Clear();
        siteOrders.AddRange(orders);

        if (updateStatus)
        {
            SetStatus($"{siteOrders.Count} bon(s) de commande web recuperes.");
        }

        UpdateSyncPanel(siteOrders.Count == 0 ? "Aucune commande web en attente." : "Bons de commande web recuperes.");

        if (showMessage)
        {
            MessageBox.Show(
                this,
                $"{siteOrders.Count} bon(s) de commande web recupere(s).",
                "Bons de commande",
                MessageBoxButtons.OK,
                MessageBoxIcon.Information);
        }
    }

    private async Task ShowOrdersAsync()
    {
        if (!settings.HasWebSyncConfiguration())
        {
            MessageBox.Show(this, "Configurez d'abord l'endpoint PME et le token API dans les parametres.", "Bons de commande", MessageBoxButtons.OK, MessageBoxIcon.Information);
            return;
        }

        try
        {
            SetStatus("Recuperation des bons de commande web...");
            await LoadOrdersFromSiteAsync(showMessage: false, updateStatus: false);
            using var dialog = new OrdersViewerForm(siteOrders.ToList(), settings.DepotCode);
            dialog.ShowDialog(this);
            SetStatus($"{siteOrders.Count} bon(s) de commande web recuperes.");
        }
        catch (Exception ex)
        {
            SetStatus("Echec de recuperation des bons de commande web.");
            MessageBox.Show(this, ex.Message, "Bons de commande", MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
    }

    private async Task ExportSelectedRowsAsync()
    {
        if (productTable is null || selectedRows.Count == 0)
        {
            MessageBox.Show(this, "Selectionnez au moins un produit avant l'export.", "Export Excel", MessageBoxButtons.OK, MessageBoxIcon.Information);
            return;
        }

        using var dialog = new SaveFileDialog
        {
            Title = "Exporter les produits selectionnes",
            Filter = "Excel workbook (*.xlsx)|*.xlsx",
            FileName = $"produits_{DateTime.Now:yyyyMMdd_HHmmss}.xlsx",
            AddExtension = true,
            DefaultExt = "xlsx",
        };

        if (dialog.ShowDialog(this) != DialogResult.OK)
        {
            return;
        }

        ToggleBusy(true, "Export en cours vers Excel...");

        try
        {
            await Task.Run(() => ExportWorkbook(dialog.FileName));
            SetStatus($"Fichier Excel cree : {dialog.FileName}");
            MessageBox.Show(this, $"Export termine.\n\n{dialog.FileName}", "Export Excel", MessageBoxButtons.OK, MessageBoxIcon.Information);
        }
        catch (Exception ex)
        {
            SetStatus("Echec de l'export.");
            MessageBox.Show(this, ex.Message, "Impossible d'exporter vers Excel", MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
        finally
        {
            ToggleBusy(false);
        }
    }

    private void ExportWorkbook(string path)
    {
        if (productTable is null)
        {
            return;
        }

        using var workbook = new XLWorkbook();
        var sheet = workbook.Worksheets.Add("Produits");

        for (var columnIndex = 0; columnIndex < productTable.Columns.Count; columnIndex++)
        {
            var cell = sheet.Cell(1, columnIndex + 1);
            cell.Value = GetColumnLabel(productTable.Columns[columnIndex].ColumnName);
            cell.Style.Font.Bold = true;
            cell.Style.Fill.BackgroundColor = XLColor.FromHtml("#DBEAFE");
            cell.Style.Border.BottomBorder = XLBorderStyleValues.Thin;
        }

        var currentRow = 2;
        foreach (var row in allRows.Where(selectedRows.Contains))
        {
            for (var columnIndex = 0; columnIndex < productTable.Columns.Count; columnIndex++)
            {
                sheet.Cell(currentRow, columnIndex + 1).Value = row[columnIndex] == DBNull.Value
                    ? string.Empty
                    : Convert.ToString(row[columnIndex], CultureInfo.InvariantCulture);
            }

            currentRow++;
        }

        sheet.RangeUsed()?.SetAutoFilter();
        sheet.Columns().AdjustToContents();
        workbook.SaveAs(path);
    }

    private void GridProducts_CurrentCellDirtyStateChanged(object? sender, EventArgs e)
    {
        if (gridProducts.IsCurrentCellDirty)
        {
            gridProducts.CommitEdit(DataGridViewDataErrorContexts.Commit);
        }
    }

    private void GridProducts_CellValueChanged(object? sender, DataGridViewCellEventArgs e)
    {
        if (suppressSelectionEvents || e.RowIndex < 0 || e.ColumnIndex != 0)
        {
            return;
        }

        var gridRow = gridProducts.Rows[e.RowIndex];
        if (gridRow.Tag is not DataRow row)
        {
            return;
        }

        var isChecked = gridRow.Cells[0].Value as bool? == true;
        if (isChecked)
        {
            selectedRows.Add(row);
        }
        else
        {
            selectedRows.Remove(row);
        }

        UpdateCounts();
    }

    private void GridProducts_CellFormatting(object? sender, DataGridViewCellFormattingEventArgs e)
    {
        if (e.RowIndex < 0 || productTable is null || e.ColumnIndex <= 0)
        {
            return;
        }

        var columnName = gridProducts.Columns[e.ColumnIndex].Name;
        if (gridProducts.Rows[e.RowIndex].Tag is not DataRow row)
        {
            return;
        }

        if (columnName == "STOCK")
        {
            var stock = ReadDecimal(row, columnName);
            e.CellStyle!.Font = new Font(gridProducts.Font, FontStyle.Bold);
            e.CellStyle.ForeColor = stock switch
            {
                > 0 => Color.FromArgb(22, 163, 74),
                < 0 => Color.FromArgb(220, 38, 38),
                _ => Color.FromArgb(71, 85, 105),
            };
            e.CellStyle.BackColor = stock switch
            {
                > 0 => Color.FromArgb(240, 253, 244),
                < 0 => Color.FromArgb(254, 242, 242),
                _ => Color.FromArgb(248, 250, 252),
            };
            e.Value = stock.ToString("0.##", CultureInfo.InvariantCulture);
            e.FormattingApplied = true;
            return;
        }

        if (IsPriceColumn(columnName))
        {
            var value = ReadDecimal(row, columnName);
            e.Value = value.ToString("0.00", CultureInfo.InvariantCulture);
            e.CellStyle!.BackColor = Color.FromArgb(239, 246, 255);
            e.CellStyle.ForeColor = Color.FromArgb(30, 64, 175);
            e.FormattingApplied = true;
            return;
        }

        if (columnName is "PROMO" or "QTE_PROMO")
        {
            e.CellStyle!.BackColor = Color.FromArgb(254, 249, 195);
            e.CellStyle.ForeColor = Color.FromArgb(133, 77, 14);
        }
    }

    private void UpdateCounts()
    {
        lblTotalValue.Text = allRows.Count.ToString(CultureInfo.InvariantCulture);
        lblVisibleValue.Text = visibleRows.Count.ToString(CultureInfo.InvariantCulture);
        lblSelectedValue.Text = selectedRows.Count.ToString(CultureInfo.InvariantCulture);
    }

    private void ToggleBusy(bool isBusy, string? message = null)
    {
        UseWaitCursor = isBusy;
        if (isBusy)
        {
            syncTimer.Stop();
        }

        btnSettings.Enabled = !isBusy;
        btnReload.Enabled = !isBusy;
        btnSelectVisible.Enabled = !isBusy;
        btnClearSelection.Enabled = !isBusy;
        btnExport.Enabled = !isBusy;
        btnResetFilters.Enabled = !isBusy;
        txtFilter.Enabled = !isBusy;
        cmbStock.Enabled = !isBusy;
        cmbFamille.Enabled = !isBusy;
        cmbSousFamille.Enabled = !isBusy;
        cmbMarque.Enabled = !isBusy;
        btnSyncNow.Enabled = !isBusy && settings.HasWebSyncConfiguration() && !syncInProgress;
        btnViewOrders.Enabled = !isBusy && settings.HasWebSyncConfiguration() && !syncInProgress;

        if (!string.IsNullOrWhiteSpace(message))
        {
            SetStatus(message);
        }
        else if (!isBusy)
        {
            SetStatus("Pret.");
        }

        if (!isBusy && settings.HasWebSyncConfiguration() && settings.AutoSyncEnabled && !syncInProgress)
        {
            syncTimer.Start();
        }
    }

    private void SetStatus(string message)
    {
        statusLabel.Text = message;
    }

    private static bool IsPriceColumn(string columnName)
    {
        return columnName is "PV1_HT" or "PV2_HT" or "PV3_HT" or "PP1_HT" or "COLISSAGE";
    }

    private static decimal ReadDecimal(DataRow row, string columnName)
    {
        if (!row.Table.Columns.Contains(columnName) || row[columnName] == DBNull.Value)
        {
            return 0m;
        }

        return Convert.ToDecimal(row[columnName], CultureInfo.InvariantCulture);
    }

    private static string ReadText(DataRow row, string columnName)
    {
        if (!row.Table.Columns.Contains(columnName) || row[columnName] == DBNull.Value)
        {
            return string.Empty;
        }

        return Convert.ToString(row[columnName], CultureInfo.CurrentCulture)?.Trim() ?? string.Empty;
    }
}
