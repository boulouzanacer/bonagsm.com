# PME Communicator

Windows desktop app for browsing the Firebird `PRODUIT` table and exporting selected rows to Excel.

## What it does

- Prompts for the Firebird database path on first launch
- Saves connection settings locally for future launches
- Loads all rows from `PRODUIT`
- Filters rows across all loaded columns
- Lets you multi-select rows with checkboxes
- Exports the selected rows to `.xlsx`

## Run the published executable

Published executable:

- `PmeCommunicator\bin\Release\net8.0-windows\win-x64\publish\PmeCommunicator.exe`

## First launch

On first launch, the app opens a settings window.

Recommended defaults:

- Server: `localhost`
- Port: `3050`
- Username: `SYSDBA`
- Password: `masterkey`
- Charset: `UTF8`

The only required value is the Firebird database file path.

## Saved settings

The app stores its configuration here:

- `%LOCALAPPDATA%\PME Communicator\settings.json`

## Build again

From the repo root:

```powershell
dotnet build "PME communicator\PmeCommunicatorApp.slnx"
dotnet publish "PME communicator\PmeCommunicator\PmeCommunicator.csproj" -c Release -r win-x64 /p:PublishSingleFile=true /p:SelfContained=true
```
