<?php
// ============================================================
// Import WordPress posts into Firestore 'articles' collection
// - Preserves existing docs where slug matches
// - Adds new docs for new slugs
// - Uses updateMask to only touch content/title/image fields
// ============================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

$DRY_RUN = in_array('--dry-run', $argv);

function log_($m) {
    echo "[" . date('H:i:s') . "] $m\n";
}

function loadEnv($p) {
    if (!file_exists($p)) return [];
    $e = [];
    foreach (file($p, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
        if (strpos(trim($l), '#') === 0 || strpos($l, '=') === false) continue;
        [$k, $v] = explode('=', $l, 2);
        if (preg_match('/^["\'](.*)["\']$/', trim($v), $m)) $v = $m[1];
        $e[trim($k)] = trim($v);
    }
    return $e;
}

$env = loadEnv(__DIR__ . '/../.env');
$PROJECT_ID       = $env['FIREBASE_PROJECT_ID'] ?? null;
$CREDENTIALS_FILE = $env['FIREBASE_CREDENTIALS'] ?? null;
$JSON_FILE        = __DIR__ . '/../storage/app/wp-posts.json';
$COLLECTION       = 'articles';

if (!$PROJECT_ID || !$CREDENTIALS_FILE) die("❌ Missing env vars\n");
if (!file_exists($CREDENTIALS_FILE)) die("❌ Credentials missing\n");
if (!file_exists($JSON_FILE)) die("❌ wp-posts.json missing\n");

// ---------- Auth ----------
function b64u($d){ return rtrim(strtr(base64_encode($d), '+/', '-_'), '='); }
$c = json_decode(file_get_contents($CREDENTIALS_FILE), true);
$h = b64u(json_encode(['alg'=>'RS256','typ'=>'JWT']));
$n = time();
$p = b64u(json_encode([
  'iss'=>$c['client_email'], 'sub'=>$c['client_email'],
  'scope'=>'https://www.googleapis.com/auth/datastore',
  'aud'=>'https://oauth2.googleapis.com/token',
  'iat'=>$n, 'exp'=>$n+3600,
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
$token = json_decode(curl_exec($ch), true)['access_token']; curl_close($ch);
if (!$token) die("❌ No token\n");
log_(($DRY_RUN ? "🧪 DRY RUN" : "🚀 LIVE") . " | Collection: $COLLECTION");

// ---------- Load WordPress posts ----------
$posts = json_decode(file_get_contents($JSON_FILE), true);
if (!is_array($posts)) die("❌ Invalid JSON\n");
log_("📄 Loaded " . count($posts) . " WordPress posts\n");

// ---------- Helpers ----------
function decodeEntity($s) {
    return html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function extractCategories($post) {
    $cats = [];
    foreach ($post['_embedded']['wp:term'][0] ?? [] as $t) {
        if (($t['taxonomy'] ?? '') === 'category') {
            $cats[] = $t['name'];
        }
    }
    return $cats;
}

function extractImage($post) {
    // Prefer OG image (largest/cleaned)
    if (!empty($post['yoast_head_json']['og_image'][0]['url'])) {
        return $post['yoast_head_json']['og_image'][0]['url'];
    }
    // Fall back to featured media
    return $post['_embedded']['wp:featuredmedia'][0]['source_url'] ?? '';
}

// ---------- Fetch existing Firestore docs ----------
$url = "https://firestore.googleapis.com/v1/projects/$PROJECT_ID/databases/(default)/documents/$COLLECTION";
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER=>true,
    CURLOPT_HTTPHEADER=>["Authorization: Bearer $token"],
]);
$res = curl_exec($ch); curl_close($ch);
$existing = json_decode($res, true)['documents'] ?? [];
log_("📚 Existing Firestore docs: " . count($existing) . "\n");

// Build map: slug -> existing doc name
$existingBySlug = [];
foreach ($existing as $doc) {
    $slug = $doc['fields']['slug']['stringValue']
         ?? $doc['fields']['id']['stringValue']
         ?? null;
    if ($slug) $existingBySlug[$slug] = $doc['name'];
}

// ---------- Build writes ----------
$writes = [];
$stats = ['added' => 0, 'updated' => 0];

foreach ($posts as $post) {
    $title    = decodeEntity($post['title']['rendered'] ?? '');
    $slug     = $post['slug'];
    $content  = $post['content']['rendered'] ?? '';
    $excerpt  = $post['excerpt']['rendered'] ?? '';
    $image    = extractImage($post);
    $cats     = extractCategories($post);
    $datePub  = $post['date_gmt'] ?? $post['date'];
    $dateMod  = $post['modified_gmt'] ?? $post['modified'];
    $author   = $post['yoast_head_json']['author'] ?? 'Raindolf Owusu';
    $readTime = $post['yoast_head_json']['twitter_misc']['Est. reading time'] ?? '';
    $wordCount= $post['yoast_head_json']['schema']['@graph'][0]['wordCount'] ?? 0;
    $link     = $post['link'] ?? '';

    $isUpdate = isset($existingBySlug[$slug]);
    $docName  = $isUpdate
        ? $existingBySlug[$slug]
        : "projects/$PROJECT_ID/databases/(default)/documents/$COLLECTION/wp_" . $post['id'];

    log_(($isUpdate ? "🔁 UPDATE" : "➕ ADD") . "  $slug");
    log_("       title:  " . substr($title, 0, 60));

    if ($DRY_RUN) continue;

    // Fields to write (only these — updateMask enforces it)
    $fields = [
        'title'         => ['stringValue' => $title],
        'slug'          => ['stringValue' => $slug],
        'content'       => ['stringValue' => $content],
        'excerpt'       => ['stringValue' => $excerpt],
        'image'         => ['stringValue' => $image],
        'author'        => ['stringValue' => $author],
        'category'      => ['stringValue' => $cats[0] ?? ''],
        'categories'    => ['arrayValue' => ['values' => array_map(
            fn($c) => ['stringValue' => $c], $cats)]],
        'link'          => ['stringValue' => $link],
        'readingTime'   => ['stringValue' => $readTime],
        'wordCount'     => ['integerValue' => (int)$wordCount],
        'wpId'          => ['integerValue' => (int)$post['id']],
        'publishedAt'   => ['timestampValue' => date('c', strtotime($datePub . ' UTC'))],
        'source'        => ['stringValue' => 'blackorbitfoundation.org'],
        'importedAt'    => ['timestampValue' => date('c')],
    ];

    // Only set createdAt/viewcount/updatedAt on NEW docs
    if (!$isUpdate) {
        $fields['createdAt'] = ['timestampValue' => date('c', strtotime($datePub . ' UTC'))];
        $fields['updatedAt'] = ['timestampValue' => date('c', strtotime($dateMod . ' UTC'))];
        $fields['viewcount'] = ['integerValue' => 0];
    } else {
        $fields['updatedAt'] = ['timestampValue' => date('c')];
    }

    $writes[] = [
        'update' => [
            'name'   => $docName,
            'fields' => $fields,
        ],
        'updateMask' => ['fieldPaths' => array_keys($fields)],
    ];

    $stats[$isUpdate ? 'updated' : 'added']++;
}

if ($DRY_RUN) {
    log_("\n🧪 Dry run — nothing written");
    exit;
}
if (empty($writes)) { log_("✅ Nothing to write"); exit; }

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

log_("\n✅ Added:   " . $stats['added']);
log_("✅ Updated: " . $stats['updated']);
