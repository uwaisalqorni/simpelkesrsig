import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axiosClient from '../api/axiosClient';
import { useAuthStore } from './authStore';

export const useNotificationStore = defineStore('notification', () => {
  const summary = ref(null);
  const loading = ref(false);
  const isSoundEnabled = ref(localStorage.getItem('simpelkes_sound_enabled') !== 'false');
  const lastEmergencyCount = ref(0);
  const isDropdownOpen = ref(false);

  let pollingTimer = null;

  // Active tickets count
  const activeTicketsCount = computed(() => {
    return summary.value?.work_orders?.active || 0;
  });

  // Emergency tickets count
  const emergencyTicketsCount = computed(() => {
    return summary.value?.work_orders?.emergency || 0;
  });

  // Expired + expiring calibrations
  const calibrationAlertsCount = computed(() => {
    return summary.value?.kpi?.total_calibration_alerts || 0;
  });

  // Overdue PM count
  const overduePmCount = computed(() => {
    return summary.value?.kpi?.overdue_pm || 0;
  });

  // Total attention alerts (emergency + calibrations + overdue PM)
  const totalAlertsCount = computed(() => {
    return emergencyTicketsCount.value + calibrationAlertsCount.value + overduePmCount.value;
  });

  // Web Audio API Synthesizer (Chime alert)
  const playAlertSound = (type = 'emergency') => {
    if (!isSoundEnabled.value) return;
    try {
      const AudioCtx = window.AudioContext || window.webkitAudioContext;
      if (!AudioCtx) return;
      const ctx = new AudioCtx();

      if (type === 'emergency') {
        // Nada darurat dua nada (800Hz -> 1000Hz -> 800Hz)
        const playTone = (freq, start, duration) => {
          const osc = ctx.createOscillator();
          const gain = ctx.createGain();
          osc.type = 'triangle';
          osc.frequency.setValueAtTime(freq, ctx.currentTime + start);
          gain.gain.setValueAtTime(0.15, ctx.currentTime + start);
          gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + start + duration);
          osc.connect(gain);
          gain.connect(ctx.destination);
          osc.start(ctx.currentTime + start);
          osc.stop(ctx.currentTime + start + duration);
        };

        playTone(880, 0, 0.25);
        playTone(1174, 0.25, 0.35);
      } else {
        // Nada notifikasi biasa (lembut)
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(587.33, ctx.currentTime);
        gain.gain.setValueAtTime(0.1, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.3);
      }
    } catch (e) {
      console.warn('AudioContext playback error:', e);
    }
  };

  const toggleSound = () => {
    isSoundEnabled.value = !isSoundEnabled.value;
    localStorage.setItem('simpelkes_sound_enabled', isSoundEnabled.value ? 'true' : 'false');
    if (isSoundEnabled.value) {
      playAlertSound('normal');
    }
  };

  const fetchSummary = async () => {
    const authStore = useAuthStore();
    if (!authStore.isAuthenticated) return;

    try {
      loading.value = true;
      const res = await axiosClient.get('/dashboard/summary');
      if (res.success && res.data) {
        summary.value = res.data;

        const currentEmergency = res.data.work_orders?.emergency || 0;
        // Jika ada lonjakan tiket darurat baru
        if (currentEmergency > lastEmergencyCount.value && lastEmergencyCount.value > 0) {
          playAlertSound('emergency');
        }
        lastEmergencyCount.value = currentEmergency;
      }
    } catch (e) {
      console.error('Error fetching dashboard notification summary:', e);
    } finally {
      loading.value = false;
    }
  };

  const startPolling = (intervalMs = 45000) => {
    stopPolling();
    fetchSummary();
    pollingTimer = setInterval(fetchSummary, intervalMs);
  };

  const stopPolling = () => {
    if (pollingTimer) {
      clearInterval(pollingTimer);
      pollingTimer = null;
    }
  };

  return {
    summary,
    loading,
    isSoundEnabled,
    isDropdownOpen,
    activeTicketsCount,
    emergencyTicketsCount,
    calibrationAlertsCount,
    overduePmCount,
    totalAlertsCount,
    playAlertSound,
    toggleSound,
    fetchSummary,
    startPolling,
    stopPolling
  };
});
