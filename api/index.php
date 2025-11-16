<?php
/**
 * stores-rest-api — PHP 7.4+/8.x compatibile, MySQLi, Basic & Bearer (JWT HS256)
 * 
 * curl -i -X PATCH https://api.payglobe.it/merchant/api/stores/T12345 \
  -H "Authorization: Basic $(printf "admin:password123" | base64)" \
  -H "Content-Type: application/json" \
  -d '{"citta":"Roma","prov":"RM"}'

  curl -i -X POST https://api.payglobe.it/merchant/api/stores \
  -H "Authorization: Basic $(printf "admin:password123" | base64)" \
  -H "Content-Type: application/json" \
  -d '{
        "TerminalID": "T54321",
        "Ragione_Sociale": "Acme Srl",
        "Insegna": "Acme Shop",
        "indirizzo": "Corso Italia 22",
        "citta": "Milano",
        "cap": "20100",
        "prov": "MI",
        "Modello_pos": "Verifone VX520"
      }'

 */

// =============================
// CONFIG
// =============================
const DB_HOST = '10.10.10.13';
const DB_USER = 'PGDBUSER';
const DB_PASS = 'PNeNkar{K1.%D~V';
const DB_NAME = 'payglobe';
const DB_PORT = 3306;

const REQUIRE_HTTPS = false;
const CORS_ORIGINS = ['*'];

const BASIC_USERS = [
 'admin' => 'password123',
'moneynet' => 'demo123'
];

const JWT_SECRET = 'ASJHC/Snaks67l2wd9j2djkdcx3123SSDFFaqc';
const JWT_ISS    = 'payglobe-stores-api';
const JWT_AUD    = 'payglobe-clients';

const TABLE = 'stores';
const PK    = 'TerminalID';

$ALL_FIELDS = [
  'TerminalID','Ragione_Sociale','Insegna','indirizzo','citta','cap','prov',
  'sia_pagobancomat','six','amex','Modello_pos','country','bu','bu1','bu2'
];
$WRITABLE_FIELDS = [
  'Ragione_Sociale','Insegna','indirizzo','citta','cap','prov',
  'sia_pagobancomat','six','amex','Modello_pos','country','bu','bu1','bu2'
];

// =============================
// POLYFILLS PHP 7.x
// =============================
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}

// =============================
// BOOTSTRAP
// =============================
if (REQUIRE_HTTPS && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on')) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'HTTPS is required']);
    exit;
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array('*', CORS_ORIGINS) || in_array($origin, CORS_ORIGINS)) {
    header('Access-Control-Allow-Origin: ' . (in_array('*', CORS_ORIGINS) ? '*' : $origin));
    header('Vary: Origin');
}
header('Access-Control-Allow-Methods: GET,POST,PUT,PATCH,DELETE,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }

header('Content-Type: application/json');

$mysqli = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed', 'detail' => $mysqli->connect_error]);
    exit;
}
$mysqli->set_charset('utf8mb4');

// =============================
// UTIL
// =============================
function json_input(): array {
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) return [];
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON body']);
        exit;
    }
    return $data ?: [];
}

function respond($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function base64url_decode(string $data): string { return base64_decode(strtr($data, '-_', '+/')); }

function verify_jwt(string $jwt): ?array {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return null;
    list($h, $p, $s) = $parts;
    $header  = json_decode(base64url_decode($h), true);
    $payload = json_decode(base64url_decode($p), true);
    $sig     = base64url_decode($s);
    if (!is_array($header) || ($header['alg'] ?? '') !== 'HS256') return null;
    $calc = hash_hmac('sha256', "$h.$p", JWT_SECRET, true);
    if (!hash_equals($calc, $sig)) return null;
    $now = time();
    if (($payload['exp'] ?? $now) < $now) return null;
    if (($payload['nbf'] ?? 0) > $now) return null;
    if (($payload['iss'] ?? '') && $payload['iss'] !== JWT_ISS) return null;
    if (($payload['aud'] ?? '') && $payload['aud'] !== JWT_AUD) return null;
    return $payload;
}

function require_auth(): array {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['Authorization'] ?? '');
    if (!$auth) {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        foreach ($headers as $k => $v) {
            if (strcasecmp($k, 'Authorization') === 0) { $auth = $v; break; }
        }
    }

    if (!$auth) unauthorized('Missing Authorization header');

    if (stripos($auth, 'Bearer ') === 0) {
        $jwt = trim(substr($auth, 7));
        $payload = verify_jwt($jwt);
        if (!$payload) unauthorized('Invalid or expired token');
        return ['method' => 'bearer', 'subject' => ($payload['sub'] ?? null), 'claims' => $payload];
    }

   if (stripos($auth, 'Basic ') === 0) {
    $b64  = trim(substr($auth, 6));
    $pair = base64_decode($b64, true);
    if ($pair === false || strpos($pair, ':') === false) unauthorized('Invalid Basic token');
    list($u, $p) = explode(':', $pair, 2);
    $stored = BASIC_USERS[$u] ?? null;
    if (!$stored || $stored !== $p) unauthorized('Invalid Basic credentials');
    return ['method' => 'basic', 'subject' => $u, 'claims' => []];
}


    unauthorized('Unsupported Authorization scheme');
}

