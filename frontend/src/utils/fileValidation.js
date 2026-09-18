/**
 * Validasi File Upload — Frontend Utilities
 * Validasi file sebelum dikirim ke server untuk user experience yang lebih baik.
 */

// Konfigurasi preset sesuai dengan backend ($upload_presets di MY_Controller.php)
const UPLOAD_PRESETS = {
  image: {
    allowedTypes: ['image/jpeg', 'image/png', 'image/webp'],
    allowedExts: ['.jpg', '.jpeg', '.png', '.webp'],
    maxSizeMB: 5,
    label: 'foto/gambar (JPG, PNG, WebP)'
  },
  image_gif: {
    allowedTypes: ['image/gif', 'image/jpeg', 'image/png', 'image/webp'],
    allowedExts: ['.gif', '.jpg', '.jpeg', '.png', '.webp'],
    maxSizeMB: 5,
    label: 'foto/gambar (JPG, PNG, WebP, GIF)'
  },
  certificate: {
    allowedTypes: ['application/pdf', 'image/jpeg', 'image/png'],
    allowedExts: ['.pdf', '.jpg', '.jpeg', '.png'],
    maxSizeMB: 10,
    label: 'sertifikat (PDF, JPG, PNG)'
  },
  logo: {
    allowedTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'],
    allowedExts: ['.jpg', '.jpeg', '.png', '.webp', '.svg'],
    maxSizeMB: 3,
    label: 'logo (JPG, PNG, WebP, SVG)'
  }
};

/**
 * Validasi file sebelum upload
 * @param {File} file - File object dari input[type="file"]
 * @param {string} preset - Nama preset ('image', 'image_gif', 'certificate', 'logo')
 * @returns {{ valid: boolean, error: string|null }}
 */
export function validateFile(file, preset = 'image') {
  if (!file) {
    return { valid: true, error: null }; // No file = skip
  }

  const config = UPLOAD_PRESETS[preset];
  if (!config) {
    return { valid: false, error: 'Konfigurasi upload tidak valid.' };
  }

  // 1. Validasi ukuran file
  const fileSizeMB = file.size / (1024 * 1024);
  if (fileSizeMB > config.maxSizeMB) {
    return {
      valid: false,
      error: `Ukuran file terlalu besar (${fileSizeMB.toFixed(1)}MB). Maksimal ${config.maxSizeMB}MB.`
    };
  }

  // 2. Validasi tipe MIME
  if (!config.allowedTypes.includes(file.type)) {
    return {
      valid: false,
      error: `Tipe file "${file.type || 'tidak dikenal'}" tidak diizinkan. Format yang diperbolehkan: ${config.label}.`
    };
  }

  // 3. Validasi ekstensi file
  const ext = '.' + file.name.split('.').pop().toLowerCase();
  if (!config.allowedExts.includes(ext)) {
    return {
      valid: false,
      error: `Ekstensi file "${ext}" tidak diizinkan. Format yang diperbolehkan: ${config.allowedExts.join(', ')}.`
    };
  }

  // 4. Cek apakah file tidak kosong (0 bytes)
  if (file.size === 0) {
    return { valid: false, error: 'File kosong (0 bytes). Silakan pilih file lain.' };
  }

  return { valid: true, error: null };
}

/**
 * Mendapatkan string accept untuk input[type="file"]
 * @param {string} preset - Nama preset
 * @returns {string} Accept string (e.g. "image/jpeg,image/png,image/webp")
 */
export function getAcceptString(preset = 'image') {
  const config = UPLOAD_PRESETS[preset];
  if (!config) return '*/*';
  return config.allowedTypes.join(',');
}

/**
 * Mendapatkan ukuran maks dalam MB
 * @param {string} preset - Nama preset
 * @returns {number} Ukuran maks dalam MB
 */
export function getMaxSizeMB(preset = 'image') {
  const config = UPLOAD_PRESETS[preset];
  return config ? config.maxSizeMB : 5;
}

/**
 * Format ukuran file menjadi human-readable
 * @param {number} bytes - Ukuran file dalam bytes
 * @returns {string} Ukuran formatted (e.g. "2.5 MB")
 */
export function formatFileSize(bytes) {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

export { UPLOAD_PRESETS };
