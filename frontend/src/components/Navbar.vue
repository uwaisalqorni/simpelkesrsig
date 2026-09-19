<template>
  <header class="bg-white border-b border-slate-200 sticky top-0 z-30 px-4 lg:px-6 py-3 flex items-center justify-between shadow-xs print:hidden">
    <div class="flex items-center gap-3">
      <!-- Burger Sidebar Toggle Button (Desktop & Mobile) -->
      <button 
        type="button"
        @click="$emit('toggle-sidebar')" 
        class="p-2 rounded-xl text-slate-600 hover:text-emerald-700 hover:bg-slate-100 transition-colors focus:outline-none cursor-pointer flex items-center justify-center active:scale-95"
        title="Buka / Tutup Menu Sidebar"
      >
        <Menu class="w-5 h-5" />
      </button>

      <div class="flex items-center gap-2">
        <span class="text-xs font-semibold px-2.5 py-1 bg-brand-50 text-brand-700 rounded-full border border-brand-200 uppercase tracking-wide">
          {{ authStore.role === 'super_admin' ? 'Super Admin' : authStore.role }}
        </span>

        <!-- Tenant Switcher Dropdown for Super Admin -->
        <div v-if="authStore.isSuperAdmin" class="relative" ref="tenantDropdownRef">
          <button 
            type="button"
            @click="toggleTenantDropdown"
            class="flex items-center gap-1.5 px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 rounded-xl text-xs font-bold transition-all shadow-xs cursor-pointer"
            title="Ganti Lingkup Faskes (Tenant Switcher)"
          >
            <Building2 class="w-3.5 h-3.5 text-amber-700 shrink-0" />
            <span class="max-w-[130px] sm:max-w-[200px] truncate">
              {{ authStore.activeTenantName || 'Semua Faskes (Holding)' }}
            </span>
            <ChevronDown class="w-3.5 h-3.5 text-amber-600 transition-transform" :class="{ 'rotate-180': isTenantDropdownOpen }" />
          </button>

          <!-- Dropdown Box -->
          <div 
            v-if="isTenantDropdownOpen"
            class="absolute left-0 mt-2 w-72 bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden z-50 animate-in fade-in slide-in-from-top-2 duration-150"
          >
            <div class="p-3 bg-amber-600 text-white flex items-center justify-between">
              <div class="font-bold text-xs flex items-center gap-1.5">
                <Building2 class="w-4 h-4" />
                <span>Pilih Lingkup Faskes</span>
              </div>
              <router-link 
                to="/tenants" 
                @click="isTenantDropdownOpen = false"
                class="text-[10px] font-bold underline hover:text-amber-100"
              >
                Kelola
              </router-link>
            </div>

            <div class="p-1 max-h-64 overflow-y-auto divide-y divide-slate-100">
              <!-- Global Holding Option -->
              <button 
                type="button"
                @click="handleSelectTenant(null)"
                class="w-full text-left p-2.5 rounded-xl hover:bg-slate-50 flex items-center justify-between group transition-colors cursor-pointer"
                :class="{ 'bg-amber-50 font-bold text-amber-900': !authStore.activeTenantId }"
              >
                <div class="flex items-center gap-2">
                  <Globe class="w-4 h-4 text-slate-500 group-hover:text-amber-600 shrink-0" />
                  <div>
                    <div class="text-xs font-semibold">🌐 Semua Faskes (Holding)</div>
                    <div class="text-[10px] text-slate-400 font-normal">Data agregat seluruh rumah sakit</div>
                  </div>
                </div>
                <Check v-if="!authStore.activeTenantId" class="w-4 h-4 text-amber-600 shrink-0" />
              </button>

              <!-- Tenant Items -->
              <button 
                v-for="t in tenantList" 
                :key="t.id"
                type="button"
                @click="handleSelectTenant(t)"
                class="w-full text-left p-2.5 rounded-xl hover:bg-slate-50 flex items-center justify-between group transition-colors cursor-pointer"
                :class="{ 'bg-amber-50 font-bold text-amber-900': String(authStore.activeTenantId) === String(t.id) }"
              >
                <div class="flex items-center gap-2 min-w-0">
                  <Building2 class="w-4 h-4 text-slate-400 group-hover:text-amber-600 shrink-0" />
                  <div class="min-w-0">
                    <div class="text-xs truncate font-medium group-hover:text-amber-900">
                      {{ t.name }}
                      <span class="text-[10px] px-1 py-0.2 bg-slate-100 text-slate-600 rounded ml-1 font-mono font-bold">{{ t.code }}</span>
                    </div>
                    <div class="text-[10px] text-slate-400 font-normal truncate">{{ t.city || 'Faskes Terdaftar' }}</div>
                  </div>
                </div>
                <Check v-if="String(authStore.activeTenantId) === String(t.id)" class="w-4 h-4 text-amber-600 shrink-0" />
              </button>
            </div>
          </div>
        </div>

        <!-- Static Tenant Badge for non-superadmin -->
        <span v-else class="text-xs font-semibold px-2.5 py-1 bg-slate-100 text-slate-700 rounded-full border border-slate-200 hidden md:inline-flex items-center gap-1.5" :title="authStore.tenantName">
          <Building2 class="w-3 h-3 text-emerald-600" />
          <span class="max-w-[150px] truncate">{{ authStore.tenantName }}</span>
        </span>

        <span class="text-sm font-medium text-slate-500 hidden sm:inline">
          Unit: <strong class="text-slate-700">{{ authStore.userRoom }}</strong>
        </span>
      </div>
    </div>

    <div class="flex items-center gap-2 sm:gap-3">
      <!-- Quick QR Scan Button -->
      <button 
        @click="$emit('open-qr-scanner')"
        class="flex items-center gap-2 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-all active:scale-95 cursor-pointer"
      >
        <QrCode class="w-4 h-4" />
        <span class="hidden sm:inline">Scan QR Alkes</span>
      </button>

      <!-- Notification Center Dropdown -->
      <div class="relative" ref="dropdownRef">
        <button 
          @click="toggleNotificationDropdown"
          class="relative p-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-all cursor-pointer flex items-center justify-center"
          title="Notifikasi & Peringatan Sistem"
        >
          <Bell class="w-5 h-5" />
          <!-- Badge counter -->
          <span 
            v-if="notifStore.totalAlertsCount > 0" 
            :class="[
              'absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 text-[10px] font-black rounded-full text-white flex items-center justify-center shadow-xs',
              notifStore.emergencyTicketsCount > 0 ? 'bg-rose-600 animate-pulse' : 'bg-amber-500'
            ]"
          >
            {{ notifStore.totalAlertsCount > 99 ? '99+' : notifStore.totalAlertsCount }}
          </span>
        </button>

        <!-- Dropdown Box -->
        <div 
          v-if="isDropdownOpen"
          class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden z-50 animate-in fade-in slide-in-from-top-2 duration-150"
        >
          <!-- Header Dropdown -->
          <div class="p-3.5 bg-slate-900 text-white flex items-center justify-between">
            <div class="flex items-center gap-2">
              <Bell class="w-4 h-4 text-emerald-400" />
              <div class="font-bold text-xs tracking-wide">Pusat Atensi & Notifikasi</div>
            </div>

            <!-- Sound Alert Toggle -->
            <button 
              @click="notifStore.toggleSound"
              :class="[
                'px-2 py-1 rounded-lg text-[10px] font-bold flex items-center gap-1 transition-all cursor-pointer',
                notifStore.isSoundEnabled ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700'
              ]"
              :title="notifStore.isSoundEnabled ? 'Suara Sirine Darurat Aktif' : 'Suara Sirine Dimatikan'"
            >
              <Volume2 v-if="notifStore.isSoundEnabled" class="w-3 h-3 text-emerald-400" />
              <VolumeX v-else class="w-3 h-3 text-slate-400" />
              <span>{{ notifStore.isSoundEnabled ? 'Suara ON' : 'Mute' }}</span>
            </button>
          </div>

          <!-- Alert Items List -->
          <div class="max-h-80 overflow-y-auto divide-y divide-slate-100 p-1">
            <!-- 1. Tiket Emergency -->
            <router-link 
              v-if="notifStore.emergencyTicketsCount > 0"
              to="/tickets"
              @click="isDropdownOpen = false"
              class="p-3 flex items-start gap-3 hover:bg-rose-50/60 transition-colors rounded-xl group cursor-pointer"
            >
              <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0 mt-0.5 group-hover:scale-105 transition-transform">
                <AlertTriangle class="w-4 h-4 text-rose-600" />
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-rose-700">Tiket Darurat (Emergency)</span>
                  <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded bg-rose-600 text-white animate-pulse">
                    {{ notifStore.emergencyTicketsCount }} Tiket
                  </span>
                </div>
                <p class="text-[11px] text-slate-600 mt-0.5">Ada kerusakan alat medis yang butuh penanganan segera dari teknisi IPSRS.</p>
              </div>
            </router-link>

            <!-- 1.5. Tiket Menunggu Validasi & Uji Fungsi Ruangan -->
            <router-link 
              v-if="notifStore.waitingVerificationCount > 0"
              to="/tickets"
              @click="isDropdownOpen = false"
              class="p-3 flex items-start gap-3 hover:bg-indigo-50/60 transition-colors rounded-xl group cursor-pointer"
            >
              <div class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0 mt-0.5 group-hover:scale-105 transition-transform">
                <CheckSquare class="w-4 h-4 text-indigo-600" />
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-indigo-700">Menunggu Uji & Validasi Unit</span>
                  <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded bg-indigo-600 text-white animate-pulse">
                    {{ notifStore.waitingVerificationCount }} Tiket
                  </span>
                </div>
                <p class="text-[11px] text-slate-600 mt-0.5">Teknisi selesai memperbaiki. Silakan uji coba fungsi & verifikasi serah terima.</p>
              </div>
            </router-link>

            <!-- 2. Kalibrasi Kedaluwarsa & Mendekati Jatuh Tempo -->
            <router-link 
              v-if="notifStore.calibrationAlertsCount > 0"
              to="/calibrations"
              @click="isDropdownOpen = false"
              class="p-3 flex items-start gap-3 hover:bg-amber-50/60 transition-colors rounded-xl group cursor-pointer"
            >
              <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0 mt-0.5 group-hover:scale-105 transition-transform">
                <Award class="w-4 h-4 text-amber-600" />
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-amber-800">Atensi Kalibrasi Sertifikat</span>
                  <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-800 border border-amber-500/30">
                    {{ notifStore.calibrationAlertsCount }} Alkes
                  </span>
                </div>
                <p class="text-[11px] text-slate-600 mt-0.5">Sertifikat kalibrasi BPFK habis atau mendekati tempo dalam 60 hari.</p>
              </div>
            </router-link>

            <!-- 3. Preventif Maintenance Terlambat -->
            <router-link 
              v-if="notifStore.overduePmCount > 0 && (authStore.isAdmin || authStore.isTeknisi)"
              to="/preventive"
              @click="isDropdownOpen = false"
              class="p-3 flex items-start gap-3 hover:bg-orange-50/60 transition-colors rounded-xl group cursor-pointer"
            >
              <div class="w-8 h-8 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center shrink-0 mt-0.5 group-hover:scale-105 transition-transform">
                <CalendarCheck class="w-4 h-4 text-orange-600" />
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-orange-800">Jadwal Preventif Terlambat</span>
                  <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-orange-500/20 text-orange-800 border border-orange-500/30">
                    {{ notifStore.overduePmCount }} Jadwal
                  </span>
                </div>
                <p class="text-[11px] text-slate-600 mt-0.5">Jadwal inspeksi rutin preventif elektromedis melewati batas waktu.</p>
              </div>
            </router-link>

            <!-- 4. Tiket Aktif Biasa -->
            <router-link 
              v-if="notifStore.activeTicketsCount > 0 && notifStore.emergencyTicketsCount === 0"
              to="/tickets"
              @click="isDropdownOpen = false"
              class="p-3 flex items-start gap-3 hover:bg-slate-50 transition-colors rounded-xl group cursor-pointer"
            >
              <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center shrink-0 mt-0.5">
                <Wrench class="w-4 h-4 text-slate-600" />
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-slate-800">Tiket Dalam Pengerjaan</span>
                  <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-200 text-slate-700">
                    {{ notifStore.activeTicketsCount }}
                  </span>
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5">Laporan perbaikan alat medis yang sedang ditangani teknisi.</p>
              </div>
            </router-link>

            <!-- Status Kosong / Semua Normal -->
            <div v-if="notifStore.totalAlertsCount === 0" class="py-8 px-4 text-center">
              <CheckCircle2 class="w-10 h-10 text-emerald-500 mx-auto mb-2 opacity-80" />
              <div class="text-xs font-bold text-slate-800">Semua Terkendali</div>
              <p class="text-[11px] text-slate-500 mt-0.5">Tidak ada peringatan darurat, kalibrasi habis, atau PM terlambat saat ini.</p>
            </div>
          </div>

          <!-- Footer Dropdown -->
          <div class="p-2.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs">
            <router-link 
              to="/maintenance-calendar" 
              @click="isDropdownOpen = false"
              class="text-[11px] font-bold text-teal-700 hover:text-teal-800 inline-flex items-center gap-1 cursor-pointer"
            >
              <CalendarDays class="w-3.5 h-3.5" />
              <span>Kalender Jadwal</span>
            </router-link>

            <router-link 
              to="/" 
              @click="isDropdownOpen = false"
              class="text-[11px] font-bold text-emerald-600 hover:text-emerald-700 inline-flex items-center gap-1 cursor-pointer"
            >
              <span>Dashboard</span>
              <span>&rarr;</span>
            </router-link>
          </div>
        </div>
      </div>

      <!-- User Profile & Logout Button -->
      <div class="flex items-center gap-3 pl-2 sm:pl-3 border-l border-slate-200">
        <div class="text-right hidden sm:block">
          <div class="text-xs font-bold text-slate-800 leading-tight">{{ authStore.userName }}</div>
          <div class="text-[10px] text-slate-500">{{ authStore.user?.username }}</div>
        </div>
        <button 
          @click="$emit('open-logout')"
          class="flex items-center gap-1.5 p-2 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors group cursor-pointer"
          title="Keluar / Logout"
        >
          <LogOut class="w-5 h-5 group-hover:translate-x-0.5 transition-transform" />
          <span class="text-xs font-bold hidden sm:inline">Keluar</span>
        </button>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { 
  Menu, QrCode, LogOut, Bell, Volume2, VolumeX, 
  AlertTriangle, Award, CalendarCheck, CalendarDays, Wrench, CheckCircle2, CheckSquare,
  Building2, ChevronDown, Globe, Check
} from 'lucide-vue-next';
import { useAuthStore } from '../stores/authStore';
import { useNotificationStore } from '../stores/notificationStore';
import axiosClient from '../api/axiosClient';

