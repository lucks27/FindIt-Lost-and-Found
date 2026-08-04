<template>
    <main class="admin-page-body">
        <header class="page-header">
            <div>
                <p class="page-kicker">Analytics & Reports</p>
                <h1>Review system performance at a glance</h1>
                <p class="page-copy">Track activity, compare item trends, and export compact report snapshots for admin
                    review.</p>
            </div>
            <button type="button" class="primary-button">Export report</button>
        </header>

        <section class="metric-grid" aria-label="Report summary metrics">
            <article class="metric-card">
                <span class="metric-label">Item activity</span>
                <strong class="metric-value">184</strong>
                <small>Events this window</small>
            </article>
            <article class="metric-card">
                <span class="metric-label">Claims processed</span>
                <strong class="metric-value">42</strong>
                <small>Resolved and logged</small>
            </article>
            <article class="metric-card">
                <span class="metric-label">Returns completed</span>
                <strong class="metric-value">21</strong>
                <small>Completed handovers</small>
            </article>
            <article class="metric-card">
                <span class="metric-label">Points awarded</span>
                <strong class="metric-value">314</strong>
                <small>Recognition activity</small>
            </article>
        </section>

        <section class="report-layout">
            <article class="report-card">
                <p class="card-label">Item activity</p>
                <strong>Weekly workflow trends</strong>
                <div class="bar-chart" aria-hidden="true">
                    <div class="bar-row"><span>Mon</span><i style="height: 38%"></i></div>
                    <div class="bar-row"><span>Tue</span><i style="height: 54%"></i></div>
                    <div class="bar-row"><span>Wed</span><i style="height: 64%"></i></div>
                    <div class="bar-row"><span>Thu</span><i style="height: 48%"></i></div>
                    <div class="bar-row"><span>Fri</span><i style="height: 76%"></i></div>
                </div>
            </article>
            <article class="report-card">
                <p class="card-label">Category mix</p>
                <strong>Items by category</strong>
                <div class="legend-list">
                    <div><span class="dot dot-gold"></span>Electronics</div>
                    <div><span class="dot dot-green"></span>Documents</div>
                    <div><span class="dot dot-muted"></span>Accessories</div>
                </div>
            </article>
            <article class="report-card wide-card">
                <p class="card-label">Recent activity</p>
                <strong>Latest operational updates</strong>
                <div class="table-shell">
                    <div class="activity-row"><span>Claim reviewed</span><strong>12 min ago</strong></div>
                    <div class="activity-row"><span>Item approved for release</span><strong>28 min ago</strong></div>
                    <div class="activity-row"><span>Notification sent</span><strong>54 min ago</strong></div>
                </div>
            </article>
        </section>
    </main>
</template>

<script setup>
</script>

<style scoped>
.admin-page-body {
    min-height: calc(100vh - 60px);
    padding: 24px;
    color: #eef1e5;
    background: radial-gradient(circle at top left, rgba(80, 130, 82, 0.16), transparent 30%), #0f2112;
}

.page-header,
.report-card,
.metric-card {
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.04);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.16);
}

.page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    padding: 20px 22px;
    border-radius: 20px;
    margin-bottom: 18px;
}

.page-kicker {
    margin: 0 0 8px;
    color: #c1b37a;
    font-size: 0.78rem;
    letter-spacing: 0.18em;
    text-transform: uppercase;
}

.page-header h1 {
    margin: 0;
    font-size: clamp(1.9rem, 3vw, 2.6rem);
    color: #fbf7eb;
}

.page-copy {
    margin: 8px 0 0;
    max-width: 720px;
    color: #bcc0a7;
    line-height: 1.6;
}

.primary-button {
    border: none;
    border-radius: 14px;
    padding: 0.86rem 1.2rem;
    background: #c49d3b;
    color: #131b10;
    font-weight: 700;
    cursor: pointer;
}

.metric-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}

.metric-card {
    padding: 18px;
    border-radius: 18px;
}

.metric-label {
    display: block;
    margin-bottom: 10px;
    color: #b8be9d;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
}

.metric-value {
    display: block;
    margin-bottom: 6px;
    font-size: 1.8rem;
    color: #fbf7eb;
}

.metric-card small {
    color: #9fa58d;
}

.report-layout {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.report-card {
    padding: 20px;
    border-radius: 20px;
}

.report-card.wide-card {
    grid-column: span 2;
}

.card-label {
    margin: 0 0 12px;
    color: #c8b16f;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    font-size: 0.76rem;
}

.report-card strong {
    display: block;
    margin-bottom: 14px;
    color: #fbf7eb;
}

.bar-chart {
    display: flex;
    align-items: flex-end;
    gap: 10px;
    height: 150px;
}

.bar-row {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: #aeb5a2;
    font-size: 0.78rem;
}

.bar-row i {
    width: 100%;
    max-width: 42px;
    border-radius: 12px 12px 4px 4px;
    background: linear-gradient(180deg, #d5b35b, #8c6a1e);
    display: block;
}

.legend-list {
    display: grid;
    gap: 12px;
    color: #cdd2ba;
}

.dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 10px;
}

.dot-gold {
    background: #c49d3b;
}

.dot-green {
    background: #6ba56e;
}

.dot-muted {
    background: #91a086;
}

.table-shell {
    display: grid;
    gap: 8px;
}

.activity-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    color: #d0d5bf;
}

.activity-row:last-child {
    border-bottom: none;
}

.activity-row strong {
    margin: 0;
    font-size: 0.92rem;
    color: #f7e2a5;
}

@media (max-width: 900px) {

    .metric-grid,
    .report-layout {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .page-header {
        flex-direction: column;
        align-items: stretch;
    }
}

@media (max-width: 640px) {

    .metric-grid,
    .report-layout {
        grid-template-columns: 1fr;
    }

    .report-card.wide-card {
        grid-column: span 1;
    }

    .admin-page-body {
        padding: 16px;
    }
}
</style>
