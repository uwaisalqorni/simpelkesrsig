<template>
  <div class="space-y-6">
    <!-- Main Inventory Viewport (Screen only, hidden on print) -->
    <div class="print:hidden space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl font-black text-slate-800 tracking-tight flex items-center gap-2">
          <Stethoscope class="w-6 h-6 text-emerald-600" />
          Inventaris Alat Medis
        </h1>
        <p class="text-xs text-slate-500 mt-0.5">Daftar seluruh aset peralatan medis dan fasilitas elektromedis rumah sakit.</p>
      </div>

      <div class="flex items-center gap-2 flex-wrap">
        <!-- Tombol Export Excel -->
        <button 
          @click="handleExportExcel" 
          :disabled="exportingExcel"
          class="flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-emerald-50 text-emerald-700 border border-emerald-300 font-bold text-xs rounded-xl shadow-2xs transition-all cursor-pointer disabled:opacity-50 active:scale-95"
          title="Unduh laporan inventaris dalam format Microsoft Excel (.xlsx)"
        >
          <FileSpreadsheet class="w-4 h-4 text-emerald-600" />
          <span>{{ exportingExcel ? 'Mengekspor...' : 'Export Excel' }}</span>
        </button>

        <!-- Tombol Export PDF -->
        <button 
          @click="handleExportPDF" 
          :disabled="exportingPDF"
          class="flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-rose-50 text-rose-700 border border-rose-300 font-bold text-xs rounded-xl shadow-2xs transition-all cursor-pointer disabled:opacity-50 active:scale-95"
          title="Unduh laporan resmi inventaris dalam format PDF ber-KOP Surat RS"
        >
          <FileText class="w-4 h-4 text-rose-600" />
          <span>{{ exportingPDF ? 'Menyiapkan...' : 'Export PDF' }}</span>
        </button>

        <button 
          v-if="authStore.isAdmin"
          @click="openAddModal"
          class="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-sm transition-all cursor-pointer active:scale-95"
        >
          <Plus class="w-4 h-4" />
          <span>Tambah Alat Medis</span>
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
      <!-- Search Input -->
      <div>
        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Cari Nama / Serial / Aset</label>
        <div class="relative">
          <input 
            v-model="filters.search"
            @input="debounceSearch"
            type="text" 
            placeholder="Ketik kata kunci..." 
            class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"
          />
        </div>
      </div>

      <!-- Room Filter -->
      <div>
        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Ruangan / Unit</label>
        <SearchableSelect 
          v-model="filters.room_id"
          :options="rooms"
          value-key="id"
          label-key="name"
          sublabel-key="code"
          placeholder="Pilih Ruangan..."
          search-placeholder="Ketik nama / kode ruangan..."
          show-all-option
          all-option-label="Semua Ruangan"
          @change="onFilterChange"
        />
      </div>

      <!-- Status Filter -->
      <div>
        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Status Kondisi</label>
        <SearchableSelect 
          v-model="filters.status"
          :options="[
            { id: 'operasional', name: 'Operasional' },
            { id: 'rusak_ringan', name: 'Rusak Ringan' },
            { id: 'rusak_berat', name: 'Rusak Berat' },
            { id: 'afkir', name: 'Afkir' }
          ]"
          value-key="id"
          label-key="name"
          placeholder="Pilih Status..."
          search-placeholder="Ketik status..."
          show-all-option
          all-option-label="Semua Status"
          @change="onFilterChange"
        />
      </div>

      <!-- Category Filter -->
      <div>
        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Kategori Risiko</label>
        <SearchableSelect 
          v-model="filters.category_id"
          :options="categories"
          value-key="id"
          label-key="name"
          sublabel-key="risk_level"
          placeholder="Pilih Kategori..."
          search-placeholder="Ketik kategori..."
          show-all-option
          all-option-label="Semua Kategori"
          @change="onFilterChange"
        />
      </div>
    </div>

    <!-- Equipment Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
      <div v-if="loading" class="py-12 flex items-center justify-center text-slate-500 text-xs">
        <div class="w-5 h-5 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mr-2"></div>
        Memuat data inventaris...
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
            <tr>
              <th class="py-3 px-3 font-semibold text-center w-10">No</th>
              <th class="py-3 px-3 font-semibold text-center w-12">Foto</th>
              <th class="py-3 px-4 font-semibold">Kode Aset</th>
              <th class="py-3 px-4 font-semibold">Nama Alat & Spesifikasi</th>
              <th class="py-3 px-3 font-semibold text-center">Jml</th>
              <th class="py-3 px-4 font-semibold">Ruangan</th>
              <th class="py-3 px-3 font-semibold">Kondisi</th>
              <th class="py-3 px-4 font-semibold">Tgl Kalibrasi</th>
              <th class="py-3 px-4 font-semibold">Berlaku Sampai</th>
              <th class="py-3 px-4 font-semibold text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="(item, index) in items" :key="item.id" class="hover:bg-slate-50/70 transition-colors">
              <!-- Kolom No -->
              <td class="py-3 px-3 text-center font-medium text-slate-500">
                {{ (page - 1) * limit + index + 1 }}
              </td>

              <!-- Foto Alat -->
              <td class="py-3 px-3 text-center">
                <div v-if="item.image_path" class="relative group cursor-pointer inline-block" @click="openPhotoPreview(item)" title="Klik untuk memperbesar">
                  <img :src="getUploadUrl(item.image_path)" class="w-10 h-10 object-cover rounded-lg border border-slate-200 shadow-2xs group-hover:scale-105 transition-transform" />
                </div>
                <div v-else class="w-10 h-10 rounded-lg bg-slate-100 border border-dashed border-slate-200 flex items-center justify-center mx-auto text-slate-400">
                  <ImageIcon class="w-4 h-4" />
                </div>
              </td>

              <!-- Kode Aset -->
              <td class="py-3 px-4 font-mono font-bold text-slate-800">
                {{ item.asset_code }}
                <div class="text-[10px] text-slate-400 font-sans font-normal">SN: {{ item.serial_number }}</div>
              </td>

              <!-- Nama Alat & Spesifikasi -->
              <td class="py-3 px-4">
                <div class="font-bold text-slate-800 text-[13px]">{{ item.name }}</div>
                <div class="text-[11px] text-slate-500">{{ item.brand || '-' }} {{ item.model_type ? '• ' + item.model_type : '' }}</div>
                <div class="text-[10px] text-slate-400 font-medium">{{ item.category_name }}</div>
              </td>

              <!-- Jumlah Alat (Qty) -->
              <td class="py-3 px-3 text-center">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-extrabold bg-slate-100 text-slate-700 border border-slate-200">
                  {{ item.quantity || 1 }}
                </span>
              </td>

              <!-- Ruangan Penempatan -->
              <td class="py-3 px-4 text-slate-700">
                <div class="font-medium text-xs">{{ item.room_name }}</div>
                <div class="text-[10px] text-slate-400">{{ item.room_code }}</div>
              </td>

              <!-- Kondisi Operasional -->
              <td class="py-3 px-3">
                <span 
                  :class="[
                    'px-2.5 py-1 rounded-full text-[10px] font-bold whitespace-nowrap inline-block',
                    item.operational_status === 'operasional' ? 'bg-emerald-100 text-emerald-700' :
                    item.operational_status === 'rusak_ringan' ? 'bg-amber-100 text-amber-700' :
                    item.operational_status === 'rusak_berat' ? 'bg-rose-100 text-rose-700' : 'bg-slate-200 text-slate-700'
                  ]"
                >
                  {{ formatStatus(item.operational_status) }}
                </span>
              </td>

              <!-- Tanggal Kalibrasi -->
              <td class="py-3 px-4">
                <div v-if="item.calibration_date" class="text-xs font-semibold text-slate-800">
                  {{ item.calibration_date }}
                  <div class="text-[10px] text-slate-400 font-mono truncate max-w-[180px] lg:max-w-xs" :title="item.certificate_number">
                    {{ item.certificate_number || '-' }}
                  </div>
                </div>
                <span v-else class="text-[11px] text-slate-400 italic">Belum ada</span>
              </td>

              <!-- Berlaku Sampai (Status Kalibrasi BPFK) -->
              <td class="py-3 px-4">
                <div v-if="item.valid_until">
                  <div class="text-xs font-bold text-slate-800">{{ item.valid_until }}</div>
                  <span 
                    :class="[
                      'inline-block px-2 py-0.5 rounded text-[10px] mt-0.5',
                      getCalibrationStatus(item).class
                    ]"
                  >
                    {{ getCalibrationStatus(item).text }}
                  </span>
                </div>
                <span v-else class="px-2 py-0.5 bg-slate-100 text-slate-500 rounded text-[10px] font-medium">
                  Belum Kalibrasi
                </span>
              </td>

              <!-- Aksi -->
              <td class="py-3 px-4 text-right space-x-1.5 whitespace-nowrap">
                <button 
                  @click="openQRModal(item)"
                  class="px-2.5 py-1 text-slate-600 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition-colors inline-flex items-center gap-1 font-semibold cursor-pointer"
                  title="Lihat Label QR"
                >
                  <QrCode class="w-3.5 h-3.5" />
                  QR
                </button>

                <router-link 
                  :to="`/equipment/${item.id}`" 
                  class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg transition-colors inline-block"
                >
                  Detail
                </router-link>

                <!-- Tombol Edit (Admin & Teknisi) -->
                <button 
                  v-if="authStore.isAdmin || authStore.isTeknisi"
                  @click="openEditModal(item)"
                  class="px-2.5 py-1 text-amber-700 hover:text-amber-800 bg-amber-50 hover:bg-amber-100 rounded-lg transition-colors inline-flex items-center gap-1 font-semibold cursor-pointer"
                  title="Edit Data Alat"
                >
                  <Pencil class="w-3.5 h-3.5" />
                  Edit
                </button>

                <!-- Tombol Hapus (Khusus Admin) -->
                <button 
                  v-if="authStore.isAdmin"
                  @click="openDeleteModal(item)"
                  class="px-2.5 py-1 text-rose-700 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 rounded-lg transition-colors inline-flex items-center gap-1 font-semibold cursor-pointer"
                  title="Hapus Alat"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                  Hapus
                </button>
              </td>
            </tr>

            <tr v-if="items.length === 0">
              <td colspan="10" class="py-10 text-center text-slate-400">
                Tidak ada data inventaris yang sesuai kriteria pencarian.
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination & Limit Footer -->
        <div class="px-5 py-3.5 bg-slate-50/80 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
          <!-- Limit Selector & Info Jumlah Data -->
          <div class="flex items-center flex-wrap gap-4 text-slate-600">
            <div class="flex items-center gap-2">
              <span class="text-[11px] font-medium text-slate-500">Baris per halaman:</span>
              <select 
                :value="limit" 
                @change="changeLimit($event.target.value)"
                class="px-2 py-1 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500"
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
                Menampilkan <strong class="font-bold text-slate-800">{{ (page - 1) * limit + 1 }}</strong> - <strong class="font-bold text-slate-800">{{ Math.min(page * limit, pagination.total) }}</strong> dari <strong class="font-bold text-slate-800">{{ pagination.total }}</strong> alkes
              </span>
              <span v-else>Total: 0 alkes</span>
            </div>
          </div>

          <!-- Pagination Page Buttons -->
          <div class="flex items-center gap-1.5">
            <button 
              @click="goToPage(page - 1)" 
              :disabled="page <= 1 || loading"
              class="px-2.5 py-1 rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed font-medium transition-colors flex items-center gap-1 cursor-pointer"
            >
              <ChevronLeft class="w-3.5 h-3.5" />
              <span>Sebelumnya</span>
            </button>

            <!-- Numbered Pages -->
            <div class="flex items-center gap-1">
              <button 
                v-for="p in visiblePages" 
                :key="p"
                @click="p !== '...' && goToPage(p)"
                :disabled="p === '...' || p === page"
                :class="[
                  'min-w-[28px] h-7 text-xs font-bold rounded-lg transition-colors flex items-center justify-center',
                  p === page ? 'bg-emerald-600 text-white shadow-sm' : 
                  p === '...' ? 'text-slate-400 cursor-default px-1' :
                  'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100 cursor-pointer'
                ]"
              >
                {{ p }}
              </button>
            </div>

            <button 
              @click="goToPage(page + 1)" 
              :disabled="page >= pagination.total_pages || loading"
              class="px-2.5 py-1 rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed font-medium transition-colors flex items-center gap-1 cursor-pointer"
            >
              <span>Selanjutnya</span>
              <ChevronRight class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </div>
    </div>
    <!-- End of Main Inventory Viewport -->
  </div>

    <!-- Modal Tambah Alat (Admin) -->
    <div v-if="showAddModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4 print:hidden">
      <div class="bg-white rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-100">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
          <h3 class="font-bold text-base">Registrasi Alat Medis Baru</h3>
          <button @click="showAddModal = false" class="text-slate-400 hover:text-white cursor-pointer"><X class="w-5 h-5" /></button>
        </div>

        <form @submit.prevent="submitAddEquipment" class="p-6 space-y-4">
          <div v-if="addError" class="p-3 bg-rose-50 text-rose-700 text-xs rounded-lg border border-rose-200">
            {{ addError }}
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="col-span-2">
              <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Alat Medis *</label>
              <input v-model="form.name" required type="text" placeholder="Misal: Patient Monitor 7 Parameter" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Seri (Serial No) *</label>
              <input v-model="form.serial_number" required type="text" placeholder="SN-XXXXX" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Merk / Brand</label>
              <input v-model="form.brand" type="text" placeholder="Mindray / GE / Zoll" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Model / Tipe</label>
              <input v-model="form.model_type" type="text" placeholder="ePM 12M" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Alat (Qty) *</label>
              <input v-model.number="form.quantity" required min="1" type="number" placeholder="1" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Ruangan Penempatan *</label>
              <SearchableSelect 
                v-model="form.room_id"
                :options="rooms"
                value-key="id"
                label-key="name"
                sublabel-key="code"
                placeholder="Cari & pilih ruangan..."
                search-placeholder="Ketik nama atau kode ruangan..."
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori Alkes *</label>
              <SearchableSelect 
                v-model="form.category_id"
                :options="categories"
                value-key="id"
                label-key="name"
                sublabel-key="risk_level"
                placeholder="Cari & pilih kategori..."
                search-placeholder="Ketik kategori alkes..."
              />
            </div>

            <div class="col-span-2">
              <label class="block text-xs font-semibold text-slate-700 mb-1">Kondisi Operasional</label>
              <SearchableSelect 
                v-model="form.operational_status"
                :options="[
                  { id: 'operasional', name: 'Operasional' },
                  { id: 'rusak_ringan', name: 'Rusak Ringan' },
                  { id: 'rusak_berat', name: 'Rusak Berat' }
                ]"
                value-key="id"
                label-key="name"
                placeholder="Pilih kondisi..."
                search-placeholder="Cari status..."
              />
            </div>

            <!-- Upload Foto Bukti Alat & Kamera Depan/Belakang -->
            <div class="col-span-2 pt-2 border-t border-slate-100">
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Foto Fisik Bukti Alat Medis (Opsional)</label>
              <div class="flex items-center flex-wrap gap-2.5">
                <!-- Tombol Buka Kamera (Depan / Belakang) -->
                <button 
                  type="button" 
                  @click="openCameraForAdd" 
                  class="flex items-center gap-1.5 px-3 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-300 rounded-xl text-xs font-bold transition-all shadow-2xs cursor-pointer active:scale-95"
                >
                  <Camera class="w-4 h-4 text-emerald-600" />
                  <span>Ambil Foto Kamera</span>
                </button>

                <!-- Tombol Pilih File dari Galeri / Komputer -->
                <label class="flex items-center gap-1.5 px-3 py-2 border border-slate-300 hover:border-slate-400 bg-white text-slate-700 rounded-xl text-xs font-semibold cursor-pointer transition-colors shadow-2xs">
                  <Upload class="w-4 h-4 text-slate-500" />
                  <span class="max-w-[150px] truncate">{{ photoFile ? photoFile.name : 'Pilih File' }}</span>
                  <input type="file" accept="image/*" @change="handlePhotoChange" class="hidden" />
                </label>

                <!-- Thumbnail Preview -->
                <div v-if="photoPreview" class="relative group ml-1">
                  <img :src="photoPreview" class="w-11 h-11 object-cover rounded-xl border border-slate-200 shadow-sm" />
                  <button type="button" @click="clearPhoto" class="absolute -top-1.5 -right-1.5 bg-rose-600 text-white rounded-full p-0.5 shadow hover:bg-rose-700 cursor-pointer" title="Hapus foto">
                    <X class="w-3 h-3" />
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
            <button type="button" @click="showAddModal = false" class="px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-lg cursor-pointer">Batal</button>
            <button type="submit" :disabled="saving" class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm disabled:opacity-50 flex items-center gap-1.5 cursor-pointer">
              <span v-if="saving" class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              <span>{{ saving ? 'Menyimpan...' : 'Daftarkan Alat' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Edit Alat Medis (Admin & Teknisi) -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4 print:hidden">
      <div class="bg-white rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-100 animate-in fade-in zoom-in duration-150">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
          <div class="flex items-center gap-2">
            <Pencil class="w-4 h-4 text-amber-400" />
            <h3 class="font-bold text-base">Edit Data Alat Medis</h3>
            <span v-if="editingItem" class="font-mono text-[11px] bg-slate-800 text-amber-300 px-2 py-0.5 rounded border border-slate-700">
              {{ editingItem.asset_code }}
            </span>
          </div>
          <button @click="showEditModal = false" class="text-slate-400 hover:text-white cursor-pointer"><X class="w-5 h-5" /></button>
        </div>

        <form @submit.prevent="submitEditEquipment" class="p-6 space-y-4">
          <div v-if="editError" class="p-3 bg-rose-50 text-rose-700 text-xs rounded-lg border border-rose-200">
            {{ editError }}
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="col-span-2">
              <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Alat Medis *</label>
              <input v-model="editForm.name" required type="text" placeholder="Misal: Patient Monitor 7 Parameter" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Seri (Serial No) *</label>
              <input v-model="editForm.serial_number" required type="text" placeholder="SN-XXXXX" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Merk / Brand</label>
              <input v-model="editForm.brand" type="text" placeholder="Mindray / GE / Zoll" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Model / Tipe</label>
              <input v-model="editForm.model_type" type="text" placeholder="ePM 12M" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Alat (Qty) *</label>
              <input v-model.number="editForm.quantity" required min="1" type="number" placeholder="1" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Ruangan Penempatan *</label>
              <SearchableSelect 
                v-model="editForm.room_id"
                :options="rooms"
                value-key="id"
                label-key="name"
                sublabel-key="code"
                placeholder="Cari & pilih ruangan..."
                search-placeholder="Ketik nama atau kode ruangan..."
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori Alkes *</label>
              <SearchableSelect 
                v-model="editForm.category_id"
                :options="categories"
                value-key="id"
                label-key="name"
                sublabel-key="risk_level"
                placeholder="Cari & pilih kategori..."
                search-placeholder="Ketik kategori alkes..."
              />
            </div>

            <div class="col-span-2">
              <label class="block text-xs font-semibold text-slate-700 mb-1">Kondisi Operasional</label>
              <SearchableSelect 
                v-model="editForm.operational_status"
                :options="[
                  { id: 'operasional', name: 'Operasional' },
                  { id: 'rusak_ringan', name: 'Rusak Ringan' },
                  { id: 'rusak_berat', name: 'Rusak Berat' },
                  { id: 'afkir', name: 'Afkir' }
                ]"
                value-key="id"
                label-key="name"
                placeholder="Pilih kondisi..."
                search-placeholder="Cari status..."
              />
            </div>

            <!-- Upload Foto Pengganti & Kamera Depan/Belakang -->
            <div class="col-span-2 pt-2 border-t border-slate-100">
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Foto Fisik Alat Medis</label>
              <div class="flex items-center flex-wrap gap-2.5">
                <!-- Tombol Buka Kamera (Depan / Belakang) -->
                <button 
                  type="button" 
                  @click="openCameraForEdit" 
                  class="flex items-center gap-1.5 px-3 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-300 rounded-xl text-xs font-bold transition-all shadow-2xs cursor-pointer active:scale-95"
                >
                  <Camera class="w-4 h-4 text-emerald-600" />
                  <span>Ambil Foto Kamera</span>
                </button>

                <!-- Tombol Pilih File dari Galeri / Komputer -->
                <label class="flex items-center gap-1.5 px-3 py-2 border border-slate-300 hover:border-slate-400 bg-white text-slate-700 rounded-xl text-xs font-semibold cursor-pointer transition-colors shadow-2xs">
                  <Upload class="w-4 h-4 text-slate-500" />
                  <span class="max-w-[150px] truncate">{{ editPhotoFile ? editPhotoFile.name : (editPhotoPreview ? 'Ganti via File' : 'Pilih File') }}</span>
                  <input type="file" accept="image/*" @change="handleEditPhotoChange" class="hidden" />
                </label>

                <!-- Thumbnail Preview -->
                <div v-if="editPhotoPreview" class="relative group ml-1">
                  <img :src="editPhotoPreview" class="w-11 h-11 object-cover rounded-xl border border-slate-200 shadow-sm" />
                  <button v-if="editPhotoFile" type="button" @click="clearEditPhoto" class="absolute -top-1.5 -right-1.5 bg-rose-600 text-white rounded-full p-0.5 shadow hover:bg-rose-700 cursor-pointer" title="Batalkan foto baru">
                    <X class="w-3 h-3" />
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
            <button type="button" @click="showEditModal = false" class="px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-lg cursor-pointer">Batal</button>
            <button type="submit" :disabled="editSaving" class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm disabled:opacity-50 flex items-center gap-1.5 cursor-pointer">
              <span v-if="editSaving" class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              <span>{{ editSaving ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Konfirmasi Hapus Alat (Admin) -->
    <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4 print:hidden">
      <div class="bg-white rounded-2xl max-w-md w-full overflow-hidden shadow-2xl border border-slate-100 animate-in fade-in zoom-in duration-150">
        <div class="p-6 text-center">
          <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-3">
            <AlertTriangle class="w-6 h-6" />
          </div>
          <h3 class="text-base font-bold text-slate-900 mb-1">Hapus Alat Medis?</h3>
          <p class="text-xs text-slate-500 mb-4 leading-relaxed">
            Apakah Anda yakin ingin menghapus data alat medis berikut dari sistem inventaris?
          </p>

          <div v-if="deletingItem" class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 text-left mb-4 space-y-1">
            <div class="text-xs font-bold text-slate-800">{{ deletingItem.name }}</div>
            <div class="text-[11px] text-slate-600 flex items-center justify-between">
              <span>Kode: <strong class="font-mono text-slate-800">{{ deletingItem.asset_code }}</strong></span>
              <span>SN: <strong class="font-mono text-slate-800">{{ deletingItem.serial_number }}</strong></span>
            </div>
            <div class="text-[10px] text-slate-500">Ruangan: {{ deletingItem.room_name }}</div>
          </div>

          <div v-if="deleteError" class="p-3 mb-4 bg-rose-50 text-rose-700 text-xs rounded-lg border border-rose-200 text-left">
            {{ deleteError }}
          </div>

          <div class="p-2.5 bg-amber-50 rounded-xl border border-amber-200/70 text-[11px] text-amber-800 text-left mb-5">
            <strong>Catatan:</strong> Data akan diarsipkan (soft delete). Riwayat kalibrasi dan riwayat tiket perbaikan sebelumnya tetap tersimpan di basis data.
          </div>

          <div class="flex items-center justify-center gap-2">
            <button 
              type="button" 
              @click="showDeleteModal = false" 
              :disabled="deleting"
              class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-lg transition-colors cursor-pointer"
            >
              Batal
            </button>
            <button 
              type="button" 
              @click="confirmDeleteEquipment" 
              :disabled="deleting"
              class="px-5 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-sm transition-all disabled:opacity-50 flex items-center gap-1.5 cursor-pointer"
            >
              <span v-if="deleting" class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              <span>{{ deleting ? 'Menghapus...' : 'Ya, Hapus Alat' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal QR Code Badge & Printable Sticker -->
    <div v-if="selectedQRItem" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4 print:p-0 print:static print:bg-white print:backdrop-blur-none print:block print:overflow-visible">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 text-center shadow-2xl border border-slate-100 relative print:p-0 print:border-none print:shadow-none print:max-w-none print:w-full print:text-center print:bg-transparent">
        <button @click="selectedQRItem = null" class="absolute top-4 right-4 text-slate-400 hover:text-slate-800 print:hidden cursor-pointer"><X class="w-5 h-5" /></button>

        <div class="text-xs font-bold uppercase tracking-wider text-emerald-700 bg-emerald-50 py-1 px-3 rounded-full inline-block mb-3 print:hidden">
          Label Stiker QR Alkes
        </div>

        <!-- Mode Toggle: URL vs Plain Code -->
        <div class="mb-3 flex items-center justify-center gap-1 bg-slate-100 p-1 rounded-xl text-xs font-semibold print:hidden">
          <button 
            type="button"
            @click="changeQRMode('url')"
            :class="['px-3 py-1.5 rounded-lg transition-all cursor-pointer', qrMode === 'url' ? 'bg-white text-emerald-700 shadow-sm font-bold' : 'text-slate-500 hover:text-slate-800']"
          >
            Tautan Web (Kamera HP)
          </button>
          <button 
            type="button"
            @click="changeQRMode('code')"
            :class="['px-3 py-1.5 rounded-lg transition-all cursor-pointer', qrMode === 'code' ? 'bg-white text-emerald-700 shadow-sm font-bold' : 'text-slate-500 hover:text-slate-800']"
          >
            Kode Aset (Scanner Fisik)
          </button>
        </div>

        <!-- Opsi Host IP untuk Testing Scan Layar dengan HP di WiFi -->
        <div v-if="qrMode === 'url' && isLocalhost" class="mb-3 bg-emerald-50/80 border border-emerald-200 rounded-xl p-2.5 text-left text-xs space-y-1.5 print:hidden">
          <div class="flex items-center justify-between">
            <span class="font-bold text-emerald-900">Akses HP (Jaringan WiFi):</span>
            <label class="inline-flex items-center gap-1.5 cursor-pointer text-[11px] font-semibold text-emerald-800">
              <input type="checkbox" v-model="useLanIp" @change="renderQR" class="rounded text-emerald-600 focus:ring-emerald-500" />
              Gunakan IP LAN PC
            </label>
          </div>
          <div v-if="useLanIp" class="flex items-center gap-2">
            <input 
              v-model="customHost" 
              @input="renderQR"
              type="text" 
              class="w-full px-2 py-1 bg-white border border-emerald-300 rounded font-mono text-xs text-slate-800 focus:outline-none" 
              placeholder="Contoh: 192.168.0.194"
            />
          </div>
          <p class="text-[10px] text-emerald-700 leading-tight">
            *Scan menggunakan kamera HP (iPhone/Android/Google Lens) untuk langsung membuka alat ini.
          </p>
        </div>

        <!-- Kartu Stiker Fisik RS (Hanya bagian ini yang dicetak, pas 1 halaman) -->
        <div class="qr-sticker-card bg-white border-2 border-slate-900 p-4 rounded-xl inline-block shadow-md text-left w-full max-w-[280px] print:border-2 print:border-black print:rounded-xl print:shadow-none print:p-3.5 print:w-[75mm] print:max-w-[75mm] print:mx-auto print:my-2 print:block">
          <div class="flex items-center justify-between border-b border-slate-300 pb-2 mb-2">
            <div class="flex items-center gap-2">
              <img :src="settingStore.hospitalLogoUrl" class="w-8 h-8 object-contain" />
              <div>
                <div class="font-black text-xs text-slate-900 leading-tight uppercase">{{ settingStore.hospitalName }}</div>
                <div class="text-[8px] font-bold text-slate-600 uppercase">{{ settingStore.hospitalSubtitle }}</div>
              </div>
            </div>
            <span class="text-[9px] font-mono px-1.5 py-0.5 bg-slate-100 border border-slate-200 rounded text-slate-800 font-extrabold">SIMPELKES</span>
          </div>

          <canvas ref="qrCanvasRef" class="mx-auto my-2 block"></canvas>

          <div class="mt-2 text-center">
            <div class="font-mono font-black text-base text-slate-900 tracking-wider">{{ selectedQRItem.asset_code }}</div>
            <div class="text-xs font-bold text-slate-800 truncate mt-0.5">{{ selectedQRItem.name }}</div>
            <div class="text-[10px] font-medium text-slate-600">{{ selectedQRItem.room_name }}</div>
            <div class="text-[8px] text-slate-400 mt-1.5 border-t border-slate-200 pt-1">Scan QR untuk riwayat & lapor kerusakan</div>
          </div>
        </div>

        <!-- Preview URL -->
        <div v-if="qrMode === 'url'" class="mt-3 text-[11px] text-slate-500 break-all text-center print:hidden">
          <span class="text-slate-400">Tautan:</span> 
          <a :href="currentGeneratedUrl" target="_blank" class="text-emerald-600 hover:underline font-medium ml-1">
            {{ currentGeneratedUrl }}
          </a>
        </div>

        <div class="mt-4 flex items-center justify-center gap-2 print:hidden">
          <button @click="printQR" class="px-4 py-2 bg-slate-900 hover:bg-black text-white text-xs font-bold rounded-lg flex items-center gap-1.5 shadow-sm cursor-pointer">
            <Printer class="w-3.5 h-3.5" />
            Cetak Stiker Label
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Preview Foto Alat -->
    <div v-if="previewModalPhoto" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4 print:hidden" @click="previewModalPhoto = null">
      <div class="bg-white rounded-2xl max-w-md w-full overflow-hidden shadow-2xl p-4 text-center relative animate-in fade-in zoom-in duration-150" @click.stop>
        <button @click="previewModalPhoto = null" class="absolute top-3 right-3 text-slate-400 hover:text-slate-800 bg-white/80 rounded-full p-1 shadow cursor-pointer"><X class="w-5 h-5" /></button>
        <img :src="previewModalPhoto.url" class="max-h-[60vh] w-auto mx-auto rounded-xl object-contain shadow-sm border border-slate-100" />
        <div class="mt-3 text-sm font-bold text-slate-900">{{ previewModalPhoto.title }}</div>
        <div class="text-xs text-slate-500 font-mono">{{ previewModalPhoto.code }}</div>
      </div>
    </div>

    <!-- Modal Kamera Interaktif (Depan / Belakang) -->
    <CameraCaptureModal 
      v-model="showCameraModal" 
      @capture="handleCameraCapture" 
      class="print:hidden"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { 
  Stethoscope, 
  Plus, 
  QrCode, 
  X, 
  Printer, 
  Image as ImageIcon, 
  Upload, 
  Pencil, 
  Trash2, 
  ChevronLeft, 
  ChevronRight, 
  AlertTriangle,
  Camera,
  FileSpreadsheet,
  FileText
} from 'lucide-vue-next';
import { useAuthStore } from '../../stores/authStore';
import { useSettingStore } from '../../stores/settingStore';
import axiosClient, { getUploadUrl } from '../../api/axiosClient';
import QRCode from 'qrcode';
import SearchableSelect from '../../components/SearchableSelect.vue';
import CameraCaptureModal from '../../components/CameraCaptureModal.vue';
import { exportEquipmentToExcel, exportEquipmentToPDF } from '../../utils/exportUtils';
import { validateFile } from '../../utils/fileValidation';

const authStore = useAuthStore();
const settingStore = useSettingStore();
const items = ref([]);
const rooms = ref([]);
const categories = ref([]);
const loading = ref(true);

const filters = ref({
  search: '',
  room_id: '',
  status: '',
  category_id: ''
});

// Pagination State
const page = ref(1);
const limit = ref(10);
const pagination = ref({
  total: 0,
  page: 1,
  limit: 10,
  total_pages: 1
});

const visiblePages = computed(() => {
  const total = pagination.value.total_pages;
  const current = page.value;
  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1);
  }
  const pages = [];
  if (current <= 4) {
    for (let i = 1; i <= 5; i++) pages.push(i);
    pages.push('...');
    pages.push(total);
  } else if (current >= total - 3) {
    pages.push(1);
    pages.push('...');
    for (let i = total - 4; i <= total; i++) pages.push(i);
  } else {
    pages.push(1);
    pages.push('...');
    pages.push(current - 1);
    pages.push(current);
    pages.push(current + 1);
    pages.push('...');
    pages.push(total);
  }
  return pages;
});

const goToPage = (p) => {
  if (p < 1 || p > pagination.value.total_pages || p === page.value) return;
  page.value = p;
  fetchEquipment();
};

const changeLimit = (val) => {
  limit.value = parseInt(val, 10) || 10;
  page.value = 1;
  fetchEquipment();
};

const onFilterChange = () => {
  page.value = 1;
  fetchEquipment();
};

let searchTimeout = null;
const debounceSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    page.value = 1;
    fetchEquipment();
  }, 300);
};

