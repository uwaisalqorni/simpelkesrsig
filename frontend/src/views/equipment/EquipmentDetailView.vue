<template>
  <div class="space-y-6">
    <!-- Printable Official Equipment History Card (Only visible on print) -->
    <div v-if="equipment" class="hidden print:block text-slate-900 pb-2">
      <!-- Kop Surat Resmi RS -->
      <div class="flex items-center justify-between pb-3">
        <div class="flex items-center gap-3">
          <img :src="settingStore.hospitalLogoUrl" class="w-14 h-14 object-contain" />
          <div>
            <div class="text-base font-extrabold text-slate-900 uppercase tracking-tight">{{ settingStore.hospitalName }}</div>
            <div class="text-[11px] font-bold text-emerald-700 uppercase tracking-wide">{{ settingStore.hospitalSubtitle }}</div>
            <div class="text-[9px] text-slate-500 mt-0.5">{{ settingStore.hospitalAddress }} &bull; Telp: {{ settingStore.hospitalPhone }}</div>
          </div>
        </div>
        <div class="text-right">
          <div class="inline-block px-3 py-1 bg-slate-100 border border-slate-300 rounded font-mono font-black text-sm text-slate-900">
            {{ equipment.asset_code }}
          </div>
          <div class="text-[10px] font-bold text-slate-700 mt-1 uppercase">Kartu Riwayat Alat (History Card)</div>
          <div class="text-[9px] text-slate-400">Dicetak: {{ printDate }}</div>
        </div>
      </div>

      <!-- Double Divider Line Kop Surat -->
      <div class="border-b-2 border-slate-900"></div>
      <div class="border-b border-slate-900 mt-0.5 mb-4"></div>

      <!-- Judul Dokumen -->
      <div class="text-center font-bold text-xs uppercase tracking-wider underline my-2">
        KARTU PEMELIHARAAN & RIWAYAT ALAT KESEHATAN
      </div>

      <!-- Identitas & Spesifikasi Alat Medis -->
      <div class="grid grid-cols-2 gap-3 text-[11px] my-3 p-3 bg-slate-50/60 border border-slate-300 rounded-md">
        <div class="space-y-1">
          <div><span class="text-slate-500 inline-block w-28">Nama Alat</span>: <strong>{{ equipment.name }}</strong></div>
          <div><span class="text-slate-500 inline-block w-28">Merk / Tipe</span>: {{ equipment.brand || '-' }} / {{ equipment.model_type || '-' }}</div>
          <div><span class="text-slate-500 inline-block w-28">Nomor Seri (SN)</span>: <span class="font-mono">{{ equipment.serial_number }}</span></div>
          <div><span class="text-slate-500 inline-block w-28">Kategori / Risiko</span>: {{ equipment.category_name }} ({{ equipment.risk_level?.toUpperCase() }} RISK)</div>
        </div>
        <div class="space-y-1">
          <div><span class="text-slate-500 inline-block w-32">Ruangan Penempatan</span>: <strong>{{ equipment.room_name }}</strong> ({{ equipment.building }})</div>
          <div><span class="text-slate-500 inline-block w-32">Tanggal Pengadaan</span>: {{ equipment.purchase_date || '-' }}</div>
          <div><span class="text-slate-500 inline-block w-32">Masa Garansi</span>: {{ equipment.warranty_expire || '-' }}</div>
          <div>
            <span class="text-slate-500 inline-block w-32">Status Operasional</span>: 
            <span class="uppercase font-bold text-slate-900">{{ formatStatus(equipment.operational_status) }}</span>
          </div>
        </div>
      </div>

      <!-- Riwayat Kalibrasi -->
      <div class="text-[11px] mb-4">
        <div class="font-bold text-slate-800 mb-1.5 flex items-center justify-between">
          <span>A. Riwayat Uji & Kalibrasi Berkala (BPFK / Vendor Terakreditasi):</span>
          <span class="text-[10px] text-slate-500 font-normal">Total: {{ equipment.calibrations?.length || 0 }} Rekaman</span>
        </div>
        <table class="w-full text-left text-[10px] border border-slate-300">
          <thead class="bg-slate-100 border-b border-slate-300 text-slate-800">
            <tr>
              <th class="p-1.5 font-bold w-8 text-center">No</th>
              <th class="p-1.5 font-bold">No. Sertifikat</th>
              <th class="p-1.5 font-bold">Lembaga Penguji</th>
              <th class="p-1.5 font-bold text-center">Tgl Kalibrasi</th>
              <th class="p-1.5 font-bold text-center">Masa Berlaku</th>
              <th class="p-1.5 font-bold text-center">Hasil Kelaikan</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(c, idx) in equipment.calibrations" :key="c.id" class="border-b border-slate-200">
              <td class="p-1.5 text-center font-bold">{{ idx + 1 }}</td>
              <td class="p-1.5 font-mono font-bold">{{ c.certificate_number }}</td>
              <td class="p-1.5">{{ c.vendor_name }}</td>
              <td class="p-1.5 text-center">{{ c.calibration_date }}</td>
              <td class="p-1.5 text-center font-bold">{{ c.valid_until }}</td>
              <td class="p-1.5 text-center uppercase font-bold">{{ c.result }}</td>
            </tr>
            <tr v-if="!equipment.calibrations || equipment.calibrations.length === 0">
              <td colspan="6" class="p-2.5 text-center text-slate-400 italic">Belum ada catatan riwayat kalibrasi resmi untuk alat ini.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Riwayat Perbaikan -->
      <div class="text-[11px] mb-4">
        <div class="font-bold text-slate-800 mb-1.5 flex items-center justify-between">
          <span>B. Riwayat Servis, Pemeliharaan & Perbaikan Kerusakan (IPSRS):</span>
          <span class="text-[10px] text-slate-500 font-normal">Total: {{ equipment.work_orders?.length || 0 }} Laporan</span>
        </div>
        <table class="w-full text-left text-[10px] border border-slate-300">
          <thead class="bg-slate-100 border-b border-slate-300 text-slate-800">
            <tr>
              <th class="p-1.5 font-bold w-8 text-center">No</th>
              <th class="p-1.5 font-bold">No. Tiket</th>
              <th class="p-1.5 font-bold text-center">Tgl Lapor</th>
              <th class="p-1.5 font-bold">Keluhan / Gejala Kerusakan</th>
              <th class="p-1.5 font-bold">Teknisi PJ</th>
              <th class="p-1.5 font-bold text-center">Status Akhir</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(wo, idx) in equipment.work_orders" :key="wo.id" class="border-b border-slate-200">
              <td class="p-1.5 text-center font-bold">{{ idx + 1 }}</td>
              <td class="p-1.5 font-mono font-bold">{{ wo.ticket_number }}</td>
              <td class="p-1.5 text-center whitespace-nowrap">{{ wo.reported_at }}</td>
              <td class="p-1.5">{{ wo.issue_description }}</td>
              <td class="p-1.5">{{ wo.technician_name || '-' }}</td>
              <td class="p-1.5 text-center uppercase font-bold">{{ formatStatus(wo.status) }}</td>
            </tr>
            <tr v-if="!equipment.work_orders || equipment.work_orders.length === 0">
              <td colspan="6" class="p-2.5 text-center text-slate-400 italic">Belum pernah ada riwayat servis atau laporan kerusakan.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Lembar Tanda Tangan & Pengesahan IPSRS -->
      <div class="grid grid-cols-2 gap-8 pt-5 mt-5 border-t border-slate-300 text-center text-[11px]">
        <div>
          <div class="text-slate-600">Mengetahui,</div>
          <div class="font-bold text-slate-900 mt-0.5">Kepala {{ settingStore.hospitalSubtitle }}</div>
          <div class="text-[10px] text-slate-500">{{ settingStore.hospitalName }}</div>
          <div class="h-14"></div>
          <div class="font-bold underline text-slate-900">( {{ settingStore.headIpsrsName || '................................' }} )</div>
          <div class="text-[9px] text-slate-500">NIP: {{ settingStore.headIpsrsNip || '....................' }}</div>
        </div>

        <div>
          <div class="text-slate-600">{{ settingStore.hospitalCity || 'Gondanglegi' }}, {{ printDate }}</div>
          <div class="font-bold text-slate-900 mt-0.5">Petugas / Teknisi Elektromedis</div>
          <div class="text-[10px] text-slate-500">{{ settingStore.hospitalName }}</div>
          <div class="h-14"></div>
          <div class="font-bold underline text-slate-900">( {{ authStore.userName || 'Teknisi IPSRS' }} )</div>
          <div class="text-[9px] text-slate-500">Petugas Pemeliharaan Alkes</div>
        </div>
      </div>

      <!-- Footer Dokumen Resmi -->
      <div class="flex justify-between items-center text-[9px] text-slate-400 mt-5 pt-2 border-t border-dashed border-slate-300">
        <div>SIMPELKES - Sistem Informasi Manajemen Pemeliharaan Fasilitas & Elektromedis RS</div>
        <div>Dokumen Rekam Fisik & Kelaikan Alat Medis Resmi &bull; Lembar Rekam Medis IPSRS</div>
      </div>
    </div>

    <!-- Back & Breadcrumb (Screen only) -->
    <div class="flex items-center justify-between print:hidden">
      <router-link to="/equipment" class="inline-flex items-center gap-1 text-xs font-bold text-slate-500 hover:text-slate-800">
        <ArrowLeft class="w-4 h-4" />
        Kembali ke Inventaris
      </router-link>

      <div class="flex items-center gap-2">
        <button 
          @click="printEquipmentCard"
          class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-900 hover:bg-black text-white text-xs font-bold rounded-lg shadow-sm"
          title="Cetak Kartu Riwayat Alat Medis"
        >
          <Printer class="w-3.5 h-3.5" />
          <span>Cetak Kartu Riwayat</span>
        </button>

        <router-link 
          :to="`/tickets/create?equipment_id=${equipment?.id}`"
          class="flex items-center gap-2 px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-lg shadow-sm"
        >
          <AlertCircle class="w-4 h-4" />
          Lapor Kerusakan Alat Ini
        </router-link>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="py-16 text-center text-slate-400 text-xs print:hidden">
      <div class="w-6 h-6 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
      Memuat detail spesifikasi alat medis...
    </div>

    <template v-else-if="equipment">
      <div class="print:hidden space-y-6">
      <!-- Main Info Card -->
      <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
          <div class="flex items-start justify-between gap-4">
            <div>
              <span class="text-xs font-mono font-bold px-2 py-0.5 bg-slate-100 text-slate-800 rounded border border-slate-200">
                {{ equipment.asset_code }}
              </span>
              <h1 class="text-2xl font-black text-slate-900 mt-2">{{ equipment.name }}</h1>
              <p class="text-xs text-slate-500 mt-0.5">
                {{ equipment.brand }} &bull; Tipe: {{ equipment.model_type || '-' }} &bull; SN: {{ equipment.serial_number }}
              </p>
            </div>

            <div>
              <span 
                :class="[
                  'px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider',
                  equipment.operational_status === 'operasional' ? 'bg-emerald-100 text-emerald-700' :
                  equipment.operational_status === 'rusak_ringan' ? 'bg-amber-100 text-amber-700' :
                  equipment.operational_status === 'rusak_berat' ? 'bg-rose-100 text-rose-700' : 'bg-slate-200 text-slate-700'
                ]"
              >
                {{ formatStatus(equipment.operational_status) }}
              </span>
            </div>
          </div>

          <!-- Key Details Grid -->
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 pt-4 border-t border-slate-100 text-xs">
            <div>
              <div class="text-slate-400 font-medium">Ruangan Penempatan</div>
              <div class="font-bold text-slate-800 mt-0.5">{{ equipment.room_name }}</div>
              <div class="text-[10px] text-slate-400">{{ equipment.building }} ({{ equipment.floor }})</div>
            </div>

            <div>
              <div class="text-slate-400 font-medium">Kategori & Risiko</div>
              <div class="font-bold text-slate-800 mt-0.5">{{ equipment.category_name }}</div>
              <span class="text-[10px] font-semibold text-rose-600 uppercase">{{ equipment.risk_level }} RISK</span>
            </div>

            <div>
              <div class="text-slate-400 font-medium">Interval Preventif (PM)</div>
              <div class="font-bold text-slate-800 mt-0.5">{{ equipment.default_maintenance_interval_days || 90 }} Hari</div>
              <div class="text-[10px] text-slate-400">Pemeriksaan Rutin</div>
            </div>

            <div>
              <div class="text-slate-400 font-medium">Jumlah Unit (Qty)</div>
              <div class="font-extrabold text-slate-900 mt-0.5">{{ equipment.quantity || 1 }} Unit</div>
            </div>

            <div>
              <div class="text-slate-400 font-medium">Tgl Kalibrasi Terakhir</div>
              <div class="font-bold text-slate-800 mt-0.5">{{ equipment.calibration_date || 'Belum Dikalibrasi' }}</div>
              <div class="text-[10px] text-slate-400 font-mono">{{ equipment.certificate_number || '-' }}</div>
            </div>

            <div>
              <div class="text-slate-400 font-medium">Masa Berlaku Kalibrasi</div>
              <div class="font-bold text-slate-800 mt-0.5">{{ equipment.valid_until || '-' }}</div>
              <span 
                v-if="equipment.valid_until"
                :class="[
                  'inline-block px-2 py-0.5 rounded text-[10px] font-bold mt-0.5',
                  getCalibrationStatus(equipment).class
                ]"
              >
                {{ getCalibrationStatus(equipment).text }}
              </span>
            </div>

            <div>
              <div class="text-slate-400 font-medium">Tanggal Pengadaan</div>
              <div class="font-bold text-slate-800 mt-0.5">{{ equipment.purchase_date || '-' }}</div>
            </div>

            <div>
              <div class="text-slate-400 font-medium">Masa Garansi</div>
              <div class="font-bold text-slate-800 mt-0.5">{{ equipment.warranty_expire || '-' }}</div>
            </div>

            <div>
              <div class="text-slate-400 font-medium">Vendor / Supplier</div>
              <div class="font-bold text-slate-800 mt-0.5">{{ equipment.vendor_supplier || '-' }}</div>
            </div>
          </div>
        </div>

        <!-- Foto & QR Column -->
        <div class="space-y-4">
          <!-- Foto Fisik Alat (Jika Ada) -->
          <div v-if="equipment.image_path" class="bg-slate-50 border border-slate-200/80 rounded-xl p-3 text-center">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-2">Foto Fisik Alat</div>
            <img 
              :src="getUploadUrl(equipment.image_path)" 
              class="w-full h-44 object-cover rounded-lg border border-slate-200 shadow-2xs cursor-pointer hover:opacity-95 transition-opacity" 
              @click="openPhotoZoom(getUploadUrl(equipment.image_path))" 
              title="Klik untuk memperbesar foto"
            />
          </div>

          <!-- QR Canvas Card -->
          <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-5 flex flex-col items-center justify-center text-center">
            <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-800 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200 mb-3">
              Stiker QR Alkes
            </div>

            <!-- Mode Toggle -->
            <div class="mb-3 flex items-center gap-1 bg-white border border-slate-200 p-1 rounded-xl text-[10px] font-semibold">
              <button 
                type="button"
                @click="changeQRMode('url')"
                :class="['px-2.5 py-1 rounded-lg transition-all', qrMode === 'url' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-500 hover:text-slate-800']"
              >
                Tautan Web (Kamera HP)
              </button>
              <button 
                type="button"
                @click="changeQRMode('code')"
                :class="['px-2.5 py-1 rounded-lg transition-all', qrMode === 'code' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-500 hover:text-slate-800']"
              >
                Kode Aset (Scanner Fisik)
              </button>
            </div>

            <canvas ref="qrCanvas" class="shadow-sm rounded-lg bg-white p-2 border border-slate-200 mx-auto"></canvas>

            <!-- Opsi IP LAN jika di localhost -->
            <div v-if="qrMode === 'url' && isLocalhost" class="mt-2 text-left w-full bg-white border border-emerald-200 rounded-lg p-2 text-[10px]">
              <div class="flex items-center justify-between text-emerald-900 font-semibold mb-1">
                <span>IP LAN untuk HP:</span>
                <label class="cursor-pointer">
                  <input type="checkbox" v-model="useLanIp" @change="renderDetailQR" class="rounded text-emerald-600 mr-1" />
                  Pakai LAN
                </label>
              </div>
              <input 
                v-if="useLanIp"
                v-model="customHost" 
                @input="renderDetailQR"
                class="w-full px-1.5 py-0.5 border border-slate-200 rounded font-mono text-[10px]"
                placeholder="Contoh: 192.168.0.194"
              />
            </div>

            <div class="mt-2 text-[11px] text-slate-500 font-medium leading-tight">
              {{ qrMode === 'url' ? 'Scan stiker QR ini menggunakan kamera HP (iPhone/Android) untuk otomatis membuka alat ini.' : 'Format teks mentah kode aset untuk barcode reader RS.' }}
            </div>

            <a 
              v-if="qrMode === 'url'" 
              :href="currentGeneratedUrl" 
              target="_blank" 
              class="mt-1 text-[10px] text-emerald-600 hover:underline break-all font-mono"
            >
              {{ currentGeneratedUrl }}
            </a>
          </div>
        </div>
      </div>

      <!-- History Tabs -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="border-b border-slate-200 flex px-6">
          <button 
            @click="activeTab = 'tickets'"
            :class="['py-3 px-4 text-xs font-bold border-b-2 transition-colors flex items-center gap-2', activeTab === 'tickets' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-slate-500 hover:text-slate-800']"
          >
            <Wrench class="w-4 h-4" />
            Riwayat Perbaikan ({{ equipment.work_orders?.length || 0 }})
          </button>

          <button 
            @click="activeTab = 'calibrations'"
            :class="['py-3 px-4 text-xs font-bold border-b-2 transition-colors flex items-center gap-2', activeTab === 'calibrations' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-slate-500 hover:text-slate-800']"
          >
            <Award class="w-4 h-4" />
            Riwayat Kalibrasi ({{ equipment.calibrations?.length || 0 }})
          </button>

          <button 
            @click="activeTab = 'preventives'"
            :class="['py-3 px-4 text-xs font-bold border-b-2 transition-colors flex items-center gap-2', activeTab === 'preventives' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-slate-500 hover:text-slate-800']"
          >
            <CalendarCheck class="w-4 h-4" />
            Jadwal Preventif Berkala ({{ equipment.preventives?.length || 0 }})
          </button>
        </div>

        <div class="p-6">
          <!-- Tab 1: Tiket Perbaikan -->
          <div v-if="activeTab === 'tickets'" class="space-y-3">
            <div v-for="wo in equipment.work_orders" :key="wo.id" class="p-4 bg-slate-50 rounded-xl border border-slate-200/60 flex items-center justify-between">
              <div>
                <div class="flex items-center gap-2">
                  <span class="font-bold text-xs text-slate-800">{{ wo.ticket_number }}</span>
                  <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 uppercase">
                    {{ wo.priority }}
                  </span>
                  <span class="text-[10px] font-bold text-emerald-700">{{ wo.status }}</span>
                </div>
                <div class="text-xs text-slate-600 mt-1">{{ wo.issue_description }}</div>
                <div class="text-[10px] text-slate-400 mt-1">Dilaporkan: {{ wo.reported_at }} &bull; Oleh: {{ wo.reported_by_name || 'Unit' }}</div>
              </div>
              <router-link :to="`/tickets/${wo.id}`" class="px-3 py-1 bg-white hover:bg-slate-100 text-slate-700 font-bold text-xs rounded-lg border border-slate-200 shadow-sm">
                Lihat Tiket
              </router-link>
            </div>
            <div v-if="!equipment.work_orders || equipment.work_orders.length === 0" class="text-center py-8 text-xs text-slate-400">
              Belum pernah ada riwayat tiket perbaikan untuk alat ini.
            </div>
          </div>

          <!-- Tab 2: Kalibrasi -->
          <div v-if="activeTab === 'calibrations'" class="space-y-3">
            <div v-for="cal in equipment.calibrations" :key="cal.id" class="p-4 bg-slate-50 rounded-xl border border-slate-200/60 flex items-center justify-between">
              <div>
                <div class="flex items-center gap-2">
                  <span class="font-bold text-xs text-slate-800">{{ cal.certificate_number }}</span>
                  <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 uppercase">
                    {{ cal.result }}
                  </span>
                </div>
                <div class="text-xs text-slate-600 mt-1">Lembaga Uji: {{ cal.vendor_name }}</div>
                <div class="text-[10px] text-slate-400 mt-1">
                  Tanggal Uji: {{ cal.calibration_date }} &bull; Berlaku Sampai: <strong class="text-slate-700">{{ cal.valid_until }}</strong>
                </div>
              </div>
            </div>
            <div v-if="!equipment.calibrations || equipment.calibrations.length === 0" class="text-center py-8 text-xs text-slate-400">
              Belum ada riwayat kalibrasi terdaftar.
            </div>
          </div>

          <!-- Tab 3: Preventif -->
          <div v-if="activeTab === 'preventives'" class="space-y-3">
            <div v-for="pm in equipment.preventives" :key="pm.id" class="p-4 bg-slate-50 rounded-xl border border-slate-200/60 flex items-center justify-between">
              <div>
                <div class="font-bold text-xs text-slate-800">Jadwal: {{ pm.scheduled_date }}</div>
                <div class="text-[10px] text-slate-500 mt-0.5">Siklus: {{ pm.frequency }} &bull; Status: <strong class="uppercase text-emerald-600">{{ pm.status }}</strong></div>
              </div>
            </div>
            <div v-if="!equipment.preventives || equipment.preventives.length === 0" class="text-center py-8 text-xs text-slate-400">
              Belum ada jadwal pemeliharaan preventif.
            </div>
          </div>
        </div>
      </div>
      </div>
    </template>

    <!-- Modal Zoom Foto Fisik Alat -->
    <div v-if="previewPhotoZoom" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4" @click="previewPhotoZoom = null">
      <div class="bg-white rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl p-4 text-center relative animate-in fade-in zoom-in duration-150" @click.stop>
        <button @click="previewPhotoZoom = null" class="absolute top-3 right-3 text-slate-400 hover:text-slate-800 bg-white/80 rounded-full p-1 shadow cursor-pointer"><X class="w-5 h-5" /></button>
        <img :src="previewPhotoZoom" class="max-h-[70vh] w-auto mx-auto rounded-xl object-contain shadow-sm border border-slate-100" />
        <div class="mt-3 text-sm font-bold text-slate-900">{{ equipment?.name }}</div>
        <div class="text-xs text-slate-500 font-mono">{{ equipment?.asset_code }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { useRoute } from 'vue-router';
import { ArrowLeft, AlertCircle, Wrench, Award, CalendarCheck, Printer, X } from 'lucide-vue-next';
import { useAuthStore } from '../../stores/authStore';
import { useSettingStore } from '../../stores/settingStore';
import axiosClient, { getUploadUrl } from '../../api/axiosClient';
import QRCode from 'qrcode';

const route = useRoute();
const authStore = useAuthStore();
const settingStore = useSettingStore();
const equipment = ref(null);
const loading = ref(true);
const activeTab = ref('tickets');
const qrCanvas = ref(null);
const previewPhotoZoom = ref(null);
const printDate = new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });

