<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=========================================\n";
echo "🚀 Firestore Import Tool (Random IDs)\n";
echo "⚠️  WARNING: This will DELETE existing personalities!\n";
echo "=========================================\n\n";

// Load .env
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    die("❌ .env file not found at: $envFile\n");
}

echo "✅ .env file found\n";

// Parse .env
$env = [];
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
    list($key, $value) = explode('=', $line, 2);
    $env[trim($key)] = trim(trim($value), '"\'');
}

// Get config
$PROJECT_ID = $env['FIREBASE_PROJECT_ID'] ?? null;
$CREDENTIALS_FILE = $env['FIREBASE_CREDENTIALS'] ?? null;
$CSV_FILE = $argv[1] ?? __DIR__ . '/storage/app/innovators.csv';

echo "📋 Configuration:\n";
echo "  Project ID: " . ($PROJECT_ID ?: '❌ NOT SET') . "\n";
echo "  Credentials: " . ($CREDENTIALS_FILE ?: '❌ NOT SET') . "\n";
echo "  CSV File: " . basename($CSV_FILE) . "\n\n";

if (!$PROJECT_ID) die("❌ FIREBASE_PROJECT_ID not set in .env\n");
if (!$CREDENTIALS_FILE) die("❌ FIREBASE_CREDENTIALS not set in .env\n");
if (!file_exists($CREDENTIALS_FILE)) die("❌ Credentials file not found: $CREDENTIALS_FILE\n");
if (!file_exists($CSV_FILE)) die("❌ CSV file not found: $CSV_FILE\n");

echo "🔑 Getting access token...\n";

// Get token
$creds = json_decode(file_get_contents($CREDENTIALS_FILE), true);
if (!$creds) die("❌ Invalid credentials JSON\n");

// Create JWT
$header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
$now = time();
$payload = base64_encode(json_encode([
    'iss' => $creds['client_email'],
    'sub' => $creds['client_email'],
    'aud' => 'https://www.googleapis.com/oauth2/v4/token',
    'iat' => $now,
    'exp' => $now + 3600,
    'scope' => 'https://www.googleapis.com/auth/datastore https://www.googleapis.com/auth/cloud-platform',
]));

$signature = '';
openssl_sign($header . '.' . $payload, $signature, $creds['private_key'], OPENSSL_ALGO_SHA256);
$jwt = $header . '.' . $payload . '.' . base64_encode($signature);

// Get access token
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v4/token');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
    'assertion' => $jwt,
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Failed to get token (HTTP $httpCode)\n";
    echo "Response: $response\n";
    exit(1);
}

$tokenData = json_decode($response, true);
$token = $tokenData['access_token'] ?? null;
if (!$token) die("❌ No access token in response\n");

echo "✅ Access token obtained\n";

// Test token
echo "🧪 Testing token...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://firestore.googleapis.com/v1/projects/$PROJECT_ID/databases/(default)");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Token test failed (HTTP $httpCode)\n";
    echo "Response: $response\n";
    exit(1);
}
echo "✅ Token works!\n\n";

// Function to generate random ID (like Firebase push ID)
function generateRandomId($length = 28) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $id = '';
    for ($i = 0; $i < $length; $i++) {
        $id .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $id;
}

// ========== STEP 1: DELETE ALL EXISTING PERSONALITIES ==========
echo "🗑️  Step 1: Deleting existing personalities...\n";

function deleteAllDocuments($projectId, $token) {
    $deleted = 0;
    $collection = 'personalities';
    
    while (true) {
        // Get a batch of documents
        $url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/$collection?pageSize=300";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            echo "  ⚠️  No documents found or error fetching\n";
            break;
        }
        
        $data = json_decode($response, true);
        if (!isset($data['documents']) || empty($data['documents'])) {
            echo "  ✅ No more documents to delete\n";
            break;
        }
        
        // Prepare delete writes
        $writes = [];
        foreach ($data['documents'] as $doc) {
            $docName = $doc['name'];
            $writes[] = ['delete' => $docName];
        }
        
        // Execute batch delete
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents:batchWrite");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['writes' => $writes]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $deleted += count($writes);
            echo "  🗑️  Deleted " . count($writes) . " documents (Total: $deleted)\n";
        } else {
            echo "  ❌ Failed to delete batch: $response\n";
            break;
        }
    }
    
    return $deleted;
}

$deletedCount = deleteAllDocuments($PROJECT_ID, $token);
echo "  ✅ Deleted $deletedCount documents total\n\n";

// ========== STEP 2: IMPORT NEW DATA WITH RANDOM IDs ==========
echo "📥 Step 2: Importing new data with random IDs...\n";

$file = fopen($CSV_FILE, 'r');
$header = fgetcsv($file);
$total = 0;
$batch = [];
$batchSize = 0;

while (($row = fgetcsv($file)) !== false) {
    $data = array_combine($header, $row);
    $name = trim($data['name'] ?? '');
    
    // Generate a random document ID
    $docId = generateRandomId(28);
    
    // Handle achievements as array
    $achievements = trim($data['achievements'] ?? '');
    $achievementsArray = [];
    if (!empty($achievements)) {
        $achievementsArray = array_map('trim', explode(',', $achievements));
    }
    
    // Create slug from name (for reference)
    $slug = strtolower(str_replace(' ', '-', $name));
    
    $docData = [
        'fields' => [
            'name' => ['stringValue' => $name],
            'occupation' => ['stringValue' => trim($data['occupation'] ?? '')],
            'bio' => ['stringValue' => trim($data['bio'] ?? '')],
            'achievements' => ['arrayValue' => ['values' => array_map(function($a) {
                return ['stringValue' => $a];
            }, $achievementsArray)]],
            'image' => ['stringValue' => trim($data['image'] ?? '')],
            'slug' => ['stringValue' => $slug],
            'createdAt' => ['timestampValue' => date('c')],
            'updatedAt' => ['timestampValue' => date('c')],
        ]
    ];
    
    $batch[$docId] = $docData;
    $batchSize++;
    $total++;
    
    echo "  📝 Added: $name -> ID: $docId\n";
    
    if ($batchSize >= 500) {
        $writes = [];
        foreach ($batch as $id => $data) {
            $writes[] = ['update' => ['name' => "projects/$PROJECT_ID/databases/(default)/documents/personalities/$id", 'fields' => $data['fields']]];
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://firestore.googleapis.com/v1/projects/$PROJECT_ID/databases/(default)/documents:batchWrite");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['writes' => $writes]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            echo "  ✅ Batch of " . count($writes) . " committed ($total total)\n";
        } else {
            echo "  ❌ Batch failed (HTTP $httpCode): " . substr($response, 0, 200) . "\n";
        }
        
        $batch = [];
        $batchSize = 0;
    }
}

// Commit remaining
if ($batchSize > 0) {
    $writes = [];
    foreach ($batch as $id => $data) {
        $writes[] = ['update' => ['name' => "projects/$PROJECT_ID/databases/(default)/documents/personalities/$id", 'fields' => $data['fields']]];
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://firestore.googleapis.com/v1/projects/$PROJECT_ID/databases/(default)/documents:batchWrite");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['writes' => $writes]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        echo "  ✅ Final batch of " . count($writes) . " committed\n";
    } else {
        echo "  ❌ Final batch failed (HTTP $httpCode): " . substr($response, 0, 200) . "\n";
    }
}

fclose($file);

echo "\n✅ Import completed!\n";
echo "📊 Summary:\n";
echo "  - Deleted: $deletedCount old documents\n";
echo "  - Created: $total new documents with random IDs\n";
echo "=========================================\n";

