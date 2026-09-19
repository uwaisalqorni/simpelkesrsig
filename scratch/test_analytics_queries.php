<?php
$c = new mysqli('localhost', 'root', 'bismillah', 'simpelkesrsig');

// 1. Monthly trend
$res1 = $c->query("
    SELECT 
        DATE_FORMAT(reported_at, '%Y-%m') as period_ym,
        DATE_FORMAT(reported_at, '%b %Y') as month_label,
        COUNT(*) as total_reported,
        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as total_closed,
        SUM(CASE WHEN priority = 'emergency' THEN 1 ELSE 0 END) as emergency_count
    FROM work_orders
    WHERE reported_at >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
    GROUP BY period_ym
    ORDER BY period_ym ASC
");
if (!$res1) {
    die("Query 1 failed: " . $c->error . "\n");
}
while ($r = $res1->fetch_assoc()) $trends[] = $r;
echo "Monthly trends count: " . count($trends) . "\n";
print_r($trends);

// 2. Top damaged rooms
$res2 = $c->query("
    SELECT 
        r.id as room_id,
        r.name as room_name,
        r.code as room_code,
        COUNT(wo.id) as ticket_count,
        COUNT(DISTINCT e.id) as affected_equipment_count,
        (SELECT COUNT(*) FROM medical_equipment WHERE room_id = r.id AND is_deleted = 0) as total_equipment
    FROM rooms r
    JOIN medical_equipment e ON e.room_id = r.id
    JOIN work_orders wo ON wo.equipment_id = e.id
    GROUP BY r.id
    ORDER BY ticket_count DESC
    LIMIT 5
");
$rooms = [];
while ($r = $res2->fetch_assoc()) $rooms[] = $r;
echo "Top damaged rooms count: " . count($rooms) . "\n";
print_r($rooms);

// 3. SPM compliance
$res3 = $c->query("
    SELECT 
        COUNT(*) as total_with_response,
        SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, reported_at, response_at) <= 15 THEN 1 ELSE 0 END) as fast_under_15m,
        SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, reported_at, response_at) > 15 AND TIMESTAMPDIFF(MINUTE, reported_at, response_at) <= 30 THEN 1 ELSE 0 END) as standard_15_30m,
        SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, reported_at, response_at) > 30 AND TIMESTAMPDIFF(MINUTE, reported_at, response_at) <= 60 THEN 1 ELSE 0 END) as moderate_30_60m,
        SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, reported_at, response_at) > 60 THEN 1 ELSE 0 END) as late_over_60m,
        ROUND(AVG(TIMESTAMPDIFF(MINUTE, reported_at, response_at)), 1) as avg_response_minutes
    FROM work_orders
    WHERE response_at IS NOT NULL
");
echo "SPM compliance:\n";
print_r($res3->fetch_assoc());
