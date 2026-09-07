<?php

/**
 * CSV TO FIRESTORE IMPORTER - Personalities Collection
 * REPLACE ALL DATA - Deletes existing documents before import
 * With Auto-Generated Document IDs
 * Includes category field
 * NO DUPLICATE CHECKING - imports all rows
 */

// ========== ENABLE ERROR REPORTING ==========
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Create a log file
$logFile = __DIR__ . '/import-log.txt';
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    echo $message . "\n";
}

writeLog("=========================================");
writeLog("🚀 Starting Firestore Import Process");
writeLog("=========================================");

// ========== LOAD .ENV FILE ==========
writeLog("📁 Loading .env file...");

function loadEnv($path) {
    if (!file_exists($path)) {
        writeLog("❌ .env file not found at: $path");
        return [];
    }

    writeLog("✅ .env file found at: $path");
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];

    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            if (strpos($value, '"') === 0 || strpos($value, "'") === 0) {
                $value = substr($value, 1, -1);
            }

            $env[$key] = $value;
        }
    }

    return $env;
}

$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    writeLog("❌ CRITICAL: .env file not found at: $envFile");
    die("❌ .env file not found. Please ensure you're running this from the project root.\n");
}

$env = loadEnv($envFile);

// ========== DEBUG: SHOW ALL FIREBASE ENV VARIABLES ==========
writeLog("🔍 Debug: Checking .env values...");
$firebaseVars = array_filter($env, function($key) {
    return strpos($key, 'FIREBASE') === 0;
}, ARRAY_FILTER_USE_KEY);

foreach ($firebaseVars as $key => $value) {
    if ($key === 'FIREBASE_API_KEY' && strlen($value) > 10) {
        $displayValue = substr($value, 0, 10) . '...' . substr($value, -5);
    } elseif ($key === 'FIREBASE_CREDENTIALS') {
        $displayValue = basename($value);
    } else {
        $displayValue = $value ? '✅ SET' : '❌ EMPTY';
    }
    writeLog("  $key = $displayValue");
}

// ========== GET CONFIGURATION FROM .ENV ==========
$PROJECT_ID = $env['FIREBASE_PROJECT_ID'] ?? null;
$CREDENTIALS_FILE = $env['FIREBASE_CREDENTIALS'] ?? null;
$CSV_FILE = $argv[1] ?? __DIR__ . '/../storage/app/personalities.csv';

// ========== VALIDATE PROJECT_ID ==========
writeLog("\n📋 Validating configuration...");

if (!$PROJECT_ID) {
    writeLog("❌ ERROR: FIREBASE_PROJECT_ID not set in .env file");
    die("❌ Missing FIREBASE_PROJECT_ID in .env\n");
}

if ($PROJECT_ID === 'YOUR_PROJECT_ID_HERE') {
    writeLog("❌ ERROR: FIREBASE_PROJECT_ID still has placeholder value");
    die("❌ Please replace 'YOUR_PROJECT_ID_HERE' with your actual project ID\n");
}

writeLog("✅ FIREBASE_PROJECT_ID: $PROJECT_ID");

// ========== VALIDATE CREDENTIALS_FILE ==========
if (!$CREDENTIALS_FILE) {
    writeLog("❌ ERROR: FIREBASE_CREDENTIALS not set in .env file");
    die("❌ Missing FIREBASE_CREDENTIALS in .env\n");
}

writeLog("📄 FIREBASE_CREDENTIALS: $CREDENTIALS_FILE");

if (!file_exists($CREDENTIALS_FILE)) {
    writeLog("❌ ERROR: Credentials file not found at: $CREDENTIALS_FILE");
    die("❌ Credentials file not found\n");
}

writeLog("✅ Credentials file exists");

// ========== VALIDATE CSV_FILE ==========
writeLog("📄 CSV File: $CSV_FILE");

if (!file_exists($CSV_FILE)) {
    writeLog("❌ CSV file not found: $CSV_FILE");
    die("❌ CSV file not found\n");
}

writeLog("✅ CSV file exists");

// ========== CHECK CREDENTIALS FILE CONTENT ==========
writeLog("\n🔑 Validating credentials file...");

$credsContent = file_get_contents($CREDENTIALS_FILE);
$creds = json_decode($credsContent, true);

if (!$creds) {
    writeLog("❌ ERROR: Invalid JSON in credentials file");
    die("❌ Credentials file is not valid JSON\n");
}

writeLog("✅ Credentials file is valid JSON");
writeLog("   Client Email: " . ($creds['client_email'] ?? 'NOT FOUND'));
writeLog("   Project ID: " . ($creds['project_id'] ?? 'NOT FOUND'));
writeLog("   Private Key: " . (isset($creds['private_key']) ? 'Found (length: ' . strlen($creds['private_key']) . ')' : 'NOT FOUND'));

if (isset($creds['project_id']) && $creds['project_id'] !== $PROJECT_ID) {
    writeLog("⚠️  WARNING: Project ID mismatch - using .env value: $PROJECT_ID");
}

