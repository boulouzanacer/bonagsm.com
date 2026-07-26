using ClosedXML.Excel;
using FirebirdSql.Data.FirebirdClient;
using System.Data;
using System.Globalization;
using System.Media;

namespace PMESync;

public partial class Form1 : Form
{
    private const string AllOption = "<Tous>";
    private static readonly string[] OrderStatuses = ["en_attente", "validee", "expediee", "livree", "annulee"];
    private static readonly string[] WatchedDatabaseEvents = ["PRODUIT_UPDATE", "TRANSFERT1_UPDATE", "TRANSFERT2_UPDATE"];

    private enum AppSection
    {
        Produits,
        Commandes,
        Evenements,
        Parametres,
    }

    private sealed record RemoteSyncExecutionResult(
        SyncProductsResult SyncResult,
        IReadOnlyList<SiteOrder> Orders,
        string? OrdersWarningMessage);

    private readonly List<DataRow> allRows = [];
    private readonly List<DataRow> visibleRows = [];
    private readonly HashSet<DataRow> selectedRows = [];
    private readonly List<SiteOrder> siteOrders = [];
    private readonly HashSet<int> knownSiteOrderIds = [];
    private readonly System.Windows.Forms.Timer syncTimer = new();
    private readonly System.Windows.Forms.Timer databaseRefreshTimer = new();
    private readonly System.Windows.Forms.Timer databaseEventReconnectTimer = new();
    private readonly Panel syncCardPanel = new();
    private readonly Button btnSyncNow = new();
    private readonly Button btnViewOrders = new();
    private readonly Button btnEventDiagnostics = new();
    private readonly Label lblSyncModeValue = new();
    private readonly Label lblEndpointValue = new();
    private readonly Label lblLastSyncValue = new();
    private readonly Label lblOrdersValue = new();
    private readonly Label lblSyncState = new();
    private readonly List<string> databaseEventLogEntries = [];
    private readonly TableLayoutPanel shellLayout = new();
    private readonly Panel sidebarPanel = new();
    private readonly FlowLayoutPanel navButtonsPanel = new();
    private readonly Panel sectionHostPanel = new();
    private readonly Panel productsSectionPanel = new();
    private readonly Panel ordersSectionPanel = new();
    private readonly Panel eventsSectionPanel = new();
    private readonly Panel settingsSectionPanel = new();
    private readonly Button btnNavProducts = new();
    private readonly Button btnNavOrders = new();
    private readonly Button btnNavEvents = new();
    private readonly Button btnNavSettings = new();
    private readonly DataGridView gridOrdersSection = new();
    private readonly DataGridView gridOrderLinesSection = new();
    private readonly TextBox txtOrderNotesSection = new();
    private readonly Label lblOrdersSectionSummary = new();
    private readonly Label lblSelectedOrderTotalValue = new();
    private readonly Label lblSelectedOrderClientValue = new();
    private readonly TextBox txtEventLogSection = new();
    private readonly Label lblEventSectionState = new();
    private readonly Button btnClearEventLogSection = new();
    private readonly Button btnReconnectEventsSection = new();
    private readonly Label lblSettingsDatabaseValue = new();
    private readonly Label lblSettingsDepotValue = new();
    private readonly Label lblSettingsWebValue = new();
    private readonly Label lblSettingsSyncValue = new();
    private readonly Label lblSettingsEventValue = new();
    private readonly Button btnOpenSettingsSection = new();
    private readonly ToolTip headerToolTip = new();
    private readonly NotifyIcon trayIcon = new();
    private readonly ContextMenuStrip trayMenu = new();
    private readonly bool startMinimizedToTray;

    private AppSettings settings = new();
    private DataTable? productTable;
    private bool suppressSelectionEvents;
    private bool suppressFilterEvents;
    private bool suppressOrderStatusEvents;
    private bool syncInProgress;
    private bool isBusy;
    private bool pendingDatabaseRefresh;
    private bool ordersInitialized;
    private DateTime? lastSyncAt;
    private string lastSyncSummary = "Jamais";
    private FbRemoteEvent? databaseEvents;
    private string? databaseEventConnectionSignature;
    private EventDiagnosticsForm? eventDiagnosticsForm;
    private AppSection currentSection = AppSection.Produits;
    private bool allowExit;

    public Form1(bool startMinimizedToTray = false)
    {
        this.startMinimizedToTray = startMinimizedToTray;
        InitializeComponent();
        Icon = AppIconProvider.GetApplicationIcon();
        EventLogStore.Initialize();
        BuildNavigationShell();
        BuildSyncPanel();
        ConfigureGrid();
        ConfigureFilterInputs();
        ConfigureSyncTimer();
        ConfigureDatabaseRefreshTimer();
        ConfigureDatabaseEventReconnectTimer();
        LoadPersistedEventLogs();
        ConfigureTrayIcon();
        WireEvents();
    }

    private void WireEvents()
    {
        Shown += async (_, _) => await HandleFirstRunAsync();
        FormClosing += Form1_FormClosing;
        btnSelectVisible.Click += (_, _) => SelectVisibleRows();
        btnClearSelection.Click += (_, _) => ClearSelection();
        btnExport.Click += async (_, _) => await ExportSelectedRowsAsync();
        btnResetFilters.Click += (_, _) => ResetFilters();
        btnSyncNow.Click += async (_, _) => await TriggerSyncAsync(true);
        btnViewOrders.Click += async (_, _) => await OpenOrdersSectionAsync();
        txtFilter.TextChanged += (_, _) => ApplyFilter();
        cmbStock.SelectedIndexChanged += FilterChanged;
        cmbFamille.SelectedIndexChanged += FilterChanged;
        cmbSousFamille.SelectedIndexChanged += FilterChanged;
        cmbMarque.SelectedIndexChanged += FilterChanged;
        gridProducts.CurrentCellDirtyStateChanged += GridProducts_CurrentCellDirtyStateChanged;
        gridProducts.CellValueChanged += GridProducts_CellValueChanged;
        gridProducts.CellFormatting += GridProducts_CellFormatting;
        btnNavProducts.Click += (_, _) => ShowSection(AppSection.Produits);
        btnNavOrders.Click += async (_, _) => await OpenOrdersSectionAsync();
        btnNavEvents.Click += (_, _) => ShowSection(AppSection.Evenements);
        btnNavSettings.Click += (_, _) => ShowSection(AppSection.Parametres);
        gridOrdersSection.SelectionChanged += (_, _) => LoadSelectedOrderLinesInSection();
        gridOrdersSection.CurrentCellDirtyStateChanged += GridOrdersSection_CurrentCellDirtyStateChanged;
        gridOrdersSection.CellValueChanged += GridOrdersSection_CellValueChanged;
        gridOrdersSection.DataError += GridOrdersSection_DataError;
        btnClearEventLogSection.Click += (_, _) => ClearEventLogs();
        btnReconnectEventsSection.Click += async (_, _) => await RestartDatabaseEventListenerAsync(force: true);
        btnOpenSettingsSection.Click += async (_, _) => await OpenSettingsAsync();
    }

    private void ConfigureTrayIcon()
    {
        var openItem = new ToolStripMenuItem("Ouvrir");
        openItem.Click += (_, _) => RestoreFromTray();

        var quitItem = new ToolStripMenuItem("Quitter");
        quitItem.Click += (_, _) => ExitApplicationFromTray();

        trayMenu.Items.Add(openItem);
        trayMenu.Items.Add(quitItem);

        trayIcon.ContextMenuStrip = trayMenu;
        trayIcon.Icon = AppIconProvider.GetApplicationIcon();
        trayIcon.Text = "PMESync";
        trayIcon.Visible = false;
        trayIcon.DoubleClick += (_, _) => RestoreFromTray();
    }

