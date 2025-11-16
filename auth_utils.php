<?php

// Function to calculate the request signature
function calculateRequestSignature($timestamp, $publicKey, $requestParams, $privateKey, $requestUri, $httpMethod) {
    // 1. Ricava i seguenti dati:
    // a. data/ora UTC nel formato YmdHis (es. 20130503101152)
    // b. chiave pubblica dell'utente (es. fVmmIm4IyI1VOimI)
    // c. tutti i parametri della request come lista di coppie "chiave/valore" (es. Nome=Pippo, COGNOME=Pluto)
    // d. chiave privata dell’utente (es. bd0d7f3d6200e3ee)
    // e. URI della request (es. http://host/root/path/option(abc)/file.ext?arg=value#anchor)
    // f. metodo HTTP fra GET/POST/PUT/DELETE in maiuscolo (es. GET)

    // 2. Calcola la firma della request:
    // a. rinomina tutte le chiavi dei parametri del passo "1-c" con lettere in minuscolo (es. nome=Pippo, cognome=Pluto)
    $lowerCaseParams = [];
    foreach ($requestParams as $key => $value) {
        $lowerCaseParams[strtolower($key)] = $value;
    }

    // b. ordina i parametri del passo "2-a" in ordine alfabetico crescente (es. cognome=Pluto, nome=Pippo)
    ksort($lowerCaseParams);

    // c. sostituisce il valore di ogni parametro del passo "2-b" con il proprio md5 (es. cognome= ea8dbc7900082678e2e4f7275c945902, nome=4a057a33f1d8158556eade51342786c6)
    $md5Params = [];
    foreach ($lowerCaseParams as $key => $value) {
        $md5Params[$key] = md5($value);
    }

    // d. genera una "query string" standard con le coppie “nome/valore” ottenute al passo "2-c" (es. cognome= ea8dbc7900082678e2e4f7275c945902&nome=4a057a33f1d8158556eade51342786c6)
    $queryString = http_build_query($md5Params);

    // e. calcola md5 della "query string" ottenuta al passo precedente (es. b4ffcc5d97e22f357ab2d63316dd318c)
    $md5QueryString = md5($queryString);

    // f. ricava la path della request (es. /root/path/option(abc)/file.ext)
    $parsedUrl = parse_url($requestUri);
    $path = $parsedUrl['path'];

    // g. genera una lista con le seguenti coppie chiave/valore in ordine alfabetico crescente:
    // i. path = path della request ottenuta al passo “2-f”
    // ii. verb = metodo della request ottenuto al passo “1-f”
    // iii. parameters = valore ottenuto dal passo "2-e"
    // iv. key = chiave pubblica dell'utente ricavata al passo “1-b”
    // v. timestamp = valore ottenuto dal passo "1-a" (es. 20130503101152)
    $signatureData = [
        'key' => $publicKey,
        'parameters' => $md5QueryString,
        'path' => $path,
        'timestamp' => $timestamp,
        'verb' => $httpMethod,
    ];
    ksort($signatureData);

    // h. genera una "query string" con le coppie nome-valore ottenute al passo "2-g" (es. key=fVmmIm4IyI1VOimI&parameters=b4ffcc5d97e22f357ab2d63316dd318c&path=%2Froot%2Fpath%2Foption%28abc%29%2Ffile.ext&timestamp=20130503101152&verb=GET)
    $signatureQueryString = http_build_query($signatureData);

    // i. calcola l’hash del valore ottenuto al passo "2-h" con il metodo "sha256" e la chiave privata dell'utente: il risultato costituisce la firma della request (es. 21ce8c1801281c6a3a6efcc6666260251fb1c2a433d73043fcd704bf6e601665)
    $signature = hash_hmac('sha256', $signatureQueryString, $privateKey);

    return $signature;
}

