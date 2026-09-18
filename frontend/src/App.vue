<template>
  <div v-if="isAuthRoute" class="min-h-screen bg-slate-900">
    <router-view />
  </div>

  <div v-else class="h-screen overflow-hidden bg-slate-50 flex print:bg-white print:block print:h-auto print:overflow-visible">
    <!-- Backdrop Gelap Khusus Mobile saat Sidebar Terbuka -->
    <div 
      v-if="isSidebarOpen" 
      @click="isSidebarOpen = false" 
      class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-30 lg:hidden cursor-pointer"
    ></div>

    <!-- Sidebar (Sticky / Fixed Full Height, tidak ikut terscroll ketika konten di-scroll) -->
    <Sidebar 
      class="print:hidden"
      :is-open="isSidebarOpen" 
      @close="isSidebarOpen = false" 
      @open-logout="isLogoutModalOpen = true"
    />

    <!-- Main Viewport (Fixed Height, Container untuk Konten Ber-scroll) -->
    <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden print:block print:w-full print:h-auto print:overflow-visible">
      <!-- Navbar (Tetap di bagian atas viewport) -->
      <Navbar 
        class="print:hidden flex-shrink-0"
        @toggle-sidebar="isSidebarOpen = !isSidebarOpen"
        @open-qr-scanner="isQRScannerOpen = true"
        @open-logout="isLogoutModalOpen = true"
      />

      <!-- Content Area dengan Independent Scroll (Sidebar tidak ikut terscroll!) -->
      <div class="flex-1 overflow-y-auto overflow-x-hidden flex flex-col">
        <main class="flex-1 p-4 lg:p-6 w-full print:p-0 print:m-0">
          <router-view />
        </main>

        <!-- Footer di Bawah Konten -->
        <footer class="py-4 px-6 text-center text-xs text-slate-400 border-t border-slate-200/60 bg-white print:hidden flex-shrink-0">
          &copy; {{ new Date().getFullYear() }} SIMPELKES &bull; {{ settingStore.hospitalSubtitle }} &bull; {{ settingStore.hospitalName }}
        </footer>
      </div>
    </div>

    <!-- QR Scanner Modal (Global) -->
    <QRScannerModal 
      :is-open="isQRScannerOpen" 
      @close="isQRScannerOpen = false" 
    />

    <!-- Modal Konfirmasi Logout (Global) -->
    <div v-if="isLogoutModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-sm w-full p-6 text-center shadow-2xl border border-slate-100 space-y-4 animate-in fade-in zoom-in duration-150">
        <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mx-auto">
          <LogOut class="w-6 h-6" />
        </div>
        <div>
          <h3 class="font-bold text-base text-slate-900">Konfirmasi Keluar</h3>
          <p class="text-xs text-slate-500 mt-1">Apakah Anda yakin ingin keluar dari sistem SIMPELKES?</p>
        </div>
        <div class="flex items-center justify-center gap-2 pt-2">
          <button 
            type="button"
            @click="isLogoutModalOpen = false"
            :disabled="loggingOut"
            class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors cursor-pointer"
          >
            Batal
          </button>
          <button 
            type="button"
            @click="confirmGlobalLogout"
            :disabled="loggingOut"
            class="px-5 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-sm transition-all disabled:opacity-50 flex items-center gap-1.5 cursor-pointer"
          >
            <span v-if="loggingOut" class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            <span>{{ loggingOut ? 'Keluar...' : 'Ya, Keluar' }}</span>
          </button>
        </div>
      </div>
    </div>
    <!-- PWA Install Banner -->
    <PwaInstallBanner />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { LogOut } from 'lucide-vue-next';
import Navbar from './components/Navbar.vue';
import Sidebar from './components/Sidebar.vue';
import QRScannerModal from './components/QRScannerModal.vue';
import PwaInstallBanner from './components/PwaInstallBanner.vue';
import { useAuthStore } from './stores/authStore';
import { useSettingStore } from './stores/settingStore';

const route = useRoute();
const authStore = useAuthStore();
const settingStore = useSettingStore();
const isSidebarOpen = ref(typeof window !== 'undefined' ? window.innerWidth >= 1024 : true);
const isQRScannerOpen = ref(false);
const isLogoutModalOpen = ref(false);
const loggingOut = ref(false);

onMounted(() => {
  settingStore.fetchSettings();
});

const isAuthRoute = computed(() => route.path === '/login');

const confirmGlobalLogout = async () => {
  loggingOut.value = true;
  try {
    await authStore.logout();
  } catch (e) {
  } finally {
    isLogoutModalOpen.value = false;
    loggingOut.value = false;
    // Hard redirect guarantees clean reload and reset
    const base = window.location.pathname.startsWith('/simpelkesrsig') ? '/simpelkesrsig/' : '/';
    window.location.href = base + 'login';
  }
};
</script>