    private void BuildNavigationShell()
    {
        btnEventDiagnostics.Visible = false;
        btnReload.Visible = false;

        contentPanel.Controls.Clear();

        shellLayout.Dock = DockStyle.Fill;
        shellLayout.ColumnCount = 2;
        shellLayout.RowCount = 1;
        shellLayout.ColumnStyles.Add(new ColumnStyle(SizeType.Absolute, 240F));
        shellLayout.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 100F));
        shellLayout.RowStyles.Add(new RowStyle(SizeType.Percent, 100F));

        sidebarPanel.Dock = DockStyle.Fill;
        sidebarPanel.BackColor = Color.FromArgb(15, 23, 42);
        sidebarPanel.Padding = new Padding(20, 24, 20, 20);

        var brandTitle = new Label
        {
            AutoSize = true,
            Font = new Font("Segoe UI", 16F, FontStyle.Bold),
            ForeColor = Color.White,
            Text = "PMESync",
            Location = new Point(20, 24),
        };

        var brandSubtitle = new Label
        {
            AutoSize = true,
            Font = new Font("Segoe UI", 9.5F, FontStyle.Regular),
            ForeColor = Color.FromArgb(148, 163, 184),
            Text = "Desktop Sync",
            Location = new Point(22, 58),
        };

        var menuCaption = new Label
        {
            AutoSize = true,
            Font = new Font("Segoe UI", 8.5F, FontStyle.Bold),
            ForeColor = Color.FromArgb(148, 163, 184),
            Text = "NAVIGATION",
            Location = new Point(20, 108),
        };

        navButtonsPanel.Dock = DockStyle.Top;
        navButtonsPanel.FlowDirection = FlowDirection.TopDown;
        navButtonsPanel.WrapContents = false;
        navButtonsPanel.AutoSize = true;
        navButtonsPanel.Location = new Point(20, 136);
        navButtonsPanel.Padding = new Padding(0, 0, 0, 12);

        ConfigureNavButton(btnNavProducts, "Produits");
        ConfigureNavButton(btnNavOrders, "Commandes");
        ConfigureNavButton(btnNavEvents, "Evenements");
        ConfigureNavButton(btnNavSettings, "Parametres");

        navButtonsPanel.Controls.Add(btnNavProducts);
        navButtonsPanel.Controls.Add(btnNavOrders);
        navButtonsPanel.Controls.Add(btnNavEvents);
        navButtonsPanel.Controls.Add(btnNavSettings);

        sidebarPanel.Controls.Add(navButtonsPanel);
        sidebarPanel.Controls.Add(menuCaption);
        sidebarPanel.Controls.Add(brandSubtitle);
        sidebarPanel.Controls.Add(brandTitle);

        sectionHostPanel.Dock = DockStyle.Fill;
        sectionHostPanel.BackColor = Color.Transparent;

        productsSectionPanel.Dock = DockStyle.Fill;
        bodyLayout.Dock = DockStyle.Fill;
        productsSectionPanel.Controls.Add(bodyLayout);

        BuildOrdersSection();
        BuildEventsSection();
        BuildSettingsSection();

        sectionHostPanel.Controls.Add(settingsSectionPanel);
        sectionHostPanel.Controls.Add(eventsSectionPanel);
        sectionHostPanel.Controls.Add(ordersSectionPanel);
        sectionHostPanel.Controls.Add(productsSectionPanel);

        shellLayout.Controls.Add(sidebarPanel, 0, 0);
        shellLayout.Controls.Add(sectionHostPanel, 1, 0);
        contentPanel.Controls.Add(shellLayout);

        ShowSection(AppSection.Produits);
    }

    private void ConfigureNavButton(Button button, string text)
    {
        button.Width = 196;
        button.Height = 48;
        button.Margin = new Padding(0, 0, 0, 10);
        button.FlatStyle = FlatStyle.Flat;
        button.FlatAppearance.BorderSize = 0;
        button.BackColor = Color.Transparent;
        button.ForeColor = Color.FromArgb(226, 232, 240);
        button.Font = new Font("Segoe UI Semibold", 10F, FontStyle.Bold);
        button.TextAlign = ContentAlignment.MiddleLeft;
        button.Padding = new Padding(16, 0, 0, 0);
        button.Text = text;
        button.Cursor = Cursors.Hand;
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

    private void BuildOrdersSection()
    {
        ordersSectionPanel.Dock = DockStyle.Fill;
        ordersSectionPanel.Visible = false;

        var layout = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            ColumnCount = 1,
            RowCount = 3,
            BackColor = Color.Transparent,
            Padding = new Padding(0),
        };
        layout.RowStyles.Add(new RowStyle(SizeType.Absolute, 124F));
        layout.RowStyles.Add(new RowStyle(SizeType.Percent, 62F));
        layout.RowStyles.Add(new RowStyle(SizeType.Percent, 38F));

        var summaryCard = CreateSectionCard(124);
        summaryCard.Padding = new Padding(16, 12, 16, 12);
        var summaryLayout = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            ColumnCount = 2,
            RowCount = 1,
            BackColor = Color.Transparent,
        };
        summaryLayout.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 100F));
        summaryLayout.ColumnStyles.Add(new ColumnStyle(SizeType.Absolute, 260F));

        var summaryTextPanel = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            BackColor = Color.Transparent,
            ColumnCount = 1,
            RowCount = 2,
            Margin = new Padding(0, 4, 12, 0),
            Padding = new Padding(0),
        };
        summaryTextPanel.RowStyles.Add(new RowStyle(SizeType.AutoSize));
        summaryTextPanel.RowStyles.Add(new RowStyle(SizeType.AutoSize));

        lblOrdersSectionSummary.AutoSize = true;
        lblOrdersSectionSummary.Font = new Font("Segoe UI", 10.5F, FontStyle.Bold);
        lblOrdersSectionSummary.ForeColor = Color.FromArgb(30, 41, 59);
        lblOrdersSectionSummary.Margin = new Padding(0, 0, 0, 6);
        lblOrdersSectionSummary.Text = "Aucune commande web chargee.";

        lblSelectedOrderClientValue.AutoSize = true;
        lblSelectedOrderClientValue.Font = new Font("Segoe UI", 9F, FontStyle.Regular);
        lblSelectedOrderClientValue.ForeColor = Color.FromArgb(71, 85, 105);
        lblSelectedOrderClientValue.Margin = new Padding(0);
        lblSelectedOrderClientValue.Text = "Client : -";

        summaryTextPanel.Controls.Add(lblOrdersSectionSummary, 0, 0);
        summaryTextPanel.Controls.Add(lblSelectedOrderClientValue, 0, 1);

        var totalPanel = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            BackColor = Color.FromArgb(239, 246, 255),
            Padding = new Padding(16, 12, 16, 12),
            Margin = new Padding(0),
            ColumnCount = 1,
            RowCount = 2,
        };
        totalPanel.RowStyles.Add(new RowStyle(SizeType.AutoSize));
        totalPanel.RowStyles.Add(new RowStyle(SizeType.AutoSize));

        var totalTitle = new Label
        {
            AutoSize = true,
            Font = new Font("Segoe UI", 8.5F, FontStyle.Bold),
            ForeColor = Color.FromArgb(37, 99, 235),
            Text = "TOTAL COMMANDE",
            Margin = new Padding(0, 0, 0, 6),
        };

        lblSelectedOrderTotalValue.AutoSize = true;
        lblSelectedOrderTotalValue.Font = new Font("Segoe UI", 20F, FontStyle.Bold);
        lblSelectedOrderTotalValue.ForeColor = Color.FromArgb(15, 23, 42);
        lblSelectedOrderTotalValue.Margin = new Padding(0);
        lblSelectedOrderTotalValue.Text = "0.00";

        totalPanel.Controls.Add(totalTitle, 0, 0);
        totalPanel.Controls.Add(lblSelectedOrderTotalValue, 0, 1);

        summaryLayout.Controls.Add(summaryTextPanel, 0, 0);
        summaryLayout.Controls.Add(totalPanel, 1, 0);
        summaryCard.Controls.Add(summaryLayout);

        var ordersCard = CreateSectionCard();
        ordersCard.Padding = new Padding(1);
        ConfigureOrdersSectionGrid();
        ordersCard.Controls.Add(gridOrdersSection);

        var detailsLayout = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            ColumnCount = 1,
            RowCount = 2,
            BackColor = Color.Transparent,
        };
        detailsLayout.RowStyles.Add(new RowStyle(SizeType.Percent, 66F));
        detailsLayout.RowStyles.Add(new RowStyle(SizeType.Percent, 34F));

        var linesCard = CreateSectionCard();
        linesCard.Padding = new Padding(1);
        ConfigureOrderLinesSectionGrid();
        linesCard.Controls.Add(gridOrderLinesSection);

        var notesCard = CreateSectionCard();
        notesCard.Padding = new Padding(18);
        var notesTitle = new Label
        {
            AutoSize = true,
            Font = new Font("Segoe UI", 10F, FontStyle.Bold),
            ForeColor = Color.FromArgb(15, 23, 42),
            Text = "Notes commande",
            Location = new Point(18, 16),
        };
        txtOrderNotesSection.Dock = DockStyle.Fill;
        txtOrderNotesSection.Multiline = true;
        txtOrderNotesSection.ReadOnly = true;
        txtOrderNotesSection.ScrollBars = ScrollBars.Vertical;
        txtOrderNotesSection.BorderStyle = BorderStyle.None;
        txtOrderNotesSection.BackColor = Color.White;
        txtOrderNotesSection.Font = new Font("Segoe UI", 9.5F);
        txtOrderNotesSection.Location = new Point(18, 44);
        txtOrderNotesSection.Text = "Aucune commande chargee.";
        notesCard.Controls.Add(txtOrderNotesSection);
        notesCard.Controls.Add(notesTitle);

        detailsLayout.Controls.Add(linesCard, 0, 0);
        detailsLayout.Controls.Add(notesCard, 0, 1);

        layout.Controls.Add(summaryCard, 0, 0);
        layout.Controls.Add(ordersCard, 0, 1);
        layout.Controls.Add(detailsLayout, 0, 2);

        ordersSectionPanel.Controls.Add(layout);
    }

    private void BuildEventsSection()
    {
        eventsSectionPanel.Dock = DockStyle.Fill;
        eventsSectionPanel.Visible = false;

        var layout = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            ColumnCount = 1,
            RowCount = 2,
            BackColor = Color.Transparent,
        };
        layout.RowStyles.Add(new RowStyle(SizeType.Absolute, 108F));
        layout.RowStyles.Add(new RowStyle(SizeType.Percent, 100F));

        var topCard = CreateSectionCard(108);
        lblEventSectionState.AutoSize = true;
        lblEventSectionState.Font = new Font("Segoe UI", 10.5F, FontStyle.Bold);
        lblEventSectionState.ForeColor = Color.FromArgb(30, 41, 59);
        lblEventSectionState.Location = new Point(20, 20);
        lblEventSectionState.Text = "Ecoute Firebird en attente.";

        var eventsHint = new Label
        {
            AutoSize = false,
            Size = new Size(740, 40),
            Font = new Font("Segoe UI", 9F),
            ForeColor = Color.FromArgb(100, 116, 139),
            Text = $"Evenements surveilles : {string.Join(", ", WatchedDatabaseEvents)}",
            Location = new Point(20, 48),
        };

        btnClearEventLogSection.Text = "Vider le journal";
        btnClearEventLogSection.Anchor = AnchorStyles.Top | AnchorStyles.Right;
        btnClearEventLogSection.BackColor = Color.FromArgb(248, 250, 252);
        btnClearEventLogSection.FlatAppearance.BorderColor = Color.FromArgb(203, 213, 225);
        btnClearEventLogSection.FlatStyle = FlatStyle.Flat;
        btnClearEventLogSection.ForeColor = Color.FromArgb(51, 65, 85);
        btnClearEventLogSection.Size = new Size(140, 36);
        btnClearEventLogSection.Location = new Point(802, 20);

        btnReconnectEventsSection.Text = "Reconnecter";
        btnReconnectEventsSection.Anchor = AnchorStyles.Top | AnchorStyles.Right;
        btnReconnectEventsSection.BackColor = Color.FromArgb(37, 99, 235);
        btnReconnectEventsSection.FlatAppearance.BorderSize = 0;
        btnReconnectEventsSection.FlatStyle = FlatStyle.Flat;
        btnReconnectEventsSection.ForeColor = Color.White;
        btnReconnectEventsSection.Size = new Size(120, 36);
        btnReconnectEventsSection.Location = new Point(950, 20);

        topCard.Controls.Add(lblEventSectionState);
        topCard.Controls.Add(eventsHint);
        topCard.Controls.Add(btnClearEventLogSection);
        topCard.Controls.Add(btnReconnectEventsSection);

        var logCard = CreateSectionCard();
        logCard.Padding = new Padding(1);
        txtEventLogSection.Dock = DockStyle.Fill;
        txtEventLogSection.Multiline = true;
        txtEventLogSection.ReadOnly = true;
        txtEventLogSection.ScrollBars = ScrollBars.Both;
        txtEventLogSection.WordWrap = false;
        txtEventLogSection.BorderStyle = BorderStyle.None;
        txtEventLogSection.BackColor = Color.White;
        txtEventLogSection.Font = new Font("Consolas", 10F);
        logCard.Controls.Add(txtEventLogSection);

        layout.Controls.Add(topCard, 0, 0);
        layout.Controls.Add(logCard, 0, 1);
        eventsSectionPanel.Controls.Add(layout);
    }

    private void BuildSettingsSection()
    {
        settingsSectionPanel.Dock = DockStyle.Fill;
        settingsSectionPanel.Visible = false;

        var container = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            ColumnCount = 2,
            RowCount = 4,
            BackColor = Color.Transparent,
        };
        container.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 50F));
        container.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 50F));
        container.RowStyles.Add(new RowStyle(SizeType.Absolute, 110F));
        container.RowStyles.Add(new RowStyle(SizeType.Percent, 33.3333F));
        container.RowStyles.Add(new RowStyle(SizeType.Percent, 33.3333F));
        container.RowStyles.Add(new RowStyle(SizeType.Percent, 33.3333F));

        var introCard = CreateSectionCard(110);
        introCard.Margin = new Padding(0, 0, 0, 18);
        var introLayout = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            BackColor = Color.Transparent,
            ColumnCount = 2,
            RowCount = 2,
        };
        introLayout.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 100F));
        introLayout.ColumnStyles.Add(new ColumnStyle(SizeType.Absolute, 220F));
        introLayout.RowStyles.Add(new RowStyle(SizeType.Absolute, 42F));
        introLayout.RowStyles.Add(new RowStyle(SizeType.Percent, 100F));
        var introTitle = new Label
        {
            AutoSize = true,
            Font = new Font("Segoe UI", 13F, FontStyle.Bold),
            ForeColor = Color.FromArgb(15, 23, 42),
            Text = "Configuration PMESync",
            Anchor = AnchorStyles.Left,
            Margin = new Padding(0, 4, 0, 0),
        };
        var introText = new Label
        {
            AutoSize = false,
            Dock = DockStyle.Fill,
            Font = new Font("Segoe UI", 9.5F),
            ForeColor = Color.FromArgb(71, 85, 105),
            Text = "Gerez la connexion Firebird, le depot actif, la synchronisation web et l'etat de l'ecoute des evenements.",
            Margin = new Padding(0, 8, 16, 0),
        };
        btnOpenSettingsSection.Text = "Modifier les parametres";
        btnOpenSettingsSection.BackColor = Color.FromArgb(37, 99, 235);
        btnOpenSettingsSection.FlatAppearance.BorderSize = 0;
        btnOpenSettingsSection.FlatStyle = FlatStyle.Flat;
        btnOpenSettingsSection.ForeColor = Color.White;
        btnOpenSettingsSection.Size = new Size(200, 38);
        btnOpenSettingsSection.Anchor = AnchorStyles.Top | AnchorStyles.Right;
        btnOpenSettingsSection.Margin = new Padding(0);
        introLayout.Controls.Add(introTitle, 0, 0);
        introLayout.Controls.Add(btnOpenSettingsSection, 1, 0);
        introLayout.Controls.Add(introText, 0, 1);
        introLayout.SetColumnSpan(introText, 2);
        introCard.Controls.Add(introLayout);
        container.Controls.Add(introCard, 0, 0);
        container.SetColumnSpan(introCard, 2);

        container.Controls.Add(CreateSettingsInfoCard("Base de donnees", lblSettingsDatabaseValue), 0, 1);
        container.Controls.Add(CreateSettingsInfoCard("Depot selectionne", lblSettingsDepotValue), 1, 1);
        container.Controls.Add(CreateSettingsInfoCard("Endpoint web", lblSettingsWebValue), 0, 2);
        container.Controls.Add(CreateSettingsInfoCard("Mode synchronisation", lblSettingsSyncValue), 1, 2);
        var eventCard = CreateSettingsInfoCard("Etat des evenements Firebird", lblSettingsEventValue);
        container.Controls.Add(eventCard, 0, 3);
        container.SetColumnSpan(eventCard, 2);

        settingsSectionPanel.Controls.Add(container);
    }

    private Panel CreateSettingsInfoCard(string title, Label valueLabel)
    {
        var card = CreateSectionCard();
        card.Padding = new Padding(20);

        var titleLabel = new Label
        {
            AutoSize = true,
            Font = new Font("Segoe UI", 9.5F, FontStyle.Bold),
            ForeColor = Color.FromArgb(100, 116, 139),
            Text = title,
            Location = new Point(20, 20),
        };

        valueLabel.AutoSize = false;
        valueLabel.Size = new Size(420, 80);
        valueLabel.Font = new Font("Segoe UI", 11F, FontStyle.Bold);
        valueLabel.ForeColor = Color.FromArgb(15, 23, 42);
        valueLabel.Location = new Point(20, 54);
        valueLabel.Text = "-";

        card.Controls.Add(titleLabel);
        card.Controls.Add(valueLabel);
        return card;
    }

    private static Panel CreateSectionCard(int? fixedHeight = null)
    {
        var card = new Panel
        {
            Dock = DockStyle.Fill,
            BackColor = Color.White,
            Margin = new Padding(0, 0, 0, 18),
            Padding = new Padding(20),
        };

        if (fixedHeight.HasValue)
        {
            card.Height = fixedHeight.Value;
        }

        return card;
    }

    private void ConfigureOrdersSectionGrid()
    {
        gridOrdersSection.Dock = DockStyle.Fill;
        gridOrdersSection.AllowUserToAddRows = false;
        gridOrdersSection.AllowUserToDeleteRows = false;
        gridOrdersSection.AllowUserToResizeRows = false;
        gridOrdersSection.MultiSelect = false;
        gridOrdersSection.ReadOnly = false;
        gridOrdersSection.RowHeadersVisible = false;
        gridOrdersSection.SelectionMode = DataGridViewSelectionMode.FullRowSelect;
        gridOrdersSection.BorderStyle = BorderStyle.None;
        gridOrdersSection.BackgroundColor = Color.White;
        gridOrdersSection.EnableHeadersVisualStyles = false;
        gridOrdersSection.ColumnHeadersDefaultCellStyle.BackColor = Color.FromArgb(30, 64, 175);
        gridOrdersSection.ColumnHeadersDefaultCellStyle.ForeColor = Color.White;
        gridOrdersSection.ColumnHeadersDefaultCellStyle.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
        gridOrdersSection.ColumnHeadersHeight = 38;
        gridOrdersSection.ColumnHeadersHeightSizeMode = DataGridViewColumnHeadersHeightSizeMode.DisableResizing;
        gridOrdersSection.AlternatingRowsDefaultCellStyle.BackColor = Color.FromArgb(248, 250, 252);
        gridOrdersSection.RowTemplate.Height = 32;
        gridOrdersSection.Columns.Add(new DataGridViewTextBoxColumn { Name = "Id", HeaderText = "Commande", Width = 110, ReadOnly = true });
        gridOrdersSection.Columns.Add(new DataGridViewTextBoxColumn { Name = "ClientNom", HeaderText = "Client", Width = 210, ReadOnly = true });
        gridOrdersSection.Columns.Add(new DataGridViewTextBoxColumn { Name = "DateCommande", HeaderText = "Date", Width = 160, ReadOnly = true });
        gridOrdersSection.Columns.Add(new DataGridViewComboBoxColumn
        {
            Name = "Statut",
            HeaderText = "Statut",
            Width = 120,
            FlatStyle = FlatStyle.Flat,
            DisplayStyle = DataGridViewComboBoxDisplayStyle.DropDownButton,
            DataSource = OrderStatuses.ToArray(),
        });
        gridOrdersSection.Columns.Add(new DataGridViewTextBoxColumn { Name = "MontantTotal", HeaderText = "Montant total", Width = 140, ReadOnly = true });
        gridOrdersSection.Columns.Add(new DataGridViewTextBoxColumn { Name = "AdresseLivraison", HeaderText = "Adresse livraison", AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill, ReadOnly = true });
        gridOrdersSection.Columns.Add(new DataGridViewTextBoxColumn { Name = "SyncedPme", HeaderText = "Sync PME", Width = 90, ReadOnly = true });
    }

    private void ConfigureOrderLinesSectionGrid()
    {
        gridOrderLinesSection.Dock = DockStyle.Fill;
        gridOrderLinesSection.AllowUserToAddRows = false;
        gridOrderLinesSection.AllowUserToDeleteRows = false;
        gridOrderLinesSection.AllowUserToResizeRows = false;
        gridOrderLinesSection.MultiSelect = false;
        gridOrderLinesSection.ReadOnly = true;
        gridOrderLinesSection.RowHeadersVisible = false;
        gridOrderLinesSection.SelectionMode = DataGridViewSelectionMode.FullRowSelect;
        gridOrderLinesSection.BorderStyle = BorderStyle.None;
        gridOrderLinesSection.BackgroundColor = Color.White;
        gridOrderLinesSection.EnableHeadersVisualStyles = false;
        gridOrderLinesSection.ColumnHeadersDefaultCellStyle.BackColor = Color.FromArgb(14, 165, 233);
        gridOrderLinesSection.ColumnHeadersDefaultCellStyle.ForeColor = Color.White;
        gridOrderLinesSection.ColumnHeadersDefaultCellStyle.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
        gridOrderLinesSection.ColumnHeadersHeight = 36;
        gridOrderLinesSection.ColumnHeadersHeightSizeMode = DataGridViewColumnHeadersHeightSizeMode.DisableResizing;
        gridOrderLinesSection.AlternatingRowsDefaultCellStyle.BackColor = Color.FromArgb(248, 250, 252);
        gridOrderLinesSection.RowTemplate.Height = 30;
        gridOrderLinesSection.Columns.Add(new DataGridViewTextBoxColumn { Name = "Reference", HeaderText = "Reference", Width = 150 });
        gridOrderLinesSection.Columns.Add(new DataGridViewTextBoxColumn { Name = "Designation", HeaderText = "Designation", AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill });
        gridOrderLinesSection.Columns.Add(new DataGridViewTextBoxColumn { Name = "Quantite", HeaderText = "Quantite", Width = 90 });
        gridOrderLinesSection.Columns.Add(new DataGridViewTextBoxColumn { Name = "PrixUnitaire", HeaderText = "Prix unitaire", Width = 120 });
        gridOrderLinesSection.Columns.Add(new DataGridViewTextBoxColumn { Name = "SousTotal", HeaderText = "Sous-total", Width = 120 });
    }

    private void ConfigureSyncTimer()
    {
        syncTimer.Tick += async (_, _) => await ProcessAutomaticWebCycleAsync();
    }

    private void ConfigureDatabaseRefreshTimer()
    {
        databaseRefreshTimer.Interval = 1000;
        databaseRefreshTimer.Tick += async (_, _) => await ProcessPendingDatabaseRefreshAsync();
    }

    private void ConfigureDatabaseEventReconnectTimer()
    {
        databaseEventReconnectTimer.Interval = 5000;
        databaseEventReconnectTimer.Tick += async (_, _) =>
        {
            databaseEventReconnectTimer.Stop();
            await RestartDatabaseEventListenerAsync(force: true);
        };
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

    private async Task OpenOrdersSectionAsync()
    {
        ShowSection(AppSection.Commandes);

        if (!settings.HasWebSyncConfiguration())
        {
            RefreshOrdersSection();
            return;
        }

        try
        {
            SetStatus("Recuperation des bons de commande web...");
            await LoadOrdersFromSiteAsync(showMessage: false, updateStatus: false);
            SetStatus($"{siteOrders.Count} bon(s) de commande web recuperes.");
        }
        catch (Exception ex)
        {
            SetStatus("Echec de recuperation des bons de commande web.");
            MessageBox.Show(this, ex.Message, "Bons de commande", MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
    }

    private void ShowSection(AppSection section)
    {
        currentSection = section;
        productsSectionPanel.Visible = section == AppSection.Produits;
        ordersSectionPanel.Visible = section == AppSection.Commandes;
        eventsSectionPanel.Visible = section == AppSection.Evenements;
        settingsSectionPanel.Visible = section == AppSection.Parametres;

        UpdateNavigationState();
        UpdateSectionHeader();

        if (section == AppSection.Commandes)
        {
            RefreshOrdersSection();
        }
        else if (section == AppSection.Evenements)
        {
            SyncEventSection();
        }
        else if (section == AppSection.Parametres)
        {
            RefreshSettingsSection();
        }
    }

    private void UpdateNavigationState()
    {
        StyleNavButton(btnNavProducts, currentSection == AppSection.Produits, Color.FromArgb(37, 99, 235));
        StyleNavButton(btnNavOrders, currentSection == AppSection.Commandes, Color.FromArgb(16, 185, 129));
        StyleNavButton(btnNavEvents, currentSection == AppSection.Evenements, Color.FromArgb(245, 158, 11));
        StyleNavButton(btnNavSettings, currentSection == AppSection.Parametres, Color.FromArgb(168, 85, 247));
    }

    private static void StyleNavButton(Button button, bool active, Color accent)
    {
        button.BackColor = active ? accent : Color.Transparent;
        button.ForeColor = active ? Color.White : Color.FromArgb(226, 232, 240);
    }

    private void UpdateSectionHeader()
    {
        switch (currentSection)
        {
            case AppSection.Commandes:
                lblTitle.Text = "Commandes web";
                lblSubtitle.Text = "Consultez les bons de commande du site web et leurs lignes detaillees.";
                break;
            case AppSection.Evenements:
                lblTitle.Text = "Evenements Firebird";
                lblSubtitle.Text = "Surveillez en direct l'etat de l'ecoute Firebird et les rafraichissements automatiques.";
                break;
            case AppSection.Parametres:
                lblTitle.Text = "Parametres";
                lblSubtitle.Text = "Reglez la connexion Firebird, le depot actif et la synchronisation web.";
                break;
            default:
                lblTitle.Text = "PMESync";
                lblSubtitle.Text = "Navigateur produits avec filtres par stock, famille, sous-famille et marque.";
                break;
        }
    }

    private void RefreshOrdersSection()
    {
        var selectedOrderId = gridOrdersSection.CurrentRow?.Tag is SiteOrder selectedOrder ? selectedOrder.Id : (int?)null;

        suppressOrderStatusEvents = true;
        gridOrdersSection.Rows.Clear();
        gridOrderLinesSection.Rows.Clear();

        if (!settings.HasWebSyncConfiguration())
        {
            lblOrdersSectionSummary.Text = "Configurez l'endpoint PME et le token API pour voir les commandes.";
            txtOrderNotesSection.Text = "Synchronisation web non configuree.";
            suppressOrderStatusEvents = false;
            return;
        }

        foreach (var order in siteOrders)
        {
            var index = gridOrdersSection.Rows.Add(
                order.Id,
                order.ClientNom,
                order.DateCommande,
                order.Statut,
                order.MontantTotal.ToString("0.00", CultureInfo.InvariantCulture),
                order.AdresseLivraison,
                order.SyncedPme == 1 ? "Oui" : "Non");

            gridOrdersSection.Rows[index].Tag = order;
        }

        lblOrdersSectionSummary.Text = siteOrders.Count == 0
            ? "Aucune commande web disponible."
            : $"{siteOrders.Count} bon(s) de commande web charges.";

        if (gridOrdersSection.Rows.Count > 0)
        {
            var rowToSelect = selectedOrderId.HasValue
                ? gridOrdersSection.Rows.Cast<DataGridViewRow>().FirstOrDefault(row => row.Tag is SiteOrder order && order.Id == selectedOrderId.Value)
                : null;

            (rowToSelect ?? gridOrdersSection.Rows[0]).Selected = true;
            LoadSelectedOrderLinesInSection();
        }
        else
        {
            txtOrderNotesSection.Text = "Aucune commande chargee.";
            lblSelectedOrderClientValue.Text = "Client : -";
            lblSelectedOrderTotalValue.Text = "0.00";
        }

        suppressOrderStatusEvents = false;
    }

    private void LoadSelectedOrderLinesInSection()
    {
        gridOrderLinesSection.Rows.Clear();

        if (gridOrdersSection.CurrentRow?.Tag is not SiteOrder order)
        {
            txtOrderNotesSection.Text = string.Empty;
            lblSelectedOrderClientValue.Text = "Client : -";
            lblSelectedOrderTotalValue.Text = "0.00";
            return;
        }

        foreach (var line in order.Lignes)
        {
            gridOrderLinesSection.Rows.Add(
                line.Reference,
                line.Designation,
                line.Quantite,
                line.PrixUnitaire.ToString("0.00", CultureInfo.InvariantCulture),
                line.SousTotal.ToString("0.00", CultureInfo.InvariantCulture));
        }

        txtOrderNotesSection.Text = string.IsNullOrWhiteSpace(order.Notes)
            ? "Aucune note client."
            : order.Notes;
        lblSelectedOrderClientValue.Text = $"Client : {order.ClientNom}";
        lblSelectedOrderTotalValue.Text = order.MontantTotal.ToString("0.00", CultureInfo.InvariantCulture);
    }

    private void GridOrdersSection_CurrentCellDirtyStateChanged(object? sender, EventArgs e)
    {
        if (gridOrdersSection.IsCurrentCellDirty &&
            string.Equals(gridOrdersSection.CurrentCell?.OwningColumn?.Name, "Statut", StringComparison.Ordinal))
        {
            gridOrdersSection.CommitEdit(DataGridViewDataErrorContexts.Commit);
        }
    }

    private async void GridOrdersSection_CellValueChanged(object? sender, DataGridViewCellEventArgs e)
    {
        if (suppressOrderStatusEvents || e.RowIndex < 0 || e.ColumnIndex < 0)
        {
            return;
        }

        var column = gridOrdersSection.Columns[e.ColumnIndex];
        if (!string.Equals(column.Name, "Statut", StringComparison.Ordinal))
        {
            return;
        }

        var row = gridOrdersSection.Rows[e.RowIndex];
        if (row.Tag is not SiteOrder order)
        {
            return;
        }

        var newStatus = Convert.ToString(row.Cells["Statut"].Value, CultureInfo.InvariantCulture)?.Trim() ?? string.Empty;
        if (string.IsNullOrWhiteSpace(newStatus) ||
            string.Equals(newStatus, order.Statut, StringComparison.OrdinalIgnoreCase))
        {
            return;
        }

        try
        {
            await ChangeOrderStatusAsync(order, newStatus);
            row.Cells["SyncedPme"].Value = order.SyncedPme == 1 ? "Oui" : "Non";
        }
        catch (Exception ex)
        {
            suppressOrderStatusEvents = true;
            row.Cells["Statut"].Value = order.Statut;
            suppressOrderStatusEvents = false;
            AddDatabaseEventLog("Commande", $"Echec changement statut commande #{order.Id} : {ex.Message}");
            SetStatus($"Echec changement statut commande #{order.Id} : {ex.Message}");
            MessageBox.Show(this, ex.Message, "Commandes web", MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
    }

    private void GridOrdersSection_DataError(object? sender, DataGridViewDataErrorEventArgs e)
    {
        e.ThrowException = false;
    }

    private void SyncEventSection()
    {
        txtEventLogSection.Lines = databaseEventLogEntries.ToArray();
        txtEventLogSection.SelectionStart = txtEventLogSection.TextLength;
        txtEventLogSection.ScrollToCaret();
        lblEventSectionState.Text = databaseEvents is null
            ? "Ecoute Firebird inactive."
            : $"Ecoute Firebird active pour {string.Join(", ", WatchedDatabaseEvents)}";
        lblSettingsEventValue.Text = lblEventSectionState.Text;
    }

    private void ClearEventLogs()
    {
        EventLogStore.ClearAll();
        databaseEventLogEntries.Clear();
        txtEventLogSection.Clear();
        eventDiagnosticsForm?.SetEntries(databaseEventLogEntries);
    }

    private void RefreshSettingsSection()
    {
        lblSettingsDatabaseValue.Text = settings.HasDatabasePath()
            ? $"{settings.Server}:{settings.Port}\r\n{settings.DatabasePath}"
            : "Non configuree";
        lblSettingsDepotValue.Text = settings.HasDepotSelection()
            ? $"{settings.DepotCode} - {settings.DepotName}"
            : "Aucun depot selectionne";
        lblSettingsWebValue.Text = settings.HasWebSyncConfiguration()
            ? settings.WebEndpoint
            : "Synchronisation web non configuree";
        lblSettingsSyncValue.Text = settings.AutoSyncEnabled
            ? $"Automatique - {FormatSyncInterval(settings.SyncIntervalSeconds)}"
            : "Manuelle";
        lblSettingsEventValue.Text = databaseEvents is null
            ? "Ecoute Firebird inactive"
            : "Ecoute Firebird active";
    }

    private async Task HandleFirstRunAsync()
    {
        settings = AppSettingsStore.Load();
        UpdateSyncPanel();

        if (startMinimizedToTray && settings.HasDatabasePath() && settings.HasDepotSelection())
        {
            BeginInvoke(new Action(() => HideToTray(showBalloonTip: false)));
        }

        if (!settings.HasDatabasePath() || !settings.HasDepotSelection())
        {
            if (startMinimizedToTray)
            {
                RestoreFromTray();
            }

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

        if (!string.Equals(previousSettings.DatabasePath, settings.DatabasePath, StringComparison.OrdinalIgnoreCase) ||
            !string.Equals(previousSettings.Username, settings.Username, StringComparison.OrdinalIgnoreCase) ||
            !string.Equals(previousSettings.Password, settings.Password, StringComparison.Ordinal) ||
            !string.Equals(previousSettings.DepotCode, settings.DepotCode, StringComparison.OrdinalIgnoreCase))
        {
            await RestartDatabaseEventListenerAsync(force: true);
        }

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
        try
        {
            WindowsStartupManager.Apply(settings.LaunchAtStartup);
            AppSettingsStore.Save(settings);
        }
        catch (Exception ex)
        {
            MessageBox.Show(this, ex.Message, "Parametres", MessageBoxButtons.OK, MessageBoxIcon.Error);
            return false;
        }

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

        var settingsSnapshot = settings.Clone();
        ToggleBusy(true, $"Chargement des produits du depot {settingsSnapshot.DepotCode}...");

        try
        {
            var table = await LoadProductsTableAsync(settingsSnapshot);

            productTable = table;
            allRows.Clear();
            allRows.AddRange(table.Rows.Cast<DataRow>());
            visibleRows.Clear();
            selectedRows.Clear();

            BuildGridColumns(table);
            PopulateDataFilters();
            ApplyFilter();
            SetStatus($"{allRows.Count} produit(s) charges pour le depot {settingsSnapshot.DepotCode}.");
            await RestartDatabaseEventListenerAsync();
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

    private static Task<DataTable> LoadProductsTableAsync(AppSettings settingsSnapshot)
    {
        return Task.Run(() =>
        {
            var table = new DataTable();

            using var connection = new FbConnection(DatabaseSettingsForm.BuildConnectionString(settingsSnapshot));
            connection.Open();

            using var command = new FbCommand(GetProductQuery(), connection);
            command.Parameters.AddWithValue("@DepotCode", settingsSnapshot.DepotCode);
            using var adapter = new FbDataAdapter(command);
            adapter.Fill(table);

            return table;
        });
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
            await ProcessAutomaticWebCycleAsync();
            return;
        }

        await LoadOrdersFromSiteAsync(showMessage: false, updateStatus: false);
        UpdateSyncPanel("Connexion web prete.");
    }

    private async Task ProcessAutomaticWebCycleAsync()
    {
        if (!settings.HasWebSyncConfiguration() || syncInProgress)
        {
            return;
        }

        if (productTable is not null && allRows.Count > 0)
        {
            await TriggerSyncAsync(false, fetchOrdersAfterSync: false);
        }

        try
        {
            await LoadOrdersFromSiteAsync(showMessage: false, updateStatus: false);
        }
        catch (Exception ex)
        {
            AddDatabaseEventLog("Web", $"Echec de la recuperation automatique des commandes : {ex.Message}");
            SetStatus($"Echec de recuperation automatique des commandes : {ex.Message}");
            UpdateSyncPanel($"Recuperation auto des commandes en echec : {ex.Message}");
            lblSyncState.ForeColor = Color.FromArgb(220, 38, 38);
        }
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

        btnReload.Enabled = settings.HasWebSyncConfiguration() && !syncInProgress && !isBusy;
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
            WHERE COALESCE(PRODUIT.SUP, 0) = 0
              AND COALESCE(PRODUIT.MAT_PREM, 0) = 0
              AND DEPOT2.CODE_DEPOT = @DepotCode
            ORDER BY PRODUIT.PRODUIT, PRODUIT.REF_PRODUIT
            """;
    }

    private async Task RestartDatabaseEventListenerAsync(bool force = false)
    {
        if (!settings.HasDatabasePath())
        {
            StopDatabaseEventListener();
            return;
        }

        var newSignature = BuildDatabaseEventSignature();
        if (!force && databaseEvents is not null && string.Equals(databaseEventConnectionSignature, newSignature, StringComparison.Ordinal))
        {
            return;
        }

        StopDatabaseEventListener();

        try
        {
            databaseEvents = new FbRemoteEvent(DatabaseSettingsForm.BuildEventConnectionString(settings));
            databaseEvents.RemoteEventCounts += (_, eventArgs) => QueueDatabaseRefresh(eventArgs.Name, eventArgs.Counts);
            databaseEvents.RemoteEventError += (_, eventArgs) => HandleDatabaseEventError(eventArgs.Error);
            databaseEvents.Open();
            databaseEvents.QueueEvents(WatchedDatabaseEvents);
            databaseEventConnectionSignature = newSignature;
            AddDatabaseEventLog("Etat", $"Ecoute active pour {string.Join(", ", WatchedDatabaseEvents)}");
            SetStatus($"Ecoute Firebird active : {string.Join(", ", WatchedDatabaseEvents)}");
        }
        catch (Exception ex)
        {
            databaseEvents = null;
            databaseEventConnectionSignature = null;
            AddDatabaseEventLog("Erreur", $"Demarrage impossible : {ex.Message}");
            SetStatus($"Ecoute Firebird indisponible : {ex.Message}");
            databaseEventReconnectTimer.Start();
        }

        await Task.CompletedTask;
    }

    private void StopDatabaseEventListener()
    {
        databaseRefreshTimer.Stop();
        databaseEventReconnectTimer.Stop();
        pendingDatabaseRefresh = false;
        databaseEventConnectionSignature = null;

        if (databaseEvents is null)
        {
            return;
        }

        try
        {
            databaseEvents.CancelEvents();
        }
        catch
        {
        }

        databaseEvents.Dispose();
        databaseEvents = null;
        AddDatabaseEventLog("Etat", "Ecoute Firebird arretee");
    }

    private void QueueDatabaseRefresh(string eventName, int counts)
    {
        if (!IsHandleCreated)
        {
            return;
        }

        BeginInvoke(new Action(() =>
        {
            AddDatabaseEventLog("Evenement", $"{eventName} | compte={counts}");
            if (counts <= 0)
            {
                return;
            }

            pendingDatabaseRefresh = true;
            databaseRefreshTimer.Stop();
            databaseRefreshTimer.Start();
            SetStatus($"Evenement Firebird recu : {eventName} ({counts}) - actualisation...");
        }));
    }

    private void HandleDatabaseEventError(Exception error)
    {
        if (!IsHandleCreated)
        {
            return;
        }

        BeginInvoke(new Action(() =>
        {
            AddDatabaseEventLog("Erreur", error.Message);
            SetStatus($"Erreur ecoute Firebird : {error.Message}");
            databaseEventReconnectTimer.Stop();
            databaseEventReconnectTimer.Start();
        }));
    }

    private string BuildDatabaseEventSignature()
    {
        return string.Join("|",
            settings.Server?.Trim() ?? string.Empty,
            settings.Port.ToString(CultureInfo.InvariantCulture),
            settings.DatabasePath?.Trim() ?? string.Empty,
            settings.Username?.Trim() ?? string.Empty,
            settings.DepotCode?.Trim() ?? string.Empty);
    }

    private async Task ProcessPendingDatabaseRefreshAsync()
    {
        databaseRefreshTimer.Stop();

        if (!pendingDatabaseRefresh)
        {
            return;
        }

        if (isBusy || syncInProgress)
        {
            databaseRefreshTimer.Start();
            return;
        }

        pendingDatabaseRefresh = false;
        await LoadProductsAsync();
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

        if (visibleRows.Count > 0)
        {
            var gridRows = new DataGridViewRow[visibleRows.Count];
            for (var index = 0; index < visibleRows.Count; index++)
            {
                gridRows[index] = CreateGridRow(visibleRows[index]);
            }

            gridProducts.Rows.AddRange(gridRows);
        }

        gridProducts.ResumeLayout();
        suppressSelectionEvents = false;
        UpdateCounts();
    }

    private DataGridViewRow CreateGridRow(DataRow sourceRow)
    {
        var cells = new object[sourceRow.ItemArray.Length + 1];
        cells[0] = selectedRows.Contains(sourceRow);

        for (var i = 0; i < sourceRow.ItemArray.Length; i++)
        {
            cells[i + 1] = sourceRow[i] == DBNull.Value ? string.Empty : sourceRow[i];
        }

        var gridRow = new DataGridViewRow();
        gridRow.CreateCells(gridProducts, cells);
        gridRow.Tag = sourceRow;
        return gridRow;
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

    private async Task TriggerSyncAsync(bool manualTrigger, bool fetchOrdersAfterSync = true)
    {
        if (syncInProgress)
        {
            AddDatabaseEventLog("Sync", "Demande ignoree : une synchronisation est deja en cours.");
            return;
        }

        if (!settings.HasWebSyncConfiguration())
        {
            AddDatabaseEventLog("Sync", "Demande refusee : endpoint PME ou token API non configure.");
            MessageBox.Show(this, "Configurez d'abord l'endpoint PME et le token API dans les parametres.", "Synchronisation web", MessageBoxButtons.OK, MessageBoxIcon.Information);
            return;
        }

        if (productTable is null || allRows.Count == 0)
        {
            AddDatabaseEventLog("Sync", "Demande refusee : aucun produit charge pour le depot selectionne.");
            MessageBox.Show(this, "Aucun produit charge a synchroniser pour le depot selectionne.", "Synchronisation web", MessageBoxButtons.OK, MessageBoxIcon.Information);
            return;
        }

        syncInProgress = true;
        syncTimer.Stop();
        UpdateSyncPanel(manualTrigger ? "Synchronisation manuelle en cours..." : "Synchronisation automatique en cours...");
        SetStatus("Synchronisation web en cours...");

        try
        {
            var settingsSnapshot = settings.Clone();
            var payload = BuildSyncProductsPayload();
            AddDatabaseEventLog(
                "Sync",
                $"{(manualTrigger ? "Synchronisation manuelle" : "Synchronisation automatique")} lancee vers {settingsSnapshot.WebEndpoint} pour {payload.Count} produit(s) du depot {settingsSnapshot.DepotCode}.");
            var syncExecution = await RunRemoteSyncAsync(settingsSnapshot, payload, fetchOrdersAfterSync);
            var syncResult = syncExecution.SyncResult;
            lastSyncAt = DateTime.Now;
            lastSyncSummary = $"{lastSyncAt:dd/MM/yyyy HH:mm:ss} - {syncResult.InsertedCount} inseres, {syncResult.UpdatedCount} mis a jour";

            var shouldRefreshOrdersSection = false;
            if (fetchOrdersAfterSync)
            {
                shouldRefreshOrdersSection = ReplaceSiteOrders(syncExecution.Orders, notifyNewOrders: !manualTrigger);
            }
            AddDatabaseEventLog(
                "Sync",
                $"Reponse synchro : {syncResult.Message} | inseres={syncResult.InsertedCount} | maj={syncResult.UpdatedCount} | commandes={(fetchOrdersAfterSync ? siteOrders.Count : 0)}");

            var syncStatusMessage = !fetchOrdersAfterSync || string.IsNullOrWhiteSpace(syncExecution.OrdersWarningMessage)
                ? syncResult.Message
                : $"{syncResult.Message} Commandes non actualisees : {syncExecution.OrdersWarningMessage}";

            SetStatus(syncStatusMessage);
            UpdateSyncPanel(!fetchOrdersAfterSync || string.IsNullOrWhiteSpace(syncExecution.OrdersWarningMessage)
                ? "Synchronisation web terminee."
                : "Synchronisation terminee avec avertissement sur les commandes.");

            if (fetchOrdersAfterSync && !string.IsNullOrWhiteSpace(syncExecution.OrdersWarningMessage))
            {
                AddDatabaseEventLog("Web", $"Recuperation des commandes echouee : {syncExecution.OrdersWarningMessage}");
            }

            if (currentSection == AppSection.Commandes && shouldRefreshOrdersSection)
            {
                RefreshOrdersSection();
            }

            if (manualTrigger)
            {
                MessageBox.Show(
                    this,
                    $"{syncResult.Message}\n\nProduits inseres : {syncResult.InsertedCount}\nProduits mis a jour : {syncResult.UpdatedCount}\nCommandes web disponibles : {siteOrders.Count}" +
                    (string.IsNullOrWhiteSpace(syncExecution.OrdersWarningMessage)
                        ? string.Empty
                        : $"\n\nAvertissement commandes : {syncExecution.OrdersWarningMessage}"),
                    "Synchronisation web",
                    MessageBoxButtons.OK,
                    MessageBoxIcon.Information);
            }
        }
        catch (Exception ex)
        {
            lastSyncSummary = $"Echec - {DateTime.Now:dd/MM/yyyy HH:mm:ss} - {ex.Message}";
            UpdateSyncPanel($"Echec de la synchronisation web : {ex.Message}");
            lblSyncState.ForeColor = Color.FromArgb(220, 38, 38);
            SetStatus($"Echec de la synchronisation web : {ex.Message}");
            AddDatabaseEventLog("Sync", $"Echec de la synchronisation web : {ex.Message}");

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

    private static Task<RemoteSyncExecutionResult> RunRemoteSyncAsync(AppSettings settingsSnapshot, IReadOnlyList<WebSyncProduct> payload, bool fetchOrdersAfterSync)
    {
        return Task.Run(async () =>
        {
            var syncResult = await WebSyncService.SyncProductsAsync(settingsSnapshot, payload).ConfigureAwait(false);
            if (!fetchOrdersAfterSync)
            {
                return new RemoteSyncExecutionResult(syncResult, [], null);
            }

            try
            {
                var orders = await WebSyncService.FetchOrdersAsync(settingsSnapshot).ConfigureAwait(false);
                return new RemoteSyncExecutionResult(syncResult, orders.ToList(), null);
            }
            catch (Exception ex)
            {
                return new RemoteSyncExecutionResult(syncResult, [], ex.Message);
            }
        });
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
        return string.IsNullOrWhiteSpace(famille) ? "General" : famille;
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
            AddDatabaseEventLog("Web", "Recuperation des commandes ignoree : synchronisation web non configuree.");
            UpdateSyncPanel();
            return;
        }

        var settingsSnapshot = settings.Clone();
        AddDatabaseEventLog("Web", $"Recuperation des commandes en attente depuis {settingsSnapshot.WebEndpoint}...");
        var orders = await FetchOrdersInBackgroundAsync(settingsSnapshot);
        var shouldRefreshOrdersSection = ReplaceSiteOrders(orders, notifyNewOrders: true);
        AddDatabaseEventLog("Web", $"{siteOrders.Count} bon(s) de commande web recupere(s).");

        if (currentSection == AppSection.Commandes && shouldRefreshOrdersSection)
        {
            RefreshOrdersSection();
        }

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

    private static Task<IReadOnlyList<SiteOrder>> FetchOrdersInBackgroundAsync(AppSettings settingsSnapshot)
    {
        return Task.Run(async () =>
        {
            var orders = await WebSyncService.FetchOrdersAsync(settingsSnapshot).ConfigureAwait(false);
            return (IReadOnlyList<SiteOrder>)orders.ToList();
        });
    }

    private bool ReplaceSiteOrders(IReadOnlyList<SiteOrder> orders, bool notifyNewOrders)
    {
        var hadOrdersSnapshot = ordersInitialized;
        var newOrders = hadOrdersSnapshot && notifyNewOrders
            ? orders.Where(order => !knownSiteOrderIds.Contains(order.Id)).ToList()
            : [];

        siteOrders.Clear();
        siteOrders.AddRange(orders);

        foreach (var order in orders)
        {
            knownSiteOrderIds.Add(order.Id);
        }

        ordersInitialized = true;

        if (newOrders.Count > 0)
        {
            NotifyNewOrders(newOrders);
        }

        return !hadOrdersSnapshot || newOrders.Count > 0;
    }

    private async Task ChangeOrderStatusAsync(SiteOrder order, string newStatus)
    {
        if (!settings.HasWebSyncConfiguration())
        {
            throw new InvalidOperationException("La synchronisation web n'est pas configuree.");
        }

        var settingsSnapshot = settings.Clone();
        AddDatabaseEventLog("Commande", $"Mise a jour du statut de la commande #{order.Id} vers '{newStatus}'...");

        if (string.Equals(newStatus, "validee", StringComparison.OrdinalIgnoreCase))
        {
            var importResult = await OrderFirebirdService.ImportValidatedOrderAsync(settingsSnapshot, order);

            AddDatabaseEventLog(
                "Commande",
                importResult.Created
                    ? $"Commande #{order.Id} importee dans Firebird sous le bon {importResult.NumBon}."
                    : $"Commande #{order.Id} deja presente dans Firebird sous le bon {importResult.NumBon}.");
        }

        await WebSyncService.UpdateOrderStatusAsync(settingsSnapshot, order.Id, newStatus);
        if (string.Equals(newStatus, "validee", StringComparison.OrdinalIgnoreCase))
        {
            await WebSyncService.MarkOrderSyncedAsync(settingsSnapshot, order.Id);
            order.SyncedPme = 1;
        }

        order.Statut = newStatus;
        AddDatabaseEventLog("Commande", $"Statut de la commande #{order.Id} mis a jour sur le site : {newStatus}.");
        SetStatus($"Commande #{order.Id} mise a jour : {newStatus}.");
        UpdateSyncPanel(order.SyncedPme == 1
            ? $"Commande #{order.Id} validee et envoyee vers Firebird."
            : $"Commande #{order.Id} mise a jour.");
    }

    private void NotifyNewOrders(IReadOnlyList<SiteOrder> newOrders)
    {
        var latestOrder = newOrders[0];
        var count = newOrders.Count;
        var clientLabel = string.IsNullOrWhiteSpace(latestOrder.ClientNom) ? $"Commande #{latestOrder.Id}" : latestOrder.ClientNom;
        var message = count == 1
            ? $"Nouvelle commande web : {clientLabel} - total {latestOrder.MontantTotal:0.00}"
            : $"{count} nouvelles commandes web recues. Derniere : {clientLabel} - {latestOrder.MontantTotal:0.00}";

        AddDatabaseEventLog("Commande", message);
        SetStatus(message);

        try
        {
            SystemSounds.Exclamation.Play();
        }
        catch
        {
        }

        trayIcon.Visible = true;
        trayIcon.BalloonTipTitle = count == 1 ? "Nouvelle commande web" : "Nouvelles commandes web";
        trayIcon.BalloonTipText = message;
        trayIcon.ShowBalloonTip(4000);
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
        this.isBusy = isBusy;
        UseWaitCursor = isBusy;
        if (isBusy)
        {
            syncTimer.Stop();
            databaseRefreshTimer.Stop();
        }

        btnSettings.Enabled = !isBusy;
        btnReload.Enabled = !isBusy && settings.HasWebSyncConfiguration() && !syncInProgress;
        btnEventDiagnostics.Enabled = !isBusy;
        btnNavProducts.Enabled = !isBusy;
        btnNavOrders.Enabled = !isBusy;
        btnNavEvents.Enabled = !isBusy;
        btnNavSettings.Enabled = !isBusy;
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

        if (!isBusy && pendingDatabaseRefresh)
        {
            databaseRefreshTimer.Start();
        }
    }

    private void Form1_FormClosing(object? sender, FormClosingEventArgs e)
    {
        if (allowExit || e.CloseReason != CloseReason.UserClosing)
        {
            return;
        }

        e.Cancel = true;
        HideToTray(showBalloonTip: true);
    }

    private void HideToTray(bool showBalloonTip)
    {
        trayIcon.Visible = true;
        ShowInTaskbar = false;
        WindowState = FormWindowState.Minimized;
        Hide();

        if (showBalloonTip)
        {
            trayIcon.BalloonTipTitle = "PMESync";
            trayIcon.BalloonTipText = "L'application reste active pres de l'horloge. Clic droit pour Ouvrir ou Quitter.";
            trayIcon.ShowBalloonTip(2500);
        }
    }

    private void RestoreFromTray()
    {
        Show();
        ShowInTaskbar = true;
        WindowState = FormWindowState.Normal;
        Activate();
        BringToFront();
        trayIcon.Visible = false;
    }

    private void ExitApplicationFromTray()
    {
        allowExit = true;
        trayIcon.Visible = false;
        Close();
    }

    private void SetStatus(string message)
    {
        statusLabel.Text = message;
    }

    private void LoadPersistedEventLogs()
    {
        databaseEventLogEntries.Clear();
        foreach (var entry in EventLogStore.GetRecent(300))
        {
            databaseEventLogEntries.Add(FormatEventLogEntry(entry));
        }

        txtEventLogSection.Lines = databaseEventLogEntries.ToArray();
        txtEventLogSection.SelectionStart = txtEventLogSection.TextLength;
        txtEventLogSection.ScrollToCaret();
        eventDiagnosticsForm?.SetEntries(databaseEventLogEntries);
        SyncEventSection();
    }

    private void AddDatabaseEventLog(string category, string message)
    {
        var persistedEntry = EventLogStore.Append(category, message);
        var line = FormatEventLogEntry(persistedEntry);
        databaseEventLogEntries.Add(line);

        const int maxEntries = 300;
        if (databaseEventLogEntries.Count > maxEntries)
        {
            databaseEventLogEntries.RemoveRange(0, databaseEventLogEntries.Count - maxEntries);
        }

        eventDiagnosticsForm?.SetEntries(databaseEventLogEntries);
        txtEventLogSection.Lines = databaseEventLogEntries.ToArray();
        txtEventLogSection.SelectionStart = txtEventLogSection.TextLength;
        txtEventLogSection.ScrollToCaret();
        SyncEventSection();
    }

    private static string FormatEventLogEntry(EventLogRecord entry)
    {
        return $"[{entry.Id} | {entry.LoggedAt:dd/MM/yyyy HH:mm:ss}] {entry.EventType} - {entry.Message}";
    }

    private void ShowEventDiagnostics()
    {
        if (eventDiagnosticsForm is null || eventDiagnosticsForm.IsDisposed)
        {
            eventDiagnosticsForm = new EventDiagnosticsForm();
            eventDiagnosticsForm.ClearRequested += (_, _) =>
            {
                databaseEventLogEntries.Clear();
                eventDiagnosticsForm?.SetEntries(databaseEventLogEntries);
            };
            eventDiagnosticsForm.FormClosed += (_, _) => eventDiagnosticsForm = null;
        }

        eventDiagnosticsForm.SetEntries(databaseEventLogEntries);
        eventDiagnosticsForm.Show(this);
        eventDiagnosticsForm.BringToFront();
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

    protected override void OnFormClosed(FormClosedEventArgs e)
    {
        if (eventDiagnosticsForm is not null && !eventDiagnosticsForm.IsDisposed)
        {
            eventDiagnosticsForm.Close();
        }

        StopDatabaseEventListener();
        syncTimer.Stop();
        trayIcon.Visible = false;
        trayIcon.Dispose();
        trayMenu.Dispose();
        base.OnFormClosed(e);
    }
}
