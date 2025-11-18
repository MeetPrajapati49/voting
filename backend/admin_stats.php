<?php
require 'config.php';
header('Content-Type: application/json');

try {
    $polls = $conn->query("SELECT COUNT(*) FROM polls")->fetchColumn();
    $participants = $conn->query("SELECT COUNT(*) FROM participants")->fetchColumn();
    $council = $conn->query("SELECT COUNT(*) FROM student_council")->fetchColumn();

    echo json_encode([
        'polls' => $polls,
        'participants' => $participants,
        'council' => $council
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
