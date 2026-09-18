<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl font-black text-slate-800 tracking-tight flex items-center gap-2">
          <Wrench class="w-6 h-6 text-emerald-600" />
          Tiket Servis & Kerusakan (Corrective)
        </h1>
        <p class="text-xs text-slate-500 mt-0.5">Monitoring perbaikan alat medis, respon teknisi IPSRS, dan status penanganan.</p>
      </div>

      <router-link 
        to="/tickets/create" 
        class="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-sm transition-all"
      >
        <Plus class="w-4 h-4" />
        <span>Lapor Kerusakan Baru</span>
      </router-link>
    </div>

    <!-- Filter Tabs & Search Bar -->
    <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-3">
      <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0">
        <button 
          v-for="st in statusFilters" 
          :key="st.key"
          @click="setStatusFilter(st.key)"
          :class="[
            'px-3.5 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer',
            activeStatus === st.key ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
          ]"
        >
          {{ st.label }}
        </button>
      </div>

      <div class="relative w-full md:w-72 shrink-0">
        <input 
          v-model="searchQuery"
          @input="debounceSearch"
          type="text" 
          placeholder="Cari nomor tiket / alkes / kendala..."
          class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"
        />
      </div>
    </div>

    <!-- Tickets List Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
      <div v-if="loading" class="py-12 flex items-center justify-center text-slate-500 text-xs">
        <div class="w-5 h-5 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mr-2"></div>
        Memuat daftar tiket...
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
            <tr>
              <th class="py-3 px-3 font-semibold text-center w-12">No</th>
              <th class="py-3 px-4 font-semibold">No. Tiket</th>
              <th class="py-3 px-4 font-semibold">Alat Medis</th>
              <th class="py-3 px-4 font-semibold">Ruangan / Pelapor</th>
              <th class="py-3 px-4 font-semibold">Kendala / Keluhan</th>
              <th class="py-3 px-4 font-semibold">Prioritas</th>
              <th class="py-3 px-4 font-semibold">Teknisi PJ</th>
              <th class="py-3 px-4 font-semibold">Status</th>
              <th class="py-3 px-4 font-semibold text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="(wo, index) in tickets" :key="wo.id" class="hover:bg-slate-50/80 transition-colors">
              <!-- Kolom No -->
              <td class="py-3 px-3 text-center font-medium text-slate-500">
                {{ (pagination.page - 1) * pagination.limit + index + 1 }}
              </td>

              <!-- No. Tiket -->
              <td class="py-3 px-4 font-mono font-bold text-slate-800">
                {{ wo.ticket_number }}
                <div class="text-[10px] text-slate-400 font-sans font-normal">{{ wo.reported_at }}</div>
              </td>

              <!-- Alat Medis -->
              <td class="py-3 px-4">
                <div class="font-bold text-slate-800 text-[13px]">{{ wo.equipment_name }}</div>
                <div class="text-[10px] text-slate-400 font-mono">{{ wo.asset_code }}</div>
              </td>

              <!-- Ruangan & Pelapor -->
              <td class="py-3 px-4">
                <div class="font-semibold text-slate-700">{{ wo.room_name }}</div>
                <div class="text-[10px] text-slate-400">{{ wo.reported_by_name }}</div>
              </td>

              <!-- Kendala -->
              <td class="py-3 px-4 max-w-xs lg:max-w-sm">
                <div class="truncate text-slate-600 font-medium" :title="wo.issue_description">
                  {{ wo.issue_description }}
                </div>
              </td>

              <!-- Prioritas -->
              <td class="py-3 px-4">
                <span 
                  :class="[
                    'px-2 py-0.5 rounded text-[10px] font-bold uppercase',
                    wo.priority === 'emergency' ? 'bg-rose-100 text-rose-700' :
                    wo.priority === 'high' ? 'bg-orange-100 text-orange-700' :
                    wo.priority === 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600'
                  ]"
                >
                  {{ wo.priority }}
                </span>
              </td>

              <!-- Teknisi -->
              <td class="py-3 px-4 text-slate-700">
                <span v-if="wo.technician_name" class="font-medium text-emerald-700 flex items-center gap-1">
                  <UserCheck class="w-3 h-3" />
                  {{ wo.technician_name }}
                </span>
                <span v-else class="text-slate-400 italic">Belum ditugaskan</span>
              </td>

              <!-- Status -->
              <td class="py-3 px-4">
                <span 
                  :class="[
                    'px-2.5 py-1 rounded-full text-[10px] font-bold',
                    wo.status === 'closed' ? 'bg-emerald-100 text-emerald-700' :
                    wo.status === 'in_progress' ? 'bg-blue-100 text-blue-700' :
                    wo.status === 'completed_technician' ? 'bg-purple-100 text-purple-700' :
                    wo.status === 'waiting_parts' ? 'bg-amber-100 text-amber-700' : 'bg-slate-200 text-slate-700'
                  ]"
                >
                  {{ formatStatus(wo.status) }}
                </span>
              </td>

              <!-- Aksi -->
              <td class="py-3 px-4 text-right space-x-1.5 whitespace-nowrap">
                <router-link 
                  :to="`/tickets/${wo.id}`" 
                  class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold rounded-lg transition-colors inline-block"
                  title="Lihat Detail & Tindak Lanjut"
                >
                  Proses
                </router-link>

                <!-- Tombol Edit -->
                <button 
                  @click="openEditModal(wo)"
                  class="px-2.5 py-1 text-amber-700 hover:text-amber-800 bg-amber-50 hover:bg-amber-100 rounded-lg transition-colors inline-flex items-center gap-1 font-semibold cursor-pointer"
                  title="Edit Kendala / Prioritas Tiket"
                >
                  <Pencil class="w-3.5 h-3.5" />
                  <span>Edit</span>
                </button>

                <!-- Tombol Hapus: Jika status closed (selesai), disable dengan proteksi -->
                <button 
                  v-if="wo.status === 'closed'"
                  disabled
                  class="px-2.5 py-1 text-slate-400 bg-slate-100 rounded-lg inline-flex items-center gap-1 font-semibold cursor-not-allowed opacity-60"
                  title="Tiket yang sudah selesai (Closed) tidak dapat dihapus demi kepatuhan audit RS"
                >
                  <Lock class="w-3.5 h-3.5 text-slate-400" />
                  <span>Hapus</span>
                </button>
                <button 
                  v-else
                  @click="openDeleteModal(wo)"
                  class="px-2.5 py-1 text-rose-700 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 rounded-lg transition-colors inline-flex items-center gap-1 font-semibold cursor-pointer"
                  title="Hapus Tiket"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                  <span>Hapus</span>
                </button>
              </td>
            </tr>

            <tr v-if="tickets.length === 0">
              <td colspan="9" class="py-10 text-center text-slate-400">
                Tidak ada tiket perbaikan yang ditemukan.
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
                Menampilkan <strong class="font-bold text-slate-800">{{ (pagination.page - 1) * pagination.limit + 1 }}</strong> - <strong class="font-bold text-slate-800">{{ Math.min(pagination.page * pagination.limit, pagination.total) }}</strong> dari <strong class="font-bold text-slate-800">{{ pagination.total }}</strong> tiket
              </span>
              <span v-else>Total: 0 tiket</span>
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

    <!-- Modal Edit Tiket -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2">
            <Pencil class="w-4 h-4 text-amber-600" />
            <h3 class="font-bold text-sm text-slate-800">Edit Tiket {{ editForm.ticket_number }}</h3>
          </div>
          <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-800 cursor-pointer">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submitEditTicket" class="space-y-3">
          <div v-if="editError" class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl font-medium">
            {{ editError }}
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Alat Medis</label>
            <div class="px-3 py-2 bg-slate-100 rounded-lg text-xs font-medium text-slate-700">
              {{ editForm.equipment_name }} <span class="text-slate-400 font-mono">({{ editForm.asset_code }})</span>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Prioritas Penanganan *</label>
            <select 
              v-model="editForm.priority" 
              required
              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"
            >
              <option value="low">Rendah (Low)</option>
              <option value="medium">Sedang (Medium)</option>
              <option value="high">Tinggi (High)</option>
              <option value="emergency">Darurat (Emergency)</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Kendala / Kerusakan *</label>
            <textarea 
              v-model="editForm.issue_description" 
              rows="4" 
              required 
              placeholder="Jelaskan kendala, gejala kerusakan, atau pesan error alat..."
              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 leading-relaxed"
            ></textarea>
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
            <button 
              type="button" 
              @click="showEditModal = false" 
              :disabled="savingEdit"
              class="px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-lg cursor-pointer"
            >
              Batal
            </button>
            <button 
              type="submit" 
              :disabled="savingEdit"
              class="px-4 py-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm disabled:opacity-50 flex items-center gap-1.5 cursor-pointer"
            >
              <span v-if="savingEdit" class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              <span>{{ savingEdit ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Konfirmasi Hapus Tiket -->
    <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-md w-full overflow-hidden shadow-2xl border border-slate-100 animate-in fade-in zoom-in duration-150">
        <div class="p-6 text-center">
          <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-3">
            <AlertTriangle class="w-6 h-6" />
          </div>
          <h3 class="text-base font-bold text-slate-900 mb-1">Hapus Tiket Perbaikan?</h3>
          <p class="text-xs text-slate-500 mb-4 leading-relaxed">
            Apakah Anda yakin ingin menghapus tiket berikut? Tindakan ini tidak dapat dibatalkan.
          </p>

          <div v-if="ticketToDelete" class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 text-left mb-4 space-y-1">
            <div class="flex items-center justify-between text-xs">
              <span class="font-mono font-bold text-slate-800">{{ ticketToDelete.ticket_number }}</span>
              <span class="text-[10px] px-2 py-0.5 rounded font-bold uppercase bg-slate-200 text-slate-700">
                {{ formatStatus(ticketToDelete.status) }}
              </span>
            </div>
            <div class="text-xs font-bold text-slate-800">{{ ticketToDelete.equipment_name }}</div>
            <div class="text-[11px] text-slate-500">Ruangan: {{ ticketToDelete.room_name }}</div>
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
              @click="confirmDeleteTicket" 
              :disabled="deleting"
              class="px-5 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-sm transition-all disabled:opacity-50 flex items-center gap-1.5 cursor-pointer"
            >
              <span v-if="deleting" class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              <span>{{ deleting ? 'Menghapus...' : 'Ya, Hapus Tiket' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Wrench, Plus, UserCheck, Pencil, Trash2, Lock, AlertTriangle, X, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import axiosClient from '../../api/axiosClient';

const tickets = ref([]);
const loading = ref(true);
const activeStatus = ref('');
const searchQuery = ref('');

// Pagination state
const pagination = ref({
  total: 0,
  page: 1,
  limit: 10,
  total_pages: 1
});

const statusFilters = [
  { key: '', label: 'Semua Tiket' },
  { key: 'reported', label: 'Baru Masuk' },
  { key: 'in_progress', label: 'Sedang Dikerjakan' },
  { key: 'waiting_parts', label: 'Menunggu Part' },
  { key: 'completed_technician', label: 'Uji Fungsi / Serah Terima' },
  { key: 'closed', label: 'Selesai / Closed' }
];

const setStatusFilter = (st) => {
  activeStatus.value = st;
  pagination.value.page = 1;
  fetchTickets();
};

let searchTimeout = null;
const debounceSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    pagination.value.page = 1;
    fetchTickets();
  }, 300);
};

const fetchTickets = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (activeStatus.value) params.append('status', activeStatus.value);
    if (searchQuery.value) params.append('search', searchQuery.value);
    params.append('page', pagination.value.page);
    params.append('limit', pagination.value.limit);

    const res = await axiosClient.get(`/work-orders?${params.toString()}`);
    if (res.success) {
      tickets.value = res.data;
      if (res.pagination) {
        pagination.value = res.pagination;
      }
    }
  } catch (err) {
    console.error('Error fetching tickets:', err);
  } finally {
    loading.value = false;
  }
};

