<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import client from '@/api/client'
import AlertMessage from '@/components/AlertMessage.vue'
import ConfirmModal from '@/components/ConfirmModal.vue'
import DataTable from '@/components/DataTable.vue'

const props = defineProps({
  title: { type: String, required: true },
  endpoint: { type: String, required: true },
  columns: { type: Array, required: true },
  fields: { type: Array, required: true },
})

const rows = ref([])
const meta = ref(null)
const loading = ref(false)
const saving = ref(false)
const deleting = ref(false)
const error = ref('')
const notice = ref('')
const search = ref('')
const page = ref(1)
const editingId = ref(null)
const confirmRow = ref(null)
const form = reactive({})

const dataColumns = computed(() => props.columns.filter((col) => col.key !== 'actions'))

function defaults() {
  const value = {}
  for (const field of props.fields) {
    value[field.key] = field.default ?? (field.type === 'checkbox' ? false : '')
  }
  return value
}

function resetForm() {
  Object.assign(form, defaults())
  editingId.value = null
}

function edit(row) {
  editingId.value = row.id
  for (const field of props.fields) {
    form[field.key] = row[field.key] ?? (field.type === 'checkbox' ? false : '')
  }
  notice.value = ''
  error.value = ''
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await client.get(`/${props.endpoint}`, {
      params: { page: page.value, per_page: 10, search: search.value || undefined },
    })
    rows.value = data.data
    meta.value = data.meta
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal memuat data.'
  } finally {
    loading.value = false
  }
}

async function submit() {
  saving.value = true
  error.value = ''
  notice.value = ''
  const editing = Boolean(editingId.value)
  try {
    if (editing) await client.put(`/${props.endpoint}/${editingId.value}`, { ...form })
    else await client.post(`/${props.endpoint}`, { ...form })
    notice.value = editing ? 'Data berhasil diperbarui.' : 'Data berhasil ditambahkan.'
    resetForm()
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal menyimpan data.'
  } finally {
    saving.value = false
  }
}

async function destroy() {
  deleting.value = true
  error.value = ''
  notice.value = ''
  try {
    await client.delete(`/${props.endpoint}/${confirmRow.value.id}`)
    confirmRow.value = null
    notice.value = 'Data berhasil dihapus.'
    if (rows.value.length === 1 && page.value > 1) page.value -= 1
    else await load()
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal menghapus data.'
  } finally {
    deleting.value = false
  }
}

let searchTimer
watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    page.value = 1
    load()
  }, 350)
})
watch(page, load)

resetForm()
onMounted(load)
</script>

<template>
  <div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
      <h1 class="h4 mb-0">{{ title }}</h1>
      <input
        v-model="search"
        type="search"
        class="form-control form-control-sm"
        style="max-width: 16rem"
        placeholder="Cari…"
      />
    </div>

    <AlertMessage :message="error" />
    <div v-if="notice" class="alert alert-success py-2">{{ notice }}</div>

    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <h2 class="h6 mb-3">{{ editingId ? 'Ubah data' : 'Tambah data' }}</h2>
        <form class="row g-3" @submit.prevent="submit">
          <div v-for="field in fields" :key="field.key" class="col-md-4">
            <label v-if="field.type !== 'checkbox'" class="form-label" :for="field.key">{{ field.label }}</label>

            <select
              v-if="field.type === 'select'"
              :id="field.key"
              v-model="form[field.key]"
              class="form-select"
              :required="field.required"
            >
              <option value="">Pilih {{ field.label }}</option>
              <option v-for="opt in field.options || []" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>

            <textarea
              v-else-if="field.type === 'textarea'"
              :id="field.key"
              v-model="form[field.key]"
              class="form-control"
              :required="field.required"
              rows="2"
            ></textarea>

            <div v-else-if="field.type === 'checkbox'" class="form-check mt-4">
              <input :id="field.key" v-model="form[field.key]" class="form-check-input" type="checkbox" />
              <label class="form-check-label" :for="field.key">{{ field.label }}</label>
            </div>

            <input
              v-else
              :id="field.key"
              v-model="form[field.key]"
              :type="field.type || 'text'"
              class="form-control"
              :required="field.required"
            />
          </div>

          <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary" type="submit" :disabled="saving">
              {{ saving ? 'Menyimpan…' : editingId ? 'Simpan perubahan' : 'Tambah' }}
            </button>
            <button v-if="editingId" class="btn btn-outline-secondary" type="button" @click="resetForm">Batal</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card shadow-sm">
      <DataTable :columns="columns" :rows="rows" :loading="loading">
        <template v-for="col in dataColumns" :key="col.key" #[col.key]="{ row }">
          <slot :name="col.key" :row="row">{{ row[col.key] }}</slot>
        </template>

        <template #actions="{ row }">
          <button class="btn btn-sm btn-outline-primary me-1" type="button" @click="edit(row)">Ubah</button>
          <button class="btn btn-sm btn-outline-danger" type="button" @click="confirmRow = row">Hapus</button>
        </template>

        <template #footer>
          <div v-if="meta" class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-3 py-2 border-top">
            <span class="small text-secondary">
              Halaman {{ meta.current_page }} dari {{ meta.last_page }} · {{ meta.total }} data
            </span>
            <div class="btn-group">
              <button
                class="btn btn-sm btn-outline-secondary"
                type="button"
                :disabled="meta.current_page <= 1 || loading"
                @click="page -= 1"
              >
                Sebelumnya
              </button>
              <button
                class="btn btn-sm btn-outline-secondary"
                type="button"
                :disabled="meta.current_page >= meta.last_page || loading"
                @click="page += 1"
              >
                Berikutnya
              </button>
            </div>
          </div>
        </template>
      </DataTable>
    </div>

    <ConfirmModal
      :show="Boolean(confirmRow)"
      title="Hapus data"
      message="Data yang dihapus tidak dapat dikembalikan. Lanjutkan?"
      confirm-label="Hapus"
      :busy="deleting"
      @confirm="destroy"
      @cancel="confirmRow = null"
    />
  </div>
</template>
