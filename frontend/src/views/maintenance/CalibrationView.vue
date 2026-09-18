<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl font-black text-slate-800 tracking-tight flex items-center gap-2">
          <Award class="w-6 h-6 text-emerald-600" />
          Kalibrasi & Sertifikasi Laik Pakai
        </h1>
        <p class="text-xs text-slate-500 mt-0.5">Tracking masa berlaku sertifikat uji kalibrasi BPFK dan kepatuhan akreditasi rumah sakit.</p>
      </div>

      <button 
        v-if="authStore.isAdmin || authStore.isTeknisi"
        @click="openAddModal"
        class="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-sm transition-all cursor-pointer"
      >
        <Plus class="w-4 h-4" />
        <span>Catat Kalibrasi Baru</span>
      </button>
    </div>

    <!-- Expiring Warning Banner -->
    <div v-if="expiringLogs.length > 0" class="p-4 bg-amber-50 border border-amber-200 rounded-2xl flex items-start gap-3">
      <AlertTriangle class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
      <div>
        <div class="text-xs font-bold text-amber-900">Perhatian: Ada {{ expiringLogs.length }} Alat Medis yang Sertifikat Kalibrasinya Hampir / Sudah Kedaluwarsa</div>
        <div class="text-[11px] text-amber-700 mt-0.5">
          Segera lakukan koordinasi pengujian ulang dengan BPFK atau vendor kalibrasi terakreditasi sebelum masa berlaku habis.
        </div>
      </div>
    </div>

    <!-- Search / Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div class="relative max-w-sm w-full">
        <input 
          v-model="searchQuery"
          @input="debounceSearch"
          type="text" 
          placeholder="Cari alkes, nomor sertifikat, atau vendor..."
          class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"
        />
      </div>

      <!-- Info Ruangan jika Login sebagai Ruangan -->
      <div v-if="authStore.user?.role === 'ruangan'" class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-200">
        Unit Kerja: {{ authStore.user.room_name || 'Unit Anda' }} (Alat Medis Terfilter Sesuai Unit)
      </div>

      <div class="text-xs text-slate-500">
        Total: <strong class="text-slate-800">{{ pagination.total }}</strong> Catatan Sertifikat
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
      <div v-if="loading" class="py-12 text-center text-slate-400 text-xs">
        <div class="w-5 h-5 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
        Memuat riwayat kalibrasi...
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
            <tr>
              <th class="py-3 px-3 font-semibold text-center w-12">No</th>
              <th class="py-3 px-4 font-semibold">Alat Medis</th>
              <th class="py-3 px-4 font-semibold">Ruangan</th>
              <th class="py-3 px-4 font-semibold">Nomor Sertifikat</th>
              <th class="py-3 px-4 font-semibold">Lembaga Kalibrasi</th>
              <th class="py-3 px-4 font-semibold">Tgl Kalibrasi</th>
              <th class="py-3 px-4 font-semibold">Berlaku Sampai</th>
              <th class="py-3 px-4 font-semibold">Hasil Uji</th>
              <!-- Kolom Aksi Hanya untuk Admin -->
              <th v-if="authStore.isAdmin" class="py-3 px-4 font-semibold text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="(log, index) in logs" :key="log.id" class="hover:bg-slate-50/80 transition-colors">
              <!-- Kolom No -->
              <td class="py-3 px-3 text-center font-medium text-slate-500">
                {{ (pagination.page - 1) * pagination.limit + index + 1 }}
              </td>

              <!-- Alat Medis -->
              <td class="py-3 px-4">
                <div class="font-bold text-slate-800">{{ log.equipment_name }}</div>
                <div class="text-[10px] text-slate-400 font-mono">{{ log.asset_code }}</div>
              </td>

              <!-- Ruangan -->
              <td class="py-3 px-4 text-slate-600">{{ log.room_name }}</td>

              <!-- Nomor Sertifikat -->
              <td class="py-3 px-4 font-mono font-bold text-slate-700">{{ log.certificate_number }}</td>

              <!-- Vendor -->
              <td class="py-3 px-4 text-slate-600">{{ log.vendor_name }}</td>

              <!-- Tanggal Kalibrasi -->
              <td class="py-3 px-4 text-slate-600">{{ log.calibration_date }}</td>

              <!-- Masa Berlaku -->
              <td class="py-3 px-4">
                <div class="font-bold" :class="[log.days_remaining <= 0 ? 'text-rose-600' : log.days_remaining <= 30 ? 'text-amber-600' : 'text-slate-800']">
                  {{ log.valid_until }}
                </div>
                <div class="text-[10px]" :class="[log.days_remaining <= 0 ? 'text-rose-500 font-bold' : log.days_remaining <= 30 ? 'text-amber-500' : 'text-slate-400']">
                  {{ log.days_remaining <= 0 ? 'KEDALUWARSA' : `Sisa ${log.days_remaining} hari` }}
                </div>
              </td>

              <!-- Hasil Uji -->
              <td class="py-3 px-4">
                <span 
                  :class="[
                    'px-2.5 py-1 rounded-full text-[10px] font-bold uppercase',
                    log.result === 'laik_pakai' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'
                  ]"
                >
                  {{ log.result === 'laik_pakai' ? 'LAIK PAKAI' : 'TIDAK LAIK' }}
                </span>
              </td>

              <!-- Aksi (Khusus Admin) -->
              <td v-if="authStore.isAdmin" class="py-3 px-4 text-right space-x-1.5 whitespace-nowrap">
                <button 
                  @click="openEditModal(log)"
                  class="px-2.5 py-1 text-amber-700 hover:text-amber-800 bg-amber-50 hover:bg-amber-100 rounded-lg transition-colors inline-flex items-center gap-1 font-semibold cursor-pointer"
                  title="Edit Kalibrasi"
                >
                  <Pencil class="w-3.5 h-3.5" />
                  <span>Edit</span>
                </button>

                <button 
                  @click="openDeleteModal(log)"
                  class="px-2.5 py-1 text-rose-700 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 rounded-lg transition-colors inline-flex items-center gap-1 font-semibold cursor-pointer"
                  title="Hapus Kalibrasi"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                  <span>Hapus</span>
                </button>
              </td>
            </tr>

            <tr v-if="logs.length === 0">
              <td :colspan="authStore.isAdmin ? 9 : 8" class="py-8 text-center text-slate-400">
                Belum ada riwayat kalibrasi alkes yang ditemukan.
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination & Limit Footer -->
        <div class="px-5 py-3.5 bg-slate-50/80 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
          <!-- Limit Selector & Info Jumlah Data -->
          <div class="flex items-center flex-wrap gap-4 text-slate-600">
            <div class="flex items-center gap-2">
              <span class="text-[11px] font-medium text-slate-500">Baris per halaman:</span>
              <select 
                :value="pagination.limit" 
                @change="changeLimit($event.target.value)"
                class="px-2 py-1 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 cursor-pointer"
              >
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
            </div>
            <span class="text-slate-300 hidden sm:inline">|</span>
            <div class="text-[11px] text-slate-500">
              <span v-if="pagination.total > 0">
                Menampilkan <strong class="font-bold text-slate-800">{{ (pagination.page - 1) * pagination.limit + 1 }}</strong> - <strong class="font-bold text-slate-800">{{ Math.min(pagination.page * pagination.limit, pagination.total) }}</strong> dari <strong class="font-bold text-slate-800">{{ pagination.total }}</strong> sertifikat
              </span>
              <span v-else>Total: 0 sertifikat</span>
            </div>
          </div>

          <!-- Pagination Page Buttons -->
          <div class="flex items-center gap-1.5">
            <button 
              @click="goToPage(pagination.page - 1)" 
              :disabled="pagination.page <= 1 || loading"
              class="px-2.5 py-1 rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed font-medium transition-colors flex items-center gap-1 cursor-pointer"
            >
              <ChevronLeft class="w-3.5 h-3.5" />
              <span>Sebelumnya</span>
            </button>

            <!-- Numbered Pages -->
            <div class="flex items-center gap-1">
              <button 
                v-for="p in visiblePages" 
                :key="p"
                @click="p !== '...' && goToPage(p)"
                :disabled="p === '...' || p === pagination.page"
                :class="[
                  'min-w-[28px] h-7 text-xs font-bold rounded-lg transition-colors flex items-center justify-center',
                  p === pagination.page ? 'bg-emerald-600 text-white shadow-sm' : 
                  p === '...' ? 'text-slate-400 cursor-default px-1' :
                  'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100 cursor-pointer'
                ]"
              >
                {{ p }}
              </button>
            </div>

            <button 
              @click="goToPage(pagination.page + 1)" 
              :disabled="pagination.page >= pagination.total_pages || loading"
              class="px-2.5 py-1 rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed font-medium transition-colors flex items-center gap-1 cursor-pointer"
            >
              <span>Selanjutnya</span>
              <ChevronRight class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Catat Kalibrasi Baru -->
    <div v-if="showAddModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="font-bold text-sm text-slate-800">Input Sertifikat Kalibrasi Alkes</h3>
          <button @click="showAddModal = false" class="text-slate-400 hover:text-slate-800 cursor-pointer"><X class="w-5 h-5" /></button>
        </div>

        <form @submit.prevent="submitAddCalibration" class="space-y-3">
          <div v-if="saveError" class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl font-medium">
            {{ saveError }}
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">
              Pilih Alat Medis *
              <span v-if="authStore.user?.role === 'ruangan'" class="text-[10px] text-emerald-600 font-normal">
                (Sesuai Unit {{ authStore.user.room_name || 'Login' }})
              </span>
            </label>
            <SearchableSelect 
              v-model="form.equipment_id"
              :options="mappedEquipmentList"
              value-key="id"
              :format-label="(eq) => `${eq.asset_code} - ${eq.name}`"
              :format-sublabel="(eq) => `${eq.room_name} • SN: ${eq.serial_number || '-'}`"
              placeholder="Ketik untuk mencari alat medis..."
              search-placeholder="Ketik nama alat, kode aset, atau ruangan..."
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Kalibrasi *</label>
              <input v-model="form.calibration_date" type="date" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Berlaku Sampai *</label>
              <input v-model="form.valid_until" type="date" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg" />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Sertifikat Kalibrasi *</label>
            <input v-model="form.certificate_number" type="text" required placeholder="CERT-BPFK-XXXX" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Lembaga Kalibrasi / Vendor</label>
            <input v-model="form.vendor_name" type="text" placeholder="Balai Pengujian Fasilitas Kesehatan (BPFK)" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Hasil Uji Kelaikan</label>
            <SearchableSelect 
              v-model="form.result"
              :options="[
                { id: 'laik_pakai', name: 'Laik Pakai (Lolos Sertifikasi)' },
                { id: 'tidak_laik_pakai', name: 'Tidak Laik Pakai (Gagal Kalibrasi)' }
              ]"
              value-key="id"
              label-key="name"
              placeholder="Pilih hasil uji..."
            />
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
            <button type="button" @click="showAddModal = false" :disabled="saving" class="px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-100 rounded-lg cursor-pointer">Batal</button>
            <button type="submit" :disabled="saving" class="px-4 py-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm disabled:opacity-50 cursor-pointer">
              {{ saving ? 'Menyimpan...' : 'Simpan Sertifikat' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Edit Kalibrasi (Khusus Admin) -->
    <div v-if="showEditModal && authStore.isAdmin" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2">
            <Pencil class="w-4 h-4 text-amber-600" />
            <h3 class="font-bold text-sm text-slate-800">Edit Sertifikat Kalibrasi</h3>
          </div>
          <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-800 cursor-pointer"><X class="w-5 h-5" /></button>
        </div>

        <form @submit.prevent="submitEditCalibration" class="space-y-3">
          <div v-if="editError" class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl font-medium">
            {{ editError }}
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Alat Medis *</label>
            <SearchableSelect 
              v-model="editForm.equipment_id"
              :options="equipmentList"
              value-key="id"
              :format-label="(eq) => `${eq.asset_code} - ${eq.name}`"
              :format-sublabel="(eq) => `${eq.room_name} • SN: ${eq.serial_number || '-'}`"
              placeholder="Pilih alat medis..."
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Kalibrasi *</label>
              <input v-model="editForm.calibration_date" type="date" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Berlaku Sampai *</label>
              <input v-model="editForm.valid_until" type="date" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg" />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Sertifikat Kalibrasi *</label>
            <input v-model="editForm.certificate_number" type="text" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Lembaga Kalibrasi / Vendor</label>
            <input v-model="editForm.vendor_name" type="text" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Hasil Uji Kelaikan</label>
            <SearchableSelect 
              v-model="editForm.result"
              :options="[
                { id: 'laik_pakai', name: 'Laik Pakai (Lolos Sertifikasi)' },
                { id: 'tidak_laik_pakai', name: 'Tidak Laik Pakai (Gagal Kalibrasi)' }
              ]"
              value-key="id"
              label-key="name"
              placeholder="Pilih hasil uji..."
            />
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
            <button type="button" @click="showEditModal = false" :disabled="savingEdit" class="px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-100 rounded-lg cursor-pointer">Batal</button>
            <button type="submit" :disabled="savingEdit" class="px-4 py-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm disabled:opacity-50 cursor-pointer">
              {{ savingEdit ? 'Menyimpan...' : 'Simpan Perubahan' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Konfirmasi Hapus Kalibrasi (Khusus Admin) -->
    <div v-if="showDeleteModal && authStore.isAdmin" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-md w-full overflow-hidden shadow-2xl border border-slate-100 animate-in fade-in zoom-in duration-150">
        <div class="p-6 text-center">
          <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-3">
            <AlertTriangle class="w-6 h-6" />
          </div>
          <h3 class="text-base font-bold text-slate-900 mb-1">Hapus Data Kalibrasi?</h3>
          <p class="text-xs text-slate-500 mb-4 leading-relaxed">
            Apakah Anda yakin ingin menghapus data catatan sertifikat kalibrasi berikut?
          </p>

          <div v-if="logToDelete" class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 text-left mb-4 space-y-1">
            <div class="text-xs font-bold text-slate-800">{{ logToDelete.equipment_name }}</div>
            <div class="text-[11px] text-slate-600 font-mono">No. Sertifikat: {{ logToDelete.certificate_number }}</div>
            <div class="text-[10px] text-slate-500">Lembaga: {{ logToDelete.vendor_name }} • Berlaku s/d: {{ logToDelete.valid_until }}</div>
          </div>

          <div v-if="deleteError" class="p-3 mb-4 bg-rose-50 text-rose-700 text-xs rounded-lg border border-rose-200 text-left">
            {{ deleteError }}
          </div>

          <div class="flex items-center justify-center gap-2">
            <button 
              type="button" 
              @click="showDeleteModal = false" 
              :disabled="deleting"
              class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-lg transition-colors cursor-pointer"
            >
              Batal
            </button>
            <button 
              type="button" 
              @click="confirmDeleteCalibration" 
              :disabled="deleting"
              class="px-5 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-sm transition-all disabled:opacity-50 flex items-center gap-1.5 cursor-pointer"
            >
              <span v-if="deleting" class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              <span>{{ deleting ? 'Menghapus...' : 'Ya, Hapus Kalibrasi' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Award, Plus, AlertTriangle, X, Pencil, Trash2, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { useAuthStore } from '../../stores/authStore';
import axiosClient from '../../api/axiosClient';
import SearchableSelect from '../../components/SearchableSelect.vue';

const authStore = useAuthStore();
const logs = ref([]);
const expiringLogs = ref([]);
const equipmentList = ref([]);
const loading = ref(true);
const showAddModal = ref(false);
const searchQuery = ref('');

// Pagination state
const pagination = ref({
  total: 0,
  page: 1,
  limit: 10,
  total_pages: 1
});

// Mapping alkes sesuai ruangan login (untuk user role ruangan)
const mappedEquipmentList = computed(() => {
  if (authStore.user?.role === 'ruangan' && authStore.user?.room_id) {
    return equipmentList.value.filter(eq => eq.room_id == authStore.user.room_id);
  }
  return equipmentList.value;
});

let searchTimeout = null;
const debounceSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    pagination.value.page = 1;
    fetchCalibrations();
  }, 300);
};

const fetchCalibrations = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (searchQuery.value) params.append('search', searchQuery.value);
    params.append('page', pagination.value.page);
    params.append('limit', pagination.value.limit);

    // Jika user ruangan, kirimkan room_id
    if (authStore.user?.role === 'ruangan' && authStore.user?.room_id) {
      params.append('room_id', authStore.user.room_id);
    }

    const [resAll, resExp] = await Promise.all([
      axiosClient.get(`/calibrations?${params.toString()}`),
      axiosClient.get('/calibrations/expiring?days=30')
    ]);

    if (resAll.success) {
      logs.value = resAll.data;
      if (resAll.pagination) {
        pagination.value = resAll.pagination;
      }
    }
    if (resExp.success) expiringLogs.value = resExp.data;
  } catch (e) {
    console.error('Error fetching calibrations:', e);
  } finally {
    loading.value = false;
  }
};

