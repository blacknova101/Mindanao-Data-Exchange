@echo off
echo Setting up scheduled task for organization membership request expiration...

REM Get the current directory
set CURRENT_DIR=%~dp0
set PHP_PATH="C:\xampp\php\php.exe"
set SCRIPT_PATH="%CURRENT_DIR%expire_requests.php"

REM Create the scheduled task
schtasks /create /tn "MindanaoDataExchange_ExpireRequests" /tr "%PHP_PATH% %SCRIPT_PATH%" /sc DAILY /st 00:00 /ru SYSTEM

echo.
if %ERRORLEVEL% EQU 0 (
    echo Task created successfully! The expire_requests.php script will run daily at midnight.
    echo Task Name: MindanaoDataExchange_ExpireRequests
) else (
    echo Failed to create the scheduled task. Please run this script as administrator.
)

echo.
pause 