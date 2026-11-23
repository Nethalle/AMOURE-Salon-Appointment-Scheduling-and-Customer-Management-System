# AMOURE SALON - repository layout setup
# Run from inside your cloned repo folder:
#   powershell -ExecutionPolicy Bypass -File setup.ps1
# Safe to run again: existing files are never overwritten.

$ErrorActionPreference = "Stop"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)   # no BOM: a BOM breaks PHP sessions/headers

function New-ProjectFile([string]$path, [string]$content = "") {
    $full = Join-Path (Get-Location) $path
    $dir  = Split-Path $full -Parent
    if (-not (Test-Path $dir)) { New-Item -ItemType Directory -Path $dir -Force | Out-Null }
    if (Test-Path $full) { Write-Host "  skip   $path (already exists)"; return }
    [System.IO.File]::WriteAllText($full, $content, $utf8NoBom)
    Write-Host "  create $path"
}

function New-PhpStub([string]$path, [string]$note) {
    New-ProjectFile $path "<?php`n// $note`n"
}

Write-Host "Setting up AMOURE SALON layout in $(Get-Location)`n"

# ---------- root ----------
New-PhpStub "index.php" "Entry point + router: reads ?page= and calls the matching controller."

New-ProjectFile ".htaccess" @"
Options -Indexes
RedirectMatch 403 /(backend|database|vendor|frontend/views)(/|$)
RedirectMatch 403 /\.git
"@

New-ProjectFile ".gitignore" @"
/vendor/
/backend/config/database.php
/backend/config/mail.php
/backend/logs/*
!/backend/logs/.gitkeep
.DS_Store
Thumbs.db
"@

New-ProjectFile "composer.json" @"
{
    "name": "fedoc/amoure-salon",
    "description": "AMOURE SALON: Salon Appointment Scheduling and Customer Management System",
    "require": {
        "php": ">=8.0",
        "phpmailer/phpmailer": "^6.9"
    }
}
"@

# ---------- frontend ----------
New-ProjectFile "frontend/assets/css/style.css" "/* AMOURE SALON styles */`n"
New-ProjectFile "frontend/assets/js/app.js" "// Form hints, filters, confirm dialogs`n"
New-ProjectFile "frontend/assets/img/.gitkeep"

foreach ($f in "header","footer","sidebar") { New-PhpStub "frontend/views/layouts/$f.php" "Layout: $f" }
foreach ($f in "home","login","register") { New-PhpStub "frontend/views/public/$f.php" "Public view: $f" }
foreach ($f in "dashboard","users","services","stylists","settings","reviews","reports") { New-PhpStub "frontend/views/admin/$f.php" "Admin view: $f" }
foreach ($f in "dashboard","customers","appointments","schedule") { New-PhpStub "frontend/views/staff/$f.php" "Staff view: $f" }
foreach ($f in "book","my-appointments","review") { New-PhpStub "frontend/views/customer/$f.php" "Customer view: $f" }
New-PhpStub "frontend/views/emails/confirmation.php" "Booking confirmed / rescheduled email template"
New-PhpStub "frontend/views/emails/cancellation.php" "Booking cancelled email template"
New-PhpStub "frontend/views/emails/reminder.php"     "Reminder email template"

# ---------- backend ----------
New-ProjectFile "backend/.htaccess" "Require all denied`n"

New-ProjectFile "backend/config/database.example.php" @"
<?php
// Copy to database.php and fill in.
return [
    'host' => 'localhost',
    'name' => 'amoure_salon_db',
    'user' => 'root',
    'pass' => '',
];
"@

New-ProjectFile "backend/config/mail.example.php" @"
<?php
// Copy to mail.php and fill in. Use a Gmail App Password, not your normal password.
return [
    'host'       => 'smtp.gmail.com',
    'port'       => 587,
    'encryption' => 'tls',
    'username'   => 'your-salon-email@gmail.com',
    'password'   => 'your-16-character-app-password',
    'from_email' => 'your-salon-email@gmail.com',
    'from_name'  => 'AMOURÉ SALON',
];
"@

New-PhpStub "backend/core/db.php"      "PDO connection"
New-PhpStub "backend/core/auth.php"    "Session, login check, require_role()"
New-PhpStub "backend/core/csrf.php"    "CSRF token helpers"
New-PhpStub "backend/core/helpers.php" "Escaping, redirects, flash messages"

foreach ($c in "Auth","Dashboard","Customer","Service","Stylist","Appointment","Review","Report","Settings") {
    New-PhpStub "backend/controllers/${c}Controller.php" "$c controller"
}

New-PhpStub "backend/services/BookingRules.php"    "Overlap + daily-limit checks"
New-PhpStub "backend/services/NotificationService.php" "Confirmation + cancellation emails"
New-PhpStub "backend/services/ReminderService.php" "Finds due appointments, sends reminders"
New-PhpStub "backend/services/Mailer.php"          "PHPMailer wrapper"
New-PhpStub "backend/services/ReportService.php"   "Report queries"

New-ProjectFile "backend/jobs/send_reminders.php" @"
<?php
// Run by Task Scheduler / cron. Command line only.
if (php_sapi_name() !== 'cli') { http_response_code(403); exit('Forbidden'); }
"@

New-ProjectFile "backend/logs/.gitkeep"

# ---------- database ----------
New-ProjectFile "database/.htaccess" "Require all denied`n"
New-ProjectFile "database/schema.sql" "-- AMOURE SALON schema: tables, keys and indexes`n"
New-ProjectFile "database/seed.sql"   "-- AMOURE SALON sample data`n"

# ---------- local config copies (ignored by git) ----------
if (-not (Test-Path "backend/config/database.php")) { Copy-Item "backend/config/database.example.php" "backend/config/database.php"; Write-Host "  create backend/config/database.php (local only)" }
if (-not (Test-Path "backend/config/mail.php"))     { Copy-Item "backend/config/mail.example.php"     "backend/config/mail.php";     Write-Host "  create backend/config/mail.php (local only)" }

Write-Host "`nDone. Next: composer install, then git add / commit / push."
