<?php
// backend/reset_db.php - MySQL version

require_once "config.php";

try {
    // Drop existing tables if they exist
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $conn->exec("DROP TABLE IF EXISTS votes;");
    $conn->exec("DROP TABLE IF EXISTS participants;");
    $conn->exec("DROP TABLE IF EXISTS polls;");
    $conn->exec("DROP TABLE IF EXISTS users;");
    $conn->exec("DROP TABLE IF EXISTS student_council;");
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // Create tables
    $conn->exec("
    CREATE TABLE polls (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        start_date DATE,
        end_date DATE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255),
        aadhaar_hmac VARCHAR(64) UNIQUE,
        email VARCHAR(255),
        mobile VARCHAR(15),
        role VARCHAR(50) DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE participants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        poll_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255),
        FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE votes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        participant_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE,
        UNIQUE KEY unique_vote (user_id, participant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE student_council (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        position VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Insert default data
    $conn->exec("
        INSERT INTO polls (title, start_date, end_date) 
        VALUES ('Student Union Election 2025', '2025-01-01', '2025-12-31');
    ");

    $conn->exec("
        INSERT INTO participants (name, email, poll_id) VALUES
        ('John Smith', 'john@example.com', 1),
        ('Maria Johnson', 'maria@example.com', 1),
        ('Alex Davis', 'alex@example.com', 1),
        ('Sarah Wilson', 'sarah@example.com', 1);
    ");

    // Create admin user
    $password = password_hash('admin123', PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')");
    $stmt->execute(['admin', $password]);

    // Create a test user
    $testPassword = password_hash('test123', PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'user')");
    $stmt->execute(['testuser', $testPassword]);

    echo "Database reset and initialized successfully.";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
