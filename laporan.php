<?php
// laporan.php

// Memasukkan file konfigurasi dan semua fungsinya
require_once 'config.php';

// Menentukan rentang tanggal. Defaultnya adalah bulan ini.
$tanggal_awal = $_GET['tanggal_awal'] ?? date('Y-m-01');
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-t');

// Mengambil data mentah dari database menggunakan fungsi yang ada di config.php
$laporan_data = getLaporanAbsensi($pdo, $tanggal_awal, $tanggal_akhir);

// Mengolah dan mengelompokkan data per guru per tanggal
$laporan_cetak = [];
if (!empty($laporan_data)) {
    foreach ($laporan_data as $row) {
        // Membuat kunci unik untuk setiap guru pada tanggal tertentu
        $tanggal_key = date('Y-m-d', strtotime($row['timestamp']));
        $key = $tanggal_key . '-' . $row['nama_guru'];

        // Jika kunci belum ada, buat entri baru
        if (!isset($laporan_cetak[$key])) {
            $laporan_cetak[$key] = [
                'tanggal' => $tanggal_key,
                'nama_guru' => $row['nama_guru'],
                'masuk' => '-',
                'pulang' => '-',
                'status_masuk' => '-',
                'status_pulang' => '-',
                'jam_kerja' => $row['nama_jam_kerja'] ?? '-'
            ];
        }

        // Mengisi data jam masuk (berdasarkan param1)
        if ($row['waktu_param1'] !== null) {
            // Ambil waktu masuk yang paling pagi
            if ($laporan_cetak[$key]['masuk'] == '-' || $row['waktu_param1'] < $laporan_cetak[$key]['masuk']) {
                $laporan_cetak[$key]['masuk'] = $row['waktu_param1'];
                $laporan_cetak[$key]['status_masuk'] = $row['status_masuk'];
            }
        }

        // Mengisi data jam pulang (berdasarkan param2)
        if ($row['waktu_param2'] !== null) {
            // Ambil waktu pulang yang paling akhir
            if ($laporan_cetak[$key]['pulang'] == '-' || $row['waktu_param2'] > $laporan_cetak[$key]['pulang']) {
                $laporan_cetak[$key]['pulang'] = $row['waktu_param2'];
                $laporan_cetak[$key]['status_pulang'] = $row['status_pulang'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Absensi</title>
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
        
        .table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        
        .table th, .table td { 
            border: 1px solid #dee2e6; 
            padding: 8px; 
            text-align: center; 
            vertical-align: middle; 
        }
        
        .table th { 
            background-color: var(--primary-color);
            color: white;
            font-weight: bold;
        }
        
        .tepat-waktu { color: var(--success-color); font-weight: bold; }
        .terlambat { color: var(--warning-color); font-weight: bold; }
        .pulang-awal { color: var(--danger-color); font-weight: bold; }
        
        .header { 
            text-align: center; 
            margin-bottom: 20px;
            padding: 15px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header h2 { 
            color: var(--secondary-color);
            margin-bottom: 5px; 
        }
        
        .header h4 {
            color: var(--primary-color);
        }
        
        @media print {
            .no-print { display: none; }
            body { padding: 0; background-color: white; }
            .header { box-shadow: none; }
            .table th { background-color: var(--primary-color) !important; color: white !important; }
            .table td, .table th { border-color: #ddd !important; }
        }
        
        .filter-container {
            background-color: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .section-title {
            color: var(--secondary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-file-earmark-text me-2"></i>Laporan Absensi
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
                        <a class="nav-link" href="absensi.php"><i class="bi bi-clock me-1"></i> Absensi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="laporan.php"><i class="bi bi-file-earmark-text me-1"></i> Laporan</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="header">
            <h2>LAPORAN ABSENSI GURU</h2>
            <h4>Periode: <?= htmlspecialchars(date('d-m-Y', strtotime($tanggal_awal))) ?> s/d <?= htmlspecialchars(date('d-m-Y', strtotime($tanggal_akhir))) ?></h4>
        </div>
        
        <div class="filter-container mb-4 no-print">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <label for="tanggal_awal" class="form-label">Tanggal Awal</label>
                    <input type="date" class="form-control" id="tanggal_awal" name="tanggal_awal"
                           value="<?= htmlspecialchars($tanggal_awal) ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="tanggal_akhir" class="form-label">Tanggal Akhir</label>
                    <input type="date" class="form-control" id="tanggal_akhir" name="tanggal_akhir"
                           value="<?= htmlspecialchars($tanggal_akhir) ?>" required>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <a href="laporan.php" class="btn btn-secondary me-2">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </a>
                    <button onclick="window.print()" class="btn btn-success">
                        <i class="bi bi-printer me-1"></i> Cetak
                    </button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="12%">Tanggal</th>
                        <th width="20%">Nama Guru</th>
                        <th width="12%">Waktu Masuk</th>
                        <th width="12%">Waktu Pulang</th>
                        <th width="12%">Status Masuk</th>
                        <th width="12%">Status Pulang</th>
                        <th width="15%">Jam Kerja</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($laporan_cetak)): ?>
                        <tr>
                            <td colspan="8" class="text-center">Tidak ada data absensi untuk periode ini.</td>
                        </tr>
                    <?php else: ?>
                        <?php $counter = 1; ?>
                        <?php foreach ($laporan_cetak as $item): ?>
                            <tr>
                                <td><?= $counter++ ?></td>
                                <td><?= htmlspecialchars(date('d-m-Y', strtotime($item['tanggal']))) ?></td>
                                <td style="text-align: left; padding-left: 10px;"><?= htmlspecialchars($item['nama_guru']) ?></td>
                                <td><?= htmlspecialchars($item['masuk']) ?></td>
                                <td><?= htmlspecialchars($item['pulang']) ?></td>
                                <td>
                                    <?php if ($item['status_masuk'] == 'Tepat Waktu'): ?>
                                        <span class="tepat-waktu">Tepat Waktu</span>
                                    <?php elseif ($item['status_masuk'] == 'Terlambat'): ?>
                                        <span class="terlambat">Terlambat</span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($item['status_pulang'] == 'Tepat Waktu'): ?>
                                        <span class="tepat-waktu">Tepat Waktu</span>
                                    <?php elseif ($item['status_pulang'] == 'Pulang Awal'): ?>
                                        <span class="pulang-awal">Pulang Awal</span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars(str_replace('.', ' ', $item['jam_kerja'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer class="bg-light mt-5 py-3">
        <div class="container text-center">
            <p class="mb-0 text-muted">© <?= date('Y') ?> Sistem Absensi Guru. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>