// ========== START IMPORT ==========
writeLog("\n🚀 Starting import process...");
writeLog("=========================================\n");

try {
    // Get access token
    writeLog("🔑 Attempting to get access token...");
    $token = getAccessToken($CREDENTIALS_FILE);

    if (!$token) {
        writeLog("❌ Failed to get access token");
        die("❌ Failed to get access token.\n");
    }

    writeLog("✅ Access token obtained successfully");

    // Test the token
    writeLog("\n🧪 Testing token with Firestore API...");
    testToken($PROJECT_ID, $token);

    // ========== DELETE ALL EXISTING DOCUMENTS ==========
    writeLog("\n🗑️ DELETE PHASE: Removing all existing documents...");
    $deletedCount = deleteAllDocuments($PROJECT_ID, $token);
    writeLog("   ✅ Deleted $deletedCount existing documents");

    // Import the CSV
    writeLog("\n📥 IMPORT PHASE: Starting CSV import...");
    importCSV($CSV_FILE, $PROJECT_ID, $token);

    writeLog("\n✅ Import completed successfully!");
    writeLog("=========================================");

} catch (Exception $e) {
    writeLog("❌ ERROR: " . $e->getMessage());
    writeLog("   Stack trace: " . $e->getTraceAsString());
    die("❌ Import failed: " . $e->getMessage() . "\n");
}


// ============================================================
// BASE64 URL ENCODER
// ============================================================

function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}


function getAccessToken($credsFile) {
    try {
        writeLog("   Reading credentials file...");
        $creds = json_decode(file_get_contents($credsFile), true);

        if (!$creds) {
            writeLog("   ❌ Invalid credentials file");
            return null;
        }

        writeLog("   ✅ Credentials loaded successfully");
        writeLog("   Client Email: " . ($creds['client_email'] ?? 'NOT FOUND'));

        $header = base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $now = time();

        $payload = base64UrlEncode(json_encode([
            'iss' => $creds['client_email'],
            'sub' => $creds['client_email'],
            'scope' => 'https://www.googleapis.com/auth/datastore',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        writeLog("   JWT created, signing with private key...");
        $signature = '';

        $signResult = openssl_sign($header . '.' . $payload, $signature, $creds['private_key'], OPENSSL_ALGO_SHA256);

        if (!$signResult) {
            writeLog("   ❌ Failed to sign JWT");
            return null;
        }

        $jwt = $header . '.' . $payload . '.' . base64UrlEncode($signature);
        writeLog("   ✅ JWT signed successfully");

        writeLog("   Exchanging JWT for access token...");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            writeLog("   ❌ CURL Error: $error");
            return null;
        }

        if ($httpCode !== 200) {
            writeLog("   ❌ HTTP Error: $httpCode");
            writeLog("   Response: $response");
            return null;
        }

        $data = json_decode($response, true);

        if (!$data) {
            writeLog("   ❌ Failed to parse response JSON");
            return null;
        }

        writeLog("   ✅ Access token obtained");
        return $data['access_token'] ?? null;

    } catch (Exception $e) {
        writeLog("   ❌ Exception in getAccessToken: " . $e->getMessage());
        return null;
    }
}

function testToken($projectId, $token) {
    writeLog("   Testing token with project ID: $projectId");

    $url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        writeLog("   ✅ Token works! Firestore database accessible");
        return true;
    } else {
        writeLog("   ❌ Token test failed with HTTP $httpCode");
        writeLog("   Response: $response");
        return false;
    }
}

// ============================================================
// DELETE ALL DOCUMENTS
// ============================================================

function deleteAllDocuments($projectId, $token) {
    $totalDeleted = 0;
    $pageToken = null;

    do {
        $url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/personalities";
        if ($pageToken) {
            $url .= "?pageToken=" . urlencode($pageToken);
        }

        writeLog("   Fetching batch of documents...");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            writeLog("   ❌ CURL Error fetching documents: $error");
            break;
        }

        if ($httpCode !== 200) {
            writeLog("   ❌ Failed to fetch documents: HTTP $httpCode");
            writeLog("   Response: " . substr($response, 0, 300));
            break;
        }

        $data = json_decode($response, true);

        if (!isset($data['documents']) || empty($data['documents'])) {
            writeLog("   No more documents to delete");
            break;
        }

        $writes = [];
        foreach ($data['documents'] as $doc) {
            $docPath = $doc['name'];
            $writes[] = [
                'delete' => $docPath
            ];
        }

        $batchCount = count($writes);
        writeLog("   Deleting $batchCount documents...");

        $url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents:batchWrite";

        $payload = json_encode(['writes' => $writes]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            writeLog("   ❌ CURL Error deleting batch: $error");
            break;
        }

        if ($httpCode !== 200) {
            writeLog("   ❌ Batch delete failed: HTTP $httpCode");
            writeLog("   Response: " . substr($response, 0, 300));
            break;
        }

        $totalDeleted += $batchCount;
        writeLog("   ✅ Deleted $batchCount documents (Total: $totalDeleted)");

        $pageToken = $data['nextPageToken'] ?? null;

    } while ($pageToken);

    return $totalDeleted;
}

