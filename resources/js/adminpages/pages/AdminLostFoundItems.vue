<template>
    <section class="admin-page-shell">
        <header class="page-header">
            <p class="page-kicker">Lost and Found</p>
            <h1>Lost &amp; Found Items</h1>
            <p class="page-copy">Browse all currently recorded items, manage their status, and track location or claim details.</p>
        </header>

        <div class="page-card">
            <p class="placeholder-text">This page will show the entire item inventory with filters for item type, status, and claim state.</p>
        </div>
    </section>
</template>

<script setup>
</script>

<style scoped>
.admin-page-shell {
    padding: 24px;
    color: #f8f4ea;
    min-height: calc(100vh - 60px);
    background: transparent;
}

.page-header {
    margin-bottom: 24px;
}

.page-kicker {
    margin: 0 0 10px;
    font-size: 12px;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: rgba(248, 244, 234, 0.72);
}

.page-header h1 {
    margin: 0;
    font-size: clamp(30px, 4vw, 42px);
    color: #fff;
}

.page-copy {
    margin: 12px 0 0;
    color: rgba(248, 244, 234, 0.78);
    max-width: 760px;
    line-height: 1.6;
}

.page-card {
    padding: 28px;
    border-radius: 26px;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.12);
    box-shadow: 0 24px 44px rgba(0, 0, 0, 0.24);
}

.placeholder-text {
    margin: 0;
    color: rgba(248, 244, 234, 0.78);
    line-height: 1.75;
}
</style>
