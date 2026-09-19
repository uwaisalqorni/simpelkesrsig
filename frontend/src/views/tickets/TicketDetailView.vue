<template>
  <div class="space-y-6">
    <!-- Printable Official Letterhead & Work Order (Only visible on print) -->
    <div v-if="ticket" class="hidden print:block text-slate-900 pb-2">
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
            {{ ticket.ticket_number }}
          </div>
          <div class="text-[9px] text-slate-400 mt-1">Tgl: {{ ticket.reported_at }}</div>
        </div>
      </div>

      <!-- Double Divider Line Kop Surat -->
      <div class="border-b-2 border-slate-900"></div>
      <div class="border-b border-slate-900 mt-0.5 mb-4"></div>

      <div class="text-center font-bold text-xs uppercase tracking-wider underline my-2">
        LEMBAR KERJA & BERITA ACARA PERBAIKAN ALAT MEDIS
      </div>

      <div class="grid grid-cols-2 gap-3 text-[11px] my-3 p-3 bg-slate-50 border border-slate-300 rounded">
        <div>
          <div><strong>Nama Alat:</strong> {{ ticket.equipment_name }}</div>
          <div><strong>Kode Aset:</strong> {{ ticket.asset_code }}</div>
          <div><strong>Merk / Tipe:</strong> {{ ticket.equipment_brand || '-' }} / {{ ticket.equipment_model || '-' }}</div>
          <div><strong>Nomor Seri:</strong> {{ ticket.serial_number }}</div>
        </div>
        <div>
          <div><strong>Ruangan Penempatan:</strong> {{ ticket.room_name }}</div>
          <div><strong>Pelapor (PIC):</strong> {{ ticket.reported_by_name }}</div>
          <div><strong>Prioritas Kerusakan:</strong> <span class="uppercase font-bold">{{ ticket.priority }}</span></div>
          <div><strong>Status Akhir:</strong> <span class="uppercase font-bold">{{ ticket.status }}</span></div>
        </div>
      </div>

      <div class="text-[11px] mb-3">
        <div class="font-bold text-slate-800">1. Kendala / Gejala Kerusakan:</div>
        <div class="p-2 border border-slate-200 mt-1 rounded bg-slate-50">{{ ticket.issue_description }}</div>
      </div>

      <div class="text-[11px] mb-3">
        <div class="font-bold text-slate-800">2. Tindakan / Perbaikan oleh Teknisi:</div>
        <div class="p-2 border border-slate-200 mt-1 rounded bg-slate-50">{{ ticket.action_summary || 'Perbaikan selesai dan pengujian operasional normal.' }}</div>
      </div>

      <div v-if="ticket.parts && ticket.parts.length > 0" class="text-[11px] mb-4">
        <div class="font-bold text-slate-800 mb-1">3. Suku Cadang yang Diganti:</div>
        <table class="w-full text-left text-[10px] border border-slate-300">
          <thead class="bg-slate-100 border-b border-slate-300">
            <tr>
              <th class="p-1.5">No. Part</th>
              <th class="p-1.5">Nama Suku Cadang</th>
              <th class="p-1.5 text-center">Qty</th>
              <th class="p-1.5 text-right">Biaya Satuan</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="pt in ticket.parts" :key="pt.id" class="border-b border-slate-200">
              <td class="p-1.5">{{ pt.part_number }}</td>
              <td class="p-1.5">{{ pt.part_name }}</td>
              <td class="p-1.5 text-center">{{ pt.quantity }}</td>
              <td class="p-1.5 text-right">Rp {{ Number(pt.unit_cost).toLocaleString('id-ID') }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Tanda Tangan Serah Terima -->
      <div class="grid grid-cols-2 gap-8 text-center text-[10px] pt-4 mt-4 border-t border-slate-400">
        <div>
          <div>Teknisi Pelaksana IPSRS,</div>
          <div class="h-14 flex items-center justify-center">
            <span class="italic text-slate-400 font-serif text-xs">[ Tervalidasi Sistem ]</span>
          </div>
          <div class="font-bold underline">{{ ticket.technician_name || 'Teknisi Elektromedis' }}</div>
          <div>{{ settingStore.hospitalSubtitle }} {{ settingStore.hospitalName }}</div>
        </div>

        <div>
          <div>Petugas PIC Unit / Ruangan,</div>
          <div class="h-14 flex items-center justify-center">
            <img v-if="ticket.room_signature_path" :src="getUploadUrl(ticket.room_signature_path)" class="h-12 object-contain mx-auto" />
            <span v-else class="italic text-slate-400 font-serif text-xs">[ Tanda Tangan ]</span>
          </div>
          <div class="font-bold underline">{{ ticket.verifier_name || ticket.reported_by_name }}</div>
          <div>Unit {{ ticket.room_name }}</div>
        </div>
      </div>

      <!-- Footer Dokumen Resmi -->
      <div class="flex justify-between items-center text-[9px] text-slate-400 mt-5 pt-2 border-t border-dashed border-slate-300">
        <div>SIMPELKES - Berita Acara Perbaikan & Pemeliharaan Alkes Resmi</div>
        <div>Dokumen Rekam Medis IPSRS &bull; Tervalidasi Sistem</div>
      </div>
    </div>

    <!-- Header (Screen only) -->
    <div class="flex items-center justify-between print:hidden">
      <router-link to="/tickets" class="inline-flex items-center gap-1 text-xs font-bold text-slate-500 hover:text-slate-800">
        <ArrowLeft class="w-4 h-4" />
        Kembali ke Daftar Tiket
      </router-link>

      <div class="flex items-center gap-2">
        <!-- Button Disposisi jika admin/teknisi & belum assign -->
        <button 
          v-if="(authStore.isAdmin || authStore.isTeknisi) && !ticket?.assigned_technician_id"
          @click="openAssignModal"
          class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-sm"
        >
          Tugaskan Teknisi
        </button>

        <!-- Button Update Progress jika teknisi/admin -->
        <button 
          v-if="(authStore.isAdmin || authStore.isTeknisi) && ticket?.status !== 'closed'"
          @click="showProgressModal = true"
          class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg shadow-sm flex items-center gap-1.5"
        >
          <Wrench class="w-3.5 h-3.5" />
          Update Pengerjaan
        </button>

        <!-- Button Tambah Sparepart -->
        <button 
          v-if="(authStore.isAdmin || authStore.isTeknisi) && ticket?.status !== 'closed'"
          @click="openPartModal"
          class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-lg shadow-sm flex items-center gap-1.5"
        >
          <Plus class="w-3.5 h-3.5" />
          Ganti Sparepart
        </button>

        <!-- Button Cetak Berita Acara -->
        <button 
          @click="printWorkOrder"
          class="px-3 py-1.5 bg-slate-900 hover:bg-black text-white text-xs font-bold rounded-lg shadow-sm flex items-center gap-1.5"
          title="Cetak Berita Acara Servis"
        >
          <Printer class="w-3.5 h-3.5" />
          <span>Cetak Berita Acara</span>
        </button>

        <!-- Button Serah Terima & Tolak jika completed_technician dan berhak verifikasi -->
        <button 
          v-if="ticket?.status === 'completed_technician' && canVerify"
          @click="showSignatureModal = true"
          class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg shadow-sm flex items-center gap-1.5 animate-pulse"
        >
          <PenTool class="w-3.5 h-3.5" />
          Uji Normal & Tanda Tangan
        </button>

        <button 
          v-if="ticket?.status === 'completed_technician' && canVerify"
          @click="showRejectModal = true"
          class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-lg shadow-sm flex items-center gap-1.5"
        >
          <XCircle class="w-3.5 h-3.5" />
          Alat Belum Normal (Tolak)
        </button>
      </div>
    </div>

    <div v-if="loading" class="py-16 text-center text-slate-400 text-xs print:hidden">
      <div class="w-6 h-6 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
      Memuat detail tiket perbaikan...
    </div>

    <template v-else-if="ticket">
      <div class="print:hidden space-y-6">
        <!-- Alert Banner jika tiket sebelumnya ditolak unit -->
        <div 
          v-if="ticket.rejection_reason && ticket.status !== 'closed'" 
          class="p-4 bg-rose-50 border-l-4 border-rose-500 rounded-2xl shadow-xs space-y-1.5"
        >
          <div class="flex items-center gap-2 text-rose-800 text-xs font-bold">
            <AlertTriangle class="w-4 h-4 text-rose-600 shrink-0" />
            <span>Hasil Pengujian Fungsi Sebelumnya Ditolak oleh Unit Kerja</span>
          </div>
          <div class="text-xs text-rose-800 bg-white/90 p-3 rounded-xl border border-rose-200">
            <strong>Catatan Kendala:</strong> {{ ticket.rejection_reason }}
          </div>
          <div class="text-[11px] text-slate-500">
            Teknisi perlu menindaklanjuti kendala di atas sebelum mengajukan uji fungsi ulang kepada unit.
          </div>
        </div>

        <!-- Panel Uji Coba Fungsi & Validasi Serah Terima jika status === 'completed_technician' -->
        <div 
          v-if="ticket.status === 'completed_technician'" 
          class="p-6 bg-gradient-to-r from-indigo-950 via-slate-900 to-indigo-900 text-white rounded-2xl shadow-xl space-y-4 border border-indigo-700/40 relative overflow-hidden"
        >
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1.5 flex-1">
              <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 text-xs font-bold">
                <CheckSquare class="w-4 h-4 text-emerald-400" />
                Pengerjaan Teknisi Selesai &bull; Siap Uji Operasional
              </div>
              <h2 class="text-lg font-black tracking-tight text-white">
                Validasi Serah Terima & Uji Fungsi Unit Ruangan
              </h2>
              <p class="text-xs text-indigo-100/90 leading-relaxed max-w-2xl">
                Teknisi (<strong class="text-emerald-300">{{ ticket.technician_name || 'IPSRS' }}</strong>) telah menyelesaikan tindakan perbaikan. Sebelum alat medis digunakan kembali untuk pelayanan pasien dan tiket ditutup, petugas unit kerja (<strong class="text-emerald-300">{{ ticket.room_name }}</strong>) wajib melakukan uji operasional bersama dan memberikan tanda tangan serah terima.
              </p>
            </div>

            <!-- Tombol Aksi Verifikasi untuk yang Berhak -->
            <div v-if="canVerify" class="flex flex-col sm:flex-row md:flex-col gap-2.5 shrink-0">
              <button 
                @click="showSignatureModal = true"
                class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-extrabold rounded-xl shadow-lg shadow-emerald-950/40 transition-all active:scale-95 flex items-center justify-center gap-2 cursor-pointer"
              >
                <PenTool class="w-4 h-4" />
                <span>Uji Normal & Tanda Tangan</span>
              </button>

              <button 
                @click="showRejectModal = true"
                class="px-5 py-2.5 bg-rose-600/90 hover:bg-rose-600 text-white text-xs font-bold rounded-xl transition-all active:scale-95 flex items-center justify-center gap-2 cursor-pointer shadow-sm"
              >
                <XCircle class="w-4 h-4" />
                <span>Alat Belum Normal (Tolak)</span>
              </button>
            </div>

            <!-- Notice jika user bukan unit ruangan terkait -->
            <div v-else class="p-4 bg-white/10 backdrop-blur-sm rounded-xl border border-white/15 text-xs text-indigo-100 max-w-xs shrink-0">
              <div class="font-bold text-white flex items-center gap-1.5 mb-1">
                <ShieldAlert class="w-4 h-4 text-amber-300" />
                Menunggu Validasi Unit
              </div>
              <div class="text-[11px] text-indigo-200">
                Persetujuan serah terima dikhususkan bagi petugas ruangan <strong>{{ ticket.room_name }}</strong> atau Administrator IPSRS.
              </div>
            </div>
          </div>
        </div>

        <!-- Top Overview Box -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-4">
          <div>
            <div class="flex items-center gap-2">
              <span class="font-mono text-base font-extrabold text-slate-900">{{ ticket.ticket_number }}</span>
              <span 
                :class="[
                  'px-2 py-0.5 rounded text-[10px] font-bold uppercase',
                  ticket.priority === 'emergency' ? 'bg-rose-100 text-rose-700' :
                  ticket.priority === 'high' ? 'bg-orange-100 text-orange-700' :
                  ticket.priority === 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600'
                ]"
              >
                {{ ticket.priority }}
              </span>
            </div>
            <div class="text-xs text-slate-500 mt-1">
              Dilaporkan: {{ ticket.reported_at }} oleh <strong class="text-slate-700">{{ ticket.reported_by_name }}</strong> ({{ ticket.room_name }})
            </div>
          </div>

          <div>
            <span 
              :class="[
                'px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wide',
                ticket.status === 'closed' ? 'bg-emerald-100 text-emerald-700' :
                ticket.status === 'in_progress' ? 'bg-blue-100 text-blue-700' :
                ticket.status === 'completed_technician' ? 'bg-purple-100 text-purple-700' :
                ticket.status === 'waiting_parts' ? 'bg-amber-100 text-amber-700' : 'bg-slate-200 text-slate-700'
              ]"
            >
              {{ formatStatus(ticket.status) }}
            </span>
          </div>
        </div>

        <!-- Issue Details & Equipment Link -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-2">
          <div class="md:col-span-2 space-y-3">
            <div>
              <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Deskripsi Kendala Kerusakan</div>
              <div class="text-sm text-slate-800 font-medium bg-slate-50 p-4 rounded-xl border border-slate-200/60 mt-1">
                {{ ticket.issue_description }}
              </div>
            </div>

            <!-- Issue Photo if any -->
            <div v-if="ticket.issue_photo_path" class="pt-2">
              <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Foto Kondisi Alat Saat Dilaporkan</div>
              <a :href="getUploadUrl(ticket.issue_photo_path)" target="_blank" class="inline-block border border-slate-200 rounded-xl overflow-hidden hover:opacity-90 transition-opacity">
                <img :src="getUploadUrl(ticket.issue_photo_path)" class="max-h-48 object-cover rounded-xl" />
              </a>
            </div>

            <!-- Signature Display if Closed -->
            <div v-if="ticket.room_signature_path" class="pt-2 p-4 bg-emerald-50/60 border border-emerald-200/80 rounded-xl">
              <div class="text-xs font-bold text-emerald-800 flex items-center gap-1.5 mb-1">
                <CheckCircle2 class="w-4 h-4 text-emerald-600" />
                Validasi Serah Terima Ruangan Resmi
              </div>
              <div class="text-[11px] text-slate-600">Diverifikasi oleh: {{ ticket.verifier_name || ticket.reported_by_name }} ({{ ticket.verified_at }})</div>
              <img :src="getUploadUrl(ticket.room_signature_path)" class="h-16 bg-white p-1 rounded-lg border border-emerald-200 mt-2" />
            </div>
          </div>

          <!-- Equipment Sidebar Info -->
          <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80 space-y-3 text-xs">
            <div class="font-bold text-slate-800 text-sm flex items-center justify-between">
              <span>Info Alat Medis</span>
              <router-link :to="`/equipment/${ticket.equipment_id}`" class="text-[11px] text-emerald-600 font-bold hover:underline">
                Lihat Alat &rarr;
              </router-link>
            </div>

            <div>
              <div class="text-slate-400">Nama Alat</div>
              <div class="font-bold text-slate-800">{{ ticket.equipment_name }}</div>
            </div>

            <div>
              <div class="text-slate-400">Kode Aset / Serial</div>
              <div class="font-mono font-bold text-slate-800">{{ ticket.asset_code }}</div>
              <div class="text-[10px] text-slate-400">SN: {{ ticket.serial_number }}</div>
            </div>

            <div>
              <div class="text-slate-400">Teknisi Penanggungjawab</div>
              <div class="font-bold text-slate-800 flex items-center gap-1 mt-0.5">
                <UserCheck class="w-3.5 h-3.5 text-emerald-600" />
                {{ ticket.technician_name || 'Belum ditugaskan' }}
              </div>
              <div v-if="ticket.technician_phone" class="text-[10px] text-slate-400">{{ ticket.technician_phone }}</div>
            </div>

            <div v-if="ticket.response_at">
              <div class="text-slate-400">Waktu Respon Awal</div>
              <div class="font-medium text-slate-700">{{ ticket.response_at }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Parts Used Table -->
      <div v-if="ticket.parts && ticket.parts.length > 0" class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-3">
        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
          <Boxes class="w-4 h-4 text-emerald-600" />
          Suku Cadang yang Diganti
        </h3>
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
            <tr>
              <th class="py-2 px-3">Part Number</th>
              <th class="py-2 px-3">Nama Sparepart</th>
              <th class="py-2 px-3 text-center">Jumlah</th>
              <th class="py-2 px-3 text-right">Biaya Satuan</th>
              <th class="py-2 px-3 text-right">Subtotal</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="p in ticket.parts" :key="p.id">
              <td class="py-2 px-3 font-mono">{{ p.part_number }}</td>
              <td class="py-2 px-3 font-medium text-slate-800">{{ p.part_name }}</td>
              <td class="py-2 px-3 text-center font-bold">{{ p.quantity }}</td>
              <td class="py-2 px-3 text-right">Rp {{ Number(p.unit_cost).toLocaleString('id-ID') }}</td>
              <td class="py-2 px-3 text-right font-bold">Rp {{ Number(p.total_cost).toLocaleString('id-ID') }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pengerjaan Timeline / Logs -->
      <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
          <Clock class="w-4 h-4 text-emerald-600" />
          Riwayat Tindakan Teknisi (Timeline Log)
        </h3>

        <div class="space-y-4 pl-4 border-l-2 border-emerald-500 ml-2">
          <div v-for="log in ticket.logs" :key="log.id" class="relative pl-6">
            <div class="absolute -left-[23px] top-1 w-3.5 h-3.5 rounded-full bg-emerald-500 border-2 border-white ring-2 ring-emerald-200"></div>
            <div>
              <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-800">{{ log.technician_name || 'Petugas' }}</span>
                <span class="text-[10px] text-slate-400 font-medium">{{ log.logged_at }}</span>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-700 uppercase">
                  {{ formatStatus(log.current_status) }}
                </span>
              </div>
              <div class="text-xs text-slate-600 mt-1">{{ log.action_taken }}</div>
              <div v-if="log.photo_progress_path" class="mt-2">
                <a :href="getUploadUrl(log.photo_progress_path)" target="_blank">
                  <img :src="getUploadUrl(log.photo_progress_path)" class="h-28 rounded-lg border border-slate-200 object-cover" />
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
      </div>
    </template>

    <!-- Modal Update Progress -->
    <div v-if="showProgressModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="font-bold text-sm text-slate-800">Catat Tindakan Teknisi</h3>
          <button @click="showProgressModal = false" class="text-slate-400 hover:text-slate-800"><X class="w-5 h-5" /></button>
        </div>

        <form @submit.prevent="submitProgress" class="space-y-3">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Ubah Status Tiket</label>
            <SearchableSelect 
              v-model="progressForm.status"
              :options="[
                { id: 'in_progress', name: 'Sedang Dikerjakan (In Progress)' },
                { id: 'waiting_parts', name: 'Menunggu Suku Cadang (Waiting Parts)' },
                { id: 'vendor_repair', name: 'Rujuk ke Vendor / Bengkel Luar' },
                { id: 'completed_technician', name: 'Pengerjaan Selesai (Siap Uji Fungsi)' }
              ]"
              value-key="id"
              label-key="name"
              placeholder="Pilih status progress..."
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Tindakan / Pekerjaan yang Dilakukan *</label>
            <textarea 
              v-model="progressForm.action_taken" 
              required 
              rows="3" 
              placeholder="Jelaskan tindakan perbaikan, penggantian sekring, pembersihan komponen, dll..."
              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"
            ></textarea>
          </div>

          <div v-if="progressForm.status === 'vendor_repair'">
            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Vendor Pihak Ketiga</label>
            <input v-model="progressForm.vendor_name" type="text" placeholder="PT Global Servis Medika" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Foto Bukti Pengerjaan (Opsional)</label>
            <input type="file" accept="image/*" @change="handleProgressPhotoChange" class="w-full text-xs text-slate-500" />
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
            <button type="button" @click="showProgressModal = false" class="px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-lg cursor-pointer">Batal</button>
            <button type="submit" :disabled="progressSubmitting" class="px-4 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg disabled:opacity-50 cursor-pointer">
              {{ progressSubmitting ? 'Menyimpan...' : 'Simpan Progress' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Assign Teknisi -->
    <div v-if="showAssignModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-100 space-y-4">
        <h3 class="font-bold text-sm text-slate-800">Tugaskan Teknisi Penanggungjawab</h3>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Teknisi Elektromedis</label>
          <SearchableSelect 
            v-model="selectedTechId"
            :options="technicians"
            value-key="id"
            label-key="full_name"
            placeholder="Pilih teknisi..."
            search-placeholder="Ketik nama teknisi..."
          />
        </div>
        <div class="flex items-center justify-end gap-2 pt-2">
          <button @click="showAssignModal = false" class="px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-100 rounded-lg cursor-pointer">Batal</button>
          <button @click="submitAssign" class="px-4 py-1.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg cursor-pointer">Tugaskan</button>
        </div>
      </div>
    </div>

    <!-- Modal Tambah Part -->
    <div v-if="showPartModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-100 space-y-4">
        <h3 class="font-bold text-sm text-slate-800">Alokasikan Suku Cadang</h3>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Sparepart</label>
          <SearchableSelect 
            v-model="partForm.sparepart_id"
            :options="availableParts"
            value-key="id"
            label-key="name"
            :format-sublabel="(sp) => 'Stok: ' + sp.stock_qty + ' unit • ' + (sp.part_number || '')"
            placeholder="Ketik untuk mencari suku cadang..."
            search-placeholder="Ketik nama part atau part number..."
          />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah (Qty)</label>
          <input v-model="partForm.quantity" type="number" min="1" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg" />
        </div>
        <div class="flex items-center justify-end gap-2 pt-2">
          <button @click="showPartModal = false" class="px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
          <button @click="submitAddPart" class="px-4 py-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg">Pasang Part</button>
        </div>
      </div>
    </div>

    <!-- Digital Signature Modal Component -->
    <DigitalSignatureModal 
      :is-open="showSignatureModal"
      @close="showSignatureModal = false"
      @confirm="handleSignatureConfirmed"
    />

    <!-- Modal Penolakan Hasil Perbaikan / Uji Coba Fungsi Belum Sesuai -->
    <div v-if="showRejectModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2 text-rose-600">
            <XCircle class="w-5 h-5" />
            <h3 class="font-bold text-sm text-slate-900">Tolak Hasil Uji Coba / Belum Normal</h3>
          </div>
          <button @click="showRejectModal = false" class="text-slate-400 hover:text-slate-800"><X class="w-5 h-5" /></button>
        </div>

        <p class="text-xs text-slate-600 leading-relaxed">
          Sebutkan kendala atau fungsi alat yang masih bermasalah saat pengujian. Tiket akan dikembalikan ke status <strong>Dalam Pengerjaan</strong> agar teknisi dapat menindaklanjutinya.
        </p>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Kendala yang Masih Ditemukan *</label>
          <textarea 
            v-model="rejectNotes" 
            rows="3" 
            placeholder="Contoh: Lampu indikator masih kedip merah dan suhu belum stabil..."
            class="w-full px-3 py-2 text-xs border border-rose-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-rose-500 bg-rose-50/20"
          ></textarea>
        </div>

        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
          <button type="button" @click="showRejectModal = false" class="px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-lg cursor-pointer">
            Batal
          </button>
          <button 
            type="button"
            @click="submitReject" 
            :disabled="rejectSubmitting || !rejectNotes.trim()" 
            class="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-lg disabled:opacity-50 cursor-pointer flex items-center gap-1.5 shadow-sm"
          >
            <span v-if="rejectSubmitting">Mengembalikan...</span>
            <span v-else>Kembalikan ke Teknisi</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { 
  ArrowLeft, Wrench, Plus, PenTool, CheckCircle2, 
  UserCheck, Boxes, Clock, X, Printer,
  AlertTriangle, CheckSquare, XCircle, ShieldAlert 
} from 'lucide-vue-next';
import { useAuthStore } from '../../stores/authStore';
import { useSettingStore } from '../../stores/settingStore';
import { useNotificationStore } from '../../stores/notificationStore';
import axiosClient, { getUploadUrl } from '../../api/axiosClient';
import DigitalSignatureModal from '../../components/DigitalSignatureModal.vue';
import SearchableSelect from '../../components/SearchableSelect.vue';
import { validateFile } from '../../utils/fileValidation';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const settingStore = useSettingStore();
const notifStore = useNotificationStore();

const ticket = ref(null);
const loading = ref(true);

const showRejectModal = ref(false);
const rejectNotes = ref('');
const rejectSubmitting = ref(false);

const canVerify = computed(() => {
  if (!ticket.value) return false;
  if (authStore.isAdmin) return true;
  if (authStore.isRuangan && authStore.user?.room_id == ticket.value.room_id) return true;
  return false;
});

const printWorkOrder = () => {
  window.print();
};

const showProgressModal = ref(false);
const progressSubmitting = ref(false);
const progressPhotoFile = ref(null);
const progressForm = ref({
  status: 'in_progress',
  action_taken: '',
  vendor_name: ''
});

const showAssignModal = ref(false);
const technicians = ref([]);
const selectedTechId = ref('');

const showPartModal = ref(false);
const availableParts = ref([]);
const partForm = ref({
  sparepart_id: '',
  quantity: 1
});

const showSignatureModal = ref(false);

const fetchTicket = async () => {
  const ticketId = route.params.id;
  if (!ticketId || ticketId === 'undefined') {
    loading.value = false;
    router.replace('/tickets');
    return;
  }

  loading.value = true;
  try {
    const res = await axiosClient.get(`/work-orders/${ticketId}`);
    if (res.success && res.data) {
      ticket.value = res.data;
      progressForm.value.status = ticket.value.status;
    }
  } catch (err) {
    console.error('Error loading ticket:', err);
  } finally {
    loading.value = false;
  }
};

const handleProgressPhotoChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    const validation = validateFile(file, 'image_gif');
    if (!validation.valid) {
      alert(validation.error);
      e.target.value = '';
      progressPhotoFile.value = null;
      return;
    }
    progressPhotoFile.value = file;
  }
};

const submitProgress = async () => {
  progressSubmitting.value = true;
  try {
    const formData = new FormData();
    formData.append('status', progressForm.value.status);
    formData.append('action_taken', progressForm.value.action_taken);
    if (progressForm.value.vendor_name) formData.append('vendor_name', progressForm.value.vendor_name);
    if (progressPhotoFile.value) formData.append('progress_photo', progressPhotoFile.value);

    const res = await axiosClient.post(`/work-orders/progress/${ticket.value.id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    if (res.success) {
      showProgressModal.value = false;
      progressForm.value.action_taken = '';
      progressPhotoFile.value = null;
      fetchTicket();
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menyimpan progress.');
  } finally {
    progressSubmitting.value = false;
  }
};

const openAssignModal = async () => {
  try {
    const res = await axiosClient.get('/users/technicians');
    if (res.success) {
      technicians.value = res.data;
      selectedTechId.value = technicians.value[0]?.id || '';
      showAssignModal.value = true;
    }
  } catch (e) {}
};

const submitAssign = async () => {
  try {
    const res = await axiosClient.post(`/work-orders/assign/${ticket.value.id}`, {
      technician_id: selectedTechId.value
    });
    if (res.success) {
      showAssignModal.value = false;
      fetchTicket();
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal mendisposisikan tiket.');
  }
};

const openPartModal = async () => {
  try {
    const res = await axiosClient.get('/spareparts');
    if (res.success) {
      availableParts.value = res.data;
      partForm.value.sparepart_id = availableParts.value[0]?.id || '';
      partForm.value.quantity = 1;
      showPartModal.value = true;
    }
  } catch (e) {}
};

const submitAddPart = async () => {
  try {
    const res = await axiosClient.post(`/work-orders/add-part/${ticket.value.id}`, partForm.value);
    if (res.success) {
      showPartModal.value = false;
      fetchTicket();
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menambahkan sparepart.');
  }
};

const handleSignatureConfirmed = async ({ signatureData, notes }) => {
  try {
    const res = await axiosClient.post(`/work-orders/verify/${ticket.value.id}`, {
      is_accepted: true,
      signature_data: signatureData,
      notes: notes
    });
    if (res.success) {
      showSignatureModal.value = false;
      notifStore.fetchSummary();
      fetchTicket();
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal memverifikasi tiket.');
  }
};

const submitReject = async () => {
  if (!rejectNotes.value.trim()) {
    alert('Silakan tuliskan catatan kendala yang masih ditemukan.');
    return;
  }
  rejectSubmitting.value = true;
  try {
    const res = await axiosClient.post(`/work-orders/verify/${ticket.value.id}`, {
      is_accepted: false,
      notes: rejectNotes.value.trim()
    });
    if (res.success) {
      showRejectModal.value = false;
      rejectNotes.value = '';
      notifStore.fetchSummary();
      fetchTicket();
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal mengembalikan tiket ke teknisi.');
  } finally {
    rejectSubmitting.value = false;
  }
};

const formatStatus = (s) => {
  const map = {
    'reported': 'Laporan Masuk',
    'in_progress': 'Dikerjakan',
    'waiting_parts': 'Tunggu Part',
    'vendor_repair': 'Servis Vendor',
    'completed_technician': 'Uji Fungsi',
    'closed': 'Selesai / Closed',
    'cancelled': 'Dibatalkan'
  };
  return map[s] || s;
};

onMounted(() => {
  fetchTicket();
});
</script>
