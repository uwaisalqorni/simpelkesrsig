<template>
  <div class="min-h-screen bg-slate-950 flex flex-col justify-center py-12 sm:px-6 lg:px-8 relative overflow-hidden">
    <!-- Ambient glowing backgrounds -->
    <div class="absolute -top-40 -right-40 w-96 h-96 rounded-full bg-emerald-500/15 blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-40 -left-40 w-96 h-96 rounded-full bg-teal-500/15 blur-3xl pointer-events-none"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] rounded-full bg-emerald-900/10 blur-[120px] pointer-events-none"></div>

    <!-- Header Logo & Hospital Title -->
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center z-10 px-4">
      <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-slate-900/90 border border-slate-700/80 shadow-2xl shadow-emerald-950/60 p-3 mb-4 backdrop-blur-sm">
        <img :src="settingStore.hospitalLogoUrl" alt="Logo RS" class="w-full h-full object-contain" />
      </div>
      <h1 class="text-3xl font-black text-white tracking-tight">SIMPELKES</h1>
      <p class="mt-1 text-sm font-bold text-emerald-400">
        {{ settingStore.hospitalName }}
      </p>
      <p class="text-xs text-slate-400 mt-0.5">
        {{ settingStore.hospitalSubtitle }}
      </p>
    </div>

    <!-- Login Box -->
    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md z-10 px-4 sm:px-0">
      <div class="bg-slate-900/85 backdrop-blur-xl border border-slate-800/80 py-8 px-6 shadow-2xl rounded-3xl sm:px-10 space-y-6">
        
        <div>
          <h2 class="text-base font-bold text-white tracking-tight">Masuk ke Akun Anda</h2>
          <p class="text-xs text-slate-400 mt-0.5">Gunakan ID Pengguna & Kata Sandi terdaftar</p>
        </div>

        <!-- Alert Error Message -->
        <div v-if="authStore.error" class="p-3.5 bg-rose-500/15 border border-rose-500/30 rounded-2xl text-rose-300 text-xs font-medium flex items-center gap-2.5 animate-in fade-in">
          <AlertCircle class="w-4 h-4 text-rose-400 flex-shrink-0" />
          <span>{{ authStore.error }}</span>
        </div>

        <form class="space-y-4" @submit.prevent="handleLogin">
          <!-- Input Username -->
          <div>
            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
              ID Pengguna / Username
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                <User class="w-4 h-4" />
              </div>
              <input 
                v-model="username" 
                type="text" 
                required 
                autocomplete="username"
                placeholder="Masukkan username Anda" 
                class="w-full pl-10 pr-4 py-2.5 bg-slate-950/80 border border-slate-700/80 rounded-xl text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-all font-medium"
              />
            </div>
          </div>

          <!-- Input Password -->
          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider">
                Kata Sandi
              </label>
              <button 
                type="button" 
                @click="showForgotModal = true"
                class="text-xs text-emerald-400 hover:text-emerald-300 transition-colors font-semibold cursor-pointer"
              >
                Lupa Password?
              </button>
            </div>
            
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                <Lock class="w-4 h-4" />
              </div>
              <input 
                v-model="password" 
                :type="showPassword ? 'text' : 'password'" 
                required 
                autocomplete="current-password"
                placeholder="Masukkan kata sandi" 
                class="w-full pl-10 pr-11 py-2.5 bg-slate-950/80 border border-slate-700/80 rounded-xl text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-all font-medium"
              />
              <button 
                type="button" 
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-500 hover:text-slate-300 transition-colors cursor-pointer"
                title="Tampilkan / Sembunyikan Kata Sandi"
              >
                <EyeOff v-if="showPassword" class="w-4 h-4" />
                <Eye v-else class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Remember Me Checkbox -->
          <div class="flex items-center justify-between pt-1">
            <label class="flex items-center gap-2 cursor-pointer select-none">
              <input 
                type="checkbox" 
                v-model="rememberMe" 
                class="w-4 h-4 rounded border-slate-700 bg-slate-950 text-emerald-600 focus:ring-emerald-500/30 cursor-pointer"
              />
              <span class="text-xs text-slate-400 font-medium">Ingat ID Pengguna di perangkat ini</span>
            </label>
          </div>

          <!-- Submit Button -->
          <button 
            type="submit" 
            :disabled="authStore.loading" 
            class="w-full mt-2 flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 shadow-lg shadow-emerald-950/60 disabled:opacity-50 transition-all cursor-pointer"
          >
            <span v-if="authStore.loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            <span>{{ authStore.loading ? 'Memverifikasi...' : 'Masuk ke Sistem' }}</span>
          </button>
        </form>

        <!-- Security Notice -->
        <div class="pt-4 border-t border-slate-800/80 text-center">
          <div class="flex items-center justify-center gap-1.5 text-[11px] text-slate-500 font-medium">
            <ShieldCheck class="w-3.5 h-3.5 text-emerald-500" />
            <span>Sistem Pemeliharaan Terenkripsi & Terintegrasi</span>
          </div>
        </div>

      </div>

      <!-- Copyright Footer -->
      <div class="text-center text-xs text-slate-500 mt-6 space-y-1">
        <div>&copy; {{ new Date().getFullYear() }} {{ settingStore.hospitalName }}</div>
        <div class="text-[11px] text-slate-600">IPSRS &bull; Sistem Informasi Pemeliharaan Alat Kesehatan</div>
      </div>
    </div>

    <!-- Modal Pop-Up: Bantuan Lupa Kata Sandi (Hubungi IT Admin) -->
    <div v-if="showForgotModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4" @click="showForgotModal = false">
      <div class="bg-slate-900 border border-slate-700/80 rounded-3xl max-w-md w-full p-6 shadow-2xl relative text-left space-y-5 animate-in fade-in zoom-in duration-150" @click.stop>
        
        <!-- Header Modal -->
        <div class="flex items-start justify-between">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center flex-shrink-0">
              <KeyRound class="w-6 h-6" />
            </div>
            <div>
              <h3 class="text-base font-bold text-white">Lupa Kata Sandi?</h3>
              <p class="text-xs text-slate-400">Pusat Bantuan Akun SIMPELKES</p>
            </div>
          </div>
          <button 
            @click="showForgotModal = false" 
            class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition cursor-pointer"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Body Explanation -->
        <div class="text-xs text-slate-300 leading-relaxed space-y-2.5">
          <p>
            Demi keamanan data rekam medis alat dan integritas validasi tindakan di lingkungan <strong>{{ settingStore.hospitalName }}</strong>, pengaturan ulang kata sandi dilakukan terpusat melalui <strong>Administrator IT / IPSRS</strong>.
          </p>
          <p class="text-slate-400 text-[11px]">
            Silakan hubungi staf IT atau kunjungi unit IPSRS dengan menyebutkan <strong>Nama Lengkap</strong> dan <strong>Unit / Ruangan Penempatan</strong> Anda.
          </p>
        </div>

        <!-- Contact Box -->
        <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-3 text-xs">
          <div class="flex items-center gap-3">
            <Building2 class="w-4 h-4 text-emerald-400 flex-shrink-0" />
            <div>
              <div class="font-bold text-white">{{ settingStore.hospitalSubtitle }}</div>
              <div class="text-[11px] text-slate-400">{{ settingStore.hospitalName }}</div>
            </div>
          </div>

          <div class="flex items-center gap-3 pt-2 border-t border-slate-800/80">
            <PhoneCall class="w-4 h-4 text-emerald-400 flex-shrink-0" />
            <div>
              <div class="font-bold text-white">Hotline / Telepon IT:</div>
              <div class="text-[11px] text-emerald-300 font-mono font-semibold">{{ settingStore.hospitalPhone || '(0341) 879222' }}</div>
            </div>
          </div>

          <div class="flex items-center gap-3 pt-2 border-t border-slate-800/80">
            <MapPin class="w-4 h-4 text-emerald-400 flex-shrink-0" />
            <div>
              <div class="font-bold text-white">Lokasi Kantor:</div>
              <div class="text-[11px] text-slate-400">{{ settingStore.hospitalAddress }}</div>
            </div>
          </div>
        </div>

        <!-- Action Button -->
        <div class="pt-2 flex items-center justify-end gap-2">
          <button 
            type="button" 
            @click="showForgotModal = false"
            class="w-full py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 shadow-sm transition-all cursor-pointer text-center"
          >
            Saya Mengerti / Tutup
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { 
  User, Lock, Eye, EyeOff, AlertCircle, ShieldCheck, 
  KeyRound, X, Building2, PhoneCall, MapPin 
} from 'lucide-vue-next';
import { useAuthStore } from '../stores/authStore';
import { useSettingStore } from '../stores/settingStore';
import { useRouter, useRoute } from 'vue-router';

const authStore = useAuthStore();
const settingStore = useSettingStore();
const router = useRouter();
const route = useRoute();

const username = ref(localStorage.getItem('simpelkes_remembered_username') || '');
const password = ref('');
const showPassword = ref(false);
const rememberMe = ref(true);
const showForgotModal = ref(false);

onMounted(() => {
  settingStore.fetchSettings();
});

const handleLogin = async () => {
  if (rememberMe.value && username.value) {
    localStorage.setItem('simpelkes_remembered_username', username.value);
  } else {
    localStorage.removeItem('simpelkes_remembered_username');
  }

  const ok = await authStore.login(username.value, password.value);
  if (ok) {
    const target = route.query.redirect || '/';
    router.push(target);
  }
};
</script>
