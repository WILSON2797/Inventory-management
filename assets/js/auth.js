function handleLogin(event) {
    event.preventDefault();

    const username = document.getElementById('login-username').value.trim();
    const password = document.getElementById('login-password').value;
    const csrfToken = document.querySelector('input[name="csrf_token"]').value;

    // Validasi basic
    if (!username || !password) {
        window.showErrorToast('Username dan password wajib diisi!', 'Error');
        return;
    }

    if (!csrfToken) {
        window.showErrorToast('Token tidak valid. Refresh halaman.', 'Error');
        return;
    }

    // Disable button
    const submitButton = event.target.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.disabled = true;
    submitButton.textContent = 'Memproses...';

    // Kirim data
    const formData = new URLSearchParams();
    formData.append('username', username);
    formData.append('password', password);
    formData.append('csrf_token', csrfToken);

    fetch('php/proses_login.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            window.showSuccessToast('Login berhasil!', 'Success');
            setTimeout(() => {
                window.location.href = data.redirect || 'select_wh_project.php';
            }, 1000);
        } else {
            window.showErrorToast(data.message || 'Login gagal', 'Error');
            submitButton.disabled = false;
            submitButton.textContent = originalText;
            document.getElementById('login-password').value = '';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        window.showErrorToast('Koneksi gagal. Cek internet Anda.', 'Error');
        submitButton.disabled = false;
        submitButton.textContent = originalText;
    });
}

// Event listener
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
});