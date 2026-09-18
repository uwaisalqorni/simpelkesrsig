<template>
  <aside 
    :class="[
      'fixed lg:sticky top-0 inset-y-0 left-0 z-40 h-screen bg-slate-900 text-white flex flex-col transition-all duration-300 ease-in-out print:hidden flex-shrink-0 shadow-2xl lg:shadow-none',
      isOpen ? 'w-64 translate-x-0' : '-translate-x-full lg:translate-x-0 lg:w-0 lg:overflow-hidden'
    ]"
  >
    <div class="w-64 h-full flex flex-col min-h-0">
      <!-- Brand Header -->
      <div class="h-16 flex items-center justify-between px-6 bg-slate-950 border-b border-slate-800 flex-shrink-0">
      <div class="flex items-center gap-3 min-w-0">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center shadow-md overflow-hidden p-1 flex-shrink-0">
          <img :src="settingStore.hospitalLogoUrl" alt="Logo RS" class="w-full h-full object-contain" />
        </div>
        <div class="min-w-0">
          <div class="font-extrabold text-base tracking-wide text-white flex items-center gap-1.5">
            SIMPELKES
            <span class="text-[9px] font-bold px-1.5 py-0.5 bg-emerald-500/20 text-emerald-400 rounded">RS</span>
          </div>
          <div class="text-[10px] text-slate-400 font-medium truncate max-w-[135px]" :title="settingStore.hospitalName">
            {{ settingStore.hospitalName }}
          </div>
        </div>
      </div>
      <button @click="$emit('close')" class="lg:hidden text-slate-400 hover:text-white flex-shrink-0">
        <X class="w-5 h-5" />
      </button>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
      <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 px-3 mb-2">Utama</div>
      
      <router-link 
        to="/" 
        @click="handleNavClick"
        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all"
        :class="[$route.path === '/' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white']"
      >
        <LayoutDashboard class="w-5 h-5" />
        <span>Dashboard</span>
      </router-link>

      <router-link 
        to="/equipment" 
        @click="handleNavClick"
        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all"
        :class="[$route.path.startsWith('/equipment') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white']"
      >
        <Stethoscope class="w-5 h-5" />
        <span>Inventaris Alkes</span>
      </router-link>

      <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 px-3 pt-4 mb-2">Pemeliharaan & Servis</div>

      <router-link 
        to="/tickets" 
        @click="handleNavClick"
        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all"
        :class="[$route.path.startsWith('/tickets') && $route.path !== '/tickets/create' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white']"
      >
        <Wrench class="w-5 h-5" />
        <span class="flex-1">Tiket Perbaikan</span>
        <!-- Badges -->
        <span 
          v-if="notifStore.emergencyTicketsCount > 0" 
          class="px-2 py-0.5 text-[10px] font-black rounded-full bg-rose-500 text-white animate-pulse shadow-sm flex items-center gap-1"
          title="Tiket Darurat (Emergency)"
        >
          <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
          {{ notifStore.emergencyTicketsCount }} DARURAT
        </span>
        <span 
          v-else-if="notifStore.activeTicketsCount > 0" 
          class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30"
          title="Tiket Aktif"
        >
          {{ notifStore.activeTicketsCount }}
        </span>
      </router-link>

      <router-link 
        to="/tickets/create" 
        @click="handleNavClick"
        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all"
        :class="[$route.path === '/tickets/create' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white']"
      >
        <PlusCircle class="w-5 h-5 text-amber-400" />
        <span>Lapor Kerusakan</span>
      </router-link>

      <!-- Menu Khusus Teknisi & Admin -->
      <template v-if="authStore.isAdmin || authStore.isTeknisi">
        <router-link 
          to="/preventive" 
          @click="handleNavClick"
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all"
          :class="[$route.path.startsWith('/preventive') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white']"
        >
          <CalendarCheck class="w-5 h-5" />
          <span class="flex-1">Preventif Berkala</span>
          <span 
            v-if="notifStore.overduePmCount > 0" 
            class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-rose-500/20 text-rose-300 border border-rose-500/30"
            title="Jadwal Terlambat (Overdue)"
          >
            {{ notifStore.overduePmCount }}
          </span>
        </router-link>
      </template>

      <router-link 
        to="/calibrations" 
        @click="handleNavClick"
        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all"
        :class="[$route.path.startsWith('/calibrations') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white']"
      >
        <Award class="w-5 h-5" />
        <span class="flex-1">Kalibrasi & Sertifikasi</span>
        <span 
          v-if="notifStore.calibrationAlertsCount > 0" 
          class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30"
          title="Atensi Kalibrasi (Habis / Mendekati Jatuh Tempo)"
        >
          {{ notifStore.calibrationAlertsCount }}
        </span>
      </router-link>

      <template v-if="authStore.isAdmin || authStore.isTeknisi">
        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 px-3 pt-4 mb-2">Logistik & Master</div>

        <router-link 
          to="/spareparts" 
          @click="handleNavClick"
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all"
          :class="[$route.path.startsWith('/spareparts') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white']"
        >
          <Boxes class="w-5 h-5" />
          <span>Suku Cadang (Part)</span>
        </router-link>

        <router-link 
          v-if="authStore.isAdmin"
          to="/rooms" 
          @click="handleNavClick"
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all"
          :class="[$route.path.startsWith('/rooms') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white']"
        >
          <DoorOpen class="w-5 h-5" />
          <span>Master Ruangan RS</span>
        </router-link>

        <router-link 
          v-if="authStore.isAdmin"
          to="/users" 
          @click="handleNavClick"
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all"
          :class="[$route.path.startsWith('/users') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white']"
        >
          <Users class="w-5 h-5" />
          <span>Master Pengguna</span>
        </router-link>

        <router-link 
          to="/reports" 
          @click="handleNavClick"
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all"
          :class="[$route.path.startsWith('/reports') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white']"
        >
          <FileBarChart class="w-5 h-5" />
          <span>Laporan Eksekutif</span>
        </router-link>

        <router-link 
          v-if="authStore.isAdmin"
          to="/audit-logs" 
          @click="handleNavClick"
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all"
          :class="[$route.path.startsWith('/audit-logs') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white']"
        >
          <ShieldCheck class="w-5 h-5" />
          <span>Audit Trail Log</span>
        </router-link>

        <router-link 
          v-if="authStore.isAdmin"
          to="/settings" 
          @click="handleNavClick"
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all"
          :class="[$route.path.startsWith('/settings') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white']"
        >
          <Sliders class="w-5 h-5" />
          <span>Konfigurasi RS</span>
        </router-link>
      </template>

      <!-- Tombol Keluar di Sidebar -->
      <div class="pt-4 mt-2 border-t border-slate-800">
        <button 
          type="button"
          @click="$emit('open-logout'); handleNavClick()"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-rose-400 hover:bg-rose-500/10 hover:text-rose-300 transition-all text-left cursor-pointer"
        >
          <LogOut class="w-5 h-5" />
          <span>Keluar Sistem</span>
        </button>
      </div>
    </nav>

    <!-- Footer Status -->
    <div class="p-4 bg-slate-950/60 border-t border-slate-800 text-xs text-slate-400 flex items-center gap-3 flex-shrink-0">
      <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse flex-shrink-0"></div>
      <div class="truncate">
        <div class="font-medium text-slate-200">Sistem Online</div>
        <div class="text-[10px] text-slate-400 truncate" :title="settingStore.hospitalSubtitle">{{ settingStore.hospitalSubtitle }}</div>
      </div>
    </div>
    </div>
  </aside>
</template>

<script setup>
import { 
  Activity, X, LayoutDashboard, Stethoscope, Wrench, 
  PlusCircle, CalendarCheck, Award, Boxes, DoorOpen,
  FileBarChart, Users, ShieldCheck, Sliders, LogOut
} from 'lucide-vue-next';
import { useAuthStore } from '../stores/authStore';
import { useSettingStore } from '../stores/settingStore';
import { useNotificationStore } from '../stores/notificationStore';

defineProps({
  isOpen: Boolean
});

const emit = defineEmits(['close', 'open-logout']);

const authStore = useAuthStore();
const settingStore = useSettingStore();
const notifStore = useNotificationStore();

const handleNavClick = () => {
  if (typeof window !== 'undefined' && window.innerWidth < 1024) {
    emit('close');
  }
};
</script>
