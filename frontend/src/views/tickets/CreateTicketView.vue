<template>
  <div class="w-full space-y-6">
    <!-- Header Fullscreen -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
      <div class="flex items-center gap-3">
        <router-link 
          to="/tickets" 
          class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-colors shrink-0"
          title="Kembali ke Daftar Tiket"
        >
          <ArrowLeft class="w-5 h-5" />
        </router-link>
        <div>
          <h1 class="text-xl font-black text-slate-800 tracking-tight flex items-center gap-2">
            <Wrench class="w-5 h-5 text-emerald-600" />
            Lapor Kerusakan Alat Medis Baru
          </h1>
          <p class="text-xs text-slate-500 mt-0.5">
            Pelaporan gangguan alkes untuk respon cepat elektromedis / teknisi IPSRS rumah sakit.
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <span class="px-3 py-1.5 bg-emerald-50 text-emerald-700 text-xs font-bold rounded-xl border border-emerald-200 flex items-center gap-1.5">
          <Clock class="w-3.5 h-3.5" />
          <span>SLA SPM: Respon Segera</span>
        </span>
      </div>
    </div>

    <!-- Main Fullscreen Form Layout -->
    <form @submit.prevent="submitTicket">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left & Center: Form Fields (2 Cols) -->
        <div class="lg:col-span-2 space-y-6">
          
          <!-- Alert Error if any -->
          <div v-if="errorMsg" class="p-4 bg-rose-50 text-rose-700 text-xs rounded-2xl border border-rose-200 font-medium flex items-center gap-2">
            <AlertTriangle class="w-4 h-4 shrink-0 text-rose-600" />
            <span>{{ errorMsg }}</span>
          </div>

          <!-- Card 1: Pemilihan Alat Medis -->
          <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
              <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-black">1</span>
                Pilih Alat Medis yang Bermasalah
              </h2>
              <span class="text-[11px] text-slate-400">* Wajib diisi</span>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Cari & Pilih Alat Medis</label>
              <SearchableSelect 
                v-model="form.equipment_id"
                :options="equipmentList"
                value-key="id"
                :format-label="(eq) => `${eq.asset_code} - ${eq.name}`"
                :format-sublabel="(eq) => `${eq.room_name} • SN: ${eq.serial_number || '-'} • Merk: ${eq.brand || '-'}`"
                placeholder="Ketik nama alat medis, kode aset, atau ruangan..."
                search-placeholder="Ketik nama alat, kode aset, nomor seri, atau ruangan..."
              />
            </div>

            <!-- Detail Alkes Terpilih Preview -->
            <div v-if="selectedEquipment" class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2 animate-in fade-in duration-200">
              <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                  <div class="text-xs font-bold text-slate-800">{{ selectedEquipment.name }}</div>
                  <div class="text-[11px] text-slate-500 font-mono">Kode Aset: {{ selectedEquipment.asset_code }}</div>
                </div>
                <div class="text-[11px] font-semibold text-emerald-700 bg-emerald-100/70 px-2.5 py-1 rounded-lg self-start sm:self-auto">
                  {{ selectedEquipment.room_name }}
                </div>
              </div>
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 pt-2 border-t border-slate-200 text-[11px] text-slate-600">
                <div>Merk: <strong class="text-slate-700">{{ selectedEquipment.brand || '-' }}</strong></div>
                <div>Model: <strong class="text-slate-700">{{ selectedEquipment.model_type || '-' }}</strong></div>
                <div>Serial Number: <strong class="font-mono text-slate-700">{{ selectedEquipment.serial_number || '-' }}</strong></div>
              </div>
            </div>
          </div>

          <!-- Card 2: Tingkat Prioritas Penanganan -->
          <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
              <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-black">2</span>
                Tingkat Prioritas / Urgensi Penanganan
              </h2>
              <span class="text-[11px] text-slate-400">* Pilih salah satu</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              <!-- Emergency -->
              <label 
                :class="[
                  'border-2 rounded-2xl p-4 text-center cursor-pointer transition-all flex flex-col items-center justify-center',
                  form.priority === 'emergency' 
                    ? 'border-rose-500 bg-rose-50/80 text-rose-800 ring-2 ring-rose-300 shadow-xs font-bold' 
                    : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'
                ]"
              >
                <input type="radio" v-model="form.priority" value="emergency" class="sr-only" />
                <div class="text-xs font-black tracking-wider text-rose-600 uppercase">EMERGENCY</div>
                <div class="text-[10px] text-slate-500 mt-1 font-medium leading-tight">Life Support / ICU / IGD</div>
                <span class="mt-2 text-[9px] px-2 py-0.5 rounded-full font-bold bg-rose-100 text-rose-700">SPM &le; 15 Mnt</span>
              </label>

              <!-- High -->
              <label 
                :class="[
                  'border-2 rounded-2xl p-4 text-center cursor-pointer transition-all flex flex-col items-center justify-center',
                  form.priority === 'high' 
                    ? 'border-orange-500 bg-orange-50/80 text-orange-800 ring-2 ring-orange-300 shadow-xs font-bold' 
                    : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'
                ]"
              >
                <input type="radio" v-model="form.priority" value="high" class="sr-only" />
                <div class="text-xs font-black tracking-wider text-orange-600 uppercase">HIGH</div>
                <div class="text-[10px] text-slate-500 mt-1 font-medium leading-tight">Tindakan Pasien Kritis</div>
                <span class="mt-2 text-[9px] px-2 py-0.5 rounded-full font-bold bg-orange-100 text-orange-700">SPM &le; 30 Mnt</span>
              </label>

              <!-- Medium -->
              <label 
                :class="[
                  'border-2 rounded-2xl p-4 text-center cursor-pointer transition-all flex flex-col items-center justify-center',
                  form.priority === 'medium' 
                    ? 'border-amber-500 bg-amber-50/80 text-amber-800 ring-2 ring-amber-300 shadow-xs font-bold' 
                    : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'
                ]"
              >
                <input type="radio" v-model="form.priority" value="medium" class="sr-only" />
                <div class="text-xs font-black tracking-wider text-amber-600 uppercase">MEDIUM</div>
                <div class="text-[10px] text-slate-500 mt-1 font-medium leading-tight">Pemeriksaan Rutin</div>
                <span class="mt-2 text-[9px] px-2 py-0.5 rounded-full font-bold bg-amber-100 text-amber-800">SPM &le; 60 Mnt</span>
              </label>

              <!-- Low -->
              <label 
                :class="[
                  'border-2 rounded-2xl p-4 text-center cursor-pointer transition-all flex flex-col items-center justify-center',
                  form.priority === 'low' 
                    ? 'border-slate-600 bg-slate-100 text-slate-800 ring-2 ring-slate-300 shadow-xs font-bold' 
                    : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'
                ]"
              >
                <input type="radio" v-model="form.priority" value="low" class="sr-only" />
                <div class="text-xs font-black tracking-wider text-slate-700 uppercase">LOW</div>
                <div class="text-[10px] text-slate-500 mt-1 font-medium leading-tight">Pendukung Umum</div>
                <span class="mt-2 text-[9px] px-2 py-0.5 rounded-full font-bold bg-slate-200 text-slate-700">Hari yang Sama</span>
              </label>
            </div>
          </div>

          <!-- Card 3: Rincian Kendala Kerusakan -->
          <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
              <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-black">3</span>
                Deskripsi Kendala & Gejala Kerusakan
              </h2>
              <span class="text-[11px] text-slate-400">* Wajib diisi</span>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Penjelasan Kendala / Error Alat Medis *</label>
              <textarea 
                v-model="form.issue_description" 
                required 
                rows="5" 
                placeholder="Deskripsikan gejala kerusakan secara spesifik (Contoh: Layar sentuh tidak merespon saat ditekan, muncul kode error E-04 saat booting, alarm baterai bunyi terus walau terhubung ke listrik AC)..."
                class="w-full px-4 py-3 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 leading-relaxed"
              ></textarea>
            </div>
          </div>
        </div>

        <!-- Right: Photo Attachment & Actions (1 Col) -->
        <div class="space-y-6">
          
          <!-- Card 4: Foto Bukti Kendala -->
          <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2 border-b border-slate-100 pb-3">
              <Upload class="w-4 h-4 text-emerald-600" />
              Foto Kendala Kerusakan
            </h3>

            <div>
              <p class="text-[11px] text-slate-500 mb-3 leading-relaxed">
                Lampirkan foto fisik alkes atau foto tampilan layar kode error untuk membantu teknisi mempersiapkan suku cadang.
              </p>

              <!-- Upload Drag / Click Box -->
              <label class="border-2 border-dashed border-slate-300 hover:border-emerald-500 rounded-2xl p-5 flex flex-col items-center justify-center text-center cursor-pointer bg-slate-50/50 hover:bg-emerald-50/30 transition-all">
                <Upload class="w-8 h-8 text-slate-400 mb-2" />
                <span class="text-xs font-bold text-slate-700">Pilih / Ambil Foto</span>
                <span class="text-[10px] text-slate-400 mt-0.5">Maksimal 5MB (JPG, PNG, WEBP, GIF)</span>
                <input 
                  type="file" 
                  accept="image/*" 
                  @change="handlePhotoChange" 
                  class="hidden" 
                />
              </label>

              <!-- Preview Foto -->
              <div v-if="photoPreview" class="mt-4 relative group rounded-2xl overflow-hidden border border-slate-200 shadow-sm">
                <img :src="photoPreview" class="w-full h-44 object-cover" />
                <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                  <button 
                    type="button" 
                    @click="clearPhoto" 
                    class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold flex items-center gap-1 shadow cursor-pointer"
                  >
                    <X class="w-3.5 h-3.5" />
                    Hapus Foto
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Card 5: Panduan & Submit Actions -->
          <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2 border-b border-slate-100 pb-3">
              <CheckCircle2 class="w-4 h-4 text-emerald-600" />
              Verifikasi & Pengiriman
            </h3>

            <div class="p-3.5 bg-amber-50 rounded-xl border border-amber-200/70 text-[11px] text-amber-900 space-y-1">
              <div class="font-bold flex items-center gap-1">
                <AlertTriangle class="w-3.5 h-3.5 text-amber-600" />
                Ketentuan Pelaporan:
              </div>
              <p class="leading-relaxed text-amber-800">
                Setelah laporan dikirim, nomor tiket resmi (WO) otomatis digenerate dan tim teknisi IPSRS akan menerima notifikasi pengerjaan.
              </p>
            </div>

            <div class="space-y-2 pt-2">
              <button 
                type="submit" 
                :disabled="submitting"
                class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs rounded-xl shadow-md shadow-emerald-950/20 disabled:opacity-50 transition-all flex items-center justify-center gap-2 cursor-pointer active:scale-[0.98]"
              >
                <span v-if="submitting" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                <span>{{ submitting ? 'Mengirim Laporan...' : 'Kirim Laporan Kerusakan' }}</span>
              </button>

              <router-link 
                to="/tickets" 
                class="w-full py-2.5 text-center text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors block cursor-pointer"
              >
                Batal & Kembali
              </router-link>
            </div>
          </div>
        </div>

      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ArrowLeft, Wrench, Clock, AlertTriangle, Upload, X, CheckCircle2 } from 'lucide-vue-next';
