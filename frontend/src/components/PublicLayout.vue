<template>
  <div class="public-layout app-shell">
    <header class="public-header px-3 py-2">
      <nav class="mx-auto flex max-w-7xl items-center justify-between px-4" aria-label="Navigasi publik">
        <RouterLink class="fw-bold text-decoration-none" to="/track">Bengkel Anda</RouterLink>
        <RouterLink class="text-decoration-none" to="/booking">Booking servis</RouterLink>
      </nav>
    </header>
    <main class="mx-auto max-w-7xl px-4 py-6"><slot /></main>
  </div>
</template>
