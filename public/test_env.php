<?php
header('Content-Type: text/plain');
echo "DB_HOST: " . var_export(getenv('DB_HOST'), true) . "\n";
echo "DB_PORT: " . var_export(getenv('DB_PORT'), true) . "\n";
echo "TELEGRAM_TOKEN: " . var_export(getenv('TELEGRAM_TOKEN'), true) . "\n";
echo "TELEGRAM_CHAT_ID: " . var_export(getenv('TELEGRAM_CHAT_ID'), true) . "\n";
echo "CRON_TOKEN: " . var_export(getenv('CRON_TOKEN'), true) . "\n";
echo "APP_URL: " . var_export(getenv('APP_URL'), true) . "\n";
