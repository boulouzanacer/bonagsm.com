using Microsoft.Data.Sqlite;

namespace PmeCommunicator;

public sealed record EventLogRecord(long Id, DateTime LoggedAt, string EventType, string Message);

public static class EventLogStore
{
    private static readonly object SyncRoot = new();

    public static void Initialize()
    {
        Directory.CreateDirectory(AppSettingsStore.GetSettingsDirectory());

        lock (SyncRoot)
        {
            using var connection = OpenConnection();
            using var command = connection.CreateCommand();
            command.CommandText =
                """
                CREATE TABLE IF NOT EXISTS event_logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    logged_at TEXT NOT NULL,
                    event_type TEXT NOT NULL,
                    message TEXT NOT NULL
                );
                """;
            command.ExecuteNonQuery();
        }
    }

    public static EventLogRecord Append(string eventType, string message)
    {
        var loggedAt = DateTime.Now;

        lock (SyncRoot)
        {
            using var connection = OpenConnection();
            using var command = connection.CreateCommand();
            command.CommandText =
                """
                INSERT INTO event_logs (logged_at, event_type, message)
                VALUES ($logged_at, $event_type, $message);
                SELECT last_insert_rowid();
                """;
            command.Parameters.AddWithValue("$logged_at", loggedAt.ToString("O"));
            command.Parameters.AddWithValue("$event_type", eventType);
            command.Parameters.AddWithValue("$message", message);

            var id = (long)(command.ExecuteScalar() ?? 0L);
            return new EventLogRecord(id, loggedAt, eventType, message);
        }
    }

    public static IReadOnlyList<EventLogRecord> GetRecent(int count)
    {
        var entries = new List<EventLogRecord>();

        lock (SyncRoot)
        {
            using var connection = OpenConnection();
            using var command = connection.CreateCommand();
            command.CommandText =
                """
                SELECT id, logged_at, event_type, message
                FROM event_logs
                ORDER BY id DESC
                LIMIT $count;
                """;
            command.Parameters.AddWithValue("$count", count);

            using var reader = command.ExecuteReader();
            while (reader.Read())
            {
                var timestamp = reader.GetString(1);
                entries.Add(new EventLogRecord(
                    reader.GetInt64(0),
                    DateTime.TryParse(timestamp, null, System.Globalization.DateTimeStyles.RoundtripKind, out var loggedAt)
                        ? loggedAt
                        : DateTime.Now,
                    reader.GetString(2),
                    reader.GetString(3)));
            }
        }

        entries.Reverse();
        return entries;
    }

    public static void ClearAll()
    {
        lock (SyncRoot)
        {
            using var connection = OpenConnection();
            using var command = connection.CreateCommand();
            command.CommandText = "DELETE FROM event_logs;";
            command.ExecuteNonQuery();
        }
    }

    private static SqliteConnection OpenConnection()
    {
        var builder = new SqliteConnectionStringBuilder
        {
            DataSource = Path.Combine(AppSettingsStore.GetSettingsDirectory(), "event_logs.db"),
            Mode = SqliteOpenMode.ReadWriteCreate,
            Cache = SqliteCacheMode.Shared,
        };

        var connection = new SqliteConnection(builder.ToString());
        connection.Open();
        return connection;
    }
}
