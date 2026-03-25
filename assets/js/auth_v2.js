function handleLogin(event) {
    event.preventDefault();

    const username = document.getElementById('login-username').value.trim();
    const password = document.getElementById('login-password').value;

    if (!username || !password) {
        window.showErrorToast('Username dan password wajib diisi!', 'Error');
        return;
    }

    fetch('php/proses_login.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            window.showSuccessToast('Login berhasil!', 'Login Success');
            setTimeout(() => {
                // PAKAI REDIRECT DARI SERVER
                window.location.href = data.redirect || 'select_wh_project.php';
            }, 1000);
        } else {
            window.showErrorToast(data.message || 'Login gagal', 'Error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        window.showErrorToast('Koneksi gagal. Cek internet Anda.', 'Network Error');
    });
}

// Pastikan event listener tetap ada
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
});