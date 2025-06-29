<?php
require_once 'config.php';

try {
    // Mulai transaksi
    $pdo->beginTransaction();

    // Update param1=0 jika param2=1 (tambahan baru)
    $updateParam1FromParam2 = $pdo->prepare("
        UPDATE x100c_data 
        SET param1 = 0 
        WHERE param2 = 1
    ");
    $updateParam1FromParam2->execute();
    $affectedParam1FromParam2 = $updateParam1FromParam2->rowCount();

    // Update param1=0 jika param1=1 dan param2=1 (yang sudah ada)
    $updateParam1 = $pdo->prepare("
        UPDATE x100c_data 
        SET param1 = 0 
        WHERE param1 = 1 AND param2 = 1
    ");
    $updateParam1->execute();
    $affectedParam1 = $updateParam1->rowCount();

    // Update data dengan param1=1 dan param2=0 untuk menandai sebagai 'Masuk'
    $updateMasuk = $pdo->prepare("
        UPDATE x100c_data 
        SET jenis_absensi = 'Masuk', keterangan = 'Masuk' 
        WHERE param1 = 1 AND param2 = 0 AND jenis_absensi IS NULL
    ");
    $updateMasuk->execute();
    $affectedMasuk = $updateMasuk->rowCount();

    // --- PERUBAHAN DIMULAI DI SINI ---
    // Update data dengan param2=1 untuk menandai sebagai 'Pulang', sesuai permintaan.
    // Kondisi 'param1 = 1' dihapus untuk memastikan query ini berjalan setelah query di atas.
    $updatePulang = $pdo->prepare("
        UPDATE x100c_data 
        SET jenis_absensi = 'Pulang', keterangan = 'Pulang' 
        WHERE param2 = 1 AND jenis_absensi IS NULL
    ");
    // --- PERUBAHAN SELESAI ---
    $updatePulang->execute();
    $affectedPulang = $updatePulang->rowCount();

    // Commit transaksi
    $pdo->commit();

    // Respon JSON
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => "Update berhasil",
        'details' => [
            'param1_from_param2_updates' => $affectedParam1FromParam2,
            'param1_updates' => $affectedParam1,
            'masuk' => $affectedMasuk,
            'pulang' => $affectedPulang,
            'total' => $affectedParam1FromParam2 + $affectedParam1 + $affectedMasuk + $affectedPulang
        ]
    ]);

} catch (PDOException $e) {
    // Rollback jika terjadi error
    $pdo->rollBack();
    
    // Respon error
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal update data absensi: ' . $e->getMessage()
    ]);
}
?>