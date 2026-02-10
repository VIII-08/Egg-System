<?php
/**
 * Pre-Deployment Verification Script
 * 
 * Run this before uploading to Hostinger to ensure everything is ready
 * Usage: php pre-deployment-check.php
 */

echo "🔍 PRE-DEPLOYMENT VERIFICATION CHECK\n";
echo str_repeat("=", 60) . "\n\n";

$errors = [];
$warnings = [];
$passed = [];

// 1. Check critical files exist
echo "📁 FILE STRUCTURE CHECK:\n";
echo str_repeat("-", 60) . "\n";

$criticalFiles = [
    'composer.json' => 'Composer configuration',
    'package.json' => 'Node package configuration',
    'public/index.php' => 'Application entry point',
    'public/.htaccess' => 'Apache rewrite rules',
    'routes/web.php' => 'Web routes',
    'bootstrap/app.php' => 'Bootstrap application (Laravel 11+)',
    'config/app.php' => 'Application config',
    'storage/logs/.gitignore' => 'Logs directory',
];

foreach ($criticalFiles as $file => $description) {
    if (file_exists($file)) {
        echo "✅ {$description}: {$file}\n";
        $passed[] = $file;
    } else {
        echo "❌ {$description}: {$file} - MISSING!\n";
        $errors[] = "Missing file: {$file}";
    }
}

// 2. Check .env.example exists (but .env should NOT be in repo)
echo "\n📝 ENVIRONMENT FILES:\n";
echo str_repeat("-", 60) . "\n";

if (file_exists('.env.example')) {
    echo "✅ .env.example exists\n";
    $passed[] = '.env.example';
} else {
    echo "⚠️  .env.example not found (optional but recommended)\n";
    $warnings[] = ".env.example not found";
}

if (file_exists('.env')) {
    echo "⚠️  .env file found in repository - Should NOT be committed!\n";
    $warnings[] = ".env file should not be in repository";
} else {
    echo "✅ .env file not in repository (correct)\n";
    $passed[] = '.env not in repo';
}

// 3. Check .gitignore
echo "\n🔒 GIT CONFIGURATION:\n";
echo str_repeat("-", 60) . "\n";

if (file_exists('.gitignore')) {
    $gitignore = file_get_contents('.gitignore');
    $shouldIgnore = ['.env', 'vendor', 'node_modules', 'storage/logs', 'public/storage'];
    $missing = [];
    
    foreach ($shouldIgnore as $item) {
        if (strpos($gitignore, $item) === false) {
            $missing[] = $item;
        }
    }
    
    if (empty($missing)) {
        echo "✅ .gitignore properly configured\n";
        $passed[] = '.gitignore';
    } else {
        echo "⚠️  .gitignore missing entries: " . implode(', ', $missing) . "\n";
        $warnings[] = ".gitignore missing entries";
    }
} else {
    echo "❌ .gitignore not found!\n";
    $errors[] = ".gitignore not found";
}

// 4. Check for hardcoded paths
echo "\n🛣️  HARDCODED PATHS CHECK:\n";
echo str_repeat("-", 60) . "\n";

$filesToCheck = [
    'app/Console/Commands/RunForecast.php',
    'app/Console/Commands/RunForecastIfNeeded.php',
];

$foundHardcoded = false;
foreach ($filesToCheck as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        // Check for Windows-specific hardcoded paths (excluding fallback detection)
        if (preg_match('/C:\\\\Users\\\\[^"]+/', $content)) {
            echo "⚠️  Found hardcoded Windows user path in: {$file}\n";
            $warnings[] = "Hardcoded path in {$file}";
            $foundHardcoded = true;
        }
    }
}

if (!$foundHardcoded) {
    echo "✅ No problematic hardcoded paths found\n";
    $passed[] = 'No hardcoded paths';
}

// 5. Check Python script exists
echo "\n🐍 PYTHON FORECASTING:\n";
echo str_repeat("-", 60) . "\n";

if (file_exists('forecasting_scripts/run_forecast.py')) {
    echo "✅ Python forecasting script exists\n";
    $passed[] = 'Python script';
    
    if (file_exists('forecasting_scripts/data/historical_data.csv')) {
        echo "✅ Historical data CSV directory exists\n";
        $passed[] = 'CSV directory';
    } else {
        echo "⚠️  Historical data CSV directory not found (will be created on first run)\n";
        $warnings[] = "CSV directory not found";
    }
} else {
    echo "⚠️  Python forecasting script not found (forecasting will use fallback)\n";
    $warnings[] = "Python script not found";
}

