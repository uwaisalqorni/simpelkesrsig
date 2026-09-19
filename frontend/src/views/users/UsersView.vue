<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl font-black text-slate-800 tracking-tight flex items-center gap-2">
          <Users class="w-6 h-6 text-emerald-600" />
          Manajemen Pengguna & Staf
        </h1>
        <p class="text-xs text-slate-500 mt-0.5">Kelola akun pengguna, hak akses peran (Admin, Teknisi Elektromedis, PIC Ruangan), dan penempatan unit.</p>
      </div>

      <button 
        @click="openAddModal"
        class="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-sm transition-all active:scale-95"
      >
        <Plus class="w-4 h-4" />
        <span>Tambah Pengguna Baru</span>
      </button>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-3">
      <div class="flex items-center gap-2 overflow-x-auto">
        <button 
          v-for="rf in roleFilters" 
          :key="rf.key"
          @click="selectedRole = rf.key; fetchUsers()"
          :class="[
            'px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer',
            selectedRole === rf.key ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
          ]"
        >
          {{ rf.label }}
        </button>
      </div>

      <div class="flex flex-col sm:flex-row sm:items-center gap-2.5">
        <!-- Tenant Filter for Super Admin -->
        <div v-if="authStore.isSuperAdmin" class="flex items-center gap-1.5">
          <Building2 class="w-4 h-4 text-amber-600 shrink-0" />
          <select 
            v-model="selectedTenant" 
            @change="fetchUsers"
            class="px-3 py-1.5 text-xs border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-amber-500 font-medium cursor-pointer"
          >
            <option value="">🌐 Semua Faskes (Holding)</option>
            <option v-for="t in tenants" :key="t.id" :value="t.id">
              [{{ t.code }}] {{ t.name }}
            </option>
          </select>
        </div>

        <div class="relative w-full sm:w-64 shrink-0">
          <input 
            v-model="searchQuery"
            type="text" 
            placeholder="Cari nama atau username..."
            class="w-full px-3 py-1.5 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500"
          />
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
      <div v-if="loading" class="py-12 text-center text-slate-400 text-xs">
        <div class="w-5 h-5 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
        Memuat data pengguna...
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
            <tr>
              <th class="py-3 px-4 font-semibold">Nama Lengkap & Kontak</th>
              <th class="py-3 px-4 font-semibold">Username</th>
              <th v-if="authStore.isSuperAdmin" class="py-3 px-4 font-semibold">Fasilitas Kesehatan</th>
              <th class="py-3 px-4 font-semibold">Peran (Role)</th>
              <th class="py-3 px-4 font-semibold">Unit Kerja / Ruangan</th>
              <th class="py-3 px-4 font-semibold">Status</th>
              <th class="py-3 px-4 font-semibold text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="u in filteredUsers" :key="u.id" class="hover:bg-slate-50/80 transition-colors">
              <td class="py-3 px-4">
                <div class="font-bold text-slate-800 text-[13px]">{{ u.full_name }}</div>
                <div class="text-[11px] text-slate-400">{{ u.phone || '-' }}</div>
              </td>
              <td class="py-3 px-4 font-mono font-semibold text-slate-700">@{{ u.username }}</td>
              
              <!-- Tenant Column for Super Admin -->
              <td v-if="authStore.isSuperAdmin" class="py-3 px-4">
                <span v-if="u.role === 'super_admin'" class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-purple-100 text-purple-800 border border-purple-200">
                  👑 Holding Global
                </span>
                <div v-else-if="u.tenant_name" class="flex items-center gap-1.5">
                  <Building2 class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                  <div>
                    <div class="font-bold text-slate-800 text-xs">{{ u.tenant_name }}</div>
                    <span class="text-[9px] font-mono font-bold px-1 py-0.2 bg-slate-100 text-slate-600 rounded">{{ u.tenant_code }}</span>
                  </div>
                </div>
                <span v-else class="text-slate-400 text-xs">-</span>
              </td>

              <td class="py-3 px-4">
                <span 
                  :class="[
                    'px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider',
                    u.role === 'super_admin' ? 'bg-purple-100 text-purple-800' :
                    u.role === 'admin' ? 'bg-indigo-100 text-indigo-700' :
                    u.role === 'teknisi' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'
                  ]"
                >
                  {{ u.role === 'super_admin' ? 'Super Admin' : u.role }}
                </span>
              </td>
              <td class="py-3 px-4 text-slate-700">
                <div class="font-medium">{{ u.room_name || 'Semua Ruangan (IPSRS)' }}</div>
              </td>
              <td class="py-3 px-4">
                <span 
                  :class="[
                    'px-2 py-0.5 rounded text-[10px] font-bold uppercase',
                    u.is_active == 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'
                  ]"
                >
                  {{ u.is_active == 1 ? 'Aktif' : 'Non-Aktif' }}
                </span>
              </td>
              <td class="py-3 px-4 text-right">
                <button 
                  @click="openEditModal(u)"
                  class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg text-xs transition-colors cursor-pointer"
                >
                  Edit Akun
                </button>
              </td>
            </tr>

            <tr v-if="filteredUsers.length === 0">
              <td :colspan="authStore.isSuperAdmin ? 7 : 6" class="py-8 text-center text-slate-400">
                Tidak ada data pengguna yang sesuai.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Tambah / Edit Pengguna -->
    <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="font-bold text-sm text-slate-800">{{ editingId ? 'Edit Data Pengguna' : 'Tambah Pengguna Baru' }}</h3>
          <button @click="showModal = false" class="text-slate-400 hover:text-slate-800 cursor-pointer"><X class="w-5 h-5" /></button>
        </div>

        <form @submit.prevent="submitUser" class="space-y-3">
          <div v-if="formError" class="p-2.5 bg-rose-50 text-rose-700 text-xs rounded-lg border border-rose-200">
            {{ formError }}
          </div>

          <!-- Tenant Selection for Super Admin -->
          <div v-if="authStore.isSuperAdmin">
            <label class="block text-xs font-semibold text-slate-700 mb-1">Fasilitas Kesehatan (Tenant) *</label>
            <div v-if="form.role === 'super_admin'" class="p-2.5 bg-purple-50 border border-purple-200 rounded-lg text-xs font-bold text-purple-800 flex items-center gap-2">
              <span>👑 Peran Super Admin memiliki akses holding global ke seluruh fasilitas kesehatan.</span>
            </div>
            <SearchableSelect 
              v-else
              v-model="form.tenant_id"
              :options="tenants"
              value-key="id"
              label-key="name"
              sublabel-key="code"
              placeholder="Pilih Rumah Sakit / Klinik..."
              @update:model-value="onTenantChanged"
            />
          </div>

          <!-- Locked Tenant Notice for Regular Admin -->
          <div v-else>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Fasilitas Kesehatan</label>
            <div class="px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-700 font-bold flex items-center gap-2">
              <Building2 class="w-4 h-4 text-emerald-600 shrink-0" />
              <span>{{ authStore.tenantName }}</span>
              <span class="text-[10px] text-slate-400 font-normal ml-auto">(Terkunci ke faskes Anda)</span>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap & Gelar *</label>
            <input v-model="form.full_name" type="text" required placeholder="Contoh: Ahmad Fauzi, A.Md.T" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Username *</label>
              <input v-model="form.username" type="text" required :disabled="!!editingId" placeholder="username" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 disabled:bg-slate-100 font-mono" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor WhatsApp / HP</label>
              <input v-model="form.phone" type="text" placeholder="08123456789" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Peran Pengguna (Role) *</label>
              <SearchableSelect 
                v-model="form.role"
                :options="availableRoles"
                value-key="id"
                label-key="name"
                placeholder="Pilih peran..."
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Unit / Ruangan</label>
              <SearchableSelect 
                v-model="form.room_id"
                :options="rooms"
                value-key="id"
                label-key="name"
                sublabel-key="code"
                placeholder="Pilih ruangan..."
                search-placeholder="Ketik nama / kode ruangan..."
                allow-clear
                show-all-option
                all-option-label="Semua Ruangan (IPSRS)"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">
              {{ editingId ? 'Reset Password (kosongkan jika tidak diubah)' : 'Password Awal *' }}
            </label>
            <input v-model="form.password" type="password" :required="!editingId" placeholder="••••••••" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Status Akun</label>
            <SearchableSelect 
              v-model="form.is_active"
              :options="[
                { id: 1, name: 'Aktif Dapat Login' },
                { id: 0, name: 'Non-Aktif / Dibekukan' }
              ]"
              value-key="id"
              label-key="name"
              placeholder="Pilih status..."
            />
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
            <button type="button" @click="showModal = false" class="px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-100 rounded-lg cursor-pointer">Batal</button>
            <button type="submit" :disabled="saving" class="px-4 py-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm disabled:opacity-50 cursor-pointer">
              {{ saving ? 'Menyimpan...' : 'Simpan Pengguna' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Users, Plus, X, Building2 } from 'lucide-vue-next';
import axiosClient from '../../api/axiosClient';
import { useAuthStore } from '../../stores/authStore';
import SearchableSelect from '../../components/SearchableSelect.vue';

const authStore = useAuthStore();

const users = ref([]);
const rooms = ref([]);
const tenants = ref([]);
const loading = ref(true);
const selectedRole = ref('');
const selectedTenant = ref('');
const searchQuery = ref('');

const showModal = ref(false);
const editingId = ref(null);
const saving = ref(false);
const formError = ref('');

const roleFilters = [
  { key: '', label: 'Semua Peran' },
  { key: 'admin', label: 'Admin IPSRS' },
  { key: 'teknisi', label: 'Teknisi Elektromedis' },
  { key: 'ruangan', label: 'PIC Ruangan' }
];

const availableRoles = computed(() => {
  const list = [
    { id: 'admin', name: 'Administrator IPSRS / Faskes' },
    { id: 'teknisi', name: 'Teknisi Elektromedis' },
    { id: 'ruangan', name: 'PIC Unit / Ruangan' }
  ];
  if (authStore.isSuperAdmin) {
    list.unshift({ id: 'super_admin', name: '👑 Super Admin (Holding Global)' });
  }
  return list;
});

const form = ref({
  full_name: '',
  username: '',
  phone: '',
  role: 'ruangan',
  tenant_id: 1,
  room_id: '',
  password: '',
  is_active: 1
});

const filteredUsers = computed(() => {
  let list = users.value;
  if (selectedRole.value) {
    list = list.filter(u => u.role === selectedRole.value);
  }
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase();
    list = list.filter(u => 
      u.full_name?.toLowerCase().includes(q) || 
      u.username?.toLowerCase().includes(q) ||
      u.tenant_name?.toLowerCase().includes(q) ||
      u.tenant_code?.toLowerCase().includes(q)
    );
  }
  return list;
});

