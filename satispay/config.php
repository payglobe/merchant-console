<?php
/**
 * =============================
 * Satispay Onboarding PHP Kit
 * PHP 7.4 + MySQL 8 (MySQLi)
 * Tables prefix: satispay_
 * =============================
 *
 * FILES INCLUDED IN THIS SINGLE SNIPPET (split them in your project):
 *
 * 1) config.php                – configuration & env
 * 2) db.php                    – mysqli connection helper
 * 3) satispay_client.php       – HTTP Signature + API client (POST/GET)
 * 4) onboarding/merchants.php  – POST endpoint to create registration (form action)
 * 5) onboarding/status_api.php – JSON API to poll status & sync with Satispay
 * 6) onboarding/status.php     – Bootstrap page to show live status (polling)
 * 7) cron/poll_open_registrations.php – CLI job for periodic polling
 *
 * NOTE: ensure tables from previous step are created with prefix `satispay_`.
 */

// =========================================
// 1) config.php
// =========================================

// Place this in src/config.php
namespace App; 

class Config
{
    // --- DB ---
    public const DB_HOST = '127.0.0.1';
    public const DB_USER = 'root';
    public const DB_PASS = '';
    public const DB_NAME = 'your_db_name';

    // --- Default env ---
    public const DEFAULT_ENV = 'sandbox'; // 'sandbox' | 'production'

    // --- Satispay Keys ---
  
    // Timeouts
    public const HTTP_TIMEOUT_SEC = 20;

    // Base hosts
    public static function host(string $env): string
    {
        return $env === 'production' ? 'authservices.satispay.com' : 'staging.authservices.satispay.com';
    }

    public static function baseUrl(string $env): string
    {
        return 'https://' . self::host($env) . '/g_provider/v1';
    }
}


