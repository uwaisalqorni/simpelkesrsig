<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl font-black text-slate-800 tracking-tight flex items-center gap-2">
          <DoorOpen class="w-6 h-6 text-emerald-600" />
          Master Ruangan & Unit Kerja RS
        </h1>
        <p class="text-xs text-slate-500 mt-0.5">Manajemen data unit pelayanan, poli, instalasi, dan penempatan alat medis rumah sakit.</p>
      </div>

      <button 
        v-if="authStore.isAdmin"
        @click="openAddModal"
        class="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-sm transition-all active:scale-95"
      >
        <Plus class="w-4 h-4" />
        <span>Tambah Ruangan Baru</span>
      </button>
    </div>

    <!-- Search / Filter -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div class="relative max-w-sm w-full">
        <input 
          v-model="searchQuery"
          type="text" 
          placeholder="Cari kode atau nama ruangan..."
          class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"
        />
      </div>

      <div class="text-xs text-slate-500">
        Total: <strong class="text-slate-800">{{ filteredRooms.length }}</strong> Unit Kerja
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
      <div v-if="loading" class="py-12 text-center text-slate-400 text-xs">
        <div class="w-5 h-5 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
        Memuat data ruangan...
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
            <tr>
              <th class="py-3 px-4 font-semibold">Kode Ruangan</th>
              <th class="py-3 px-4 font-semibold">Nama Unit / Ruangan</th>
              <th class="py-3 px-4 font-semibold">Gedung</th>
              <th class="py-3 px-4 font-semibold">Lantai</th>
              <th class="py-3 px-4 font-semibold text-center">Jumlah Alkes</th>
              <th class="py-3 px-4 font-semibold">Status</th>
              <th class="py-3 px-4 font-semibold text-right" v-if="authStore.isAdmin">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="r in filteredRooms" :key="r.id" class="hover:bg-slate-50/80 transition-colors">
              <td class="py-3 px-4 font-mono font-bold text-slate-800">
                <span class="px-2 py-0.5 bg-slate-100 rounded border border-slate-200">{{ r.code }}</span>
              </td>
              <td class="py-3 px-4 font-bold text-slate-800 text-[13px]">{{ r.name }}</td>
              <td class="py-3 px-4 text-slate-600">{{ r.building || '-' }}</td>
              <td class="py-3 px-4 text-slate-600">{{ r.floor || '-' }}</td>
              <td class="py-3 px-4 text-center font-bold text-slate-800">
                <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-700 rounded-full font-bold">
                  {{ r.total_equipment || 0 }} Unit
                </span>
              </td>
              <td class="py-3 px-4">
                <span 
                  :class="[
                    'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase',
                    r.is_active == 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600'
                  ]"
                >
                  {{ r.is_active == 1 ? 'Aktif' : 'Non-Aktif' }}
                </span>
              </td>
              <td class="py-3 px-4 text-right" v-if="authStore.isAdmin">
                <button 
                  @click="openEditModal(r)"
                  class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg text-xs transition-colors"
                >
                  Edit
                </button>
              </td>
            </tr>

            <tr v-if="filteredRooms.length === 0">
              <td colspan="7" class="py-8 text-center text-slate-400">Tidak ada data ruangan yang cocok.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Form Ruangan -->
    <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="font-bold text-sm text-slate-800">{{ editingId ? 'Edit Data Ruangan' : 'Tambah Ruangan Baru' }}</h3>
          <button @click="showModal = false" class="text-slate-400 hover:text-slate-800"><X class="w-5 h-5" /></button>
        </div>

        <form @submit.prevent="submitRoom" class="space-y-3">
          <div v-if="formError" class="p-2.5 bg-rose-50 text-rose-700 text-xs rounded-lg border border-rose-200">
            {{ formError }}
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Ruangan *</label>
            <input v-model="form.code" type="text" required placeholder="Misal: RM-IGD / RM-LAB" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Ruangan / Unit Pelayanan *</label>
            <input v-model="form.name" type="text" required placeholder="Instalasi Gawat Darurat (IGD)" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Gedung</label>
              <input v-model="form.building" type="text" placeholder="Gedung Utama / Bedah" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Lantai</label>
              <input v-model="form.floor" type="text" placeholder="Lantai 1 / 2" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Status Operasional</label>
            <SearchableSelect 
              v-model="form.is_active"
              :options="[
                { id: 1, name: 'Aktif Digunakan' },
                { id: 0, name: 'Non-Aktif / Renovasi' }
              ]"
              value-key="id"
              label-key="name"
              placeholder="Pilih status..."
            />
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
            <button type="button" @click="showModal = false" class="px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
            <button type="submit" :disabled="saving" class="px-4 py-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm disabled:opacity-50">
              {{ saving ? 'Menyimpan...' : 'Simpan Ruangan' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { DoorOpen, Plus, X } from 'lucide-vue-next';
import { useAuthStore } from '../../stores/authStore';
import axiosClient from '../../api/axiosClient';
import SearchableSelect from '../../components/SearchableSelect.vue';

const authStore = useAuthStore();
const rooms = ref([]);
const loading = ref(true);
const searchQuery = ref('');

const showModal = ref(false);
const editingId = ref(null);
const saving = ref(false);
const formError = ref('');

const form = ref({
  code: '',
  name: '',
  building: '',
  floor: '',
  is_active: 1
});

const filteredRooms = computed(() => {
  if (!searchQuery.value.trim()) return rooms.value;
  const q = searchQuery.value.toLowerCase();
  return rooms.value.filter(r => 
    r.name?.toLowerCase().includes(q) || 
    r.code?.toLowerCase().includes(q) ||
    r.building?.toLowerCase().includes(q)
  );
});

const fetchRooms = async () => {
  loading.value = true;
  try {
    const res = await axiosClient.get('/rooms');
    if (res.success) rooms.value = res.data;
  } catch (err) {
    console.error('Failed to fetch rooms:', err);
  } finally {
    loading.value = false;
  }
};

const openAddModal = () => {
  editingId.value = null;
  form.value = {
    code: '',
    name: '',
    building: '',
    floor: '',
    is_active: 1
  };
  formError.value = '';
  showModal.value = true;
};

const openEditModal = (r) => {
  editingId.value = r.id;
  form.value = {
    code: r.code,
    name: r.name,
    building: r.building || '',
    floor: r.floor || '',
    is_active: r.is_active
  };
  formError.value = '';
  showModal.value = true;
};

const submitRoom = async () => {
  saving.value = true;
  formError.value = '';
  try {
    const url = editingId.value ? `/rooms/update/${editingId.value}` : '/rooms';
    const res = await axiosClient.post(url, form.value);
    if (res.success) {
      showModal.value = false;
      fetchRooms();
    } else {
      formError.value = res.message || 'Gagal menyimpan data ruangan.';
    }
  } catch (err) {
    formError.value = err.response?.data?.message || 'Terjadi kesalahan sistem.';
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  fetchRooms();
});
</script>
