<template>
  <div class="space-y-6">
    <!-- Welcome Header -->
    <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-emerald-950 rounded-2xl p-6 text-white shadow-md relative overflow-hidden flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div class="relative z-10">
        <div class="inline-flex items-center gap-2 px-2.5 py-1 bg-emerald-500/20 text-emerald-300 text-xs font-semibold rounded-full mb-2 border border-emerald-500/30">
          <Sparkles class="w-3.5 h-3.5" />
          SIMPELKES Dashboard
        </div>
        <h1 class="text-2xl font-black tracking-tight">Selamat Datang, {{ authStore.userName }}</h1>
        <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-xl">
          Sistem Pemantauan Pemeliharaan Alat Medis, Kalibrasi, dan Respon Tanggap Darurat Elektromedis Rumah Sakit.
        </p>
      </div>

      <div class="relative z-10 flex items-center gap-2.5">
        <router-link 
          to="/tickets/create" 
          class="flex items-center gap-2 px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-lg shadow-emerald-950/40 transition-all active:scale-95"
        >
          <PlusCircle class="w-4 h-4" />
          <span>Lapor Kerusakan</span>
        </router-link>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-16 text-slate-500 text-sm">
      <div class="w-6 h-6 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mr-3"></div>
      Memuat statistik alkes...
    </div>

    <!-- Main Content -->
    <template v-else-if="summary">
      <!-- 4 Core Metric Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Metric 1: Readiness Rate -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-start justify-between">
          <div>
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Kesiapan Alat Medis</div>
            <div class="text-2xl font-extrabold text-slate-800 mt-1.5 flex items-baseline gap-1.5">
              {{ summary.equipment.readiness_pct }}%
              <span class="text-[11px] font-medium text-emerald-600">({{ summary.equipment.operasional }}/{{ summary.equipment.total }})</span>
            </div>
            <div class="text-[11px] text-slate-400 mt-1">Status operasional aktif</div>
          </div>
          <div class="p-3 bg-emerald-50 rounded-xl text-emerald-600">
            <CheckCircle2 class="w-6 h-6" />
          </div>
        </div>

        <!-- Metric 2: Active Tickets -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-start justify-between">
          <div>
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Tiket Servis Aktif</div>
            <div class="text-2xl font-extrabold text-slate-800 mt-1.5 flex items-baseline gap-2">
              {{ summary.work_orders.active }}
              <span v-if="summary.work_orders.emergency > 0" class="text-xs font-bold px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full animate-pulse">
                {{ summary.work_orders.emergency }} Darurat
              </span>
            </div>
            <div class="text-[11px] text-slate-400 mt-1">Dalam proses pengerjaan</div>
          </div>
          <div class="p-3 bg-amber-50 rounded-xl text-amber-600">
            <Wrench class="w-6 h-6" />
          </div>
        </div>

        <!-- Metric 3: Expiring & Expired Calibrations -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-start justify-between">
          <div>
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Atensi Kalibrasi</div>
            <div class="text-2xl font-extrabold text-slate-800 mt-1.5 flex items-baseline gap-2">
              {{ summary.kpi.total_calibration_alerts ?? (summary.kpi.expiring_calibrations + (summary.kpi.expired_calibrations || 0)) }}
              <span class="text-xs font-bold text-slate-500">Unit</span>
            </div>
            <div class="flex items-center gap-1.5 mt-1">
              <span v-if="summary.kpi.expired_calibrations > 0" class="px-1.5 py-0.5 bg-rose-100 text-rose-700 text-[10px] font-bold rounded">
                {{ summary.kpi.expired_calibrations }} Habis
              </span>
              <span v-if="summary.kpi.expiring_calibrations > 0" class="px-1.5 py-0.5 bg-amber-100 text-amber-800 text-[10px] font-bold rounded">
                {{ summary.kpi.expiring_calibrations }} Mendekati
              </span>
            </div>
          </div>
          <div class="p-3 bg-rose-50 rounded-xl text-rose-600">
            <Award class="w-6 h-6" />
          </div>
        </div>

        <!-- Metric 4: Average Response Time -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-start justify-between">
          <div>
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Respon Teknisi (SPM)</div>
            <div class="text-2xl font-extrabold text-slate-800 mt-1.5 flex items-baseline gap-1">
              {{ summary.kpi.avg_response_minutes }} <span class="text-xs font-normal text-slate-500">Menit</span>
            </div>
            <div class="text-[11px] text-slate-400 mt-1">Rata-rata tanggap darurat</div>
          </div>
          <div class="p-3 bg-blue-50 rounded-xl text-blue-600">
            <Clock class="w-6 h-6" />
          </div>
        </div>
      </div>

      <!-- SECTION: Peringatan Kalibrasi Alat Medis (Mendekati Jatuh Tempo & Habis Masa Berlaku) -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-4">
          <div>
            <div class="flex items-center gap-2">
              <Award class="w-5 h-5 text-amber-500" />
              <h3 class="text-sm font-bold text-slate-800">
                Peringatan Kalibrasi Alat Medis (Mendekati Jatuh Tempo & Habis Masa Berlaku)
              </h3>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">
              Daftar alat medis yang masa berlaku sertifikat kalibrasinya sudah habis atau mendekati waktu uji ulang BPFK.
            </p>
          </div>

          <div class="flex items-center gap-2 self-start sm:self-auto flex-wrap">
            <!-- Filter Tabs -->
            <div class="inline-flex items-center p-1 bg-slate-100 rounded-xl text-xs">
              <button 
                @click="calibFilter = 'all'"
                :class="[
                  'px-3 py-1 rounded-lg font-bold transition-all cursor-pointer',
                  calibFilter === 'all' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-800'
                ]"
              >
                Semua ({{ calibCounts.all }})
              </button>
              <button 
                @click="calibFilter = 'expired'"
                :class="[
                  'px-3 py-1 rounded-lg font-bold transition-all cursor-pointer flex items-center gap-1',
                  calibFilter === 'expired' ? 'bg-rose-600 text-white shadow-sm' : 'text-rose-600 hover:text-rose-800'
                ]"
              >
                <AlertOctagon class="w-3 h-3" />
                <span>Habis ({{ calibCounts.expired }})</span>
              </button>
              <button 
                @click="calibFilter = 'expiring'"
                :class="[
                  'px-3 py-1 rounded-lg font-bold transition-all cursor-pointer flex items-center gap-1',
                  calibFilter === 'expiring' ? 'bg-amber-500 text-white shadow-sm' : 'text-amber-700 hover:text-amber-900'
                ]"
              >
                <Clock class="w-3 h-3" />
                <span>Mendekati ({{ calibCounts.expiring }})</span>
              </button>
            </div>

            <!-- Link ke Kalender & Kalibrasi -->
            <router-link 
              to="/maintenance-calendar" 
              class="px-3 py-1.5 text-xs font-bold text-teal-700 bg-teal-50 hover:bg-teal-100 rounded-xl transition-colors inline-flex items-center gap-1 cursor-pointer"
              title="Buka Kalender Pemeliharaan Alkes"
            >
              <CalendarDays class="w-3.5 h-3.5" />
              <span>Kalender</span>
            </router-link>

            <router-link 
              to="/calibrations" 
              class="px-3 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-xl transition-colors inline-flex items-center gap-1 cursor-pointer"
            >
              <span>Kelola Kalibrasi</span>
              <ArrowRight class="w-3.5 h-3.5" />
            </router-link>
          </div>
        </div>

        <!-- Table Alkes Kalibrasi -->
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
              <tr>
                <th class="py-2.5 px-3 font-semibold text-center w-10">No</th>
                <th class="py-2.5 px-4 font-semibold">Nama Alat Medis</th>
                <th class="py-2.5 px-3 font-semibold">Ruangan / Unit</th>
                <th class="py-2.5 px-3 font-semibold">No. Sertifikat & Vendor</th>
                <th class="py-2.5 px-3 font-semibold">Masa Berlaku</th>
                <th class="py-2.5 px-3 font-semibold">Status Urgensi</th>
                <th class="py-2.5 px-3 font-semibold text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr 
                v-for="(item, idx) in displayedCalibAlerts" 
                :key="item.calibration_id"
                class="hover:bg-slate-50/80 transition-colors"
              >
                <td class="py-3 px-3 text-center font-medium text-slate-500">{{ idx + 1 }}</td>
                
                <!-- Nama Alat Medis -->
                <td class="py-3 px-4">
                  <div class="font-bold text-slate-800 text-[13px] flex items-center gap-1.5">
                    {{ item.equipment_name }}
                    <span 
                      v-if="item.urgency_status === 'expired'" 
                      class="px-1.5 py-0.5 bg-rose-600 text-white text-[9px] font-black uppercase rounded tracking-wider"
                    >
                      EXPIRED
                    </span>
                  </div>
                  <div class="text-[11px] text-slate-400 font-mono">
                    {{ item.asset_code }} <span v-if="item.serial_number">• SN: {{ item.serial_number }}</span>
                  </div>
                </td>

                <!-- Ruangan -->
                <td class="py-3 px-3 text-slate-700 font-medium">
                  {{ item.room_name || '-' }}
                </td>

                <!-- No Sertifikat & Vendor -->
                <td class="py-3 px-3">
                  <div class="font-mono font-bold text-slate-700">{{ item.certificate_number }}</div>
                  <div class="text-[10px] text-slate-500 truncate max-w-xs">{{ item.vendor_name }}</div>
                </td>

                <!-- Masa Berlaku -->
                <td class="py-3 px-3">
                  <div 
                    class="font-bold font-mono"
                    :class="[item.urgency_status === 'expired' ? 'text-rose-600 font-black' : 'text-amber-700 font-bold']"
                  >
                    {{ item.valid_until }}
                  </div>
                  <div 
                    class="text-[10px] font-semibold mt-0.5"
                    :class="[item.urgency_status === 'expired' ? 'text-rose-500' : 'text-amber-600']"
                  >
                    <span v-if="item.days_remaining <= 0">
                      Lewat {{ Math.abs(item.days_remaining) }} Hari Lalu
                    </span>
                    <span v-else>
                      Sisa {{ item.days_remaining }} Hari Lagi
                    </span>
                  </div>
                </td>

                <!-- Status Urgensi Badge -->
                <td class="py-3 px-3">
                  <span 
                    v-if="item.urgency_status === 'expired'"
                    class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-700 border border-rose-200 inline-flex items-center gap-1 shadow-sm"
                  >
                    <AlertOctagon class="w-3 h-3 text-rose-600" />
                    SUDAH HABIS
                  </span>
                  <span 
                    v-else
                    class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-200 inline-flex items-center gap-1 shadow-sm"
                  >
                    <Clock class="w-3 h-3 text-amber-600" />
                    MENDEKATI JATUH TEMPO
                  </span>
                </td>

                <!-- Aksi -->
                <td class="py-3 px-3 text-right whitespace-nowrap">
                  <router-link 
                    :to="`/equipment/${item.equipment_id}`" 
                    class="px-2.5 py-1 text-[11px] font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors inline-block"
                  >
                    Detail Alkes
                  </router-link>
                </td>
              </tr>

              <!-- Empty State -->
              <tr v-if="displayedCalibAlerts.length === 0">
                <td colspan="7" class="py-8 text-center text-slate-400">
                  <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-2">
                    <CheckCircle2 class="w-5 h-5" />
                  </div>
                  <div class="text-xs font-bold text-slate-700">Kondisi Kalibrasi Aman</div>
                  <div class="text-[11px] text-slate-400 mt-0.5">
                    Tidak ada sertifikat kalibrasi yang habis masa berlakunya atau mendekati jatuh tempo pada kategori ini.
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Equipment Condition Distribution & Recent Tickets -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Card 1: Equipment Status -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
          <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
            <Stethoscope class="w-4 h-4 text-emerald-600" />
            Distribusi Kondisi Alkes
          </h3>

          <div class="space-y-3 pt-2">
            <div>
              <div class="flex justify-between text-xs font-semibold mb-1">
                <span class="text-emerald-700">Operasional Normal</span>
                <span>{{ summary.equipment.operasional }} unit</span>
              </div>
              <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                <div 
                  class="bg-emerald-500 h-2 rounded-full" 
                  :style="{ width: ((summary.equipment.operasional / summary.equipment.total) * 100 || 0) + '%' }"
                ></div>
              </div>
            </div>

            <div>
              <div class="flex justify-between text-xs font-semibold mb-1">
                <span class="text-amber-600">Rusak Ringan</span>
                <span>{{ summary.equipment.rusak_ringan }} unit</span>
              </div>
              <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                <div 
                  class="bg-amber-400 h-2 rounded-full" 
                  :style="{ width: ((summary.equipment.rusak_ringan / summary.equipment.total) * 100 || 0) + '%' }"
                ></div>
              </div>
            </div>

            <div>
              <div class="flex justify-between text-xs font-semibold mb-1">
                <span class="text-rose-600">Rusak Berat</span>
                <span>{{ summary.equipment.rusak_berat }} unit</span>
              </div>
              <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                <div 
                  class="bg-rose-500 h-2 rounded-full" 
                  :style="{ width: ((summary.equipment.rusak_berat / summary.equipment.total) * 100 || 0) + '%' }"
                ></div>
              </div>
            </div>
          </div>

          <div class="pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
            <span>Total Aset Terdata:</span>
            <strong class="text-slate-800 font-bold">{{ summary.equipment.total }} Unit</strong>
          </div>
        </div>

        <!-- Card 2: Recent Work Orders -->
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
              <Wrench class="w-4 h-4 text-emerald-600" />
              Laporan Kerusakan & Servis Terbaru
            </h3>
            <router-link to="/tickets" class="text-xs font-bold text-emerald-600 hover:text-emerald-700">
              Lihat Semua &rarr;
            </router-link>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                <tr>
                  <th class="py-2.5 px-3 font-semibold">No. Tiket</th>
                  <th class="py-2.5 px-3 font-semibold">Alat Medis</th>
                  <th class="py-2.5 px-3 font-semibold">Ruangan</th>
                  <th class="py-2.5 px-3 font-semibold">Prioritas</th>
                  <th class="py-2.5 px-3 font-semibold">Status</th>
                  <th class="py-2.5 px-3 font-semibold text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="ticket in summary.recent_tickets" :key="ticket.id" class="hover:bg-slate-50/80 transition-colors">
                  <td class="py-3 px-3 font-bold text-slate-800">{{ ticket.ticket_number }}</td>
                  <td class="py-3 px-3">
                    <div class="font-semibold text-slate-800">{{ ticket.equipment_name }}</div>
                    <div class="text-[10px] text-slate-400">{{ ticket.asset_code }}</div>
                  </td>
                  <td class="py-3 px-3 text-slate-600">{{ ticket.room_name }}</td>
                  <td class="py-3 px-3">
                    <span 
                      :class="[
                        'px-2 py-0.5 rounded text-[10px] font-bold uppercase',
                        ticket.priority === 'emergency' ? 'bg-rose-100 text-rose-700' :
                        ticket.priority === 'high' ? 'bg-orange-100 text-orange-700' :
                        ticket.priority === 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600'
                      ]"
                    >
                      {{ ticket.priority }}
                    </span>
                  </td>
                  <td class="py-3 px-3">
                    <span 
                      :class="[
                        'px-2 py-0.5 rounded-full text-[10px] font-bold',
                        ticket.status === 'closed' ? 'bg-emerald-100 text-emerald-700' :
                        ticket.status === 'in_progress' ? 'bg-blue-100 text-blue-700' :
                        ticket.status === 'completed_technician' ? 'bg-purple-100 text-purple-700' : 'bg-amber-100 text-amber-700'
                      ]"
                    >
                      {{ formatStatus(ticket.status) }}
                    </span>
                  </td>
                  <td class="py-3 px-3 text-right">
                    <router-link 
                      :to="`/tickets/${ticket.id}`" 
                      class="px-2.5 py-1 text-[11px] font-bold text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors inline-block"
                    >
                      Detail
                    </router-link>
                  </td>
                </tr>
                <tr v-if="!summary.recent_tickets || summary.recent_tickets.length === 0">
                  <td colspan="6" class="py-6 text-center text-slate-400">Tidak ada tiket perbaikan terbaru.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { 
  Sparkles, PlusCircle, CheckCircle2, Wrench, AlertTriangle, 
  Clock, Stethoscope, Award, AlertOctagon, ArrowRight, CalendarDays 
} from 'lucide-vue-next';
import { useAuthStore } from '../stores/authStore';
import axiosClient from '../api/axiosClient';