const openPhotoZoom = (url) => {
  previewPhotoZoom.value = url;
};

const getCalibrationStatus = (item) => {
  if (!item || !item.valid_until) {
    return {
      text: 'Belum Dikalibrasi',
      class: 'bg-slate-100 text-slate-600 border border-slate-200'
    };
  }
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const validDate = new Date(item.valid_until);
  const diffTime = validDate.getTime() - today.getTime();
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

  if (diffDays < 0) {
    return {
      text: 'Kadaluarsa',
      class: 'bg-rose-50 text-rose-700 border border-rose-200 font-bold'
    };
  } else if (diffDays <= 30) {
    return {
      text: `H-${diffDays} Hari`,
      class: 'bg-amber-50 text-amber-700 border border-amber-200 font-bold'
    };
  } else {
    return {
      text: 'Laik Pakai',
      class: 'bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold'
    };
  }
};

const qrMode = ref('url');
const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
const lanIp = '192.168.0.194';
const customHost = ref(isLocalhost ? `${lanIp}${window.location.port ? ':' + window.location.port : ''}` : window.location.host);
const useLanIp = ref(isLocalhost);
const currentGeneratedUrl = ref('');

const getDetailTargetUrl = () => {
  if (!equipment.value) return '';
  if (qrMode.value === 'code') {
    return equipment.value.asset_code;
  }
  let host = window.location.host;
  if (useLanIp.value && customHost.value.trim()) {
    host = customHost.value.trim();
  }
  const base = window.location.pathname.startsWith('/simpelkesrsig') ? '/simpelkesrsig/' : '/';
  return `${window.location.protocol}//${host}${base}equipment/${equipment.value.id}`;
};

const renderDetailQR = async () => {
  if (!equipment.value) return;
  await nextTick();
  if (qrCanvas.value) {
    const textToEncode = getDetailTargetUrl();
    currentGeneratedUrl.value = textToEncode;
    QRCode.toCanvas(qrCanvas.value, textToEncode, {
      width: 140,
      margin: 1,
      color: {
        dark: '#0f172a',
        light: '#ffffff'
      }
    });
  }
};

const changeQRMode = (mode) => {
  qrMode.value = mode;
  renderDetailQR();
};

const printEquipmentCard = () => {
  window.print();
};

const fetchDetail = async () => {
  loading.value = true;
  try {
    const res = await axiosClient.get(`/equipment/${route.params.id}`);
    if (res.success && res.data) {
      equipment.value = res.data;
      await renderDetailQR();
    }
  } catch (err) {
    console.error('Failed to fetch equipment details:', err);
  } finally {
    loading.value = false;
  }
};

const formatStatus = (s) => {
  const map = {
    'operasional': 'Operasional',
    'rusak_ringan': 'Rusak Ringan',
    'rusak_berat': 'Rusak Berat',
    'afkir': 'Afkir'
  };
  return map[s] || s;
};

onMounted(() => {
  fetchDetail();
});
</script>
