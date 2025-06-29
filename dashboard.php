<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'config.php';

// Get date range for this month
$currentMonthStart = date('Y-m-01');
$currentMonthEnd = date('Y-m-t');
$today = date('Y-m-d');

// Get statistics
$totalGuru = count(getAllGuru($pdo));
$availableDevices = count(getAvailableDevices($pdo));

// Get today's attendance data
$todayAttendance = getLaporanAbsensi($pdo, $today, $today);

// Count attendance status for today
$hadirToday = 0;
$terlambatToday = 0;
$pulangAwalToday = 0;

foreach ($todayAttendance as $record) {
    if ($record['status_masuk'] === 'Tepat Waktu') $hadirToday++;
    if ($record['status_masuk'] === 'Terlambat') $terlambatToday++;
    if ($record['status_pulang'] === 'Pulang Awal') $pulangAwalToday++;
}

// Get this month's attendance summary
$monthlyAttendance = getLaporanAbsensi($pdo, $currentMonthStart, $currentMonthEnd);

// Prepare monthly summary data
$monthlySummary = [];
foreach ($monthlyAttendance as $record) {
    $date = date('Y-m-d', strtotime($record['timestamp']));
    $guruName = $record['nama_guru'];
    
    if (!isset($monthlySummary[$date][$guruName])) {
        $monthlySummary[$date][$guruName] = [
            'masuk' => '-',
            'pulang' => '-',
            'status_masuk' => '-',
            'status_pulang' => '-'
        ];
    }
    
    // Update earliest arrival
    if ($record['waktu_param1'] !== null && 
        ($monthlySummary[$date][$guruName]['masuk'] === '-' || 
         $record['waktu_param1'] < $monthlySummary[$date][$guruName]['masuk'])) {
        $monthlySummary[$date][$guruName]['masuk'] = $record['waktu_param1'];
        $monthlySummary[$date][$guruName]['status_masuk'] = $record['status_masuk'];
    }
    
    // Update latest departure
    if ($record['waktu_param2'] !== null && 
        ($monthlySummary[$date][$guruName]['pulang'] === '-' || 
         $record['waktu_param2'] > $monthlySummary[$date][$guruName]['pulang'])) {
        $monthlySummary[$date][$guruName]['pulang'] = $record['waktu_param2'];
        $monthlySummary[$date][$guruName]['status_pulang'] = $record['status_pulang'];
    }
}

// Count monthly stats
$totalHadir = 0;
$totalTerlambat = 0;
$totalPulangAwal = 0;

foreach ($monthlySummary as $date => $gurus) {
    foreach ($gurus as $guru) {
        if ($guru['status_masuk'] === 'Tepat Waktu') $totalHadir++;
        if ($guru['status_masuk'] === 'Terlambat') $totalTerlambat++;
        if ($guru['status_pulang'] === 'Pulang Awal') $totalPulangAwal++;
    }
}

