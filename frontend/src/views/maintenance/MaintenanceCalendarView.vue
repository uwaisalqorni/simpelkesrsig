<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl font-black text-slate-800 tracking-tight flex items-center gap-2">
          <CalendarDays class="w-6 h-6 text-emerald-600" />
          Kalender Pemeliharaan (Maintenance Calendar)
        </h1>
        <p class="text-xs text-slate-500 mt-0.5">
          Visualisasi jadwal pemeliharaan preventif (PM) dan atensi jatuh tempo sertifikat kalibrasi alkes.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <!-- View Mode Switcher -->
        <div class="bg-white border border-slate-200 p-1 rounded-xl shadow-xs flex items-center gap-1">
          <button 
            type="button"
            @click="currentView = 'month'"
            :class="[
              'px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer',
              currentView === 'month' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'
            ]"
          >
            <Calendar class="w-3.5 h-3.5" />
            <span>Kalender</span>
          </button>
          <button 
            type="button"
            @click="currentView = 'agenda'"
            :class="[
              'px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer',
              currentView === 'agenda' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'
            ]"
          >
            <ListFilter class="w-3.5 h-3.5" />
            <span>Agenda</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Mini KPI Indicator Stats Bar -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
        <div>
          <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Agenda</div>
          <div class="text-xl font-black text-slate-800 mt-0.5">{{ calendarStats.total_events || 0 }}</div>
        </div>
        <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center">
          <CalendarDays class="w-4 h-4" />
        </div>
      </div>

      <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
        <div>
          <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">PM Terjadwal</div>
          <div class="text-xl font-black text-emerald-600 mt-0.5">
            {{ calendarStats.pm_scheduled || 0 }}
            <span class="text-xs text-slate-400 font-normal">({{ calendarStats.pm_done || 0 }} Selesai)</span>
          </div>
        </div>
        <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
          <CalendarCheck class="w-4 h-4" />
        </div>
      </div>

      <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
        <div>
          <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">PM Terlambat</div>
          <div class="text-xl font-black text-rose-600 mt-0.5">
            {{ calendarStats.pm_overdue || 0 }}
          </div>
        </div>
        <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
          <Clock class="w-4 h-4" />
        </div>
      </div>

      <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
        <div>
          <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Atensi Kalibrasi</div>
          <div class="text-xl font-black text-amber-600 mt-0.5">
            {{ (calendarStats.cal_expired || 0) + (calendarStats.cal_expiring || 0) }}
            <span v-if="calendarStats.cal_expired > 0" class="text-xs text-rose-600 font-bold">
              ({{ calendarStats.cal_expired }} Habis)
            </span>
          </div>
        </div>
        <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
          <Award class="w-4 h-4" />
        </div>
      </div>
    </div>

    <!-- Calendar Navigation & Filters Toolbar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
      <!-- Month Navigation Controls -->
      <div class="flex items-center gap-3">
        <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl">
          <button 
            type="button"
            @click="prevMonth"
            class="p-1.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-white transition-all cursor-pointer"
            title="Bulan Sebelumnya"
          >
            <ChevronLeft class="w-4 h-4" />
          </button>
          <button 
            type="button"
            @click="nextMonth"
            class="p-1.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-white transition-all cursor-pointer"
            title="Bulan Berikutnya"
          >
            <ChevronRight class="w-4 h-4" />
          </button>
        </div>

        <div class="font-extrabold text-base text-slate-900 tracking-tight">
          {{ monthYearLabel }}
        </div>

        <button 
          type="button"
          @click="goToToday"
          class="px-2.5 py-1 text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition-colors cursor-pointer"
        >
          Hari Ini
        </button>
      </div>

      <!-- Filters & Legend -->
      <div class="flex flex-wrap items-center gap-2.5">
        <!-- Event Type Filter -->
        <select 
          v-model="selectedType"
          @change="fetchEvents"
          class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 cursor-pointer"
        >
          <option value="all">Semua Agenda</option>
          <option value="preventive">Preventive Maintenance (PM)</option>
          <option value="calibration">Jatuh Tempo Kalibrasi</option>
        </select>

        <!-- Room Filter (if not locked to room) -->
        <select 
          v-if="authStore.user?.role !== 'ruangan'"
          v-model="selectedRoomId"
          @change="fetchEvents"
          class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 max-w-[180px] truncate cursor-pointer"
        >
          <option value="">Semua Ruangan</option>
          <option v-for="r in roomList" :key="r.id" :value="r.id">{{ r.name }}</option>
        </select>

        <!-- Legend Pills -->
        <div class="hidden xl:flex items-center gap-2 pl-2 border-l border-slate-200 text-[11px] text-slate-500 font-medium">
          <span class="flex items-center gap-1">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
            PM Selesai
          </span>
          <span class="flex items-center gap-1">
            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
            Terjadwal / Dekat
          </span>
          <span class="flex items-center gap-1">
            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
            Terlambat / Expired
          </span>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="py-20 text-center text-slate-400 text-xs bg-white rounded-2xl border border-slate-200/80">
      <div class="w-6 h-6 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
      Memuat agenda pemeliharaan...
    </div>

    <!-- VIEW 1: MONTH GRID CALENDAR -->
    <div v-else-if="currentView === 'month'" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
      <!-- Days of Week Header -->
      <div class="grid grid-cols-7 bg-slate-50 border-b border-slate-200 text-center py-2.5 text-xs font-bold text-slate-600">
        <div v-for="d in ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu']" :key="d" class="truncate">
          {{ d }}
        </div>
      </div>

      <!-- Days Grid (6 Rows x 7 Cols) -->
      <div class="grid grid-cols-7 auto-rows-fr divide-x divide-y divide-slate-100">
        <div 
          v-for="(day, idx) in calendarDays" 
          :key="idx"
          :class="[
            'min-h-[110px] sm:min-h-[125px] p-1.5 sm:p-2 transition-colors flex flex-col',
            day.isCurrentMonth ? 'bg-white' : 'bg-slate-50/50 text-slate-300',
            day.isToday ? 'bg-emerald-50/30' : ''
          ]"
        >
          <!-- Day Header: Date Number & Today Indicator -->
          <div class="flex items-center justify-between mb-1">
            <span 
              :class="[
                'text-xs font-bold w-6 h-6 flex items-center justify-center rounded-full',
                day.isToday ? 'bg-emerald-600 text-white shadow-xs' : day.isCurrentMonth ? 'text-slate-800' : 'text-slate-400'
              ]"
            >
              {{ day.dateNumber }}
            </span>
            <span v-if="day.events.length > 0" class="text-[10px] font-bold text-slate-400">
              {{ day.events.length }}
            </span>
          </div>

          <!-- Day Event Pills Container -->
          <div class="space-y-1 flex-1 overflow-y-auto max-h-[85px] pr-0.5">
            <button 
              v-for="ev in day.events.slice(0, 3)" 
              :key="ev.id"
              type="button"
              @click="openEventDetail(ev)"
              :class="[
                'w-full text-left px-1.5 py-1 rounded-md text-[10px] font-bold truncate flex items-center gap-1 transition-all cursor-pointer border',
                ev.color === 'emerald' ? 'bg-emerald-50 text-emerald-800 border-emerald-200 hover:bg-emerald-100' :
                ev.color === 'rose' ? 'bg-rose-50 text-rose-800 border-rose-200 hover:bg-rose-100' :
                ev.color === 'amber' ? 'bg-amber-50 text-amber-900 border-amber-200 hover:bg-amber-100' :
                'bg-sky-50 text-sky-800 border-sky-200 hover:bg-sky-100'
              ]"
              :title="`${ev.type_label}: ${ev.equipment_name} (${ev.room_name})`"
            >
              <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="[
                ev.color === 'emerald' ? 'bg-emerald-500' :
                ev.color === 'rose' ? 'bg-rose-500' :
                ev.color === 'amber' ? 'bg-amber-500' : 'bg-sky-500'
              ]"></span>
              <span class="truncate">{{ ev.type === 'preventive' ? 'PM' : 'Kalib' }}: {{ ev.equipment_name }}</span>
            </button>

            <!-- Overflow indicator if > 3 events -->
            <button 
              v-if="day.events.length > 3" 
              type="button"
              @click="openDayEventsModal(day)"
              class="w-full text-center py-0.5 text-[9px] font-extrabold text-slate-500 hover:text-emerald-700 bg-slate-100 hover:bg-emerald-50 rounded transition-colors cursor-pointer"
            >
              +{{ day.events.length - 3 }} lainnya
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- VIEW 2: AGENDA / TIMELINE LIST -->
    <div v-else-if="currentView === 'agenda'" class="space-y-4">
      <div v-if="groupedAgendaList.length === 0" class="bg-white p-12 text-center text-slate-400 rounded-2xl border border-slate-200">
        <CalendarX class="w-10 h-10 mx-auto mb-2 text-slate-300" />
        <div class="font-bold text-sm text-slate-700">Tidak ada agenda pemeliharaan</div>
        <p class="text-xs text-slate-400 mt-0.5">Tidak ditemukan jadwal PM atau kalibrasi pada rentang tanggal ini.</p>
      </div>

      <div v-for="group in groupedAgendaList" :key="group.date" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <!-- Date Header Row -->
        <div class="px-5 py-3 bg-slate-50/90 border-b border-slate-200 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <Calendar class="w-4 h-4 text-emerald-600" />
            <span class="font-black text-xs text-slate-800">{{ formatFullDate(group.date) }}</span>
          </div>
          <span class="text-[11px] font-bold px-2 py-0.5 bg-slate-200 text-slate-700 rounded-full">
            {{ group.items.length }} Agenda
          </span>
        </div>

        <!-- Events in Date -->
        <div class="divide-y divide-slate-100">
          <div 
            v-for="ev in group.items" 
            :key="ev.id"
            @click="openEventDetail(ev)"
            class="p-4 hover:bg-slate-50 transition-colors flex flex-col sm:flex-row sm:items-center justify-between gap-3 cursor-pointer group"
          >
            <div class="flex items-start gap-3">
              <div 
                :class="[
                  'w-10 h-10 rounded-xl flex items-center justify-center shrink-0 mt-0.5 group-hover:scale-105 transition-transform shadow-xs',
                  ev.color === 'emerald' ? 'bg-emerald-100 text-emerald-700' :
                  ev.color === 'rose' ? 'bg-rose-100 text-rose-700' :
                  ev.color === 'amber' ? 'bg-amber-100 text-amber-700' : 'bg-sky-100 text-sky-700'
                ]"
              >
                <CalendarCheck v-if="ev.type === 'preventive'" class="w-5 h-5" />
                <Award v-else class="w-5 h-5" />
              </div>

              <div>
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="font-extrabold text-sm text-slate-900">{{ ev.equipment_name }}</span>
                  <span class="font-mono text-[10px] text-slate-400">{{ ev.asset_code }}</span>
                  <!-- Type Badge -->
                  <span 
                    :class="[
                      'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase',
                      ev.type === 'preventive' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'
                    ]"
                  >
                    {{ ev.type_label }}
                  </span>
                </div>

                <div class="text-xs text-slate-500 mt-1 flex items-center gap-3 flex-wrap">
                  <span>Unit: <strong class="text-slate-700">{{ ev.room_name }}</strong></span>
                  <span v-if="ev.frequency_label">&bull; Siklus: {{ ev.frequency_label }}</span>
                  <span v-if="ev.certificate_number">&bull; No. Sertifikat: {{ ev.certificate_number }}</span>
                  <span v-if="ev.technician_name">&bull; Teknisi: {{ ev.technician_name }}</span>
                </div>
              </div>
            </div>

            <!-- Status Indicator & Detail Button -->
            <div class="flex items-center gap-2 self-end sm:self-center">
              <span 
                :class="[
                  'px-2.5 py-1 rounded-full text-[11px] font-bold inline-flex items-center gap-1.5',
                  ev.color === 'emerald' ? 'bg-emerald-100 text-emerald-800' :
                  ev.color === 'rose' ? 'bg-rose-100 text-rose-800' :
                  ev.color === 'amber' ? 'bg-amber-100 text-amber-900' : 'bg-sky-100 text-sky-800'
                ]"
              >
                <span class="w-1.5 h-1.5 rounded-full" :class="[
                  ev.color === 'emerald' ? 'bg-emerald-500' :
                  ev.color === 'rose' ? 'bg-rose-500' :
                  ev.color === 'amber' ? 'bg-amber-500' : 'bg-sky-500'
                ]"></span>
                {{ ev.status_label }}
              </span>
              <ChevronRight class="w-4 h-4 text-slate-400 group-hover:translate-x-0.5 transition-transform" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL 1: EVENT DETAIL POPUP -->
    <div v-if="selectedEvent" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-100 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <!-- Header Modal -->
        <div 
          :class="[
            'p-5 text-white flex items-start justify-between',
            selectedEvent.color === 'emerald' ? 'bg-gradient-to-r from-emerald-700 to-teal-800' :
            selectedEvent.color === 'rose' ? 'bg-gradient-to-r from-rose-700 to-red-800' :
            selectedEvent.color === 'amber' ? 'bg-gradient-to-r from-amber-600 to-orange-700' :
            'bg-gradient-to-r from-slate-800 to-slate-900'
          ]"
        >
          <div>
            <span class="px-2 py-0.5 rounded bg-white/20 text-white font-bold text-[10px] uppercase tracking-wide inline-block mb-1.5">
              {{ selectedEvent.type_label }}
            </span>
            <h3 class="font-extrabold text-base leading-snug">{{ selectedEvent.equipment_name }}</h3>
            <div class="text-xs text-white/80 font-mono mt-0.5">{{ selectedEvent.asset_code }}</div>
          </div>
          <button @click="selectedEvent = null" class="text-white/70 hover:text-white cursor-pointer"><X class="w-5 h-5" /></button>
        </div>

        <!-- Body Modal -->
        <div class="p-5 space-y-4 text-xs">
          <!-- Status Alert Banner -->
          <div 
            :class="[
              'p-3 rounded-xl border flex items-center justify-between font-bold',
              selectedEvent.color === 'emerald' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' :
              selectedEvent.color === 'rose' ? 'bg-rose-50 text-rose-800 border-rose-200' :
              selectedEvent.color === 'amber' ? 'bg-amber-50 text-amber-900 border-amber-200' :
              'bg-sky-50 text-sky-800 border-sky-200'
            ]"
          >
            <div class="flex items-center gap-2">
              <Clock class="w-4 h-4" />
              <span>Status Agenda:</span>
            </div>
            <span>{{ selectedEvent.status_label }}</span>
          </div>

          <!-- Specifications Grid -->
          <div class="bg-slate-50 p-3.5 rounded-xl space-y-2 border border-slate-200/70">
            <div class="flex justify-between">
              <span class="text-slate-500">Tanggal Agenda:</span>
              <strong class="text-slate-800">{{ formatFullDate(selectedEvent.date) }}</strong>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Ruangan / Unit:</span>
              <strong class="text-slate-800">{{ selectedEvent.room_name }}</strong>
            </div>
            <div v-if="selectedEvent.brand" class="flex justify-between">
              <span class="text-slate-500">Merk / Model:</span>
              <strong class="text-slate-800">{{ selectedEvent.brand }} {{ selectedEvent.model_type || '' }}</strong>
            </div>
            <div v-if="selectedEvent.serial_number" class="flex justify-between">
              <span class="text-slate-500">Serial Number (SN):</span>
              <strong class="text-slate-800 font-mono">{{ selectedEvent.serial_number }}</strong>
            </div>

            <!-- Specific for Preventive -->
            <template v-if="selectedEvent.type === 'preventive'">
              <div class="flex justify-between">
                <span class="text-slate-500">Frekuensi Siklus:</span>
                <strong class="text-slate-800">{{ selectedEvent.frequency_label }}</strong>
              </div>
              <div v-if="selectedEvent.technician_name" class="flex justify-between">
                <span class="text-slate-500">Teknisi Pelaksana:</span>
                <strong class="text-slate-800">{{ selectedEvent.technician_name }}</strong>
              </div>
            </template>

            <!-- Specific for Calibration -->
            <template v-if="selectedEvent.type === 'calibration'">
              <div v-if="selectedEvent.certificate_number" class="flex justify-between">
                <span class="text-slate-500">No. Sertifikat:</span>
                <strong class="text-slate-800 font-mono">{{ selectedEvent.certificate_number }}</strong>
              </div>
              <div v-if="selectedEvent.vendor_name" class="flex justify-between">
                <span class="text-slate-500">Lembaga Uji BPFK:</span>
                <strong class="text-slate-800">{{ selectedEvent.vendor_name }}</strong>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500">Sisa Masa Berlaku:</span>
                <strong :class="selectedEvent.days_remaining <= 0 ? 'text-rose-600 font-black' : 'text-slate-800'">
                  {{ selectedEvent.days_remaining <= 0 ? 'KEDALUWARSA' : `${selectedEvent.days_remaining} Hari` }}
                </strong>
              </div>
            </template>
          </div>

          <!-- Notes section -->
          <div v-if="selectedEvent.notes" class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-amber-900">
            <div class="font-bold text-[11px] mb-0.5">Catatan Khusus:</div>
            <p class="leading-relaxed">{{ selectedEvent.notes }}</p>
          </div>

          <!-- Quick Action Buttons -->
          <div class="space-y-2 pt-2 border-t border-slate-100">
            <router-link 
              :to="`/equipment/${selectedEvent.equipment_id}`"
              class="w-full py-2.5 bg-slate-900 hover:bg-black text-white font-bold text-center rounded-xl flex items-center justify-center gap-2 cursor-pointer transition-all"
            >
              <Stethoscope class="w-4 h-4 text-emerald-400" />
              <span>Buka Kartu Riwayat Alkes</span>
            </router-link>

            <router-link 
              v-if="selectedEvent.type === 'preventive' && (authStore.isAdmin || authStore.isTeknisi)"
              to="/preventive"
              class="w-full py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-center rounded-xl block cursor-pointer transition-colors"
            >
              Buka Modul Pemeliharaan Preventif
            </router-link>

            <router-link 
              v-if="selectedEvent.type === 'calibration'"
              to="/calibrations"
              class="w-full py-2 bg-amber-50 hover:bg-amber-100 text-amber-800 font-bold text-center rounded-xl block cursor-pointer transition-colors"
            >
              Buka Modul Kalibrasi & Sertifikasi
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL 2: DAY EVENTS OVERFLOW LIST -->
    <div v-if="selectedDayOverflow" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-md w-full p-5 shadow-2xl border border-slate-100 space-y-3">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h3 class="font-bold text-sm text-slate-800">Daftar Agenda Tanggal</h3>
            <div class="text-xs text-slate-500 font-semibold">{{ formatFullDate(selectedDayOverflow.dateStr) }}</div>
          </div>
          <button @click="selectedDayOverflow = null" class="text-slate-400 hover:text-slate-800 cursor-pointer"><X class="w-5 h-5" /></button>
        </div>

        <div class="space-y-2 max-h-96 overflow-y-auto pr-1">
          <div 
            v-for="ev in selectedDayOverflow.events" 
            :key="ev.id"
            @click="selectedDayOverflow = null; openEventDetail(ev)"
            :class="[
              'p-3 rounded-xl border flex items-center justify-between gap-2 hover:shadow-xs transition-all cursor-pointer',
              ev.color === 'emerald' ? 'bg-emerald-50/50 border-emerald-200' :
              ev.color === 'rose' ? 'bg-rose-50/50 border-rose-200' :
              ev.color === 'amber' ? 'bg-amber-50/50 border-amber-200' : 'bg-sky-50/50 border-sky-200'
            ]"
          >
            <div>
              <span class="text-[10px] font-extrabold uppercase" :class="[
                ev.color === 'emerald' ? 'text-emerald-700' :
                ev.color === 'rose' ? 'text-rose-700' :
                ev.color === 'amber' ? 'text-amber-800' : 'text-sky-700'
              ]">
                {{ ev.type_label }}
              </span>
              <div class="font-bold text-xs text-slate-800">{{ ev.equipment_name }}</div>
              <div class="text-[10px] text-slate-500">{{ ev.room_name }} &bull; {{ ev.asset_code }}</div>
            </div>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-white shadow-2xs border border-slate-200 text-slate-700 shrink-0">
              {{ ev.status_label }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { 
  CalendarDays, Calendar, ListFilter, CalendarCheck, Clock, 
  Award, ChevronLeft, ChevronRight, X, Stethoscope, CalendarX 
} from 'lucide-vue-next';
import { useAuthStore } from '../../stores/authStore';
import axiosClient from '../../api/axiosClient';

const authStore = useAuthStore();

const currentView = ref('month'); // 'month' or 'agenda'
const loading = ref(false);
const events = ref([]);
const calendarStats = ref({});
const roomList = ref([]);

// Selected filters
const selectedType = ref('all');
const selectedRoomId = ref('');

// Calendar date navigation state
const currentDate = ref(new Date());

const currentYear = computed(() => currentDate.value.getFullYear());
const currentMonth = computed(() => currentDate.value.getMonth());

const monthNames = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const monthYearLabel = computed(() => {
  return `${monthNames[currentMonth.value]} ${currentYear.value}`;
});

const prevMonth = () => {
  currentDate.value = new Date(currentYear.value, currentMonth.value - 1, 1);
  fetchEvents();
};

const nextMonth = () => {
  currentDate.value = new Date(currentYear.value, currentMonth.value + 1, 1);
  fetchEvents();
};

const goToToday = () => {
  currentDate.value = new Date();
  fetchEvents();
};

// Date formatter helpers
const formatFullDate = (dateStr) => {
  if (!dateStr) return '-';
  const parts = dateStr.split('-');
  if (parts.length < 3) return dateStr;
  const y = parseInt(parts[0]);
  const m = parseInt(parts[1]) - 1;
  const d = parseInt(parts[2]);
  const date = new Date(y, m, d);
  const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
  return `${dayNames[date.getDay()]}, ${d} ${monthNames[m]} ${y}`;
};

// Calculate 42 days grid for current month (Mon - Sun layout)
const calendarDays = computed(() => {
  const year = currentYear.value;
  const month = currentMonth.value;

  const firstDayOfMonth = new Date(year, month, 1);
  const lastDayOfMonth = new Date(year, month + 1, 0);

  // Day of week: 0 is Sun, 1 is Mon. We want 0=Mon ... 6=Sun
  let startDayOfWeek = firstDayOfMonth.getDay() - 1;
  if (startDayOfWeek === -1) startDayOfWeek = 6;

  const totalDaysInMonth = lastDayOfMonth.getDate();
  const prevMonthLastDay = new Date(year, month, 0).getDate();

  const todayStr = new Date().toISOString().split('T')[0];

  const days = [];

  // 1. Fill trailing days from previous month
  for (let i = startDayOfWeek - 1; i >= 0; i--) {
    const d = prevMonthLastDay - i;
    const prevM = month === 0 ? 11 : month - 1;
    const prevY = month === 0 ? year - 1 : year;
    const dateStr = `${prevY}-${String(prevM + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
    days.push({
      dateNumber: d,
      dateStr: dateStr,
      isCurrentMonth: false,
      isToday: dateStr === todayStr,
      events: events.value.filter(ev => ev.date === dateStr)
    });
  }

  // 2. Fill days of current month
  for (let d = 1; d <= totalDaysInMonth; d++) {
    const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
    days.push({
      dateNumber: d,
      dateStr: dateStr,
      isCurrentMonth: true,
      isToday: dateStr === todayStr,
      events: events.value.filter(ev => ev.date === dateStr)
    });
  }

  // 3. Fill leading days from next month to complete 42 cells (6 rows x 7 cols)
  const remaining = 42 - days.length;
  for (let d = 1; d <= remaining; d++) {
    const nextM = month === 11 ? 0 : month + 1;
    const nextY = month === 11 ? year + 1 : year;
    const dateStr = `${nextY}-${String(nextM + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
    days.push({
      dateNumber: d,
      dateStr: dateStr,
      isCurrentMonth: false,
      isToday: dateStr === todayStr,
      events: events.value.filter(ev => ev.date === dateStr)
    });
  }

  return days;
});

// Grouped Agenda View
const groupedAgendaList = computed(() => {
  const map = {};
  events.value.forEach(ev => {
    if (!map[ev.date]) {
      map[ev.date] = [];
    }
    map[ev.date].push(ev);
  });

  const sortedDates = Object.keys(map).sort();
  return sortedDates.map(date => ({
    date: date,
    items: map[date]
  }));
});

// Fetch events from API
const fetchEvents = async () => {
  loading.value = true;
  try {
    const year = currentYear.value;
    const month = currentMonth.value;

    // Fetch range: 1st of month - 7 days to end of month + 14 days
    const startDate = new Date(year, month, -7).toISOString().split('T')[0];
    const endDate = new Date(year, month + 1, 14).toISOString().split('T')[0];

    const params = {
      start: startDate,
      end: endDate,
      type: selectedType.value !== 'all' ? selectedType.value : undefined,
      room_id: selectedRoomId.value || undefined
    };

    const res = await axiosClient.get('/calendar/events', { params });
    if (res.success && res.data) {
      events.value = res.data.events || [];
      calendarStats.value = res.data.stats || {};
    }
  } catch (err) {
    console.error('Error fetching calendar events:', err);
  } finally {
    loading.value = false;
  }
};

const fetchRooms = async () => {
  try {
    const res = await axiosClient.get('/rooms');
    if (res.success) roomList.value = res.data;
  } catch (e) {}
};

// Modal Detail state
const selectedEvent = ref(null);
const openEventDetail = (ev) => {
  selectedEvent.value = ev;
};

// Modal Day Overflow state
const selectedDayOverflow = ref(null);
const openDayEventsModal = (day) => {
  selectedDayOverflow.value = day;
};

onMounted(() => {
  fetchEvents();
  if (authStore.user?.role !== 'ruangan') {
    fetchRooms();
  }
});
</script>