const goToPage = (p) => {
  if (p < 1 || p > pagination.value.total_pages) return;
  pagination.value.page = p;
  fetchCalibrations();
};

const changeLimit = (newLimit) => {
  pagination.value.limit = parseInt(newLimit, 10);
  pagination.value.page = 1;
  fetchCalibrations();
};

const visiblePages = computed(() => {
  const total = pagination.value.total_pages || 1;
  const current = pagination.value.page || 1;
  const pages = [];

  if (total <= 7) {
    for (let i = 1; i <= total; i++) pages.push(i);
  } else {
    pages.push(1);
    if (current > 3) pages.push('...');
    
    const start = Math.max(2, current - 1);
    const end = Math.min(total - 1, current + 1);
    for (let i = start; i <= end; i++) {
      pages.push(i);
    }
    
    if (current < total - 2) pages.push('...');
    pages.push(total);
  }
  return pages;
});

const fetchEquipment = async () => {
  try {
    const params = new URLSearchParams();
    if (authStore.user?.role === 'ruangan' && authStore.user?.room_id) {
      params.append('room_id', authStore.user.room_id);
    }
    params.append('limit', '0'); // Ambil semua alat untuk dropdown
    const res = await axiosClient.get(`/equipment?${params.toString()}`);
    if (res.success) {
      equipmentList.value = res.data;
    }
  } catch (e) {}
};

