<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl font-black text-slate-800 tracking-tight flex items-center gap-2">
          <CalendarCheck class="w-6 h-6 text-emerald-600" />
          Pemeliharaan Preventif (Preventive Maintenance)
        </h1>
        <p class="text-xs text-slate-500 mt-0.5">
          Standar Kemenkes RI / MFK 8: Pemantauan fungsi fisik, tindakan preventif, uji keselamatan listrik & lembar kerja resmi.
        </p>
      </div>

      <button 
        v-if="authStore.isAdmin || authStore.isTeknisi"
        @click="openAddScheduleModal"
        class="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-sm transition-all active:scale-95 cursor-pointer"
      >
        <Plus class="w-4 h-4" />
        <span>Buat Jadwal Baru</span>
      </button>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
        <button 
          v-for="st in [
            { key: '', label: 'Semua Jadwal' },
            { key: 'pending', label: 'Terjadwal' },
            { key: 'overdue', label: 'Terlambat (Overdue)' },
            { key: 'done', label: 'Selesai' }
          ]"
          :key="st.key"
          @click="changeStatusFilter(st.key)"
          :class="[
            'px-3.5 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer',
            statusFilter === st.key ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
          ]"
        >
          {{ st.label }}
        </button>
      </div>

      <div class="relative w-full sm:w-72 shrink-0">
        <Search class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
        <input 
          v-model="searchQuery"
          @input="onSearchInput"
          type="text" 
          placeholder="Cari alat medis, kode, ruangan..."
          class="w-full pl-9 pr-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"
        />
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
      <div v-if="loading" class="py-12 text-center text-slate-400 text-xs">
        <div class="w-5 h-5 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
        Memuat jadwal preventif...
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
            <tr>
              <th class="py-3 px-3 font-semibold text-center w-12">No.</th>
              <th class="py-3 px-4 font-semibold">Tanggal Jadwal</th>
              <th class="py-3 px-4 font-semibold">Alat Medis</th>
              <th class="py-3 px-4 font-semibold">Ruangan</th>
              <th class="py-3 px-4 font-semibold">Frekuensi</th>
              <th class="py-3 px-4 font-semibold">Status & Kondisi</th>
              <th class="py-3 px-4 font-semibold">Teknisi / Pelaksana</th>
              <th class="py-3 px-4 font-semibold text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="(sch, index) in schedules" :key="sch.id" class="hover:bg-slate-50/80 transition-colors">
              <!-- No Urut -->
              <td class="py-3 px-3 text-center text-slate-400 font-medium">
                {{ (pagination.page - 1) * pagination.limit + index + 1 }}
              </td>

              <!-- Tanggal Jadwal -->
              <td class="py-3 px-4 font-bold text-slate-800 whitespace-nowrap">
                <div>{{ sch.scheduled_date }}</div>
                <div v-if="sch.completed_at" class="text-[10px] text-slate-400 font-normal">
                  Selesai: {{ sch.completed_at.substring(0, 10) }}
                </div>
              </td>

              <!-- Alat Medis -->
              <td class="py-3 px-4">
                <div class="font-bold text-slate-800">{{ sch.equipment_name }}</div>
                <div class="text-[10px] text-slate-400 font-mono">
                  {{ sch.asset_code }} • {{ sch.brand || sch.equipment_brand || '-' }}
                </div>
              </td>

              <!-- Ruangan -->
              <td class="py-3 px-4 text-slate-600">{{ sch.room_name }}</td>

              <!-- Frekuensi -->
              <td class="py-3 px-4">
                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-semibold text-[11px] uppercase">
                  {{ formatFrequency(sch.frequency) }}
                </span>
              </td>

              <!-- Status & Kondisi -->
              <td class="py-3 px-4 space-y-1">
                <div>
                  <span 
                    :class="[
                      'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase inline-flex items-center gap-1',
                      sch.status === 'done' ? 'bg-emerald-100 text-emerald-700' :
                      sch.status === 'overdue' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700'
                    ]"
                  >
                    <span class="w-1.5 h-1.5 rounded-full" :class="sch.status === 'done' ? 'bg-emerald-500' : sch.status === 'overdue' ? 'bg-rose-500' : 'bg-amber-500'"></span>
                    {{ sch.status === 'done' ? 'Selesai' : sch.status === 'overdue' ? 'Terlambat' : 'Terjadwal' }}
                  </span>
                </div>
                <!-- Kondisi Akhir jika done -->
                <div v-if="sch.status === 'done' && sch.final_condition">
                  <span 
                    :class="[
                      'px-2 py-0.5 rounded text-[9.5px] font-bold uppercase tracking-wider',
                      sch.final_condition === 'laik_pakai' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' :
                      sch.final_condition === 'rusak_ringan' ? 'bg-amber-50 text-amber-800 border border-amber-200' :
                      'bg-rose-50 text-rose-800 border border-rose-200'
                    ]"
                  >
                    {{ formatCondition(sch.final_condition) }}
                  </span>
                </div>
              </td>

              <!-- Teknisi Pelaksana -->
              <td class="py-3 px-4 text-slate-700">
                <div class="font-medium">{{ sch.technician_name || '-' }}</div>
                <div v-if="sch.executor_type" class="text-[10px] text-slate-400 capitalize">
                  {{ sch.executor_type === 'external' ? 'Vendor Luar' : 'IPSRS Internal' }}
                </div>
              </td>

              <!-- Aksi -->
              <td class="py-3 px-4 text-right space-x-1.5 whitespace-nowrap">
                <!-- Jika Selesai: Tombol Cetak LK & Lihat Detail -->
                <template v-if="sch.status === 'done'">
                  <button 
                    @click="handlePrintLK(sch)"
                    class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg text-xs transition-colors inline-flex items-center gap-1 cursor-pointer"
                    title="Cetak Formulir Lembar Kerja PM (PDF Standar MFK 8)"
                  >
                    <Printer class="w-3.5 h-3.5 text-slate-600" />
                    <span>Cetak LK</span>
                  </button>

                  <button 
                    @click="openDetailModal(sch)"
                    class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold rounded-lg text-xs transition-colors inline-flex items-center gap-1 cursor-pointer"
                    title="Lihat Detail Lembar Kerja"
                  >
                    <Eye class="w-3.5 h-3.5 text-emerald-600" />
                    <span>Detail</span>
                  </button>
                </template>

                <!-- Eksekusi PM (Jika belum selesai) -->
                <button 
                  v-if="sch.status !== 'done' && (authStore.isAdmin || authStore.isTeknisi)"
                  @click="openExecuteModal(sch)"
                  class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs transition-colors shadow-sm inline-flex items-center gap-1 cursor-pointer"
                  title="Eksekusi Formulir PM & Pemantauan Fungsi"
                >
                  <CheckSquare class="w-3.5 h-3.5" />
                  <span>Eksekusi</span>
                </button>

                <!-- Edit Jadwal (Jika belum selesai) -->
                <button 
                  v-if="sch.status !== 'done' && (authStore.isAdmin || authStore.isTeknisi)"
                  @click="openEditModal(sch)"
                  class="px-2.5 py-1 text-amber-700 hover:text-amber-800 bg-amber-50 hover:bg-amber-100 rounded-lg transition-colors inline-flex items-center gap-1 font-semibold cursor-pointer"
                  title="Edit Jadwal Preventif"
                >
                  <Pencil class="w-3.5 h-3.5" />
                  <span>Edit</span>
                </button>

                <!-- Hapus (Proteksi status done) -->
                <template v-if="authStore.isAdmin || authStore.isTeknisi">
                  <span 
                    v-if="sch.status === 'done'"
                    class="inline-block"
                    title="Jadwal yang sudah selesai (Done) tidak dapat dihapus demi kepatuhan audit riwayat alkes"
                  >
                    <button 
                      disabled
                      class="px-2.5 py-1 text-slate-400 bg-slate-100 rounded-lg cursor-not-allowed inline-flex items-center gap-1 font-semibold opacity-60"
                    >
                      <Lock class="w-3.5 h-3.5" />
                      <span>Hapus</span>
                    </button>
                  </span>
                  <button 
                    v-else
                    @click="openDeleteModal(sch)"
                    class="px-2.5 py-1 text-rose-700 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 rounded-lg transition-colors inline-flex items-center gap-1 font-semibold cursor-pointer"
                    title="Hapus Jadwal Preventif"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                    <span>Hapus</span>
                  </button>
                </template>
              </td>
            </tr>

            <tr v-if="schedules.length === 0">
              <td colspan="8" class="py-8 text-center text-slate-400">Tidak ada jadwal pemeliharaan preventif yang ditemukan.</td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination & Limit Footer -->
        <div class="px-5 py-3.5 bg-slate-50/80 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
          <div class="flex items-center flex-wrap gap-4 text-slate-600">
            <div class="flex items-center gap-2">
              <span class="text-[11px] font-medium text-slate-500">Baris per halaman:</span>
              <select 
                :value="pagination.limit" 
                @change="changeLimit($event.target.value)"
                class="px-2 py-1 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 cursor-pointer"
              >
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
            </div>
            <span class="text-slate-300 hidden sm:inline">|</span>
            <div class="text-[11px] text-slate-500">
              <span v-if="pagination.total > 0">
                Menampilkan <strong class="font-bold text-slate-800">{{ (pagination.page - 1) * pagination.limit + 1 }}</strong> - <strong class="font-bold text-slate-800">{{ Math.min(pagination.page * pagination.limit, pagination.total) }}</strong> dari <strong class="font-bold text-slate-800">{{ pagination.total }}</strong> jadwal
              </span>
              <span v-else>Total: 0 jadwal</span>
            </div>
          </div>

          <!-- Pagination Page Buttons -->
          <div class="flex items-center gap-1.5">
            <button 
              @click="goToPage(pagination.page - 1)" 
              :disabled="pagination.page <= 1 || loading"
              class="px-2.5 py-1 rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed font-medium transition-colors flex items-center gap-1 cursor-pointer"
            >
              <ChevronLeft class="w-3.5 h-3.5" />
              <span>Sebelumnya</span>
            </button>

            <div class="flex items-center gap-1">
              <button 
                v-for="p in visiblePages" 
                :key="p"
                @click="p !== '...' && goToPage(p)"
                :disabled="p === '...' || p === pagination.page"
                :class="[
                  'min-w-[28px] h-7 text-xs font-bold rounded-lg transition-colors flex items-center justify-center',
                  p === pagination.page ? 'bg-emerald-600 text-white shadow-sm' : 
                  p === '...' ? 'text-slate-400 cursor-default px-1' :
                  'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100 cursor-pointer'
                ]"
              >
                {{ p }}
              </button>
            </div>

            <button 
              @click="goToPage(pagination.page + 1)" 
              :disabled="pagination.page >= pagination.total_pages || loading"
              class="px-2.5 py-1 rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed font-medium transition-colors flex items-center gap-1 cursor-pointer"
            >
              <span>Selanjutnya</span>
              <ChevronRight class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODAL 1: BUAT JADWAL PREVENTIF BARU                          -->
    <!-- ============================================================ -->
    <div v-if="showAddModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="font-bold text-sm text-slate-800 flex items-center gap-2">
            <CalendarCheck class="w-4 h-4 text-emerald-600" />
            Buat Jadwal Pemeliharaan Preventif
          </h3>
          <button @click="showAddModal = false" class="text-slate-400 hover:text-slate-800 cursor-pointer"><X class="w-5 h-5" /></button>
        </div>

        <form @submit.prevent="submitAddSchedule" class="space-y-3">
          <div v-if="scheduleError" class="p-2.5 bg-rose-50 text-rose-700 text-xs rounded-lg border border-rose-200">
            {{ scheduleError }}
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">
              Pilih Alat Medis *
              <span v-if="authStore.user?.role === 'ruangan'" class="text-[10px] text-emerald-600 font-normal">
                (Unit {{ authStore.user.room_name || 'Login' }})
              </span>
            </label>
            <SearchableSelect 
              v-model="newScheduleForm.equipment_id"
              :options="mappedEquipmentList"
              value-key="id"
              :format-label="(eq) => `${eq.asset_code} - ${eq.name}`"
              :format-sublabel="(eq) => `${eq.room_name} • SN: ${eq.serial_number || '-'}`"
              placeholder="Ketik untuk mencari alat medis..."
              search-placeholder="Ketik nama alat, kode aset, atau ruangan..."
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Jadwal *</label>
              <input v-model="newScheduleForm.scheduled_date" type="date" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Frekuensi Siklus *</label>
              <SearchableSelect 
                v-model="newScheduleForm.frequency"
                :options="frequencyOptions"
                value-key="id"
                label-key="name"
                placeholder="Pilih siklus..."
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Khusus / Instruksi Kerja</label>
            <textarea v-model="newScheduleForm.notes" rows="2" placeholder="Cek berkala kelistrikan dan filter..." class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
            <button type="button" @click="showAddModal = false" class="px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-100 rounded-lg cursor-pointer">Batal</button>
            <button type="submit" :disabled="savingSchedule" class="px-4 py-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm disabled:opacity-50 cursor-pointer">
              {{ savingSchedule ? 'Menyimpan...' : 'Jadwalkan' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODAL 2: EDIT JADWAL PREVENTIF                               -->
    <!-- ============================================================ -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h3 class="font-bold text-sm text-slate-800">Edit Jadwal Pemeliharaan Preventif</h3>
            <div class="text-[11px] text-slate-500">{{ editingSchedule?.equipment_name }} ({{ editingSchedule?.asset_code }})</div>
          </div>
          <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-800 cursor-pointer"><X class="w-5 h-5" /></button>
        </div>

        <form @submit.prevent="submitEditSchedule" class="space-y-3">
          <div v-if="editError" class="p-2.5 bg-rose-50 text-rose-700 text-xs rounded-lg border border-rose-200">
            {{ editError }}
          </div>

          <div v-if="editingSchedule?.status !== 'done'">
            <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Jadwal *</label>
            <input v-model="editForm.scheduled_date" type="date" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" />
          </div>

          <div v-if="editingSchedule?.status !== 'done'">
            <label class="block text-xs font-semibold text-slate-700 mb-1">Frekuensi Siklus *</label>
            <SearchableSelect 
              v-model="editForm.frequency"
              :options="frequencyOptions"
              value-key="id"
              label-key="name"
              placeholder="Pilih siklus..."
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Khusus / Instruksi Kerja</label>
            <textarea v-model="editForm.notes" rows="3" placeholder="Catatan inspeksi atau instruksi..." class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
            <button type="button" @click="showEditModal = false" class="px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-100 rounded-lg cursor-pointer">Batal</button>
            <button type="submit" :disabled="savingEdit" class="px-4 py-1.5 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-lg shadow-sm disabled:opacity-50 cursor-pointer">
              {{ savingEdit ? 'Memperbarui...' : 'Simpan Perubahan' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODAL 3: KONFIRMASI HAPUS                                    -->
    <!-- ============================================================ -->
    <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-100 space-y-4">
        <div class="flex items-center gap-3 text-rose-600">
          <div class="w-10 h-10 rounded-full bg-rose-100 flex items-center justify-center shrink-0">
            <Trash2 class="w-5 h-5" />
          </div>
          <div>
            <h3 class="font-bold text-sm text-slate-800">Hapus Jadwal Preventif</h3>
            <p class="text-xs text-slate-500">Tindakan ini tidak dapat dibatalkan.</p>
          </div>
        </div>

        <p class="text-xs text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-200">
          Apakah Anda yakin ingin menghapus jadwal pemeliharaan alat <strong class="text-slate-800">{{ deletingSchedule?.equipment_name }}</strong> pada tanggal <strong class="text-slate-800">{{ deletingSchedule?.scheduled_date }}</strong>?
        </p>

        <div v-if="deleteError" class="p-2.5 bg-rose-50 text-rose-700 text-xs rounded-lg border border-rose-200">
          {{ deleteError }}
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
          <button @click="showDeleteModal = false" class="px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-100 rounded-lg cursor-pointer">Batal</button>
          <button @click="confirmDeleteSchedule" :disabled="deleting" class="px-4 py-1.5 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-sm disabled:opacity-50 cursor-pointer">
            {{ deleting ? 'Menghapus...' : 'Ya, Hapus Jadwal' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODAL 4: EKSEKUSI FORMULIR LK-IPSRS (STANDAR KEMENKES / MFK 8) -->
    <!-- ============================================================ -->
    <div v-if="selectedSchedule" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
      <div class="bg-white rounded-2xl max-w-4xl w-full max-h-[92vh] flex flex-col shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        
        <!-- Modal Top Bar -->
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between shrink-0">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
              <ShieldCheck class="w-5 h-5" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h3 class="font-bold text-sm tracking-tight text-white">Lembar Kerja Pemeliharaan Preventif & Pemantauan Fungsi</h3>
                <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 text-[10px] font-bold border border-emerald-500/30">
                  MFK 8 Kemenkes
                </span>
              </div>
              <p class="text-xs text-slate-300">
                {{ selectedSchedule.equipment_name }} • <span class="font-mono text-emerald-400">{{ selectedSchedule.asset_code }}</span> • {{ selectedSchedule.room_name }}
              </p>
            </div>
          </div>
          <button @click="selectedSchedule = null" class="text-slate-400 hover:text-white transition-colors cursor-pointer p-1 rounded-lg hover:bg-slate-800">
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Scrollable Form Body -->
        <div class="p-6 overflow-y-auto space-y-6 flex-1 text-xs">
          
          <!-- Box 1: Informasi Pelaksanaan & Administrasi -->
          <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200/80 space-y-3">
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
              <span class="font-bold text-slate-800 text-xs flex items-center gap-1.5">
                <Clock class="w-3.5 h-3.5 text-emerald-600" />
                Data Pelaksanaan & Surat Perintah Kerja (SPK)
              </span>
              <span class="text-[11px] text-slate-500">Jadwal: {{ selectedSchedule.scheduled_date }}</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
              <div>
                <label class="block font-semibold text-slate-700 mb-1">Mulai Pelaksanaan *</label>
                <input 
                  v-model="executeForm.execution_start_at" 
                  type="datetime-local" 
                  required 
                  class="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" 
                />
              </div>

              <div>
                <label class="block font-semibold text-slate-700 mb-1">Selesai Pelaksanaan *</label>
                <input 
                  v-model="executeForm.execution_end_at" 
                  type="datetime-local" 
                  required 
                  class="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" 
                />
              </div>

              <div>
                <label class="block font-semibold text-slate-700 mb-1">No. SPK / Surat Tugas</label>
                <input 
                  v-model="executeForm.sp_number" 
                  type="text" 
                  placeholder="Contoh: SPK-PM-2026-001" 
                  class="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 font-mono" 
                />
              </div>

              <div>
                <label class="block font-semibold text-slate-700 mb-1">Pelaksana Pemeliharaan</label>
                <div class="grid grid-cols-2 gap-1.5 pt-0.5">
                  <button 
                    type="button" 
                    @click="executeForm.executor_type = 'internal'"
                    :class="[
                      'py-1.5 px-2 text-center rounded-lg font-bold border transition-all cursor-pointer',
                      executeForm.executor_type === 'internal' ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-100'
                    ]"
                  >
                    Internal IPSRS
                  </button>
                  <button 
                    type="button" 
                    @click="executeForm.executor_type = 'external'"
                    :class="[
                      'py-1.5 px-2 text-center rounded-lg font-bold border transition-all cursor-pointer',
                      executeForm.executor_type === 'external' ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-100'
                    ]"
                  >
                    Eksternal
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Section 1: Pemantauan Fungsi (8 Item Standar Fisik & Fungsi) -->
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <h4 class="font-bold text-slate-800 text-xs flex items-center gap-2">
                <span class="w-5 h-5 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] font-bold">1</span>
                Bagian I: Pemantauan Fungsi & Kondisi Fisik Alat (8 Parameter)
              </h4>
              <button 
                type="button"
                @click="setAllInspection('baik')"
                class="text-[11px] text-emerald-600 hover:text-emerald-700 font-semibold cursor-pointer underline"
              >
                Set Semua Baik
              </button>
            </div>

            <div class="border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
              <table class="w-full text-left">
                <thead class="bg-slate-100/90 text-slate-600 border-b border-slate-200 text-[11px]">
                  <tr>
                    <th class="py-2.5 px-3 w-10 text-center">No</th>
                    <th class="py-2.5 px-3">Komponen / Bagian Yang Diperiksa</th>
                    <th class="py-2.5 px-3 text-center w-52">Kondisi Hasil Pemantauan</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="(item, idx) in inspectionItems" :key="item.key" class="hover:bg-slate-50/60">
                    <td class="py-2 px-3 text-center text-slate-400 font-medium">{{ idx + 1 }}</td>
                    <td class="py-2 px-3">
                      <div class="font-bold text-slate-800">{{ item.label }}</div>
                      <div class="text-[10px] text-slate-400">{{ item.desc }}</div>
                    </td>
                    <td class="py-2 px-3 text-center">
                      <div class="inline-flex items-center p-0.5 bg-slate-100 rounded-lg border border-slate-200 gap-1">
                        <button 
                          type="button"
                          @click="executeForm.inspection_checklist[item.key] = 'na'"
                          :class="[
                            'px-2 py-1 rounded text-[10px] font-bold uppercase transition-all cursor-pointer',
                            executeForm.inspection_checklist[item.key] === 'na' ? 'bg-slate-700 text-white shadow-xs' : 'text-slate-500 hover:text-slate-800'
                          ]"
                        >
                          N/A
                        </button>
                        <button 
                          type="button"
                          @click="executeForm.inspection_checklist[item.key] = 'baik'"
                          :class="[
                            'px-2.5 py-1 rounded text-[10px] font-bold uppercase transition-all cursor-pointer',
                            executeForm.inspection_checklist[item.key] === 'baik' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-500 hover:text-emerald-700'
                          ]"
                        >
                          Baik
                        </button>
                        <button 
                          type="button"
                          @click="executeForm.inspection_checklist[item.key] = 'rusak'"
                          :class="[
                            'px-2 py-1 rounded text-[10px] font-bold uppercase transition-all cursor-pointer',
                            executeForm.inspection_checklist[item.key] === 'rusak' ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-500 hover:text-rose-700'
                          ]"
                        >
                          Rusak
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Section 2: Tindakan Pemeliharaan Preventif (4 Kegiatan Utama) -->
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <h4 class="font-bold text-slate-800 text-xs flex items-center gap-2">
                <span class="w-5 h-5 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] font-bold">2</span>
                Bagian II: Tindakan Pemeliharaan Preventif (4 Kegiatan)
              </h4>
              <button 
                type="button"
                @click="setAllActions('ya')"
                class="text-[11px] text-emerald-600 hover:text-emerald-700 font-semibold cursor-pointer underline"
              >
                Set Semua Ya
              </button>
            </div>

            <div class="border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
              <table class="w-full text-left">
                <thead class="bg-slate-100/90 text-slate-600 border-b border-slate-200 text-[11px]">
                  <tr>
                    <th class="py-2.5 px-3 w-10 text-center">No</th>
                    <th class="py-2.5 px-3">Kegiatan Pemeliharaan Berkala</th>
                    <th class="py-2.5 px-3 text-center w-52">Realisasi Tindakan</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="(act, idx) in actionItems" :key="act.key" class="hover:bg-slate-50/60">
                    <td class="py-2 px-3 text-center text-slate-400 font-medium">{{ idx + 1 }}</td>
                    <td class="py-2 px-3">
                      <div class="font-bold text-slate-800">{{ act.label }}</div>
                      <div class="text-[10px] text-slate-400">{{ act.desc }}</div>
                    </td>
                    <td class="py-2 px-3 text-center">
                      <div class="inline-flex items-center p-0.5 bg-slate-100 rounded-lg border border-slate-200 gap-1">
                        <button 
                          type="button"
                          @click="executeForm.maintenance_actions[act.key] = 'na'"
                          :class="[
                            'px-2.5 py-1 rounded text-[10px] font-bold uppercase transition-all cursor-pointer',
                            executeForm.maintenance_actions[act.key] === 'na' ? 'bg-slate-700 text-white shadow-xs' : 'text-slate-500 hover:text-slate-800'
                          ]"
                        >
                          N/A
                        </button>
                        <button 
                          type="button"
                          @click="executeForm.maintenance_actions[act.key] = 'ya'"
                          :class="[
                            'px-3 py-1 rounded text-[10px] font-bold uppercase transition-all cursor-pointer',
                            executeForm.maintenance_actions[act.key] === 'ya' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-500 hover:text-emerald-700'
                          ]"
                        >
                          Ya
                        </button>
                        <button 
                          type="button"
                          @click="executeForm.maintenance_actions[act.key] = 'tidak'"
                          :class="[
                            'px-2 py-1 rounded text-[10px] font-bold uppercase transition-all cursor-pointer',
                            executeForm.maintenance_actions[act.key] === 'tidak' ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-500 hover:text-rose-700'
                          ]"
                        >
                          Tidak
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Section 3: Pengukuran Keselamatan Listrik (Electrical Safety) -->
          <div class="space-y-2">
            <h4 class="font-bold text-slate-800 text-xs flex items-center gap-2">
              <span class="w-5 h-5 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] font-bold">3</span>
              Bagian III: Pengukuran Keselamatan Listrik (Electrical Safety Test)
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Grounding Resistance -->
              <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                <div class="flex items-center justify-between">
                  <span class="font-bold text-slate-800 text-xs">Tahanan Pembumian (Grounding)</span>
                  <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-slate-200 text-slate-700">Standar: &le; 0.20 &Omega;</span>
                </div>
                <div class="flex items-center gap-2">
                  <div class="relative flex-1">
                    <input 
                      v-model="executeForm.electrical_safety.grounding_resistance" 
                      type="number" 
                      step="0.01" 
                      placeholder="0.12" 
                      class="w-full pl-3 pr-8 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 font-mono" 
                    />
                    <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 font-bold">&Omega;</span>
                  </div>
                  <span 
                    :class="[
                      'px-2.5 py-1.5 rounded-lg text-[10px] font-bold uppercase whitespace-nowrap',
                      parseFloat(executeForm.electrical_safety.grounding_resistance || 0) <= 0.20 
                        ? 'bg-emerald-100 text-emerald-800' 
                        : 'bg-rose-100 text-rose-800'
                    ]"
                  >
                    {{ parseFloat(executeForm.electrical_safety.grounding_resistance || 0) <= 0.20 ? 'Lolos' : 'Melebihi Batas' }}
                  </span>
                </div>
              </div>

              <!-- Leakage Current -->
              <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                <div class="flex items-center justify-between">
                  <span class="font-bold text-slate-800 text-xs">Kebocoran Arus Sasis (Chassis Leakage)</span>
                  <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-slate-200 text-slate-700">Standar: &le; 100 &mu;A</span>
                </div>
                <div class="flex items-center gap-2">
                  <div class="relative flex-1">
                    <input 
                      v-model="executeForm.electrical_safety.leakage_current" 
                      type="number" 
                      step="0.1" 
                      placeholder="45.0" 
                      class="w-full pl-3 pr-10 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 font-mono" 
                    />
                    <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 font-bold">&mu;A</span>
                  </div>
                  <span 
                    :class="[
                      'px-2.5 py-1.5 rounded-lg text-[10px] font-bold uppercase whitespace-nowrap',
                      parseFloat(executeForm.electrical_safety.leakage_current || 0) <= 100 
                        ? 'bg-emerald-100 text-emerald-800' 
                        : 'bg-rose-100 text-rose-800'
                    ]"
                  >
                    {{ parseFloat(executeForm.electrical_safety.leakage_current || 0) <= 100 ? 'Lolos' : 'Melebihi Batas' }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Section 4: Kesimpulan Kondisi Akhir & Catatan -->
          <div class="space-y-3 pt-2">
            <h4 class="font-bold text-slate-800 text-xs flex items-center gap-2">
              <span class="w-5 h-5 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] font-bold">4</span>
              Bagian IV: Evaluasi Kelayakan & Kesimpulan Kondisi Akhir
            </h4>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <label 
                :class="[
                  'p-3.5 rounded-xl border-2 flex items-start gap-3 cursor-pointer transition-all',
                  executeForm.final_condition === 'laik_pakai' 
                    ? 'border-emerald-500 bg-emerald-50/50 shadow-xs' 
                    : 'border-slate-200 bg-white hover:border-slate-300'
                ]"
              >
                <input type="radio" value="laik_pakai" v-model="executeForm.final_condition" class="mt-0.5 text-emerald-600 focus:ring-emerald-500" />
                <div>
                  <div class="font-bold text-slate-900 text-xs">Laik Pakai</div>
                  <div class="text-[10px] text-slate-500">Alat normal, fungsi baik, aman dan siap dipakai untuk pelayanan.</div>
                </div>
              </label>

              <label 
                :class="[
                  'p-3.5 rounded-xl border-2 flex items-start gap-3 cursor-pointer transition-all',
                  executeForm.final_condition === 'rusak_ringan' 
                    ? 'border-amber-500 bg-amber-50/50 shadow-xs' 
                    : 'border-slate-200 bg-white hover:border-slate-300'
                ]"
              >
                <input type="radio" value="rusak_ringan" v-model="executeForm.final_condition" class="mt-0.5 text-amber-600 focus:ring-amber-500" />
                <div>
                  <div class="font-bold text-slate-900 text-xs">Rusak Ringan</div>
                  <div class="text-[10px] text-slate-500">Ada kendala minor/aksesori perlu penggantian berkala.</div>
                </div>
              </label>

              <label 
                :class="[
                  'p-3.5 rounded-xl border-2 flex items-start gap-3 cursor-pointer transition-all',
                  executeForm.final_condition === 'rusak_berat' 
                    ? 'border-rose-500 bg-rose-50/50 shadow-xs' 
                    : 'border-slate-200 bg-white hover:border-slate-300'
                ]"
              >
                <input type="radio" value="rusak_berat" v-model="executeForm.final_condition" class="mt-0.5 text-rose-600 focus:ring-rose-500" />
                <div>
                  <div class="font-bold text-slate-900 text-xs">Rusak Berat</div>
                  <div class="text-[10px] text-slate-500">Fungsi gagal atau bahaya listrik, alat wajib dikarantina/servis besar.</div>
                </div>
              </label>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block font-semibold text-slate-700 mb-1">Nama Petugas / Teknisi Pelaksana *</label>
                <input 
                  v-model="executeForm.technician_name" 
                  type="text" 
                  required 
                  class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" 
                />
              </div>

              <div>
                <label class="block font-semibold text-slate-700 mb-1">Nama Penanggung Jawab / Pengawas Ruangan</label>
                <input 
                  v-model="executeForm.supervisor_name" 
                  type="text" 
                  placeholder="Contoh: Kepala Ruangan / Kepala IPSRS" 
                  class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" 
                />
              </div>
            </div>

            <div>
              <label class="block font-semibold text-slate-700 mb-1">Keterangan / Catatan Tindak Lanjut</label>
              <textarea 
                v-model="executeForm.notes" 
                rows="2" 
                placeholder="Semua fungsi dan kelistrikan normal. Stiker pemeliharaan telah ditempelkan..." 
                class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"
              ></textarea>
            </div>

            <!-- Upload Foto Bukti Stiker Pemeliharaan -->
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Foto Bukti Pemeliharaan / Stiker Fisik (Opsional)</label>
              <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 px-3 py-2 border border-dashed border-slate-300 hover:border-emerald-500 rounded-xl bg-slate-50 hover:bg-emerald-50/40 text-slate-600 text-xs font-semibold cursor-pointer transition-colors">
                  <Camera class="w-4 h-4 text-emerald-600" />
                  <span>{{ photoFileName ? 'Ganti Foto Bukti' : 'Ambil / Pilih Foto Stiker' }}</span>
                  <input type="file" accept="image/*" @change="onPhotoSelected" class="hidden" />
                </label>
                <div v-if="photoPreviewUrl" class="relative inline-block">
                  <img :src="photoPreviewUrl" class="w-12 h-12 object-cover rounded-lg border border-slate-300 shadow-2xs" />
                  <button 
                    type="button" 
                    @click="clearPhoto"
                    class="absolute -top-1.5 -right-1.5 bg-rose-600 text-white rounded-full w-4 h-4 flex items-center justify-center text-[10px] cursor-pointer"
                  >
                    &times;
                  </button>
                </div>
                <span v-if="photoFileName" class="text-[11px] text-slate-500 truncate max-w-xs">{{ photoFileName }}</span>
              </div>
            </div>
          </div>

        </div>

        <!-- Modal Bottom Footer -->
        <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between shrink-0">
          <div class="text-[11px] text-slate-500 hidden sm:block">
            * Menyimpan akan memperbarui status alkes dan menjadwalkan siklus berikutnya secara otomatis.
          </div>

          <div class="flex items-center gap-2 ml-auto">
            <button 
              type="button" 
              @click="selectedSchedule = null" 
              class="px-3.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-200 rounded-xl transition-colors cursor-pointer"
            >
              Batal
            </button>
            <button 
              type="button" 
              @click="submitCompletePM" 
              :disabled="executingPM" 
              class="flex items-center gap-1.5 px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 active:scale-95 rounded-xl shadow-sm disabled:opacity-50 transition-all cursor-pointer"
            >
              <CheckCircle2 v-if="!executingPM" class="w-4 h-4" />
              <div v-else class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
              <span>{{ executingPM ? 'Menyimpan LK...' : 'Selesaikan Pemeliharaan & Jadwalkan Baru' }}</span>
            </button>
          </div>
        </div>

      </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODAL 5: DETAIL LEMBAR KERJA PEMELIHARAAN (SELESAI)          -->
    <!-- ============================================================ -->
    <div v-if="detailSchedule" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
      <div class="bg-white rounded-2xl max-w-3xl w-full max-h-[90vh] flex flex-col shadow-2xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between shrink-0">
          <div>
            <h3 class="font-bold text-sm tracking-tight text-white flex items-center gap-2">
              <FileText class="w-4 h-4 text-emerald-400" />
              Detail Lembar Kerja Pemeliharaan Preventif (Selesai)
            </h3>
            <p class="text-xs text-slate-300">
              {{ detailSchedule.equipment_name }} • <span class="font-mono text-emerald-400">{{ detailSchedule.asset_code }}</span>
            </p>
          </div>
          <button @click="detailSchedule = null" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 cursor-pointer">
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="p-6 overflow-y-auto space-y-4 text-xs">
          <!-- Summary Cards -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
              <span class="text-slate-400 text-[10px] block">Tanggal Selesai</span>
              <span class="font-bold text-slate-800">{{ detailSchedule.completed_at || detailSchedule.scheduled_date }}</span>
            </div>
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
              <span class="text-slate-400 text-[10px] block">No. SPK</span>
              <span class="font-bold font-mono text-slate-800">{{ detailSchedule.sp_number || ('LK-PM-' + detailSchedule.id) }}</span>
            </div>
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
              <span class="text-slate-400 text-[10px] block">Kondisi Akhir</span>
              <span 
                :class="[
                  'font-bold uppercase text-[11px]',
                  detailSchedule.final_condition === 'laik_pakai' ? 'text-emerald-700' :
                  detailSchedule.final_condition === 'rusak_ringan' ? 'text-amber-700' : 'text-rose-700'
                ]"
              >
                {{ formatCondition(detailSchedule.final_condition) }}
              </span>
            </div>
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
              <span class="text-slate-400 text-[10px] block">Teknisi Pelaksana</span>
              <span class="font-bold text-slate-800">{{ detailSchedule.technician_name || '-' }}</span>
            </div>
          </div>

          <!-- Checklist Summary -->
          <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
            <span class="font-bold text-slate-800 text-xs block">Catatan Pemeriksaan & Tindak Lanjut:</span>
            <p class="text-slate-700 italic bg-white p-3 rounded-lg border border-slate-200">
              {{ detailSchedule.notes || 'Tidak ada catatan khusus. Semua parameter berfungsi sesuai spesifikasi standar.' }}
            </p>
          </div>

          <!-- Foto Stiker Bukti jika ada -->
          <div v-if="detailSchedule.photo_proof_path" class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
            <span class="font-bold text-slate-800 text-xs block">Foto Stiker Fisik Pemeliharaan:</span>
            <img :src="getUploadUrl(detailSchedule.photo_proof_path)" class="max-h-56 rounded-lg border border-slate-300 object-contain mx-auto" />
          </div>
        </div>

        <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between shrink-0">
          <button 
            type="button" 
            @click="detailSchedule = null" 
            class="px-3.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-200 rounded-xl cursor-pointer"
          >
            Tutup
          </button>

          <button 
            type="button" 
            @click="handlePrintLK(detailSchedule)" 
            class="flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 rounded-xl shadow-sm transition-all cursor-pointer"
          >
            <Printer class="w-4 h-4" />
            <span>Cetak Lembar Kerja (PDF)</span>
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { 
  CalendarCheck, Plus, X, Pencil, Trash2, Lock, 
  CheckSquare, Search, ChevronLeft, ChevronRight,
  Printer, FileText, CheckCircle2, ShieldCheck, 
  Clock, Camera, Eye
} from 'lucide-vue-next';
import { useAuthStore } from '../../stores/authStore';
import { useSettingStore } from '../../stores/settingStore';
import { exportPreventiveWorkOrderToPDF } from '../../utils/exportUtils';
import axiosClient, { getUploadUrl } from '../../api/axiosClient';
import SearchableSelect from '../../components/SearchableSelect.vue';

