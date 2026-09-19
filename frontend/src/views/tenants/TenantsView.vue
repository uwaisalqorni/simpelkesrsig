<template>
  <div class="space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl font-black text-slate-800 tracking-tight flex items-center gap-2">
          <Building2 class="w-6 h-6 text-amber-600" />
          Kelola Fasilitas Kesehatan (Multi-Tenant)
        </h1>
        <p class="text-xs text-slate-500 mt-0.5">
          Manajemen data seluruh Rumah Sakit, Klinik Cabang, dan isolasi data alkes multi-faskes.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button 
          @click="openAddModal"
          class="flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-xl shadow-sm transition-all active:scale-95 cursor-pointer"
        >
          <Plus class="w-4 h-4" />
          <span>Daftarkan Faskes Baru</span>
        </button>
      </div>
    </div>

    <!-- Overview Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Card 1: Total Faskes -->
      <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
        <div>
          <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Faskes Terdaftar</div>
          <div class="text-2xl font-black text-slate-800 mt-1">{{ overviewData.total_tenants || tenants.length }}</div>
          <div class="text-[11px] text-emerald-600 font-semibold mt-0.5 flex items-center gap-1">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            {{ activeTenantsCount }} Faskes Aktif Operasional
          </div>
        </div>
        <div class="w-11 h-11 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
          <Building2 class="w-5 h-5" />
        </div>
      </div>

      <!-- Card 2: Total Alkes Seluruh Holding -->
      <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
        <div>
          <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Alkes Holding</div>
          <div class="text-2xl font-black text-emerald-600 mt-1">{{ overviewData.total_equipment_all || totalEquipmentSum }}</div>
          <div class="text-[11px] text-slate-500 mt-0.5">Seluruh unit elektromedis</div>
        </div>
        <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
          <Stethoscope class="w-5 h-5" />
        </div>
      </div>

      <!-- Card 3: Total Tiket Servis Aktif -->
      <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
        <div>
          <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Tiket Servis Aktif</div>
          <div class="text-2xl font-black text-rose-600 mt-1">{{ overviewData.total_active_tickets || 0 }}</div>
          <div class="text-[11px] text-slate-500 mt-0.5">Penanganan perbaikan</div>
        </div>
        <div class="w-11 h-11 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center">
          <Wrench class="w-5 h-5" />
        </div>
      </div>

      <!-- Card 4: Lingkup Aktif Saat Ini -->
      <div class="bg-gradient-to-br from-amber-500 to-amber-600 text-white p-4 rounded-2xl shadow-xs flex items-center justify-between">
        <div class="min-w-0">
          <div class="text-[10px] font-bold text-amber-100 uppercase tracking-wider">Lingkup Aktif Saat Ini</div>
          <div class="text-base font-black truncate mt-1" :title="authStore.activeTenantName || 'Semua Faskes'">
            {{ authStore.activeTenantName || 'Semua Faskes (Holding)' }}
          </div>
          <button 
            v-if="authStore.activeTenantId" 
            @click="handleSwitchTenant(null)"
            class="text-[10px] font-bold underline text-amber-100 hover:text-white mt-0.5 cursor-pointer"
          >
            &larr; Reset ke Holding View
          </button>
          <div v-else class="text-[10px] text-amber-100 mt-0.5">Melihat agregat semua RS</div>
        </div>
        <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center shrink-0">
          <Globe class="w-5 h-5 text-white" />
        </div>
      </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div class="flex items-center gap-2 flex-1 max-w-md">
        <div class="relative w-full">
          <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
          <input 
            v-model="searchQuery"
            type="text" 
            placeholder="Cari kode, nama rumah sakit, atau kota..."
            class="w-full pl-9 pr-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500"
          />
        </div>
      </div>

      <div class="flex items-center gap-3">
        <select 
          v-model="statusFilter"
          class="px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white"
        >
          <option value="all">Semua Status</option>
          <option value="active">Hanya Aktif</option>
          <option value="inactive">Non-Aktif</option>
        </select>

        <div class="text-xs text-slate-500 hidden sm:block">
          Total: <strong class="text-slate-800">{{ filteredTenants.length }}</strong> Faskes
        </div>
      </div>
    </div>

    <!-- Tenants Grid / List Cards -->
    <div v-if="loading" class="py-16 text-center text-slate-400 text-xs bg-white rounded-2xl border border-slate-200">
      <div class="w-6 h-6 border-2 border-amber-500 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
      Memuat data seluruh fasilitas kesehatan...
    </div>

    <div v-else-if="filteredTenants.length === 0" class="py-16 text-center text-slate-400 text-xs bg-white rounded-2xl border border-slate-200">
      <Building2 class="w-10 h-10 text-slate-300 mx-auto mb-2" />
      <div>Tidak ada data fasilitas kesehatan yang sesuai kriteria pencarian.</div>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div 
        v-for="tenant in filteredTenants" 
        :key="tenant.id"
        class="bg-white rounded-2xl border transition-all hover:shadow-md flex flex-col justify-between overflow-hidden"
        :class="[
          String(authStore.activeTenantId) === String(tenant.id)
            ? 'border-amber-400 ring-2 ring-amber-400/30' 
            : 'border-slate-200/90'
        ]"
      >
        <!-- Top Header Card -->
        <div class="p-5 border-b border-slate-100">
          <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center font-bold text-amber-800 text-sm overflow-hidden shrink-0">
                <img 
                  v-if="tenant.logo_path" 
                  :src="tenant.logo_path" 
                  :alt="tenant.name" 
                  class="w-full h-full object-contain"
                />
                <Building2 v-else class="w-6 h-6 text-amber-600" />
              </div>

              <div>
                <div class="flex items-center gap-2">
                  <span class="px-2 py-0.5 font-mono text-[11px] font-extrabold bg-slate-100 text-slate-700 rounded border border-slate-200">
                    {{ tenant.code }}
                  </span>
                  <span 
                    :class="[
                      'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase',
                      tenant.is_active == 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'
                    ]"
                  >
                    {{ tenant.is_active == 1 ? 'Aktif' : 'Non-Aktif' }}
                  </span>
                </div>
                <h3 class="font-extrabold text-slate-800 text-sm mt-1 leading-snug">
                  {{ tenant.name }}
                </h3>
                <div class="text-[11px] text-slate-500 font-medium">
                  {{ tenant.hospital_subtitle || 'Fasilitas Kesehatan Terintegrasi' }}
                </div>
              </div>
            </div>
          </div>

          <!-- Location & Contact -->
          <div class="mt-4 space-y-1.5 text-xs text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-100">
            <div class="flex items-center gap-2">
              <MapPin class="w-3.5 h-3.5 text-slate-400 shrink-0" />
              <span class="truncate">{{ tenant.city ? tenant.city + ' - ' : '' }}{{ tenant.address || 'Alamat belum diatur' }}</span>
            </div>
            <div class="flex items-center gap-2">
              <Phone class="w-3.5 h-3.5 text-slate-400 shrink-0" />
              <span>{{ tenant.phone || 'Nomor telepon belum diatur' }}</span>
            </div>
          </div>
        </div>

        <!-- Middle: Stats Pill -->
        <div class="px-5 py-3 bg-slate-50/50 flex items-center justify-around text-center text-xs border-b border-slate-100">
          <div>
            <div class="font-black text-slate-800 text-sm">{{ tenant.total_equipment || 0 }}</div>
            <div class="text-[10px] text-slate-400 font-medium">Alat Medis</div>
          </div>
          <div class="w-px h-6 bg-slate-200"></div>
          <div>
            <div class="font-black text-slate-800 text-sm">{{ tenant.total_rooms || 0 }}</div>
            <div class="text-[10px] text-slate-400 font-medium">Ruangan</div>
          </div>
          <div class="w-px h-6 bg-slate-200"></div>
          <div>
            <div class="font-black text-slate-800 text-sm">{{ tenant.total_users || 0 }}</div>
            <div class="text-[10px] text-slate-400 font-medium">Pengguna</div>
          </div>
        </div>

        <!-- Card Actions -->
        <div class="p-4 bg-white flex items-center justify-between gap-2">
          <!-- Switch Tenant Button -->
          <button 
            v-if="String(authStore.activeTenantId) === String(tenant.id)"
            disabled
            class="flex-1 py-1.5 px-3 bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold rounded-xl flex items-center justify-center gap-1.5 cursor-default"
          >
            <Check class="w-4 h-4 text-emerald-600" />
            <span>Sedang Aktif</span>
          </button>
          <button 
            v-else
            @click="handleSwitchTenant(tenant)"
            class="flex-1 py-1.5 px-3 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 text-xs font-bold rounded-xl flex items-center justify-center gap-1.5 transition-colors cursor-pointer"
          >
            <ArrowRightLeft class="w-3.5 h-3.5 text-amber-700" />
            <span>Beralih ke Faskes Ini</span>
          </button>

          <!-- Edit Button -->
          <button 
            @click="openEditModal(tenant)"
            class="p-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-colors cursor-pointer"
            title="Edit Profil Faskes"
          >
            <Edit2 class="w-4 h-4" />
          </button>

          <!-- Toggle Active Status Button -->
          <button 
            v-if="tenant.id != 1"
            @click="toggleTenantStatus(tenant)"
            :class="[
              'p-2 rounded-xl transition-colors cursor-pointer',
              tenant.is_active == 1 
                ? 'text-rose-500 hover:bg-rose-50' 
                : 'text-emerald-600 hover:bg-emerald-50'
            ]"
            :title="tenant.is_active == 1 ? 'Non-aktifkan Faskes' : 'Aktifkan Faskes'"
          >
            <Power class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Form Tambah Faskes Baru -->
    <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 overflow-y-auto">
      <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 max-w-lg w-full overflow-hidden animate-in fade-in zoom-in-95 duration-200 my-8">
        <div class="px-6 py-4 bg-amber-600 text-white flex items-center justify-between">
          <div class="flex items-center gap-2">
            <Building2 class="w-5 h-5" />
            <h3 class="font-bold text-sm">Daftarkan Fasilitas Kesehatan Baru</h3>
          </div>
          <button @click="showAddModal = false" class="text-amber-100 hover:text-white cursor-pointer">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submitAddTenant" class="p-6 space-y-4">
          <!-- Section 1: Identitas Faskes -->
          <div class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 pb-1">
            1. Identitas Rumah Sakit / Klinik
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Kode Faskes (Singkatan) *</label>
              <input 
                v-model="newForm.code"
                type="text" 
                placeholder="misal: RSIA, KLINIK-B"
                required
                maxlength="20"
                class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500 font-mono uppercase font-bold"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Kota / Kabupaten *</label>
              <input 
                v-model="newForm.city"
                type="text" 
                placeholder="misal: Malang"
                required
                class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap Fasilitas Kesehatan *</label>
            <input 
              v-model="newForm.name"
              type="text" 
              placeholder="misal: RS Islam Gondanglegi Cabang 2"
              required
              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500 font-medium"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Subtitle / Tagline Instansi</label>
            <input 
              v-model="newForm.hospital_subtitle"
              type="text" 
              placeholder="misal: Melayani Sepenuh Hati dan Profesional"
              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500"
            />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Nomor Telepon</label>
              <input 
                v-model="newForm.phone"
                type="text" 
                placeholder="misal: 0341-879xxx"
                class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Lengkap</label>
              <input 
                v-model="newForm.address"
                type="text" 
                placeholder="misal: Jl. Raya Hayam Wuruk No. 20"
                class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500"
              />
            </div>
          </div>

          <!-- Section 2: Akun Admin Utama Faskes -->
          <div class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 pb-1 pt-3">
            2. Akun Administrator Faskes Tersebut
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Username Admin *</label>
              <input 
                v-model="newForm.admin_username"
                type="text" 
                placeholder="misal: adminrsia"
                required
                class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500 font-mono"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap Admin *</label>
              <input 
                v-model="newForm.admin_full_name"
                type="text" 
                placeholder="misal: Administrator RSIA"
                required
                class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Password Default *</label>
            <input 
              v-model="newForm.admin_password"
              type="password" 
              placeholder="Minimal 6 karakter"
              required
              minlength="6"
              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500"
            />
          </div>

          <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
            <button 
              type="button" 
              @click="showAddModal = false"
              class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors cursor-pointer"
            >
              Batal
            </button>
            <button 
              type="submit" 
              :disabled="submitting"
              class="px-5 py-2 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-xl shadow-xs transition-all active:scale-95 disabled:opacity-50 cursor-pointer flex items-center gap-1.5"
            >
              <span v-if="submitting">Mendaftarkan...</span>
              <span v-else>Daftarkan Faskes</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Form Edit Faskes -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 overflow-y-auto">
      <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 max-w-md w-full overflow-hidden animate-in fade-in zoom-in-95 duration-200 my-8">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
          <div class="flex items-center gap-2">
            <Edit2 class="w-5 h-5 text-amber-400" />
            <h3 class="font-bold text-sm">Edit Profil Faskes</h3>
          </div>
          <button @click="showEditModal = false" class="text-slate-400 hover:text-white cursor-pointer">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submitEditTenant" class="p-6 space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Kode Faskes (Permanen)</label>
            <input 
              :value="editForm.code"
              disabled
              class="w-full px-3 py-2 text-xs border border-slate-200 bg-slate-100 text-slate-500 rounded-xl font-mono font-bold"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Nama Faskes *</label>
            <input 
              v-model="editForm.name"
              type="text" 
              required
              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500 font-medium"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Subtitle / Tagline</label>
            <input 
              v-model="editForm.hospital_subtitle"
              type="text" 
              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500"
            />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Kota</label>
              <input 
                v-model="editForm.city"
                type="text" 
                class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Telepon</label>
              <input 
                v-model="editForm.phone"
                type="text" 
                class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Alamat</label>
            <textarea 
              v-model="editForm.address"
              rows="2"
              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500"
            ></textarea>
          </div>

          <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
            <button 
              type="button" 
              @click="showEditModal = false"
              class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors cursor-pointer"
            >
              Batal
            </button>
            <button 
              type="submit" 
              :disabled="submitting"
              class="px-5 py-2 text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 rounded-xl shadow-xs transition-all active:scale-95 disabled:opacity-50 cursor-pointer"
            >
              <span v-if="submitting">Menyimpan...</span>
              <span v-else>Simpan Perubahan</span>
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
  Building2, Plus, Stethoscope, Wrench, Globe, Search, 
  MapPin, Phone, ArrowRightLeft, Edit2, Power, X, Check 
} from 'lucide-vue-next';
import { useAuthStore } from '../../stores/authStore';
import axiosClient from '../../api/axiosClient';

