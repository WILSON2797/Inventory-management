<?php
// Pastikan file hanya bisa dijalankan melalui CLI
if (php_sapi_name() !== 'cli') {
    header('HTTP/1.0 403 Forbidden');
    echo "Access denied: This script can only be run from the command line.";
    exit(1);
}
// Koneksi ke database
require_once __DIR__ . '/php/config.php';
// Direktori uploads
$uploadDir = '/home/u272899607/domains/fislogapps.com/public_html/inventory/Uploads/';
$reportDir = '/home/u272899607/domains/fislogapps.com/public_html/inventory/Uploads/reports/';
try {
    // ========== CLEANUP FILE_PATH ==========
    echo "Memulai cleanup file_path...\n";
    
    $stmt = $pdo->prepare("
        SELECT file_path 
        FROM queue_task 
        WHERE status IN ('success', 'error') 
        AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND file_path IS NOT NULL
        AND file_path != ''
    ");
    $stmt->execute();
    $filesToDelete = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $deletedCount = 0;
    
    foreach ($filesToDelete as $file) {
        $filePath = $file['file_path'];
        
        // Pastikan path adalah absolut
        if (str_starts_with($filePath, '/home/u272899607')) {
            $absoluteFilePath = $filePath;
        } else {
            $absoluteFilePath = $uploadDir . ltrim($filePath, '/');
        }
        
        // Validasi path untuk keamanan (prevent directory traversal)
        $realPath = realpath($absoluteFilePath);
        if ($realPath && str_starts_with($realPath, $uploadDir) && file_exists($realPath)) {
            if (unlink($realPath)) {
                $deletedCount++;
                echo "File dihapus: $realPath\n";
                error_log("File lama dihapus (usia > 30 hari): $realPath");
            } else {
                error_log("Gagal menghapus file: $realPath");
            }
        }
    }
    
    echo "Total file dari database dihapus: $deletedCount\n\n";
    
    // ========== CLEANUP REPORT_PATH ==========
    echo "Memulai cleanup report_path...\n";
    
    $stmt = $pdo->prepare("
        SELECT report_path 
        FROM queue_task 
        WHERE status IN ('success', 'error') 
        AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND report_path IS NOT NULL
        AND report_path != ''
    ");
    $stmt->execute();
    $reportsToDelete = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Ditemukan " . count($reportsToDelete) . " file report dalam database untuk dihapus\n";
    
    $deletedReportCount = 0;
    
    foreach ($reportsToDelete as $report) {
        $reportPath = $report['report_path'];
        
        // Pastikan path adalah absolut
        if (str_starts_with($reportPath, '/home/u272899607')) {
            $absoluteReportPath = $reportPath;
        } else {
            // Jika relative path, gabungkan dengan report directory
            $absoluteReportPath = $reportDir . ltrim($reportPath, '/');
        }
        
        // Validasi path untuk keamanan (prevent directory traversal)
        $realPath = realpath($absoluteReportPath);
        if ($realPath && str_starts_with($realPath, $reportDir) && file_exists($realPath)) {
            if (unlink($realPath)) {
                $deletedReportCount++;
                echo "File report dihapus: " . basename($realPath) . "\n";
                error_log("File report dihapus (usia > 30 hari): $realPath");
            } else {
                echo "Gagal menghapus report: " . basename($realPath) . "\n";
                error_log("Gagal menghapus file report: $realPath");
            }
        }
    }
    
    echo "Total file report dari database dihapus: $deletedReportCount\n\n";
    
    // ========== CLEANUP ORPHANED FILES (UPLOADS) ==========
    echo "Memulai cleanup orphaned files (uploads)...\n";
    
    $filesInDir = glob($uploadDir . '*');
    $orphanedCount = 0;
    
    if ($filesInDir !== false) {
        foreach ($filesInDir as $file) {
            // Skip direktori reports
            if (is_dir($file) && basename($file) === 'reports') {
                continue;
            }
            
            if (is_file($file) && (time() - filemtime($file)) > (30 * 24 * 60 * 60)) {
                $basename = basename($file);
                
                // Cek apakah file terkait dengan job
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) 
                    FROM queue_task 
                    WHERE file_path LIKE ?
                ");
                $stmt->execute(['%' . $basename . '%']);
                
                if ($stmt->fetchColumn() == 0) {
                    if (unlink($file)) {
                        $orphanedCount++;
                        echo "File orphaned dihapus: $file\n";
                        error_log("File orphaned dihapus (usia > 30 hari): $file");
                    } else {
                        error_log("Gagal menghapus file orphaned: $file");
                    }
                }
            }
        }
    }
    
    echo "Total file orphaned (uploads) dihapus: $orphanedCount\n\n";
    
    // ========== CLEANUP ORPHANED REPORTS ==========
    echo "Memulai cleanup orphaned reports...\n";
    
    $reportsInDir = glob($reportDir . '*');
    $orphanedReportCount = 0;
    
    if ($reportsInDir !== false) {
        foreach ($reportsInDir as $file) {
            if (is_file($file) && (time() - filemtime($file)) > (30 * 24 * 60 * 60)) {
                $basename = basename($file);
                
                // Cek apakah report terkait dengan job
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) 
                    FROM queue_task 
                    WHERE report_path LIKE ?
                ");
                $stmt->execute(['%' . $basename . '%']);
                
                if ($stmt->fetchColumn() == 0) {
                    if (unlink($file)) {
                        $orphanedReportCount++;
                        echo "Report orphaned dihapus: $file\n";
                        error_log("Report orphaned dihapus (usia > 30 hari): $file");
                    } else {
                        error_log("Gagal menghapus report orphaned: $file");
                    }
                }
            }
        }
    }
    
    echo "Total report orphaned dihapus: $orphanedReportCount\n\n";
    
    // ========== SUMMARY ==========
    $totalDeleted = $deletedCount + $deletedReportCount + $orphanedCount + $orphanedReportCount;
    echo "========================================\n";
    echo "RINGKASAN:\n";
    echo "- File upload (database): $deletedCount\n";
    echo "- File report (database): $deletedReportCount\n";
    echo "- Orphaned uploads: $orphanedCount\n";
    echo "- Orphaned reports: $orphanedReportCount\n";
    echo "- TOTAL: $totalDeleted file dihapus\n";
    echo "========================================\n";
    echo "Proses selesai.\n";
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo "Error: Gagal mengakses database\n";
    exit(1);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
exit(0);
?>