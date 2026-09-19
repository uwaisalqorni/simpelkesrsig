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

            <!-- Pejabat Pengesahan (Kepala Instalasi IPSRS) -->
            <div class="pt-4 border-t border-slate-100">
              <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                <UserCheck class="w-3.5 h-3.5 text-emerald-600" />
                Pejabat Pengesahan (Kepala Instalasi IPSRS)
              </h3>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                    Nama Kepala Instalasi <span class="text-rose-500">*</span>
                  </label>
                  <input 
                    v-model="form.head_ipsrs_name" 
                    type="text" 
                    placeholder="Contoh: Ahmad Elektromedik, S.Tr.Kes"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition"
                  />
                  <p class="text-[11px] text-slate-400 mt-1">Dicantumkan pada kolom tanda tangan "Mengetahui" di seluruh laporan resmi.</p>
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                    NIP / NIK Kepala Instalasi
                  </label>
                  <input 
                    v-model="form.head_ipsrs_nip" 
                    type="text" 
                    placeholder="Contoh: 19850712 201001 1 002"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition"
                  />
                  <p class="text-[11px] text-slate-400 mt-1">Nomor Induk Pegawai / Karyawan penandatangan.</p>
                </div>
              </div>
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

        <!-- Card Integrasi Telegram Bot -->
        <div class="bg-white rounded-2xl border border-sky-200/80 shadow-sm p-6 space-y-5">
          <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
              <div class="w-7 h-7 rounded-lg bg-sky-500 text-white flex items-center justify-center shadow-xs">
                <Send class="w-4 h-4" />
              </div>
              <span>Integrasi Notifikasi Telegram Bot</span>
            </h2>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-sky-50 text-sky-700 border border-sky-200">
              Multi-Faskes
            </span>
          </div>

          <p class="text-xs text-slate-500 leading-relaxed">
            Kirimkan alert instan otomatis ke grup Telegram tim teknisi & kepala ruangan saat terjadi laporan kerusakan darurat, pemeliharaan selesai, atau sertifikat kalibrasi mendekati jatuh tempo.
          </p>

          <!-- Tutorial Singkat 3 Langkah -->
          <div class="p-3.5 rounded-xl bg-sky-50/60 border border-sky-100 text-xs text-slate-700 space-y-1.5">
            <div class="font-bold text-sky-900 flex items-center gap-1.5">
              <HelpCircle class="w-3.5 h-3.5 text-sky-600" />
              <span>Panduan Cepat Pengaturan Bot:</span>
            </div>
            <ol class="list-decimal list-inside space-y-1 text-[11px] text-slate-600 pl-1">
              <li>Buka Telegram, cari akun resmi <b>@BotFather</b>, ketik <code>/newbot</code>, ikuti petunjuknya dan salin <b>API Token</b>.</li>
              <li>Buat Grup Telegram teknisi faskes Anda, lalu masukkan bot tersebut ke dalam grup.</li>
              <li>Masukkan bot pembantu <b>@userinfobot</b> atau <b>@getidsbot</b> ke grup untuk melihat <b>Chat ID Grup</b> (biasanya berawalan tanda minus, contoh: <code>-1001234567890</code>).</li>
            </ol>
          </div>

          <div class="space-y-4">
            <!-- Telegram Bot Token -->
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                Telegram Bot API Token
              </label>
              <div class="relative">
                <input 
                  v-model="form.telegram_bot_token" 
                  :type="showToken ? 'text' : 'password'" 
                  placeholder="Contoh: 1234567890:ABCdefGhIJKlmNoPQRsTUVwxyZ"
                  class="w-full pl-4 pr-10 py-2.5 rounded-xl border border-slate-200 text-xs font-mono text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition"
                />
                <button 
                  type="button" 
                  @click="showToken = !showToken"
                  class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 cursor-pointer"
                  title="Tampilkan / Sembunyikan Token"
                >
                  <Eye v-if="!showToken" class="w-4 h-4" />
                  <EyeOff v-else class="w-4 h-4" />
                </button>
              </div>
            </div>

            <!-- Telegram Chat ID Grup -->
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                Telegram Chat ID (ID Grup Teknisi Faskes)
              </label>
              <input 
                v-model="form.telegram_chat_id" 
                type="text" 
                placeholder="Contoh: -1001928374820"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-mono text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition"
              />
              <p class="text-[11px] text-slate-400 mt-1">ID grup Telegram tim elektromedis yang akan menerima notifikasi.</p>
            </div>

            <!-- Notification Event Toggles -->
            <div class="pt-2 border-t border-slate-100">
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                Preferensi Jenis Notifikasi Aktif
              </label>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
                <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition">
                  <input type="checkbox" v-model="form.telegram_notif_emergency" :true-value="1" :false-value="0" class="rounded text-sky-600 focus:ring-sky-500" />
                  <span class="font-medium text-slate-700">🚨 Tiket Darurat (Emergency)</span>
                </label>

                <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition">
                  <input type="checkbox" v-model="form.telegram_notif_routine" :true-value="1" :false-value="0" class="rounded text-sky-600 focus:ring-sky-500" />
                  <span class="font-medium text-slate-700">🛠️ Laporan Kerusakan Rutin</span>
                </label>

                <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition">
                  <input type="checkbox" v-model="form.telegram_notif_validation" :true-value="1" :false-value="0" class="rounded text-sky-600 focus:ring-sky-500" />
                  <span class="font-medium text-slate-700">✍️ Permintaan Uji Fungsi Ruangan</span>
                </label>

                <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition">
                  <input type="checkbox" v-model="form.telegram_notif_calibration" :true-value="1" :false-value="0" class="rounded text-sky-600 focus:ring-sky-500" />
                  <span class="font-medium text-slate-700">📅 Peringatan Kalibrasi BPFK</span>
                </label>
              </div>
            </div>

            <!-- Test Connection Button & Result -->
            <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
              <div v-if="telegramTestStatus" class="text-xs" :class="telegramTestStatus.success ? 'text-emerald-600 font-bold' : 'text-rose-600 font-medium'">
                {{ telegramTestStatus.message }}
              </div>
              <div v-else class="text-[11px] text-slate-400">
                Pastikan Bot sudah diundang ke grup sebelum melakukan pengujian.
              </div>

              <button 
                type="button"
                @click="runTelegramTest"
                :disabled="settingStore.testingTelegram || !form.telegram_bot_token || !form.telegram_chat_id"
                class="inline-flex items-center gap-2 px-4 py-2 bg-sky-500 hover:bg-sky-600 active:bg-sky-700 text-white text-xs font-bold rounded-xl shadow-xs transition disabled:opacity-50 cursor-pointer"
              >
                <Send v-if="!settingStore.testingTelegram" class="w-3.5 h-3.5" />
                <Loader2 v-else class="w-3.5 h-3.5 animate-spin" />
                <span>{{ settingStore.testingTelegram ? 'Menguji Koneksi...' : 'Test Kirim Pesan Telegram' }}</span>
              </button>
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
                <div class="font-bold underline text-slate-900">{{ form.head_ipsrs_name || 'Ahmad Elektromedik, S.Tr.Kes' }}</div>
                <div v-if="form.head_ipsrs_nip" class="text-[8px] text-slate-500">NIP: {{ form.head_ipsrs_nip }}</div>
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
  X, Sliders, Image, Upload, Eye, EyeOff, Sparkles, Send, HelpCircle, UserCheck 
} from 'lucide-vue-next';
import { useSettingStore } from '../../stores/settingStore';
import { validateFile } from '../../utils/fileValidation';

