<template>
  <div v-if="modelValue" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-3 sm:p-4">
    <div class="bg-slate-900 border border-slate-700/80 rounded-3xl max-w-xl w-full overflow-hidden shadow-2xl flex flex-col max-h-[92vh]">
      
      <!-- Modal Header -->
      <div class="px-5 py-4 bg-slate-800/80 border-b border-slate-700/80 flex items-center justify-between text-white shrink-0">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-xl bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center text-emerald-400">
            <Camera class="w-4 h-4" />
          </div>
          <div>
            <h3 class="text-sm font-bold tracking-tight">Kamera Fisik Alat Medis</h3>
            <p class="text-[11px] text-slate-400">
              {{ capturedImage ? 'Periksa hasil foto fisik alkes' : 'Arahkan kamera ke alat medis rumah sakit' }}
            </p>
          </div>
        </div>

        <button 
          @click="closeModal" 
          class="w-8 h-8 rounded-xl bg-slate-700/50 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center transition-colors cursor-pointer"
        >
          <X class="w-4 h-4" />
        </button>
      </div>

      <!-- Viewfinder / Preview Body -->
      <div class="relative flex-1 bg-black flex items-center justify-center min-h-[300px] sm:min-h-[380px] overflow-hidden">
        
        <!-- Live Video Stream -->
        <video 
          v-show="!capturedImage && !errorMessage" 
          ref="videoRef" 
          autoplay 
          playsinline 
          class="w-full h-full object-cover max-h-[55vh]"
        ></video>

        <!-- Hidden canvas for rendering frame -->
        <canvas ref="canvasRef" class="hidden"></canvas>

        <!-- Still Captured Photo Preview -->
        <img 
          v-if="capturedImage" 
          :src="capturedImage" 
          class="w-full h-full object-contain max-h-[55vh] animate-in fade-in zoom-in duration-200" 
          alt="Hasil Foto Kamera" 
        />

        <!-- Loading Viewfinder Indicator -->
        <div v-if="cameraLoading && !errorMessage" class="absolute inset-0 bg-slate-950/80 flex flex-col items-center justify-center text-white text-xs gap-3">
          <div class="w-8 h-8 border-3 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
          <span class="font-medium text-slate-300">Menghubungkan ke sensor kamera...</span>
        </div>

        <!-- Camera Framing Guide Overlay -->
        <div v-if="!capturedImage && !errorMessage && !cameraLoading" class="absolute inset-6 pointer-events-none border-2 border-dashed border-white/40 rounded-2xl flex items-center justify-center">
          <div class="text-[11px] text-white/70 bg-black/40 px-3 py-1 rounded-full backdrop-blur-xs">
            Posisikan fisik alkes di dalam bingkai
          </div>
        </div>

        <!-- Error State (e.g. Permission Denied or Not Supported) -->
        <div v-if="errorMessage" class="p-6 text-center text-slate-300 max-w-sm space-y-3">
          <div class="w-12 h-12 rounded-full bg-rose-500/20 border border-rose-500/40 text-rose-400 flex items-center justify-center mx-auto">
            <AlertCircle class="w-6 h-6" />
          </div>
          <div class="text-xs font-bold text-white">{{ errorMessage }}</div>
          <p class="text-[11px] text-slate-400 leading-relaxed">
            Periksa izin akses kamera pada peramban web atau gunakan tombol alternatif kamera native HP berikut:
          </p>

          <!-- Fallback Native Mobile Capture -->
          <label class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow cursor-pointer transition-colors mt-2">
            <Camera class="w-4 h-4" />
            <span>Gunakan Kamera Bawaan HP</span>
            <input 
              type="file" 
              accept="image/*" 
              capture="environment" 
              @change="handleNativeCapture" 
              class="hidden" 
            />
          </label>
        </div>

        <!-- Floating Camera Info & Switch Button (When Live) -->
        <div v-if="!capturedImage && !errorMessage && !cameraLoading" class="absolute top-4 right-4 flex items-center gap-2 z-10">
          <button 
            type="button" 
            @click="switchCamera" 
            class="px-3 py-1.5 bg-slate-900/80 hover:bg-slate-800 text-white text-xs font-bold rounded-xl border border-slate-700 backdrop-blur-md flex items-center gap-1.5 transition-all shadow cursor-pointer active:scale-95"
            title="Beralih Kamera Depan / Belakang"
          >
            <RefreshCw class="w-3.5 h-3.5 text-emerald-400" :class="{ 'animate-spin': switchingCamera }" />
            <span>{{ facingMode === 'environment' ? 'Kamera Belakang' : 'Kamera Depan' }}</span>
          </button>
        </div>
      </div>

      <!-- Modal Footer & Controls -->
      <div class="px-5 py-4 bg-slate-800/90 border-t border-slate-700/80 flex items-center justify-between gap-3 shrink-0">
        
        <!-- Mode Live Camera: Capture Button -->
        <template v-if="!capturedImage && !errorMessage">
          <button 
            type="button" 
            @click="closeModal" 
            class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white transition-colors cursor-pointer"
          >
            Batal
          </button>

          <!-- Main Circular Shutter Button -->
          <button 
            type="button" 
            @click="capturePhoto" 
            :disabled="cameraLoading"
            class="w-14 h-14 rounded-full bg-emerald-500 hover:bg-emerald-400 text-white flex items-center justify-center p-1.5 shadow-lg shadow-emerald-500/40 ring-4 ring-emerald-500/20 transition-all active:scale-90 cursor-pointer disabled:opacity-50"
            title="Ambil Foto Alkes"
          >
            <div class="w-full h-full rounded-full border-2 border-white flex items-center justify-center">
              <Camera class="w-5 h-5 text-white" />
            </div>
          </button>

          <button 
            type="button" 
            @click="switchCamera" 
            class="px-3 py-2 text-xs font-medium text-slate-400 hover:text-white transition-colors cursor-pointer flex items-center gap-1"
          >
            <RefreshCw class="w-3.5 h-3.5" />
            <span>Putar</span>
          </button>
        </template>

        <!-- Mode Review: Retake or Confirm Photo -->
        <template v-else-if="capturedImage">
          <button 
            type="button" 
            @click="retakePhoto" 
            class="px-4 py-2 text-xs font-bold text-slate-300 hover:text-white bg-slate-700/70 hover:bg-slate-700 rounded-xl transition-colors flex items-center gap-1.5 cursor-pointer"
          >
            <RotateCcw class="w-3.5 h-3.5" />
            <span>Foto Ulang</span>
          </button>

          <button 
            type="button" 
            @click="confirmPhoto" 
            class="px-5 py-2.5 text-xs font-extrabold text-white bg-emerald-600 hover:bg-emerald-500 rounded-xl shadow-lg shadow-emerald-600/30 transition-all flex items-center gap-2 cursor-pointer active:scale-95"
          >
            <Check class="w-4 h-4" />
            <span>Gunakan Foto Ini</span>
          </button>
        </template>

        <!-- Mode Error: Close Button -->
        <template v-else>
          <div class="w-full flex justify-end">
            <button 
              type="button" 
              @click="closeModal" 
              class="px-4 py-2 text-xs font-bold text-white bg-slate-700 hover:bg-slate-600 rounded-xl cursor-pointer"
            >
              Tutup
            </button>
          </div>
        </template>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onBeforeUnmount, nextTick } from 'vue';
