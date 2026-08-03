<template>
  <div class="student-spa-container">
    <StudentNavbar />

    <main class="spa-content">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import StudentNavbar from '../js/components/StudentNavbar.vue';
</script>

<style scoped>
.spa-content {
  /* This pushes content down so it's not under your fixed green topbar */
  margin-top: 120px; 
}
</style>