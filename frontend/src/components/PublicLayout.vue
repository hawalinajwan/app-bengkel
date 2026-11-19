<template>
  <div class="public-layout app-shell">
    <header class="public-header px-3 py-2">
      <nav class="container d-flex align-items-center justify-content-between" aria-label="Navigasi publik">
        <RouterLink class="fw-bold text-decoration-none" to="/track">Bengkel Anda</RouterLink>
        <RouterLink class="text-decoration-none" to="/booking">Booking servis</RouterLink>
      </nav>
    </header>
    <main class="container py-4"><slot /></main>
  </div>
</template>