const authStore = useAuthStore();
const notifStore = useNotificationStore();

const isDropdownOpen = ref(false);
const dropdownRef = ref(null);

const isTenantDropdownOpen = ref(false);
const tenantDropdownRef = ref(null);
const tenantList = ref([]);

defineEmits(['toggle-sidebar', 'open-qr-scanner', 'open-logout']);

const toggleNotificationDropdown = () => {
  isDropdownOpen.value = !isDropdownOpen.value;
};

const toggleTenantDropdown = () => {
  isTenantDropdownOpen.value = !isTenantDropdownOpen.value;
};

const handleSelectTenant = (tenant) => {
  authStore.switchTenant(tenant);
  isTenantDropdownOpen.value = false;
  // Reload window to re-trigger all queries and stores with the new X-Tenant-Id
  window.location.reload();
};

const fetchTenants = async () => {
  if (!authStore.isSuperAdmin) return;
  try {
    const res = await axiosClient.get('/tenants');
    if (res.success && Array.isArray(res.data)) {
      tenantList.value = res.data;
    }
  } catch (err) {
    console.error('Gagal mengambil daftar faskes:', err);
  }
};

const handleClickOutside = (e) => {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    isDropdownOpen.value = false;
  }
  if (tenantDropdownRef.value && !tenantDropdownRef.value.contains(e.target)) {
    isTenantDropdownOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  if (authStore.isAuthenticated) {
    notifStore.startPolling(45000);
    fetchTenants();
  }
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
  notifStore.stopPolling();
});
</script>
