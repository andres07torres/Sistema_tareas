<?php
function verificarWebhookConfiguracion() {
    $telegramToken = $_ENV['TELEGRAM_TOKEN'] ?? getenv('TELEGRAM_TOKEN');
    $appUrl = $_ENV['APP_URL'] ?? getenv('APP_URL');
    
    if (!$telegramToken || !$appUrl) return;
    
    $targetUrl = rtrim($appUrl, '/') . '/webhook.php';
    $apiUrl = "https://api.telegram.org/bot{$telegramToken}/getWebhookInfo";
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $info = json_decode($response, true);
        if (isset($info['ok']) && $info['ok']) {
            $currentUrl = $info['result']['url'] ?? '';
            $allowedUpdates = $info['result']['allowed_updates'] ?? [];
            
            $needsUpdate = false;
            if ($currentUrl !== $targetUrl) {
                $needsUpdate = true;
            }
            // Asegurarnos de que callback_query y message estén permitidos
            if (!in_array('callback_query', $allowedUpdates) || !in_array('message', $allowedUpdates)) {
                $needsUpdate = true;
            }
            
            if ($needsUpdate) {
                // Actualizar webhook forzando los permisos correctos
                $setWebhookUrl = "https://api.telegram.org/bot{$telegramToken}/setWebhook";
                $ch2 = curl_init($setWebhookUrl);
                curl_setopt($ch2, CURLOPT_POST, 1);
                curl_setopt($ch2, CURLOPT_POSTFIELDS, http_build_query([
                    'url' => $targetUrl,
                    'allowed_updates' => json_encode(['message', 'callback_query'])
                ]));
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch2, CURLOPT_TIMEOUT, 10);
                curl_exec($ch2);
                curl_close($ch2);
                
                if (function_exists('logMsg')) {
                    logMsg("Webhook autocorregido. URL: '$targetUrl'");
                } else {
                    error_log("Webhook autocorregido. URL: '$targetUrl'");
                }
            }
        }
    }
}

// Ejecutar la verificación sin bloquear
verificarWebhookConfiguracion();
