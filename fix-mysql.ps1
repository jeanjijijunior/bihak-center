# MySQL Permission Fix Script for XAMPP
# This script fixes the "Host 'localhost' is not allowed to connect" error

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "MySQL Permission Fix Script" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""


# Step 1: Stop MySQL if running
Write-Host "[Step 1] Checking if MySQL is running..." -ForegroundColor Yellow
$mysqlProcess = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue
if ($mysqlProcess) {
    Write-Host "✓ MySQL is currently running (PID: $($mysqlProcess.Id))" -ForegroundColor Green
    Write-Host "[Step 2] Stopping MySQL..." -ForegroundColor Yellow
    Stop-Process -Name "mysqld" -Force -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 3
    Write-Host "✓ MySQL stopped" -ForegroundColor Green
} else {
    Write-Host "✓ MySQL is not running" -ForegroundColor Green
}
Write-Host ""


# Step 2: Start MySQL in safe mode (skip-grant-tables)
$mysqldPath = "C:\xampp\mysql\bin\mysqld.exe"
$mysqlPath = "C:\xampp\mysql\bin\mysql.exe"
$mysqlcheckPath = "C:\xampp\mysql\bin\mysqlcheck.exe"
Write-Host "[Step 3] Starting MySQL in safe mode (bypass permissions)..." -ForegroundColor Yellow
$mysqldJob = Start-Process -FilePath $mysqldPath -ArgumentList "--skip-grant-tables" -NoNewWindow -PassThru
Write-Host "✓ MySQL started in safe mode (PID: $($mysqldJob.Id))" -ForegroundColor Green
Write-Host "⏱  Waiting 5 seconds for MySQL to initialize..." -ForegroundColor Yellow
Start-Sleep -Seconds 5
Write-Host ""

# Step 3: Repair tables (Aria/MyISAM)
Write-Host "[Step 4] Repairing all tables (Aria/MyISAM)..." -ForegroundColor Yellow
try {
    $repairOutput = & $mysqlcheckPath --repair --all-databases --use-frm -u root 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ Tables repaired successfully!" -ForegroundColor Green
    } else {
        Write-Host "⚠ Warning: mysqlcheck returned exit code $LASTEXITCODE" -ForegroundColor Yellow
        Write-Host "Output: $repairOutput" -ForegroundColor Gray
    }
} catch {
    Write-Host "✗ Error repairing tables: $_" -ForegroundColor Red
}
Write-Host ""

# Step 4: Connect to MySQL and fix permissions
Write-Host "[Step 5] Fixing root user permissions..." -ForegroundColor Yellow
$sqlCommands = @"
FLUSH PRIVILEGES;
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' IDENTIFIED BY '' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' IDENTIFIED BY '' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON *.* TO 'root'@'::1' IDENTIFIED BY '' WITH GRANT OPTION;
UPDATE mysql.user SET plugin='mysql_native_password' WHERE user='root';
FLUSH PRIVILEGES;
"@
$tempSqlFile = "$env:TEMP\fix_mysql_permissions.sql"
$sqlCommands | Out-File -FilePath $tempSqlFile -Encoding ASCII
try {
    $sqlText = Get-Content -Raw $tempSqlFile
    $output = & $mysqlPath -u root --execute="$sqlText" 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ Permissions fixed successfully!" -ForegroundColor Green
    } else {
        Write-Host "⚠ Warning: MySQL returned exit code $LASTEXITCODE" -ForegroundColor Yellow
        Write-Host "Output: $output" -ForegroundColor Gray
    }
} catch {
    Write-Host "✗ Error executing SQL commands: $_" -ForegroundColor Red
}
Remove-Item -Path $tempSqlFile -ErrorAction SilentlyContinue
Write-Host ""

# Step 4: Kill safe mode and restart MySQL normally
Write-Host "[Step 5] Stopping MySQL safe mode..." -ForegroundColor Yellow
Stop-Process -Name "mysqld" -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 3
Write-Host "[Step 6] Starting MySQL normally..." -ForegroundColor Yellow
Start-Process -FilePath $mysqldPath -NoNewWindow -PassThru
Write-Host "✓ MySQL restarted normally." -ForegroundColor Green

# Step 5: Restart MySQL normally
Write-Host "[Step 5] Restarting MySQL in normal mode..." -ForegroundColor Yellow

# Stop safe mode MySQL
Stop-Process -Id $mysqldJob.Id -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 3

# Start MySQL normally
$mysqldNormal = Start-Process -FilePath $mysqldPath `
    -NoNewWindow `
    -PassThru

Write-Host "✓ MySQL restarted normally (PID: $($mysqldNormal.Id))" -ForegroundColor Green
Write-Host "⏱  Waiting 5 seconds for MySQL to initialize..." -ForegroundColor Yellow
Start-Sleep -Seconds 5

Write-Host ""

# Step 6: Test connection
Write-Host "[Step 6] Testing database connection..." -ForegroundColor Yellow

try {
    $testOutput = & $mysqlPath -u root -e "SELECT 'Connection Successful!' AS Status;" 2>&1

    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ DATABASE CONNECTION WORKS!" -ForegroundColor Green
        Write-Host "✓ MySQL is ready to use!" -ForegroundColor Green
    } else {
        Write-Host "✗ Connection test failed" -ForegroundColor Red
        Write-Host "Output: $testOutput" -ForegroundColor Gray
    }
} catch {
    Write-Host "✗ Error testing connection: $_" -ForegroundColor Red
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Fix Complete!" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Open: http://localhost/bihak-center/test_connection.php" -ForegroundColor White
Write-Host "   (Should show green checkmarks ✓)" -ForegroundColor Gray
Write-Host ""
Write-Host "2. Then run: http://localhost/bihak-center/install_via_admin.php" -ForegroundColor White
Write-Host "   (To install the incubation platform)" -ForegroundColor Gray
Write-Host ""
Write-Host "Press any key to exit..." -ForegroundColor Cyan
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
