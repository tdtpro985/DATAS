<?php
/* ============================================================
   GET /api/v1/users/sr-performance-detail?sr_id=X
   Returns per-project tracking timestamps for one SR.
   ============================================================ */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

requireRole(['superadmin', 'admin', 'sales_rep']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

$srId = (int)($_GET['sr_id'] ?? 0);
if (!$srId) jsonError('sr_id is required', 400);

try {
    $db = getDB();

    // Check if timestamp cols exist
    $hasTs = (bool)$db->query("SHOW COLUMNS FROM sales_tracking LIKE 'contacted_at'")->fetch();

    $tsSelect = $hasTs ? "
        st.assigned_at,
        st.contacted_at,
        st.sales_qualified_at,
        st.quoted_at,
        st.to_win_at,
        CASE WHEN st.assigned_at IS NOT NULL AND st.to_win_at IS NOT NULL
             THEN TIMESTAMPDIFF(SECOND, st.assigned_at, st.to_win_at)
        END AS full_cycle_seconds
    " : "
        NULL AS assigned_at,
        NULL AS contacted_at,
        NULL AS sales_qualified_at,
        NULL AS quoted_at,
        NULL AS to_win_at,
        NULL AS full_cycle_seconds
    ";

    $stmt = $db->prepare("
        SELECT
            p.id            AS project_id,
            p.project_name,
            p.contractor_name,
            p.project_value,
            st.contacted,
            st.sales_qualified,
            st.quoted,
            st.to_win,
            st.wa_amount,
            st.tracking_status,
            st.created_at   AS tracking_created,
            st.updated_at   AS tracking_updated,
            $tsSelect
        FROM sales_tracking st
        INNER JOIN projects p ON st.project_id = p.id
        WHERE st.sales_rep_id = :sr_id
          AND p.archived_at IS NULL
        ORDER BY st.updated_at DESC
        LIMIT 100
    ");
    $stmt->execute([':sr_id' => $srId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Compute avg full cycle from completed rows (assigned → win)
    $cycles = array_filter($rows, fn($r) => $r['full_cycle_seconds'] !== null);
    $avgFullCycleSec = count($cycles) > 0
        ? round(array_sum(array_column($cycles, 'full_cycle_seconds')) / count($cycles))
        : null;

    // Compute per-stage averages in seconds
    function avgSecondsCol(array $rows, string $fromCol, string $toCol): ?int {
        $vals = [];
        foreach ($rows as $r) {
            if (!empty($r[$fromCol]) && !empty($r[$toCol])) {
                $diff = strtotime($r[$toCol]) - strtotime($r[$fromCol]);
                if ($diff >= 0) $vals[] = $diff;
            }
        }
        return count($vals) > 0 ? (int)round(array_sum($vals) / count($vals)) : null;
    }

    $avgAssignToContact   = avgSecondsCol($rows, 'assigned_at', 'contacted_at');
    $avgContactToQuote    = avgSecondsCol($rows, 'contacted_at', 'quoted_at');
    $avgQuoteToSql        = avgSecondsCol($rows, 'quoted_at', 'sales_qualified_at');
    $avgSqlToWin          = avgSecondsCol($rows, 'sales_qualified_at', 'to_win_at');

    // Fallback: avg assigned → last updated
    $avgProcessingSec = null;
    if ($hasTs) {
        $procVals = [];
        foreach ($rows as $r) {
            if (!empty($r['assigned_at']) && !empty($r['tracking_updated'])) {
                $diff = strtotime($r['tracking_updated']) - strtotime($r['assigned_at']);
                if ($diff >= 0) $procVals[] = $diff;
            }
        }
        if (count($procVals) > 0) {
            $avgProcessingSec = (int)round(array_sum($procVals) / count($procVals));
        }
    }

    // Total for percentage calculation (use processing as fallback for full cycle)
    $totalSec = $avgFullCycleSec ?? $avgProcessingSec;

    $projects = array_map(function ($r) {
        return [
            'project_id'       => (int) $r['project_id'],
            'project_name'     => $r['project_name'],
            'contractor_name'  => $r['contractor_name'],
            'project_value'    => (float) $r['project_value'],
            'contacted'        => $r['contacted'],
            'sales_qualified'  => $r['sales_qualified'],
            'quoted'           => $r['quoted'],
            'to_win'           => $r['to_win'],
            'wa_amount'        => (float) ($r['wa_amount'] ?? 0),
            'tracking_status'  => $r['tracking_status'],
            'assigned_at'      => $r['assigned_at'],
            'contacted_at'     => $r['contacted_at'],
            'sales_qualified_at' => $r['sales_qualified_at'],
            'quoted_at'        => $r['quoted_at'],
            'to_win_at'        => $r['to_win_at'],
            'full_cycle_seconds' => $r['full_cycle_seconds'] !== null ? (int)$r['full_cycle_seconds'] : null,
            'tracking_updated' => $r['tracking_updated'],
        ];
    }, $rows);

    jsonResponse([
        'projects'               => $projects,
        'avg_full_cycle_sec'     => $avgFullCycleSec,
        'avg_assign_to_contact'  => $avgAssignToContact,
        'avg_contact_to_quote'   => $avgContactToQuote,
        'avg_quote_to_sql'       => $avgQuoteToSql,
        'avg_sql_to_win'         => $avgSqlToWin,
        'avg_processing_sec'     => $avgProcessingSec,
        'total_sec'              => $totalSec,
        'has_timing_data'        => $hasTs,
    ]);

} catch (Exception $e) {
    error_log('SR performance detail error: ' . $e->getMessage());
    jsonResponse(['projects' => [], 'avg_full_cycle_hours' => null, 'has_timing_data' => false]);
}