// Function to calculate the response signature
function calculateResponseSignature($timestamp, $publicKey, $responseBody, $privateKey, $requestUri, $httpMethod) {
    // 1. Ricava dalla response:
    // a. header personalizzato "auth_time"
    // b. header personalizzato "auth_key"
    // c. header personalizzato "auth_sign"
    // d. il body della response

    // 2. Calcola la firma della response:
    // a. calcola md5 del valore ottenuto al passo "1-d"
    $md5ResponseBody = md5($responseBody);

    // b. segue la procedura della “request signing” a partire dal passo "2-f" rispettando le seguenti note:
    // i. il valore di "parameters" è indicato al punto "2-a" di questa sezione
    // ii. il valore di “key” è indicato al punto "1-b" di questa sezione
    // iii. il valore di "timestamp" è indicato al "1-a" di questa sezione
    $parsedUrl = parse_url($requestUri);
    $path = $parsedUrl['path'];

    $signatureData = [
        'key' => $publicKey,
        'parameters' => $md5ResponseBody,
        'path' => $path,
        'timestamp' => $timestamp,
        'verb' => $httpMethod,
    ];
    ksort($signatureData);

    $signatureQueryString = http_build_query($signatureData);
    $signature = hash_hmac('sha256', $signatureQueryString, $privateKey);

    return $signature;
}

// Function to verify the response signature
function verifyResponseSignature($responseHeaders, $responseBody, $privateKey, $requestUri, $httpMethod, $publicKey) {
    // 1. Ricava dalla response:
    // a. header personalizzato "auth_time"
    // b. header personalizzato "auth_key"
    // c. header personalizzato "auth_sign"
    // d. il body della response
    $authTime = $responseHeaders['auth_time'];
    $authKey = $responseHeaders['auth_key'];
    $authSign = $responseHeaders['auth_sign'];

    // 2. Calcola la firma della response:
    // a. calcola md5 del valore ottenuto al passo "1-d"
    // b. segue la procedura della “request signing” a partire dal passo "2-f" rispettando le seguenti note:
    // i. il valore di "parameters" è indicato al punto "2-a" di questa sezione
    // ii. il valore di “key” è indicato al punto "1-b" di questa sezione
    // iii. il valore di "timestamp" è indicato al "1-a" di questa sezione
    $calculatedSignature = calculateResponseSignature($authTime, $authKey, $responseBody, $privateKey, $requestUri, $httpMethod);

    // 3. Verifica che:
    // a. il valore del passo "1-a" riporti una data/ora con una differenza non superiore a 15 minuti (questo limite può variare o essere ignorato)
    $now = new DateTime();
    $responseDateTime = DateTime::createFromFormat('YmdHis', $authTime);
    $diff = $now->diff($responseDateTime);
    $minutesDiff = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
    if ($minutesDiff > 15) {
        return false; // Time difference is greater than 15 minutes
    }

    // b. il valore del passo "1-b" sia uguale alla chiave pubblica dell'utente
    if ($authKey !== $publicKey) {
        return false; // Public key mismatch
    }

    // c. il valore ottenuto al passo “2-b” sia uguale al valore ottenuto al passo "1-c"
    if ($calculatedSignature !== $authSign) {
        return false; // Signature mismatch
    }

    // 4. La response è adesso verificata e può essere elaborata
    return true;
}

// Example Usage (Client-Side)
// --- Request Signing ---
// Dummy data for demonstration
$timestamp = date('YmdHis'); // Current timestamp
$publicKey = "fVmmIm4IyI1VOimI"; // User's public key
$privateKey = "bd0d7f3d6200e3ee"; // User's private key
$requestParams = [
    "Nome" => "Pippo",
    "COGNOME" => "Pluto",
    "arg" => "value"
];
$requestUri = "https://payglobews.moneynet.it/rest/evo/tid/evBankTerminalIdList?id=122456";
$httpMethod = "GET";

// Calculate the request signature
$requestSignature = calculateRequestSignature($timestamp, $publicKey, $requestParams, $privateKey, $requestUri, $httpMethod);