const goToPage = (p) => {
  if (p < 1 || p > pagination.value.total_pages) return;
  pagination.value.page = p;
  fetchTickets();
};

const changeLimit = (newLimit) => {
  pagination.value.limit = parseInt(newLimit, 10);
  pagination.value.page = 1;
  fetchTickets();
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

// Edit ticket state
const showEditModal = ref(false);
const savingEdit = ref(false);
const editError = ref('');
const editForm = ref({
  id: null,
  ticket_number: '',
  equipment_name: '',
  asset_code: '',
  priority: 'medium',
  issue_description: ''
});

const openEditModal = (wo) => {
  editForm.value = {
    id: wo.id,
    ticket_number: wo.ticket_number,
    equipment_name: wo.equipment_name,
    asset_code: wo.asset_code,
    priority: wo.priority || 'medium',
    issue_description: wo.issue_description || ''
  };
  editError.value = '';
  showEditModal.value = true;
};

const submitEditTicket = async () => {
  savingEdit.value = true;
  editError.value = '';
  try {
    const res = await axiosClient.post(`/work-orders/update/${editForm.value.id}`, {
      priority: editForm.value.priority,
      issue_description: editForm.value.issue_description
    });
    if (res.success) {
      showEditModal.value = false;
      fetchTickets();
    } else {
      editError.value = res.message || 'Gagal memperbarui tiket.';
    }
  } catch (err) {
    editError.value = err.response?.data?.message || 'Gagal memperbarui tiket.';
  } finally {
    savingEdit.value = false;
  }
};

// Delete ticket state
const showDeleteModal = ref(false);
const deleting = ref(false);
const deleteError = ref('');
const ticketToDelete = ref(null);

const openDeleteModal = (wo) => {
  if (wo.status === 'closed') {
    alert('Tiket yang sudah berstatus Selesai (Closed) tidak dapat dihapus.');
    return;
  }
  ticketToDelete.value = wo;
  deleteError.value = '';
  showDeleteModal.value = true;
};

const confirmDeleteTicket = async () => {
  if (!ticketToDelete.value) return;
  deleting.value = true;
  deleteError.value = '';
  try {
    const res = await axiosClient.post(`/work-orders/delete/${ticketToDelete.value.id}`);
    if (res.success) {
      showDeleteModal.value = false;
      ticketToDelete.value = null;
      fetchTickets();
    } else {
      deleteError.value = res.message || 'Gagal menghapus tiket.';
    }
  } catch (err) {
    deleteError.value = err.response?.data?.message || 'Gagal menghapus tiket.';
  } finally {
    deleting.value = false;
  }
};

const formatStatus = (s) => {
  const map = {
    'reported': 'Laporan Masuk',
    'in_progress': 'Dikerjakan',
    'waiting_parts': 'Tunggu Part',
    'vendor_repair': 'Servis Vendor',
    'completed_technician': 'Uji Fungsi',
    'closed': 'Selesai / Closed',
    'cancelled': 'Dibatalkan'
  };
  return map[s] || s;
};

onMounted(() => {
  fetchTickets();
});
</script>
