<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 print:hidden">
      <div>
        <h1 class="text-xl font-black text-slate-800 tracking-tight flex items-center gap-2">
          <FileBarChart class="w-6 h-6 text-emerald-600" />
          Laporan & Rekapitulasi Eksekutif IPSRS
        </h1>
        <p class="text-xs text-slate-500 mt-0.5">Laporan kelaikan alat medis, kepatuhan kalibrasi BPFK, dan indikator pemeliharaan standar akreditasi.</p>
      </div>

      <div class="flex items-center gap-2">
        <button 
          @click="handleExportExcel" 
          :disabled="!recap || exportingExcel"
          class="flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-sm transition-all cursor-pointer disabled:opacity-50 active:scale-95"
          title="Unduh rekapitulasi data dalam format Microsoft Excel (.xlsx)"
        >
          <FileSpreadsheet class="w-4 h-4" />
          <span>{{ exportingExcel ? 'Mengekspor...' : 'Export Excel' }}</span>
        </button>

        <button 
          @click="printReport"
          class="flex items-center gap-2 px-4 py-2 bg-slate-900 hover:bg-black text-white font-bold text-xs rounded-xl shadow-sm transition-all"
        >
          <Printer class="w-4 h-4" />
          <span>Cetak Laporan Resmi</span>
        </button>
      </div>
    </div>

    <!-- Printable Official Header (Only shown when printing) -->
    <div class="hidden print:block border-b-2 border-slate-900 pb-4 mb-6 text-center">
      <div class="flex items-center justify-center gap-4 mb-2">
        <img :src="settingStore.hospitalLogoUrl" class="w-14 h-14 object-contain" />
        <div class="text-left">
          <div class="text-xl font-extrabold tracking-tight uppercase text-slate-900">{{ settingStore.hospitalName }}</div>
          <div class="text-xs font-semibold text-slate-700 uppercase">{{ settingStore.hospitalSubtitle }}</div>
          <div class="text-[10px] text-slate-500">{{ settingStore.hospitalAddress }} &bull; Telp: {{ settingStore.hospitalPhone }}</div>
        </div>
      </div>
      <div class="mt-3 text-sm font-bold uppercase underline">LAPORAN REKAPITULASI PEMELIHARAAN & KELAIKAN ALAT KESEHATAN</div>
      <div class="text-[10px] text-slate-500 mt-0.5">Dicetak pada: {{ printDate }}</div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="py-12 text-center text-slate-400 text-xs">
      <div class="w-5 h-5 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
      Memuat rekapitulasi data...
    </div>

    <template v-else-if="recap">
      <!-- 3 Key Metric Summary Boxes -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm print:border-slate-400">
          <div class="text-xs font-bold text-slate-500 uppercase">Total Kalibrasi Bersertifikat</div>
          <div class="text-2xl font-black text-slate-900 mt-1">
            {{ recap.calibration_compliance?.total_calibrated || 0 }} Unit
          </div>
          <div class="text-[11px] text-emerald-600 font-bold mt-1">
            {{ recap.calibration_compliance?.laik_aktif || 0 }} Unit Laik Pakai Aktif
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm print:border-slate-400">
          <div class="text-xs font-bold text-slate-500 uppercase">Jatuh Tempo / Kedaluwarsa</div>
          <div class="text-2xl font-black text-rose-600 mt-1">
            {{ (recap.calibration_compliance?.expired || 0) + (recap.calibration_compliance?.expiring_soon || 0) }} Unit
          </div>
          <div class="text-[11px] text-rose-500 font-bold mt-1">
            {{ recap.calibration_compliance?.expired || 0 }} Expired &bull; {{ recap.calibration_compliance?.expiring_soon || 0 }} Segera Uji
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm print:border-slate-400">
          <div class="text-xs font-bold text-slate-500 uppercase">Tiket Servis Bulan Ini</div>
          <div class="text-2xl font-black text-slate-900 mt-1">
            {{ recap.ticket_recap?.total_tickets_month || 0 }} Laporan
          </div>
          <div class="text-[11px] text-blue-600 font-bold mt-1">
            {{ recap.ticket_recap?.closed_month || 0 }} Terselesaikan
          </div>
        </div>
      </div>

      <!-- ANALYTICS SECTION 1: Tren Kerusakan Bulanan & Top Ruangan -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Chart 1: Visual Bar Tren Bulanan (6 Bulan Terakhir) -->
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4 print:border-slate-400">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 pb-3">
            <div>
              <h3 class="font-bold text-sm text-slate-800 flex items-center gap-2">
                <BarChart3 class="w-4 h-4 text-emerald-600" />
                Tren Kerusakan & Penyelesaian Servis (6 Bulan Terakhir)
              </h3>
              <p class="text-[11px] text-slate-500 mt-0.5">Perbandingan tiket kerusakan dilaporkan vs yang selesai diperbaiki dan diverifikasi.</p>
            </div>

            <!-- Legend -->
            <div class="flex items-center gap-3 text-[11px] font-bold">
              <div class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded bg-blue-600 inline-block"></span>
                <span class="text-slate-600">Laporan Masuk</span>
              </div>
              <div class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded bg-emerald-500 inline-block"></span>
                <span class="text-slate-600">Selesai (Closed)</span>
              </div>
              <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-rose-500 inline-block animate-pulse"></span>
                <span class="text-slate-600">Darurat</span>
              </div>
            </div>
          </div>

          <!-- Bar Chart Canvas/Container -->
          <div class="pt-2 pb-2">
            <div class="h-56 flex items-end justify-between gap-2 sm:gap-4 px-2 border-b border-slate-200 relative">
              <!-- Y-Axis Guide Lines -->
              <div class="absolute inset-0 flex flex-col justify-between pointer-events-none opacity-40">
                <div class="border-b border-slate-200 border-dashed w-full h-0 flex items-center justify-end pr-1">
                  <span class="text-[9px] text-slate-400 bg-white px-1">{{ maxTrendVal }}</span>
                </div>
                <div class="border-b border-slate-200 border-dashed w-full h-0 flex items-center justify-end pr-1">
                  <span class="text-[9px] text-slate-400 bg-white px-1">{{ Math.round(maxTrendVal * 0.5) }}</span>
                </div>
                <div class="w-full h-0"></div>
              </div>

              <!-- Month Bars -->
              <div 
                v-for="item in recap.monthly_trends" 
                :key="item.period_ym"
                class="flex-1 flex flex-col items-center h-full justify-end group relative z-10"
              >
                <!-- Tooltip on hover -->
                <div class="absolute -top-10 opacity-0 group-hover:opacity-100 transition-opacity bg-slate-900 text-white text-[10px] font-bold py-1 px-2 rounded shadow pointer-events-none whitespace-nowrap z-20">
                  {{ item.month_label }}: {{ item.total_reported }} Masuk &bull; {{ item.total_closed }} Selesai
                  <span v-if="item.emergency_count > 0"> &bull; {{ item.emergency_count }} Darurat</span>
                </div>

                <!-- Emergency Indicator Tag -->
                <span 
                  v-if="item.emergency_count > 0" 
                  class="text-[9px] font-black text-rose-600 mb-1 bg-rose-100 px-1 py-0.5 rounded-full flex items-center gap-0.5"
                  title="Ada Tiket Emergency"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                  {{ item.emergency_count }}
                </span>

                <!-- Bars Pair -->
                <div class="w-full flex items-end justify-center gap-1 sm:gap-2 h-44">
                  <!-- Bar Reported -->
                  <div 
                    class="w-3.5 sm:w-5 bg-gradient-to-t from-blue-700 to-blue-500 rounded-t-md transition-all duration-300 relative group-hover:brightness-110 flex items-start justify-center"
                    :style="{ height: `${Math.max(6, (Number(item.total_reported) / maxTrendVal) * 100)}%` }"
                  >
                    <span v-if="item.total_reported > 0" class="text-[9px] font-bold text-white mt-1">
                      {{ item.total_reported }}
                    </span>
                  </div>

                  <!-- Bar Closed -->
                  <div 
                    class="w-3.5 sm:w-5 bg-gradient-to-t from-emerald-600 to-emerald-400 rounded-t-md transition-all duration-300 relative group-hover:brightness-110 flex items-start justify-center"
                    :style="{ height: `${Math.max(6, (Number(item.total_closed) / maxTrendVal) * 100)}%` }"
                  >
                    <span v-if="item.total_closed > 0" class="text-[9px] font-bold text-white mt-1">
                      {{ item.total_closed }}
                    </span>
                  </div>
                </div>

                <!-- Month Label -->
                <div class="mt-2 text-[10px] font-bold text-slate-600 text-center tracking-tight truncate w-full">
                  {{ item.month_label }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Chart 2: Top 5 Ruangan Kerusakan Tertinggi -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4 print:border-slate-400 flex flex-col justify-between">
          <div>
            <div class="border-b border-slate-100 pb-3">
              <h3 class="font-bold text-sm text-slate-800 flex items-center gap-2">
                <AlertTriangle class="w-4 h-4 text-amber-500" />
                Unit Ruangan Frekuensi Kerusakan Tertinggi
              </h3>
              <p class="text-[11px] text-slate-500 mt-0.5">Ranking unit kerja yang paling sering melaporkan kendala alat medis.</p>
            </div>

            <div class="space-y-3.5 pt-4">
              <div 
                v-for="(r, idx) in (recap.top_damaged_rooms || [])" 
                :key="r.room_id"
                class="space-y-1.5"
              >
                <div class="flex items-center justify-between text-xs">
                  <div class="font-bold text-slate-800 flex items-center gap-1.5">
                    <span 
                      :class="[
                        'w-4 h-4 rounded-full flex items-center justify-center text-[10px] font-extrabold text-white',
                        idx === 0 ? 'bg-rose-500' : idx === 1 ? 'bg-amber-500' : 'bg-slate-400'
                      ]"
                    >
                      {{ idx + 1 }}
                    </span>
                    <span>{{ r.room_name }}</span>
                  </div>
                  <div class="font-black text-slate-900">
                    {{ r.ticket_count }} Tiket
                  </div>
                </div>

                <!-- Progress Bar -->
                <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                  <div 
                    class="h-2 rounded-full transition-all duration-500"
                    :class="[
                      idx === 0 ? 'bg-rose-500' : idx === 1 ? 'bg-amber-500' : 'bg-indigo-500'
                    ]"
                    :style="{ width: `${(Number(r.ticket_count) / maxRoomDamageCount) * 100}%` }"
                  ></div>
                </div>

                <div class="flex justify-between text-[10px] text-slate-400">
                  <span>{{ r.affected_equipment_count }} alkes bermasalah</span>
                  <span>Total aset ruangan: {{ r.total_equipment }} unit</span>
                </div>
              </div>

              <div v-if="!recap.top_damaged_rooms || recap.top_damaged_rooms.length === 0" class="text-center py-6 text-xs text-slate-400">
                Belum ada data kerusakan unit tercatat.
              </div>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 text-[11px] text-slate-500 flex items-center justify-between">
            <span>Fokus inspeksi rutin preventif:</span>
            <strong class="text-emerald-700">Ruangan Prioritas</strong>
          </div>
        </div>
      </div>

      <!-- ANALYTICS SECTION 2: Kepatuhan Respon & SPM Standar Pelayanan Minimal IPSRS -->
      <div v-if="recap.spm_compliance" class="bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950 text-white rounded-2xl p-6 shadow-md border border-slate-700/60 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-700/80 pb-4">
          <div>
            <div class="flex items-center gap-2">
              <Zap class="w-5 h-5 text-amber-400" />
              <h3 class="text-base font-extrabold tracking-tight text-white">
                Kepatuhan Standar Pelayanan Minimal (SPM) Respon Teknisi
              </h3>
            </div>
            <p class="text-xs text-slate-300 mt-0.5">
              Tingkat kecepatan respon teknisi elektromedis terhitung sejak tiket dilaporkan hingga teknisi merespon penanganan.
            </p>
          </div>

          <div class="flex items-center gap-3">
            <div class="text-right">
              <div class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Kepatuhan SPM (&le; 30 Menit)</div>
              <div class="text-2xl font-black text-emerald-400 leading-tight">
                {{ spmCompliancePct }}%
              </div>
            </div>
            <div class="p-2.5 rounded-xl bg-emerald-500/20 border border-emerald-400/30 text-emerald-300">
              <CheckCircle2 class="w-6 h-6" />
            </div>
          </div>
        </div>

        <!-- 4 SPM Speed Brackets Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1">
          <!-- Bracket 1: <= 15 Min -->
          <div class="bg-white/10 backdrop-blur-sm rounded-xl p-3.5 border border-white/10 space-y-1">
            <div class="text-[10px] text-emerald-300 font-bold uppercase flex items-center justify-between">
              <span>Sangat Cepat</span>
              <span>&le; 15 Menit</span>
            </div>
            <div class="text-xl font-extrabold text-white">
              {{ recap.spm_compliance.fast_under_15m || 0 }} <span class="text-xs font-normal text-slate-300">Tiket</span>
            </div>
            <div class="text-[10px] text-emerald-200">Kategori Prioritas Darurat</div>
          </div>

          <!-- Bracket 2: 15 - 30 Min -->
          <div class="bg-white/10 backdrop-blur-sm rounded-xl p-3.5 border border-white/10 space-y-1">
            <div class="text-[10px] text-blue-300 font-bold uppercase flex items-center justify-between">
              <span>Standar SPM</span>
              <span>15 - 30 Menit</span>
            </div>
            <div class="text-xl font-extrabold text-white">
              {{ recap.spm_compliance.standard_15_30m || 0 }} <span class="text-xs font-normal text-slate-300">Tiket</span>
            </div>
            <div class="text-[10px] text-blue-200">Sesuai Regulasi Kemenkes</div>
          </div>

          <!-- Bracket 3: 30 - 60 Min -->
          <div class="bg-white/10 backdrop-blur-sm rounded-xl p-3.5 border border-white/10 space-y-1">
            <div class="text-[10px] text-amber-300 font-bold uppercase flex items-center justify-between">
              <span>Cukup</span>
              <span>30 - 60 Menit</span>
            </div>
            <div class="text-xl font-extrabold text-white">
              {{ recap.spm_compliance.moderate_30_60m || 0 }} <span class="text-xs font-normal text-slate-300">Tiket</span>
            </div>
            <div class="text-[10px] text-amber-200">Perlu Peningkatan Koordinasi</div>
          </div>

          <!-- Bracket 4: > 60 Min -->
          <div class="bg-white/10 backdrop-blur-sm rounded-xl p-3.5 border border-white/10 space-y-1">
            <div class="text-[10px] text-rose-300 font-bold uppercase flex items-center justify-between">
              <span>Terlambat</span>
              <span>&gt; 60 Menit</span>
            </div>
            <div class="text-xl font-extrabold text-white">
              {{ recap.spm_compliance.late_over_60m || 0 }} <span class="text-xs font-normal text-slate-300">Tiket</span>
            </div>
            <div class="text-[10px] text-rose-200">Melebihi Ambang Batas SPM</div>
          </div>
        </div>
      </div>

      <!-- Table 1: Rekapitulasi per Ruangan -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden print:border-slate-400">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
          <h3 class="font-bold text-sm text-slate-800 flex items-center gap-2">
            <Building2 class="w-4 h-4 text-emerald-600" />
            Distribusi Inventaris Alat Medis Berdasarkan Unit Kerja / Ruangan
          </h3>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 print:bg-slate-100">
              <tr>
                <th class="py-2.5 px-4 font-bold">Kode</th>
                <th class="py-2.5 px-4 font-bold">Nama Ruangan / Unit</th>
                <th class="py-2.5 px-4 font-bold text-center">Total Alkes</th>
                <th class="py-2.5 px-4 font-bold text-center text-emerald-700">Operasional</th>
                <th class="py-2.5 px-4 font-bold text-center text-amber-700">Rusak Ringan</th>
                <th class="py-2.5 px-4 font-bold text-center text-rose-700">Rusak Berat</th>
                <th class="py-2.5 px-4 font-bold text-right">Kesiapan Unit</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 print:divide-slate-200">
              <tr v-for="r in recap.by_room" :key="r.room_code" class="hover:bg-slate-50/70">
                <td class="py-2.5 px-4 font-mono font-bold">{{ r.room_code }}</td>
                <td class="py-2.5 px-4 font-bold text-slate-800">{{ r.room_name }}</td>
                <td class="py-2.5 px-4 text-center font-bold">{{ r.total_alkes }}</td>
                <td class="py-2.5 px-4 text-center font-semibold text-emerald-600">{{ r.operasional || 0 }}</td>
                <td class="py-2.5 px-4 text-center font-semibold text-amber-600">{{ r.rusak_ringan || 0 }}</td>
                <td class="py-2.5 px-4 text-center font-semibold text-rose-600">{{ r.rusak_berat || 0 }}</td>
                <td class="py-2.5 px-4 text-right font-bold text-slate-800">
                  {{ r.total_alkes > 0 ? Math.round((r.operasional / r.total_alkes) * 100) : 0 }}%
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Table 2: Rekapitulasi per Kategori Risiko -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden print:border-slate-400">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
          <h3 class="font-bold text-sm text-slate-800 flex items-center gap-2">
            <ShieldAlert class="w-4 h-4 text-emerald-600" />
            Distribusi Alat Medis Berdasarkan Klasifikasi Tingkat Risiko (Permenkes 65/2016)
          </h3>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 print:bg-slate-100">
              <tr>
                <th class="py-2.5 px-4 font-bold">Kategori Peralatan Medis</th>
                <th class="py-2.5 px-4 font-bold text-center">Tingkat Risiko</th>
                <th class="py-2.5 px-4 font-bold text-right">Jumlah Unit Terpasang</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="c in recap.by_category" :key="c.category_name">
                <td class="py-2.5 px-4 font-bold text-slate-800">{{ c.category_name }}</td>
                <td class="py-2.5 px-4 text-center">
                  <span 
                    :class="[
                      'px-2 py-0.5 rounded text-[10px] font-bold uppercase',
                      c.risk_level === 'high' ? 'bg-rose-100 text-rose-700' :
                      c.risk_level === 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600'
                    ]"
                  >
                    {{ c.risk_level }} RISK
                  </span>
                </td>
                <td class="py-2.5 px-4 text-right font-bold text-slate-900">{{ c.total_alkes }} Unit</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Printable Signature Footer -->
      <div class="hidden print:grid grid-cols-2 gap-8 pt-8 mt-8 border-t border-slate-400 text-center text-xs">
        <div>
          <div>Mengetahui,</div>
          <div class="font-bold text-slate-900 mt-0.5">Kepala {{ settingStore.hospitalSubtitle }}</div>
          <div class="text-[11px] text-slate-600">{{ settingStore.hospitalName }}</div>
          <div class="h-16"></div>
          <div class="font-bold underline">( Ahmad Elektromedik, S.Tr.Kes )</div>
          <div class="text-[10px] text-slate-500">NIP: 19850712 201001 1 002</div>
        </div>

        <div>
          <div>{{ settingStore.hospitalCity }}, {{ printDate }}</div>
          <div class="font-bold text-slate-900 mt-0.5">Petugas Penanggungjawab Sarpras</div>
          <div class="text-[11px] text-slate-600">{{ settingStore.hospitalName }}</div>
          <div class="h-16"></div>
          <div class="font-bold underline">( {{ authStore.userName }} )</div>
          <div class="text-[10px] text-slate-500">Administrator Sistem SIMPELKES</div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { 
  FileBarChart, Printer, Building2, ShieldAlert, FileSpreadsheet,
  TrendingUp, Activity, CheckCircle2, AlertTriangle, Clock, Zap, BarChart3 
} from 'lucide-vue-next';
import { useAuthStore } from '../../stores/authStore';
import { useSettingStore } from '../../stores/settingStore';
import { exportReportsToExcel } from '../../utils/exportUtils';
import axiosClient from '../../api/axiosClient';

