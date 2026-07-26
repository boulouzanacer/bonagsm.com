namespace PMESync;

public sealed class EventDiagnosticsForm : Form
{
    private readonly TextBox txtLog = new();
    private readonly Button btnClear = new();
    private readonly Button btnClose = new();

    public event EventHandler? ClearRequested;

    public EventDiagnosticsForm()
    {
        Text = "Diagnostic des evenements Firebird";
        Icon = AppIconProvider.GetApplicationIcon();
        StartPosition = FormStartPosition.CenterParent;
        MinimumSize = new Size(760, 420);
        Size = new Size(900, 520);
        UiTheme.ApplyDialogChrome(this);

        var topPanel = new Panel
        {
            Dock = DockStyle.Top,
            Height = 74,
            Padding = new Padding(20, 18, 20, 12),
            BackColor = UiTheme.HeaderBackground,
        };

        var title = new Label
        {
            AutoSize = true,
            Font = new Font("Segoe UI", 13F, FontStyle.Bold),
            ForeColor = Color.White,
            Text = "Journal des evenements Firebird",
            Location = new Point(20, 22),
        };

        btnClose.Text = "Fermer";
        btnClose.Anchor = AnchorStyles.Top | AnchorStyles.Right;
        btnClose.Size = new Size(96, 32);
        btnClose.Location = new Point(772, 20);
        btnClose.Click += (_, _) => Close();

        btnClear.Text = "Vider";
        btnClear.Anchor = AnchorStyles.Top | AnchorStyles.Right;
        btnClear.Size = new Size(96, 32);
        btnClear.Location = new Point(670, 20);
        btnClear.Click += (_, _) => ClearRequested?.Invoke(this, EventArgs.Empty);

        topPanel.Controls.Add(title);
        topPanel.Controls.Add(btnClear);
        topPanel.Controls.Add(btnClose);

        var logHost = new Panel
        {
            Dock = DockStyle.Fill,
            BackColor = UiTheme.CardBorder,
            Padding = new Padding(1),
            Margin = new Padding(20, 18, 20, 18),
        };

        txtLog.Dock = DockStyle.Fill;
        txtLog.Multiline = true;
        txtLog.ReadOnly = true;
        txtLog.ScrollBars = ScrollBars.Both;
        txtLog.WordWrap = false;
        UiTheme.StyleInput(txtLog, mono: true);
        txtLog.BorderStyle = BorderStyle.None;

        UiTheme.StylePrimaryButton(btnClear);
        UiTheme.StyleSecondaryButton(btnClose);

        logHost.Controls.Add(txtLog);
        Controls.Add(logHost);
        Controls.Add(topPanel);
    }

    public void SetEntries(IEnumerable<string> entries)
    {
        txtLog.Lines = entries.ToArray();
        txtLog.SelectionStart = txtLog.TextLength;
        txtLog.ScrollToCaret();
    }
}
