<?php
declare(strict_types=1);

namespace App\Service;

final class ExerciseDbClient
{
    private string $baseUrl;
    private int $timeoutSeconds;

    public function __construct(string $baseUrl = 'https://exercisedb-api.vercel.app/api/v1/exercises', int $timeoutSeconds = 6)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeoutSeconds = $timeoutSeconds;
    }

    //Fetch one page with given offset and limit
    public function fetchPage(int $offset, int $limit): array
    {
        $url = $this->baseUrl . '?offset=' . urlencode((string)$offset) . '&limit=' . urlencode((string)$limit);
        // Fetch the JSON data from the API
        return $this->httpGetJson($url);
    }

    //Fetch one page with given offset and limit, with retry on failure
    public function fetchPageWithRetry(int $offset, int $limit): array
    {
        $r = $this->fetchPage($offset, $limit);
        if (!isset($r['success']) || $r['success'] !== true || !isset($r['data'])) {
            // one retry
            $r2 = $this->fetchPage($offset, $limit);
            return $r2;
        }
        return $r;
    }

    // Simple HTTP GET returning decoded JSON or error
    // Returns ['success'=>bool, 'metadata'=>array, 'data'=>array]
    private function httpGetJson(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $code < 200 || $code >= 300) {
            return ['success' => false, 'error' => $err ?: ('HTTP ' . $code)];
        }
        $json = json_decode($body, true);
        if (!is_array($json)) {
            return ['success' => false, 'error' => 'Invalid JSON'];
        }
        return $json;
    }
}
