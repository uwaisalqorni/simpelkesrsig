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
import { ref, onMounted } from 'vue';
import { FileBarChart, Printer, Building2, ShieldAlert, FileSpreadsheet } from 'lucide-vue-next';
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
