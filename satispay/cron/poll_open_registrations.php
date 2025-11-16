<?php
// =========================================
// 7) cron/poll_open_registrations.php – CLI cron job
// =========================================

// Usage: php cron/poll_open_registrations.php

namespace App\Cron; 

use App\Db; 
use App\SatispayClient; 

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/satispay_client.php';

echo "Polling open registrations...\n";

$db = Db::conn();
$res = $db->query("SELECT registration_id, env FROM satispay_merchant_registration WHERE status NOT IN ('COMPLETE','NOT_VALID','FAILED') AND registration_id IS NOT NULL");

$cnt = 0; $upd = 0;
while ($row = $res->fetch_assoc()) {
    $cnt++;
    try {
        $client = new SatispayClient($row['env']);
        $resp = $client->getRegistration($row['registration_id']);
        $status = $resp['status'] ?? null;
        if ($status) {
            $merchant_id = $resp['merchant_id'] ?? null;
            $sat_update  = isset($resp['update_date']) ? date('Y-m-d H:i:s.u', strtotime($resp['update_date'])) : null;
            $up = $db->prepare('UPDATE satispay_merchant_registration SET merchant_id=COALESCE(?,merchant_id), status=?, sat_update_date=?, updated_at=NOW(6) WHERE registration_id=?');
            $up->bind_param('ssss', $merchant_id, $status, $sat_update, $row['registration_id']);
            $up->execute();
            $upd++;

            $payloadLog = json_encode($resp, JSON_UNESCAPED_SLASHES);
            $ev = $db->prepare('INSERT INTO satispay_onboarding_event (registration_id, source_internal_code, event_type, payload)
                                SELECT registration_id, source_internal_code, "CRON_POLLED", CAST(? AS JSON)
                                FROM satispay_merchant_registration WHERE registration_id=? LIMIT 1');
            $ev->bind_param('ss', $payloadLog, $row['registration_id']);
            $ev->execute();
        }
    } catch (\Throwable $e) {
        // log minimal
        $msg = substr($e->getMessage(), 0, 500);
        $db->query("UPDATE satispay_merchant_registration SET last_error_code='CRON', last_error_message='".$db->real_escape_string($msg)."' WHERE registration_id='".$db->real_escape_string($row['registration_id'])."' LIMIT 1");
    }
}

echo "Checked: {$cnt}, Updated: {$upd}\n";