function unauthorized(string $msg): void {
    http_response_code(401);
    header('WWW-Authenticate: Basic realm="stores"');
    echo json_encode(['error' => 'Unauthorized', 'detail' => $msg]);
    exit;
}

function not_found(string $msg = 'Not found'): void { respond(['error' => $msg], 404); }

function sanitize_id(string $id): string {
    return substr(preg_replace('/[^A-Za-z0-9._-]/', '', $id), 0, 45);
}

function read_query_param(string $key, $default = null) { return $_GET[$key] ?? $default; }

// =============================
// AUTH
// =============================
$user = require_auth();

// =============================
// ROUTER
// =============================
$method = $_SERVER['REQUEST_METHOD'];
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$script = $_SERVER['SCRIPT_NAME'];
$base = rtrim(str_replace('index.php', '', $script), '/');
if ($base && substr($path, 0, strlen($base)) === $base) { $path = substr($path, strlen($base)); }
$path = '/' . ltrim($path, '/');

$matches = [];
if ($path === '/stores') {
    switch ($method) {
        case 'GET':  list_stores($mysqli, $ALL_FIELDS); break;
        case 'POST': create_store($mysqli, $ALL_FIELDS, $WRITABLE_FIELDS); break;
        default: respond(['error' => 'Method not allowed'], 405);
    }
} elseif (preg_match('#^/stores/([^/]+)$#', $path, $matches)) {
    $id = sanitize_id(urldecode($matches[1]));
    switch ($method) {
        case 'GET':    get_store($mysqli, $ALL_FIELDS, $id); break;
        case 'PUT':    update_store($mysqli, $WRITABLE_FIELDS, $id, false); break;
        case 'PATCH':  update_store($mysqli, $WRITABLE_FIELDS, $id, true); break;
        case 'DELETE': delete_store($mysqli, $id); break;
        default: respond(['error' => 'Method not allowed'], 405);
    }
} else {
    respond(['status' => 'ok', 'service' => 'payglobe.stores', 'user' => $user['subject']]);
}

// =============================
// HANDLERS
// =============================
function list_stores(mysqli $db, array $fields): void {
    $page   = max(1, (int)read_query_param('page', 1));
    $size   = min(200, max(1, (int)read_query_param('page_size', 25)));
    $offset = ($page - 1) * $size;

    $where  = [];
    $params = [];
    $types  = '';

    $filters = [ 'citta' => 'citta', 'prov' => 'prov', 'country' => 'country', 'bu' => 'bu' ];
    foreach ($filters as $q => $col) {
        if (isset($_GET[$q]) && $_GET[$q] !== '') { $where[] = "$col = ?"; $params[] = $_GET[$q]; $types .= 's'; }
    }
    if (isset($_GET['q']) && $_GET['q'] !== '') {
        $q = '%' . $_GET['q'] . '%';
        $where[] = '(TerminalID LIKE ? OR Ragione_Sociale LIKE ? OR Insegna LIKE ? OR citta LIKE ? OR indirizzo LIKE ?)';
        array_push($params, $q, $q, $q, $q, $q);
        $types .= 'sssss';
    }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $sqlCount = 'SELECT COUNT(*) FROM ' . TABLE . ' ' . $whereSql;
    $stmt = $db->prepare($sqlCount);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();
    $stmt->close();

    $cols = implode(',', array_map(function($c){return "`$c`";}, $fields));
    $sql  = 'SELECT ' . $cols . ' FROM ' . TABLE . ' ' . $whereSql . ' ORDER BY TerminalID ASC LIMIT ? OFFSET ?';
    $stmt = $db->prepare($sql);

    if ($types) {
        $types2  = $types . 'ii';
        $params2 = array_merge($params, [$size, $offset]);
        $stmt->bind_param($types2, ...$params2);
    } else {
        $stmt->bind_param('ii', $size, $offset);
    }

    $stmt->execute();
    $res  = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    respond(['page' => $page, 'page_size' => $size, 'total' => (int)$total, 'items' => $rows]);
}