// Export State & Handlers
const exportingExcel = ref(false);
const exportingPDF = ref(false);

const getExportData = async () => {
  try {
    const params = new URLSearchParams();
    if (filters.value.search) params.append('search', filters.value.search);
    if (filters.value.room_id) params.append('room_id', filters.value.room_id);
    if (filters.value.status) params.append('status', filters.value.status);
    if (filters.value.category_id) params.append('category_id', filters.value.category_id);
    params.append('limit', '0'); // Ambil seluruh data sesuai filter aktif

    const res = await axiosClient.get(`/equipment?${params.toString()}`);
    if (res.success && res.data && res.data.length > 0) {
      return res.data;
    }
  } catch (e) {
    console.error('Error fetching data for export:', e);
  }
  return items.value;
};

const handleExportExcel = async () => {
  exportingExcel.value = true;
  try {
    const data = await getExportData();
    exportEquipmentToExcel(data, {
      name: settingStore.hospitalName,
      subtitle: settingStore.hospitalSubtitle,
      address: settingStore.hospitalAddress,
      phone: settingStore.hospitalPhone
    });
  } catch (err) {
    console.error('Export Excel error:', err);
    alert('Gagal mengekspor data Excel: ' + err.message);
  } finally {
    exportingExcel.value = false;
  }
};

