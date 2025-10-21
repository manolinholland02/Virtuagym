<?php
declare(strict_types=1);

namespace App\Repository;

final class MockDataRepository
{
    private string $dataDir;

    public function __construct(string $dataDir)
    {
        $this->dataDir = rtrim($dataDir, '/');
    }

    /** @return array<int,array<string,mixed>> */
    public function clubs(): array { return $this->read('clubs.json'); }
    /** @return array<int,array<string,mixed>> */
    public function members(): array { return $this->read('members.json'); }
    /** @return array<int,array<string,mixed>> */
    public function checkins(): array { return $this->read('checkins.json'); }
    /** @return array<int,array<string,mixed>> */
    public function exercises(): array { return $this->read('exercises.json'); }
    /** @return array<int,array<string,mixed>> */
    public function exerciseInstances(): array { return $this->read('exercise_instances.json'); }

    /** @return array<int,array<string,mixed>> */
    private function read(string $file): array
    {
        $path = $this->dataDir . '/' . $file;
        if (!is_file($path)) return [];
        $raw = file_get_contents($path);
        if ($raw === false) return [];
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}
