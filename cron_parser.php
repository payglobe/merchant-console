<?php
/**
 * LogHost File Parser - Cron Script
 * Parses FTFS LogHost files and imports transactions into MySQL
 * Configurato per PayGlobe database esistente
 */

// Configuration - AGGIORNA QUESTE CREDENZIALI
$config = [
    'db_host' => '10.10.10.13',
    'db_name' => 'payglobe',
    'db_user' => 'ftfs_cron',  // Utente creato nello schema SQL
    'db_pass' => 'Ft#s_Cr0n_2024!',  // CAMBIA CON PASSWORD SICURA
    'files_directory' => '/flussi-ecommerce1',
    'log_file' => '/var/log/loghost_parser.log',
    'file_pattern' => 'loghost_MONEYNET_*',
];

// Initialize logging
function writeLog($message) {
    global $config;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($config['log_file'], "[$timestamp] $message\n", FILE_APPEND);
    echo "[$timestamp] $message\n";
}

// Database connection
try {
    $pdo = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'],
        $config['db_pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    writeLog("Database connection established");
} catch (PDOException $e) {
    writeLog("Database connection failed: " . $e->getMessage());
    exit(1);
}

// Parse LogHost file according to FTFS specification
function parseLogHostFile($filepath) {
    $records = [];
    $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    if (!$lines) {
        throw new Exception("Could not read file: $filepath");
    }
    
    foreach ($lines as $lineNum => $line) {
        $lineLength = strlen($line);
        
        // Skip header (GTHEAD) and trailer (GTTAIL) records
        if (substr($line, 0, 6) === 'GTHEAD' || substr($line, 0, 6) === 'GTTAIL') {
            continue;
        }
        
        // Parse detail record (should be 900 chars according to spec)
        if ($lineLength >= 900) {
            try {
                $record = parseDetailRecord($line);
                $records[] = $record;
            } catch (Exception $e) {
                writeLog("Error parsing line " . ($lineNum + 1) . ": " . $e->getMessage());
            }
        }
    }
    
    return $records;
}

// Parse detail record according to FTFS field positions
function parseDetailRecord($line) {
    return [
        'transaction_type' => trim(substr($line, 0, 6)),
        'posid' => trim(substr($line, 6, 8)),
        'termid' => trim(substr($line, 14, 8)),
        'store_code' => trim(substr($line, 22, 15)) ?: null,
        'acid' => trim(substr($line, 37, 16)) ?: null,
        'meid' => trim(substr($line, 53, 15)) ?: null,
        'amount_raw' => trim(substr($line, 68, 12)) ?: null,
        'currency' => trim(substr($line, 80, 3)) ?: '978',
        'pan' => trim(substr($line, 83, 19)),
        'transaction_date_raw' => trim(substr($line, 102, 14)),
        'transaction_number' => trim(substr($line, 116, 6)),
        'acquirer_bank' => trim(substr($line, 122, 5)),
        'card_read_mode' => trim(substr($line, 127, 1)),
        'approval_code' => trim(substr($line, 128, 6)) ?: null,
        'merchant_description' => trim(substr($line, 134, 72)) ?: null,
        'pos_data_code' => trim(substr($line, 206, 12)) ?: null,
        'cashback_amount_raw' => trim(substr($line, 218, 12)) ?: null,
        'amount_surcharge_raw' => trim(substr($line, 230, 5)) ?: null,
        'rte_rule' => trim(substr($line, 235, 100)) ?: null,
        'transaction_status' => trim(substr($line, 446, 1)) ?: null,
        'settlement_flag' => trim(substr($line, 447, 1)),
        'dcc_status' => trim(substr($line, 448, 2)) ?: null,
        'dcc_curr' => trim(substr($line, 450, 3)) ?: null,
        'dcc_ext' => trim(substr($line, 453, 9)) ?: null,
        'dcc_amount_raw' => trim(substr($line, 462, 12)) ?: null,
        'ib_response_code' => trim(substr($line, 474, 3)) ?: null,
        'confirmation' => trim(substr($line, 477, 1)) ?: null,
        'pos_acid' => trim(substr($line, 478, 16)),
        'response_code' => trim(substr($line, 494, 3)) ?: null,
        'reversal_trid' => trim(substr($line, 502, 33)) ?: null,
        'merchant_id' => trim(substr($line, 535, 24)) ?: null,
        'rrn' => trim(substr($line, 559, 12)) ?: null,
        'term_model' => trim(substr($line, 571, 15)),
        'transaction_id' => trim(substr($line, 586, 32)) ?: null,
        'df8102' => trim(substr($line, 618, 32)) ?: null,
        'df8103' => trim(substr($line, 650, 32)) ?: null,
        'df8104' => trim(substr($line, 682, 32)) ?: null,
        'df8106' => trim(substr($line, 714, 32)) ?: null,
        'df8107' => trim(substr($line, 746, 32)) ?: null,
        'df8108' => trim(substr($line, 778, 32)) ?: null,
        'card_brand' => trim(substr($line, 816, 32)) ?: null,
        'card_settlement_type' => trim(substr($line, 848, 1)) ?: null,
        'card_type' => trim(substr($line, 849, 40)) ?: null,
        'card_abi_issuer' => trim(substr($line, 889, 5)) ?: null,
        'card_country_alpha' => trim(substr($line, 894, 3)) ?: null,
        'card_country_num' => trim(substr($line, 897, 3)) ?: null,
    ];
}

// Convert raw amounts to decimal (divide by 100)
function convertAmount($amountRaw) {
    if (empty($amountRaw) || !is_numeric($amountRaw)) {
        return null;
    }
    return floatval($amountRaw) / 100;
}

// Convert date format YYYYMMDDHHMMSS to MySQL DATETIME
function convertDate($dateRaw) {
    if (strlen($dateRaw) !== 14) {
        throw new Exception("Invalid date format: $dateRaw");
    }
    
    $year = substr($dateRaw, 0, 4);
    $month = substr($dateRaw, 4, 2);
    $day = substr($dateRaw, 6, 2);
    $hour = substr($dateRaw, 8, 2);
    $minute = substr($dateRaw, 10, 2);
    $second = substr($dateRaw, 12, 2);
    
    return "$year-$month-$day $hour:$minute:$second";
}

// Insert transaction into database
function insertTransaction($pdo, $record, $filename) {
    $sql = "INSERT INTO transactions (
        transaction_type, posid, termid, store_code, acid, meid,
        amount, amount_raw, currency, pan, transaction_date, transaction_number,
        acquirer_bank, card_read_mode, approval_code, merchant_description,
        pos_data_code, cashback_amount, amount_surcharge, rte_rule,
        transaction_status, settlement_flag, dcc_status, dcc_curr, dcc_ext,
        dcc_amount, ib_response_code, confirmation, pos_acid, response_code,
        reversal_trid, merchant_id, rrn, term_model, transaction_id,
        df8102, df8103, df8104, df8106, df8107, df8108,
        card_brand, card_settlement_type, card_type, card_abi_issuer,
        card_country_alpha, card_country_num, file_source
    ) VALUES (
        :transaction_type, :posid, :termid, :store_code, :acid, :meid,
        :amount, :amount_raw, :currency, :pan, :transaction_date, :transaction_number,
        :acquirer_bank, :card_read_mode, :approval_code, :merchant_description,
        :pos_data_code, :cashback_amount, :amount_surcharge, :rte_rule,
        :transaction_status, :settlement_flag, :dcc_status, :dcc_curr, :dcc_ext,
        :dcc_amount, :ib_response_code, :confirmation, :pos_acid, :response_code,
        :reversal_trid, :merchant_id, :rrn, :term_model, :transaction_id,
        :df8102, :df8103, :df8104, :df8106, :df8107, :df8108,
        :card_brand, :card_settlement_type, :card_type, :card_abi_issuer,
        :card_country_alpha, :card_country_num, :file_source
    ) ON DUPLICATE KEY UPDATE
        amount = VALUES(amount),
        settlement_flag = VALUES(settlement_flag),
        transaction_status = VALUES(transaction_status),
        updated_at = CURRENT_TIMESTAMP";
    
    $stmt = $pdo->prepare($sql);
    
    // Process data
    $data = [
        ':transaction_type' => $record['transaction_type'],
        ':posid' => $record['posid'],
        ':termid' => $record['termid'],
        ':store_code' => $record['store_code'],
        ':acid' => $record['acid'],
        ':meid' => $record['meid'],
        ':amount' => convertAmount($record['amount_raw']),
        ':amount_raw' => $record['amount_raw'],
        ':currency' => $record['currency'],
        ':pan' => $record['pan'],
        ':transaction_date' => convertDate($record['transaction_date_raw']),
        ':transaction_number' => $record['transaction_number'],
        ':acquirer_bank' => $record['acquirer_bank'],
        ':card_read_mode' => $record['card_read_mode'],
        ':approval_code' => $record['approval_code'],
        ':merchant_description' => $record['merchant_description'],
        ':pos_data_code' => $record['pos_data_code'],
        ':cashback_amount' => convertAmount($record['cashback_amount_raw']),
        ':amount_surcharge' => convertAmount($record['amount_surcharge_raw']),
        ':rte_rule' => $record['rte_rule'],
        ':transaction_status' => $record['transaction_status'],
        ':settlement_flag' => $record['settlement_flag'],
        ':dcc_status' => $record['dcc_status'],
        ':dcc_curr' => $record['dcc_curr'],
        ':dcc_ext' => $record['dcc_ext'],
        ':dcc_amount' => convertAmount($record['dcc_amount_raw']),
        ':ib_response_code' => $record['ib_response_code'],
        ':confirmation' => $record['confirmation'],
        ':pos_acid' => $record['pos_acid'],
        ':response_code' => $record['response_code'],
        ':reversal_trid' => $record['reversal_trid'],
        ':merchant_id' => $record['merchant_id'],
        ':rrn' => $record['rrn'],
        ':term_model' => $record['term_model'],
        ':transaction_id' => $record['transaction_id'],
        ':df8102' => $record['df8102'],
        ':df8103' => $record['df8103'],
        ':df8104' => $record['df8104'],
        ':df8106' => $record['df8106'],
        ':df8107' => $record['df8107'],
        ':df8108' => $record['df8108'],
        ':card_brand' => $record['card_brand'],
        ':card_settlement_type' => $record['card_settlement_type'],
        ':card_type' => $record['card_type'],
        ':card_abi_issuer' => $record['card_abi_issuer'],
        ':card_country_alpha' => $record['card_country_alpha'],
        ':card_country_num' => $record['card_country_num'],
        ':file_source' => $filename,
    ];
    
    return $stmt->execute($data);
}

