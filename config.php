<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$db   = 'X100C';
$user = 'setiyo';
$pass = 'setiyo';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Create tables if they don't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS `guru` (
        `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `nama_guru` varchar(255) NOT NULL,
        `device_id` int NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS `jam_kerja` (
        `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `nama_jam_kerja` varchar(100) NOT NULL,
        `jam_masuk` time NOT NULL,
        `jam_pulang` time NOT NULL,
        `toleransi_keterlambatan` int DEFAULT '0'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS `guru_jam_kerja` (
        `guru_id` int NOT NULL,
        `jam_kerja_id` int NOT NULL,
        PRIMARY KEY (`guru_id`),
        FOREIGN KEY (`guru_id`) REFERENCES `guru` (`id`) ON DELETE CASCADE,
        FOREIGN KEY (`jam_kerja_id`) REFERENCES `jam_kerja` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS `x100c_data` (
        `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `device_id` int NOT NULL,
        `karyawan_id` int DEFAULT NULL,
        `timestamp` datetime NOT NULL,
        `param1` int NOT NULL,
        `param2` int NOT NULL,
        `param3` int NOT NULL,
        `param4` int NOT NULL,
        `jenis_absensi` enum('Masuk','Pulang','Istirahat','Kembali') DEFAULT NULL,
        `keterangan` varchar(255) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
    
    // Create temporary table with same structure as x100c_data
    $pdo->exec("CREATE TABLE IF NOT EXISTS `x100c_data_temp` (
        `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `device_id` int NOT NULL,
        `karyawan_id` int DEFAULT NULL,
        `timestamp` datetime NOT NULL,
        `param1` int NOT NULL,
        `param2` int NOT NULL,
        `param3` int NOT NULL,
        `param4` int NOT NULL,
        `jenis_absensi` enum('Masuk','Pulang','Istirahat','Kembali') DEFAULT NULL,
        `keterangan` varchar(255) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
    
} catch (\PDOException $e) {
    die("Database error: " . $e->getMessage());
}

/**
 * Get attendance report with separate status for arrival and departure
 */
function getLaporanAbsensi($pdo, $tanggal_awal, $tanggal_akhir) {
    try {
        $sql = "SELECT 
                    g.nama_guru, 
                    x.timestamp, 
                    x.param1,
                    x.param2,
                    CASE WHEN x.param1 = 1 THEN TIME_FORMAT(x.timestamp, '%H:%i:%s') ELSE NULL END as waktu_param1,
                    CASE WHEN x.param2 = 1 THEN TIME_FORMAT(x.timestamp, '%H:%i:%s') ELSE NULL END as waktu_param2,
                    jk.nama_jam_kerja, 
                    jk.jam_masuk, 
                    jk.jam_pulang, 
                    jk.toleransi_keterlambatan,
                    CASE 
                        WHEN x.param1 = 1 AND TIME(x.timestamp) > ADDTIME(jk.jam_masuk, SEC_TO_TIME(IFNULL(jk.toleransi_keterlambatan, 0) * 60))
                            THEN 'Terlambat'
                        WHEN x.param1 = 1 
                            THEN 'Tepat Waktu'
                        ELSE NULL
                    END as status_masuk,
                    CASE
                        WHEN x.param2 = 1 AND TIME(x.timestamp) < jk.jam_pulang 
                            THEN 'Pulang Awal'
                        WHEN x.param2 = 1 
                            THEN 'Tepat Waktu'
                        ELSE NULL
                    END as status_pulang
                FROM x100c_data x
                JOIN guru g ON x.device_id = g.device_id
                LEFT JOIN guru_jam_kerja gj ON g.id = gj.guru_id
                LEFT JOIN jam_kerja jk ON gj.jam_kerja_id = jk.id
                WHERE DATE(x.timestamp) BETWEEN ? AND ?
                ORDER BY x.timestamp DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tanggal_awal, $tanggal_akhir]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error in getLaporanAbsensi: " . $e->getMessage());
        return [];
    }
}

/**
 * Add new teacher
 */
function tambahGuru($pdo, $nama_guru, $device_id) {
    try {
        $stmt = $pdo->prepare("SELECT id FROM guru WHERE device_id = ?");
        $stmt->execute([$device_id]);
        
        if ($stmt->rowCount() > 0) {
            return "duplicate_device";
        }
        
        $stmt = $pdo->prepare("INSERT INTO guru (nama_guru, device_id) VALUES (?, ?)");
        $stmt->execute([$nama_guru, $device_id]);
        
        return ($stmt->rowCount() > 0) ? true : false;
    } catch (PDOException $e) {
        error_log("Error in tambahGuru: " . $e->getMessage());
        return false;
    }
}