const authStore = useAuthStore();
const settingStore = useSettingStore();

const schedules = ref([]);
const equipmentList = ref([]);
const loading = ref(true);
const statusFilter = ref('');
const searchQuery = ref('');

const pagination = ref({
  page: 1,
  limit: 10,
  total: 0,
  total_pages: 1
});

const frequencyOptions = [
  { id: 'monthly', name: 'Bulanan (Monthly)' },
  { id: 'quarterly', name: '3 Bulan (Quarterly)' },
  { id: 'semi_annual', name: '6 Bulan (Semi-Annual)' },
  { id: 'annual', name: 'Tahunan (Annual)' }
];

const formatFrequency = (freq) => {
  const map = {
    'monthly': 'Bulanan',
    'quarterly': '3 Bulan',
    'semi_annual': '6 Bulan',
    'annual': 'Tahunan'
  };
  return map[freq] || freq;
};

const formatCondition = (cond) => {
  const map = {
    'laik_pakai': 'Laik Pakai',
    'rusak_ringan': 'Rusak Ringan',
    'rusak_berat': 'Rusak Berat'
  };
  return map[cond] || cond || 'Laik Pakai';
};

// Filtered equipment list based on role
const mappedEquipmentList = computed(() => {
  if (authStore.user?.role === 'ruangan' && authStore.user?.room_id) {
    return equipmentList.value.filter(eq => eq.room_id == authStore.user.room_id);
  }
  return equipmentList.value;
});

