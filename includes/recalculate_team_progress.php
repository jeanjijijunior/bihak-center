<?php
/**
 * Recalculate Team Progress
 *
 * This script recalculates completion percentages for all teams based on:
 * 1. Approved submissions in exercise_submissions table
 * 2. Completed exercises in team_exercise_progress table
 *
 * Run this script to fix progress bars after retroactive approvals
 */

require_once __DIR__ . '/../config/database.php';

$conn = getDatabaseConnection();

echo "=== Recalculating Team Progress ===\n\n";

// Get total number of required exercises
$total_exercises_query = "
    SELECT COUNT(*) as total
    FROM incubation_exercises
    WHERE is_active = 1 AND is_required = 1
";
$total_result = $conn->query($total_exercises_query);
$total_exercises = $total_result->fetch_assoc()['total'];

echo "Total required exercises: {$total_exercises}\n\n";

// Get all active teams
$teams_query = "
    SELECT id, team_name, completion_percentage, status
    FROM incubation_teams
    WHERE status != 'archived'
";
$teams_result = $conn->query($teams_query);
$teams = $teams_result->fetch_all(MYSQLI_ASSOC);

echo "Processing " . count($teams) . " teams...\n\n";

foreach ($teams as $team) {
    $team_id = $team['id'];
    $team_name = $team['team_name'];
    $old_percentage = $team['completion_percentage'];
    $old_status = $team['status'];

    echo "Team: {$team_name} (ID: {$team_id})\n";
    echo "  Old: {$old_percentage}% - {$old_status}\n";

    // Count completed exercises from team_exercise_progress
    $progress_completed_query = "
        SELECT COUNT(DISTINCT exercise_id) as count
        FROM team_exercise_progress
        WHERE team_id = ? AND status = 'completed'
    ";
    $stmt = $conn->prepare($progress_completed_query);
    $stmt->bind_param('i', $team_id);
    $stmt->execute();
    $progress_completed = $stmt->get_result()->fetch_assoc()['count'];

    // Count approved submissions (that might not be in team_exercise_progress yet)
    $submissions_approved_query = "
        SELECT COUNT(DISTINCT exercise_id) as count
        FROM exercise_submissions
        WHERE team_id = ? AND status = 'approved'
    ";
    $stmt = $conn->prepare($submissions_approved_query);
    $stmt->bind_param('i', $team_id);
    $stmt->execute();
    $submissions_approved = $stmt->get_result()->fetch_assoc()['count'];

    // Take the maximum (some might be in both tables, we don't want to double count)
    // Actually, let's get distinct exercises from BOTH tables
    $all_completed_query = "
        SELECT COUNT(DISTINCT exercise_id) as count
        FROM (
            SELECT exercise_id FROM team_exercise_progress WHERE team_id = ? AND status = 'completed'
            UNION
            SELECT exercise_id FROM exercise_submissions WHERE team_id = ? AND status = 'approved'
        ) AS completed_exercises
    ";
    $stmt = $conn->prepare($all_completed_query);
    $stmt->bind_param('ii', $team_id, $team_id);
    $stmt->execute();
    $completed_exercises = $stmt->get_result()->fetch_assoc()['count'];

    echo "  Completed exercises: {$completed_exercises}\n";
    echo "    - From team_exercise_progress: {$progress_completed}\n";
    echo "    - From exercise_submissions: {$submissions_approved}\n";

    // Calculate new percentage
    $new_percentage = ($total_exercises > 0) ? ($completed_exercises / $total_exercises) * 100 : 0;

    // Determine new status
    $new_status = $old_status;
    if ($new_percentage >= 100) {
        $new_status = 'completed';
    } elseif ($new_percentage > 0) {
        $new_status = 'in_progress';
    } elseif ($old_status === 'in_progress') {
        $new_status = 'forming'; // Reset to forming if 0%
    }

    echo "  New: " . round($new_percentage, 2) . "% - {$new_status}\n";

    // Update team
    if ($new_percentage != $old_percentage || $new_status != $old_status) {
        $update_query = "
            UPDATE incubation_teams
            SET completion_percentage = ?,
                status = ?
            WHERE id = ?
        ";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param('dsi', $new_percentage, $new_status, $team_id);
        $stmt->execute();

        echo "  ✅ Updated!\n";
    } else {
        echo "  ⏭️  No changes needed\n";
    }

    echo "\n";
}

echo "=== Done! ===\n";
echo "All team progress percentages have been recalculated.\n";

closeDatabaseConnection($conn);
?>
