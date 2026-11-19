<script setup>
defineProps({
  show: { type: Boolean, default: false },
  title: { type: String, default: 'Konfirmasi' },
  message: { type: String, default: '' },
  busy: { type: Boolean, default: false },
  confirmLabel: { type: String, default: 'Ya' },
})

const emit = defineEmits(['confirm', 'cancel'])
</script>

<template>
  <Teleport to="body">
    <div v-if="show">
      <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">{{ title }}</h5>
              <button type="button" class="btn-close" aria-label="Tutup" :disabled="busy" @click="emit('cancel')"></button>
            </div>
            <div class="modal-body">
              <p class="mb-0">{{ message }}</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" :disabled="busy" @click="emit('cancel')">Batal</button>
              <button type="button" class="btn btn-danger" :disabled="busy" @click="emit('confirm')">
                {{ busy ? 'Memproses…' : confirmLabel }}
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-backdrop fade show"></div>
    </div>
  </Teleport>
</template>
