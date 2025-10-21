<?php
declare(strict_types=1);

date_default_timezone_set('Europe/Amsterdam');

$DATA_DIR = __DIR__ . '/../data';
@mkdir($DATA_DIR, 0775, true);

// Load data
$clubs     = json_decode(file_get_contents($DATA_DIR . '/clubs.json') ?: '[]', true);
$members   = json_decode(file_get_contents($DATA_DIR . '/members.json') ?: '[]', true);
$exercises = json_decode(file_get_contents($DATA_DIR . '/exercises.json') ?: '[]', true);

if (!is_array($clubs) || !is_array($members) || !is_array($exercises) || count($exercises) < 1) {
    fwrite(STDERR, "Missing or empty data. Ensure create_mock_data and create_exercises_from_api have run.\n");
    exit(1);
}

// Index by club
$membersByClub = [];
foreach ($members as $m) {
    $membersByClub[$m['club_id']][] = $m;
}
$exerciseByClub = [];
foreach ($exercises as $e) {
    $exerciseByClub[$e['club_id']][] = $e;
}

// Compute weights for clubs based on member count
$weights = [];
$totalMembers = 0;
foreach ($clubs as $c) {
    $cnt = isset($membersByClub[$c['club_id']]) ? count($membersByClub[$c['club_id']]) : 0;
    $weights[$c['club_id']] = max(1, $cnt);
    $totalMembers += $weights[$c['club_id']];
}

function uuidv4(): string {
    $d = random_bytes(16);
    $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
    $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
    $h = bin2hex($d);
    return sprintf('%s-%s-%s-%s-%s', substr($h,0,8), substr($h,8,4), substr($h,12,4), substr($h,16,4), substr($h,20,12));
}
function pick(array $a) { return $a[array_rand($a)]; }
function weighted_gym_hour(): int {
    $bag = [];
    foreach (range(6,9) as $h)  $bag = array_merge($bag, array_fill(0,2,$h));
    foreach (range(10,16) as $h) $bag = array_merge($bag, array_fill(0,1,$h));
    foreach (range(17,21) as $h) $bag = array_merge($bag, array_fill(0,3,$h));
    foreach (range(22,23) as $h) $bag = array_merge($bag, array_fill(0,1,$h));
    return pick($bag);
}
function to_utc_iso(\DateTimeImmutable $local): string {
    return $local->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
}

// Target is 1000 instances total
$TARGET = 1000;
$out = [];
$nowLocal = new DateTimeImmutable('now', new DateTimeZone('Europe/Amsterdam'));

for ($i = 0; $i < $TARGET; $i++) {
    // Select club based on weights
    $r = random_int(1, max(1,$totalMembers));
    $acc = 0;
    $clubId = array_keys($weights)[0];
    foreach ($weights as $cid => $w) {
        $acc += $w;
        if ($r <= $acc) { $clubId = $cid; break; }
    }

    $mems = $membersByClub[$clubId] ?? [];
    $exs  = $exerciseByClub[$clubId] ?? [];
    if (!$mems || !$exs) { $i--; continue; } // skip if no members or exercises

    $m  = pick($mems);
    $ex = pick($exs);

    // Random date within last 90 days
    $daysAgo = random_int(0, 89);
    $local   = $nowLocal->sub(new DateInterval("P{$daysAgo}D"))->setTime(weighted_gym_hour(), random_int(0,59), random_int(0,59));

    $out[] = [
        'instance_id'  => uuidv4(),
        'exercise_id'  => $ex['exercise_id'],
        'club_id'      => $clubId,
        'member_id'    => $m['member_id'],
        'performed_at' => to_utc_iso($local),
    ];
}

// Sort by performed_at
usort($out, fn($a,$b) => strcmp($a['performed_at'], $b['performed_at']));

// Write to data/exercise_instances.json
file_put_contents($DATA_DIR . '/exercise_instances.json', json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
echo "Saved " . count($out) . " exercise_instances to data/exercise_instances.json\n";