function get_store(mysqli $db, array $fields, string $id): void {
    $cols = implode(',', array_map(function($c){return "`$c`";}, $fields));
    $sql  = 'SELECT ' . $cols . ' FROM ' . TABLE . ' WHERE ' . PK . ' = ? LIMIT 1';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    if (!$row) not_found(PK . ' not found');
    respond($row);
}

function create_store(mysqli $db, array $allFields, array $writable): void {
    $data = json_input();
    if (empty($data['TerminalID'])) respond(['error' => 'Missing TerminalID'], 400);
    $id = sanitize_id((string)$data['TerminalID']);
    if ($id === '') respond(['error' => 'Invalid TerminalID'], 400);

    $data = array_intersect_key($data, array_flip($allFields));
    if (empty($data['country'])) $data['country'] = 'IT';
    if (empty($data['bu'])) $data['bu'] = 'MUL';

    $stmt = $db->prepare('SELECT 1 FROM ' . TABLE . ' WHERE ' . PK . ' = ?');
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) { $stmt->close(); respond(['error' => 'Already exists'], 409); }
    $stmt->close();

    $columns = [];$placeholders = [];$types = '';$values = [];
    foreach ($allFields as $col) {
        if ($col === 'TerminalID') { $columns[]=$col; $placeholders[]='?'; $types.='s'; $values[]=$id; continue; }
        if (array_key_exists($col, $data)) { $columns[]=$col; $placeholders[]='?'; $types.='s'; $values[]=(string)$data[$col]; }
    }
    $sql = 'INSERT INTO ' . TABLE . ' (`' . implode('`,`', $columns) . '`) VALUES (' . implode(',', $placeholders) . ')';
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$values);
    if (!$stmt->execute()) respond(['error' => 'Insert failed', 'detail' => $stmt->error], 500);
    $stmt->close();

    get_store($db, $allFields, $id);
}

function update_store(mysqli $db, array $writable, string $id, bool $partial): void {
    $data = json_input();
    unset($data['TerminalID']);
    $data = array_intersect_key($data, array_flip($writable));
    if (!$partial && empty($data)) respond(['error' => 'No fields provided for full update'], 400);
    if ( $partial && empty($data)) respond(['error' => 'No fields to update'], 400);

    $sets = [];$types='';$values=[];
    foreach ($data as $col => $val) { $sets[] = "`$col` = ?"; $types.='s'; $values[] = (string)$val; }
    $sql = 'UPDATE ' . TABLE . ' SET ' . implode(', ', $sets) . ' WHERE ' . PK . ' = ?';
    $types .= 's';
    $values[] = $id;

    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$values);
    if (!$stmt->execute()) respond(['error' => 'Update failed', 'detail' => $stmt->error], 500);
    if ($stmt->affected_rows === 0) {
        $stmt->close();
        $chk = $db->prepare('SELECT 1 FROM ' . TABLE . ' WHERE ' . PK . ' = ?');
        $chk->bind_param('s', $id);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows === 0) { $chk->close(); not_found(PK . ' not found'); }
        $chk->close();
    } else { $stmt->close(); }

    global $ALL_FIELDS; get_store($db, $ALL_FIELDS, $id);
}

function delete_store(mysqli $db, string $id): void {
    $stmt = $db->prepare('DELETE FROM ' . TABLE . ' WHERE ' . PK . ' = ?');
    $stmt->bind_param('s', $id);
    if (!$stmt->execute()) respond(['error' => 'Delete failed', 'detail' => $stmt->error], 500);
}
