<?php
// api/includes/session_boot.php
// Centralized session bootstrap to ensure consistent cookie settings across endpoints
// Handles localhost (no dot in host) by omitting cookie domain to allow cookies to be set

if (!function_exists('session_boot')) {
    function session_boot(): void {
        // Compute a safe cookie domain. For localhost or hosts without a dot, omit domain.
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $hasDot = strpos($host, '.') !== false;
        $cookieDomain = $hasDot ? $host : '';

        // Configure session cookie parameters
        $params = [
            'lifetime' => 3600 * 24 * 30, // 30 days
            'path' => '/',
            // Important: omit domain on localhost to allow browser to set cookie
            // When empty string is provided, PHP will not set the Domain attribute
            'domain' => $cookieDomain ?: '',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ];

        // Apply cookie params (PHP >=7.3 supports array form)
        session_set_cookie_params($params);

        // Ensure session is not already started to avoid conflicts
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Prevent session fixation by regenerating session ID
        if (!isset($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;
        }

        // Debugging log
        error_log('Session initialized successfully.');
    }
}
