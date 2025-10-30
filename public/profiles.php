<?php
/**
 * Bihak Center - Profiles API
 * Returns profile data for AJAX loading
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 8;

    $conn = getDatabaseConnection();

    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM profiles WHERE status = 'approved' AND is_published = TRUE";
    $countResult = $conn->query($countQuery);
    $total = $countResult->fetch_assoc()['total'];

    // Get profiles
    $query = "SELECT
        id, full_name, title, short_description, profile_image,
        media_type, media_url, city, district, field_of_study,
        created_at, view_count
    FROM profiles
    WHERE status = 'approved' AND is_published = TRUE
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $profiles = [];
    while ($row = $result->fetch_assoc()) {
        $profiles[] = $row;
    }

    $stmt->close();
    closeDatabaseConnection($conn);

    echo json_encode([
        'success' => true,
        'profiles' => $profiles,
        'total' => $total,
        'hasMore' => ($offset + $limit) < $total
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
