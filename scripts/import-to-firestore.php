#!/usr/bin/env php
<?php

/**
 * CSV TO FIRESTORE IMPORTER - Personalities Collection
 * With Full Debug Logging
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
    // Mask the API key for security
    if ($key === 'FIREBASE_API_KEY' && strlen($value) > 10) {
        $displayValue = substr($value, 0, 10) . '...' . substr($value, -5);
    } else {
        $displayValue = $value;
    }
    writeLog("  $key = $displayValue");
}

// ========== GET CONFIGURATION FROM .ENV ==========
$PROJECT_ID = $env['FIREBASE_PROJECT_ID'] ?? null;
$CREDENTIALS_FILE = $env['FIREBASE_CREDENTIALS'] ?? null;
$CSV_FILE = $argv[1] ?? __DIR__ . '/../storage/app/innovators.csv';

// ========== VALIDATE PROJECT_ID ==========
writeLog("\n📋 Validating configuration...");

if (!$PROJECT_ID) {
    writeLog("❌ ERROR: FIREBASE_PROJECT_ID not set in .env file");
    writeLog("   Please add: FIREBASE_PROJECT_ID=your-project-id");
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
    writeLog("   Please ensure the file exists and the path is correct");
    die("❌ Credentials file not found\n");
}

writeLog("✅ Credentials file exists");

// ========== VALIDATE CSV_FILE ==========
writeLog("📄 CSV File: $CSV_FILE");

if (!file_exists($CSV_FILE)) {
    writeLog("❌ CSV file not found: $CSV_FILE");
    die("❌ CSV file not found: $CSV_FILE\n\nUsage: php import-to-firestore.php [path-to-csv]\n");
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

// Check if project IDs match
if (isset($creds['project_id']) && $creds['project_id'] !== $PROJECT_ID) {
    writeLog("⚠️  WARNING: Project ID in .env ($PROJECT_ID) doesn't match credentials file (" . $creds['project_id'] . ")");
    writeLog("   Using project ID from .env: $PROJECT_ID");
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
        die("❌ Failed to get access token. Check your credentials file.\n");
    }

    writeLog("✅ Access token obtained successfully (length: " . strlen($token) . " characters)");
    writeLog("   Token preview: " . substr($token, 0, 30) . "...");

    // Test the token with a simple API call
    writeLog("\n🧪 Testing token with Firestore API...");
    testToken($PROJECT_ID, $token);

    // Check if personalities collection exists
    writeLog("\n📊 Checking personalities collection...");
    checkExistingDocuments($PROJECT_ID, $token);

    // Import the CSV
    writeLog("\n📥 Starting CSV import...");
    importCSV($CSV_FILE, $PROJECT_ID, $token);

    writeLog("\n✅ Import completed successfully!");
    writeLog("=========================================");

} catch (Exception $e) {
    writeLog("❌ ERROR: " . $e->getMessage());
    writeLog("   Stack trace: " . $e->getTraceAsString());
    die("❌ Import failed: " . $e->getMessage() . "\n");
}

function getAccessToken($credsFile) {
    global $logFile;

    try {
        writeLog("   Reading credentials file...");
        $creds = json_decode(file_get_contents($credsFile), true);

        if (!$creds) {
            writeLog("   ❌ Invalid credentials file");
            return null;
        }

        writeLog("   ✅ Credentials loaded successfully");
        writeLog("   Client Email: " . ($creds['client_email'] ?? 'NOT FOUND'));

        // Create JWT
        writeLog("   Creating JWT...");
        $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $now = time();
        $payload = base64_encode(json_encode([
            'iss' => $creds['client_email'],
            'sub' => $creds['client_email'],
            'aud' => 'https://www.googleapis.com/oauth2/v4/token',
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

        $jwt = $header . '.' . $payload . '.' . base64_encode($signature);
        writeLog("   ✅ JWT signed successfully");

        // Exchange JWT for access token
        writeLog("   Exchanging JWT for access token...");
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

function checkExistingDocuments($projectId, $token) {
    $url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/personalities?pageSize=1";

    writeLog("   Checking URL: $url");

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
        writeLog("   ❌ CURL Error: $error");
        return;
    }

    writeLog("   HTTP Status Code: $httpCode");

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if (isset($data['documents']) && count($data['documents']) > 0) {
            writeLog("   ⚠️  Personalities collection already has " . count($data['documents']) . " document(s)");
            writeLog("   New documents will be added, existing ones will be updated if IDs match");
        } else {
            writeLog("   ✅ Personalities collection is empty or doesn't exist yet");
            writeLog("   Will create the collection with new documents");
        }
    } elseif ($httpCode === 403) {
        writeLog("   ❌ PERMISSION DENIED (403)");
        writeLog("   This means the service account doesn't have access to this project");
        writeLog("   Check that:");
        writeLog("     1. The project ID '$projectId' is correct");
        writeLog("     2. The service account has Firestore permissions");
        writeLog("     3. Firestore API is enabled in Google Cloud Console");
        writeLog("   Response: $response");
    } else {
        writeLog("   ❌ Unexpected response code: $httpCode");
        writeLog("   Response: $response");
    }
}

function importCSV($csvFile, $projectId, $token) {
    $file = fopen($csvFile, 'r');
    if (!$file) {
        throw new Exception("Could not open CSV file");
    }

    // Read header
    $header = fgetcsv($file);
    if (!$header) {
        fclose($file);
        throw new Exception("Empty CSV or no header");
    }

    writeLog("   CSV Header: " . implode(', ', $header));

    $total = 0;
    $updated = 0;
    $created = 0;
    $batch = [];
    $batchSize = 0;

    writeLog("   Starting to process rows...");

    while (($row = fgetcsv($file)) !== false) {
        if (count($row) !== count($header)) {
            writeLog("   ⚠️  Skipping row " . ($total + 1) . " - incorrect column count");
            continue;
        }

        $data = array_combine($header, $row);

        // Clean data
        $name = trim($data['name'] ?? '');
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
        $docId = generateSlug($name);

        // Prepare document data
        $docData = [
            'fields' => [
                'name' => ['stringValue' => $name],
                'occupation' => ['stringValue' => $occupation],
                'bio' => ['stringValue' => $bio],
                'achievements' => ['arrayValue' => ['values' => array_map(function($a) {
                    return ['stringValue' => $a];
                }, $achievementsArray)]],
                'image' => ['stringValue' => $image],
                'id' => ['stringValue' => $docId],
                'createdAt' => ['timestampValue' => date('c')],
                'updatedAt' => ['timestampValue' => date('c')],
            ]
        ];

        // Check if document exists
        $exists = documentExists($docId, $projectId, $token);

        $batch[$docId] = $docData;
        $batchSize++;
        $total++;

        if ($exists) {
            $updated++;
        } else {
            $created++;
        }

        if ($batchSize >= 500) {
            commitBatch($batch, $projectId, $token);
            $batch = [];
            $batchSize = 0;
            writeLog("   ✅ Processed $total records (Created: $created, Updated: $updated)");
        }
    }

    // Commit remaining
    if ($batchSize > 0) {
        commitBatch($batch, $projectId, $token);
    }

    fclose($file);

    writeLog("\n📊 Import Summary:");
    writeLog("  - Total processed: $total");
    writeLog("  - New documents created: $created");
    writeLog("  - Existing documents updated: $updated");
}

function documentExists($docId, $projectId, $token) {
    $url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/personalities/$docId";

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

    return $httpCode === 200;
}

function commitBatch($batch, $projectId, $token) {
    if (empty($batch)) return;

    $writes = [];
    foreach ($batch as $docId => $docData) {
        $writes[] = [
            'update' => [
                'name' => "projects/$projectId/databases/(default)/documents/personalities/$docId",
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
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-');
}
