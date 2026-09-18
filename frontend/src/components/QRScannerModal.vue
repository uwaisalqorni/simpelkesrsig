<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full overflow-hidden shadow-2xl border border-slate-100 animate-in fade-in zoom-in duration-200">
      <!-- Modal Header -->
      <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
        <div class="flex items-center gap-2">
          <QrCode class="w-5 h-5 text-emerald-400" />
          <h3 class="font-bold text-base">Pemindai QR Code Alkes</h3>
        </div>
        <button @click="closeModal" class="p-1 text-slate-400 hover:text-white rounded-lg">
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Scanner Area -->
      <div class="p-6 space-y-4">
        <div class="relative bg-slate-950 rounded-xl overflow-hidden min-h-[260px] flex items-center justify-center">
          <div id="qr-reader" class="w-full"></div>
          <div v-if="isScanning" class="absolute inset-0 pointer-events-none border-2 border-emerald-500/60 rounded-xl m-8 flex items-center justify-center">
            <div class="w-full h-0.5 bg-emerald-400 animate-pulse shadow-[0_0_8px_#34d399]"></div>
          </div>
        </div>

        <div v-if="loading" class="p-3 bg-emerald-50 text-emerald-700 text-xs rounded-lg border border-emerald-200 flex items-center justify-center gap-2">
          <div class="w-3.5 h-3.5 border-2 border-emerald-600 border-t-transparent rounded-full animate-spin"></div>
          <span>Memproses QR Code & membuka halaman alat...</span>
        </div>

        <div v-if="scanError" class="p-3 bg-rose-50 text-rose-700 text-xs rounded-lg border border-rose-200">
          {{ scanError }}
        </div>

        <!-- Manual Code Search fallback -->
        <div class="pt-2 border-t border-slate-100">
          <label class="block text-xs font-semibold text-slate-600 mb-1">Atau Masukkan Kode Aset / Serial Manual</label>
          <div class="flex gap-2">
            <input 
              v-model="manualCode" 
              @keyup.enter="handleManualSearch"
              type="text" 
              placeholder="Contoh: EQ-2024-001" 
              class="flex-1 px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"
            />
            <button 
              @click="handleManualSearch" 
              :disabled="loading"
              class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold rounded-lg disabled:opacity-50"
            >
              Cari
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, nextTick, onBeforeUnmount } from 'vue';
import { QrCode, X } from 'lucide-vue-next';
import { Html5Qrcode } from 'html5-qrcode';
import axiosClient from '../api/axiosClient';
import { useRouter } from 'vue-router';

const props = defineProps({
  isOpen: Boolean
});

const emit = defineEmits(['close']);
const router = useRouter();

const manualCode = ref('');
const scanError = ref('');
const isScanning = ref(false);
const loading = ref(false);
let html5QrCode = null;

const startCamera = async () => {
  scanError.value = '';
  await nextTick();
  try {
    html5QrCode = new Html5Qrcode('qr-reader');
    isScanning.value = true;
    await html5QrCode.start(
      { facingMode: 'environment' },
      {
        fps: 10,
        qrbox: { width: 220, height: 220 }
      },
      onScanSuccess,
      () => {}
    );
  } catch (err) {
    isScanning.value = false;
    scanError.value = 'Tidak dapat mengakses kamera: ' + (err.message || 'Periksa izin kamera browser.');
  }
};

const stopCamera = async () => {
  if (html5QrCode && html5QrCode.isScanning) {
    try {
      await html5QrCode.stop();
      html5QrCode.clear();
    } catch (e) {}
  }
  isScanning.value = false;
};

const onScanSuccess = async (decodedText) => {
  await stopCamera();
  lookupEquipment(decodedText);
};

const handleManualSearch = () => {
  if (!manualCode.value.trim()) return;
  lookupEquipment(manualCode.value.trim());
};

const lookupEquipment = async (input) => {
  const trimmed = (input || '').trim();
  if (!trimmed) return;

  loading.value = true;
  scanError.value = '';

  try {
    // 1. Cek jika hasil scan adalah direct URL alkes, misal http://.../equipment/5 atau /equipment/5
    const matchId = trimmed.match(/\/equipment\/(\d+)/i);
    if (matchId && matchId[1]) {
      closeModal();
      router.push(`/equipment/${matchId[1]}`);
      return;
    }

    // 2. Cek jika hasil scan adalah URL dengan query param ?code=...
    let cleanCode = trimmed;
    try {
      if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
        const parsedUrl = new URL(trimmed);
        const qCode = parsedUrl.searchParams.get('code');
        if (qCode) cleanCode = qCode;
      }
    } catch (_) {}

    // 3. Cari ke backend melalui lookup kode aset / nomor seri
    const res = await axiosClient.get(`/equipment/lookup?code=${encodeURIComponent(cleanCode)}`);
    if (res.success && res.data) {
      closeModal();
      router.push(`/equipment/${res.data.id}`);
    } else {
      scanError.value = res.message || 'Alat medis tidak ditemukan.';
    }
  } catch (err) {
    scanError.value = err.response?.data?.message || 'Kode atau tautan alkes tidak ditemukan dalam sistem.';
  } finally {
    loading.value = false;
  }
};

const closeModal = () => {
  stopCamera();
  manualCode.value = '';
  scanError.value = '';
  emit('close');
};

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    startCamera();
  } else {
    stopCamera();
  }
});

onBeforeUnmount(() => {
  stopCamera();
});
</script>
