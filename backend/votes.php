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
        case 'count':
            $poll_id = $_GET['poll_id'] ?? null;
            
            if ($poll_id) {
                // Count votes for specific poll
                $stmt = $conn->prepare("
                    SELECT COUNT(*) as total 
                    FROM votes v 
                    JOIN participants p ON v.participant_id = p.id 
                    WHERE p.poll_id = ?
                ");
                $stmt->execute([$poll_id]);
            } else {
                // Count all votes
                $stmt = $conn->query("SELECT COUNT(*) as total FROM votes");
            }
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(["total" => (int)$result['total']]);
            break;

        case 'results':
            $poll_id = $_GET['poll_id'] ?? null;
            
            if (!$poll_id) {
                echo json_encode(["status" => "error", "message" => "Poll ID is required"]);
                exit;
            }

            // Check if poll is active
            $pollCheck = $conn->prepare("
                SELECT 1 FROM polls p
                WHERE p.id = :poll_id
                AND date(p.start_date) <= date('now')
                AND date(p.end_date) >= date('now')
            ");
            $pollCheck->execute([":poll_id" => $poll_id]);

            if (!$pollCheck->fetch()) {
                echo json_encode(["success" => false, "message" => "This poll is closed."]);
                exit;
            }

            // Get vote results for all participants in this poll
            $stmt = $conn->prepare("
                SELECT 
                    p.id,
                    p.name,
                    p.email,
                    COUNT(v.id) as vote_count,
                    CASE 
                        WHEN total_votes.total > 0 
                        THEN ROUND((COUNT(v.id) * 100.0 / total_votes.total), 2)
                        ELSE 0 
                    END as percentage
                FROM participants p
                LEFT JOIN votes v ON p.id = v.participant_id
                CROSS JOIN (
                    SELECT COUNT(*) as total 
                    FROM votes v2 
                    JOIN participants p2 ON v2.participant_id = p2.id 
                    WHERE p2.poll_id = ?
                ) as total_votes
                WHERE p.poll_id = ?
                GROUP BY p.id, p.name, p.email, total_votes.total
                ORDER BY vote_count DESC
            ");
            $stmt->execute([$poll_id, $poll_id]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($results);
            break;

        case 'list':
            $poll_id = $_GET['poll_id'] ?? null;
            
            if ($poll_id) {
                // Get votes for specific poll
                $stmt = $conn->prepare("
                    SELECT 
                        v.id,
                        v.user_id,
                        v.participant_id,
                        v.created_at,
                        p.name as participant_name,
                        u.username as voter_username
                    FROM votes v
                    JOIN participants p ON v.participant_id = p.id
                    LEFT JOIN users u ON v.user_id = u.id
                    WHERE p.poll_id = ?
                    ORDER BY v.created_at DESC
                ");
                $stmt->execute([$poll_id]);
            } else {
                // Get all votes
                $stmt = $conn->query("
                    SELECT 
                        v.id,
                        v.user_id,
                        v.participant_id,
                        v.created_at,
                        p.name as participant_name,
                        u.username as voter_username,
                        pol.title as poll_title
                    FROM votes v
                    JOIN participants p ON v.participant_id = p.id
                    JOIN polls pol ON p.poll_id = pol.id
                    LEFT JOIN users u ON v.user_id = u.id
                    ORDER BY v.created_at DESC
                ");
            }
            
            $votes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($votes);
            break;

        case 'participation_rate':
            $poll_id = $_GET['poll_id'] ?? null;
            
            if ($poll_id) {
                // Get participation rate for specific poll
                $stmt = $conn->prepare("
                    SELECT 
                        COUNT(DISTINCT p.id) as total_participants,
                        COUNT(DISTINCT v.user_id) as total_voters,
                        CASE 
                            WHEN COUNT(DISTINCT p.id) > 0 
                            THEN ROUND((COUNT(DISTINCT v.user_id) * 100.0 / COUNT(DISTINCT p.id)), 2)
                            ELSE 0 
                        END as participation_rate
                    FROM participants p
                    LEFT JOIN votes v ON p.id = v.participant_id
                    WHERE p.poll_id = ?
                ");
                $stmt->execute([$poll_id]);
            } else {
                // Get overall participation rate
                $stmt = $conn->query("
                    SELECT 
                        COUNT(DISTINCT p.id) as total_participants,
                        COUNT(DISTINCT v.user_id) as total_voters,
                        CASE 
                            WHEN COUNT(DISTINCT p.id) > 0 
                            THEN ROUND((COUNT(DISTINCT v.user_id) * 100.0 / COUNT(DISTINCT p.id)), 2)
                            ELSE 0 
                        END as participation_rate
                    FROM participants p
                    LEFT JOIN votes v ON p.id = v.participant_id
                ");
            }
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($result);
            break;

        case 'activity':
            $limit = $_GET['limit'] ?? 10;
            
            // Get recent voting activity
            $stmt = $conn->prepare("
                SELECT 
                    v.created_at,
                    p.name as participant_name,
                    pol.title as poll_title,
                    u.username as voter_username
                FROM votes v
                JOIN participants p ON v.participant_id = p.id
                JOIN polls pol ON p.poll_id = pol.id
                LEFT JOIN users u ON v.user_id = u.id
                ORDER BY v.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($activity);
            break;

        case 'stats':
            // Get comprehensive voting statistics
            $stats = [];
            
            // Total votes
            $stmt = $conn->query("SELECT COUNT(*) as total FROM votes");
            $stats['total_votes'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Votes by poll
            $stmt = $conn->query("
                SELECT 
                    pol.id,
                    pol.title,
                    COUNT(v.id) as vote_count
                FROM polls pol
                LEFT JOIN participants p ON pol.id = p.poll_id
                LEFT JOIN votes v ON p.id = v.participant_id
                GROUP BY pol.id, pol.title
                ORDER BY vote_count DESC
            ");
            $stats['votes_by_poll'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Recent activity count (last 24 hours)
            $stmt = $conn->query("
                SELECT COUNT(*) as recent_votes
                FROM votes 
                WHERE created_at >= datetime('now', '-1 day')
            ");
            $stats['recent_votes'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['recent_votes'];
            
            echo json_encode($stats);
            break;

        default:
            echo json_encode(["status" => "error", "message" => "Invalid action: " . $action]);
    }
} catch (Exception $e) {
    error_log("Votes.php error: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}
?>