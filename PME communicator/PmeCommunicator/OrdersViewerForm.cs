using System.Globalization;

namespace PmeCommunicator;

public sealed class OrdersViewerForm : Form
{
    private readonly DataGridView gridOrders = new();
    private readonly DataGridView gridLines = new();
    private readonly Label lblSummary = new() { AutoSize = true };
    private readonly TextBox txtNotes = new()
    {
        Multiline = true,
        ReadOnly = true,
        ScrollBars = ScrollBars.Vertical,
        Dock = DockStyle.Fill,
    };

    private readonly IReadOnlyList<SiteOrder> orders;

    public OrdersViewerForm(IReadOnlyList<SiteOrder> orders, string depotCode)
    {
        this.orders = orders;

        Text = $"Bons de commande web - Depot {depotCode}";
        Icon = AppIconProvider.GetApplicationIcon();
        StartPosition = FormStartPosition.CenterParent;
        MinimumSize = new Size(980, 620);
        Size = new Size(1120, 720);
        BackColor = Color.FromArgb(241, 245, 249);

        BuildLayout();
        ConfigureOrdersGrid();
        ConfigureLinesGrid();
        LoadOrders();
    }

    private void BuildLayout()
    {
        var header = new Panel
        {
            Dock = DockStyle.Top,
            Height = 64,
            BackColor = Color.White,
            Padding = new Padding(18, 18, 18, 12),
        };

        lblSummary.Font = new Font("Segoe UI Semibold", 10F, FontStyle.Bold);
        lblSummary.ForeColor = Color.FromArgb(30, 41, 59);
        header.Controls.Add(lblSummary);

        var split = new SplitContainer
        {
            Dock = DockStyle.Fill,
            Orientation = Orientation.Horizontal,
            SplitterDistance = 320,
            BackColor = Color.FromArgb(226, 232, 240),
            Padding = new Padding(18, 18, 18, 12),
        };

        var topPanel = new Panel { Dock = DockStyle.Fill, BackColor = Color.White, Padding = new Padding(1) };
        topPanel.Controls.Add(gridOrders);
        split.Panel1.Controls.Add(topPanel);

        var bottomLayout = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            ColumnCount = 1,
            RowCount = 2,
            BackColor = Color.FromArgb(241, 245, 249),
        };
        bottomLayout.RowStyles.Add(new RowStyle(SizeType.Percent, 70));
        bottomLayout.RowStyles.Add(new RowStyle(SizeType.Percent, 30));

        var linesPanel = new Panel { Dock = DockStyle.Fill, BackColor = Color.White, Padding = new Padding(1), Margin = new Padding(0, 0, 0, 10) };
        linesPanel.Controls.Add(gridLines);
        bottomLayout.Controls.Add(linesPanel, 0, 0);

        var notesPanel = new Panel { Dock = DockStyle.Fill, BackColor = Color.White, Padding = new Padding(14) };
        notesPanel.Controls.Add(txtNotes);
        bottomLayout.Controls.Add(notesPanel, 0, 1);

        split.Panel2.Controls.Add(bottomLayout);

