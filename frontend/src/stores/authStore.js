import { defineStore } from 'pinia';
import axiosClient from '../api/axiosClient';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('simpelkes_user') || 'null'),
    token: localStorage.getItem('simpelkes_token') || null,
    loading: false,
    error: null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token && !!state.user,
    role: (state) => state.user?.role || '',
    isAdmin: (state) => state.user?.role === 'admin',
    isTeknisi: (state) => state.user?.role === 'teknisi',
    isRuangan: (state) => state.user?.role === 'ruangan',
    userName: (state) => state.user?.full_name || state.user?.username || 'User',
    userRoom: (state) => state.user?.room_name || 'Semua Ruangan',
  },

  actions: {
    async login(username, password) {
      this.loading = true;
      this.error = null;
      try {
        const res = await axiosClient.post('/auth/login', { username, password });
        if (res.success && res.data) {
          this.token = res.data.token;
          this.user = res.data.user;
          localStorage.setItem('simpelkes_token', this.token);
          localStorage.setItem('simpelkes_user', JSON.stringify(this.user));
          return true;
        }
        throw new Error(res.message || 'Login gagal.');
      } catch (err) {
        this.error = err.response?.data?.message || err.message || 'Gagal terhubung ke server.';
        return false;
      } finally {
        this.loading = false;
      }
    },

    async fetchMe() {
      if (!this.token) return;
      try {
        const res = await axiosClient.get('/auth/me');
        if (res.success && res.data) {
          this.user = res.data;
          localStorage.setItem('simpelkes_user', JSON.stringify(this.user));
        }
      } catch (err) {
        this.logout();
      }
    },

    async logout() {
      const currentToken = this.token;
      // 1. Bersihkan state dan storage lokal secara instan
      this.user = null;
      this.token = null;
      this.error = null;
      localStorage.removeItem('simpelkes_token');
      localStorage.removeItem('simpelkes_user');
      sessionStorage.clear();

      // 2. Beritahu server backend untuk audit log
      try {
        if (currentToken) {
          await axiosClient.post('/auth/logout', {}, {
            headers: { Authorization: `Bearer ${currentToken}` }
          });
        }
      } catch (e) {
        // Abaikan jika network gagal atau token kadaluarsa
      }
    }
  }
});
