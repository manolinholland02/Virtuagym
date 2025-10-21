<?php
declare(strict_types=1);

namespace App\Service;

use DateTimeImmutable;
use DateTimeZone;

final class ReportsService
{
    private DateTimeZone $localTz;

    public function __construct(string $localTz = 'Europe/Amsterdam')
    {
        $this->localTz = new DateTimeZone($localTz);
    }

    /**
     * activity → member check-ins grouped per month (YYYY-MM) and per club
     * @param array<int,array<string,mixed>> $checkins
     * @return array<int,array{club_id:string,month:string,checkins:int}>
     */
    public function activity(array $checkins): array
    {
        $counts = [];
        foreach ($checkins as $ci) {
            $club = (string)($ci['club_id'] ?? '');
            $ts   = (string)($ci['timestamp'] ?? '');
            if ($club === '' || $ts === '') continue;

            $utc = DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s\Z', $ts, new DateTimeZone('UTC'));
            if (!$utc) continue;

            $local = $utc->setTimezone($this->localTz);
            $month = $local->format('Y-m');

            $key = $club . '|' . $month;
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        $rows = [];
        foreach ($counts as $key => $count) {
            [$club, $month] = explode('|', $key, 2);
            $rows[] = ['club_id' => $club, 'month' => $month, 'checkins' => $count];
        }

        usort($rows, fn($a, $b) => ($a['club_id'] <=> $b['club_id']) ?: strcmp($a['month'], $b['month']));
        return $rows;
    }

    /**
     * popular-exercises → top 10 exercises per club by usage
     * @param array<int,array<string,mixed>> $instances
     * @param array<int,array<string,mixed>> $exercises
     * @return array<int,array{club_id:string,rank:int,exercise_id:string,name:string,usage_count:int}>
     */
    public function popularExercises(array $instances, array $exercises): array
    {
        // Maps exercise_id with name
        $nameById = [];
        foreach ($exercises as $e) {
            $id = (string)($e['exercise_id'] ?? '');
            if ($id !== '') $nameById[$id] = (string)($e['name'] ?? '');
        }

        // Count usages per club and exercise
        $counts = [];
        foreach ($instances as $ins) {
            $club = (string)($ins['club_id'] ?? '');
            $eid  = (string)($ins['exercise_id'] ?? '');
            if ($club === '' || $eid === '' || !isset($nameById[$eid])) continue;
            $counts[$club][$eid] = ($counts[$club][$eid] ?? 0) + 1;
        }

        $rows = [];
        foreach ($counts as $club => $byEx) {
            $list = [];
            foreach ($byEx as $eid => $cnt) {
                $list[] = ['exercise_id' => $eid, 'name' => $nameById[$eid] ?? '', 'usage_count' => $cnt];
            }
            usort($list, function ($a, $b) {
                return ($b['usage_count'] <=> $a['usage_count']) ?: strcmp($a['name'], $b['name']);
            });

            $rank = 1;
            foreach (array_slice($list, 0, 10) as $row) {
                $rows[] = [
                    'club_id'      => $club,
                    'rank'         => $rank++,
                    'exercise_id'  => $row['exercise_id'],
                    'name'         => $row['name'],
                    'usage_count'  => $row['usage_count'],
                ];
            }
        }

        usort($rows, fn($a, $b) => ($a['club_id'] <=> $b['club_id']) ?: ($a['rank'] <=> $b['rank']));
        return $rows;
    }
}
