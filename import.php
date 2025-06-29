<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Database configuration
$servername = "localhost";
$username = "setiyo";
$password = "setiyo";
$dbname = "X100C";

// Function to import data from .dat file
function importDatFile($filePath) {
    global $servername, $username, $password, $dbname;
    
    // Create database connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        return ["status" => "error", "message" => "Koneksi gagal: " . $conn->connect_error];
    }

    // Begin transaction for data integrity
    $conn->begin_transaction();

    $output = [];
    $output[] = "Mulai import data dari {$filePath}...";

    if (($handle = fopen($filePath, "r")) !== false) {
        // Prepare statement to check for duplicates
        $checkStmt = $conn->prepare("SELECT id FROM x100c_data WHERE device_id = ? AND timestamp = ?");
        if ($checkStmt === false) {
            fclose($handle);
            $conn->rollback();
            return ["status" => "error", "message" => "Error dalam mempersiapkan statement pengecekan: " . $conn->error];
        }
        $checkStmt->bind_param("is", $device_id, $timestamp);

        // Prepare statement for insertion
        $insertStmt = $conn->prepare("INSERT INTO x100c_data (device_id, timestamp, param1, param2, param3, param4) VALUES (?, ?, ?, ?, ?, ?)");
        if ($insertStmt === false) {
            fclose($handle);
            $checkStmt->close();
            $conn->rollback();
            return ["status" => "error", "message" => "Error dalam mempersiapkan statement insert: " . $conn->error];
        }
        $insertStmt->bind_param("isiiii", $device_id, $timestamp, $param1, $param2, $param3, $param4);

        $row = 0;
        $successCount = 0;
        $duplicateCount = 0;
        $errorCount = 0;
        
        while (($line = fgets($handle)) !== false) {
            $row++;
            $line = trim($line);

            if (empty($line)) continue;

            $parts = preg_split('/\s+/', $line);

            if (isset($parts[0]) && empty($parts[0])) {
                array_shift($parts);
            }

            if (count($parts) == 7) {
                $timestamp_str = $parts[1] . ' ' . $parts[2];
                array_splice($parts, 1, 2, $timestamp_str);
            }

            if (count($parts) == 6) {
                try {
                    $device_id = (int)$parts[0];
                    $timestamp = $parts[1];
                    $param1 = (int)$parts[2];
                    $param2 = (int)$parts[3];
                    $param3 = (int)$parts[4];
                    $param4 = (int)$parts[5];

                    // Check for duplicate data
                    $checkStmt->execute();
                    $checkStmt->store_result();

                    if ($checkStmt->num_rows > 0) {
                        // Data already exists, skip insertion
                        $duplicateCount++;
                    } else {
                        // Data does not exist, insert it
                        if ($insertStmt->execute()) {
                            $successCount++;
                        } else {
                            $output[] = "Baris {$row}: Gagal mengimpor data. Error: " . $insertStmt->error;
                            $errorCount++;
                        }
                    }
                } catch (Throwable $e) {
                    $output[] = "Baris {$row}: Melewatkan baris karena kesalahan format. Error: " . $e->getMessage();
                    $errorCount++;
                }
            } else {
                $output[] = "Baris {$row}: Format tidak sesuai (ditemukan " . count($parts) . " kolom, diharapkan 6)";
                $errorCount++;
            }
        }

        $checkStmt->close();
        $insertStmt->close();
        fclose($handle);

        // If there were any errors, rollback the transaction. Otherwise, commit.
        if ($errorCount > 0) {
            $conn->rollback();
            $output[] = "Terjadi kesalahan, semua data yang diimpor dari file ini telah dibatalkan.";
            return ["status" => "error", "message" => implode("<br>", $output)];
        } else {
            $conn->commit();
            $output[] = "Proses import selesai.";
            $output[] = "Total baris diproses: {$row}";
            $output[] = "Berhasil diimpor: {$successCount}";
            $output[] = "Data duplikat dilewati: {$duplicateCount}";
            return ["status" => "success", "message" => implode("<br>", $output)];
        }
    } else {
        $conn->close();
        return ["status" => "error", "message" => "Tidak dapat membuka file {$filePath}"];
    }
}

// Handle file upload and import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['dat_file'])) {
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = basename($_FILES['dat_file']['name']);
    $filePath = $uploadDir . $fileName;
    $fileType = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    // Check if file is a .dat file
    if ($fileType != 'dat') {
        $result = ["status" => "error", "message" => "Hanya file .dat yang diperbolehkan."];
    } elseif (move_uploaded_file($_FILES['dat_file']['tmp_name'], $filePath)) {
        // Import the data from the uploaded file
        $result = importDatFile($filePath);

        // If import was successful, run update.php and redirect
        if (isset($result['status']) && $result['status'] === 'success') {
            // Check if update.php exists before trying to include it
            if (file_exists('update.php')) {
                // Run the update script
                include 'update.php';
                
                // Redirect to the dashboard
                header('Location: dashboard.php');
                exit; // Terminate script execution to ensure redirect happens
            } else {
                // If update.php is not found, show an error on the current page instead of redirecting
                $result['message'] .= "<br><br><hr><strong style='color:red;'>Error: File update.php tidak ditemukan. Tidak dapat melanjutkan ke dashboard.</strong>";
            }
        }
        
        // Optionally delete the file after import
        unlink($filePath);
    } else {
        $result = ["status" => "error", "message" => "Gagal mengunggah file."];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Data dari File .dat</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box; /* Important for padding and width */
        }
        button {
            background-color: #28a745;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        button:hover {
            background-color: #218838;
        }
        .result {
            margin-top: 25px;
            padding: 15px;
            border-radius: 4px;
            word-wrap: break-word;
            line-height: 1.6;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Import Data dari File .dat</h1>
        
        <form action="" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="dat_file">Pilih File .dat:</label>
                <input type="file" name="dat_file" id="dat_file" accept=".dat" required>
            </div>
            <button type="submit">Import Data & Buka Dashboard</button>
        </form>
        
        <?php if (isset($result)): ?>
            <div class="result <?php echo htmlspecialchars($result['status']); ?>">
                <?php echo $result['message']; /* Using br from php, no need for nl2br */ ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
