<?php
declare(strict_types=1);

use App\Service\ExerciseDbClient;

require __DIR__ . '/../vendor/autoload.php';

date_default_timezone_set('Europe/Amsterdam');

$DATA_DIR = __DIR__ . '/../data';
@mkdir($DATA_DIR, 0775, true);

// Load clubs (needed to assign club_id)
$clubsPath = $DATA_DIR . '/clubs.json';
if (!is_file($clubsPath)) {
    // Clubs data is missing
    fwrite(STDERR, "Missing $clubsPath. Run create_mock_data.php first.\n");
    exit(1);
}
$clubs = json_decode(file_get_contents($clubsPath) ?: '[]', true);
if (!is_array($clubs) || count($clubs) < 1) {
    // No clubs found
    fwrite(STDERR, "No clubs found in $clubsPath\n");
    exit(1);
}
$clubIds = array_values(array_map(fn($c) => $c['club_id'], $clubs));

$client = new ExerciseDbClient();

$target = 200;
// fetch 50 per page
$pageLimit = 50;
$offset = 0;
// exerciseId → true
$seen   = [];
// collected items              
$items  = [];              

while (count($items) < $target) {
    $resp = $client->fetchPageWithRetry($offset, $pageLimit);

    if (!isset($resp['success']) || $resp['success'] !== true || !isset($resp['data']) || !is_array($resp['data'])) {
        // Try fallback to existing cache if it has >= 200
        $cachePath = $DATA_DIR . '/exercises.json';
        if (is_file($cachePath)) {
            $cached = json_decode(file_get_contents($cachePath) ?: '[]', true);
            if (is_array($cached) && count($cached) >= $target) {
                fwrite(STDERR, "API fetch failed; using cached exercises.json\n");
                echo "Exercises ready from cache: " . count($cached) . "\n";
                exit(0);
            }
        }
        fwrite(STDERR, "Failed to fetch ExerciseDB: " . ($resp['error'] ?? 'unknown error') . "\n");
        exit(1);
    }

    foreach ($resp['data'] as $row) {
        $eid = $row['exerciseId'] ?? null;
        if (!$eid || isset($seen[$eid])) continue;
        $seen[$eid] = true;
        $items[] = $row;
        if (count($items) >= $target) break;
    }

    // Advance offset; stop if no progress
    if (empty($resp['data'])) break;
    $offset += $pageLimit;
}

// Map to our structure and assign club_id in round-robin
$mapped = [];
for ($i = 0; $i < count($items) && $i < $target; $i++) {
    $row = $items[$i];
    $clubId = $clubIds[$i % count($clubIds)];

    $mapped[] = [
        'exercise_id'    => (string)($row['exerciseId'] ?? ''),
        'club_id'        => $clubId,
        'name'           => (string)($row['name'] ?? ''),
        'target_muscles' => array_values(array_map('strval', $row['targetMuscles'] ?? [])),
    ];
}

// Write to data/exercises.json
$outPath = $DATA_DIR . '/exercises.json';
file_put_contents($outPath, json_encode($mapped, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));

echo "Saved " . count($mapped) . " exercises to data/exercises.json\n";