// Pagination visible pages helper
const visiblePages = computed(() => {
  const total = pagination.value.total_pages;
  const current = pagination.value.page;
  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1);
  }
  const pages = [];
  pages.push(1);
  if (current > 3) pages.push('...');
  const start = Math.max(2, current - 1);
  const end = Math.min(total - 1, current + 1);
  for (let i = start; i <= end; i++) {
    pages.push(i);
  }
  if (current < total - 2) pages.push('...');
  pages.push(total);
  return pages;
});

let searchTimeout = null;
const onSearchInput = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    pagination.value.page = 1;
    fetchSchedules();
  }, 350);
};

const changeStatusFilter = (st) => {
  statusFilter.value = st;
  pagination.value.page = 1;
  fetchSchedules();
};

const goToPage = (p) => {
  if (p < 1 || p > pagination.value.total_pages) return;
  pagination.value.page = p;
  fetchSchedules();
};

const changeLimit = (val) => {
  pagination.value.limit = parseInt(val) || 10;
  pagination.value.page = 1;
  fetchSchedules();
};

// Fetch schedules from backend
const fetchSchedules = async () => {
  loading.value = true;
  try {
    const params = {
      page: pagination.value.page,
      limit: pagination.value.limit,
      search: searchQuery.value || undefined,
      status: statusFilter.value || undefined
    };
    const res = await axiosClient.get('/preventive/schedules', { params });
    if (res.success && res.data) {
      if (res.data.items && res.data.pagination) {
        schedules.value = res.data.items;
        pagination.value = res.data.pagination;
      } else {
        schedules.value = Array.isArray(res.data) ? res.data : [];
        pagination.value.total = schedules.value.length;
        pagination.value.total_pages = 1;
      }
    }
  } catch (e) {
    console.error('Error loading preventive schedules:', e);
  } finally {
    loading.value = false;
  }
};