/**
 * Get available devices not assigned to teachers
 */
function getAvailableDevices($pdo) {
    try {
        $stmt = $pdo->query("SELECT DISTINCT device_id FROM x100c_data WHERE device_id IS NOT NULL");
        $allDevices = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $stmt = $pdo->query("SELECT device_id FROM guru WHERE device_id IS NOT NULL");
        $usedDevices = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $availableDevices = array_diff($allDevices, $usedDevices);
        
        return array_map(function($id) {
            return ['device_id' => $id];
        }, $availableDevices);
    } catch (PDOException $e) {
        error_log("Error in getAvailableDevices: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all teachers with their work schedules
 */
function getAllGuru($pdo) {
    try {
        $sql = "SELECT g.id, g.nama_guru, g.device_id, jk.nama_jam_kerja, jk.id as jam_kerja_id
                FROM guru g
                LEFT JOIN guru_jam_kerja gj ON g.id = gj.guru_id
                LEFT JOIN jam_kerja jk ON gj.jam_kerja_id = jk.id
                ORDER BY g.nama_guru";
        return $pdo->query($sql)->fetchAll();
    } catch (PDOException $e) {
        error_log("Error in getAllGuru: " . $e->getMessage());
        return $pdo->query("SELECT id, nama_guru, device_id FROM guru ORDER BY nama_guru")->fetchAll();
    }
}

/**
 * Get raw attendance data
 */
function getX100CData($pdo, $limit = 100) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM x100c_data ORDER BY timestamp DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error in getX100CData: " . $e->getMessage());
        return [];
    }
}

/**
 * Get raw data from temporary table
 */
function getX100CTempData($pdo, $limit = 100) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM x100c_data_temp ORDER BY timestamp DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error in getX100CTempData: " . $e->getMessage());
        return [];
    }
}

/**
 * Copy data from main table to temporary table
 */
function copyToTempTable($pdo, $limit = 100) {
    try {
        $pdo->beginTransaction();
        
        // Clear temp table first
        $pdo->exec("TRUNCATE TABLE x100c_data_temp");
        
        // Copy data
        $stmt = $pdo->prepare("INSERT INTO x100c_data_temp 
                              SELECT * FROM x100c_data ORDER BY timestamp DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error in copyToTempTable: " . $e->getMessage());
        return false;
    }
}

/**
 * Move data from temp table back to main table
 */
function moveFromTempToMain($pdo) {
    try {
        $pdo->beginTransaction();
        
        // Insert data from temp to main
        $pdo->exec("INSERT INTO x100c_data 
                   SELECT * FROM x100c_data_temp 
                   WHERE id NOT IN (SELECT id FROM x100c_data)");
        
        // Clear temp table
        $pdo->exec("TRUNCATE TABLE x100c_data_temp");
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error in moveFromTempToMain: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all work schedules
 */
function getAllJamKerja($pdo) {
    try {
        return $pdo->query("SELECT * FROM jam_kerja ORDER BY nama_jam_kerja")->fetchAll();
    } catch (PDOException $e) {
        error_log("Error in getAllJamKerja: " . $e->getMessage());
        return [];
    }
}

/**
 * Add new work schedule
 */
function tambahJamKerja($pdo, $nama, $jam_masuk, $jam_pulang, $toleransi) {
    try {
        $sql = "INSERT INTO jam_kerja (nama_jam_kerja, jam_masuk, jam_pulang, toleransi_keterlambatan) 
                VALUES (:nama, :jam_masuk, :jam_pulang, :toleransi)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':nama' => $nama,
            ':jam_masuk' => $jam_masuk,
            ':jam_pulang' => $jam_pulang,
            ':toleransi' => $toleransi
        ]);
    } catch (PDOException $e) {
        error_log("Error in tambahJamKerja: " . $e->getMessage());
        return false;
    }
}

/**
 * Update teacher's work schedule
 */
function updateJamKerjaGuru($pdo, $guru_id, $jam_kerja_id) {
    try {
        $pdo->beginTransaction();
        
        $pdo->prepare("DELETE FROM guru_jam_kerja WHERE guru_id = ?")->execute([$guru_id]);
        
        if (!empty($jam_kerja_id)) {
            $sql = "INSERT INTO guru_jam_kerja (guru_id, jam_kerja_id) VALUES (?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$guru_id, $jam_kerja_id]);
        }
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error in updateJamKerjaGuru: " . $e->getMessage());
        return false;
    }
}
?>