const handleExportPDF = async () => {
  exportingPDF.value = true;
  try {
    const data = await getExportData();
    exportEquipmentToPDF(data, {
      name: settingStore.hospitalName,
      subtitle: settingStore.hospitalSubtitle,
      address: settingStore.hospitalAddress,
      phone: settingStore.hospitalPhone
    });
  } catch (err) {
    console.error('Export PDF error:', err);
    alert('Gagal mengekspor data PDF: ' + err.message);
  } finally {
    exportingPDF.value = false;
  }
};

const fetchEquipment = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (filters.value.search) params.append('search', filters.value.search);
    if (filters.value.room_id) params.append('room_id', filters.value.room_id);
    if (filters.value.status) params.append('status', filters.value.status);
    if (filters.value.category_id) params.append('category_id', filters.value.category_id);
    params.append('page', page.value);
    params.append('limit', limit.value);

    const res = await axiosClient.get(`/equipment?${params.toString()}`);
    if (res.success) {
      items.value = res.data;
      if (res.pagination) {
        pagination.value = res.pagination;
      } else {
        pagination.value = {
          total: res.data.length,
          page: page.value,
          limit: limit.value,
          total_pages: Math.ceil(res.data.length / limit.value) || 1
        };
      }
    }
  } catch (err) {
    console.error('Failed to load equipment:', err);
  } finally {
    loading.value = false;
  }
};

