#!/bin/bash
set -e

echo "🧪 Testing SignalWire PHP development setup..."

# Test PHP version
echo "🐘 PHP Version:"
php -v

# Test Composer
echo "🎼 Composer Version:"
composer --version

# Test if we can run basic commands
echo "📦 Testing basic functionality..."
php -r "echo 'PHP is working!' . PHP_EOL;"

# Check if vendor directory exists
if [ ! -d "vendor" ]; then
    echo "⚠️  Vendor directory not found. Running composer install..."
    composer install
fi

# Check if SignalWire classes can be loaded
echo "📡 Testing SignalWire autoload..."
php -r "
    require_once 'vendor/autoload.php';
    if (class_exists('SignalWire\LaML\VoiceResponse')) {
        echo 'SignalWire classes loaded successfully!' . PHP_EOL;
    } else {
        echo 'Failed to load SignalWire classes' . PHP_EOL;
        exit(1);
    }
"

# Test basic SignalWire functionality
echo "🔧 Testing SignalWire VoiceResponse creation..."
php -r "
    require_once 'vendor/autoload.php';
    \$response = new SignalWire\LaML\VoiceResponse();
    \$response->say('Hello from SignalWire PHP!');
    echo 'VoiceResponse created successfully!' . PHP_EOL;
    echo 'Generated XML: ' . \$response . PHP_EOL;
"

echo "🎉 All tests passed!"
