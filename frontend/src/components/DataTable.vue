<script setup>
defineProps({
  columns: { type: Array, required: true },
  rows: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  empty: { type: String, default: 'Belum ada data.' },
  rowKey: { type: String, default: 'id' }
})
</script>

<template>
  <div class="overflow-x-auto">
    <table class="service-table">
      <thead class="bg-forest-soft">
        <tr>
          <th v-for="col in columns" :key="col.key" scope="col">{{ col.label }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="loading">
          <td :colspan="columns.length" class="text-center py-6">
            <span
              class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-forest border-t-transparent align-middle"
            ></span
            >Memuat…
          </td>
        </tr>
        <tr v-else-if="!rows.length">
          <td :colspan="columns.length" class="py-6 text-center text-muted">{{ empty }}</td>
        </tr>
        <tr v-else v-for="row in rows" :key="row[rowKey]">
          <td v-for="col in columns" :key="col.key">
            <slot :name="col.key" :row="row">{{ row[col.key] }}</slot>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  <slot name="footer"></slot>
</template>
