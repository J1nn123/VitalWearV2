<?php
/**
 * Database Configuration & Connection
 * Heart Rate Monitoring System
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'heart_rate_system');

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
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

/**
 * Initialize Database Tables & Seed Data
 * Column names match the actual live database schema.
 */
function initDatabase() {
    $pdo = getDB();

    // Users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        username    VARCHAR(50)  UNIQUE NOT NULL,
        password    VARCHAR(255) NOT NULL,
        email       VARCHAR(100),
        full_name   VARCHAR(100),
        role        ENUM('admin','manager','rescuer','responder') NOT NULL,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status      ENUM('active','inactive') DEFAULT 'active'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Patients table — uses medical_condition and assigned_to to match live DB
    $pdo->exec("CREATE TABLE IF NOT EXISTS patients (
        id                INT AUTO_INCREMENT PRIMARY KEY,
        name              VARCHAR(100) NOT NULL,
        age               INT,
        medical_condition TEXT,
        assigned_to       INT,
        created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Heart rate logs — uses timestamp to match live DB
    $pdo->exec("CREATE TABLE IF NOT EXISTS heart_rate_logs (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        bpm        INT NOT NULL,
        status     ENUM('normal','warning','critical') NOT NULL,
        timestamp  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // System logs — no ip_address column to match live DB
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_logs (
        id        INT AUTO_INCREMENT PRIMARY KEY,
        user_id   INT,
        action    VARCHAR(255),
        details   TEXT,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Incident reports — uses incident_type to match live DB
    $pdo->exec("CREATE TABLE IF NOT EXISTS incident_reports (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        patient_id    INT,
        rescuer_id    INT,
        incident_type VARCHAR(100),
        description   TEXT,
        severity      VARCHAR(50),
        status        VARCHAR(50) DEFAULT 'pending',
        created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (rescuer_id)  REFERENCES users(id)    ON DELETE SET NULL,
        FOREIGN KEY (patient_id)  REFERENCES patients(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Seed default users only if table is empty
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count == 0) {
        $users = [
            ['admin',      password_hash('admin123',      PASSWORD_DEFAULT), 'admin@heartcare.com',      'System Admin',    'admin'],
            ['manager1',   password_hash('manager123',    PASSWORD_DEFAULT), 'manager@heartcare.com',    'John Manager',    'manager'],
            ['rescuer1',   password_hash('rescuer123',    PASSWORD_DEFAULT), 'rescuer@heartcare.com',    'Mike Rescuer',    'rescuer'],
            ['rescuer2',   password_hash('rescuer123',    PASSWORD_DEFAULT), 'rescuer2@heartcare.com',   'Ana Rescuer',     'rescuer'],
            ['responder1', password_hash('responder123',  PASSWORD_DEFAULT), 'responder@heartcare.com',  'Sarah Responder', 'responder'],
            ['responder2', password_hash('responder123',  PASSWORD_DEFAULT), 'responder2@heartcare.com', 'Dr. Jose Rizal',  'responder'],
        ];
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES (?,?,?,?,?)");
        foreach ($users as $u) {
            $stmt->execute($u);
        }

        // Seed patients using correct column names
        $rescuerIds = array_column(
            $pdo->query("SELECT id FROM users WHERE role='rescuer' LIMIT 2")->fetchAll(),
            'id'
        );
        $r1 = $rescuerIds[0] ?? null;
        $r2 = $rescuerIds[1] ?? null;

        $patients = [
            ['Carlos Mendoza',   45, 'Hypertension',       $r1],
            ['Lita Gonzales',    62, 'Arrhythmia',         $r1],
            ['Roberto Cruz',     38, 'Post-op Recovery',   $r2],
            ['Elena Villanueva', 71, 'Cardiac Arrest',     $r2],
            ['Mark Fernandez',   55, 'Heart Failure',      $r1],
            ['Sofia Reyes',      49, 'Atrial Fibrillation',$r2],
            ['Pedro Bautista',   67, 'Coronary Artery',    $r1],
            ['Marisol Torres',   53, 'Palpitations',       $r2],
        ];
        $pstmt = $pdo->prepare("INSERT INTO patients (name, age, medical_condition, assigned_to) VALUES (?,?,?,?)");
        foreach ($patients as $p) {
            $pstmt->execute($p);
        }

        // Seed heart rate logs using correct column name (timestamp)
        $patientIds = array_column(
            $pdo->query("SELECT id FROM patients")->fetchAll(),
            'id'
        );
        $logStmt = $pdo->prepare("INSERT INTO heart_rate_logs (patient_id, bpm, status, timestamp) VALUES (?,?,?,?)");
        foreach ($patientIds as $pid) {
            for ($i = 20; $i >= 0; $i--) {
                $bpm    = rand(55, 125);
                $status = getBpmStatus($bpm);
                $time   = date('Y-m-d H:i:s', strtotime("-{$i} minutes"));
                $logStmt->execute([$pid, $bpm, $status, $time]);
            }
        }
    }
}

/**
 * Classify a BPM value into normal / warning / critical
 */
function getBpmStatus($bpm) {
    if ($bpm < 60 || $bpm > 120) return 'critical';
    if ($bpm >= 100)              return 'warning';
    return 'normal';
}

/**
 * Write an entry to system_logs.
 * Matches the actual live table (no ip_address column).
 */
function logAction($userId, $action, $details = '') {
    try {
        $pdo  = getDB();
        $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details) VALUES (?,?,?)");
        $stmt->execute([$userId, $action, $details]);
    } catch (Exception $e) {
        error_log("logAction error: " . $e->getMessage());
    }
}