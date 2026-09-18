<template>
  <div class="relative w-full text-left" ref="containerRef">
    <!-- Trigger Button (Styled as standard form input) -->
    <button
      type="button"
      @click="toggleDropdown"
      :disabled="disabled"
      :class="[
        'w-full px-3 py-2 text-xs border rounded-xl flex items-center justify-between gap-2 transition-all text-left outline-none cursor-pointer',
        isOpen 
          ? 'border-emerald-500 ring-2 ring-emerald-500/20 bg-white' 
          : 'border-slate-300 hover:border-slate-400 bg-white',
        disabled ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'text-slate-800'
      ]"
    >
      <div class="truncate flex-1">
        <span v-if="selectedOption" class="font-semibold text-slate-800 flex items-center gap-1.5 truncate">
          <span>{{ getOptionLabel(selectedOption) }}</span>
          <span v-if="getOptionSublabel(selectedOption)" class="text-[10px] text-slate-400 font-normal truncate">
            ({{ getOptionSublabel(selectedOption) }})
          </span>
        </span>
        <span v-else class="text-slate-400">{{ placeholder }}</span>
      </div>

      <div class="flex items-center gap-1 shrink-0">
        <button
          v-if="allowClear && modelValue !== '' && modelValue !== null && modelValue !== undefined"
          type="button"
          @click.stop="clearSelection"
          class="p-0.5 text-slate-400 hover:text-slate-700 rounded-full transition-colors cursor-pointer"
          title="Hapus pilihan"
        >
          <X class="w-3.5 h-3.5" />
        </button>
        <ChevronDown 
          :class="[
            'w-4 h-4 text-slate-400 transition-transform duration-200',
            isOpen ? 'rotate-180 text-emerald-600' : ''
          ]" 
        />
      </div>
    </button>

    <!-- Floating Dropdown Menu -->
    <div
      v-if="isOpen"
      class="absolute left-0 right-0 top-full mt-1.5 z-50 bg-white rounded-xl shadow-xl border border-slate-200/90 overflow-hidden animate-in fade-in zoom-in-95 duration-150"
    >
      <!-- Search Input Header -->
      <div class="p-2 border-b border-slate-100 bg-slate-50/70">
        <div class="relative flex items-center">
          <Search class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 pointer-events-none" />
          <input
            ref="searchInputRef"
            v-model="searchQuery"
            type="text"
            :placeholder="searchPlaceholder"
            class="w-full pl-8 pr-3 py-1.5 text-xs bg-white border border-slate-200 rounded-lg focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
            @keydown.esc.stop="closeDropdown"
            @keydown.enter.prevent="selectFirstMatch"
          />
          <button
            v-if="searchQuery"
            type="button"
            @click="searchQuery = ''"
            class="absolute right-2 text-slate-400 hover:text-slate-600 cursor-pointer"
          >
            <X class="w-3 h-3" />
          </button>
        </div>
      </div>

      <!-- Options List -->
      <div class="max-h-56 overflow-y-auto divide-y divide-slate-50 py-1 text-xs">
        <!-- Option: All / Clear (if provided as special item) -->
        <button
          v-if="showAllOption"
          type="button"
          @click="selectOption({ [valueKey]: '', [labelKey]: allOptionLabel })"
          :class="[
            'w-full px-3 py-2 text-left flex items-center justify-between transition-colors cursor-pointer',
            modelValue === '' || modelValue === null ? 'bg-emerald-50/80 text-emerald-800 font-bold' : 'text-slate-600 hover:bg-slate-50'
          ]"
        >
          <span>{{ allOptionLabel }}</span>
          <Check v-if="modelValue === '' || modelValue === null" class="w-3.5 h-3.5 text-emerald-600" />
        </button>

        <button
          v-for="(opt, idx) in filteredOptions"
          :key="getOptionValue(opt) ?? idx"
          type="button"
          @click="selectOption(opt)"
          :class="[
            'w-full px-3 py-2 text-left flex items-center justify-between gap-2 transition-colors cursor-pointer',
            isSelected(opt) ? 'bg-emerald-50 text-emerald-900 font-bold' : 'text-slate-700 hover:bg-slate-50'
          ]"
        >
          <div class="truncate flex-1">
            <div class="truncate text-xs font-semibold">
              {{ getOptionLabel(opt) }}
            </div>
            <div v-if="getOptionSublabel(opt)" class="text-[10px] text-slate-400 truncate mt-0.5">
              {{ getOptionSublabel(opt) }}
            </div>
          </div>
          <Check v-if="isSelected(opt)" class="w-4 h-4 text-emerald-600 shrink-0" />
        </button>

        <!-- Empty State -->
        <div v-if="filteredOptions.length === 0" class="py-6 px-3 text-center text-slate-400 text-xs">
          Tidak ada data yang cocok dengan "<span class="font-semibold text-slate-600">{{ searchQuery }}</span>"
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { Search, ChevronDown, Check, X } from 'lucide-vue-next';

