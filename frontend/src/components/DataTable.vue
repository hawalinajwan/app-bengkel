<script setup>
defineProps({
  columns: { type: Array, required: true },
  rows: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  empty: { type: String, default: 'Belum ada data.' },
  rowKey: { type: String, default: 'id' },
})
</script>

<template>
  <div class="overflow-x-auto">
    <service-table class="service-service-table">
      <thead class="bg-forest-soft">
        <tr>
          <th v-for="col in columns" :key="col.key" scope="col">{{ col.label }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="loading">
          <td :colspan="columns.length" class="text-center py-6">
            <span class="spinner-border spinner-border-sm me-2"></span>Memuat…
          </td>
        </tr>
        <tr v-else-if="!rows.length">
          <td :colspan="columns.length" class="text-center text-muted py-6">{{ empty }}</td>
        </tr>
        <tr v-else v-for="row in rows" :key="row[rowKey]">
          <td v-for="col in columns" :key="col.key">
            <slot :name="col.key" :row="row">{{ row[col.key] }}</slot>
          </td>
        </tr>
      </tbody>
    </service-table>
  </div>
  <slot name="footer"></slot>
</template>