const fetchEquipment = async () => {
  try {
    const res = await axiosClient.get('/equipment');
    if (res.success) equipmentList.value = res.data;
  } catch (e) {}
};

// State Modal Add
const showAddModal = ref(false);
const savingSchedule = ref(false);
const scheduleError = ref('');
const newScheduleForm = ref({
  equipment_id: '',
  scheduled_date: '',
  frequency: 'quarterly',
  notes: ''
});

const openAddScheduleModal = () => {
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 7);
  const dateStr = tomorrow.toISOString().split('T')[0];

  const defaultEquip = mappedEquipmentList.value[0]?.id || '';
  newScheduleForm.value = {
    equipment_id: defaultEquip,
    scheduled_date: dateStr,
    frequency: 'quarterly',
    notes: ''
  };
  scheduleError.value = '';
  showAddModal.value = true;
};

const submitAddSchedule = async () => {
  savingSchedule.value = true;
  scheduleError.value = '';
  try {
    const res = await axiosClient.post('/preventive/schedules', newScheduleForm.value);
    if (res.success) {
      showAddModal.value = false;
      fetchSchedules();
    } else {
      scheduleError.value = res.message || 'Gagal menambahkan jadwal.';
    }
  } catch (err) {
    scheduleError.value = err.response?.data?.message || 'Terjadi kesalahan sistem.';
  } finally {
    savingSchedule.value = false;
  }
};

