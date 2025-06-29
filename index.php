<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    require 'config.php';
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_guru = trim($_POST['nama_guru'] ?? '');
    $device_id = trim($_POST['device_id'] ?? '');
    
    if (!empty($nama_guru) && !empty($device_id)) {
        $result = tambahGuru($pdo, $nama_guru, $device_id);
        
        if ($result === true) {
            $success = "Guru berhasil ditambahkan!";
            // Refresh data after successful addition
            $availableDevices = getAvailableDevices($pdo);
            $allGuru = getAllGuru($pdo);
        } elseif ($result === "duplicate_device") {
            $error = "Device ID $device_id sudah digunakan oleh guru lain!";
        } else {
            $error = "Gagal menambahkan guru! Silakan coba lagi.";
        }
    } else {
        $error = "Nama guru dan device ID harus diisi!";
    }
}

$availableDevices = getAvailableDevices($pdo);
$allGuru = getAllGuru($pdo);
$x100cData = getX100CData($pdo, 50);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Guru dan Device</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }
        
        .navbar-brand {
            font-weight: 700;
            color: var(--secondary-color);
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            font-weight: 600;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            background-color: var(--primary-color);
            color: white;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.1);
        }
        
        .badge-success {
            background-color: var(--success-color);
        }
        
        .badge-danger {
            background-color: var(--danger-color);
        }
        
        .scrollable-table {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }
        
        .alert {
            border-radius: 8px;
        }
        
        .no-device {
            color: var(--danger-color);
            font-style: italic;
        }
        
        .section-title {
            color: var(--secondary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .action-buttons .btn {
            margin-right: 5px;
        }
        
        .device-status {
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-people-fill me-2"></i>Sistem Manajemen Guru
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php"><i class="bi bi-house-door me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="absensi.php"><i class="bi bi-clock me-1"></i> Absensi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="laporan.php"><i class="bi bi-file-earmark-text me-1"></i> Laporan</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Notifikasi -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-person-plus me-2"></i>Tambah Guru Baru</span>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="mb-3">
                                <label for="nama_guru" class="form-label">Nama Guru <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nama_guru" name="nama_guru" required
                                       value="<?= isset($_POST['nama_guru']) ? htmlspecialchars($_POST['nama_guru']) : '' ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="device_id" class="form-label">Device ID <span class="text-danger">*</span></label>
                                <?php if (!empty($availableDevices)): ?>
                                    <select class="form-select" id="device_id" name="device_id" required>
                                        <option value="">Pilih Device ID</option>
                                        <?php foreach ($availableDevices as $device): ?>
                                            <option value="<?= htmlspecialchars($device['device_id']) ?>"
                                                <?= (isset($_POST['device_id']) && $_POST['device_id'] == $device['device_id']) ? 'selected' : '' ?>>
                                                Device <?= htmlspecialchars($device['device_id']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="device-status text-muted mt-1">
                                        Tersedia: <?= count($availableDevices) ?> device
                                    </div>
                                <?php else: ?>
                                    <p class="no-device mb-2"><i class="bi bi-exclamation-circle me-1"></i>Tidak ada device tersedia. Pastikan tabel x100c_data berisi data.</p>
                                    <input type="number" class="form-control" id="device_id" name="device_id" required 
                                           placeholder="Masukkan device ID manual"
                                           value="<?= isset($_POST['device_id']) ? htmlspecialchars($_POST['device_id']) : '' ?>">
                                <?php endif; ?>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i>Simpan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <span><i class="bi bi-info-circle me-2"></i>Statistik Sistem</span>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-6 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary">Total Guru</h5>
                                        <p class="card-text display-6"><?= count($allGuru) ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary">Device Tersedia</h5>
                                        <p class="card-text display-6"><?= count($availableDevices) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-people-fill me-2"></i>Daftar Guru</span>
                        <span class="badge bg-primary">Total: <?= count($allGuru) ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($allGuru)): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>Belum ada data guru. Silakan tambahkan guru baru.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Guru</th>
                                            <th>Device ID</th>
                                            <th>Status Device</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allGuru as $index => $guru): ?>
                                            <tr>
                                                <td><?= $index + 1 ?></td>
                                                <td><?= htmlspecialchars($guru['nama_guru']) ?></td>
                                                <td>
                                                    <span class="badge bg-primary"><?= htmlspecialchars($guru['device_id']) ?></span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $deviceUsed = false;
                                                    foreach ($x100cData as $data) {
                                                        if ($data['device_id'] == $guru['device_id']) {
                                                            $deviceUsed = true;
                                                            break;
                                                        }
                                                    }
                                                    if ($deviceUsed): ?>
                                                        <span class="badge bg-success">Aktif</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark">Belum digunakan</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="action-buttons">
                                                    <a href="edit_guru.php?id=<?= $guru['id'] ?>" class="btn btn-sm btn-warning" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-danger" title="Hapus" onclick="confirmDelete(<?= $guru['id'] ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        
        </div>
    </div>

    <footer class="bg-light mt-5 py-3">
        <div class="container text-center">
            <p class="mb-0 text-muted">© <?= date('Y') ?> Sistem Manajemen Guru. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Confirm before deleting
        function confirmDelete(guruId) {
            if (confirm('Apakah Anda yakin ingin menghapus guru ini?')) {
                window.location.href = 'hapus_guru.php?id=' + guruId;
            }
        }
        
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>