// Form Tambah Kalibrasi
const form = ref({
  equipment_id: '',
  calibration_date: '',
  valid_until: '',
  certificate_number: '',
  vendor_name: 'Balai Pengujian Fasilitas Kesehatan (BPFK)',
  result: 'laik_pakai'
});

const saving = ref(false);
const saveError = ref('');

const openAddModal = () => {
  form.value = {
    equipment_id: mappedEquipmentList.value[0]?.id || '',
    calibration_date: new Date().toISOString().split('T')[0],
    valid_until: '',
    certificate_number: '',
    vendor_name: 'Balai Pengujian Fasilitas Kesehatan (BPFK)',
    result: 'laik_pakai'
  };
  saveError.value = '';
  showAddModal.value = true;
};

const submitAddCalibration = async () => {
  saving.value = true;
  saveError.value = '';
  try {
    const res = await axiosClient.post('/calibrations', form.value);
    if (res.success) {
      showAddModal.value = false;
      fetchCalibrations();
    } else {
      saveError.value = res.message || 'Gagal menyimpan sertifikat.';
    }
  } catch (err) {
    saveError.value = err.response?.data?.message || 'Gagal menyimpan sertifikat.';
  } finally {
    saving.value = false;
  }
};

// Form Edit Kalibrasi (Admin Only)
const showEditModal = ref(false);
const savingEdit = ref(false);
const editError = ref('');
const editForm = ref({
  id: null,
  equipment_id: '',
  calibration_date: '',
  valid_until: '',
  certificate_number: '',
  vendor_name: '',
  result: 'laik_pakai'
});