import axiosClient from '../../api/axiosClient';
import SearchableSelect from '../../components/SearchableSelect.vue';
import { validateFile } from '../../utils/fileValidation';

const route = useRoute();
const router = useRouter();

const equipmentList = ref([]);
const submitting = ref(false);
const errorMsg = ref('');
const photoFile = ref(null);
const photoPreview = ref(null);

const form = ref({
  equipment_id: '',
  priority: 'medium',
  issue_description: ''
});

const selectedEquipment = computed(() => {
  if (!form.value.equipment_id) return null;
  return equipmentList.value.find(e => e.id == form.value.equipment_id);
});

const handlePhotoChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    const validation = validateFile(file, 'image_gif');
    if (!validation.valid) {
      errorMsg.value = validation.error;
      e.target.value = '';
      return;
    }
    errorMsg.value = '';
    photoFile.value = file;
    photoPreview.value = URL.createObjectURL(file);
  }
};

const clearPhoto = () => {
  photoFile.value = null;
  photoPreview.value = null;
};

const fetchEquipmentList = async () => {
  try {
    const res = await axiosClient.get('/equipment');
    if (res.success) {
      equipmentList.value = res.data;
      if (route.query.equipment_id) {
        form.value.equipment_id = route.query.equipment_id;
      }
    }
  } catch (e) {
    console.error('Error loading equipment:', e);
  }
};