const settingStore = useSettingStore();

const form = reactive({
  hospital_name: '',
  hospital_subtitle: '',
  hospital_address: '',
  hospital_phone: '',
  hospital_city: '',
  head_ipsrs_name: '',
  head_ipsrs_nip: '',
  telegram_bot_token: '',
  telegram_chat_id: '',
  telegram_notif_emergency: 1,
  telegram_notif_routine: 1,
  telegram_notif_validation: 1,
  telegram_notif_calibration: 1
});

const showToken = ref(false);
const telegramTestStatus = ref(null);
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
  form.head_ipsrs_name = s.head_ipsrs_name || 'Ahmad Elektromedik, S.Tr.Kes';
  form.head_ipsrs_nip = s.head_ipsrs_nip || '19850712 201001 1 002';
  form.telegram_bot_token = s.telegram_bot_token || '';
  form.telegram_chat_id = s.telegram_chat_id || '';
  form.telegram_notif_emergency = s.telegram_notif_emergency !== undefined ? Number(s.telegram_notif_emergency) : 1;
  form.telegram_notif_routine = s.telegram_notif_routine !== undefined ? Number(s.telegram_notif_routine) : 1;
  form.telegram_notif_validation = s.telegram_notif_validation !== undefined ? Number(s.telegram_notif_validation) : 1;
  form.telegram_notif_calibration = s.telegram_notif_calibration !== undefined ? Number(s.telegram_notif_calibration) : 1;
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
  formData.append('head_ipsrs_name', form.head_ipsrs_name);
  formData.append('head_ipsrs_nip', form.head_ipsrs_nip);
  formData.append('telegram_bot_token', form.telegram_bot_token);
  formData.append('telegram_chat_id', form.telegram_chat_id);
  formData.append('telegram_notif_emergency', form.telegram_notif_emergency);
  formData.append('telegram_notif_routine', form.telegram_notif_routine);
  formData.append('telegram_notif_validation', form.telegram_notif_validation);
  formData.append('telegram_notif_calibration', form.telegram_notif_calibration);

  if (logoFile.value) {
    formData.append('logo', logoFile.value);
  }

  const result = await settingStore.updateSettings(formData);
  if (result.success) {
    alertSuccess.value = true;
    alertMessage.value = 'Pengaturan Rumah Sakit & Notifikasi Telegram berhasil diperbarui!';
    resetLogoSelection();
    syncFormWithStore();
  } else {
    alertSuccess.value = false;
    alertMessage.value = result.message || 'Gagal menyimpan pengaturan.';
  }
}

async function runTelegramTest() {
  telegramTestStatus.value = null;
  if (!form.telegram_bot_token || !form.telegram_chat_id) {
    telegramTestStatus.value = {
      success: false,
      message: 'Harap isi Token Bot dan Chat ID terlebih dahulu.'
    };
    return;
  }

  const result = await settingStore.testTelegram({
    bot_token: form.telegram_bot_token,
    chat_id: form.telegram_chat_id
  });
  telegramTestStatus.value = result;
}
</script>