// Get recent attendance data
$recentData = getX100CData($pdo, 5);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Sistem Absensi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .table th {
            background-color: var(--primary-color);
            color: white;
        }
        
        .badge-success { background-color: var(--success-color); }
        .badge-warning { background-color: var(--warning-color); }
        .badge-danger { background-color: var(--danger-color); }
        .badge-info { background-color: var(--info-color); }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }
        
        .section-title {
            color: var(--secondary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .recent-activity-item {
            border-left: 3px solid var(--primary-color);
            padding: 10px 15px;
            margin-bottom: 10px;
            background-color: white;
            border-radius: 0 5px 5px 0;
        }
        
        .recent-activity-time {
            font-size: 0.8rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-speedometer2 me-2"></i>Dashboard Absensi
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="import.php"><i class="bi bi-people-fill me-1"></i>Import Data Mesin X100C</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="bi bi-people-fill me-1"></i> Guru</a>
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
        <h2 class="section-title">Ringkasan Hari Ini</h2>
        
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-hadir">
                    <div class="card-body stat-card">
                        <div class="value"><?= $hadirToday ?></div>
                        <div class="label">Hadir Tepat Waktu</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-terlambat">
                    <div class="card-body stat-card">
                        <div class="value"><?= $terlambatToday ?></div>
                        <div class="label">Terlambat</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-pulang-awal">
                    <div class="card-body stat-card">
                        <div class="value"><?= $pulangAwalToday ?></div>
                        <div class="label">Pulang Awal</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-total">
                    <div class="card-body stat-card">
                        <div class="value"><?= $totalGuru ?></div>
                        <div class="label">Total Guru</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-calendar-check me-2"></i>Statistik Bulan Ini (<?= date('F Y') ?>)
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-pie-chart me-2"></i>Persentasi Kehadiran
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="attendancePieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-clock-history me-2"></i>Aktivitas Terkini
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentData)): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>Tidak ada aktivitas terbaru.
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentData as $activity): ?>
                                <div class="recent-activity-item">
                                    <div class="d-flex justify-content-between">
                                        <strong>Device <?= htmlspecialchars($activity['device_id']) ?></strong>
                                        <span class="recent-activity-time">
                                            <?= date('H:i', strtotime($activity['timestamp'])) ?>
                                        </span>
                                    </div>
                                    <div>
                                        <?php if ($activity['jenis_absensi'] === 'Masuk'): ?>
                                            <span class="badge bg-success">Masuk</span>
                                        <?php elseif ($activity['jenis_absensi'] === 'Pulang'): ?>
                                            <span class="badge bg-info">Pulang</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($activity['jenis_absensi'] ?? 'Aktivitas') ?></span>
                                        <?php endif; ?>
                                        - <?= date('d/m/Y', strtotime($activity['timestamp'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-list-check me-2"></i>Kehadiran Hari Ini
                    </div>
                    <div class="card-body">
                        <?php if (empty($todayAttendance)): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>Tidak ada data kehadiran hari ini.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nama Guru</th>
                                            <th>Masuk</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $uniqueGuru = [];
                                        foreach ($todayAttendance as $record) {
                                            if (!isset($uniqueGuru[$record['nama_guru']])) {
                                                $uniqueGuru[$record['nama_guru']] = $record;
                                            } else {
                                                // Keep the earliest arrival time
                                                if ($record['waktu_param1'] < $uniqueGuru[$record['nama_guru']]['waktu_param1']) {
                                                    $uniqueGuru[$record['nama_guru']] = $record;
                                                }
                                            }
                                        }
                                        ?>
                                        <?php foreach ($uniqueGuru as $guru): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($guru['nama_guru']) ?></td>
                                                <td><?= htmlspecialchars($guru['waktu_param1'] ?? '-') ?></td>
                                                <td>
                                                    <?php if ($guru['status_masuk'] === 'Tepat Waktu'): ?>
                                                        <span class="badge bg-success">Tepat Waktu</span>
                                                    <?php elseif ($guru['status_masuk'] === 'Terlambat'): ?>
                                                        <span class="badge bg-warning">Terlambat</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">-</span>
                                                    <?php endif; ?>
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

    <footer class="bg-light mt-5 py-3">
        <div class="container text-center">
            <p class="mb-0 text-muted">© <?= date('Y') ?> Sistem Absensi Guru. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Monthly Attendance Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: ['Hadir', 'Terlambat', 'Pulang Awal'],
                datasets: [{
                    label: 'Statistik Bulanan',
                    data: [<?= $totalHadir ?>, <?= $totalTerlambat ?>, <?= $totalPulangAwal ?>],
                    backgroundColor: [
                        'rgba(46, 204, 113, 0.7)',
                        'rgba(243, 156, 18, 0.7)',
                        'rgba(231, 76, 60, 0.7)'
                    ],
                    borderColor: [
                        'rgba(46, 204, 113, 1)',
                        'rgba(243, 156, 18, 1)',
                        'rgba(231, 76, 60, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Attendance Pie Chart
        const pieCtx = document.getElementById('attendancePieChart').getContext('2d');
        const pieChart = new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: ['Tepat Waktu', 'Terlambat', 'Belum Absen'],
                datasets: [{
                    data: [
                        <?= $hadirToday ?>, 
                        <?= $terlambatToday ?>, 
                        <?= $totalGuru - $hadirToday - $terlambatToday ?>
                    ],
                    backgroundColor: [
                        'rgba(46, 204, 113, 0.7)',
                        'rgba(243, 156, 18, 0.7)',
                        'rgba(149, 165, 166, 0.7)'
                    ],
                    borderColor: [
                        'rgba(46, 204, 113, 1)',
                        'rgba(243, 156, 18, 1)',
                        'rgba(149, 165, 166, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>