        Controls.Add(split);
        Controls.Add(header);
    }

    private void ConfigureOrdersGrid()
    {
        gridOrders.Dock = DockStyle.Fill;
        gridOrders.AllowUserToAddRows = false;
        gridOrders.AllowUserToDeleteRows = false;
        gridOrders.AllowUserToResizeRows = false;
        gridOrders.MultiSelect = false;
        gridOrders.ReadOnly = true;
        gridOrders.RowHeadersVisible = false;
        gridOrders.SelectionMode = DataGridViewSelectionMode.FullRowSelect;
        gridOrders.BorderStyle = BorderStyle.None;
        gridOrders.BackgroundColor = Color.White;
        gridOrders.EnableHeadersVisualStyles = false;
        gridOrders.ColumnHeadersDefaultCellStyle.BackColor = Color.FromArgb(30, 64, 175);
        gridOrders.ColumnHeadersDefaultCellStyle.ForeColor = Color.White;
        gridOrders.ColumnHeadersDefaultCellStyle.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
        gridOrders.ColumnHeadersHeight = 38;
        gridOrders.ColumnHeadersHeightSizeMode = DataGridViewColumnHeadersHeightSizeMode.DisableResizing;
        gridOrders.AlternatingRowsDefaultCellStyle.BackColor = Color.FromArgb(248, 250, 252);
        gridOrders.RowTemplate.Height = 32;
        gridOrders.Columns.Add(new DataGridViewTextBoxColumn { Name = "Id", HeaderText = "Commande", Width = 110 });
        gridOrders.Columns.Add(new DataGridViewTextBoxColumn { Name = "DateCommande", HeaderText = "Date", Width = 160 });
        gridOrders.Columns.Add(new DataGridViewTextBoxColumn { Name = "Statut", HeaderText = "Statut", Width = 120 });
        gridOrders.Columns.Add(new DataGridViewTextBoxColumn { Name = "MontantTotal", HeaderText = "Montant total", Width = 140 });
        gridOrders.Columns.Add(new DataGridViewTextBoxColumn { Name = "AdresseLivraison", HeaderText = "Adresse livraison", AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill });
        gridOrders.Columns.Add(new DataGridViewTextBoxColumn { Name = "SyncedPme", HeaderText = "Sync PME", Width = 90 });
        gridOrders.SelectionChanged += (_, _) => LoadSelectedLines();
    }

    private void ConfigureLinesGrid()
    {
        gridLines.Dock = DockStyle.Fill;
        gridLines.AllowUserToAddRows = false;
        gridLines.AllowUserToDeleteRows = false;
        gridLines.AllowUserToResizeRows = false;
        gridLines.ReadOnly = true;
        gridLines.RowHeadersVisible = false;
        gridLines.SelectionMode = DataGridViewSelectionMode.FullRowSelect;
        gridLines.BorderStyle = BorderStyle.None;
        gridLines.BackgroundColor = Color.White;
        gridLines.EnableHeadersVisualStyles = false;
        gridLines.ColumnHeadersDefaultCellStyle.BackColor = Color.FromArgb(14, 165, 233);
        gridLines.ColumnHeadersDefaultCellStyle.ForeColor = Color.White;
        gridLines.ColumnHeadersDefaultCellStyle.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
        gridLines.ColumnHeadersHeight = 36;
        gridLines.ColumnHeadersHeightSizeMode = DataGridViewColumnHeadersHeightSizeMode.DisableResizing;
        gridLines.AlternatingRowsDefaultCellStyle.BackColor = Color.FromArgb(248, 250, 252);
        gridLines.RowTemplate.Height = 30;
        gridLines.Columns.Add(new DataGridViewTextBoxColumn { Name = "Reference", HeaderText = "Reference", Width = 150 });
        gridLines.Columns.Add(new DataGridViewTextBoxColumn { Name = "Designation", HeaderText = "Designation", AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill });
        gridLines.Columns.Add(new DataGridViewTextBoxColumn { Name = "Quantite", HeaderText = "Quantite", Width = 90 });
        gridLines.Columns.Add(new DataGridViewTextBoxColumn { Name = "PrixUnitaire", HeaderText = "Prix unitaire", Width = 120 });
        gridLines.Columns.Add(new DataGridViewTextBoxColumn { Name = "SousTotal", HeaderText = "Sous-total", Width = 120 });
    }

    private void LoadOrders()
    {
        gridOrders.Rows.Clear();
        foreach (var order in orders)
        {
            var index = gridOrders.Rows.Add(
                order.Id,
                order.DateCommande,
                order.Statut,
                order.MontantTotal.ToString("0.00", CultureInfo.InvariantCulture),
                order.AdresseLivraison,
                order.SyncedPme == 1 ? "Oui" : "Non");

            gridOrders.Rows[index].Tag = order;
        }

        lblSummary.Text = $"{orders.Count} bon(s) de commande web non synchronise(s).";

        if (gridOrders.Rows.Count > 0)
        {
            gridOrders.Rows[0].Selected = true;
            LoadSelectedLines();
        }
        else
        {
            txtNotes.Text = "Aucun bon de commande recupere depuis le site.";
        }
    }

    private void LoadSelectedLines()
    {
        gridLines.Rows.Clear();

        if (gridOrders.CurrentRow?.Tag is not SiteOrder order)
        {
            txtNotes.Text = string.Empty;
            return;
        }

        foreach (var line in order.Lignes)
        {
            gridLines.Rows.Add(
                line.Reference,
                line.Designation,
                line.Quantite,
                line.PrixUnitaire.ToString("0.00", CultureInfo.InvariantCulture),
                line.SousTotal.ToString("0.00", CultureInfo.InvariantCulture));
        }

        txtNotes.Text = string.IsNullOrWhiteSpace(order.Notes)
            ? "Aucune note client."
            : order.Notes;
    }
}
