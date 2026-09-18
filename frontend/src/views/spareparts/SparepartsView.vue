<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl font-black text-slate-800 tracking-tight flex items-center gap-2">
          <Boxes class="w-6 h-6 text-emerald-600" />
          Manajemen Suku Cadang Alkes
        </h1>
        <p class="text-xs text-slate-500 mt-0.5">Inventaris komponen pengganti, sensor, baterai, dan suku cadang elektromedis.</p>
      </div>

      <button 
        @click="openAddModal"
        class="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-sm transition-all"
      >
        <Plus class="w-4 h-4" />
        <span>Tambah Suku Cadang</span>
      </button>
    </div>

    <!-- Search / Filter -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div class="relative max-w-sm w-full">
        <input 
          v-model="searchQuery"
          type="text" 
          placeholder="Cari nama atau part number..."
          class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"
        />
      </div>

      <div class="text-xs text-slate-500">
        Total: <strong class="text-slate-800">{{ filteredParts.length }}</strong> Jenis Suku Cadang
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
      <div v-if="loading" class="py-12 text-center text-slate-400 text-xs">
        <div class="w-5 h-5 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
        Memuat data sparepart...
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
            <tr>
              <th class="py-3 px-4 font-semibold">Part Number</th>
              <th class="py-3 px-4 font-semibold">Nama Suku Cadang</th>
              <th class="py-3 px-4 font-semibold">Kategori</th>
              <th class="py-3 px-4 font-semibold text-center">Sisa Stok</th>
              <th class="py-3 px-4 font-semibold text-right">Harga Satuan</th>
              <th class="py-3 px-4 font-semibold text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="sp in filteredParts" :key="sp.id" class="hover:bg-slate-50/80 transition-colors">
              <td class="py-3 px-4 font-mono font-bold text-slate-800">{{ sp.part_number }}</td>
              <td class="py-3 px-4 font-bold text-slate-800">{{ sp.name }}</td>
              <td class="py-3 px-4 text-slate-600">{{ sp.category || '-' }}</td>
              <td class="py-3 px-4 text-center">
                <span 
                  :class="[
                    'px-2.5 py-0.5 rounded-full font-bold text-xs',
                    sp.stock_qty <= 2 ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'
                  ]"
                >
                  {{ sp.stock_qty }} unit
                </span>
              </td>
              <td class="py-3 px-4 text-right font-medium text-slate-800">
                Rp {{ Number(sp.unit_cost).toLocaleString('id-ID') }}
              </td>
              <td class="py-3 px-4 text-right">
                <button 
                  @click="openEditModal(sp)"
                  class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg text-xs"
                >
                  Edit Stok
                </button>
              </td>
            </tr>

            <tr v-if="parts.length === 0">
              <td colspan="6" class="py-8 text-center text-slate-400">Belum ada data suku cadang terdaftar.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Form Sparepart -->
    <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-100 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="font-bold text-sm text-slate-800">{{ editingId ? 'Edit Suku Cadang' : 'Tambah Suku Cadang' }}</h3>
          <button @click="showModal = false" class="text-slate-400 hover:text-slate-800"><X class="w-5 h-5" /></button>
        </div>

        <form @submit.prevent="submitPart" class="space-y-3">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Part (Part Number) *</label>
            <input v-model="form.part_number" type="text" required placeholder="SP-XXX-01" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Suku Cadang *</label>
            <input v-model="form.name" type="text" required placeholder="Sensor SpO2 Dewasa" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori</label>
            <input v-model="form.category" type="text" placeholder="Baterai / Sensor / Komponen" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg" />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Stok Tersedia *</label>
              <input v-model="form.stock_qty" type="number" min="0" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Biaya Satuan (Rp)</label>
              <input v-model="form.unit_cost" type="number" step="1000" min="0" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg" />
            </div>
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
            <button type="button" @click="showModal = false" class="px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
            <button type="submit" class="px-4 py-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm">
              Simpan
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Boxes, Plus, X } from 'lucide-vue-next';
import axiosClient from '../../api/axiosClient';

const parts = ref([]);
const loading = ref(true);
const showModal = ref(false);
const editingId = ref(null);
const searchQuery = ref('');

const filteredParts = computed(() => {
  if (!searchQuery.value) return parts.value;
  const q = searchQuery.value.toLowerCase();
  return parts.value.filter(sp => 
    (sp.name && sp.name.toLowerCase().includes(q)) ||
    (sp.part_number && sp.part_number.toLowerCase().includes(q)) ||
    (sp.category && sp.category.toLowerCase().includes(q))
  );
});

const form = ref({
  part_number: '',
  name: '',
  category: '',
  stock_qty: 0,
  unit_cost: 0
});

const fetchParts = async () => {
  loading.value = true;
  try {
    const res = await axiosClient.get('/spareparts');
    if (res.success) parts.value = res.data;
  } catch (e) {
  } finally {
    loading.value = false;
  }
};

const openAddModal = () => {
  editingId.value = null;
  form.value = {
    part_number: '',
    name: '',
    category: '',
    stock_qty: 0,
    unit_cost: 0
  };
  showModal.value = true;
};

const openEditModal = (sp) => {
  editingId.value = sp.id;
  form.value = {
    part_number: sp.part_number,
    name: sp.name,
    category: sp.category,
    stock_qty: sp.stock_qty,
    unit_cost: sp.unit_cost
  };
  showModal.value = true;
};

const submitPart = async () => {
  try {
    const url = editingId.value ? `/spareparts/update/${editingId.value}` : '/spareparts';
    const res = await axiosClient.post(url, form.value);
    if (res.success) {
      showModal.value = false;
      fetchParts();
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menyimpan sparepart.');
  }
};

onMounted(() => {
  fetchParts();
});
</script>
