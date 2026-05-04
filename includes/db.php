<?php
/**
 * Database Configuration & Connection
 * Heart Rate Monitoring System
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'heart_rate_system');


define('BASE_URL', $_SERVER['HTTP_HOST'] === 'localhost' ? '/VitalWearV2' : '');


/* =========================
   DATABASE CONNECTION
   ========================= */

function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $pdo->exec("SET time_zone = '+08:00'");

        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode([
                'error' => 'Database connection failed: ' . $e->getMessage()
            ]));
        }
    }

    return $pdo;
}


/* =========================
   INIT DATABASE
   ========================= */

function initDatabase() {
    $pdo = getDB();

    // USERS
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100),
        full_name VARCHAR(100),
        role ENUM('admin','manager','rescuer','responder') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('active','inactive') DEFAULT 'active'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // PATIENTS
    $pdo->exec("CREATE TABLE IF NOT EXISTS patients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        age INT,
        medical_condition TEXT,
        assigned_to INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // HEART RATE LOGS
    $pdo->exec("CREATE TABLE IF NOT EXISTS heart_rate_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        bpm INT NOT NULL,
        status ENUM('normal','warning','critical') NOT NULL,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // SYSTEM LOGS
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(255),
        details TEXT,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // INCIDENT REPORTS
    $pdo->exec("CREATE TABLE IF NOT EXISTS incident_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT,
        rescuer_id INT,
        incident_type VARCHAR(100),
        description TEXT,
        severity VARCHAR(50),
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (rescuer_id) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // SEED USERS
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

    if ($count == 0) {
        $users = [
            ['admin',      password_hash('admin123',     PASSWORD_DEFAULT), 'admin@heartcare.com',      'System Admin',    'admin'],
            ['manager1',   password_hash('manager123',   PASSWORD_DEFAULT), 'manager@heartcare.com',    'John Manager',    'manager'],
            ['rescuer1',   password_hash('rescuer123',   PASSWORD_DEFAULT), 'rescuer@heartcare.com',    'Mike Rescuer',    'rescuer'],
            ['rescuer2',   password_hash('rescuer123',   PASSWORD_DEFAULT), 'rescuer2@heartcare.com',   'Ana Rescuer',     'rescuer'],
            ['responder1', password_hash('responder123', PASSWORD_DEFAULT), 'responder@heartcare.com',  'Sarah Responder', 'responder'],
            ['responder2', password_hash('responder123', PASSWORD_DEFAULT), 'responder2@heartcare.com', 'Dr. Jose Rizal',  'responder'],
        ];

        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES (?,?,?,?,?)");

        foreach ($users as $u) {
            $stmt->execute($u);
        }
    }
}


/* =========================
   HELPERS
   ========================= */

function getBpmStatus($bpm) {
    if ($bpm < 60 || $bpm > 120) return 'critical';
    if ($bpm >= 100) return 'warning';
    return 'normal';
}

function logAction($userId, $action, $details = '') {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details) VALUES (?,?,?)");
        $stmt->execute([$userId, $action, $details]);
    } catch (Exception $e) {
        error_log("logAction error: " . $e->getMessage());
    }
}