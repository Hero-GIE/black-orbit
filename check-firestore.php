<?php

$env = [];
foreach (file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') continue;
    if (strpos($line, '=') === false) continue;
    list($k, $v) = explode('=', $line, 2);
    $env[trim($k)] = trim($v, " \"'");
}

$credsPath = $env['FIREBASE_CREDENTIALS'] ?? null;
$projectId = $env['FIREBASE_PROJECT_ID'] ?? null;

echo "Project: $projectId\n";
echo "Credentials file: $credsPath\n";

if (!$credsPath || !file_exists($credsPath)) {
    die("❌ Credentials file not found\n");
}

$creds = json_decode(file_get_contents($credsPath), true);
if (!$creds) {
    die("❌ Invalid credentials JSON\n");
}

echo "Service account: {$creds['client_email']}\n\n";

// Build JWT
$b64 = fn($d) => rtrim(strtr(base64_encode($d), '+/', '-_'), '=');
$header = $b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
$now = time();
$payload = $b64(json_encode([
    'iss'   => $creds['client_email'],
    'sub'   => $creds['client_email'],
    'scope' => 'https://www.googleapis.com/auth/datastore',
    'aud'   => 'https://oauth2.googleapis.com/token',
    'iat'   => $now,
    'exp'   => $now + 3600,
]));

openssl_sign($header . '.' . $payload, $sig, $creds['private_key'], OPENSSL_ALGO_SHA256);
$jwt = $header . '.' . $payload . '.' . $b64($sig);

// Exchange for access token
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
    'assertion'  => $jwt,
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($code !== 200) {
    die("❌ Token exchange failed (HTTP $code): $res\n");
}

$token = json_decode($res, true)['access_token'] ?? null;
echo "✅ Access token acquired\n\n";

// List documents in `videos` collection
$url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/videos?pageSize=50";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Firestore response: HTTP $code\n";

if ($code !== 200) {
    echo "Body: " . substr($res, 0, 500) . "\n";
    exit(1);
}

$data = json_decode($res, true);
$docs = $data['documents'] ?? [];

echo "Documents in `videos`: " . count($docs) . "\n\n";

foreach ($docs as $doc) {
    $name = basename($doc['name']);
    $f = $doc['fields'] ?? [];
    echo "📹 $name\n";
    echo "   courseid: " . ($f['courseid']['stringValue'] ?? '(missing)') . "\n";
    echo "   lessonid: " . ($f['lessonid']['stringValue'] ?? '(missing)') . "\n";
    echo "   title:    " . ($f['title']['stringValue'] ?? '(missing)') . "\n";
    echo "   videourl: " . substr($f['videourl']['stringValue'] ?? '(missing)', 0, 70) . "\n";
    echo "\n";
}

if (empty($docs)) {
    echo "⚠️  No documents found. Nothing has been written to Firestore yet.\n";
}
