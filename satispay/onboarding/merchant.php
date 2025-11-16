<?php
/ =========================================
// 4) onboarding/merchants.php – form POST handler
// =========================================

namespace App\Onboarding; 

use App\Config; 
use App\Db; 
use App\SatispayClient; 

// Place this file at public/onboarding/merchants.php and set form action to /onboarding/merchants.php

// Basic hardening
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo 'Method Not Allowed'; exit;
}

require_once __DIR__ . '/../../src/config.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/satispay_client.php';

function required($arr, $key) {
    if (!isset($arr[$key]) || $arr[$key] === '') {
        throw new \InvalidArgumentException('Missing required field: ' . $key);
    }
    return $arr[$key];
}

function isoOrNull($val): ?string {
    if (!$val) return null;
    // Expect already ISO8601Z from the form JS; allow pass-through
    return $val;
}

try {
    $post = $_POST; // Using standard form fields

    // --- Validate minimal required fields
    $source_internal_code = required($post, 'source_internal_code');
    $company_name         = required($post, 'company_name');
    $legal_form           = required($post, 'legal_form');
    $vat_code             = required($post, 'vat_code');
    $email                = required($post, 'email');
    $mcc_code             = required($post, 'mcc_code');
    $iban                 = required($post, 'iban');
    $mobile_number        = required($post, 'mobile_number');

    // Address
    $address = $post['address'] ?? [];
    foreach (['address','city','district','zip_code','country'] as $k) {
        if (empty($address[$k])) throw new \InvalidArgumentException('Missing address field: ' . $k);
    }

    // Privacy
    $privacy = $post['privacy_consent'] ?? [];
    foreach (['type','acceptance_date'] as $k) {
        if (empty($privacy[$k])) throw new \InvalidArgumentException('Missing privacy_consent field: ' . $k);
    }

    // People list
    $people_list = $post['people_list'] ?? [];
    if (!is_array($people_list) || count($people_list) === 0) {
        throw new \InvalidArgumentException('At least one person is required');
    }
    $hasLR = false;
    foreach ($people_list as $p) {
        if (($p['role'] ?? '') === 'LEGAL_REPRESENTATIVE') { $hasLR = true; break; }
    }
    if (!$hasLR) {
        throw new \InvalidArgumentException('At least one LEGAL_REPRESENTATIVE is required');
    }

    // Optional
    $referral_id = $post['referral_id'] ?? null;
    $landline_number = $post['landline_number'] ?? null;
    $foundation_date = isoOrNull($post['foundation_date'] ?? null);
    $first_due_diligence_date = isoOrNull($post['first_due_diligence_date'] ?? null);
    $last_due_diligence_date  = isoOrNull($post['last_due_diligence_date'] ?? null);
    $additional_info = $post['additional_info'] ?? null;
    $env = in_array(($post['env'] ?? Config::DEFAULT_ENV), ['sandbox','production'], true) ? $post['env'] : Config::DEFAULT_ENV;

    // Build Satispay payload
    $payload = [
        'source_internal_code' => $source_internal_code,
        'referral_id'          => $referral_id,
        'company_name'         => $company_name,
        'vat_code'             => $vat_code,
        'legal_form'           => $legal_form,
        'email'                => $email,
        'address'              => [
            'address'        => $address['address'],
            'address_number' => $address['address_number'] ?? null,
            'city'           => $address['city'],
            'district'       => $address['district'],
            'zip_code'       => $address['zip_code'],
            'country'        => $address['country'],
        ],
        'foundation_date'           => $foundation_date,
        'mcc_code'                  => $mcc_code,
        'mobile_number'             => $mobile_number,
        'landline_number'           => $landline_number,
        'first_due_diligence_date'  => $first_due_diligence_date,
        'last_due_diligence_date'   => $last_due_diligence_date,
        'iban'                      => $iban,
        'additional_info'           => $additional_info,
        'privacy_consent'           => [
            'type'            => $privacy['type'],
            'version'         => $privacy['version'] ?? null,
            'acceptance_date' => $privacy['acceptance_date'],
        ],
        'people_list' => array_values($people_list),
    ];

    // Persist initial row
    $db = Db::conn();
    $stmt = $db->prepare('INSERT INTO satispay_merchant_registration
        (source_internal_code, referral_id, company_name, legal_form, vat_code, email, iban, mcc_code,
         mobile_number, landline_number, foundation_date, first_due_diligence_date, last_due_diligence_date,
         address_json, privacy_consent_json, people_list_json, status, env, created_at, updated_at)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?, CAST(? AS JSON), CAST(? AS JSON), CAST(? AS JSON), "CREATED", ?, NOW(6), NOW(6))');
    if (!$stmt) throw new \RuntimeException('DB prepare failed: ' . $db->error);

    $addrJson = json_encode($payload['address'], JSON_UNESCAPED_SLASHES);
    $privJson = json_encode($payload['privacy_consent'], JSON_UNESCAPED_SLASHES);
    $peopleJson = json_encode($payload['people_list'], JSON_UNESCAPED_SLASHES);

    $stmt->bind_param(
        'ssssssssssssssss',
        $source_internal_code,
        $referral_id,
        $company_name,
        $legal_form,
        $vat_code,
        $email,
        $iban,
        $mcc_code,
        $mobile_number,
        $landline_number,
        $foundation_date,
        $first_due_diligence_date,
        $last_due_diligence_date,
        $addrJson,
        $privJson,
        $peopleJson,
        $env
    );
    if (!$stmt->execute()) {
        throw new \RuntimeException('DB insert failed: ' . $stmt->error);
    }

    $insertId = (int)$db->insert_id;

    // Log event CREATED
    $ev = $db->prepare('INSERT INTO satispay_onboarding_event (registration_id, source_internal_code, event_type, payload) VALUES (NULL, ?, "CREATED", NULL)');
    $ev->bind_param('s', $source_internal_code);
    $ev->execute();

    // Call Satispay POST /merchants
    $client = new SatispayClient($env);
    $resp = $client->createMerchant(array_filter($payload, fn($v) => $v !== null));

    // Update DB with Satispay response
    $registration_id = $resp['id'] ?? null;
    $merchant_id     = $resp['merchant_id'] ?? null;
    $status          = $resp['status'] ?? 'DATA_VALIDATION';
    $sat_insert_date = isset($resp['insert_date']) ? date('Y-m-d H:i:s.u', strtotime($resp['insert_date'])) : null;

    $up = $db->prepare('UPDATE satispay_merchant_registration
        SET registration_id=?, merchant_id=?, status=?, sat_insert_date=?, updated_at=NOW(6)
        WHERE id=?');
    $up->bind_param('ssssi', $registration_id, $merchant_id, $status, $sat_insert_date, $insertId);
    $up->execute();

    // Log POST_SENT
    $payloadLog = json_encode($resp, JSON_UNESCAPED_SLASHES);
    $ev2 = $db->prepare('INSERT INTO satispay_onboarding_event (registration_id, source_internal_code, event_type, payload) VALUES (?,?, "POST_SENT", CAST(? AS JSON))');
    $ev2->bind_param('sss', $registration_id, $source_internal_code, $payloadLog);
    $ev2->execute();

    // Redirect to status page
    header('Location: /onboarding/status.php?registration_id=' . urlencode($registration_id));
    exit;
}
catch (\Throwable $e) {
    // Persist error and show message
    try {
        $db = Db::conn();
        if (isset($source_internal_code)) {
            $errCode = 'EXC';
            $errMsg = substr($e->getMessage(), 0, 500);
            $db->query("UPDATE satispay_merchant_registration SET status='FAILED', last_error_code='" . $db->real_escape_string($errCode) . "', last_error_message='" . $db->real_escape_string($errMsg) . "' WHERE source_internal_code='" . $db->real_escape_string($source_internal_code) . "' LIMIT 1");
            $ev = $db->prepare('INSERT INTO satispay_onboarding_event (registration_id, source_internal_code, event_type, payload) VALUES (NULL, ?, "POST_ERROR", NULL)');
            $ev->bind_param('s', $source_internal_code);
            $ev->execute();
        }
    } catch (\Throwable $inner) {}

    http_response_code(400);
    echo '<!doctype html><html><body style="font-family:system-ui;max-width:720px;margin:40px auto">';
    echo '<h2>Errore creazione registrazione</h2>';
    echo '<div style="color:#b00020">' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<p><a href="/onboarding/form.html">Torna al form</a></p>';
    echo '</body></html>';
    exit;
}
?>