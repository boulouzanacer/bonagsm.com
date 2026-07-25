namespace PmeCommunicator;

partial class Form1
{
    /// <summary>
    ///  Required designer variable.
    /// </summary>
    private System.ComponentModel.IContainer components = null;

    /// <summary>
    ///  Clean up any resources being used.
    /// </summary>
    /// <param name="disposing">true if managed resources should be disposed; otherwise, false.</param>
    protected override void Dispose(bool disposing)
    {
        if (disposing && (components != null))
        {
            components.Dispose();
        }
        base.Dispose(disposing);
    }

    #region Windows Form Designer generated code

    /// <summary>
    ///  Required method for Designer support - do not modify
    ///  the contents of this method with the code editor.
    /// </summary>
    private void InitializeComponent()
    {
        headerPanel = new Panel();
        btnSettings = new Button();
        btnReload = new Button();
        lblSubtitle = new Label();
        lblTitle = new Label();
        contentPanel = new Panel();
        bodyLayout = new TableLayoutPanel();
        leftPanel = new Panel();
        gridCardPanel = new Panel();
        gridProducts = new DataGridView();
        gridTopPanel = new Panel();
        btnExport = new Button();
        btnClearSelection = new Button();
        btnSelectVisible = new Button();
        txtFilter = new TextBox();
        lblSearch = new Label();
        statsPanel = new TableLayoutPanel();
        cardSelected = new Panel();
        lblSelectedCaption = new Label();
        lblSelectedValue = new Label();
        cardVisible = new Panel();
        lblVisibleCaption = new Label();
        lblVisibleValue = new Label();
        cardTotal = new Panel();
        lblTotalCaption = new Label();
        lblTotalValue = new Label();
        rightPanel = new Panel();
        filterCardPanel = new Panel();
        btnResetFilters = new Button();
        cmbMarque = new ComboBox();
        lblMarque = new Label();
        cmbSousFamille = new ComboBox();
        lblSousFamille = new Label();
        cmbFamille = new ComboBox();
        lblFamille = new Label();
        cmbStock = new ComboBox();
        lblStock = new Label();
        lblFiltersTitle = new Label();
        statusStrip = new StatusStrip();
        statusLabel = new ToolStripStatusLabel();
        headerPanel.SuspendLayout();
        contentPanel.SuspendLayout();
        bodyLayout.SuspendLayout();
        leftPanel.SuspendLayout();
        gridCardPanel.SuspendLayout();
        ((System.ComponentModel.ISupportInitialize)gridProducts).BeginInit();
        gridTopPanel.SuspendLayout();
        statsPanel.SuspendLayout();
        cardSelected.SuspendLayout();
        cardVisible.SuspendLayout();
        cardTotal.SuspendLayout();
        rightPanel.SuspendLayout();
        filterCardPanel.SuspendLayout();
        statusStrip.SuspendLayout();
        SuspendLayout();
        // 
        // headerPanel
        // 
        headerPanel.BackColor = Color.FromArgb(17, 24, 39);
        headerPanel.Controls.Add(lblSubtitle);
        headerPanel.Controls.Add(lblTitle);
        headerPanel.Dock = DockStyle.Top;
        headerPanel.Location = new Point(0, 0);
        headerPanel.Name = "headerPanel";
        headerPanel.Padding = new Padding(24, 18, 24, 18);
        headerPanel.Size = new Size(1484, 104);
        headerPanel.TabIndex = 0;
        // 
        // btnSettings
        // 
        btnSettings.Anchor = AnchorStyles.Top | AnchorStyles.Right;
        btnSettings.BackColor = Color.FromArgb(255, 255, 255);
        btnSettings.FlatAppearance.BorderSize = 0;
        btnSettings.FlatStyle = FlatStyle.Flat;
        btnSettings.Font = new Font("Segoe UI Semibold", 9F, FontStyle.Bold);
        btnSettings.ForeColor = Color.FromArgb(31, 41, 55);
        btnSettings.Location = new Point(1284, 30);
        btnSettings.Name = "btnSettings";
        btnSettings.Size = new Size(126, 42);
        btnSettings.TabIndex = 2;
        btnSettings.Text = "Parametres";
        btnSettings.UseVisualStyleBackColor = false;
        // 
        // btnReload
        // 
        btnReload.Anchor = AnchorStyles.Top | AnchorStyles.Right;
        btnReload.BackColor = Color.FromArgb(37, 99, 235);
        btnReload.FlatAppearance.BorderSize = 0;
        btnReload.FlatStyle = FlatStyle.Flat;
        btnReload.Font = new Font("Segoe MDL2 Assets", 15F, FontStyle.Regular);
        btnReload.ForeColor = Color.White;
        btnReload.Location = new Point(1418, 30);
        btnReload.Name = "btnReload";
        btnReload.Size = new Size(42, 42);
        btnReload.TabIndex = 3;
        btnReload.Text = "";
        btnReload.UseVisualStyleBackColor = false;
        // 
        // lblSubtitle
        // 
        lblSubtitle.AutoSize = true;
        lblSubtitle.ForeColor = Color.FromArgb(203, 213, 225);
        lblSubtitle.Location = new Point(28, 58);
        lblSubtitle.Name = "lblSubtitle";
        lblSubtitle.Size = new Size(554, 20);
        lblSubtitle.TabIndex = 1;
        lblSubtitle.Text = "Navigateur produits avec filtres par stock, famille, sous-famille et marque.";
        // 
        // lblTitle
        // 
        lblTitle.AutoSize = true;
        lblTitle.Font = new Font("Segoe UI", 18F, FontStyle.Bold);
        lblTitle.ForeColor = Color.White;
        lblTitle.Location = new Point(24, 16);
        lblTitle.Name = "lblTitle";
        lblTitle.Size = new Size(363, 41);
        lblTitle.TabIndex = 0;
        lblTitle.Text = "PME Communicator";
        // 
        // contentPanel
        // 
        contentPanel.BackColor = Color.FromArgb(241, 245, 249);
        contentPanel.Controls.Add(bodyLayout);
        contentPanel.Dock = DockStyle.Fill;
        contentPanel.Location = new Point(0, 104);
        contentPanel.Name = "contentPanel";
        contentPanel.Padding = new Padding(18, 18, 18, 10);
        contentPanel.Size = new Size(1484, 707);
        contentPanel.TabIndex = 1;
        // 
        // bodyLayout
        // 
        bodyLayout.ColumnCount = 2;
        bodyLayout.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 77F));
        bodyLayout.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 23F));
        bodyLayout.Controls.Add(leftPanel, 0, 0);
        bodyLayout.Controls.Add(rightPanel, 1, 0);
        bodyLayout.Dock = DockStyle.Fill;
        bodyLayout.Location = new Point(18, 18);
        bodyLayout.Name = "bodyLayout";
        bodyLayout.RowCount = 1;
        bodyLayout.RowStyles.Add(new RowStyle(SizeType.Percent, 100F));
        bodyLayout.Size = new Size(1448, 679);
        bodyLayout.TabIndex = 0;
        // 
        // leftPanel
        // 
        leftPanel.Controls.Add(gridCardPanel);
        leftPanel.Controls.Add(statsPanel);
        leftPanel.Dock = DockStyle.Fill;
        leftPanel.Location = new Point(3, 3);
        leftPanel.Name = "leftPanel";
        leftPanel.Padding = new Padding(0, 0, 16, 0);
        leftPanel.Size = new Size(1108, 673);
        leftPanel.TabIndex = 0;
        // 
        // gridCardPanel
        // 
        gridCardPanel.BackColor = Color.White;
        gridCardPanel.Controls.Add(gridProducts);
        gridCardPanel.Controls.Add(gridTopPanel);
        gridCardPanel.Dock = DockStyle.Fill;
        gridCardPanel.Location = new Point(0, 112);
        gridCardPanel.Name = "gridCardPanel";
        gridCardPanel.Padding = new Padding(1);
        gridCardPanel.Size = new Size(1092, 561);
        gridCardPanel.TabIndex = 1;
        // 
        // gridProducts
        // 
        gridProducts.AllowUserToAddRows = false;
        gridProducts.AllowUserToDeleteRows = false;
        gridProducts.AllowUserToResizeRows = false;
        gridProducts.BackgroundColor = Color.White;
        gridProducts.ColumnHeadersHeightSizeMode = DataGridViewColumnHeadersHeightSizeMode.AutoSize;
        gridProducts.Dock = DockStyle.Fill;
        gridProducts.Location = new Point(1, 69);
        gridProducts.MultiSelect = false;
        gridProducts.Name = "gridProducts";
        gridProducts.RowHeadersVisible = false;
        gridProducts.RowHeadersWidth = 51;
        gridProducts.SelectionMode = DataGridViewSelectionMode.FullRowSelect;
        gridProducts.Size = new Size(1090, 491);
        gridProducts.TabIndex = 1;
        // 
        // gridTopPanel
        // 
        gridTopPanel.BackColor = Color.White;
        gridTopPanel.Controls.Add(btnExport);
        gridTopPanel.Controls.Add(btnClearSelection);
        gridTopPanel.Controls.Add(btnSelectVisible);
        gridTopPanel.Controls.Add(txtFilter);
        gridTopPanel.Controls.Add(lblSearch);
        gridTopPanel.Dock = DockStyle.Top;
        gridTopPanel.Location = new Point(1, 1);
        gridTopPanel.Name = "gridTopPanel";
        gridTopPanel.Padding = new Padding(20, 16, 20, 12);
        gridTopPanel.Size = new Size(1090, 68);
        gridTopPanel.TabIndex = 0;
        // 
        // btnExport
        // 
        btnExport.Anchor = AnchorStyles.Top | AnchorStyles.Right;
        btnExport.BackColor = Color.FromArgb(16, 185, 129);
        btnExport.FlatAppearance.BorderSize = 0;
        btnExport.FlatStyle = FlatStyle.Flat;
        btnExport.Font = new Font("Segoe UI Semibold", 9F, FontStyle.Bold);
        btnExport.ForeColor = Color.White;
        btnExport.Location = new Point(957, 16);
        btnExport.Name = "btnExport";
        btnExport.Size = new Size(110, 36);
        btnExport.TabIndex = 4;
        btnExport.Text = "Exporter Excel";
        btnExport.UseVisualStyleBackColor = false;
        // 
        // btnClearSelection
        // 
        btnClearSelection.Anchor = AnchorStyles.Top | AnchorStyles.Right;
        btnClearSelection.BackColor = Color.FromArgb(248, 250, 252);
        btnClearSelection.FlatAppearance.BorderColor = Color.FromArgb(203, 213, 225);
        btnClearSelection.FlatStyle = FlatStyle.Flat;
        btnClearSelection.Font = new Font("Segoe UI", 9F);
        btnClearSelection.ForeColor = Color.FromArgb(51, 65, 85);
        btnClearSelection.Location = new Point(835, 16);
        btnClearSelection.Name = "btnClearSelection";
        btnClearSelection.Size = new Size(110, 36);
        btnClearSelection.TabIndex = 3;
        btnClearSelection.Text = "Effacer";
        btnClearSelection.UseVisualStyleBackColor = false;
        // 
        // btnSelectVisible
        // 
        btnSelectVisible.Anchor = AnchorStyles.Top | AnchorStyles.Right;
        btnSelectVisible.BackColor = Color.FromArgb(248, 250, 252);
        btnSelectVisible.FlatAppearance.BorderColor = Color.FromArgb(203, 213, 225);
        btnSelectVisible.FlatStyle = FlatStyle.Flat;
        btnSelectVisible.Font = new Font("Segoe UI", 9F);
        btnSelectVisible.ForeColor = Color.FromArgb(51, 65, 85);
        btnSelectVisible.Location = new Point(713, 16);
        btnSelectVisible.Name = "btnSelectVisible";
        btnSelectVisible.Size = new Size(110, 36);
        btnSelectVisible.TabIndex = 2;
        btnSelectVisible.Text = "Tout selectionner";
        btnSelectVisible.UseVisualStyleBackColor = false;
        // 
        // txtFilter
        // 
        txtFilter.Location = new Point(73, 20);
        txtFilter.Name = "txtFilter";
        txtFilter.PlaceholderText = "Rechercher par code, ref, designation, famille...";
        txtFilter.Size = new Size(403, 27);
        txtFilter.TabIndex = 1;
        // 
        // lblSearch
        // 
        lblSearch.AutoSize = true;
        lblSearch.ForeColor = Color.FromArgb(71, 85, 105);
        lblSearch.Location = new Point(20, 23);
        lblSearch.Name = "lblSearch";
        lblSearch.Size = new Size(47, 20);
        lblSearch.TabIndex = 0;
        lblSearch.Text = "Recherche :";
        // 
        // statsPanel
        // 
        statsPanel.ColumnCount = 3;
        statsPanel.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 33.3333321F));
        statsPanel.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 33.3333321F));
        statsPanel.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 33.3333321F));
        statsPanel.Controls.Add(cardSelected, 2, 0);
        statsPanel.Controls.Add(cardVisible, 1, 0);
        statsPanel.Controls.Add(cardTotal, 0, 0);
        statsPanel.Dock = DockStyle.Top;
        statsPanel.Location = new Point(0, 0);
        statsPanel.Name = "statsPanel";
        statsPanel.RowCount = 1;
        statsPanel.RowStyles.Add(new RowStyle(SizeType.Percent, 100F));
        statsPanel.Size = new Size(1092, 112);
        statsPanel.TabIndex = 0;
        // 
        // cardSelected
        // 
        cardSelected.BackColor = Color.FromArgb(14, 165, 233);
        cardSelected.Controls.Add(lblSelectedCaption);
        cardSelected.Controls.Add(lblSelectedValue);
        cardSelected.Dock = DockStyle.Fill;
        cardSelected.Location = new Point(731, 3);
        cardSelected.Margin = new Padding(3, 3, 0, 12);
        cardSelected.Name = "cardSelected";
        cardSelected.Padding = new Padding(18, 16, 18, 16);
        cardSelected.Size = new Size(361, 97);
        cardSelected.TabIndex = 2;
        // 
        // lblSelectedCaption
        // 
        lblSelectedCaption.AutoSize = true;
        lblSelectedCaption.ForeColor = Color.FromArgb(224, 242, 254);
        lblSelectedCaption.Location = new Point(20, 18);
        lblSelectedCaption.Name = "lblSelectedCaption";
        lblSelectedCaption.Size = new Size(66, 20);
        lblSelectedCaption.TabIndex = 1;
        lblSelectedCaption.Text = "Selectionnes";
        // 
        // lblSelectedValue
        // 
        lblSelectedValue.AutoSize = true;
        lblSelectedValue.Font = new Font("Segoe UI", 20F, FontStyle.Bold);
        lblSelectedValue.ForeColor = Color.White;
        lblSelectedValue.Location = new Point(18, 38);
        lblSelectedValue.Name = "lblSelectedValue";
        lblSelectedValue.Size = new Size(41, 46);
        lblSelectedValue.TabIndex = 0;
        lblSelectedValue.Text = "0";
        // 
        // cardVisible
        // 
        cardVisible.BackColor = Color.FromArgb(37, 99, 235);
        cardVisible.Controls.Add(lblVisibleCaption);
        cardVisible.Controls.Add(lblVisibleValue);
        cardVisible.Dock = DockStyle.Fill;
        cardVisible.Location = new Point(367, 3);
        cardVisible.Margin = new Padding(3, 3, 3, 12);
        cardVisible.Name = "cardVisible";
        cardVisible.Padding = new Padding(18, 16, 18, 16);
        cardVisible.Size = new Size(358, 97);
        cardVisible.TabIndex = 1;
        // 
        // lblVisibleCaption
        // 
        lblVisibleCaption.AutoSize = true;
        lblVisibleCaption.ForeColor = Color.FromArgb(219, 234, 254);
        lblVisibleCaption.Location = new Point(20, 18);
        lblVisibleCaption.Name = "lblVisibleCaption";
        lblVisibleCaption.Size = new Size(51, 20);
        lblVisibleCaption.TabIndex = 1;
        lblVisibleCaption.Text = "Visibles";
        // 
        // lblVisibleValue
        // 
        lblVisibleValue.AutoSize = true;
        lblVisibleValue.Font = new Font("Segoe UI", 20F, FontStyle.Bold);
        lblVisibleValue.ForeColor = Color.White;
        lblVisibleValue.Location = new Point(18, 38);
        lblVisibleValue.Name = "lblVisibleValue";
        lblVisibleValue.Size = new Size(41, 46);
        lblVisibleValue.TabIndex = 0;
        lblVisibleValue.Text = "0";
        // 
        // cardTotal
        // 
        cardTotal.BackColor = Color.FromArgb(15, 23, 42);
        cardTotal.Controls.Add(lblTotalCaption);
        cardTotal.Controls.Add(lblTotalValue);
        cardTotal.Dock = DockStyle.Fill;
        cardTotal.Location = new Point(0, 3);
        cardTotal.Margin = new Padding(0, 3, 3, 12);
        cardTotal.Name = "cardTotal";
        cardTotal.Padding = new Padding(18, 16, 18, 16);
        cardTotal.Size = new Size(361, 97);
        cardTotal.TabIndex = 0;
        // 
        // lblTotalCaption
        // 
        lblTotalCaption.AutoSize = true;
        lblTotalCaption.ForeColor = Color.FromArgb(191, 219, 254);
        lblTotalCaption.Location = new Point(20, 18);
        lblTotalCaption.Name = "lblTotalCaption";
        lblTotalCaption.Size = new Size(41, 20);
        lblTotalCaption.TabIndex = 1;
        lblTotalCaption.Text = "Total";
        // 
        // lblTotalValue
        // 
        lblTotalValue.AutoSize = true;
        lblTotalValue.Font = new Font("Segoe UI", 20F, FontStyle.Bold);
        lblTotalValue.ForeColor = Color.White;
        lblTotalValue.Location = new Point(18, 38);
        lblTotalValue.Name = "lblTotalValue";
        lblTotalValue.Size = new Size(41, 46);
        lblTotalValue.TabIndex = 0;
        lblTotalValue.Text = "0";
        // 
        // rightPanel
        // 
        rightPanel.Controls.Add(filterCardPanel);
        rightPanel.Dock = DockStyle.Fill;
        rightPanel.Location = new Point(1117, 3);
        rightPanel.Name = "rightPanel";
        rightPanel.Size = new Size(328, 673);
        rightPanel.TabIndex = 1;
        // 
        // filterCardPanel
        // 
        filterCardPanel.BackColor = Color.White;
        filterCardPanel.Controls.Add(btnResetFilters);
        filterCardPanel.Controls.Add(cmbMarque);
        filterCardPanel.Controls.Add(lblMarque);
        filterCardPanel.Controls.Add(cmbSousFamille);
        filterCardPanel.Controls.Add(lblSousFamille);
        filterCardPanel.Controls.Add(cmbFamille);
        filterCardPanel.Controls.Add(lblFamille);
        filterCardPanel.Controls.Add(cmbStock);
        filterCardPanel.Controls.Add(lblStock);
        filterCardPanel.Controls.Add(lblFiltersTitle);
        filterCardPanel.Dock = DockStyle.Top;
        filterCardPanel.Location = new Point(0, 0);
        filterCardPanel.Name = "filterCardPanel";
        filterCardPanel.Padding = new Padding(20);
        filterCardPanel.Size = new Size(328, 396);
        filterCardPanel.TabIndex = 0;
        // 
        // btnResetFilters
        // 
        btnResetFilters.Anchor = AnchorStyles.Top | AnchorStyles.Left | AnchorStyles.Right;
        btnResetFilters.BackColor = Color.FromArgb(248, 250, 252);
        btnResetFilters.FlatAppearance.BorderColor = Color.FromArgb(203, 213, 225);
        btnResetFilters.FlatStyle = FlatStyle.Flat;
        btnResetFilters.Font = new Font("Segoe UI", 9F);
        btnResetFilters.ForeColor = Color.FromArgb(51, 65, 85);
        btnResetFilters.Location = new Point(20, 334);
        btnResetFilters.Name = "btnResetFilters";
        btnResetFilters.Size = new Size(288, 38);
        btnResetFilters.TabIndex = 9;
        btnResetFilters.Text = "Reinitialiser les filtres";
        btnResetFilters.UseVisualStyleBackColor = false;
        // 
        // cmbMarque
        // 
        cmbMarque.Anchor = AnchorStyles.Top | AnchorStyles.Left | AnchorStyles.Right;
        cmbMarque.DropDownStyle = ComboBoxStyle.DropDownList;
        cmbMarque.FormattingEnabled = true;
        cmbMarque.Location = new Point(20, 286);
        cmbMarque.Name = "cmbMarque";
        cmbMarque.Size = new Size(288, 28);
        cmbMarque.TabIndex = 8;
        // 
        // lblMarque
        // 
        lblMarque.AutoSize = true;
        lblMarque.ForeColor = Color.FromArgb(30, 41, 59);
        lblMarque.Location = new Point(20, 263);
        lblMarque.Name = "lblMarque";
        lblMarque.Size = new Size(59, 20);
        lblMarque.TabIndex = 7;
        lblMarque.Text = "Marque";
        // 
        // cmbSousFamille
        // 
        cmbSousFamille.Anchor = AnchorStyles.Top | AnchorStyles.Left | AnchorStyles.Right;
        cmbSousFamille.DropDownStyle = ComboBoxStyle.DropDownList;
        cmbSousFamille.FormattingEnabled = true;
        cmbSousFamille.Location = new Point(20, 219);
        cmbSousFamille.Name = "cmbSousFamille";
        cmbSousFamille.Size = new Size(288, 28);
        cmbSousFamille.TabIndex = 6;
        // 
        // lblSousFamille
        // 
        lblSousFamille.AutoSize = true;
        lblSousFamille.ForeColor = Color.FromArgb(30, 41, 59);
        lblSousFamille.Location = new Point(20, 196);
        lblSousFamille.Name = "lblSousFamille";
        lblSousFamille.Size = new Size(91, 20);
        lblSousFamille.TabIndex = 5;
        lblSousFamille.Text = "Sous famille";
        // 
        // cmbFamille
        // 
        cmbFamille.Anchor = AnchorStyles.Top | AnchorStyles.Left | AnchorStyles.Right;
        cmbFamille.DropDownStyle = ComboBoxStyle.DropDownList;
        cmbFamille.FormattingEnabled = true;
        cmbFamille.Location = new Point(20, 152);
        cmbFamille.Name = "cmbFamille";
        cmbFamille.Size = new Size(288, 28);
        cmbFamille.TabIndex = 4;
        // 
        // lblFamille
        // 
        lblFamille.AutoSize = true;
        lblFamille.ForeColor = Color.FromArgb(30, 41, 59);
        lblFamille.Location = new Point(20, 129);
        lblFamille.Name = "lblFamille";
        lblFamille.Size = new Size(56, 20);
        lblFamille.TabIndex = 3;
        lblFamille.Text = "Famille";
        // 
        // cmbStock
        // 
        cmbStock.Anchor = AnchorStyles.Top | AnchorStyles.Left | AnchorStyles.Right;
        cmbStock.DropDownStyle = ComboBoxStyle.DropDownList;
        cmbStock.FormattingEnabled = true;
        cmbStock.Location = new Point(20, 85);
        cmbStock.Name = "cmbStock";
        cmbStock.Size = new Size(288, 28);
        cmbStock.TabIndex = 2;
        // 
        // lblStock
        // 
        lblStock.AutoSize = true;
        lblStock.ForeColor = Color.FromArgb(30, 41, 59);
        lblStock.Location = new Point(20, 62);
        lblStock.Name = "lblStock";
        lblStock.Size = new Size(45, 20);
        lblStock.TabIndex = 1;
        lblStock.Text = "Stock";
        // 
        // lblFiltersTitle
        // 
        lblFiltersTitle.AutoSize = true;
        lblFiltersTitle.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
        lblFiltersTitle.ForeColor = Color.FromArgb(15, 23, 42);
        lblFiltersTitle.Location = new Point(20, 20);
        lblFiltersTitle.Name = "lblFiltersTitle";
        lblFiltersTitle.Size = new Size(71, 28);
        lblFiltersTitle.TabIndex = 0;
        lblFiltersTitle.Text = "Filtres";
        // 
        // statusStrip
        // 
        statusStrip.ImageScalingSize = new Size(20, 20);
        statusStrip.Items.AddRange(new ToolStripItem[] { statusLabel });
        statusStrip.Location = new Point(0, 811);
        statusStrip.Name = "statusStrip";
        statusStrip.Size = new Size(1484, 26);
        statusStrip.TabIndex = 2;
        // 
        // statusLabel
        // 
        statusLabel.Name = "statusLabel";
        statusLabel.Size = new Size(50, 20);
        statusLabel.Text = "Pret.";
        // 
        // Form1
        // 
        AutoScaleDimensions = new SizeF(8F, 20F);
        AutoScaleMode = AutoScaleMode.Font;
        BackColor = Color.FromArgb(241, 245, 249);
        ClientSize = new Size(1484, 837);
        Controls.Add(contentPanel);
        Controls.Add(statusStrip);
        Controls.Add(headerPanel);
        MinimumSize = new Size(1200, 720);
        Name = "Form1";
        StartPosition = FormStartPosition.CenterScreen;
        Text = "PME Communicator";
        headerPanel.ResumeLayout(false);
        headerPanel.PerformLayout();
        contentPanel.ResumeLayout(false);
        bodyLayout.ResumeLayout(false);
        leftPanel.ResumeLayout(false);
        gridCardPanel.ResumeLayout(false);
        ((System.ComponentModel.ISupportInitialize)gridProducts).EndInit();
        gridTopPanel.ResumeLayout(false);
        gridTopPanel.PerformLayout();
        statsPanel.ResumeLayout(false);
        cardSelected.ResumeLayout(false);
        cardSelected.PerformLayout();
        cardVisible.ResumeLayout(false);
        cardVisible.PerformLayout();
        cardTotal.ResumeLayout(false);
        cardTotal.PerformLayout();
        rightPanel.ResumeLayout(false);
        filterCardPanel.ResumeLayout(false);
        filterCardPanel.PerformLayout();
        statusStrip.ResumeLayout(false);
        statusStrip.PerformLayout();
        ResumeLayout(false);
        PerformLayout();
    }

    #endregion

    private Panel headerPanel;
    private Button btnSettings;
    private Button btnReload;
    private Label lblSubtitle;
    private Label lblTitle;
    private Panel contentPanel;
    private TableLayoutPanel bodyLayout;
    private Panel leftPanel;
    private Panel gridCardPanel;
    private DataGridView gridProducts;
    private Panel gridTopPanel;
    private Button btnExport;
    private Button btnClearSelection;
    private Button btnSelectVisible;
    private TextBox txtFilter;
    private Label lblSearch;
    private TableLayoutPanel statsPanel;
    private Panel cardSelected;
    private Label lblSelectedCaption;
    private Label lblSelectedValue;
    private Panel cardVisible;
    private Label lblVisibleCaption;
    private Label lblVisibleValue;
    private Panel cardTotal;
    private Label lblTotalCaption;
    private Label lblTotalValue;
    private Panel rightPanel;
    private Panel filterCardPanel;
    private Button btnResetFilters;
    private ComboBox cmbMarque;
    private Label lblMarque;
    private ComboBox cmbSousFamille;
    private Label lblSousFamille;
    private ComboBox cmbFamille;
    private Label lblFamille;
    private ComboBox cmbStock;
    private Label lblStock;
    private Label lblFiltersTitle;
    private StatusStrip statusStrip;
    private ToolStripStatusLabel statusLabel;
}
