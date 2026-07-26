namespace PMESync;

internal static class UiTheme
{
    public static Color AppBackground => Color.FromArgb(241, 245, 249);
    public static Color CardBackground => Color.White;
    public static Color CardAltBackground => Color.FromArgb(248, 250, 252);
    public static Color CardBorder => Color.FromArgb(226, 232, 240);
    public static Color HeaderBackground => Color.FromArgb(15, 23, 42);
    public static Color HeaderAccent => Color.FromArgb(37, 99, 235);
    public static Color SecondaryAccent => Color.FromArgb(14, 165, 233);
    public static Color SuccessAccent => Color.FromArgb(22, 163, 74);
    public static Color WarningAccent => Color.FromArgb(217, 119, 6);
    public static Color DangerAccent => Color.FromArgb(220, 38, 38);
    public static Color TextPrimary => Color.FromArgb(15, 23, 42);
    public static Color TextSecondary => Color.FromArgb(100, 116, 139);

    public static void ApplyDialogChrome(Form form)
    {
        form.BackColor = AppBackground;
        form.Font = new Font("Segoe UI", 9.5F, FontStyle.Regular);
    }

    public static void StyleCard(Control control)
    {
        control.BackColor = CardBackground;
        control.ForeColor = TextPrimary;
    }

    public static void StylePrimaryButton(Button button)
    {
        button.FlatStyle = FlatStyle.Flat;
        button.FlatAppearance.BorderSize = 0;
        button.BackColor = HeaderAccent;
        button.ForeColor = Color.White;
        button.Font = new Font("Segoe UI Semibold", 9.5F, FontStyle.Bold);
        button.Cursor = Cursors.Hand;
    }

    public static void StyleSecondaryButton(Button button)
    {
        button.FlatStyle = FlatStyle.Flat;
        button.FlatAppearance.BorderColor = CardBorder;
        button.FlatAppearance.BorderSize = 1;
        button.BackColor = CardAltBackground;
        button.ForeColor = TextPrimary;
        button.Font = new Font("Segoe UI Semibold", 9.5F, FontStyle.Bold);
        button.Cursor = Cursors.Hand;
    }

    public static void StyleInput(TextBox textBox, bool mono = false)
    {
        textBox.BorderStyle = BorderStyle.FixedSingle;
        textBox.BackColor = Color.White;
        textBox.ForeColor = TextPrimary;
        textBox.Font = mono
            ? new Font("Consolas", 10F, FontStyle.Regular)
            : new Font("Segoe UI", 9.5F, FontStyle.Regular);
    }

    public static void StyleComboBox(ComboBox comboBox)
    {
        comboBox.BackColor = Color.White;
        comboBox.ForeColor = TextPrimary;
        comboBox.Font = new Font("Segoe UI", 9.5F, FontStyle.Regular);
        comboBox.FlatStyle = FlatStyle.Flat;
    }

    public static void StyleNumericUpDown(NumericUpDown numeric)
    {
        numeric.BackColor = Color.White;
        numeric.ForeColor = TextPrimary;
        numeric.Font = new Font("Segoe UI", 9.5F, FontStyle.Regular);
        numeric.BorderStyle = BorderStyle.FixedSingle;
    }

    public static void StyleCheckBox(CheckBox checkBox)
    {
        checkBox.ForeColor = TextPrimary;
        checkBox.Font = new Font("Segoe UI Semibold", 9.25F, FontStyle.Bold);
    }

    public static void StyleLabel(Label label, bool secondary = false, bool caption = false)
    {
        label.ForeColor = secondary ? TextSecondary : TextPrimary;
        label.Font = caption
            ? new Font("Segoe UI", 8.75F, FontStyle.Bold)
            : new Font("Segoe UI", 9.5F, FontStyle.Regular);
    }

    public static void StyleDataGrid(DataGridView grid, Color headerBackColor, int rowHeight = 40)
    {
        grid.BorderStyle = BorderStyle.None;
        grid.BackgroundColor = CardBackground;
        grid.GridColor = CardBorder;
        grid.CellBorderStyle = DataGridViewCellBorderStyle.SingleHorizontal;
        grid.EnableHeadersVisualStyles = false;
        grid.ColumnHeadersBorderStyle = DataGridViewHeaderBorderStyle.None;
        grid.ColumnHeadersDefaultCellStyle.BackColor = headerBackColor;
        grid.ColumnHeadersDefaultCellStyle.ForeColor = Color.White;
        grid.ColumnHeadersDefaultCellStyle.SelectionBackColor = headerBackColor;
        grid.ColumnHeadersDefaultCellStyle.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
        grid.DefaultCellStyle.BackColor = CardBackground;
        grid.DefaultCellStyle.ForeColor = TextPrimary;
        grid.DefaultCellStyle.SelectionBackColor = Color.FromArgb(239, 246, 255);
        grid.DefaultCellStyle.SelectionForeColor = TextPrimary;
        grid.DefaultCellStyle.Padding = new Padding(8, 6, 8, 6);
        grid.DefaultCellStyle.Font = new Font("Segoe UI", 9.5F, FontStyle.Regular);
        grid.AlternatingRowsDefaultCellStyle.BackColor = CardAltBackground;
        grid.RowTemplate.Height = rowHeight;
        grid.RowHeadersVisible = false;
    }

    public static Panel CreateCardPanel(Padding? padding = null)
    {
        return new Panel
        {
            BackColor = CardBackground,
            Padding = padding ?? new Padding(20),
            Margin = new Padding(0),
        };
    }
}
