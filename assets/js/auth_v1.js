// Fungsi untuk menangani login
function handleLogin(event) {
    event.preventDefault();

    const username = document.getElementById('login-username').value;
    const password = document.getElementById('login-password').value;

    fetch('php/proses_login', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`,
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            window.showSuccessToast('Anda Berhasil Login!', 'You’re successfully signed in');
            setTimeout(() => {
                window.location.href = 'index'; // Redirect ke halaman utama setelah 1 detik
            }, 1000);
        } else {
            window.showErrorToast(data.message || 'Gagal login. Silakan cek kredensial Anda.', 'Login Failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        window.showErrorToast(error.message || 'A system error occurred', 'Error');
    });
}

// Tambahkan event listener untuk form login dan sign up
document.getElementById('loginForm').addEventListener('submit', handleLogin);
document.getElementById('signupForm').addEventListener('submit', handleSignUp);
