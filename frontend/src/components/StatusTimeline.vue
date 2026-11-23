<script setup>
defineProps({ items: { type: Array, default: () => [] } })

function formatTime(value) {
  return value
    ? new Date(value).toLocaleString('id-ID', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
      })
    : ''
}
</script>

<template>
  <ol class="grid gap-3 border-l-2 border-line pl-5">
    <li v-for="item in items" :key="item.at" class="relative">
      <span
        class="absolute -left-[27px] top-1.5 h-3 w-3 rounded-full border-2 border-forest bg-white"
        aria-hidden="true"
      />
      <p class="font-semibold capitalize text-ink">{{ item.status }}</p>
      <p class="text-xs text-muted">{{ formatTime(item.at) }}</p>
    </li>
  </ol>
</template>
