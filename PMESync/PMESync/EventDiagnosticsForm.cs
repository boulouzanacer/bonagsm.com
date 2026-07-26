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
        BackColor = Color.FromArgb(241, 245, 249);

        var topPanel = new Panel
        {
            Dock = DockStyle.Top,
            Height = 56,
            Padding = new Padding(16, 12, 16, 8),
        };

        var title = new Label
        {
            AutoSize = true,
            Font = new Font("Segoe UI", 11F, FontStyle.Bold),
            ForeColor = Color.FromArgb(15, 23, 42),
            Text = "Journal des evenements Firebird",
            Location = new Point(16, 14),
        };

        btnClose.Text = "Fermer";
        btnClose.Anchor = AnchorStyles.Top | AnchorStyles.Right;
        btnClose.BackColor = Color.White;
        btnClose.FlatStyle = FlatStyle.Flat;
        btnClose.FlatAppearance.BorderColor = Color.FromArgb(203, 213, 225);
        btnClose.ForeColor = Color.FromArgb(51, 65, 85);
        btnClose.Size = new Size(96, 32);
        btnClose.Location = new Point(772, 12);
        btnClose.Click += (_, _) => Close();

        btnClear.Text = "Vider";
        btnClear.Anchor = AnchorStyles.Top | AnchorStyles.Right;
        btnClear.BackColor = Color.FromArgb(37, 99, 235);
        btnClear.FlatStyle = FlatStyle.Flat;
        btnClear.FlatAppearance.BorderSize = 0;
        btnClear.ForeColor = Color.White;
        btnClear.Size = new Size(96, 32);
        btnClear.Location = new Point(670, 12);
        btnClear.Click += (_, _) => ClearRequested?.Invoke(this, EventArgs.Empty);

        topPanel.Controls.Add(title);
        topPanel.Controls.Add(btnClear);
        topPanel.Controls.Add(btnClose);

        txtLog.Dock = DockStyle.Fill;
        txtLog.Multiline = true;
        txtLog.ReadOnly = true;
        txtLog.ScrollBars = ScrollBars.Both;
        txtLog.WordWrap = false;
        txtLog.Font = new Font("Consolas", 10F);
        txtLog.BackColor = Color.White;
        txtLog.BorderStyle = BorderStyle.FixedSingle;

        Controls.Add(txtLog);
        Controls.Add(topPanel);
    }

    public void SetEntries(IEnumerable<string> entries)
    {
        txtLog.Lines = entries.ToArray();
        txtLog.SelectionStart = txtLog.TextLength;
        txtLog.ScrollToCaret();
    }
}