// State Modal Edit
const showEditModal = ref(false);
const savingEdit = ref(false);
const editError = ref('');
const editingSchedule = ref(null);
const editForm = ref({
  scheduled_date: '',
  frequency: 'quarterly',
  notes: ''
});

const openEditModal = (sch) => {
  editingSchedule.value = sch;
  editForm.value = {
    scheduled_date: sch.scheduled_date || '',
    frequency: sch.frequency || 'quarterly',
    notes: sch.notes || ''
  };
  editError.value = '';
  showEditModal.value = true;
};

const submitEditSchedule = async () => {
  if (!editingSchedule.value) return;
  savingEdit.value = true;
  editError.value = '';
  try {
    const res = await axiosClient.post(`/preventive/update/${editingSchedule.value.id}`, editForm.value);
    if (res.success) {
      showEditModal.value = false;
      fetchSchedules();
    } else {
      editError.value = res.message || 'Gagal memperbarui jadwal.';
    }
  } catch (err) {
    editError.value = err.response?.data?.message || 'Terjadi kesalahan sistem saat update.';
  } finally {
    savingEdit.value = false;
  }
};

// State Modal Delete
const showDeleteModal = ref(false);
const deleting = ref(false);
const deleteError = ref('');
const deletingSchedule = ref(null);

