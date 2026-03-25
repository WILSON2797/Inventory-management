// Fungsi global untuk loading spinner (start)
function showLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.classList.add('show');
        console.log('✅ Loading spinner ditampilkan');
    } else {
        console.error('❌ Element #loadingOverlay tidak ditemukan!');
    }
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.classList.remove('show');
        console.log('✅ Loading spinner disembunyikan');
    } else {
        console.error('❌ Element #loadingOverlay tidak ditemukan!');
    }
}

// Opsional: Fungsi untuk mengubah kecepatan spinner
function changeSpeed(speed) {
    const spinner = document.querySelector(".spinner");
    if (!spinner) {
        console.warn('⚠️ Element .spinner tidak ditemukan!');
        return;
    }
    
    if (speed === "slow") {
        spinner.style.animation = "spin 4s linear infinite";
    } else if (speed === "normal") {
        spinner.style.animation = "spin 2s linear infinite";
    } else if (speed === "fast") {
        spinner.style.animation = "spin 1s linear infinite";
    }
}

// Opsional: Fungsi untuk mengubah teks
const texts = [
    "Loading...",
    "Please wait...",
    "Processing...",
    "Almost there...",
    "Just a moment...",
];
let currentText = 0;

function changeText() {
    const loadingText = document.getElementById("loading-text");
    if (!loadingText) {
        console.warn('⚠️ Element #loading-text tidak ditemukan!');
        return;
    }
    currentText = (currentText + 1) % texts.length;
    loadingText.textContent = texts[currentText];
}

// Log saat file berhasil dimuat
console.log('✅ spinner.js berhasil dimuat!');

// fungsi loading spinner (end)