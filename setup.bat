@echo off
echo ==============================================
echo  OpenVulnTracker - Setup Script
echo ==============================================
echo.
echo Make sure XAMPP MySQL is running before continuing!
echo.
pause

echo [1/3] Creating database...
mysql -u root -e "CREATE DATABASE IF NOT EXISTS openvulntracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if %errorlevel% neq 0 (
    echo ERROR: Could not connect to MySQL. Start XAMPP MySQL first.
    pause
    exit /b 1
)

echo [2/3] Running migrations...
php artisan migrate --force

echo [3/3] Creating admin user...
php artisan db:seed --force

echo.
echo ==============================================
echo  Setup complete!
echo.
echo  Login at: http://localhost/openvulntracker/public
echo  Email:    admin@vulntracker.local
echo  Password: Admin@1234
echo.
echo  Change your password after first login!
echo ==============================================
pause