const openDeleteModal = (sch) => {
  if (sch.status === 'done') {
    alert('Jadwal yang sudah selesai (Done) tidak dapat dihapus demi kepatuhan audit & riwayat pemeliharaan alkes.');
    return;
  }
  deletingSchedule.value = sch;
  deleteError.value = '';
  showDeleteModal.value = true;
};

const confirmDeleteSchedule = async () => {
  if (!deletingSchedule.value) return;
  deleting.value = true;
  deleteError.value = '';
  try {
    const res = await axiosClient.delete(`/preventive/delete/${deletingSchedule.value.id}`);
    if (res.success) {
      showDeleteModal.value = false;
      deletingSchedule.value = null;
      fetchSchedules();
    } else {
      deleteError.value = res.message || 'Gagal menghapus jadwal.';
    }
  } catch (err) {
    deleteError.value = err.response?.data?.message || 'Gagal menghapus jadwal preventif.';
  } finally {
    deleting.value = false;
  }
};

// ============================================================
// CHECKLIST DEFINITIONS (KEMENKES / MFK 8)
// ============================================================
const inspectionItems = [
  { key: 'casing', label: 'Casing / Kotak / Rangka Alat', desc: 'Tidak ada keretakan, penyok, karat atau baut kendor' },
  { key: 'battery', label: 'Baterai / Catu Daya Cadangan', desc: 'Indikator baterai normal, tidak kembung atau bocor' },
  { key: 'mounting', label: 'Roda / Kaki / Braket Pemasangan', desc: 'Roda lancar, pengunci rem berfungsi, braket kokoh' },
  { key: 'power_cord', label: 'Kabel Power & Steker', desc: 'Kabel tidak terkelupas, steker utuh dan grounding tersambung' },
  { key: 'filter', label: 'Filter Udara / Saringan Debu', desc: 'Bersih, sirkulasi udara lancar dan tidak tersumbat' },
  { key: 'probe_connector', label: 'Konektor, Probe & Sensor', desc: 'Pin konektor utuh, kancing terkunci baik, sensor bersih' },
  { key: 'alarm', label: 'Sistem Alarm & Indikator Visual', desc: 'Buzzer/audio berbunyi nyaring, lampu indikator menyala' },
  { key: 'controls', label: 'Tombol, Saklar & Display Kontrol', desc: 'Tombol responsif, touchscreen normal, display tajam' }
];

