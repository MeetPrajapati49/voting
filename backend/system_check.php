<?php
// Create as backend/system_check.php
require_once "config.php";
header('Content-Type: text/plain');

echo "=== VOTEEASY SYSTEM DIAGNOSTIC ===\n\n";

// Check file structure
$requiredFiles = [
    'config.php',
    'login.php',
    'register.php',
    'submit_vote.php',
    'participants.php',
    'poll.php',
    'me.php',
    'logout.php',
    'admin_dashboard.php',
    'council.php'
];

echo "=== FILE CHECK ===\n";
foreach ($requiredFiles as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    echo "$file: " . ($exists ? "✓ EXISTS" : "✗ MISSING") . "\n";
}

// Check database tables
echo "\n=== DATABASE TABLES ===\n";
$tables = ['polls', 'users', 'participants', 'votes', 'student_council'];
foreach ($tables as $table) {
    try {
        $result = $conn->query("SELECT COUNT(*) FROM $table");
        $count = $result->fetchColumn();
        echo "$table: ✓ EXISTS ($count records)\n";
    } catch (Exception $e) {
        echo "$table: ✗ ERROR - " . $e->getMessage() . "\n";
    }
}

// Check API endpoints
echo "\n=== API ENDPOINTS TEST ===\n";
$endpoints = [
    'poll.php?action=list',
    'poll.php?action=count',
    'participants.php?action=count',
    'council.php?action=count',
    'council.php?action=list'
];

foreach ($endpoints as $endpoint) {
    try {
        $url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/" . $endpoint;
        $context = stream_context_create(['http' => ['timeout' => 5]]);
        $result = @file_get_contents($url, false, $context);
        if ($result !== false) {
            $data = json_decode($result, true);
            echo "$endpoint: ✓ WORKING\n";
        } else {
            echo "$endpoint: ✗ NOT RESPONDING\n";
        }
    } catch (Exception $e) {
        echo "$endpoint: ✗ ERROR - " . $e->getMessage() . "\n";
    }
}

// Check admin functionality
echo "\n=== ADMIN USER CHECK ===\n";
try {
    $stmt = $conn->prepare("SELECT id, username, role FROM users WHERE role = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "Admin user: ✓ EXISTS (ID: {$admin['id']}, Username: {$admin['username']})\n";
    } else {
        echo "Admin user: ✗ NOT FOUND\n";
        echo "Creating admin user...\n";
        $password = password_hash('admin123', PASSWORD_BCRYPT);
        $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')")
             ->execute(['admin', $password]);
        echo "Admin user created: admin / admin123\n";
    }
} catch (Exception $e) {
    echo "Admin check error: " . $e->getMessage() . "\n";
}

// Check foreign key constraints
echo "\n=== FOREIGN KEY STATUS ===\n";
$result = $conn->query("PRAGMA foreign_keys");
$fk_status = $result->fetchColumn();
echo "Foreign keys: " . ($fk_status ? "✓ ENABLED" : "✗ DISABLED") . "\n";

// Check for orphaned records
echo "\n=== DATA INTEGRITY CHECK ===\n";
try {
    // Check for participants without polls
    $result = $conn->query("
        SELECT COUNT(*) FROM participants p 
        LEFT JOIN polls po ON p.poll_id = po.id 
        WHERE po.id IS NULL
    ");
    $orphaned_participants = $result->fetchColumn();
    echo "Orphaned participants: " . ($orphaned_participants == 0 ? "✓ NONE" : "✗ $orphaned_participants found") . "\n";

    // Check for votes without valid users or participants
    $result = $conn->query("
        SELECT COUNT(*) FROM votes v
        LEFT JOIN users u ON v.user_id = u.id
        LEFT JOIN participants p ON v.participant_id = p.id
        WHERE u.id IS NULL OR p.id IS NULL
    ");
    $orphaned_votes = $result->fetchColumn();
    echo "Orphaned votes: " . ($orphaned_votes == 0 ? "✓ NONE" : "✗ $orphaned_votes found") . "\n";

} catch (Exception $e) {
    echo "Data integrity check error: " . $e->getMessage() . "\n";
}

echo "\n=== SUMMARY ===\n";
echo "System appears to be: ";
$issues = 0;

// Count issues
foreach ($requiredFiles as $file) {
    if (!file_exists(__DIR__ . '/' . $file)) $issues++;
}

if ($issues == 0) {
    echo "✓ HEALTHY\n";
    echo "You can now focus on improving specific features!\n";
} else {
    echo "⚠ NEEDS ATTENTION ($issues issues found)\n";
    echo "Please address the missing files and errors above.\n";
}

echo "\nNext steps:\n";
echo "1. Fix any missing files\n";
echo "2. Test admin dashboard\n";
echo "3. Create results page\n";
echo "4. Improve user experience\n";
?>