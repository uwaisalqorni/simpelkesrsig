import { defineStore } from 'pinia';
import axiosClient, { getUploadUrl } from '../api/axiosClient';

export const useSettingStore = defineStore('setting', {
  state: () => ({
    settings: {
      hospital_name: 'RS Islam Gondanglegi',
      hospital_subtitle: 'Instalasi Pemeliharaan Sarana Rumah Sakit (IPSRS)',
      hospital_address: 'Jl. Hayam Wuruk No. 123, Gondanglegi, Malang',
      hospital_phone: '(0341) 879222',
      hospital_city: 'Gondanglegi',
      hospital_logo: null
    },
    loading: false,
    saving: false,
    error: null
  }),

  getters: {
    hospitalName: (state) => state.settings?.hospital_name || 'RS Islam Gondanglegi',
    hospitalSubtitle: (state) => state.settings?.hospital_subtitle || 'Instalasi Pemeliharaan Sarana Rumah Sakit (IPSRS)',
    hospitalAddress: (state) => state.settings?.hospital_address || 'Jl. Hayam Wuruk No. 123, Gondanglegi, Malang',
    hospitalPhone: (state) => state.settings?.hospital_phone || '(0341) 879222',
    hospitalCity: (state) => state.settings?.hospital_city || 'Gondanglegi',
    hospitalLogoUrl: (state) => {
      if (state.settings?.hospital_logo) {
        return getUploadUrl(state.settings.hospital_logo);
      }
      return '/logo.svg';
    }
  },

  actions: {
    async fetchSettings() {
      this.loading = true;
      try {
        const res = await axiosClient.get('/settings');
        if (res.success && res.data) {
          this.settings = res.data;
          // Update page document title dynamically if desired
          if (res.data.hospital_name) {
            document.title = `SIMPELKES - ${res.data.hospital_name}`;
          }
        }
      } catch (err) {
        console.error('Gagal mengambil pengaturan RS:', err);
      } finally {
        this.loading = false;
      }
    },

    async updateSettings(data) {
      this.saving = true;
      this.error = null;
      try {
        let payload = data;
        let headers = {};
        // If data is FormData (has files)
        if (data instanceof FormData) {
          headers['Content-Type'] = 'multipart/form-data';
        }
        const res = await axiosClient.post('/settings/update', payload, { headers });
        if (res.success && res.data) {
          this.settings = res.data;
          if (res.data.hospital_name) {
            document.title = `SIMPELKES - ${res.data.hospital_name}`;
          }
          return { success: true, message: res.message };
        }
        throw new Error(res.message || 'Gagal menyimpan pengaturan.');
      } catch (err) {
        this.error = err.response?.data?.message || err.message || 'Gagal menyimpan pengaturan.';
        return { success: false, message: this.error };
      } finally {
        this.saving = false;
      }
    }
  }
});
