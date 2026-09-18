<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl font-black text-slate-800 tracking-tight flex items-center gap-2">
          <CalendarCheck class="w-6 h-6 text-emerald-600" />
          Pemeliharaan Preventif (Preventive Maintenance)
        </h1>
        <p class="text-xs text-slate-500 mt-0.5">Jadwal inspeksi rutin berkala, uji kelistrikan, dan lembar kerja elektromedis.</p>
      </div>

      <button 
        v-if="authStore.isAdmin || authStore.isTeknisi"
        @click="openAddScheduleModal"
        class="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-sm transition-all active:scale-95 cursor-pointer"
      >
        <Plus class="w-4 h-4" />
        <span>Buat Jadwal Baru</span>
      </button>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
        <button 
          v-for="st in [
            { key: '', label: 'Semua Jadwal' },
            { key: 'pending', label: 'Terjadwal' },
            { key: 'overdue', label: 'Terlambat (Overdue)' },
            { key: 'done', label: 'Selesai' }
          ]"
          :key="st.key"
          @click="changeStatusFilter(st.key)"
          :class="[
            'px-3.5 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer',
            statusFilter === st.key ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
          ]"
        >
          {{ st.label }}
        </button>
      </div>

      <div class="relative w-full sm:w-72 shrink-0">
        <Search class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
        <input 
          v-model="searchQuery"
          @input="onSearchInput"
          type="text" 
          placeholder="Cari alat medis, kode, ruangan..."
          class="w-full pl-9 pr-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"
        />
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
      <div v-if="loading" class="py-12 text-center text-slate-400 text-xs">
        <div class="w-5 h-5 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
        Memuat jadwal preventif...
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
            <tr>
              <th class="py-3 px-3 font-semibold text-center w-12">No.</th>
              <th class="py-3 px-4 font-semibold">Tanggal Jadwal</th>
              <th class="py-3 px-4 font-semibold">Alat Medis</th>
              <th class="py-3 px-4 font-semibold">Ruangan</th>
              <th class="py-3 px-4 font-semibold">Frekuensi</th>
              <th class="py-3 px-4 font-semibold">Status</th>
              <th class="py-3 px-4 font-semibold">Teknisi Pelaksana</th>
              <th class="py-3 px-4 font-semibold text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="(sch, index) in schedules" :key="sch.id" class="hover:bg-slate-50/80 transition-colors">
              <!-- No Urut -->
              <td class="py-3 px-3 text-center text-slate-400 font-medium">
                {{ (pagination.page - 1) * pagination.limit + index + 1 }}
              </td>

              <!-- Tanggal Jadwal -->
              <td class="py-3 px-4 font-bold text-slate-800 whitespace-nowrap">
                {{ sch.scheduled_date }}
              </td>

              <!-- Alat Medis -->
              <td class="py-3 px-4">
                <div class="font-bold text-slate-800">{{ sch.equipment_name }}</div>
                <div class="text-[10px] text-slate-400 font-mono">{{ sch.asset_code }}</div>
              </td>

              <!-- Ruangan -->
              <td class="py-3 px-4 text-slate-600">{{ sch.room_name }}</td>

              <!-- Frekuensi -->
              <td class="py-3 px-4">
                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-semibold text-[11px] uppercase">
                  {{ formatFrequency(sch.frequency) }}
                </span>
              </td>

              <!-- Status -->
              <td class="py-3 px-4">
                <span 
                  :class="[
                    'px-2.5 py-1 rounded-full text-[10px] font-bold uppercase inline-flex items-center gap-1',
                    sch.status === 'done' ? 'bg-emerald-100 text-emerald-700' :
                    sch.status === 'overdue' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700'
                  ]"
                >
                  <span class="w-1.5 h-1.5 rounded-full" :class="sch.status === 'done' ? 'bg-emerald-500' : sch.status === 'overdue' ? 'bg-rose-500' : 'bg-amber-500'"></span>
                  {{ sch.status === 'done' ? 'Selesai' : sch.status === 'overdue' ? 'Terlambat' : 'Terjadwal' }}
                </span>
              </td>

              <!-- Teknisi Pelaksana -->
              <td class="py-3 px-4 text-slate-700">{{ sch.technician_name || '-' }}</td>

              <!-- Aksi -->
              <td class="py-3 px-4 text-right space-x-1.5 whitespace-nowrap">
                <!-- Eksekusi PM -->
                <button 
                  v-if="sch.status !== 'done' && (authStore.isAdmin || authStore.isTeknisi)"
                  @click="openExecuteModal(sch)"
                  class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs transition-colors shadow-sm inline-flex items-center gap-1 cursor-pointer"
                  title="Eksekusi Lembar Kerja PM"
                >
                  <CheckSquare class="w-3.5 h-3.5" />
                  <span>Eksekusi</span>
                </button>

                <!-- Edit Jadwal -->
                <button 
                  v-if="authStore.isAdmin || authStore.isTeknisi"
                  @click="openEditModal(sch)"
                  class="px-2.5 py-1 text-amber-700 hover:text-amber-800 bg-amber-50 hover:bg-amber-100 rounded-lg transition-colors inline-flex items-center gap-1 font-semibold cursor-pointer"
                  title="Edit Jadwal Preventif"
                >
                  <Pencil class="w-3.5 h-3.5" />
                  <span>Edit</span>
                </button>

                <!-- Hapus (Proteksi status done) -->
                <template v-if="authStore.isAdmin || authStore.isTeknisi">
                  <span 
                    v-if="sch.status === 'done'"
                    class="inline-block"
                    title="Jadwal yang sudah selesai (Done) tidak dapat dihapus demi kepatuhan riwayat pemeliharaan alkes"
                  >
                    <button 
                      disabled
                      class="px-2.5 py-1 text-slate-400 bg-slate-100 rounded-lg cursor-not-allowed inline-flex items-center gap-1 font-semibold opacity-60"
                    >
                      <Lock class="w-3.5 h-3.5" />
                      <span>Hapus</span>
                    </button>
                  </span>
                  <button 
                    v-else
                    @click="openDeleteModal(sch)"
                    class="px-2.5 py-1 text-rose-700 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 rounded-lg transition-colors inline-flex items-center gap-1 font-semibold cursor-pointer"
                    title="Hapus Jadwal Preventif"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                    <span>Hapus</span>
                  </button>
                </template>
              </td>
            </tr>

            <tr v-if="schedules.length === 0">
              <td colspan="8" class="py-8 text-center text-slate-400">Tidak ada jadwal pemeliharaan preventif yang ditemukan.</td>
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
                Menampilkan <strong class="font-bold text-slate-800">{{ (pagination.page - 1) * pagination.limit + 1 }}</strong> - <strong class="font-bold text-slate-800">{{ Math.min(pagination.page * pagination.limit, pagination.total) }}</strong> dari <strong class="font-bold text-slate-800">{{ pagination.total }}</strong> jadwal
              </span>
              <span v-else>Total: 0 jadwal</span>
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

    <!-- Modal Buat Jadwal Baru -->
    <div v-if="showAddModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="font-bold text-sm text-slate-800">Buat Jadwal Pemeliharaan Preventif</h3>
          <button @click="showAddModal = false" class="text-slate-400 hover:text-slate-800 cursor-pointer"><X class="w-5 h-5" /></button>
        </div>

        <form @submit.prevent="submitAddSchedule" class="space-y-3">
          <div v-if="scheduleError" class="p-2.5 bg-rose-50 text-rose-700 text-xs rounded-lg border border-rose-200">
            {{ scheduleError }}
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">
              Pilih Alat Medis *
              <span v-if="authStore.user?.role === 'ruangan'" class="text-[10px] text-emerald-600 font-normal">
                (Unit {{ authStore.user.room_name || 'Login' }})
              </span>
            </label>
            <SearchableSelect 
              v-model="newScheduleForm.equipment_id"
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
              <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Jadwal *</label>
              <input v-model="newScheduleForm.scheduled_date" type="date" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Frekuensi Siklus *</label>
              <SearchableSelect 
                v-model="newScheduleForm.frequency"
                :options="frequencyOptions"
                value-key="id"
                label-key="name"
                placeholder="Pilih siklus..."
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Khusus / Instruksi Kerja</label>
            <textarea v-model="newScheduleForm.notes" rows="2" placeholder="Cek berkala kelistrikan dan filter..." class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
            <button type="button" @click="showAddModal = false" class="px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-100 rounded-lg cursor-pointer">Batal</button>
            <button type="submit" :disabled="savingSchedule" class="px-4 py-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm disabled:opacity-50 cursor-pointer">
              {{ savingSchedule ? 'Menyimpan...' : 'Jadwalkan' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Edit Jadwal -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h3 class="font-bold text-sm text-slate-800">Edit Jadwal Pemeliharaan Preventif</h3>
            <div class="text-[11px] text-slate-500">{{ editingSchedule?.equipment_name }} ({{ editingSchedule?.asset_code }})</div>
          </div>
          <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-800 cursor-pointer"><X class="w-5 h-5" /></button>
        </div>

        <form @submit.prevent="submitEditSchedule" class="space-y-3">
          <div v-if="editError" class="p-2.5 bg-rose-50 text-rose-700 text-xs rounded-lg border border-rose-200">
            {{ editError }}
          </div>

          <div v-if="editingSchedule?.status !== 'done'">
            <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Jadwal *</label>
            <input v-model="editForm.scheduled_date" type="date" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" />
          </div>

          <div v-if="editingSchedule?.status !== 'done'">
            <label class="block text-xs font-semibold text-slate-700 mb-1">Frekuensi Siklus *</label>
            <SearchableSelect 
              v-model="editForm.frequency"
              :options="frequencyOptions"
              value-key="id"
              label-key="name"
              placeholder="Pilih siklus..."
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Khusus / Instruksi Kerja</label>
            <textarea v-model="editForm.notes" rows="3" placeholder="Catatan inspeksi atau instruksi..." class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
            <button type="button" @click="showEditModal = false" class="px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-100 rounded-lg cursor-pointer">Batal</button>
            <button type="submit" :disabled="savingEdit" class="px-4 py-1.5 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-lg shadow-sm disabled:opacity-50 cursor-pointer">
              {{ savingEdit ? 'Memperbarui...' : 'Simpan Perubahan' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-100 space-y-4">
        <div class="flex items-center gap-3 text-rose-600">
          <div class="w-10 h-10 rounded-full bg-rose-100 flex items-center justify-center shrink-0">
            <Trash2 class="w-5 h-5" />
          </div>
          <div>
            <h3 class="font-bold text-sm text-slate-800">Hapus Jadwal Preventif</h3>
            <p class="text-xs text-slate-500">Tindakan ini tidak dapat dibatalkan.</p>
          </div>
        </div>

        <p class="text-xs text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-200">
          Apakah Anda yakin ingin menghapus jadwal pemeliharaan alat <strong class="text-slate-800">{{ deletingSchedule?.equipment_name }}</strong> pada tanggal <strong class="text-slate-800">{{ deletingSchedule?.scheduled_date }}</strong>?
        </p>

        <div v-if="deleteError" class="p-2.5 bg-rose-50 text-rose-700 text-xs rounded-lg border border-rose-200">
          {{ deleteError }}
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
          <button @click="showDeleteModal = false" class="px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-100 rounded-lg cursor-pointer">Batal</button>
          <button @click="confirmDeleteSchedule" :disabled="deleting" class="px-4 py-1.5 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-sm disabled:opacity-50 cursor-pointer">
            {{ deleting ? 'Menghapus...' : 'Ya, Hapus Jadwal' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Eksekusi PM -->
    <div v-if="selectedSchedule" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h3 class="font-bold text-sm text-slate-800">Lembar Kerja Preventif Elektromedis</h3>
            <div class="text-[11px] text-slate-500">{{ selectedSchedule.equipment_name }} ({{ selectedSchedule.asset_code }})</div>
          </div>
          <button @click="selectedSchedule = null" class="text-slate-400 hover:text-slate-800 cursor-pointer"><X class="w-5 h-5" /></button>
        </div>

        <form @submit.prevent="submitCompletePM" class="space-y-3">
          <div class="space-y-2 text-xs">
            <div class="font-semibold text-slate-700 mb-1">Checklist Pengujian Standar:</div>
            
            <label class="flex items-center gap-2 p-2 bg-slate-50 rounded-lg cursor-pointer">
              <input type="checkbox" v-model="checklist.cek_fisik" class="rounded text-emerald-600 focus:ring-emerald-500" />
              <span>Pemeriksaan fisik sasis, roda/kaki, kabel power & steker</span>
            </label>

            <label class="flex items-center gap-2 p-2 bg-slate-50 rounded-lg cursor-pointer">
              <input type="checkbox" v-model="checklist.kebocoran_arus" class="rounded text-emerald-600 focus:ring-emerald-500" />
              <span>Uji keselamatan listrik (Electrical Safety / Grounding)</span>
            </label>

            <label class="flex items-center gap-2 p-2 bg-slate-50 rounded-lg cursor-pointer">
              <input type="checkbox" v-model="checklist.uji_performa" class="rounded text-emerald-600 focus:ring-emerald-500" />
              <span>Uji performa output & sistem alarm darurat</span>
            </label>

            <label class="flex items-center gap-2 p-2 bg-slate-50 rounded-lg cursor-pointer">
              <input type="checkbox" v-model="checklist.kebersihan_filter" class="rounded text-emerald-600 focus:ring-emerald-500" />
              <span>Pembersihan saringan udara (air filter) & internal dust</span>
            </label>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Hasil Pemeriksaan</label>
            <textarea v-model="pmNotes" rows="2" placeholder="Semua parameter normal, arus bocor < 100uA..." class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
            <button type="button" @click="selectedSchedule = null" class="px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-100 rounded-lg cursor-pointer">Batal</button>
            <button type="submit" :disabled="executingPM" class="px-4 py-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm disabled:opacity-50 cursor-pointer">
              {{ executingPM ? 'Menyimpan...' : 'Simpan & Jadwalkan Ulang Otomatis' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { 
  CalendarCheck, Plus, X, Pencil, Trash2, Lock, 
  CheckSquare, Search, ChevronLeft, ChevronRight 
} from 'lucide-vue-next';
import { useAuthStore } from '../../stores/authStore';
import axiosClient from '../../api/axiosClient';
import SearchableSelect from '../../components/SearchableSelect.vue';

const authStore = useAuthStore();

const schedules = ref([]);
const equipmentList = ref([]);
const loading = ref(true);
const statusFilter = ref('');
const searchQuery = ref('');

const pagination = ref({
  page: 1,
  limit: 10,
  total: 0,
  total_pages: 1
});

const frequencyOptions = [
  { id: 'monthly', name: 'Bulanan (Monthly)' },
  { id: 'quarterly', name: '3 Bulan (Quarterly)' },
  { id: 'semi_annual', name: '6 Bulan (Semi-Annual)' },
  { id: 'annual', name: 'Tahunan (Annual)' }
];

const formatFrequency = (freq) => {
  const map = {
    'monthly': 'Bulanan',
    'quarterly': '3 Bulan',
    'semi_annual': '6 Bulan',
    'annual': 'Tahunan'
  };
  return map[freq] || freq;
};

// Filtered equipment list based on role
const mappedEquipmentList = computed(() => {
  if (authStore.user?.role === 'ruangan' && authStore.user?.room_id) {
    return equipmentList.value.filter(eq => eq.room_id == authStore.user.room_id);
  }
  return equipmentList.value;
});

// Pagination visible pages helper
const visiblePages = computed(() => {
  const total = pagination.value.total_pages;
  const current = pagination.value.page;
  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1);
  }
  const pages = [];
  pages.push(1);
  if (current > 3) pages.push('...');
  const start = Math.max(2, current - 1);
  const end = Math.min(total - 1, current + 1);
  for (let i = start; i <= end; i++) {
    pages.push(i);
  }
  if (current < total - 2) pages.push('...');
  pages.push(total);
  return pages;
});

let searchTimeout = null;
const onSearchInput = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    pagination.value.page = 1;
    fetchSchedules();
  }, 350);
};

const changeStatusFilter = (st) => {
  statusFilter.value = st;
  pagination.value.page = 1;
  fetchSchedules();
};

const goToPage = (p) => {
  if (p < 1 || p > pagination.value.total_pages) return;
  pagination.value.page = p;
  fetchSchedules();
};

const changeLimit = (val) => {
  pagination.value.limit = parseInt(val) || 10;
  pagination.value.page = 1;
  fetchSchedules();
};

// Fetch schedules from backend
const fetchSchedules = async () => {
  loading.value = true;
  try {
    const params = {
      page: pagination.value.page,
      limit: pagination.value.limit,
      search: searchQuery.value || undefined,
      status: statusFilter.value || undefined
    };
    const res = await axiosClient.get('/preventive/schedules', { params });
    if (res.success && res.data) {
      if (res.data.items && res.data.pagination) {
        schedules.value = res.data.items;
        pagination.value = res.data.pagination;
      } else {
        schedules.value = Array.isArray(res.data) ? res.data : [];
        pagination.value.total = schedules.value.length;
        pagination.value.total_pages = 1;
      }
    }
  } catch (e) {
    console.error('Error loading preventive schedules:', e);
  } finally {
    loading.value = false;
  }
};

const fetchEquipment = async () => {
  try {
    const res = await axiosClient.get('/equipment');
    if (res.success) equipmentList.value = res.data;
  } catch (e) {}
};

// State Modal Add
const showAddModal = ref(false);
const savingSchedule = ref(false);
const scheduleError = ref('');
const newScheduleForm = ref({
  equipment_id: '',
  scheduled_date: '',
  frequency: 'quarterly',
  notes: ''
});

const openAddScheduleModal = () => {
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 7);
  const dateStr = tomorrow.toISOString().split('T')[0];

  const defaultEquip = mappedEquipmentList.value[0]?.id || '';
  newScheduleForm.value = {
    equipment_id: defaultEquip,
    scheduled_date: dateStr,
    frequency: 'quarterly',
    notes: ''
  };
  scheduleError.value = '';
  showAddModal.value = true;
};

const submitAddSchedule = async () => {
  savingSchedule.value = true;
  scheduleError.value = '';
  try {
    const res = await axiosClient.post('/preventive/schedules', newScheduleForm.value);
    if (res.success) {
      showAddModal.value = false;
      fetchSchedules();
    } else {
      scheduleError.value = res.message || 'Gagal menambahkan jadwal.';
    }
  } catch (err) {
    scheduleError.value = err.response?.data?.message || 'Terjadi kesalahan sistem.';
  } finally {
    savingSchedule.value = false;
  }
};

// State Modal Edit
const showEditModal = ref(false);
const savingEdit = ref(false);
const editError = ref('');
const editingSchedule = ref(null);
const editForm = ref({
  scheduled_date: '',
  frequency: 'quarterly',
  notes: ''
});

const openEditModal = (sch) => {
  editingSchedule.value = sch;
  editForm.value = {
    scheduled_date: sch.scheduled_date || '',
    frequency: sch.frequency || 'quarterly',
    notes: sch.notes || ''
  };
  editError.value = '';
  showEditModal.value = true;
};

const submitEditSchedule = async () => {
  if (!editingSchedule.value) return;
  savingEdit.value = true;
  editError.value = '';
  try {
    const res = await axiosClient.post(`/preventive/update/${editingSchedule.value.id}`, editForm.value);
    if (res.success) {
      showEditModal.value = false;
      fetchSchedules();
    } else {
      editError.value = res.message || 'Gagal memperbarui jadwal.';
    }
  } catch (err) {
    editError.value = err.response?.data?.message || 'Terjadi kesalahan sistem saat update.';
  } finally {
    savingEdit.value = false;
  }
};

// State Modal Delete
const showDeleteModal = ref(false);
const deleting = ref(false);
const deleteError = ref('');
const deletingSchedule = ref(null);

const openDeleteModal = (sch) => {
  if (sch.status === 'done') {
    alert('Jadwal yang sudah selesai (Done) tidak dapat dihapus demi kepatuhan audit & riwayat pemeliharaan alkes.');
    return;
  }
  deletingSchedule.value = sch;
  deleteError.value = '';
  showDeleteModal.value = true;
};

const confirmDeleteSchedule = async () => {
  if (!deletingSchedule.value) return;
  deleting.value = true;
  deleteError.value = '';
  try {
    const res = await axiosClient.delete(`/preventive/delete/${deletingSchedule.value.id}`);
    if (res.success) {
      showDeleteModal.value = false;
      deletingSchedule.value = null;
      fetchSchedules();
    } else {
      deleteError.value = res.message || 'Gagal menghapus jadwal.';
    }
  } catch (err) {
    deleteError.value = err.response?.data?.message || 'Gagal menghapus jadwal preventif.';
  } finally {
    deleting.value = false;
  }
};

// State Modal Eksekusi PM
const selectedSchedule = ref(null);
const pmNotes = ref('');
const executingPM = ref(false);
const checklist = ref({
  cek_fisik: true,
  kebocoran_arus: true,
  uji_performa: true,
  kebersihan_filter: true
});

const openExecuteModal = (sch) => {
  selectedSchedule.value = sch;
  pmNotes.value = 'Pemeriksaan rutin selesai, alat siap operasional.';
};

const submitCompletePM = async () => {
  executingPM.value = true;
  try {
    const res = await axiosClient.post(`/preventive/complete/${selectedSchedule.value.id}`, {
      checklist_data: checklist.value,
      notes: pmNotes.value
    });
    if (res.success) {
      alert(res.message);
      selectedSchedule.value = null;
      fetchSchedules();
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menyimpan PM.');
  } finally {
    executingPM.value = false;
  }
};

onMounted(() => {
  fetchSchedules();
  fetchEquipment();
});
</script>
