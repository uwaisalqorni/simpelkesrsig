<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-100 animate-in fade-in zoom-in duration-200">
      <!-- Header -->
      <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
        <div class="flex items-center gap-2">
          <PenTool class="w-5 h-5 text-emerald-400" />
          <h3 class="font-bold text-base">Tanda Tangan Serah Terima Digital</h3>
        </div>
        <button @click="$emit('close')" class="text-slate-400 hover:text-white">
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Content -->
      <div class="p-6 space-y-4">
        <div class="text-xs text-slate-600">
          Silakan buat goresan tanda tangan Anda di area kanvas bawah ini sebagai bukti validasi serah terima alkes pasca-perbaikan.
        </div>

        <!-- Canvas Box -->
        <div class="border-2 border-dashed border-slate-300 rounded-xl bg-slate-50 relative overflow-hidden">
          <canvas 
            ref="canvasRef"
            @mousedown="startDrawing"
            @mousemove="draw"
            @mouseup="stopDrawing"
            @mouseleave="stopDrawing"
            @touchstart.passive="handleTouchStart"
            @touchmove.prevent="handleTouchMove"
            @touchend="stopDrawing"
            class="w-full h-44 cursor-crosshair touch-none"
          ></canvas>
          <div v-if="!hasDrawn" class="absolute inset-0 pointer-events-none flex items-center justify-center text-xs text-slate-400 font-medium">
            Goreskan tanda tangan di sini
          </div>
        </div>

        <!-- Validation Notes -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Verifikasi / Hasil Uji Coba</label>
          <textarea 
            v-model="notes" 
            rows="2" 
            placeholder="Alat telah diuji fungsi dan bekerja normal sesuai spesifikasi..." 
            class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"
          ></textarea>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between pt-2">
          <button 
            type="button" 
            @click="clearCanvas"
            class="px-3 py-2 text-xs font-medium text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors flex items-center gap-1.5"
          >
            <RotateCcw class="w-3.5 h-3.5" />
            Hapus Tanda Tangan
          </button>

          <div class="flex items-center gap-2">
            <button 
              type="button" 
              @click="$emit('close')" 
              class="px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-lg"
            >
              Batal
            </button>
            <button 
              type="button" 
              @click="submitSignature" 
              :disabled="!hasDrawn"
              class="px-4 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg disabled:opacity-50 transition-all shadow-sm"
            >
              Simpan & Verifikasi
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick, watch } from 'vue';
import { PenTool, X, RotateCcw } from 'lucide-vue-next';

const props = defineProps({
  isOpen: Boolean
});

const emit = defineEmits(['close', 'confirm']);

const canvasRef = ref(null);
const hasDrawn = ref(false);
const isDrawing = ref(false);
const notes = ref('');
let ctx = null;

const initCanvas = () => {
  if (!canvasRef.value) return;
  const canvas = canvasRef.value;
  canvas.width = canvas.offsetWidth;
  canvas.height = canvas.offsetHeight;
  ctx = canvas.getContext('2d');
  ctx.strokeStyle = '#0f172a';
  ctx.lineWidth = 2.5;
  ctx.lineCap = 'round';
  ctx.lineJoin = 'round';
};

const startDrawing = (e) => {
  isDrawing.value = true;
  hasDrawn.value = true;
  const rect = canvasRef.value.getBoundingClientRect();
  ctx.beginPath();
  ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
};

const draw = (e) => {
  if (!isDrawing.value) return;
  const rect = canvasRef.value.getBoundingClientRect();
  ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
  ctx.stroke();
};

const stopDrawing = () => {
  if (!isDrawing.value) return;
  isDrawing.value = false;
  ctx.closePath();
};

const handleTouchStart = (e) => {
  if (e.touches.length === 1) {
    const touch = e.touches[0];
    const rect = canvasRef.value.getBoundingClientRect();
    isDrawing.value = true;
    hasDrawn.value = true;
    ctx.beginPath();
    ctx.moveTo(touch.clientX - rect.left, touch.clientY - rect.top);
  }
};

const handleTouchMove = (e) => {
  if (!isDrawing.value || e.touches.length !== 1) return;
  const touch = e.touches[0];
  const rect = canvasRef.value.getBoundingClientRect();
  ctx.lineTo(touch.clientX - rect.left, touch.clientY - rect.top);
  ctx.stroke();
};

const clearCanvas = () => {
  if (!canvasRef.value || !ctx) return;
  ctx.clearRect(0, 0, canvasRef.value.width, canvasRef.value.height);
  hasDrawn.value = false;
};

const submitSignature = () => {
  if (!hasDrawn.value || !canvasRef.value) return;
  const base64Png = canvasRef.value.toDataURL('image/png');
  emit('confirm', {
    signatureData: base64Png,
    notes: notes.value
  });
};

watch(() => props.isOpen, async (val) => {
  if (val) {
    hasDrawn.value = false;
    notes.value = '';
    await nextTick();
    initCanvas();
  }
});
</script>
