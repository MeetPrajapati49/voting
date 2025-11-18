<?php
require_once "config.php";
header("Content-Type: application/json");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            $poll_id = $_GET['poll_id'] ?? null;

            if ($poll_id) {
                $stmt = $conn->prepare("SELECT * FROM participants WHERE poll_id = ? ORDER BY name");
                $stmt->execute([$poll_id]);
            } else {
                $stmt = $conn->query("SELECT * FROM participants ORDER BY name");
            }
            
            $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($participants);
            break;

        case 'add':
            // Handle both JSON and form data
            if ($_SERVER['CONTENT_TYPE'] === 'application/json' || strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
                $data = json_decode(file_get_contents("php://input"), true);
            } else {
                $data = $_POST;
            }

            $name = trim($data['name'] ?? '');
            $email = trim($data['email'] ?? '');
            $poll_id = $data['poll_id'] ?? null;

            if (!$name || !$poll_id) {
                echo json_encode(["status" => "error", "message" => "Name and poll_id are required"]);
                exit;
            }

            // Check if poll exists
            $pollCheck = $conn->prepare("SELECT id FROM polls WHERE id = ?");
            $pollCheck->execute([$poll_id]);
            if (!$pollCheck->fetch()) {
                echo json_encode(["status" => "error", "message" => "Invalid poll ID"]);
                exit;
            }

            // Insert participant
            $stmt = $conn->prepare("INSERT INTO participants (name, email, poll_id) VALUES (?, ?, ?)");
            $success = $stmt->execute([$name, $email, $poll_id]);
            
            if ($success) {
                echo json_encode(["status" => "success", "message" => "Participant added successfully", "id" => $conn->lastInsertId()]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to add participant"]);
            }
            break;

        case 'remove':
        case 'delete':
            $id = $_POST['id'] ?? $_GET['id'] ?? null;
            
            if (!$id) {
                echo json_encode(["status" => "error", "message" => "Participant ID is required"]);
                exit;
            }

            $conn->beginTransaction();
            try {
                // Delete votes first
                $deleteVotes = $conn->prepare("DELETE FROM votes WHERE participant_id = ?");
                $deleteVotes->execute([$id]);

                // Delete participant
                $deleteParticipant = $conn->prepare("DELETE FROM participants WHERE id = ?");
                $success = $deleteParticipant->execute([$id]);

                if ($success && $deleteParticipant->rowCount() > 0) {
                    $conn->commit();
                    echo json_encode(["status" => "success", "message" => "Participant removed successfully"]);
                } else {
                    $conn->rollBack();
                    echo json_encode(["status" => "error", "message" => "Participant not found"]);
                }
            } catch (Exception $e) {
                $conn->rollBack();
                throw $e;
            }
            break;

        case 'count':
            $stmt = $conn->query("SELECT COUNT(*) as total FROM participants");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(["total" => (int)$result['total']]);
            break;

        default:
            echo json_encode(["status" => "error", "message" => "Invalid action: " . $action]);
    }
} catch (Exception $e) {
    error_log("Participants.php error: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}
?>