const fetchTenants = async () => {
  if (!authStore.isSuperAdmin) return;
  try {
    const res = await axiosClient.get('/tenants');
    if (res.success && Array.isArray(res.data)) {
      tenants.value = res.data;
    }
  } catch (err) {
    console.error('Failed to fetch tenants:', err);
  }
};

const fetchUsers = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (selectedRole.value) params.append('role', selectedRole.value);
    if (authStore.isSuperAdmin && selectedTenant.value) {
      params.append('tenant_id', selectedTenant.value);
    }
    const res = await axiosClient.get(`/users?${params.toString()}`);
    if (res.success) users.value = res.data;
  } catch (err) {
    console.error('Failed to fetch users:', err);
  } finally {
    loading.value = false;
  }
};

const fetchRooms = async (tenantId = null) => {
  try {
    const url = tenantId ? `/rooms?tenant_id=${tenantId}` : '/rooms';
    const res = await axiosClient.get(url);
    if (res.success) rooms.value = res.data;
  } catch (e) {
    console.error('Failed to fetch rooms:', e);
  }
};

const onTenantChanged = async (tenantId) => {
  if (!tenantId) return;
  await fetchRooms(tenantId);
};

const openAddModal = () => {
  editingId.value = null;
  const initialTenantId = authStore.activeTenantId || (tenants.value[0]?.id || 1);
  form.value = {
    full_name: '',
    username: '',
    phone: '',
    role: 'ruangan',
    tenant_id: initialTenantId,
    room_id: '',
    password: 'password123',
    is_active: 1
  };
  if (authStore.isSuperAdmin) {
    onTenantChanged(initialTenantId);
  } else {
    fetchRooms();
  }
  formError.value = '';
  showModal.value = true;
};

const openEditModal = (u) => {
  editingId.value = u.id;
  form.value = {
    full_name: u.full_name,
    username: u.username,
    phone: u.phone || '',
    role: u.role,
    tenant_id: u.tenant_id || 1,
    room_id: u.room_id || '',
    password: '',
    is_active: u.is_active
  };
  if (authStore.isSuperAdmin && u.tenant_id) {
    onTenantChanged(u.tenant_id);
  } else {
    fetchRooms();
  }
  formError.value = '';
  showModal.value = true;
};

const submitUser = async () => {
  saving.value = true;
  formError.value = '';
  try {
    const url = editingId.value ? `/users/update/${editingId.value}` : '/users';
    const res = await axiosClient.post(url, form.value);
    if (res.success) {
      showModal.value = false;
      fetchUsers();
    } else {
      formError.value = res.message || 'Gagal menyimpan pengguna.';
    }
  } catch (err) {
    formError.value = err.response?.data?.message || 'Terjadi kesalahan sistem.';
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  if (authStore.isSuperAdmin) {
    fetchTenants();
  }
  fetchRooms();
  fetchUsers();
});
</script>
