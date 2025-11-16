<?php
// =========================================
// 5) onboarding/status_api.php – poll & sync
// =========================================

namespace App\Onboarding; 

use App\Db; 
use App\SatispayClient; 

// Place at public/onboarding/status_api.php

header('Content-Type: application/json');

require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/satispay_client.php';

$registrationId = $_GET['registration_id'] ?? '';
if (!$registrationId) { http_response_code(400); echo json_encode(['error'=>'missing registration_id']); exit; }

$finals = ['COMPLETE','NOT_VALID'];

try {
    $db = Db::conn();
    $stmt = $db->prepare('SELECT env, status FROM satispay_merchant_registration WHERE registration_id=? LIMIT 1');
    $stmt->bind_param('s', $registrationId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) { http_response_code(404); echo json_encode(['error'=>'not_found']); exit; }

    $env = $row['env'];
    $status = $row['status'];

    // If not final, query Satispay now
    if (!in_array($status, $finals, true)) {
        $client = new SatispayClient($env);
        $resp = $client->getRegistration($registrationId);

        $merchant_id = $resp['merchant_id'] ?? null;
        $newStatus   = $resp['status'] ?? $status;
        $sat_update  = isset($resp['update_date']) ? date('Y-m-d H:i:s.u', strtotime($resp['update_date'])) : null;

        $up = $db->prepare('UPDATE satispay_merchant_registration SET merchant_id=COALESCE(?,merchant_id), status=?, sat_update_date=?, updated_at=NOW(6) WHERE registration_id=?');
        $up->bind_param('ssss', $merchant_id, $newStatus, $sat_update, $registrationId);
        $up->execute();

        // log event
        $payloadLog = json_encode($resp, JSON_UNESCAPED_SLASHES);
        $ev = $db->prepare('INSERT INTO satispay_onboarding_event (registration_id, source_internal_code, event_type, payload)
                            SELECT registration_id, source_internal_code, "POLLED", CAST(? AS JSON)
                            FROM satispay_merchant_registration WHERE registration_id=? LIMIT 1');
        $ev->bind_param('ss', $payloadLog, $registrationId);
        $ev->execute();

        $status = $newStatus;
    }

    // Return current snapshot
    $q = $db->prepare('SELECT company_name, email, iban, env, status, merchant_id, sat_insert_date, sat_update_date, last_error_code, last_error_message FROM satispay_merchant_registration WHERE registration_id=? LIMIT 1');
    $q->bind_param('s', $registrationId);
    $q->execute();
    $snap = $q->get_result()->fetch_assoc();

    echo json_encode(['ok'=>true,'data'=>$snap]);
}
catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error'=>$e->getMessage()]);
}


?>