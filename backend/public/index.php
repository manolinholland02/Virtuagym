<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Repository\MockDataRepository;
use App\Service\ReportsService;
use App\Utilities\CsvEmitter;

// Parse request
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$query  = $_GET ?? [];

// Initialize repository and service
$repo    = new MockDataRepository(__DIR__ . '/../data');
$reports = new ReportsService('Europe/Amsterdam');

// JSON output helper
function jsonOut(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
}

if ($path === '/') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Virtuagym Export Service is running.\nTry /health, /api/exercises or /api/export";
    exit;
}

// GET /api/exercises?offset=<n>&limit=<m>
if ($method === 'GET' && $path === '/api/exercises') {
    $offset = isset($query['offset']) ? max(0, (int)$query['offset']) : 0;
    $limit  = isset($query['limit'])  ? max(1, min(100, (int)$query['limit'])) : 50;

    $all    = $repo->exercises();
    $total  = count($all);
    $items  = array_slice($all, $offset, $limit);

    // body_parts, equipments and secondary_muscles are not included in this output, because Step 5 "Integrate ExerciseDB" asked to store only exercise_id, club_id, name, and target_muscles for Exercises.
    jsonOut([
        'items'  => array_map(function ($e) {
            return [
                'exercise_id'    => (string)($e['exercise_id'] ?? ''),
                'club_id'        => (string)($e['club_id'] ?? ''),
                'name'           => (string)($e['name'] ?? ''),
                'target_muscles' => array_values(array_map('strval', $e['target_muscles'] ?? [])),
            ];
        }, $items),
        'total'  => $total,
        'offset' => $offset,
        'limit'  => $limit
    ]);
    exit;
}

// GET /api/export?type=<activity|popular-exercises>&format=<json|csv>
if ($method === 'GET' && $path === '/api/export') {
    $type   = isset($query['type']) ? (string)$query['type'] : '';
    $format = isset($query['format']) ? (string)$query['format'] : 'json';

    // Validate type and format
    if ($type !== 'activity' && $type !== 'popular-exercises') {
        jsonOut(['error' => 'Invalid type. Use activity or popular-exercises'], 400);
        exit;
    }
    if ($format !== 'json' && $format !== 'csv') {
        jsonOut(['error' => 'Invalid format. Use json or csv'], 400);
        exit;
    }

    if ($type === 'activity') {
        $rows = $reports->activity($repo->checkins());

        if ($format === 'json') {
            jsonOut(['type' => 'activity', 'rows' => $rows, 'generated_at' => gmdate('c')]);
        } else {
            CsvEmitter::send('activity.csv', $rows, ['club_id','month','checkins']);
        }
        exit;
    }

    if ($type === 'popular-exercises') {
        $rows = $reports->popularExercises($repo->exerciseInstances(), $repo->exercises());

        if ($format === 'json') {
            jsonOut(['type' => 'popular-exercises', 'rows' => $rows, 'generated_at' => gmdate('c')]);
        } else {
            CsvEmitter::send('popular-exercises.csv', $rows, ['club_id','rank','exercise_id','name','usage_count']);
        }
        exit;
    }
}

// Fallback 404
jsonOut(['error' => 'Not Found', 'path' => $path], 404);
