<?php
session_start(); // WAJIB!

// Hapus semua session
$_SESSION = array();

// Hapus cookie session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logout</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Import Google Font Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Style responsive untuk SweetAlert dengan font Poppins */
        .swal2-popup {
            font-family: 'Poppins', sans-serif;
            font-size: clamp(12px, 2vw, 14px); /* Ukuran font responsif */
            width: clamp(280px, 90vw, 350px); /* Lebar responsif */
            padding: clamp(0.8rem, 2vw, 1.2rem); /* Padding responsif */
        }
        .swal2-title {
            font-family: 'Poppins', sans-serif;
            font-size: clamp(16px, 3vw, 20px); /* Ukuran judul responsif */
            font-weight: 600; /* Semi-bold untuk judul */
        }
        .swal2-content {
            font-family: 'Poppins', sans-serif;
            font-size: clamp(12px, 2vw, 14px); /* Ukuran teks konten responsif */
            font-weight: 400; /* Regular untuk konten */
        }
        .swal2-confirm {
            font-family: 'Poppins', sans-serif;
            font-size: clamp(12px, 2vw, 14px); /* Ukuran tombol responsif */
            padding: clamp(0.4rem, 1vw, 0.6rem) clamp(0.8rem, 2vw, 1.2rem); /* Padding tombol responsif */
            font-weight: 500; /* Medium untuk tombol */
        }
    </style>
</head>
<body>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Logout Success!',
            text: 'Anda Berhasil Logout.',
            confirmButtonText: 'OK',
            willClose: () => {
                window.location.href = '../login.php'; // Redirect ke halaman login
            }
        });
    </script>
</body>
</html>