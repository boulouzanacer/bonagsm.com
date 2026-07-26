using System.Globalization;
using System.Drawing.Drawing2D;

namespace PMESync;

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
        UiTheme.ApplyDialogChrome(this);

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
            Height = 74,
            BackColor = UiTheme.HeaderBackground,
            Padding = new Padding(22, 18, 22, 14),
        };

        lblSummary.Font = new Font("Segoe UI Semibold", 10F, FontStyle.Bold);
        lblSummary.ForeColor = Color.White;
        header.Controls.Add(lblSummary);

        var split = new SplitContainer
        {
            Dock = DockStyle.Fill,
            Orientation = Orientation.Horizontal,
            SplitterDistance = 320,
            BackColor = Color.FromArgb(226, 232, 240),
            Padding = new Padding(18, 18, 18, 12),
        };

        var topPanel = new Panel { Dock = DockStyle.Fill, BackColor = UiTheme.CardBorder, Padding = new Padding(1) };
        topPanel.Controls.Add(gridOrders);
        split.Panel1.Controls.Add(topPanel);

        var bottomLayout = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            ColumnCount = 1,
            RowCount = 2,
            BackColor = UiTheme.AppBackground,
        };
        bottomLayout.RowStyles.Add(new RowStyle(SizeType.Percent, 70));
        bottomLayout.RowStyles.Add(new RowStyle(SizeType.Percent, 30));

        var linesPanel = new Panel { Dock = DockStyle.Fill, BackColor = UiTheme.CardBorder, Padding = new Padding(1), Margin = new Padding(0, 0, 0, 10) };
        linesPanel.Controls.Add(gridLines);
        bottomLayout.Controls.Add(linesPanel, 0, 0);

        var notesPanel = new Panel { Dock = DockStyle.Fill, BackColor = UiTheme.CardBackground, Padding = new Padding(18) };
        notesPanel.Controls.Add(txtNotes);
        bottomLayout.Controls.Add(notesPanel, 0, 1);

        UiTheme.StyleInput(txtNotes);

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
        UiTheme.StyleDataGrid(gridOrders, UiTheme.HeaderBackground, 44);
        gridOrders.ColumnHeadersHeight = 42;
        gridOrders.ColumnHeadersHeightSizeMode = DataGridViewColumnHeadersHeightSizeMode.DisableResizing;
        gridOrders.Columns.Add(new DataGridViewTextBoxColumn { Name = "Id", HeaderText = "Commande", Width = 120 });
        gridOrders.Columns.Add(new DataGridViewTextBoxColumn { Name = "DateCommande", HeaderText = "Date", Width = 150 });
        gridOrders.Columns.Add(new DataGridViewTextBoxColumn { Name = "Statut", HeaderText = "Statut", Width = 128 });
        gridOrders.Columns.Add(new DataGridViewTextBoxColumn { Name = "MontantTotal", HeaderText = "Montant total", Width = 140 });
        gridOrders.Columns.Add(new DataGridViewTextBoxColumn { Name = "AdresseLivraison", HeaderText = "Adresse livraison", AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill });
        gridOrders.Columns.Add(new DataGridViewTextBoxColumn { Name = "SyncedPme", HeaderText = "Sync PME", Width = 102 });
        gridOrders.Columns["Id"].DefaultCellStyle.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
        gridOrders.Columns["Id"].DefaultCellStyle.ForeColor = UiTheme.HeaderAccent;
        gridOrders.Columns["MontantTotal"].DefaultCellStyle.Alignment = DataGridViewContentAlignment.MiddleRight;
        gridOrders.Columns["MontantTotal"].DefaultCellStyle.Font = new Font("Segoe UI", 9.5F, FontStyle.Bold);
        gridOrders.Columns["AdresseLivraison"].DefaultCellStyle.ForeColor = UiTheme.TextSecondary;
        gridOrders.SelectionChanged += (_, _) => LoadSelectedLines();
        gridOrders.CellFormatting += GridOrders_CellFormatting;
        gridOrders.CellPainting += GridOrders_CellPainting;
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
        UiTheme.StyleDataGrid(gridLines, UiTheme.SecondaryAccent, 34);
        gridLines.ColumnHeadersHeight = 36;
        gridLines.ColumnHeadersHeightSizeMode = DataGridViewColumnHeadersHeightSizeMode.DisableResizing;
        gridLines.Columns.Add(new DataGridViewTextBoxColumn { Name = "Reference", HeaderText = "Reference", Width = 150 });
        gridLines.Columns.Add(new DataGridViewTextBoxColumn { Name = "Designation", HeaderText = "Designation", AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill });
        gridLines.Columns.Add(new DataGridViewTextBoxColumn { Name = "Quantite", HeaderText = "Quantite", Width = 90 });
        gridLines.Columns.Add(new DataGridViewTextBoxColumn { Name = "PrixUnitaire", HeaderText = "Prix unitaire", Width = 120 });
        gridLines.Columns.Add(new DataGridViewTextBoxColumn { Name = "SousTotal", HeaderText = "Sous-total", Width = 120 });
        gridLines.Columns["PrixUnitaire"].DefaultCellStyle.Alignment = DataGridViewContentAlignment.MiddleRight;
        gridLines.Columns["SousTotal"].DefaultCellStyle.Alignment = DataGridViewContentAlignment.MiddleRight;
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

    private void GridOrders_CellFormatting(object? sender, DataGridViewCellFormattingEventArgs e)
    {
        if (e.RowIndex < 0 || e.ColumnIndex < 0 || gridOrders.Rows[e.RowIndex].Tag is not SiteOrder order)
        {
            return;
        }

        var columnName = gridOrders.Columns[e.ColumnIndex].Name;
        if (columnName == "Id")
        {
            e.Value = $"#{order.Id:D6}";
            e.FormattingApplied = true;
            return;
        }

        if (columnName == "DateCommande" &&
            (DateTime.TryParse(order.DateCommande, CultureInfo.InvariantCulture, DateTimeStyles.AssumeLocal, out var parsed) ||
             DateTime.TryParse(order.DateCommande, CultureInfo.CurrentCulture, DateTimeStyles.AssumeLocal, out parsed)))
        {
            e.Value = parsed.ToString("dd/MM/yyyy HH:mm", CultureInfo.InvariantCulture);
            e.FormattingApplied = true;
            return;
        }

        if (columnName == "MontantTotal")
        {
            e.Value = $"{order.MontantTotal:0.00} DA";
            e.CellStyle!.BackColor = Color.FromArgb(239, 246, 255);
            e.CellStyle.ForeColor = Color.FromArgb(30, 64, 175);
            e.FormattingApplied = true;
        }
    }

    private void GridOrders_CellPainting(object? sender, DataGridViewCellPaintingEventArgs e)
    {
        if (e.RowIndex < 0 || e.ColumnIndex < 0)
        {
            return;
        }

        var columnName = gridOrders.Columns[e.ColumnIndex].Name;
        if (columnName is not ("Statut" or "SyncedPme"))
        {
            return;
        }

        var rawValue = Convert.ToString(e.FormattedValue, CultureInfo.InvariantCulture)?.Trim() ?? string.Empty;
        var (backColor, textColor, text) = columnName == "Statut"
            ? rawValue switch
            {
                "en_attente" => (Color.FromArgb(255, 247, 237), Color.FromArgb(194, 65, 12), "En attente"),
                "validee" => (Color.FromArgb(236, 253, 245), Color.FromArgb(5, 150, 105), "Validee"),
                "expediee" => (Color.FromArgb(239, 246, 255), Color.FromArgb(29, 78, 216), "Expediee"),
                "livree" => (Color.FromArgb(238, 242, 255), Color.FromArgb(67, 56, 202), "Livree"),
                "annulee" => (Color.FromArgb(254, 242, 242), Color.FromArgb(220, 38, 38), "Annulee"),
                _ => (UiTheme.CardAltBackground, UiTheme.TextSecondary, rawValue),
            }
            : string.Equals(rawValue, "Oui", StringComparison.OrdinalIgnoreCase)
                ? (Color.FromArgb(236, 253, 245), UiTheme.SuccessAccent, "Oui")
                : (Color.FromArgb(255, 251, 235), UiTheme.WarningAccent, string.IsNullOrWhiteSpace(rawValue) ? "Non" : rawValue);

        var graphics = e.Graphics;
        if (graphics is null)
        {
            return;
        }

        e.PaintBackground(e.CellBounds, e.State.HasFlag(DataGridViewElementStates.Selected));
        var badgeBounds = Rectangle.Inflate(e.CellBounds, -12, -9);
        using var path = CreateRoundedRectanglePath(badgeBounds, 16);
        using var brush = new SolidBrush(backColor);
        using var pen = new Pen(backColor);
        graphics.SmoothingMode = SmoothingMode.AntiAlias;
        graphics.FillPath(brush, path);
        graphics.DrawPath(pen, path);
        TextRenderer.DrawText(
            graphics,
            text,
            new Font("Segoe UI Semibold", 8.5F, FontStyle.Bold),
            badgeBounds,
            textColor,
            TextFormatFlags.HorizontalCenter | TextFormatFlags.VerticalCenter | TextFormatFlags.EndEllipsis);
        e.Handled = true;
    }

    private static GraphicsPath CreateRoundedRectanglePath(Rectangle bounds, int radius)
    {
        var diameter = radius * 2;
        var path = new GraphicsPath();
        path.AddArc(bounds.X, bounds.Y, diameter, diameter, 180, 90);
        path.AddArc(bounds.Right - diameter, bounds.Y, diameter, diameter, 270, 90);
        path.AddArc(bounds.Right - diameter, bounds.Bottom - diameter, diameter, diameter, 0, 90);
        path.AddArc(bounds.X, bounds.Bottom - diameter, diameter, diameter, 90, 90);
        path.CloseFigure();
        return path;
    }
}