// ============================================================
// IMPORT CSV - WITH CATEGORY SUPPORT, NO DUPLICATE CHECKING
// ============================================================

function importCSV($csvFile, $projectId, $token) {
    $file = fopen($csvFile, 'r');

    if (!$file) {
        throw new Exception("Could not open CSV file");
    }

    $header = fgetcsv($file);

    if (!$header) {
        fclose($file);
        throw new Exception("Empty CSV or no header");
    }

    // Clean header
    $header = array_map('trim', $header);

    writeLog("   CSV Header: " . implode(', ', $header));

    $total = 0;
    $created = 0;
    $batch = [];
    $batchSize = 0;

    writeLog("   Starting to process rows...");

    while (($row = fgetcsv($file)) !== false) {
        // Skip empty rows
        if (count($row) === 1 && empty($row[0])) {
            continue;
        }

        // If row has fewer columns than header, pad with empty strings
        if (count($row) < count($header)) {
            $row = array_pad($row, count($header), '');
        }

        // If row has more columns than header, truncate
        if (count($row) > count($header)) {
            $row = array_slice($row, 0, count($header));
        }

        $data = array_combine($header, $row);

        $name = trim($data['name'] ?? '');

        if (empty($name)) {
            continue;
        }

        $occupation = trim($data['occupation'] ?? '');
        $bio = trim($data['bio'] ?? '');
        $bio = str_replace(["\n", "\r"], ' ', $bio);
        $bio = preg_replace('/\s+/', ' ', $bio);

        $achievements = trim($data['achievements'] ?? '');
        $achievementsArray = [];

        if (!empty($achievements)) {
            $achievementsArray = array_map('trim', explode(',', $achievements));
        }

        $image = trim($data['image'] ?? '');

        // Get category from CSV
        $category = trim($data['category'] ?? '');

        // Generate slug from name
        $slug = generateSlug($name);

        // Prepare document data with category field
        $docData = [
            'fields' => [
                'name' => ['stringValue' => $name],
                'occupation' => ['stringValue' => $occupation],
                'bio' => ['stringValue' => $bio],
                'achievements' => [
                    'arrayValue' => [
                        'values' => array_map(
                            function($a) {
                                return ['stringValue' => trim($a)];
                            },
                            array_filter($achievementsArray, function($a) {
                                return !empty(trim($a));
                            })
                        )
                    ]
                ],
                'image' => ['stringValue' => $image],
                'category' => ['stringValue' => $category],
                'slug' => ['stringValue' => $slug],
                'createdAt' => ['timestampValue' => date('c')],
                'updatedAt' => ['timestampValue' => date('c')],
            ]
        ];

        $batch[] = $docData;
        $batchSize++;
        $total++;
        $created++;

        // Log every 10 rows to show progress
        if ($total % 10 === 0) {
            writeLog("   📝 Processed $total records (Category: $category)");
        }

        if ($batchSize >= 500) {
            commitBatch($batch, $projectId, $token);
            $batch = [];
            $batchSize = 0;
            writeLog("   ✅ Processed $total records (Created: $created)");
        }
    }

    // Commit any remaining records
    if ($batchSize > 0) {
        commitBatch($batch, $projectId, $token);
    }

    fclose($file);

    writeLog("\n📊 Import Summary:");
    writeLog("  - Total processed: $total");
    writeLog("  - New documents created: $created");
}

function commitBatch($batch, $projectId, $token) {
    if (empty($batch)) {
        return;
    }

    $writes = [];

    foreach ($batch as $docData) {
        $writes[] = [
            'update' => [
                'name' => "projects/$projectId/databases/(default)/documents/personalities/" . uniqid(),
                'fields' => $docData['fields'],
            ]
        ];
    }

    $url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents:batchWrite";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['writes' => $writes]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception("CURL Error in batch commit: $error");
    }

    if ($httpCode !== 200) {
        throw new Exception("Batch commit failed (HTTP $httpCode): " . substr($response, 0, 500));
    }

    writeLog("   📦 Batch of " . count($writes) . " documents committed");
}

function generateSlug($name) {
    $slug = strtolower(trim($name));

    $specialChars = [
        ' ' => '-',
        '—' => '-',
        '–' => '-',
        '&' => 'and',
        'é' => 'e',
        'è' => 'e',
        'ê' => 'e',
        'ë' => 'e',
        'à' => 'a',
        'â' => 'a',
        'ä' => 'a',
        'ô' => 'o',
        'ö' => 'o',
        'û' => 'u',
        'ü' => 'u',
        'ç' => 'c',
        'ñ' => 'n',
        "'" => '-',
        '"' => '-',
        ',' => '-',
        '.' => '-',
        '?' => '',
        '!' => '',
        ':' => '',
        ';' => '',
    ];

    $slug = str_replace(array_keys($specialChars), array_values($specialChars), $slug);
    $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');

    return $slug;
}
