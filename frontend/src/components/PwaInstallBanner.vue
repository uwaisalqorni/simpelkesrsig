<template>
  <div 
    v-if="canInstall && !isDismissed" 
    class="fixed bottom-4 right-4 z-50 max-w-sm bg-slate-900 text-white p-4 rounded-2xl shadow-2xl border border-emerald-500/30 flex items-start gap-3 animate-in fade-in slide-in-from-bottom-4 duration-300 print:hidden"
  >
    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 p-1.5 flex items-center justify-center shrink-0 shadow-md">
      <Smartphone class="w-6 h-6 text-white" />
    </div>

    <div class="flex-1 min-w-0">
      <div class="font-bold text-xs text-white">Pasang Aplikasi di HP</div>
      <p class="text-[11px] text-slate-300 mt-0.5 leading-snug">
        Akses cepat SIMPELKES dari home screen smartphone tanpa perlu buka browser.
      </p>

      <div class="flex items-center gap-2 mt-2.5">
        <button 
          type="button"
          @click="installApp"
          class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-lg shadow-sm transition-all active:scale-95 cursor-pointer"
        >
          Pasang Sekarang
        </button>
        <button 
          type="button"
          @click="dismissBanner"
          class="px-2.5 py-1.5 text-xs text-slate-400 hover:text-white rounded-lg transition-colors cursor-pointer"
        >
          Nanti Saja
        </button>
      </div>
    </div>

    <button 
      type="button"
      @click="dismissBanner" 
      class="text-slate-400 hover:text-white p-1 rounded-lg transition-colors cursor-pointer"
      title="Tutup"
    >
      <X class="w-4 h-4" />
    </button>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Smartphone, X } from 'lucide-vue-next';

const canInstall = ref(false);
const isDismissed = ref(sessionStorage.getItem('simpelkes_pwa_dismissed') === 'true');
let deferredPrompt = null;

onMounted(() => {
  window.addEventListener('beforeinstallprompt', (e) => {
    // Prevent the mini-infobar from appearing on mobile
    e.preventDefault();
    deferredPrompt = e;
    canInstall.value = true;
  });

  window.addEventListener('appinstalled', () => {
    canInstall.value = false;
    deferredPrompt = null;
    console.log('SIMPELKES PWA was installed successfully');
  });
});

const installApp = async () => {
  if (!deferredPrompt) return;
  deferredPrompt.prompt();
  const { outcome } = await deferredPrompt.userChoice;
  if (outcome === 'accepted') {
    canInstall.value = false;
  }
  deferredPrompt = null;
};

const dismissBanner = () => {
  isDismissed.value = true;
  sessionStorage.setItem('simpelkes_pwa_dismissed', 'true');
};
</script>