import { Camera, RefreshCw, X, Check, RotateCcw, AlertCircle } from 'lucide-vue-next';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue', 'capture', 'close']);

const videoRef = ref(null);
const canvasRef = ref(null);
const stream = ref(null);
const cameraLoading = ref(true);
const switchingCamera = ref(false);
const errorMessage = ref('');

// 'environment' = kamera belakang, 'user' = kamera depan
const facingMode = ref('environment');

const capturedImage = ref(null);
const capturedBlob = ref(null);

const stopStream = () => {
  if (stream.value) {
    stream.value.getTracks().forEach((track) => track.stop());
    stream.value = null;
  }
};

const startCamera = async () => {
  stopStream();
  errorMessage.value = '';
  cameraLoading.value = true;
  capturedImage.value = null;
  capturedBlob.value = null;

  try {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      throw new Error('Fitur live kamera tidak didukung pada peramban web ini.');
    }

    const constraints = {
      video: {
        facingMode: { ideal: facingMode.value },
        width: { ideal: 1280 },
        height: { ideal: 720 }
      },
      audio: false
    };

    const mediaStream = await navigator.mediaDevices.getUserMedia(constraints);
    stream.value = mediaStream;

    await nextTick();
    if (videoRef.value) {
      videoRef.value.srcObject = mediaStream;
      videoRef.value.onloadedmetadata = () => {
        videoRef.value.play();
        cameraLoading.value = false;
      };
    }
  } catch (err) {
    console.error('Camera access error:', err);
    cameraLoading.value = false;
    if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
      errorMessage.value = 'Izin akses kamera ditolak. Berikan izin kamera di browser untuk mengambil foto.';
    } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
      errorMessage.value = 'Perangkat kamera tidak ditemukan pada komputer/perangkat ini.';
    } else {
      errorMessage.value = err.message || 'Gagal membuka akses kamera.';
    }
  } finally {
    switchingCamera.value = false;
  }
};

