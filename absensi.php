<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/config.php';

// Tambah Jam Kerja
if (isset($_POST['tambah_jam_kerja'])) {
    $nama = trim($_POST['nama_jam_kerja'] ?? '');
    $jam_masuk = trim($_POST['jam_masuk'] ?? '');
    $jam_pulang = trim($_POST['jam_pulang'] ?? '');
    $toleransi = (int)($_POST['toleransi'] ?? 0);
    
    if (!empty($nama) && !empty($jam_masuk) && !empty($jam_pulang)) {
        $result = tambahJamKerja($pdo, $nama, $jam_masuk, $jam_pulang, $toleransi);
        
        if ($result) {
            $_SESSION['success'] = "Jam kerja berhasil ditambahkan!";
        } else {
            $_SESSION['error'] = "Gagal menambahkan jam kerja!";
        }
    } else {
        $_SESSION['error'] = "Semua field harus diisi!";
    }
    
    header("Location: absensi.php");
    exit();
}

// Atur Jam Kerja Guru
if (isset($_POST['atur_jam_kerja'])) {
    $guru_id = (int)($_POST['guru_id'] ?? 0);
    $jam_kerja_id = (int)($_POST['jam_kerja_id'] ?? 0);
    
    if ($guru_id > 0) {
        $result = updateJamKerjaGuru($pdo, $guru_id, $jam_kerja_id > 0 ? $jam_kerja_id : null);
        
        if ($result) {
            $_SESSION['success'] = "Jam kerja guru berhasil diupdate!";
        } else {
            $_SESSION['error'] = "Gagal mengupdate jam kerja guru!";
        }
    } else {
        $_SESSION['error'] = "Guru tidak valid!";
    }
    
    header("Location: absensi.php");
    exit();
}

// Ambil data
$allGuru = getAllGuru($pdo);
$allJamKerja = getAllJamKerja($pdo);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Absensi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #1abc9c;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
            padding-top: 0;
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
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            font-weight: 600;
        }
        
        .table th {
            background-color: var(--primary-color);
            color: white;
        }
        
        .badge-success { background-color: var(--success-color); }
        .badge-warning { background-color: var(--warning-color); }
        .badge-danger { background-color: var(--danger-color); }
        .badge-info { background-color: var(--info-color); }
        
        .section-title {
            color: var(--secondary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            text-align: center;
            padding: 15px 0;
        }
        
        .stat-card .value {
            font-size: 2.5rem;
            font-weight: 700;
        }
        
        .stat-card .label {
            font-size: 1rem;
            color: #6c757d;
        }
        
        .bg-hadir { background-color: var(--success-color); }
        .bg-terlambat { background-color: var(--warning-color); }
        .bg-pulang-awal { background-color: var(--danger-color); }
        .bg-total { background-color: var(--primary-color); }
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-clock me-2"></i>Kelola Absensi
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="bi bi-people-fill me-1"></i> Guru</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="absensi.php"><i class="bi bi-clock me-1"></i> Absensi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="laporan.php"><i class="bi bi-file-earmark-text me-1"></i> Laporan</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <h2 class="section-title">Kelola Jam Kerja</h2>

        <div class="card">
            <div class="card-header">
                <i class="bi bi-plus-circle me-2"></i>Tambah Jam Kerja
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="nama_jam_kerja" class="form-label">Nama Jam Kerja</label>
                            <input type="text" class="form-control" id="nama_jam_kerja" name="nama_jam_kerja" required>
                        </div>
                        <div class="col-md-2">
                            <label for="jam_masuk" class="form-label">Jam Masuk</label>
                            <input type="time" class="form-control" id="jam_masuk" name="jam_masuk" required>
                        </div>
                        <div class="col-md-2">
                            <label for="jam_pulang" class="form-label">Jam Pulang</label>
                            <input type="time" class="form-control" id="jam_pulang" name="jam_pulang" required>
                        </div>
                        <div class="col-md-2">
                            <label for="toleransi" class="form-label">Toleransi (menit)</label>
                            <input type="number" class="form-control" id="toleransi" name="toleransi" value="0" min="0">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100" name="tambah_jam_kerja">
                                <i class="bi bi-save me-1"></i> Simpan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-list-check me-2"></i>Daftar Jam Kerja
                        </div>
                        <span class="badge bg-light text-dark"><?= count($allJamKerja) ?></span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Jam Masuk</th>
                                        <th>Jam Pulang</th>
                                        <th>Toleransi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allJamKerja as $jk): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($jk['nama_jam_kerja']) ?></td>
                                        <td><?= $jk['jam_masuk'] ?></td>
                                        <td><?= $jk['jam_pulang'] ?></td>
                                        <td><?= $jk['toleransi_keterlambatan'] ?> menit</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-people-fill me-2"></i>Atur Jam Kerja Guru
                        </div>
                        <span class="badge bg-light text-dark"><?= count($allGuru) ?></span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nama Guru</th>
                                        <th>Device ID</th>
                                        <th>Jam Kerja</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allGuru as $guru): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($guru['nama_guru']) ?></td>
                                        <td><?= $guru['device_id'] ?></td>
                                        <td><?= $guru['nama_jam_kerja'] ?? 'Belum diatur' ?></td>
                                        <td>
                                            <form method="post" class="d-flex gap-2">
                                                <input type="hidden" name="guru_id" value="<?= $guru['id'] ?>">
                                                <select name="jam_kerja_id" class="form-select form-select-sm">
                                                    <option value="">-- Pilih --</option>
                                                    <?php foreach ($allJamKerja as $jk): ?>
                                                    <option value="<?= $jk['id'] ?>" <?= ($guru['jam_kerja_id'] == $jk['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($jk['nama_jam_kerja']) ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-primary" name="atur_jam_kerja">
                                                    <i class="bi bi-check"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="bi bi-file-earmark-text me-2"></i>Laporan Absensi
            </div>
            <div class="card-body">
                <form method="get" action="laporan.php" class="row g-3">
                    <div class="col-md-4">
                        <label for="tanggal_awal" class="form-label">Tanggal Awal</label>
                        <input type="date" class="form-control" id="tanggal_awal" name="tanggal_awal" required>
                    </div>
                    <div class="col-md-4">
                        <label for="tanggal_akhir" class="form-label">Tanggal Akhir</label>
                        <input type="date" class="form-control" id="tanggal_akhir" name="tanggal_akhir" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-file-earmark-text me-1"></i> Lihat Laporan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer class="bg-light mt-5 py-3">
        <div class="container text-center">
            <p class="mb-0 text-muted">© <?= date('Y') ?> Sistem Absensi Guru. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set default dates for report
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('tanggal_awal').value = today;
            document.getElementById('tanggal_akhir').value = today;
        });
    </script>
</body>
</html>