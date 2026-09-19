import axios from 'axios';

// Di mode dev vite menggunakan proxy /api, di production xampp mengarah ke /simpelkesrsig-backend/api
const baseURL = import.meta.env.DEV 
  ? '/api' 
  : '/simpelkesrsig-backend/api';

const axiosClient = axios.create({
  baseURL,
  headers: {
    'Accept': 'application/json'
  }
});

// ============================================================
// JWT Auto-Refresh System
// ============================================================

// State untuk mengontrol proses refresh agar tidak terjadi bersamaan
let isRefreshing = false;
let failedQueue = []; // Antrian request yang gagal 401 saat sedang refresh

/**
 * Proses antrian request yang tertunda saat token sedang di-refresh
 */
const processQueue = (error, token = null) => {
  failedQueue.forEach(({ resolve, reject }) => {
    if (error) {
      reject(error);
    } else {
      resolve(token);
    }
  });
  failedQueue = [];
};

/**
 * Decode JWT payload tanpa library external
 * Mengambil bagian payload (bagian ke-2) dari token JWT
 */
const decodeJwtPayload = (token) => {
  try {
    if (!token) return null;
    const parts = token.split('.');
    if (parts.length !== 3) return null;
    // Base64Url decode
    const base64 = parts[1].replace(/-/g, '+').replace(/_/g, '/');
    const jsonPayload = decodeURIComponent(
      atob(base64)
        .split('')
        .map(c => '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2))
        .join('')
    );
    return JSON.parse(jsonPayload);
  } catch (e) {
    return null;
  }
};

/**
 * Cek apakah token mendekati kedaluwarsa (kurang dari threshold)
 * Default threshold: 3600 detik (1 jam)
 */
const isTokenNearExpiry = (token, thresholdSeconds = 3600) => {
  const payload = decodeJwtPayload(token);
  if (!payload || !payload.exp) return false;
  const now = Math.floor(Date.now() / 1000);
  return (payload.exp - now) < thresholdSeconds;
};

/**
 * Cek apakah token sudah benar-benar expired
 */
const isTokenExpired = (token) => {
  const payload = decodeJwtPayload(token);
  if (!payload || !payload.exp) return true;
  const now = Math.floor(Date.now() / 1000);
  return payload.exp <= now;
};

/**
 * Lakukan refresh token ke backend
 * Mengembalikan token baru jika berhasil, null jika gagal
 */
const refreshToken = async () => {
  const currentToken = localStorage.getItem('simpelkes_token');
  if (!currentToken || isTokenExpired(currentToken)) {
    return null; // Tidak bisa refresh jika token sudah expired total
  }

  try {
    const response = await axios.post(`${baseURL}/auth/refresh`, {}, {
      headers: {
        'Authorization': `Bearer ${currentToken}`,
        'Accept': 'application/json'
      }
    });

    const newToken = response.data?.data?.token;
    if (newToken) {
      localStorage.setItem('simpelkes_token', newToken);
      console.info('[JWT] Token berhasil di-refresh secara otomatis.');
      return newToken;
    }
    return null;
  } catch (err) {
    console.warn('[JWT] Gagal refresh token:', err.message);
    return null;
  }
};

/**
 * Redirect ke halaman login dan bersihkan session
 */
const forceLogout = () => {
  localStorage.removeItem('simpelkes_token');
  localStorage.removeItem('simpelkes_user');
  sessionStorage.clear();
  if (window.location.pathname.indexOf('/login') === -1) {
    const base = window.location.pathname.startsWith('/simpelkesrsig') ? '/simpelkesrsig/' : '/';
    window.location.href = base + 'login';
  }
};

// ============================================================
// Request Interceptor: Pasang Token + Proactive Refresh
// ============================================================
axiosClient.interceptors.request.use(async (config) => {
  let token = localStorage.getItem('simpelkes_token');

  if (token) {
    // Proactive refresh: jika token mendekati expired, refresh dulu sebelum request
    // Hanya lakukan jika bukan request ke endpoint auth (mencegah loop)
    const isAuthRequest = config.url?.includes('/auth/');
    if (!isAuthRequest && !isRefreshing && isTokenNearExpiry(token)) {
      isRefreshing = true;
      try {
        const newToken = await refreshToken();
        if (newToken) {
          token = newToken;
        }
      } finally {
        isRefreshing = false;
      }
    }

    config.headers.Authorization = `Bearer ${token}`;

    const activeTenantId = localStorage.getItem('simpelkes_active_tenant_id');
    if (activeTenantId) {
      config.headers['X-Tenant-Id'] = activeTenantId;
    }
  }
  return config;
}, (error) => {
  return Promise.reject(error);
});

// ============================================================
// Response Interceptor: Tangani 401 dengan Retry
// ============================================================
axiosClient.interceptors.response.use((response) => {
  return response.data;
}, async (error) => {
  const originalRequest = error.config;

  // Jika 401 dan belum pernah di-retry untuk request ini
  if (error.response?.status === 401 && !originalRequest._retry) {
    // Jangan retry untuk endpoint auth (login/refresh)
    if (originalRequest.url?.includes('/auth/')) {
      forceLogout();
      return Promise.reject(error);
    }

    // Jika sedang proses refresh, masukkan ke antrian
    if (isRefreshing) {
      return new Promise((resolve, reject) => {
        failedQueue.push({ resolve, reject });
      }).then(token => {
        originalRequest.headers.Authorization = `Bearer ${token}`;
        originalRequest._retry = true;
        return axiosClient(originalRequest);
      }).catch(err => {
        return Promise.reject(err);
      });
    }

    // Tandai bahwa sedang refresh
    originalRequest._retry = true;
    isRefreshing = true;

    try {
      const newToken = await refreshToken();
      
      if (newToken) {
        // Refresh berhasil — proses antrian dengan token baru
        processQueue(null, newToken);
        originalRequest.headers.Authorization = `Bearer ${newToken}`;
        return axiosClient(originalRequest);
      } else {
        // Refresh gagal — tolak semua antrian dan logout
        processQueue(new Error('Refresh token gagal'), null);
        forceLogout();
        return Promise.reject(error);
      }
    } catch (refreshError) {
      processQueue(refreshError, null);
      forceLogout();
      return Promise.reject(refreshError);
    } finally {
      isRefreshing = false;
    }
  }

  return Promise.reject(error);
});

// ============================================================
// Utility Exports
// ============================================================

export const getUploadUrl = (path) => {
  if (!path) return '';
  if (path.startsWith('http')) return path;
  const base = import.meta.env.DEV ? '' : '/simpelkesrsig-backend';
  return `${base}/${path}`;
};

/**
 * Helper: Informasi token saat ini (untuk debugging)
 */
export const getTokenInfo = () => {
  const token = localStorage.getItem('simpelkes_token');
  if (!token) return { hasToken: false };
  
  const payload = decodeJwtPayload(token);
  if (!payload) return { hasToken: true, valid: false };
  
  const now = Math.floor(Date.now() / 1000);
  const expiresIn = payload.exp - now;
  
  return {
    hasToken: true,
    valid: expiresIn > 0,
    expiresIn: expiresIn,
    expiresInMinutes: Math.round(expiresIn / 60),
    expiresAt: new Date(payload.exp * 1000).toLocaleString('id-ID'),
    isNearExpiry: expiresIn < 3600,
    user: payload.username,
    role: payload.role
  };
};

export default axiosClient;
