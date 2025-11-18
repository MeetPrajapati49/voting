<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'list') {
    try {
        $today = date("Y-m-d");

        // Only show active polls
        $stmt = $conn->prepare("
            SELECT * FROM polls
            WHERE date(start_date) <= :today
              AND date(end_date) >= :today
            ORDER BY start_date DESC
        ");
        $stmt->execute([":today" => $today]);
        $polls = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($polls);
    } catch (PDOException $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
    exit;
}

try {
    switch ($action) {
        case 'list':
            $stmt = $conn->query("SELECT id, title, start_date, end_date FROM polls ORDER BY id DESC");
            $polls = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($polls);
            break;

        case 'create':
            $title = trim($_POST['title'] ?? '');
            $start = $_POST['start_date'] ?? '';
            $end = $_POST['end_date'] ?? '';
            
            if (!$title) {
                echo json_encode(["status" => "error", "message" => "Title is required"]);
                exit;
            }
            
            $stmt = $conn->prepare("INSERT INTO polls (title, start_date, end_date) VALUES (?, ?, ?)");
            $success = $stmt->execute([$title, $start, $end]);
            
            if ($success) {
                $pollId = $conn->lastInsertId();
                echo json_encode([
                    "status" => "success", 
                    "message" => "Poll created successfully",
                    "poll_id" => $pollId
                ]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to create poll"]);
            }
            break;

        case 'delete':
            $id = $_POST['id'] ?? 0;
            
            if (!$id) {
                echo json_encode(["status" => "error", "message" => "Poll ID is required"]);
                exit;
            }

            $conn->beginTransaction();
            try {
                // Delete votes first (cascade through participants)
                $conn->prepare("DELETE FROM votes WHERE participant_id IN (SELECT id FROM participants WHERE poll_id = ?)")->execute([$id]);
                
                // Delete participants
                $conn->prepare("DELETE FROM participants WHERE poll_id = ?")->execute([$id]);
                
                // Delete poll
                $stmt = $conn->prepare("DELETE FROM polls WHERE id = ?");
                $success = $stmt->execute([$id]);

                if ($success && $stmt->rowCount() > 0) {
                    $conn->commit();
                    echo json_encode(["status" => "success", "message" => "Poll deleted successfully"]);
                } else {
                    $conn->rollBack();
                    echo json_encode(["status" => "error", "message" => "Poll not found"]);
                }
            } catch (Exception $e) {
                $conn->rollBack();
                throw $e;
            }
            break;

        case 'count':
            $stmt = $conn->query("SELECT COUNT(*) as total FROM polls");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(["total" => (int)$result['total']]);
            break;

        case 'get':
            $id = $_GET['id'] ?? 0;
            
            if (!$id) {
                echo json_encode(["status" => "error", "message" => "Poll ID is required"]);
                exit;
            }

            $stmt = $conn->prepare("SELECT * FROM polls WHERE id = ?");
            $stmt->execute([$id]);
            $poll = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($poll) {
                echo json_encode(["status" => "success", "poll" => $poll]);
            } else {
                echo json_encode(["status" => "error", "message" => "Poll not found"]);
            }
            break;

        default:
            echo json_encode(["status" => "error", "message" => "Invalid action: " . $action]);
    }
} catch (Exception $e) {
    error_log("Poll.php error: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}
?>