const authStore = useAuthStore();

const loading = ref(true);
const submitting = ref(false);
const tenants = ref([]);
const overviewData = ref({});

const searchQuery = ref('');
const statusFilter = ref('all');

const showAddModal = ref(false);
const showEditModal = ref(false);

const newForm = ref({
  code: '',
  name: '',
  hospital_subtitle: '',
  city: '',
  address: '',
  phone: '',
  admin_username: '',
  admin_full_name: '',
  admin_password: '',
});

const editForm = ref({
  id: null,
  code: '',
  name: '',
  hospital_subtitle: '',
  city: '',
  address: '',
  phone: '',
});

const activeTenantsCount = computed(() => {
  return tenants.value.filter(t => t.is_active == 1).length;
});

const totalEquipmentSum = computed(() => {
  return tenants.value.reduce((sum, t) => sum + (parseInt(t.total_equipment) || 0), 0);
});

const filteredTenants = computed(() => {
  return tenants.value.filter(t => {
    const q = searchQuery.value.toLowerCase();
    const matchesSearch = 
      t.name.toLowerCase().includes(q) || 
      t.code.toLowerCase().includes(q) || 
      (t.city && t.city.toLowerCase().includes(q));

    if (!matchesSearch) return false;

    if (statusFilter.value === 'active') return t.is_active == 1;
    if (statusFilter.value === 'inactive') return t.is_active == 0;
    return true;
  });
});