const submitTicket = async () => {
  if (!form.value.equipment_id) {
    errorMsg.value = 'Silakan pilih alat medis yang bermasalah terlebih dahulu.';
    return;
  }
  if (!form.value.issue_description.trim()) {
    errorMsg.value = 'Silakan isi deskripsi kendala kerusakan alat.';
    return;
  }
  // Validasi ulang file sebelum kirim
  if (photoFile.value) {
    const validation = validateFile(photoFile.value, 'image_gif');
    if (!validation.valid) {
      errorMsg.value = validation.error;
      return;
    }
  }

  submitting.value = true;
  errorMsg.value = '';
  try {
    const formData = new FormData();
    formData.append('equipment_id', form.value.equipment_id);
    formData.append('priority', form.value.priority);
    formData.append('issue_description', form.value.issue_description.trim());
    if (photoFile.value) {
      formData.append('issue_photo', photoFile.value);
    }

    const res = await axiosClient.post('/work-orders', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    if (res.success && res.data && res.data.id) {
      router.push(`/tickets/${res.data.id}`);
    } else if (res.success) {
      router.push('/tickets');
    } else {
      errorMsg.value = res.message || 'Gagal mengirim laporan kerusakan.';
    }
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Terjadi kesalahan pada server saat mengirim laporan.';
  } finally {
    submitting.value = false;
  }
};

onMounted(() => {
  fetchEquipmentList();
});
</script>
