-- Goal: visits per club per calendar month (local time), plus distinct visitors and an average.
SELECT
  ci.club_id,
  -- Bucket visits by calendar months in local time
  DATE_FORMAT(CONVERT_TZ(ci.timestamp,'UTC','Europe/Amsterdam'), '%Y-%m-01') AS month_start,
  COUNT(*)                             AS total_visits,           -- how busy the club was
  COUNT(DISTINCT ci.member_id)         AS unique_visitors,        -- breadth of engagement
  ROUND(COUNT(*) / NULLIF(COUNT(DISTINCT ci.member_id), 0), 2)
                                       AS avg_visits_per_member   -- intensity per active member
FROM CheckIns ci
WHERE ci.timestamp >= '2025-01-01'      -- filter to recent data (from this year) only
GROUP BY ci.club_id, month_start
ORDER BY ci.club_id, month_start;