const props = defineProps({
  modelValue: {
    type: [String, Number, null],
    default: ''
  },
  options: {
    type: Array,
    default: () => []
  },
  valueKey: {
    type: String,
    default: 'id'
  },
  labelKey: {
    type: String,
    default: 'name'
  },
  sublabelKey: {
    type: String,
    default: ''
  },
  formatLabel: {
    type: Function,
    default: null
  },
  formatSublabel: {
    type: Function,
    default: null
  },
  placeholder: {
    type: String,
    default: 'Pilih data...'
  },
  searchPlaceholder: {
    type: String,
    default: 'Ketik untuk mencari...'
  },
  disabled: {
    type: Boolean,
    default: false
  },
  allowClear: {
    type: Boolean,
    default: false
  },
  showAllOption: {
    type: Boolean,
    default: false
  },
  allOptionLabel: {
    type: String,
    default: 'Semua Data'
  }
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const searchQuery = ref('');
const containerRef = ref(null);
const searchInputRef = ref(null);

const getOptionValue = (opt) => {
  if (typeof opt === 'object' && opt !== null) {
    return opt[props.valueKey];
  }
  return opt;
};

const getOptionLabel = (opt) => {
  if (!opt) return '';
  if (props.formatLabel) {
    return props.formatLabel(opt);
  }
  if (typeof opt === 'object' && opt !== null) {
    return opt[props.labelKey] || '';
  }
  return String(opt);
};

const getOptionSublabel = (opt) => {
  if (!opt) return '';
  if (props.formatSublabel) {
    return props.formatSublabel(opt);
  }
  if (props.sublabelKey && typeof opt === 'object' && opt !== null) {
    return opt[props.sublabelKey] || '';
  }
  return '';
};

const isSelected = (opt) => {
  const val = getOptionValue(opt);
  return String(val) === String(props.modelValue);
};

const selectedOption = computed(() => {
  if (props.modelValue === '' || props.modelValue === null || props.modelValue === undefined) {
    return null;
  }
  return props.options.find(opt => String(getOptionValue(opt)) === String(props.modelValue)) || null;
});

const filteredOptions = computed(() => {
  if (!searchQuery.value.trim()) {
    return props.options;
  }
  const q = searchQuery.value.toLowerCase().trim();
  return props.options.filter(opt => {
    const label = String(getOptionLabel(opt)).toLowerCase();
    const sublabel = String(getOptionSublabel(opt)).toLowerCase();
    const val = String(getOptionValue(opt)).toLowerCase();
    return label.includes(q) || sublabel.includes(q) || val.includes(q);
  });
});

const toggleDropdown = async () => {
  if (props.disabled) return;
  isOpen.value = !isOpen.value;
  if (isOpen.value) {
    searchQuery.value = '';
    await nextTick();
    searchInputRef.value?.focus();
  }
};

const closeDropdown = () => {
  isOpen.value = false;
  searchQuery.value = '';
};

const selectOption = (opt) => {
  const val = getOptionValue(opt);
  emit('update:modelValue', val);
  emit('change', val, opt);
  closeDropdown();
};

const selectFirstMatch = () => {
  if (filteredOptions.value.length > 0) {
    selectOption(filteredOptions.value[0]);
  }
};

const clearSelection = () => {
  emit('update:modelValue', '');
  emit('change', '', null);
};

const handleClickOutside = (e) => {
  if (containerRef.value && !containerRef.value.contains(e.target)) {
    closeDropdown();
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>