// 6. Check storage directories
echo "\n💾 STORAGE DIRECTORIES:\n";
echo str_repeat("-", 60) . "\n";

$storageDirs = [
    'storage/app',
    'storage/app/public',
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'bootstrap/cache',
];

foreach ($storageDirs as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "✅ {$dir} (writable)\n";
            $passed[] = $dir;
        } else {
            echo "⚠️  {$dir} (exists but not writable)\n";
            $warnings[] = "{$dir} not writable";
        }
    } else {
        echo "❌ {$dir} (missing)\n";
        $errors[] = "Missing directory: {$dir}";
    }
}

// 7. Check composer dependencies
echo "\n📦 DEPENDENCIES:\n";
echo str_repeat("-", 60) . "\n";

if (file_exists('composer.json')) {
    echo "✅ composer.json exists\n";
    $passed[] = 'composer.json';
    
    if (is_dir('vendor')) {
        echo "✅ vendor directory exists (dependencies installed)\n";
        $passed[] = 'vendor directory';
    } else {
        echo "⚠️  vendor directory not found - Run: composer install\n";
        $warnings[] = "vendor directory not found";
    }
} else {
    echo "❌ composer.json not found!\n";
    $errors[] = "composer.json not found";
}

// 8. Check for development files that shouldn't be deployed
echo "\n🚫 DEVELOPMENT FILES:\n";
echo str_repeat("-", 60) . "\n";

$devFiles = [
    '.env',
    'Homestead.yaml',
    'Homestead.json',
    'phpunit.xml',
    'tests/',
];

$foundDevFiles = false;
foreach ($devFiles as $file) {
    if (file_exists($file) || is_dir($file)) {
        if ($file === '.env') {
            // Already checked above
            continue;
        }
        echo "⚠️  Development file found: {$file} (should be in .gitignore)\n";
        $warnings[] = "Development file: {$file}";
        $foundDevFiles = true;
    }
}

if (!$foundDevFiles) {
    echo "✅ No problematic development files found\n";
    $passed[] = 'No dev files';
}

// 9. Check migrations
echo "\n🗄️  DATABASE MIGRATIONS:\n";
echo str_repeat("-", 60) . "\n";

if (is_dir('database/migrations')) {
    $migrations = glob('database/migrations/*.php');
    $count = count($migrations);
    if ($count > 0) {
        echo "✅ Found {$count} migration file(s)\n";
        $passed[] = 'Migrations';
    } else {
        echo "⚠️  No migration files found\n";
        $warnings[] = "No migrations found";
    }
} else {
    echo "❌ database/migrations directory not found!\n";
    $errors[] = "Migrations directory not found";
}

// Summary
echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 SUMMARY:\n";
echo str_repeat("=", 60) . "\n\n";

echo "✅ Passed: " . count($passed) . " checks\n";
echo "⚠️  Warnings: " . count($warnings) . "\n";
echo "❌ Errors: " . count($errors) . "\n\n";

if (count($errors) > 0) {
    echo "❌ ERRORS (Must fix before deployment):\n";
    foreach ($errors as $error) {
        echo "   - {$error}\n";
    }
    echo "\n";
}

if (count($warnings) > 0) {
    echo "⚠️  WARNINGS (Review before deployment):\n";
    foreach ($warnings as $warning) {
        echo "   - {$warning}\n";
    }
    echo "\n";
}

if (count($errors) === 0) {
    echo "✅ READY FOR DEPLOYMENT!\n\n";
    echo "📋 Next Steps:\n";
    echo "   1. Review warnings above\n";
    echo "   2. Create production .env file on Hostinger\n";
    echo "   3. Upload files (excluding .env, vendor, node_modules)\n";
    echo "   4. Run: composer install --optimize-autoloader --no-dev\n";
    echo "   5. Run: php artisan key:generate\n";
    echo "   6. Run: php artisan migrate --force\n";
    echo "   7. Run: php artisan storage:link\n";
    echo "   8. Set file permissions\n";
    echo "   9. Test the application\n\n";
    echo "📖 See HOSTINGER_DEPLOYMENT_GUIDE.md for detailed instructions\n";
    exit(0);
} else {
    echo "❌ NOT READY FOR DEPLOYMENT - Fix errors above first!\n";
    exit(1);
}

