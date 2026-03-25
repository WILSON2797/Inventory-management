function initPageScripts() {
    console.log("profile.js loaded");
    
    // Re-initialize feather icons setelah konten dimuat
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
    
    // HAPUS DOMContentLoaded - langsung jalankan kode
    // Karena initPageScripts() dipanggil setelah konten dimuat
    
    const uploadForm = document.getElementById('uploadProfileForm');
    const fileInput = document.getElementById('profileImageInput');
    const uploadBtn = document.getElementById('uploadBtn');
    const btnText = document.getElementById('btnText');
    const spinner = document.getElementById('uploadSpinner');
    const profileImage = document.getElementById('profileImage');
    
    // Debug: Cek apakah semua element ada
    console.log('Form element:', uploadForm);
    console.log('File input:', fileInput);
    
    if (!uploadForm) {
        console.error('Form tidak ditemukan!');
        return;
    }
    
    // Hapus event listener lama untuk menghindari duplikasi
    // Clone node untuk remove all event listeners
    const newFileInput = fileInput.cloneNode(true);
    fileInput.parentNode.replaceChild(newFileInput, fileInput);
    
    const newUploadForm = uploadForm.cloneNode(true);
    uploadForm.parentNode.replaceChild(newUploadForm, uploadForm);
    
    // Update reference ke element baru
    const cleanFileInput = document.getElementById('profileImageInput');
    const cleanUploadForm = document.getElementById('uploadProfileForm');
    const cleanUploadBtn = document.getElementById('uploadBtn');
    const cleanBtnText = document.getElementById('btnText');
    const cleanSpinner = document.getElementById('uploadSpinner');
    const cleanProfileImage = document.getElementById('profileImage');
    
    // Preview image saat file dipilih
    cleanFileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        console.log('File selected:', file);
        
        if (file) {
            // Validasi ukuran file (5MB)
            if (file.size > 5 * 1024 * 1024) {
                showErrorToast('Ukuran file terlalu besar! Maksimal 5 MB', 'Error');
                cleanFileInput.value = '';
                return;
            }
            
            // Validasi tipe file
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!allowedTypes.includes(file.type)) {
                showErrorToast('Format file tidak didukung! Gunakan JPG atau PNG', 'Error');
                cleanFileInput.value = '';
                return;
            }
            
            // Preview image
            const reader = new FileReader();
            reader.onload = function(e) {
                cleanProfileImage.src = e.target.result;
            };
            reader.readAsDataURL(file);
            
            // Show info toast
            showInfoToast('File siap untuk diupload', 'Info');
        }
    });
    
    // Handle form submit
    cleanUploadForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        console.log('Form submitted!');
        
        // Validasi apakah file sudah dipilih
        if (!cleanFileInput.files || cleanFileInput.files.length === 0) {
            showWarningToast('Silakan pilih file terlebih dahulu!', 'Peringatan');
            return;
        }
        
        // Prepare form data
        const formData = new FormData();
        formData.append('profile_image', cleanFileInput.files[0]);
        
        console.log('FormData created:', formData.get('profile_image'));
        
        // Show loading state
        cleanUploadBtn.disabled = true;
        cleanBtnText.textContent = 'Uploading...';
        cleanSpinner.style.display = 'inline-block';
        
        // Show loading toast
        showInfoToast('Sedang mengupload foto profil...', 'Mohon Tunggu');
        
        // AJAX request
        fetch('modules/upload_profile.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('Raw response:', text);
            
            try {
                const data = JSON.parse(text);
                console.log('Parsed data:', data);
                
                if (data.success) {
                    clearAllToasts();
                    
                    showSuccessToast(
                        data.message || 'Upload Success!',
                        'Success'
                    );
                    
                    // Update profile image dengan timestamp
                    cleanProfileImage.src = data.imagePath + '?t=' + new Date().getTime();
                    
                    // Update header profile image
                    const headerImg = document.querySelector('#navbarDropdownUserImage img');
                    if (headerImg) {
                        headerImg.src = data.imagePath + '?t=' + new Date().getTime();
                    }
                    
                    // Update dropdown profile image
                    const dropdownImg = document.querySelector('.dropdown-user-img');
                    if (dropdownImg) {
                        dropdownImg.src = data.imagePath + '?t=' + new Date().getTime();
                    }
                    
                    // Reset form
                    cleanFileInput.value = '';
                    
                } else {
                    clearAllToasts();
                    showErrorToast(
                        data.message || 'Gagal upload!',
                        'Error'
                    );
                }
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Response text:', text);
                
                clearAllToasts();
                showErrorToast(
                    'Response tidak valid dari server. Cek console untuk detail.',
                    'Error'
                );
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            clearAllToasts();
            showErrorToast(
                'Terjadi kesalahan koneksi: ' + error.message,
                'Error'
            );
        })
        .finally(() => {
            // Reset loading state
            cleanUploadBtn.disabled = false;
            cleanBtnText.textContent = 'Upload new image';
            cleanSpinner.style.display = 'none';
            
            // Re-initialize feather icons
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        });
    });
}