<?php


error_reporting(E_ALL);
ini_set('display_errors', 1);

$DRY_RUN = in_array('--dry-run', $argv);
$BUMP_UPDATED_AT = true;   // set false if you don't want updatedAt to change

$logFile = __DIR__ . '/migrate-content-log.txt';
function log_($m) {
    global $logFile;
    $line = "[" . date('H:i:s') . "] $m\n";
    file_put_contents($logFile, $line, FILE_APPEND);
    echo $m . "\n";
}

function loadEnv($p) {
    if (!file_exists($p)) return [];
    $e = [];
    foreach (file($p, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
        if (strpos(trim($l), '#') === 0) continue;
        if (strpos($l, '=') === false) continue;
        [$k, $v] = explode('=', $l, 2);
        $k = trim($k); $v = trim($v);
        if (preg_match('/^["\'](.*)["\']$/', $v, $m)) $v = $m[1];
        $e[$k] = $v;
    }
    return $e;
}

$env = loadEnv(__DIR__ . '/../.env');
$PROJECT_ID       = $env['FIREBASE_PROJECT_ID'] ?? null;
$CREDENTIALS_FILE = $env['FIREBASE_CREDENTIALS'] ?? null;
$COLLECTION       = 'articles';

if (!$PROJECT_ID || !$CREDENTIALS_FILE) die("❌ Missing env vars\n");
if (!file_exists($CREDENTIALS_FILE)) die("❌ Credentials file missing\n");

function b64u($d){ return rtrim(strtr(base64_encode($d), '+/', '-_'), '='); }

function getToken($credsFile) {
    $c = json_decode(file_get_contents($credsFile), true);
    $h = b64u(json_encode(['alg'=>'RS256','typ'=>'JWT']));
    $n = time();
    $p = b64u(json_encode([
        'iss'=>$c['client_email'],'sub'=>$c['client_email'],
        'scope'=>'https://www.googleapis.com/auth/datastore',
        'aud'=>'https://oauth2.googleapis.com/token',
        'iat'=>$n,'exp'=>$n+3600,
    ]));
    openssl_sign($h.'.'.$p, $sig, $c['private_key'], OPENSSL_ALGO_SHA256);
    $jwt = $h.'.'.$p.'.'.b64u($sig);
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST=>true,
        CURLOPT_POSTFIELDS=>http_build_query([
            'grant_type'=>'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'=>$jwt,
        ]),
        CURLOPT_RETURNTRANSFER=>true,
    ]);
    $r = curl_exec($ch); curl_close($ch);
    return json_decode($r, true)['access_token'] ?? null;
}

$token = getToken($CREDENTIALS_FILE);
if (!$token) die("❌ Could not obtain access token\n");

log_(($DRY_RUN ? "🧪 DRY RUN" : "🚀 LIVE RUN") . " | Collection: $COLLECTION");

// ---------- Fetch all articles ----------
$url = "https://firestore.googleapis.com/v1/projects/$PROJECT_ID/databases/(default)/documents/$COLLECTION";
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER=>true,
    CURLOPT_HTTPHEADER=>["Authorization: Bearer $token"],
]);
$res = curl_exec($ch); curl_close($ch);
$data = json_decode($res, true);

if (!isset($data['documents'])) {
    log_("⚠️  No documents found in '$COLLECTION'");
    exit(0);
}

$docs = $data['documents'];
log_("📄 Found " . count($docs) . " articles\n");

// ---------- Block array → HTML ----------
function blocksToHtml($blocks, $title) {
    $html = "<h1>" . htmlspecialchars(trim($title), ENT_QUOTES) . "</h1>\n";
    foreach ($blocks as $b) {
        $type = $b['type'] ?? 'paragraph';
        $text = $b['text'] ?? '';
        $text = htmlspecialchars($text, ENT_QUOTES);
        $text = nl2br($text);

        if ($type === 'heading') {
            $lvl = max(1, min(6, (int)($b['level'] ?? 2)));
            $html .= "<h$lvl>$text</h$lvl>\n";
        } elseif ($type === 'list') {
            $html .= "<ul><li>$text</li></ul>\n";
        } else {
            $html .= "<p>$text</p>\n";
        }
    }
    return $html;
}

// ---------- Build writes ----------
$writes = [];
foreach ($docs as $doc) {
    $name   = $doc['name'];
    $fields = $doc['fields'] ?? [];

    $docId = $fields['id']['stringValue'] ?? basename($name);
    $title = $fields['title']['stringValue'] ?? '';

    // Skip if content is already a string (already migrated)
    if (isset($fields['content']['stringValue'])) {
        log_("⏭️  $docId  — already HTML, skipping");
        continue;
    }

    // Skip if no block array
    if (!isset($fields['content']['arrayValue'])) {
        log_("⚠️  $docId  — no content block array, skipping");
        continue;
    }

    $blocks = $fields['content']['arrayValue']['values'] ?? [];
    $parsed = [];
    foreach ($blocks as $b) {
        $parsed[] = [
            'type'  => $b['mapValue']['fields']['type']['stringValue']  ?? 'paragraph',
            'text'  => $b['mapValue']['fields']['text']['stringValue']  ?? '',
            'level' => (int)($b['mapValue']['fields']['level']['integerValue'] ?? 2),
        ];
    }

    if (empty($parsed)) {
        log_("⚠️  $docId  — empty content, skipping");
        continue;
    }

    $html = blocksToHtml($parsed, $title);

    log_("✏️  $docId  — " . count($parsed) . " blocks → " . strlen($html) . " chars HTML");
    log_("      title:   " . substr($title, 0, 60));
    log_("      preview: " . substr(strip_tags($html), 0, 80) . "…\n");

    if ($DRY_RUN) continue;

    $updateFields = ['content' => ['stringValue' => $html]];
    $updatePaths  = ['content'];

    if ($BUMP_UPDATED_AT) {
        $updateFields['updatedAt'] = ['timestampValue' => date('c')];
        $updatePaths[] = 'updatedAt';
    }

    $writes[] = [
        'update' => [
            'name'   => $name,
            'fields' => $updateFields,
        ],
        'updateMask' => [
            'fieldPaths' => $updatePaths,
        ],
    ];
}

if ($DRY_RUN) {
    log_("🧪 Dry run finished — nothing was written");
    exit(0);
}

if (empty($writes)) {
    log_("✅ Nothing to update");
    exit(0);
}

// ---------- Commit ----------
$ch = curl_init("https://firestore.googleapis.com/v1/projects/$PROJECT_ID/databases/(default)/documents:batchWrite");
curl_setopt_array($ch, [
    CURLOPT_POST=>true,
    CURLOPT_POSTFIELDS=>json_encode(['writes'=>$writes]),
    CURLOPT_RETURNTRANSFER=>true,
    CURLOPT_HTTPHEADER=>[
        "Authorization: Bearer $token",
        "Content-Type: application/json",
    ],
]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($code !== 200) {
    log_("❌ HTTP $code: " . substr($res, 0, 500));
    exit(1);
}

log_("\n✅ Updated " . count($writes) . " articles (content field only)");