const openEditModal = (log) => {
  editForm.value = {
    id: log.id,
    equipment_id: log.equipment_id,
    calibration_date: log.calibration_date,
    valid_until: log.valid_until,
    certificate_number: log.certificate_number,
    vendor_name: log.vendor_name,
    result: log.result || 'laik_pakai'
  };
  editError.value = '';
  showEditModal.value = true;
};

const submitEditCalibration = async () => {
  savingEdit.value = true;
  editError.value = '';
  try {
    const res = await axiosClient.post(`/calibrations/update/${editForm.value.id}`, editForm.value);
    if (res.success) {
      showEditModal.value = false;
      fetchCalibrations();
    } else {
      editError.value = res.message || 'Gagal memperbarui sertifikat kalibrasi.';
    }
  } catch (err) {
    editError.value = err.response?.data?.message || 'Gagal memperbarui sertifikat kalibrasi.';
  } finally {
    savingEdit.value = false;
  }
};

// Delete Kalibrasi (Admin Only)
const showDeleteModal = ref(false);
const deleting = ref(false);
const deleteError = ref('');
const logToDelete = ref(null);

const openDeleteModal = (log) => {
  logToDelete.value = log;
  deleteError.value = '';
  showDeleteModal.value = true;
};

const confirmDeleteCalibration = async () => {
  if (!logToDelete.value) return;
  deleting.value = true;
  deleteError.value = '';
  try {
    const res = await axiosClient.post(`/calibrations/delete/${logToDelete.value.id}`);
    if (res.success) {
      showDeleteModal.value = false;
      logToDelete.value = null;
      fetchCalibrations();
    } else {
      deleteError.value = res.message || 'Gagal menghapus catatan kalibrasi.';
    }
  } catch (err) {
    deleteError.value = err.response?.data?.message || 'Gagal menghapus catatan kalibrasi.';
  } finally {
    deleting.value = false;
  }
};

onMounted(() => {
  fetchCalibrations();
  fetchEquipment();
});
</script>