const fetchMasters = async () => {
  try {
    const [resRooms, resCats] = await Promise.all([
      axiosClient.get('/rooms'),
      axiosClient.get('/categories')
    ]);
    if (resRooms.success) rooms.value = resRooms.data;
    if (resCats.success) categories.value = resCats.data;
  } catch (e) {}
};

// Modal Tambah State
const showAddModal = ref(false);
const saving = ref(false);
const addError = ref('');
const form = ref({
  name: '',
  serial_number: '',
  brand: '',
  model_type: '',
  room_id: '',
  category_id: '',
  operational_status: 'operasional',
  quantity: 1
});
const photoFile = ref(null);
const photoPreview = ref(null);

// State Kamera (Depan / Belakang)
const showCameraModal = ref(false);
const cameraTarget = ref('add'); // 'add' atau 'edit'

const openCameraForAdd = () => {
  cameraTarget.value = 'add';
  showCameraModal.value = true;
};

const openCameraForEdit = () => {
  cameraTarget.value = 'edit';
  showCameraModal.value = true;
};

const handleCameraCapture = (file) => {
  const validation = validateFile(file, 'image');
  if (!validation.valid) {
    if (cameraTarget.value === 'add') {
      addError.value = validation.error;
    } else {
      editError.value = validation.error;
    }
    return;
  }
  if (cameraTarget.value === 'add') {
    addError.value = '';
    photoFile.value = file;
    photoPreview.value = URL.createObjectURL(file);
  } else {
    editError.value = '';
    editPhotoFile.value = file;
    editPhotoPreview.value = URL.createObjectURL(file);
  }
};

const handlePhotoChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    const validation = validateFile(file, 'image');
    if (!validation.valid) {
      addError.value = validation.error;
      e.target.value = '';
      return;
    }
    addError.value = '';
    photoFile.value = file;
    photoPreview.value = URL.createObjectURL(file);
  }
};

const clearPhoto = () => {
  photoFile.value = null;
  photoPreview.value = null;
};

const openAddModal = () => {
  form.value = {
    name: '',
    serial_number: '',
    brand: '',
    model_type: '',
    room_id: rooms.value[0]?.id || '',
    category_id: categories.value[0]?.id || '',
    operational_status: 'operasional',
    quantity: 1
  };
  clearPhoto();
  addError.value = '';
  showAddModal.value = true;
};

const submitAddEquipment = async () => {
  saving.value = true;
  addError.value = '';
  try {
    const formData = new FormData();
    formData.append('name', form.value.name);
    formData.append('serial_number', form.value.serial_number);
    formData.append('brand', form.value.brand || '');
    formData.append('model_type', form.value.model_type || '');
    formData.append('room_id', form.value.room_id);
    formData.append('category_id', form.value.category_id);
    formData.append('operational_status', form.value.operational_status || 'operasional');
    formData.append('quantity', form.value.quantity || 1);
    if (photoFile.value) {
      formData.append('image', photoFile.value);
    }

    const res = await axiosClient.post('/equipment', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    if (res.success) {
      showAddModal.value = false;
      clearPhoto();
      fetchEquipment();
    } else {
      addError.value = res.message || 'Gagal menyimpan alat.';
    }
  } catch (err) {
    addError.value = err.response?.data?.message || 'Terjadi kesalahan sistem.';
  } finally {
    saving.value = false;
  }
};

// Modal Edit State
const showEditModal = ref(false);
const editingItem = ref(null);
const editSaving = ref(false);
const editError = ref('');
const editForm = ref({
  name: '',
  serial_number: '',
  brand: '',
  model_type: '',
  room_id: '',
  category_id: '',
  operational_status: 'operasional',
  quantity: 1
});
const editPhotoFile = ref(null);
const editPhotoPreview = ref(null);

const openEditModal = (item) => {
  editingItem.value = item;
  editForm.value = {
    name: item.name || '',
    serial_number: item.serial_number || '',
    brand: item.brand || '',
    model_type: item.model_type || '',
    quantity: item.quantity || 1,
    room_id: item.room_id || '',
    category_id: item.category_id || '',
    operational_status: item.operational_status || 'operasional'
  };
  editPhotoFile.value = null;
  editPhotoPreview.value = item.image_path ? getUploadUrl(item.image_path) : null;
  editError.value = '';
  showEditModal.value = true;
};

const handleEditPhotoChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    const validation = validateFile(file, 'image');
    if (!validation.valid) {
      editError.value = validation.error;
      e.target.value = '';
      return;
    }
    editError.value = '';
    editPhotoFile.value = file;
    editPhotoPreview.value = URL.createObjectURL(file);
  }
};

