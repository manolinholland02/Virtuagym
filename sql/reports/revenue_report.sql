-- Goal: Active monthly subscription revenue per club on a specific date (snapshot).
-- :param as_of DATE - the date to evaluate active subscriptions, e.g., '2025-10-20'
SELECT
  m.club_id,
  SUM(s.price) AS active_monthly_revenue     -- total monthly value attributable to the club
FROM Subscriptions s
JOIN Members m
  ON m.member_id = s.member_id               -- map subscription to a club via the member
WHERE s.start_date <= :as_of
  AND (s.end_date IS NULL OR s.end_date >= :as_of)  -- active on the :as_of date
GROUP BY m.club_id
ORDER BY m.club_id;