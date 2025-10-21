<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
http_response_code(200);
echo json_encode([
  'status' => 'ok',
  'service' => 'virtuagym-export',
  'ts' => gmdate('c')
], JSON_UNESCAPED_SLASHES);
