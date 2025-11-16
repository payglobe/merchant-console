<?php

// Includi il file di configurazione del database (config.php)
include 'config.php';

// Verifica se la richiesta è di tipo POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Leggi il contenuto del JSON dalla richiesta POST
    $json = file_get_contents('php://input');

    // Decodifica il JSON in un array associativo
    $data = json_decode($json, true);

    // Verifica se la decodifica è avvenuta correttamente
    if ($data === null) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }

    // Verifica se l'array FtfsTransactions esiste
    if (!isset($data['FtfsTransactions']) || !is_array($data['FtfsTransactions'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Missing or invalid FtfsTransactions array']);
        exit;
    }

    // Itera su ogni transazione
    foreach ($data['FtfsTransactions'] as $transaction) {
        // Sanifica i dati (importante per la sicurezza)
        $Trid = isset($transaction['Trid']) ? intval($transaction['Trid']) : null;
        $TermId = isset($transaction['TermId']) ? htmlspecialchars($transaction['TermId']) : null;
        $SiaCode = isset($transaction['SiaCode']) ? htmlspecialchars($transaction['SiaCode']) : null;
        $DtTrans = isset($transaction['DtTrans']) ? htmlspecialchars($transaction['DtTrans']) : null;
        $ApprNum = isset($transaction['ApprNum']) ? htmlspecialchars($transaction['ApprNum']) : null;
        $Acid = isset($transaction['Acid']) ? htmlspecialchars($transaction['Acid']) : null;
        $Acquirer = isset($transaction['Acquirer']) ? htmlspecialchars($transaction['Acquirer']) : null;
        $Pan = isset($transaction['Pan']) ? htmlspecialchars($transaction['Pan']) : null;
        $Amount = isset($transaction['Amount']) ? floatval(str_replace(",",".",$transaction['Amount'])) : null;
        $Currency = isset($transaction['Currency']) ? htmlspecialchars($transaction['Currency']) : null;
        $DtIns = isset($transaction['DtIns']) ? htmlspecialchars($transaction['DtIns']) : null;
        $PointOfService = isset($transaction['PointOfService']) ? htmlspecialchars($transaction['PointOfService']) : null;
        $Cont = isset($transaction['Cont']) ? htmlspecialchars($transaction['Cont']) : null;
        $NumOper = isset($transaction['NumOper']) ? intval($transaction['NumOper']) : null;
        $DtPos = isset($transaction['DtPos']) ? htmlspecialchars($transaction['DtPos']) : null;
        $PosReq = isset($transaction['PosReq']) ? htmlspecialchars($transaction['PosReq']) : null;
        $PosStan = isset($transaction['PosStan']) ? intval($transaction['PosStan']) : null;
        $PfCode = isset($transaction['PfCode']) ? htmlspecialchars($transaction['PfCode']) : null;
        $PMrc = isset($transaction['PMrc']) ? htmlspecialchars($transaction['PMrc']) : null;
        $PosAcq = isset($transaction['PosAcq']) ? htmlspecialchars($transaction['PosAcq']) : null;
        $GtResp = isset($transaction['GtResp']) ? htmlspecialchars($transaction['GtResp']) : null;
        $NumTent = isset($transaction['NumTent']) ? intval($transaction['NumTent']) : null;
        $TP = isset($transaction['TP']) ? htmlspecialchars($transaction['TP']) : null;
        $CatMer = isset($transaction['CatMer']) ? htmlspecialchars($transaction['CatMer']) : null;
        $VndId = isset($transaction['VndId']) ? htmlspecialchars($transaction['VndId']) : null;
        $PvdId = isset($transaction['PvdId']) ? intval($transaction['PvdId']) : null;
        $Bin = isset($transaction['Bin']) ? htmlspecialchars($transaction['Bin']) : null;
        $Tpc = isset($transaction['Tpc']) ? htmlspecialchars($transaction['Tpc']) : null;
        $VaFl = isset($transaction['VaFl']) ? htmlspecialchars($transaction['VaFl']) : null;
        $FvFl = isset($transaction['FvFl']) ? htmlspecialchars($transaction['FvFl']) : null;
        $TrKey = isset($transaction['TrKey']) ? htmlspecialchars($transaction['TrKey']) : null;
        $CSeq = isset($transaction['CSeq']) ? intval($transaction['CSeq']) : null;
        $Conf = isset($transaction['Conf']) ? htmlspecialchars($transaction['Conf']) : null;
        $AutTime = isset($transaction['AutTime']) ? intval($transaction['AutTime']) : null;
        $DBTime = isset($transaction['DBTime']) ? intval($transaction['DBTime']) : null;
        $TOTTime = isset($transaction['TOTTime']) ? intval($transaction['TOTTime']) : null;
        $DFN = isset($transaction['DFN']) ? htmlspecialchars($transaction['DFN']) : null;
        $CED = isset($transaction['CED']) ? htmlspecialchars($transaction['CED']) : null;
        $TTQ = isset($transaction['TTQ']) ? htmlspecialchars($transaction['TTQ']) : null;
        $FFI = isset($transaction['FFI']) ? htmlspecialchars($transaction['FFI']) : null;
        $TCAP = isset($transaction['TCAP']) ? htmlspecialchars($transaction['TCAP']) : null;
        $ISR = isset($transaction['ISR']) ? htmlspecialchars($transaction['ISR']) : null;
        $IST = isset($transaction['IST']) ? htmlspecialchars($transaction['IST']) : null;
        $IAutD = isset($transaction['IAutD']) ? htmlspecialchars($transaction['IAutD']) : null;
        $CryptCurr = isset($transaction['CryptCurr']) ? htmlspecialchars($transaction['CryptCurr']) : null;
        $CryptType = isset($transaction['CryptType']) ? htmlspecialchars($transaction['CryptType']) : null;
        $CrypAmnt = isset($transaction['CrypAmnt']) ? htmlspecialchars($transaction['CrypAmnt']) : null;
        $CryptTD = isset($transaction['CryptTD']) ? htmlspecialchars($transaction['CryptTD']) : null;
        $UN = isset($transaction['UN']) ? htmlspecialchars($transaction['UN']) : null;
        $CVR = isset($transaction['CVR']) ? htmlspecialchars($transaction['CVR']) : null;
        $TVR = isset($transaction['TVR']) ? htmlspecialchars($transaction['TVR']) : null;
        $IAD = isset($transaction['IAD']) ? htmlspecialchars($transaction['IAD']) : null;
        $CID = isset($transaction['CID']) ? htmlspecialchars($transaction['CID']) : null;
        $AId = isset($transaction['AId']) ? htmlspecialchars($transaction['AId']) : null;
        $HATC = isset($transaction['HATC']) ? htmlspecialchars($transaction['HATC']) : null;
        $AIP = isset($transaction['AIP']) ? htmlspecialchars($transaction['AIP']) : null;
        $ACrypt = isset($transaction['ACrypt']) ? htmlspecialchars($transaction['ACrypt']) : null;
        $PaymentId = isset($transaction['PaymentId']) ? htmlspecialchars($transaction['PaymentId']) : null;
        $CCode = isset($transaction['CCode']) ? htmlspecialchars($transaction['CCode']) : null;

        // Prepara la query SQL (usa prepared statements per la sicurezza)
        $sql = "INSERT INTO ftfs_transactions (Trid, TermId, SiaCode, DtTrans, ApprNum, Acid, Acquirer, Pan, Amount, Currency, DtIns, PointOfService, Cont, NumOper, DtPos, PosReq, PosStan, PfCode, PMrc, PosAcq, GtResp, NumTent, TP, CatMer, VndId, PvdId, Bin, Tpc, VaFl, FvFl, TrKey, CSeq, Conf, AutTime, DBTime, TOTTime, DFN, CED, TTQ, FFI, TCAP, ISR, IST, IAutD, CryptCurr, CryptType, CrypAmnt, CryptTD, UN, CVR, TVR, IAD, CID, AId, HATC, AIP, ACrypt, PaymentId, CCode) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        // Verifica se la preparazione della query è avvenuta correttamente
        if ($stmt === false) {
            http_response_code(500); // Internal Server Error
            echo json_encode(['error' => 'Database error: ' . $conn->error]);
            exit;
        }

        // Associa i parametri
        $stmt->bind_param("isssssssdssssissssisssisiiissssssiiiiisssssssssssssssssssss", $Trid, $TermId, $SiaCode, $DtTrans, $ApprNum, $Acid, $Acquirer, $Pan, $Amount, $Currency, $DtIns, $PointOfService, $Cont, $NumOper, $DtPos, $PosReq, $PosStan, $PfCode, $PMrc, $PosAcq, $GtResp, $NumTent, $TP, $CatMer, $VndId, $PvdId, $Bin, $Tpc, $VaFl, $FvFl, $TrKey, $CSeq, $Conf, $AutTime, $DBTime, $TOTTime, $DFN, $CED, $TTQ, $FFI, $TCAP, $ISR, $IST, $IAutD, $CryptCurr, $CryptType, $CrypAmnt, $CryptTD, $UN, $CVR, $TVR, $IAD, $CID, $AId, $HATC, $AIP, $ACrypt, $PaymentId, $CCode);

        // Esegui la query
        if ($stmt->execute()) {
            // Transazione inserita correttamente
        } else {
            http_response_code(500); // Internal Server Error
            echo json_encode(['error' => 'Database error: ' . $stmt->error]);
            $stmt->close();
            exit;
        }

        // Chiudi lo statement
        $stmt->close();
    }

    // Chiudi la connessione al database
    $conn->close();

    // Invia una risposta di successo
    http_response_code(200); // OK
    echo json_encode(['success' => 'Transactions processed successfully']);

} else {
    // Se la richiesta non è di tipo POST
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Method not allowed']);
}

?>