// Prepare the request headers
$requestHeaders = [
    "auth_key" => $publicKey,
    "auth_time" => $timestamp,
    "auth_sign" => $requestSignature,
];

echo "<h2>Request Signing Example</h2>";
echo "<p>Timestamp: " . $timestamp . "</p>";
echo "<p>Public Key: " . $publicKey . "</p>";
echo "<p>Private Key: " . $privateKey . "</p>";
echo "<p>Request Parameters: " . json_encode($requestParams) . "</p>";
echo "<p>Request URI: " . $requestUri . "</p>";
echo "<p>HTTP Method: " . $httpMethod . "</p>";
echo "<p>Request Signature: " . $requestSignature . "</p>";
echo "<p>Request Headers: " . json_encode($requestHeaders) . "</p>";

// --- Response Signing and Verification ---
// Dummy data for demonstration
$responseBody = "{\"status\":\"success\",\"message\":\"Plafond verificato\",\"plafond\":1000}";
$responseHeaders = [
    "auth_time" => date('YmdHis', strtotime('+5 minutes')), // Simulate a response time
    "auth_key" => $publicKey,
    "auth_sign" => calculateResponseSignature(date('YmdHis', strtotime('+5 minutes')), $publicKey, $responseBody, $privateKey, $requestUri, $httpMethod),
];

echo "<h2>Response Signing Example</h2>";
echo "<p>Response Body: " . $responseBody . "</p>";
echo "<p>Response Headers: " . json_encode($responseHeaders) . "</p>";

// Verify the response signature
$isResponseValid = verifyResponseSignature($responseHeaders, $responseBody, $privateKey, $requestUri, $httpMethod, $publicKey);

echo "<h2>Response Verification Example</h2>";
echo "<p>Is Response Valid: " . ($isResponseValid ? "Yes" : "No") . "</p>";

// Example of an invalid response (wrong signature)
$invalidResponseHeaders = [
    "auth_time" => date('YmdHis', strtotime('+5 minutes')), // Simulate a response time
    "auth_key" => $publicKey,
    "auth_sign" => "wrong_signature",
];

$isResponseInvalid = verifyResponseSignature($invalidResponseHeaders, $responseBody, $privateKey, $requestUri, $httpMethod, $publicKey);
echo "<h2>Invalid Response Verification Example</h2>";
echo "<p>Is Response Invalid: " . ($isResponseInvalid ? "Yes" : "No") . "</p>";

// Example of an invalid response (wrong time)
$invalidResponseHeaders = [
    "auth_time" => date('YmdHis', strtotime('+20 minutes')), // Simulate a response time
    "auth_key" => $publicKey,
    "auth_sign" => calculateResponseSignature(date('YmdHis', strtotime('+20 minutes')), $publicKey, $responseBody, $privateKey, $requestUri, $httpMethod),
];

$isResponseInvalid = verifyResponseSignature($invalidResponseHeaders, $responseBody, $privateKey, $requestUri, $httpMethod, $publicKey);
echo "<h2>Invalid Response Verification Example (wrong time)</h2>";
echo "<p>Is Response Invalid: " . ($isResponseInvalid ? "Yes" : "No") . "</p>";

// Example of an invalid response (wrong key)
$invalidResponseHeaders = [
    "auth_time" => date('YmdHis', strtotime('+5 minutes')), // Simulate a response time
    "auth_key" => "wrong_key",
    "auth_sign" => calculateResponseSignature(date('YmdHis', strtotime('+5 minutes')), "wrong_key", $responseBody, $privateKey, $requestUri, $httpMethod),
];

$isResponseInvalid = verifyResponseSignature($invalidResponseHeaders, $responseBody, $privateKey, $requestUri, $httpMethod, $publicKey);
echo "<h2>Invalid Response Verification Example (wrong key)</h2>";
echo "<p>Is Response Invalid: " . ($isResponseInvalid ? "Yes" : "No") . "</p>";

?>