// Log file processing
function logFileProcessing($pdo, $filename, $recordsCount, $status = 'completed', $errorMessage = null) {
    $fileDate = extractDateFromFilename($filename);
    
    $sql = "INSERT INTO file_processing_log (filename, file_date, records_count, status, error_message)
            VALUES (:filename, :file_date, :records_count, :status, :error_message)
            ON DUPLICATE KEY UPDATE
            records_count = VALUES(records_count),
            status = VALUES(status),
            error_message = VALUES(error_message),
            processed_at = CURRENT_TIMESTAMP";
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        ':filename' => $filename,
        ':file_date' => $fileDate,
        ':records_count' => $recordsCount,
        ':status' => $status,
        ':error_message' => $errorMessage,
    ]);
}

// Extract date from filename (loghost_MONEYNET_YYYYMMDDHHMMSS)
function extractDateFromFilename($filename) {
    if (preg_match('/loghost_MONEYNET_(\d{8})\d{6}/', $filename, $matches)) {
        $dateStr = $matches[1];
        return substr($dateStr, 0, 4) . '-' . substr($dateStr, 4, 2) . '-' . substr($dateStr, 6, 2);
    }
    return date('Y-m-d'); // Fallback to current date
}

// Main execution
writeLog("Starting LogHost file processing");