const switchCamera = async () => {
  switchingCamera.value = true;
  facingMode.value = facingMode.value === 'environment' ? 'user' : 'environment';
  await startCamera();
};

const capturePhoto = () => {
  if (!videoRef.value || !canvasRef.value) return;

  const video = videoRef.value;
  const canvas = canvasRef.value;

  const width = video.videoWidth || 1280;
  const height = video.videoHeight || 720;

  canvas.width = width;
  canvas.height = height;

  const ctx = canvas.getContext('2d');

  // Jika kamera depan, lakukan horizontal mirror agar pratinjau natural
  if (facingMode.value === 'user') {
    ctx.translate(width, 0);
    ctx.scale(-1, 1);
  }

  ctx.drawImage(video, 0, 0, width, height);

  canvas.toBlob(
    (blob) => {
      if (blob) {
        capturedBlob.value = blob;
        capturedImage.value = URL.createObjectURL(blob);
      }
    },
    'image/jpeg',
    0.85
  );
};

const retakePhoto = () => {
  if (capturedImage.value) {
    URL.revokeObjectURL(capturedImage.value);
  }
  capturedImage.value = null;
  capturedBlob.value = null;
  if (!stream.value) {
    startCamera();
  }
};

const confirmPhoto = () => {
  if (!capturedBlob.value) return;

  const fileName = `alkes_${Date.now()}_camera.jpg`;
  const file = new File([capturedBlob.value], fileName, {
    type: 'image/jpeg',
    lastModified: Date.now()
  });

  emit('capture', file);
  closeModal();
};

const handleNativeCapture = (e) => {
  const file = e.target.files[0];
  if (file) {
    emit('capture', file);
    closeModal();
  }
};

const closeModal = () => {
  stopStream();
  if (capturedImage.value) {
    URL.revokeObjectURL(capturedImage.value);
    capturedImage.value = null;
    capturedBlob.value = null;
  }
  emit('update:modelValue', false);
  emit('close');
};

watch(
  () => props.modelValue,
  (val) => {
    if (val) {
      startCamera();
    } else {
      stopStream();
    }
  }
);

onBeforeUnmount(() => {
  stopStream();
  if (capturedImage.value) {
    URL.revokeObjectURL(capturedImage.value);
  }
});
</script>
