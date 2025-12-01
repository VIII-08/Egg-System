<?php
/**
 * Environment Configuration Validator
 * 
 * Run this script to check if your .env file is properly configured for production
 * Usage: php check-env.php
 */

echo "🔍 Checking .env Configuration...\n\n";

// Check if .env file exists
if (!file_exists('.env')) {
    echo "❌ ERROR: .env file not found!\n";
    echo "   Create a .env file based on .env.example\n";
    exit(1);
}

// Load .env file
$envFile = file_get_contents('.env');
$lines = explode("\n", $envFile);
$env = [];

foreach ($lines as $line) {
    $line = trim($line);
    // Skip empty lines and comments
    if (empty($line) || strpos($line, '#') === 0) {
        continue;
    }
    
    // Parse KEY=VALUE pairs
    if (strpos($line, '=') !== false) {
        list($key, $value) = explode('=', $line, 2);
        $env[trim($key)] = trim($value);
    }
}

$errors = [];
$warnings = [];
$info = [];

// Critical checks
echo "📋 CRITICAL CHECKS:\n";
echo str_repeat("=", 50) . "\n\n";

// 1. APP_DEBUG
if (!isset($env['APP_DEBUG'])) {
    $errors[] = "APP_DEBUG is not set";
} elseif ($env['APP_DEBUG'] === 'true') {
    $errors[] = "❌ APP_DEBUG=true - MUST be false for production!";
} else {
    echo "✅ APP_DEBUG=" . $env['APP_DEBUG'] . "\n";
}

// 2. APP_ENV
if (!isset($env['APP_ENV'])) {
    $warnings[] = "APP_ENV is not set (defaults to 'production')";
} elseif ($env['APP_ENV'] !== 'production') {
    $warnings[] = "⚠️  APP_ENV=" . $env['APP_ENV'] . " (should be 'production' for production)";
} else {
    echo "✅ APP_ENV=" . $env['APP_ENV'] . "\n";
}

// 3. APP_KEY
if (!isset($env['APP_KEY']) || empty($env['APP_KEY'])) {
    $errors[] = "❌ APP_KEY is not set! Run: php artisan key:generate";
} elseif (strlen($env['APP_KEY']) < 20) {
    $errors[] = "❌ APP_KEY appears to be too short";
} else {
    echo "✅ APP_KEY is set\n";
}

// 4. APP_URL
if (!isset($env['APP_URL']) || empty($env['APP_URL'])) {
    $warnings[] = "⚠️  APP_URL is not set";
} elseif (strpos($env['APP_URL'], 'localhost') !== false || strpos($env['APP_URL'], '127.0.0.1') !== false) {
    $warnings[] = "⚠️  APP_URL contains localhost/127.0.0.1 - Update to your production domain";
} elseif (strpos($env['APP_URL'], 'http://') !== false) {
    $warnings[] = "⚠️  APP_URL uses HTTP - Consider using HTTPS for production";
} else {
    echo "✅ APP_URL=" . $env['APP_URL'] . "\n";
}

// 5. Database Configuration
echo "\n📊 DATABASE CONFIGURATION:\n";
echo str_repeat("=", 50) . "\n\n";

if (!isset($env['DB_CONNECTION']) || empty($env['DB_CONNECTION'])) {
    $warnings[] = "DB_CONNECTION not set (defaults to mysql)";
} else {
    echo "✅ DB_CONNECTION=" . $env['DB_CONNECTION'] . "\n";
}

if (!isset($env['DB_HOST']) || empty($env['DB_HOST'])) {
    $errors[] = "❌ DB_HOST is not set";
} else {
    echo "✅ DB_HOST=" . $env['DB_HOST'] . "\n";
}

if (!isset($env['DB_PORT']) || empty($env['DB_PORT'])) {
    $warnings[] = "DB_PORT not set (defaults to 3306)";
} else {
    echo "✅ DB_PORT=" . $env['DB_PORT'] . "\n";
}