const authStore = useAuthStore();
const settingStore = useSettingStore();
const recap = ref(null);
const loading = ref(true);
const exportingExcel = ref(false);
const printDate = new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });

// Maksimum nilai untuk scaling grafik batang bulanan
const maxTrendVal = computed(() => {
  if (!recap.value?.monthly_trends?.length) return 5;
  const max = Math.max(...recap.value.monthly_trends.map(t => Math.max(Number(t.total_reported), Number(t.total_closed))));
  return Math.max(5, max);
});

// Kepatuhan SPM % (<= 30 menit)
const spmCompliancePct = computed(() => {
  const spm = recap.value?.spm_compliance;
  if (!spm || !spm.total_with_response || Number(spm.total_with_response) === 0) return 0;
  const compliant = (Number(spm.fast_under_15m) || 0) + (Number(spm.standard_15_30m) || 0);
  return Math.round((compliant / Number(spm.total_with_response)) * 100);
});

// Nilai maksimum tiket ruangan untuk bar scaling
const maxRoomDamageCount = computed(() => {
  if (!recap.value?.top_damaged_rooms?.length) return 1;
  const max = Math.max(...recap.value.top_damaged_rooms.map(r => Number(r.ticket_count)));
  return Math.max(1, max);
});

const handleExportExcel = () => {
  if (!recap.value) return;
  exportingExcel.value = true;
  try {
    exportReportsToExcel(recap.value, {
      name: settingStore.hospitalName,
      subtitle: settingStore.hospitalSubtitle,
      address: settingStore.hospitalAddress,
      phone: settingStore.hospitalPhone
    });
  } catch (err) {
    console.error('Export Excel error:', err);
    alert('Gagal mengekspor laporan: ' + err.message);
  } finally {
    exportingExcel.value = false;
  }
};

const fetchRecap = async () => {
  loading.value = true;
  try {
    const res = await axiosClient.get('/reports/recap');
    if (res.success) recap.value = res.data;
  } catch (e) {
  } finally {
    loading.value = false;
  }
};

const printReport = () => {
  window.print();
};

onMounted(() => {
  fetchRecap();
});
</script>
