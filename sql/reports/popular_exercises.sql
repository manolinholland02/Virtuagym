-- Goal: Top exercises per club (all-time). Add a date filter or month bucketing if needed.
WITH counts AS (
  SELECT
    ei.club_id,
    ei.exercise_id,
    COUNT(*) AS performed_count          -- how many times performed
  FROM ExerciseInstances ei
  WHERE ei.timestamp >= '2025-01-01'     -- filter to recent data (from this year) only
  GROUP BY ei.club_id, ei.exercise_id
)
SELECT
  c.club_id,
  e.exercise_id,
  e.name,
  c.performed_count,
  -- Rank exercises per club by popularity
  RANK() OVER (PARTITION BY c.club_id ORDER BY c.performed_count DESC) AS rnk_in_club
FROM counts c
JOIN Exercises e
  ON e.club_id = c.club_id
 AND e.exercise_id = c.exercise_id
ORDER BY c.club_id, rnk_in_club, e.name;
