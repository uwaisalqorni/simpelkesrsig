<template>
  <div class="space-y-6 animate-in fade-in duration-200">
    <!-- Header Page -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
      <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-inner">
          <Building2 class="w-6 h-6" />
        </div>
        <div>
          <h1 class="text-xl font-bold text-slate-900 tracking-tight">Konfigurasi & Identitas Rumah Sakit</h1>
          <p class="text-xs sm:text-sm text-slate-500">Kelola nama institusi, unit kerja, alamat kop surat, dan logo resmi aplikasi</p>
        </div>
      </div>
      
      <div>
        <button 
          @click="saveSettings" 
          :disabled="settingStore.saving"
          class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-sm font-semibold rounded-xl shadow-sm transition disabled:opacity-50"
        >
          <Save v-if="!settingStore.saving" class="w-4 h-4" />
          <Loader2 v-else class="w-4 h-4 animate-spin" />
          <span>{{ settingStore.saving ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
        </button>
      </div>
    </div>

    <!-- Alert Notifikasi -->
    <div v-if="alertMessage" :class="[
      'p-4 rounded-xl text-sm font-medium flex items-center justify-between shadow-sm animate-in fade-in',
      alertSuccess ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200'
    ]">
      <div class="flex items-center gap-3">
        <CheckCircle2 v-if="alertSuccess" class="w-5 h-5 text-emerald-600 flex-shrink-0" />
        <AlertCircle v-else class="w-5 h-5 text-rose-600 flex-shrink-0" />
        <span>{{ alertMessage }}</span>
      </div>
      <button @click="alertMessage = ''" class="text-slate-400 hover:text-slate-600">
        <X class="w-4 h-4" />
      </button>
    </div>

    <!-- Main Grid: Form + Live Preview -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      
      <!-- Kolom Kiri: Form Konfigurasi (7 Kolom) -->
      <div class="lg:col-span-7 space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-5">
          <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
            <Sliders class="w-4 h-4 text-emerald-600" />
            Informasi Institusi Rumah Sakit
          </h2>

          <div class="space-y-4">
            <!-- Nama Rumah Sakit -->
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                Nama Rumah Sakit <span class="text-rose-500">*</span>
              </label>
              <input 
                v-model="form.hospital_name" 
                type="text" 
                placeholder="Contoh: RS Islam Gondanglegi"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition"
              />
              <p class="text-[11px] text-slate-400 mt-1">Nama ini akan tampil di seluruh judul aplikasi, navbar, header dokumen, dan tiket.</p>
            </div>

            <!-- Subtitle / Unit IPSRS -->
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                Unit Kerja / Sub-Title <span class="text-rose-500">*</span>
              </label>
              <input 
                v-model="form.hospital_subtitle" 
                type="text" 
                placeholder="Contoh: Instalasi Pemeliharaan Sarana Rumah Sakit (IPSRS)"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition"
              />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <!-- Kota Domisili -->
              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                  Kota / Wilayah <span class="text-rose-500">*</span>
                </label>
                <input 
                  v-model="form.hospital_city" 
                  type="text" 
                  placeholder="Contoh: Gondanglegi / Malang"
                  class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition"
                />
                <p class="text-[11px] text-slate-400 mt-1">Digunakan pada titimangsa tanda tangan laporan.</p>
              </div>

              <!-- Kontak Telepon -->
              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                  No. Telepon / Hotline
                </label>
                <input 
                  v-model="form.hospital_phone" 
                  type="text" 
                  placeholder="Contoh: (0341) 879222"
                  class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition"
                />
              </div>
            </div>

            <!-- Alamat Lengkap -->
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                Alamat Lengkap Rumah Sakit
              </label>
              <textarea 
                v-model="form.hospital_address" 
                rows="2"
                placeholder="Contoh: Jl. Hayam Wuruk No. 123, Gondanglegi, Kab. Malang, Jawa Timur"
                class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition resize-none"
              ></textarea>
            </div>
          </div>
        </div>

        <!-- Card Upload Logo -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-4">
          <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
            <Image class="w-4 h-4 text-emerald-600" />
            Logo Resmi Rumah Sakit
          </h2>

          <div class="flex flex-col sm:flex-row items-center gap-6 p-4 rounded-xl bg-slate-50 border border-slate-100">
            <!-- Logo Preview Container -->
            <div class="w-24 h-24 rounded-2xl bg-white border border-slate-200 shadow-sm flex items-center justify-center p-2 flex-shrink-0 overflow-hidden">
              <img 
                :src="logoPreviewUrl || settingStore.hospitalLogoUrl" 
                alt="Logo RS" 
                class="max-w-full max-h-full object-contain"
              />
            </div>

            <div class="space-y-2 text-center sm:text-left flex-1">
              <div class="text-xs font-semibold text-slate-700">Pilih File Logo Baru</div>
              <p class="text-[11px] text-slate-500">Format yang didukung: PNG, JPG, WEBP, atau SVG. Disarankan berlatar belakang transparan dengan resolusi minimal 200x200 px.</p>
              
              <div class="flex flex-wrap items-center gap-2 pt-1 justify-center sm:justify-start">
                <label class="cursor-pointer inline-flex items-center gap-2 px-3.5 py-1.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 text-xs font-semibold rounded-lg shadow-sm transition">
                  <Upload class="w-3.5 h-3.5" />
                  <span>Unggah File Logo</span>
                  <input 
                    type="file" 
                    accept="image/png,image/jpeg,image/webp,image/svg+xml" 
                    @change="onLogoSelected" 
                    class="hidden" 
                  />
                </label>

                <button 
                  v-if="logoFile" 
                  type="button" 
                  @click="resetLogoSelection"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 rounded-lg transition"
                >
                  <X class="w-3.5 h-3.5" />
                  <span>Batal</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Kolom Kanan: Live Preview Kop & Identitas (5 Kolom) -->
      <div class="lg:col-span-5 space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
              <Eye class="w-4 h-4 text-emerald-600" />
              Live Preview Kop Dokumen
            </h2>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700">Otomatis Realtime</span>
          </div>
          <p class="text-xs text-slate-500">Berikut simulasi kop surat berita acara & laporan hasil pemeliharaan yang dicetak:</p>

          <!-- Kop Surat Box Preview -->
          <div class="p-4 rounded-xl border border-slate-300 bg-white shadow-inner space-y-3 font-sans">
            <div class="flex items-center gap-3 pb-3 border-b-2 border-slate-800">
              <div class="w-12 h-12 flex-shrink-0 flex items-center justify-center">
                <img 
                  :src="logoPreviewUrl || settingStore.hospitalLogoUrl" 
                  alt="Logo Kop" 
                  class="max-w-full max-h-full object-contain"
                />
              </div>
              <div class="flex-1 min-w-0">
                <div class="text-xs sm:text-sm font-black text-slate-900 uppercase tracking-tight truncate">
                  {{ form.hospital_name || 'RS Islam Gondanglegi' }}
                </div>
                <div class="text-[10px] font-bold text-emerald-700 uppercase truncate">
                  {{ form.hospital_subtitle || 'Instalasi Pemeliharaan Sarana Rumah Sakit (IPSRS)' }}
                </div>
                <div class="text-[9px] text-slate-500 leading-tight truncate">
                  {{ form.hospital_address || 'Jl. Hayam Wuruk No. 123' }} &bull; Telp: {{ form.hospital_phone || '(0341) 879222' }}
                </div>
              </div>
            </div>

            <!-- Isi simulasi -->
            <div class="space-y-1.5 text-[10px] text-slate-600">
              <div class="text-center font-bold text-slate-800 text-[11px] underline">BERITA ACARA PEMELIHARAAN ALAT KESEHATAN</div>
              <div class="flex justify-between py-1 border-b border-slate-100">
                <span>Unit Kerja:</span>
                <span class="font-semibold text-slate-800">Instalasi Gawat Darurat (IGD)</span>
              </div>
              <div class="flex justify-between py-1 border-b border-slate-100">
                <span>Nama Alat Medis:</span>
                <span class="font-semibold text-slate-800">Biphasic Defibrillator Monitor</span>
              </div>
              <div class="flex justify-between py-1">
                <span>Status Tindakan:</span>
                <span class="font-bold text-emerald-700">Laik Pakai / Operasional</span>
              </div>
            </div>

            <!-- Tanda tangan simulasi -->
            <div class="pt-3 border-t border-slate-100 flex justify-end text-[9px] text-slate-600">
              <div class="text-right">
                <div>{{ form.hospital_city || 'Gondanglegi' }}, {{ new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}</div>
                <div class="font-bold mt-0.5">Kepala {{ form.hospital_subtitle || 'IPSRS' }}</div>
                <div class="h-8"></div>
                <div class="font-bold underline text-slate-900">Ahmad Elektromedik, A.Md.T</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Info Tambahan -->
        <div class="bg-gradient-to-br from-emerald-50 to-teal-50/50 rounded-2xl border border-emerald-100/80 p-5 space-y-2">
          <div class="flex items-center gap-2 text-emerald-800 font-bold text-xs uppercase tracking-wider">
            <Sparkles class="w-4 h-4 text-emerald-600" />
            Integrasi Otomatis
          </div>
          <p class="text-xs text-emerald-950/80 leading-relaxed">
            Perubahan nama rumah sakit dan unit kerja akan langsung diterapkan ke seluruh bagian sistem:
          </p>
          <ul class="text-[11px] text-emerald-900/80 space-y-1 list-disc list-inside">
            <li>Kop Cetak Tiket Perbaikan & Work Order</li>
            <li>Kop Cetak Laporan Eksekutif Bulanan</li>
            <li>Label QR Code Inventaris Alat Medis</li>
            <li>Footer Hak Cipta dan Header Navigasi Aplikasi</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { 
  Building2, Save, Loader2, CheckCircle2, AlertCircle, 
  X, Sliders, Image, Upload, Eye, Sparkles 
} from 'lucide-vue-next';
import { useSettingStore } from '../../stores/settingStore';
import { validateFile } from '../../utils/fileValidation';

const settingStore = useSettingStore();

const form = reactive({
  hospital_name: '',
  hospital_subtitle: '',
  hospital_address: '',
  hospital_phone: '',
  hospital_city: ''
});

const logoFile = ref(null);
const logoPreviewUrl = ref(null);
const alertMessage = ref('');
const alertSuccess = ref(true);

onMounted(async () => {
  await settingStore.fetchSettings();
  syncFormWithStore();
});

function syncFormWithStore() {
  const s = settingStore.settings || {};
  form.hospital_name = s.hospital_name || 'RS Islam Gondanglegi';
  form.hospital_subtitle = s.hospital_subtitle || 'Instalasi Pemeliharaan Sarana Rumah Sakit (IPSRS)';
  form.hospital_address = s.hospital_address || 'Jl. Hayam Wuruk No. 123, Gondanglegi, Malang';
  form.hospital_phone = s.hospital_phone || '(0341) 879222';
  form.hospital_city = s.hospital_city || 'Gondanglegi';
}

function onLogoSelected(e) {
  const file = e.target.files?.[0];
  if (!file) return;

  const validation = validateFile(file, 'logo');
  if (!validation.valid) {
    alertSuccess.value = false;
    alertMessage.value = validation.error;
    e.target.value = '';
    return;
  }

  alertMessage.value = '';
  logoFile.value = file;
  logoPreviewUrl.value = URL.createObjectURL(file);
}

function resetLogoSelection() {
  logoFile.value = null;
  if (logoPreviewUrl.value) {
    URL.revokeObjectURL(logoPreviewUrl.value);
    logoPreviewUrl.value = null;
  }
}

async function saveSettings() {
  alertMessage.value = '';

  const formData = new FormData();
  formData.append('hospital_name', form.hospital_name);
  formData.append('hospital_subtitle', form.hospital_subtitle);
  formData.append('hospital_address', form.hospital_address);
  formData.append('hospital_phone', form.hospital_phone);
  formData.append('hospital_city', form.hospital_city);

  if (logoFile.value) {
    formData.append('logo', logoFile.value);
  }

  const result = await settingStore.updateSettings(formData);
  if (result.success) {
    alertSuccess.value = true;
    alertMessage.value = 'Pengaturan Rumah Sakit berhasil diperbarui!';
    resetLogoSelection();
    syncFormWithStore();
  } else {
    alertSuccess.value = false;
    alertMessage.value = result.message || 'Gagal menyimpan pengaturan.';
  }
}
</script>