const authStore = useAuthStore();
const summary = ref(null);
const loading = ref(true);
const calibFilter = ref('all');

const fetchSummary = async () => {
  loading.value = true;
  try {
    const res = await axiosClient.get('/dashboard/summary');
    if (res.success && res.data) {
      summary.value = res.data;
    }
  } catch (err) {
    console.error('Error fetching dashboard summary:', err);
  } finally {
    loading.value = false;
  }
};

const calibAlerts = computed(() => {
  return summary.value?.calibration_alerts || [];
});

const calibCounts = computed(() => {
  const all = calibAlerts.value.length;
  const expired = calibAlerts.value.filter(a => a.urgency_status === 'expired').length;
  const expiring = calibAlerts.value.filter(a => a.urgency_status === 'expiring').length;
  return { all, expired, expiring };
});

const displayedCalibAlerts = computed(() => {
  if (calibFilter.value === 'expired') {
    return calibAlerts.value.filter(a => a.urgency_status === 'expired');
  }
  if (calibFilter.value === 'expiring') {
    return calibAlerts.value.filter(a => a.urgency_status === 'expiring');
  }
  return calibAlerts.value;
});

const formatStatus = (s) => {
  const map = {
    'reported': 'Laporan Masuk',
    'in_progress': 'Dikerjakan',
    'waiting_parts': 'Tunggu Part',
    'vendor_repair': 'Servis Vendor',
    'completed_technician': 'Uji Fungsi',
    'closed': 'Selesai / Ditutup',
    'cancelled': 'Dibatalkan'
  };
  return map[s] || s;
};

onMounted(() => {
  fetchSummary();
});
</script>