const clearEditPhoto = () => {
  editPhotoFile.value = null;
  editPhotoPreview.value = editingItem.value?.image_path ? getUploadUrl(editingItem.value.image_path) : null;
};

const submitEditEquipment = async () => {
  if (!editingItem.value) return;
  editSaving.value = true;
  editError.value = '';
  try {
    const formData = new FormData();
    formData.append('name', editForm.value.name);
    formData.append('serial_number', editForm.value.serial_number);
    formData.append('brand', editForm.value.brand || '');
    formData.append('model_type', editForm.value.model_type || '');
    formData.append('room_id', editForm.value.room_id);
    formData.append('category_id', editForm.value.category_id);
    formData.append('operational_status', editForm.value.operational_status || 'operasional');
    formData.append('quantity', editForm.value.quantity || 1);
    if (editPhotoFile.value) {
      formData.append('image', editPhotoFile.value);
    }

    const res = await axiosClient.post(`/equipment/update/${editingItem.value.id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    if (res.success) {
      showEditModal.value = false;
      editingItem.value = null;
      fetchEquipment();
    } else {
      editError.value = res.message || 'Gagal memperbarui alat medis.';
    }
  } catch (err) {
    editError.value = err.response?.data?.message || 'Terjadi kesalahan sistem saat memperbarui.';
  } finally {
    editSaving.value = false;
  }
};

// Modal Delete State
const showDeleteModal = ref(false);
const deletingItem = ref(null);
const deleting = ref(false);
const deleteError = ref('');

const openDeleteModal = (item) => {
  deletingItem.value = item;
  deleteError.value = '';
  showDeleteModal.value = true;
};

const confirmDeleteEquipment = async () => {
  if (!deletingItem.value) return;
  deleting.value = true;
  deleteError.value = '';
  try {
    const res = await axiosClient.post(`/equipment/delete/${deletingItem.value.id}`);
    if (res.success) {
      showDeleteModal.value = false;
      deletingItem.value = null;
      // Bila menghapus item terakhir di halaman tertentu (> 1), kembali ke halaman sebelumnya
      if (items.value.length === 1 && page.value > 1) {
        page.value -= 1;
      }
      fetchEquipment();
    } else {
      deleteError.value = res.message || 'Gagal menghapus data alat.';
    }
  } catch (err) {
    deleteError.value = err.response?.data?.message || 'Terjadi kesalahan sistem saat menghapus data.';
  } finally {
    deleting.value = false;
  }
};

// Modal Preview Foto Alat
const previewModalPhoto = ref(null);
const openPhotoPreview = (item) => {
  if (!item.image_path) return;
  previewModalPhoto.value = {
    url: getUploadUrl(item.image_path),
    title: item.name,
    code: item.asset_code
  };
};

const getCalibrationStatus = (item) => {
  if (!item.valid_until) {
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

// Modal QR Code State
const selectedQRItem = ref(null);
const qrCanvasRef = ref(null);
const qrMode = ref('url');
const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
const lanIp = '192.168.0.194';
const customHost = ref(isLocalhost ? `${lanIp}${window.location.port ? ':' + window.location.port : ''}` : window.location.host);
const useLanIp = ref(isLocalhost);
const currentGeneratedUrl = ref('');

const getTargetUrl = (item) => {
  if (qrMode.value === 'code') {
    return item.asset_code;
  }
  let host = window.location.host;
  if (useLanIp.value && customHost.value.trim()) {
    host = customHost.value.trim();
  }
  const base = window.location.pathname.startsWith('/simpelkesrsig') ? '/simpelkesrsig/' : '/';
  return `${window.location.protocol}//${host}${base}equipment/${item.id}`;
};

const renderQR = async () => {
  if (!selectedQRItem.value) return;
  await nextTick();
  if (qrCanvasRef.value) {
    const textToEncode = getTargetUrl(selectedQRItem.value);
    currentGeneratedUrl.value = textToEncode;
    QRCode.toCanvas(qrCanvasRef.value, textToEncode, {
      width: 170,
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
  renderQR();
};

const openQRModal = async (item) => {
  selectedQRItem.value = item;
  await renderQR();
};

const printQR = () => {
  window.print();
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
  fetchMasters();
  fetchEquipment();
});
</script>
