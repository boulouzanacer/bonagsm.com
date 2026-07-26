namespace PMESync;

static class Program
{
    private const string StartMinimizedArgument = "--tray";

    /// <summary>
    ///  The main entry point for the application.
    /// </summary>
    [STAThread]
    static void Main(string[] args)
    {
        // To customize application configuration such as set high DPI settings or default font,
        // see https://aka.ms/applicationconfiguration.
        ApplicationConfiguration.Initialize();
        var startMinimizedToTray = args.Any(arg => string.Equals(arg, StartMinimizedArgument, StringComparison.OrdinalIgnoreCase));
        Application.Run(new Form1(startMinimizedToTray));
    }    
}
