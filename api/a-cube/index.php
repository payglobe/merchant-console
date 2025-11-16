<?php
declare(strict_types=1);
// (CORS lato PHP; se già gestisci da Apache, puoi togliere questo blocco)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type,X-Idempotency-Key,X-App-Signature');
header('Access-Control-Allow-Methods: GET,POST,DELETE,OPTIONS');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }
require __DIR__ . '/receipts.php';