const actionItems = [
  { key: 'cleaning', label: 'Pembersihan Fisik, Sasis & Debu Internal', desc: 'Membersihkan bodi luar, kisi pendingin & debu sasis' },
  { key: 'lubricating', label: 'Pelumasan Bagian Bergerak / Mekanis', desc: 'Pemberian pelumas pada gear, roda atau engsel mekanis' },
  { key: 'tightening', label: 'Pengencangan Baut, Mur & Soket', desc: 'Memastikan semua sambungan mekanis & terminal kencang' },
  { key: 'replacement', label: 'Penggantian Komponen Aus / Filter Baru', desc: 'Penggantian sparepart aus, seal atau filter udara baru' }
];

// ============================================================
// STATE MODAL EKSEKUSI LEMBAR KERJA PM
// ============================================================
const selectedSchedule = ref(null);
const executingPM = ref(false);
const photoFile = ref(null);
const photoFileName = ref('');
const photoPreviewUrl = ref('');

const executeForm = ref({
  execution_start_at: '',
  execution_end_at: '',
  sp_number: '',
  executor_type: 'internal',
  activity_type: 'pemeliharaan',
  final_condition: 'laik_pakai',
  technician_name: '',
  supervisor_name: '',
  notes: '',
  inspection_checklist: {},
  maintenance_actions: {},
  electrical_safety: {
    grounding_resistance: '0.12',
    leakage_current: '45.0'
  }
});

