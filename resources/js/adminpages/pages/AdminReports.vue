<template>
    <main class="admin-page-body">
        <header class="page-header">
            <p class="page-kicker">Analytics & Reports</p>
            <p class="page-copy">Review system activity, lost item trends, and report summaries with export options.</p>
        </header>

        <section class="report-layout">
            <article class="report-card">
                <p class="card-label">Activity Overview</p>
                <strong>Admin workflow insights</strong>
                <p class="card-copy">Monitor recent sign-ins, item processing, and communication events across the
                    portal.</p>
            </article>
            <article class="report-card">
                <p class="card-label">Lost & Found Trends</p>
                <strong>Item volume analysis</strong>
                <p class="card-copy">See how many surrendered items, matches, and releases occurred over time.</p>
            </article>
            <article class="report-card wide-card">
                <p class="card-label">Export Reports</p>
                <strong>Download audit and summary reports</strong>
                <p class="card-copy">Generate CSV or PDF summaries for audit logs, claims, and communication activity.
                </p>
            </article>
        </section>
    </main>
</template>

<script setup>
</script>

<style scoped>
.admin-page-body {
    padding: 24px;
    color: #f8f4ea;
    min-height: calc(100vh - 60px);
    background: transparent;
}

.page-header {
    margin-bottom: 28px;
}

.page-kicker {
    margin: 0 0 10px;
    font-size: 12px;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: rgba(248, 244, 234, 0.72);
}

.page-copy {
    margin: 0;
    color: rgba(248, 244, 234, 0.82);
    max-width: 720px;
    line-height: 1.7;
}

.report-layout {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
}

.report-card {
    padding: 26px;
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.12);
    box-shadow: 0 20px 36px rgba(0, 0, 0, 0.18);
}

.report-card.wide-card {
    grid-column: span 2;
}

.card-label {
    margin: 0 0 12px;
    font-size: 12px;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: rgba(243, 212, 106, 0.9);
}

.report-card strong {
    display: block;
    margin-bottom: 14px;
    font-size: 20px;
    color: #fff;
}

.card-copy {
    margin: 0;
    color: rgba(255, 255, 255, 0.78);
    line-height: 1.7;
}
</style>