if (!isset($env['DB_DATABASE']) || empty($env['DB_DATABASE'])) {
    $errors[] = "❌ DB_DATABASE is not set";
} else {
    echo "✅ DB_DATABASE=" . $env['DB_DATABASE'] . "\n";
}

if (!isset($env['DB_USERNAME']) || empty($env['DB_USERNAME'])) {
    $errors[] = "❌ DB_USERNAME is not set";
} else {
    echo "✅ DB_USERNAME=" . $env['DB_USERNAME'] . "\n";
}

if (!isset($env['DB_PASSWORD'])) {
    $warnings[] = "⚠️  DB_PASSWORD is not set (may be empty, but not recommended)";
} elseif (empty($env['DB_PASSWORD'])) {
    $warnings[] = "⚠️  DB_PASSWORD is empty - Use a strong password for production";
} elseif (strlen($env['DB_PASSWORD']) < 8) {
    $warnings[] = "⚠️  DB_PASSWORD is too short (less than 8 characters)";
} else {
    echo "✅ DB_PASSWORD is set (length: " . strlen($env['DB_PASSWORD']) . ")\n";
}

// 6. Session Configuration
echo "\n🍪 SESSION CONFIGURATION:\n";
echo str_repeat("=", 50) . "\n\n";

if (!isset($env['SESSION_DRIVER']) || empty($env['SESSION_DRIVER'])) {
    $warnings[] = "SESSION_DRIVER not set (defaults to 'database')";
} else {
    echo "✅ SESSION_DRIVER=" . $env['SESSION_DRIVER'] . "\n";
}

if (isset($env['APP_URL']) && strpos($env['APP_URL'], 'https://') !== false) {
    if (!isset($env['SESSION_SECURE_COOKIE']) || $env['SESSION_SECURE_COOKIE'] !== 'true') {
        $errors[] = "❌ SESSION_SECURE_COOKIE should be 'true' when using HTTPS";
    } else {
        echo "✅ SESSION_SECURE_COOKIE=" . $env['SESSION_SECURE_COOKIE'] . "\n";
    }
} else {
    if (isset($env['SESSION_SECURE_COOKIE']) && $env['SESSION_SECURE_COOKIE'] === 'true') {
        $warnings[] = "⚠️  SESSION_SECURE_COOKIE=true but APP_URL doesn't use HTTPS";
    }
}

// 7. Mail Configuration (Optional but recommended)
echo "\n📧 MAIL CONFIGURATION (Optional):\n";
echo str_repeat("=", 50) . "\n\n";

if (!isset($env['MAIL_MAILER']) || empty($env['MAIL_MAILER'])) {
    $info[] = "MAIL_MAILER not set (email features may not work)";
} else {
    echo "✅ MAIL_MAILER=" . $env['MAIL_MAILER'] . "\n";
}

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 SUMMARY\n";
echo str_repeat("=", 50) . "\n\n";

if (empty($errors) && empty($warnings)) {
    echo "✅ All critical checks passed!\n";
    echo "✅ Your .env file looks good for production!\n\n";
} else {
    if (!empty($errors)) {
        echo "❌ ERRORS (Must Fix):\n";
        foreach ($errors as $error) {
            echo "   $error\n";
        }
        echo "\n";
    }
    
    if (!empty($warnings)) {
        echo "⚠️  WARNINGS (Should Fix):\n";
        foreach ($warnings as $warning) {
            echo "   $warning\n";
        }
        echo "\n";
    }
    
    if (!empty($info)) {
        echo "ℹ️  INFO:\n";
        foreach ($info as $i) {
            echo "   $i\n";
        }
        echo "\n";
    }
}

// Production readiness check
$isProductionReady = empty($errors);

if ($isProductionReady) {
    echo "🎉 Your .env file is PRODUCTION READY!\n";
    echo "\nNext steps:\n";
    echo "1. Run: php artisan config:cache\n";
    echo "2. Run: php artisan route:cache\n";
    echo "3. Run: php artisan view:cache\n";
    echo "4. Test your application\n";
} else {
    echo "⚠️  Your .env file needs fixes before production deployment.\n";
    echo "\nPlease fix the errors above and run this script again.\n";
}

echo "\n";





