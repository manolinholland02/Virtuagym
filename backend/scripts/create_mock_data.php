<?php
declare(strict_types=1);

/**
 * Build minimal fixtures for:
 *  - data/clubs.json
 *  - data/members.json
 *  - data/checkins.json
 */

date_default_timezone_set('Europe/Amsterdam');

$DATA_DIR = __DIR__ . '/../data';
if (!is_dir($DATA_DIR)) {
    mkdir($DATA_DIR, 0775, true);
}

$nowLocal = new DateTimeImmutable('now', new DateTimeZone('Europe/Amsterdam'));

/** --- helpers --- */
function uuidv4(): string {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); 
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); 
    $hex = bin2hex($data);
    return sprintf('%s-%s-%s-%s-%s',
        substr($hex, 0, 8), substr($hex, 8, 4),
        substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20, 12)
    );
}
function rand_int(int $min, int $max): int { return random_int($min, $max); }
function pick(array $arr) { return $arr[array_rand($arr)]; }
function weighted_gym_hour(): int {
    // Some popular gym session hours: Morning 06–09 (weight 2), Noon 10–16 (1), Evening 17–21 (3), Late 22–23 (0.5)
    $bucket = [];
    foreach (range(6,9) as $h)  for ($i=0;$i<2;$i++) $bucket[]=$h;
    foreach (range(10,16) as $h) for ($i=0;$i<1;$i++) $bucket[]=$h;
    foreach (range(17,21) as $h) for ($i=0;$i<3;$i++) $bucket[]=$h;
    foreach (range(22,23) as $h) for ($i=0;$i<1;$i++) $bucket[]=$h; // light
    return pick($bucket);
}
function to_utc_iso(DateTimeImmutable $local): string {
    return $local->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
}

$clubs = [
    ['club_id' => uuidv4(), 'club_name' => 'Canal Fitness',   'city' => 'Amsterdam'],
    ['club_id' => uuidv4(), 'club_name' => 'Harbor Strength', 'city' => 'Rotterdam'],
    ['club_id' => uuidv4(), 'club_name' => 'Utrecht Lift Lab','city' => 'Utrecht'],
];

$first = ['Alex','Jordan','Taylor','Morgan','Sam','Casey','Jamie','Avery','Chris','Drew','Riley','Quinn','Rowan','Hayden','Skyler','Logan','Parker','Reese','Elliot','Finley'];
$last  = ['Jansen','de Vries','van Dijk','Bakker','Visser','Smit','Meijer','de Boer','Mulder','Bos','Vos','Peters','Kok','Hendriks','van Leeuwen','Dekker','Brouwer','de Groot','Schouten','Koster'];

$members = [];
$targetMembers = 80;
for ($i = 0; $i < $targetMembers; $i++) {
    $club = $clubs[array_rand($clubs)];
    $name = pick($first) . ' ' . pick($last);

    // birthday: ranged between 18 and 65
    $ageYears = rand_int(18, 65);
    $birthday = $nowLocal
        ->sub(new DateInterval('P' . $ageYears . 'Y'))
        ->sub(new DateInterval('P' . rand_int(0, 364) . 'D'))
        ->format('Y-m-d');

    // start_date: within last 3 years
    $startMin = $nowLocal->sub(new DateInterval('P3Y'))->getTimestamp();
    $startMax = $nowLocal->getTimestamp();
    $startTs  = rand_int($startMin, $startMax);
    $start_date = (new DateTimeImmutable('@'.$startTs))->setTimezone(new DateTimeZone('Europe/Amsterdam'))->format('Y-m-d');

    // some 25% to 30% inactive members
    $active_status = (rand_int(1,100) <= 28) ? 0 : 1;

    $members[] = [
        'member_id'     => uuidv4(),
        'club_id'       => $club['club_id'],
        'name'          => $name,
        'birthday'      => $birthday,
        'start_date'    => $start_date,
        'active_status' => $active_status
    ];
}

$checkins = [];
foreach ($members as $m) {
    $isActive = $m['active_status'] === 1;
    // Active members come more often
    $n = $isActive ? rand_int(10, 35) : rand_int(0, 6);

    for ($k = 0; $k < $n; $k++) {
        $daysAgo = rand_int(0, 89); // range within last 90 days, including today
        $hour    = weighted_gym_hour();
        $minute  = rand_int(0, 59);
        $second  = rand_int(0, 59);

        $local = $nowLocal->sub(new DateInterval("P{$daysAgo}D"))->setTime($hour, $minute, $second);
        $checkins[] = [
            'checkin_id' => uuidv4(),
            'member_id'  => $m['member_id'],
            'club_id'    => $m['club_id'],
            'timestamp'  => to_utc_iso($local)
        ];
    }
}

// Sort by time for nicer diffs
usort($checkins, fn($a,$b) => strcmp($a['timestamp'], $b['timestamp']));

// Writing the actual files
file_put_contents($DATA_DIR . '/clubs.json', json_encode($clubs, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
file_put_contents($DATA_DIR . '/members.json', json_encode($members, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
file_put_contents($DATA_DIR . '/checkins.json', json_encode($checkins, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

echo "Mock data created:\n";
echo "  clubs:     " . count($clubs)    . " -> data/clubs.json\n";
echo "  members:   " . count($members)  . " -> data/members.json\n";
echo "  checkins:  " . count($checkins) . " -> data/checkins.json\n";
