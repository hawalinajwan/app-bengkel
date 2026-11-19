export function rupiah(value) {
  return `Rp ${Number(value ?? 0).toLocaleString('id-ID')}`
}