const setAllInspection = (val) => {
  inspectionItems.forEach(item => {
    executeForm.value.inspection_checklist[item.key] = val;
  });
};

const setAllActions = (val) => {
  actionItems.forEach(act => {
    executeForm.value.maintenance_actions[act.key] = val;
  });
};

const onPhotoSelected = (e) => {
  const file = e.target.files[0];
  if (file) {
    photoFile.value = file;
    photoFileName.value = file.name;
    photoPreviewUrl.value = URL.createObjectURL(file);
  }
};

const clearPhoto = () => {
  photoFile.value = null;
  photoFileName.value = '';
  photoPreviewUrl.value = '';
};

const openExecuteModal = (sch) => {
  selectedSchedule.value = sch;
  
  const now = new Date();
  const oneHourLater = new Date(now.getTime() + 60 * 60 * 1000);
  
  const pad = (n) => String(n).padStart(2, '0');
  const formatDatetimeLocal = (d) => {
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
  };

  const initialInspection = {};
  inspectionItems.forEach(i => {
    initialInspection[i.key] = 'baik';
  });

  const initialActions = {};
  actionItems.forEach(a => {
    initialActions[a.key] = (a.key === 'replacement') ? 'na' : 'ya';
  });

  executeForm.value = {
    execution_start_at: formatDatetimeLocal(now),
    execution_end_at: formatDatetimeLocal(oneHourLater),
    sp_number: sch.sp_number || `SPK-PM-${new Date().getFullYear()}-${String(sch.id).padStart(4, '0')}`,
    executor_type: sch.executor_type || 'internal',
    activity_type: 'pemeliharaan',
    final_condition: 'laik_pakai',
    technician_name: authStore.user?.full_name || 'Teknisi IPSRS',
    supervisor_name: settingStore.headIpsrsName || '',
    notes: 'Pemeliharaan preventif selesai sesuai standar SOP MFK 8. Alat medis siap digunakan.',
    inspection_checklist: initialInspection,
    maintenance_actions: initialActions,
    electrical_safety: {
      grounding_resistance: '0.12',
      leakage_current: '42.5'
    }
  };

  clearPhoto();
};

const submitCompletePM = async () => {
  if (!selectedSchedule.value) return;
  executingPM.value = true;
  try {
    const formData = new FormData();
    formData.append('execution_start_at', executeForm.value.execution_start_at);
    formData.append('execution_end_at', executeForm.value.execution_end_at);
    formData.append('sp_number', executeForm.value.sp_number || '');
    formData.append('executor_type', executeForm.value.executor_type);
    formData.append('activity_type', executeForm.value.activity_type);
    formData.append('final_condition', executeForm.value.final_condition);
    formData.append('technician_name', executeForm.value.technician_name);
    formData.append('supervisor_name', executeForm.value.supervisor_name || '');
    formData.append('inspection_checklist', JSON.stringify(executeForm.value.inspection_checklist));
    formData.append('maintenance_actions', JSON.stringify(executeForm.value.maintenance_actions));
    formData.append('electrical_safety', JSON.stringify(executeForm.value.electrical_safety));
    formData.append('notes', executeForm.value.notes || '');

    if (photoFile.value) {
      formData.append('photo_proof', photoFile.value);
    }

    const res = await axiosClient.post(`/preventive/complete/${selectedSchedule.value.id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    if (res.success) {
      alert(res.message);
      selectedSchedule.value = null;
      fetchSchedules();
    } else {
      alert(res.message || 'Gagal menyelesaikan lembar kerja preventif.');
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menyimpan PM.');
  } finally {
    executingPM.value = false;
  }
};

// ============================================================
// DETAIL & PRINT LEMBAR KERJA (LK-IPSRS)
// ============================================================
const detailSchedule = ref(null);

const openDetailModal = (sch) => {
  detailSchedule.value = sch;
};

const handlePrintLK = (sch) => {
  exportPreventiveWorkOrderToPDF(sch, {
    name: settingStore.hospitalName,
    subtitle: settingStore.hospitalSubtitle,
    address: settingStore.hospitalAddress,
    phone: settingStore.hospitalPhone,
    city: settingStore.hospitalCity,
    headName: settingStore.headIpsrsName,
    headNip: settingStore.headIpsrsNip
  });
};

onMounted(() => {
  fetchSchedules();
  fetchEquipment();
  settingStore.fetchSettings();
});
</script>
