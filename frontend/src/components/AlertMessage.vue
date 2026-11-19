<script setup>
defineProps({
  message: { type: String, default: '' },
})
</script>

<template>
  <div v-if="message" class="service-alert py-2" role="alert">{{ message }}</div>
</template>
