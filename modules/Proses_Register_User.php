<?php
session_start();
include '../php/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$response = ['status' => 'error', 'message' => 'Terjadi kesalahan'];

$user_id = $_POST['user_id'] ?? null;
$nama = mysqli_real_escape_string($conn, $_POST['nama']);
$email = mysqli_real_escape_string($conn, $_POST['email']);
$username = mysqli_real_escape_string($conn, $_POST['username']);
$wh_names = $_POST['wh_names'] ?? [];
$project_names = $_POST['project_names'] ?? [];

// Validasi
if (empty($wh_names) || empty($project_names)) {
    echo json_encode(['status' => 'error', 'message' => 'Pilih minimal 1 WH dan 1 Project']);
    exit();
}

// Cek email/username unik (kecuali edit)
$check = "SELECT id FROM data_username WHERE (email = '$email' OR username = '$username')";
if ($user_id) $check .= " AND id != $user_id";
$result = $conn->query($check);
if ($result->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Email atau username sudah digunakan']);
    exit();
}

$conn->begin_transaction();

try {
    if ($user_id) {
        // EDIT USER
        $sql = "UPDATE data_username SET nama='$nama', email='$email', username='$username' WHERE id=$user_id";
        $conn->query($sql);

        // Hapus akses lama
        $conn->query("DELETE FROM user_warehouses WHERE user_id=$user_id");
        $conn->query("DELETE FROM user_projects WHERE user_id=$user_id");
    } else {
        // TAMBAH USER
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql = "INSERT INTO data_username (nama, email, username, password, role, is_active) 
                VALUES ('$nama', '$email', '$username', '$password', 'user', 1)";
        $conn->query($sql);
        $user_id = $conn->insert_id;
    }

    // Insert WH
    foreach ($wh_names as $wh_name) {
        $wh_name = mysqli_real_escape_string($conn, $wh_name);
        $q = $conn->query("SELECT wh_id FROM warehouses WHERE wh_name = '$wh_name'");
        if ($q->num_rows > 0) {
            $wh_id = $q->fetch_assoc()['wh_id'];
            $conn->query("INSERT INTO user_warehouses (user_id, wh_id, wh_name) VALUES ($user_id, '$wh_id', '$wh_name')");
        }
    }

    // Insert Project
    foreach ($project_names as $proj_name) {
        $proj_name = mysqli_real_escape_string($conn, $proj_name);
        $q = $conn->query("SELECT Project_Code FROM project_name WHERE Project_Name = '$proj_name'");
        if ($q->num_rows > 0) {
            $proj_code = $q->fetch_assoc()['Project_Code'];
            $conn->query("INSERT INTO user_projects (user_id, project_code, project_name) VALUES ($user_id, '$proj_code', '$proj_name')");
        }
    }

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'User berhasil disimpan']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Gagal: ' . $e->getMessage()]);
}

$conn->close();
?>