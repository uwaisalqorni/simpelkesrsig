<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl font-black text-slate-800 tracking-tight flex items-center gap-2">
          <ShieldCheck class="w-6 h-6 text-emerald-600" />
          Audit Trail & Log Aktivitas Sistem
        </h1>
        <p class="text-xs text-slate-500 mt-0.5">Rekam jejak seluruh aktivitas pengguna, perubahan data alat, dan verifikasi tiket untuk kepatuhan tata kelola RS.</p>
      </div>
    </div>

    <!-- Search / Filter -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between gap-3">
      <div class="relative max-w-sm w-full">
        <input 
          v-model="search" 
          @input="debounceSearch"
          type="text" 
          placeholder="Cari aktivitas, nama user, atau tabel..."
          class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"
        />
      </div>

      <button @click="fetchLogs" class="px-3 py-1.5 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors">
        Refresh Log
      </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
      <div v-if="loading" class="py-12 text-center text-slate-400 text-xs">
        <div class="w-5 h-5 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
        Memuat log audit aktivitas...
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
            <tr>
              <th class="py-3 px-4 font-semibold">Waktu Aktivitas</th>
              <th class="py-3 px-4 font-semibold">Pengguna</th>
              <th class="py-3 px-4 font-semibold">Tindakan (Action)</th>
              <th class="py-3 px-4 font-semibold">Tabel Data</th>
              <th class="py-3 px-4 font-semibold">Detail Keterangan</th>
              <th class="py-3 px-4 font-semibold text-right">Alamat IP</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="log in logs" :key="log.id" class="hover:bg-slate-50/70 transition-colors">
              <td class="py-3 px-4 text-slate-500 font-mono text-[11px] whitespace-nowrap">
                {{ log.created_at }}
              </td>
              <td class="py-3 px-4">
                <div class="font-bold text-slate-800">{{ log.user_name || 'Sistem' }}</div>
                <div class="text-[10px] text-slate-400 uppercase">{{ log.user_role || '-' }}</div>
              </td>
              <td class="py-3 px-4">
                <span 
                  :class="[
                    'px-2 py-0.5 rounded text-[10px] font-bold font-mono',
                    log.action.includes('CREATE') ? 'bg-emerald-100 text-emerald-700' :
                    log.action.includes('VERIFY') ? 'bg-indigo-100 text-indigo-700' :
                    log.action.includes('DELETE') ? 'bg-rose-100 text-rose-700' :
                    log.action.includes('LOGIN') ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-700'
                  ]"
                >
                  {{ log.action }}
                </span>
              </td>
              <td class="py-3 px-4 font-mono text-slate-600 text-[11px]">{{ log.table_name || '-' }}</td>
              <td class="py-3 px-4 max-w-xl">
                <div class="truncate text-slate-700 font-mono text-[11px]" :title="log.details">{{ log.details }}</div>
              </td>
              <td class="py-3 px-4 text-right font-mono text-slate-400 text-[11px]">{{ log.ip_address || '127.0.0.1' }}</td>
            </tr>

            <tr v-if="logs.length === 0">
              <td colspan="6" class="py-8 text-center text-slate-400">Belum ada riwayat aktivitas yang tercatat.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { ShieldCheck } from 'lucide-vue-next';
import axiosClient from '../../api/axiosClient';

const logs = ref([]);
const loading = ref(true);
const search = ref('');
let debounceTimer = null;

const debounceSearch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    fetchLogs();
  }, 300);
};

const fetchLogs = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (search.value.trim()) params.append('search', search.value.trim());
    const res = await axiosClient.get(`/audit-logs?${params.toString()}`);
    if (res.success) logs.value = res.data;
  } catch (e) {
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchLogs();
});
</script>