const fetchTenants = async () => {
  loading.value = true;
  try {
    const [resTenants, resOverview] = await Promise.all([
      axiosClient.get('/tenants'),
      axiosClient.get('/tenants/overview')
    ]);

    if (resTenants.success && Array.isArray(resTenants.data)) {
      tenants.value = resTenants.data;
    }
    if (resOverview.success && resOverview.data) {
      overviewData.value = resOverview.data;
    }
  } catch (err) {
    console.error('Gagal mengambil data tenants:', err);
  } finally {
    loading.value = false;
  }
};

const handleSwitchTenant = (tenant) => {
  authStore.switchTenant(tenant);
  window.location.reload();
};

const openAddModal = () => {
  newForm.value = {
    code: '',
    name: '',
    hospital_subtitle: '',
    city: '',
    address: '',
    phone: '',
    admin_username: '',
    admin_full_name: '',
    admin_password: '',
  };
  showAddModal.value = true;
};

const submitAddTenant = async () => {
  submitting.value = true;
  try {
    const res = await axiosClient.post('/tenants', newForm.value);
    if (res.success) {
      showAddModal.value = false;
      await fetchTenants();
      alert('Fasilitas kesehatan dan akun admin berhasil didaftarkan!');
    } else {
      alert(res.message || 'Gagal mendaftarkan faskes.');
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Terjadi kesalahan sistem.');
  } finally {
    submitting.value = false;
  }
};

const openEditModal = (t) => {
  editForm.value = {
    id: t.id,
    code: t.code,
    name: t.name,
    hospital_subtitle: t.hospital_subtitle || '',
    city: t.city || '',
    address: t.address || '',
    phone: t.phone || '',
  };
  showEditModal.value = true;
};

const submitEditTenant = async () => {
  submitting.value = true;
  try {
    const res = await axiosClient.post(`/tenants/update/${editForm.value.id}`, editForm.value);
    if (res.success) {
      showEditModal.value = false;
      await fetchTenants();
      alert('Profil faskes berhasil diperbarui.');
    } else {
      alert(res.message || 'Gagal memperbarui faskes.');
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Terjadi kesalahan sistem.');
  } finally {
    submitting.value = false;
  }
};

const toggleTenantStatus = async (tenant) => {
  const actionText = tenant.is_active == 1 ? 'menonaktifkan' : 'mengaktifkan kembali';
  if (!confirm(`Apakah Anda yakin ingin ${actionText} faskes "${tenant.name}"?`)) return;

  try {
    const res = await axiosClient.post(`/tenants/toggle-status/${tenant.id}`);
    if (res.success) {
      await fetchTenants();
    } else {
      alert(res.message || 'Gagal mengubah status faskes.');
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Terjadi kesalahan sistem.');
  }
};

onMounted(() => {
  fetchTenants();
});
</script>
