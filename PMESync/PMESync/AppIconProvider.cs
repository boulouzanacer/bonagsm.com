namespace PMESync;

internal static class AppIconProvider
{
    private static Icon? cachedIcon;

    public static Icon GetApplicationIcon()
    {
        cachedIcon ??= LoadIcon();
        return (Icon)cachedIcon.Clone();
    }

    private static Icon LoadIcon()
    {
        try
        {
            var executableIcon = Icon.ExtractAssociatedIcon(Application.ExecutablePath);
            if (executableIcon is not null)
            {
                return (Icon)executableIcon.Clone();
            }
        }
        catch
        {
        }

        foreach (var candidatePath in GetAssetCandidatePaths())
        {
            try
            {
                if (File.Exists(candidatePath))
                {
                    return new Icon(candidatePath);
                }
            }
            catch
            {
            }
        }

        return SystemIcons.Application;
    }

    private static IEnumerable<string> GetAssetCandidatePaths()
    {
        yield return Path.Combine(AppContext.BaseDirectory, "Assets", "PmeSync.ico");
        yield return Path.Combine(AppContext.BaseDirectory, "PmeSync.ico");
        yield return Path.Combine(AppContext.BaseDirectory, "..", "..", "..", "Assets", "PmeSync.ico");
    }
}