// Find files to process
$files = glob($config['files_directory'] . '/' . $config['file_pattern']);

if (empty($files)) {
    writeLog("No LogHost files found in directory: {$config['files_directory']}");
    exit(0);
}

writeLog("Found " . count($files) . " LogHost files to process");

foreach ($files as $filepath) {
    $filename = basename($filepath);
    writeLog("Processing file: $filename");
    
    // Check if file already processed today
    $stmt = $pdo->prepare("SELECT id FROM file_processing_log WHERE filename = ? AND DATE(processed_at) = CURDATE()");
    $stmt->execute([$filename]);
    
    if ($stmt->fetch()) {
        writeLog("File $filename already processed today, skipping");
        continue;
    }
    
    try {
        // Log processing start
        logFileProcessing($pdo, $filename, 0, 'processing');
        
        // Parse file
        $records = parseLogHostFile($filepath);
        $recordsCount = count($records);
        
        writeLog("Parsed $recordsCount records from $filename");
        
        // Insert records
        $insertedCount = 0;
        foreach ($records as $record) {
            try {
                insertTransaction($pdo, $record, $filename);
                $insertedCount++;
            } catch (Exception $e) {
                writeLog("Error inserting record: " . $e->getMessage());
            }
        }
        
        // Log success
        logFileProcessing($pdo, $filename, $recordsCount, 'completed');
        writeLog("Successfully processed $filename: $insertedCount/$recordsCount records inserted");
        
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
        logFileProcessing($pdo, $filename, 0, 'error', $errorMsg);
        writeLog("Error processing $filename: $errorMsg");
    }
}

writeLog("LogHost file processing completed");
?>
