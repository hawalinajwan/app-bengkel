<script setup>
defineProps({
  show: { type: Boolean, default: false },
  title: { type: String, default: 'Konfirmasi' },
  message: { type: String, default: '' },
  busy: { type: Boolean, default: false },
  confirmLabel: { type: String, default: 'Ya' }
})

const emit = defineEmits(['confirm', 'cancel'])
</script>

<template>
  <Teleport to="body">
    <div v-if="show">
      <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4"
        role="dialog"
        aria-modal="true"
      >
        <div class="w-full max-w-md rounded-service border border-line bg-white p-6 shadow-lg">
          <div class="mb-4 flex items-start justify-between gap-4">
            <h2 class="text-lg font-bold text-forest">{{ title }}</h2>
            <button
              type="button"
              class="text-2xl leading-none text-muted"
              aria-label="Tutup"
              :disabled="busy"
              @click="emit('cancel')"
            >
              ×
            </button>
          </div>
          <p class="mb-6 text-sm text-ink">{{ message }}</p>
          <div class="flex flex-wrap justify-end gap-2">
            <button type="button" class="service-muted" :disabled="busy" @click="emit('cancel')">
              Batal
            </button>
            <button type="button" class="service-danger" :disabled="busy" @click="emit('confirm')">
              {{ busy ? 'Memproses…' : confirmLabel }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
