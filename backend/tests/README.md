# Virtuagym — Testing Plan (Task 7)

This document explains **how** I would tackle **Task 7** of the technical assignment (design tests).

## Endpoints to be tested

GET /api/exercises?offset=<n>&limit=<m>

### Happy path
- Should return status `200 OK`
- Response JSON has attributes: `items (≤ 10)`, `total`, `offset`, `limit`
- Each `item` has contains the following fields: `exercise_id`, `club_id`, `name`, `target_muscles` (JSON array)

### Pagination behavior
- If Page 1 has parameters `offset=0&limit=5`and Page 2 has parameters `offset=5&limit=5`, no overlap should be made between pages

### Parameter validation
- If `limit` is clamped between 2 values (e.g. `1 and 100`), `limit=0` behaves as 1, while `limit=1000` behaves as 100.
- Negative `offset` (e.g. `offset=-25` is treated as `0`).
- If `offset` > `total`, returns empty `items` array but still status `200 OK`.

### No data available
- If `data/exercises.json` is missing, return status `200 OK` with empty list (because the operation has been executed nonetheless)

GET /api/export?type=activity&format=<json|csv>

### Happy path (JSON)
- Should return status `200 OK`

### Happy path (CSV)
- Sends 2 headers: `Content-Type: text/csv` and `Content-Disposition: attachment; filename="activity.csv"`
- Row count matches the rows count of `Happy path (JSON)`

### Bad requests
- Missing `type` returns status `400 Bad Request`
- if `type=unknown`, return status `400 Bad Request`
- if format is different than `json` or `csv` (e.g. `format=xml`), return status `400 Bad Request`

GET /api/export?type=popular-exercises&format=<json|csv>

### Happy path (JSON)
- Should return status `200 OK`
- Each row has `club_id`, `rank (1..10)`, `exercise_id`, `name`, `usage_count`
- For each club there is maximum of 10 rows, sorted by `usage_count` desc, and `ranks` are contiguous

### Happy path (CSV)
- Sends 2 headers: `Content-Type: text/csv` and `Content-Disposition: attachment; filename="popular-exercises.csv"`
- Each row has `club_id`, `rank (1..10)`, `exercise_id`, `name`, `usage_count`
- Row count matches the rows count of `Happy path (JSON)`

### Join logic
- If an instance references a non-existing `exercise_id`, the row is ignored (no crash)

## If authentication were required

If endpoints were to require authentication token or an API key:

### Request call
- If header missing or token is invalid, return status `401 Unauthorized`
- If scopes existed and token is not in scope, return status `403 Forbidden`

### Possible test
- No header returns status `401 Unauthorized`
- Wrong token returns status `401 Unauthorized` or `403 Forbidden`
- Correct token returns status `200 Ok`
- CSV endpoints still set the correct CSV headers when authorized