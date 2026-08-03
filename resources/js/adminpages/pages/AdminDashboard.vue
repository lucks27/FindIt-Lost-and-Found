<template>
    <div class="dashboard-page">
        <div class="page-bg"></div>

        <div class="dashboard-content">
            <header class="dashboard-topbar">
                <div class="hero-copy">
                    <p class="page-label">Dashboard</p>
                    <h1>Welcome back, Super Admin! <span>👋</span></h1>
                    <p class="page-description">Here’s what’s happening in the system today.</p>
                </div>
            </header>

            <section class="stats-grid">
                <article class="stat-card accent-1">
                    <p>Pending Surrender</p>
                    <strong>{{ stats.pendingSurrender }}</strong>
                    <small>View reports →</small>
                </article>
                <article class="stat-card accent-2">
                    <p>Items Received</p>
                    <strong>{{ stats.itemsReceived }}</strong>
                    <small>View items →</small>
                </article>
                <article class="stat-card accent-3">
                    <p>Awaiting Verification</p>
                    <strong>{{ stats.awaitingVerification }}</strong>
                    <small>View items →</small>
                </article>
                <article class="stat-card accent-4">
                    <p>Verified Items</p>
                    <strong>{{ stats.verifiedItems }}</strong>
                    <small>View items →</small>
                </article>
                <article class="stat-card accent-5">
                    <p>Potential Matches</p>
                    <strong>{{ stats.potentialMatches }}</strong>
                    <small>View matches →</small>
                </article>
                <article class="stat-card accent-6">
                    <p>Pending Claims</p>
                    <strong>{{ stats.pendingClaims }}</strong>
                    <small>Review claims →</small>
                </article>
                <article class="stat-card accent-7">
                    <p>Ready for Release</p>
                    <strong>{{ stats.readyForRelease }}</strong>
                    <small>View items →</small>
                </article>
                <article class="stat-card accent-8">
                    <p>Returned Items</p>
                    <strong>{{ stats.returnedItems }}</strong>
                    <small>View history →</small>
                </article>
            </section>

            <section class="dashboard-panels">
                <div class="panel overview-panel">
                    <div class="panel-header">
                        <div>
                            <p class="panel-label">Reports Overview</p>
                            <h2>128 Total Reports</h2>
                        </div>
                        <button class="panel-button">This Month</button>
                    </div>
                    <div class="overview-chart">
                        <div class="donut" aria-label="Report distribution"></div>
                        <div class="overview-metrics">
                            <div><span class="metric-dot lost"></span>Lost Reports <strong>64 (50%)</strong></div>
                            <div><span class="metric-dot found"></span>Found Reports <strong>54 (42%)</strong></div>
                            <div><span class="metric-dot verified"></span>Verified Items <strong>45 (35%)</strong>
                            </div>
                            <div><span class="metric-dot returned"></span>Returned Items <strong>32 (25%)</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel activity-panel">
                    <div class="panel-header">
                        <div>
                            <p class="panel-label">Recent Activity</p>
                            <h2>Live updates</h2>
                        </div>
                        <button class="panel-button secondary">View All</button>
                    </div>
                    <ul class="activity-list">
                        <li>
                            <span class="activity-badge blue"></span>
                            <div>
                                <p>Item received from Juan Dela Cruz</p>
                                <small>Black Wallet • INV-2025-0056</small>
                            </div>
                            <span>10:24 AM</span>
                        </li>
                        <li>
                            <span class="activity-badge orange"></span>
                            <div>
                                <p>New claim submitted by Maria Santos</p>
                                <small>ITEM-2025-0045</small>
                            </div>
                            <span>09:15 AM</span>
                        </li>
                        <li>
                            <span class="activity-badge green"></span>
                            <div>
                                <p>Claim approved for John Paul</p>
                                <small>ITEM-2025-0032</small>
                            </div>
                            <span>Yesterday</span>
                        </li>
                        <li>
                            <span class="activity-badge gold"></span>
                            <div>
                                <p>Points awarded to Ana Reyes</p>
                                <small>+100 Points</small>
                            </div>
                            <span>Yesterday</span>
                        </li>
                        <li>
                            <span class="activity-badge purple"></span>
                            <div>
                                <p>New potential match found</p>
                                <small>ITEM-2025-0048</small>
                            </div>
                            <span>May 23</span>
                        </li>
                        <li>
                            <span class="activity-badge teal"></span>
                            <div>
                                <p>Announcement sent to all students</p>
                                <small>General Assembly Reminder</small>
                            </div>
                            <span>May 22</span>
                        </li>
                    </ul>
                </div>

                <div class="panel quick-actions-panel">
                    <div class="panel-header">
                        <div>
                            <p class="panel-label">Quick Actions</p>
                            <h2>Take action</h2>
                        </div>
                    </div>
                    <div class="quick-actions-grid">
                        <button type="button" class="action-card">Add Student</button>
                        <button type="button" class="action-card">Verify Item</button>
                        <button type="button" class="action-card">Review Claims</button>
                        <button type="button" class="action-card">Send Announcement</button>
                        <button type="button" class="action-card">Award Points</button>
                        <button type="button" class="action-card">View Inventory</button>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup>
import { reactive } from 'vue'

const stats = reactive({
    pendingSurrender: 0,
    itemsReceived: 0,
    awaitingVerification: 0,
    verifiedItems: 0,
    potentialMatches: 0,
    pendingClaims: 0,
    readyForRelease: 0,
    returnedItems: 0,
})
</script>

<style scoped>
.dashboard-page {
    position: relative;
    min-height: 100%;
    height: 100%;
    padding: 24px 28px;
    background: radial-gradient(circle at top left, rgba(28, 79, 41, 0.22), transparent 24%),
        radial-gradient(circle at bottom right, rgba(43, 112, 64, 0.25), transparent 28%),
        linear-gradient(180deg, #03140f 0%, #081f14 45%, #0d2717 100%);
    color: #eef4e8;
    overflow: auto;
}

.page-bg {
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 10% 10%, rgba(247, 242, 230, 0.12), transparent 22%),
        radial-gradient(circle at 92% 15%, rgba(255, 255, 255, 0.08), transparent 18%);
    pointer-events: none;
    z-index: 0;
}

.dashboard-content {
    position: relative;
    z-index: 1;
    display: grid;
    gap: 28px;
    max-width: 1280px;
    margin: 0 auto;
    min-height: 100%;
    height: 100%;
    overflow: auto;
}

.dashboard-topbar {
    display: grid;
    grid-template-columns: 1.3fr 0.9fr;
    gap: 24px;
    align-items: center;
}

.dashboard-panels {
    display: grid;
    grid-template-columns: 1.3fr 0.9fr;
    gap: 24px;
    min-height: 0;
    overflow: hidden;
}

.hero-copy {
    display: grid;
    gap: 16px;
}

.page-label {
    margin: 0;
    color: rgba(217, 185, 104, 0.86);
    letter-spacing: 0.24em;
    text-transform: uppercase;
    font-size: 12px;
}

.dashboard-topbar h1 {
    margin: 0;
    font-size: clamp(30px, 3vw, 44px);
    line-height: 1.08;
}

.page-description {
    margin: 0;
    max-width: 700px;
    color: rgba(255, 255, 255, 0.78);
    font-size: 15px;
    line-height: 1.7;
}

.topbar-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 16px;
    flex-wrap: wrap;
}

.search-box {
    flex: 1;
    min-width: 240px;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.08);
    color: #fff;
}

.search-box svg {
    width: 20px;
    height: 20px;
    fill: rgba(255, 255, 255, 0.72);
}

.search-box input {
    width: 100%;
    border: none;
    background: transparent;
    color: #fff;
    outline: none;
    font-size: 15px;
}

.icon-button {
    position: relative;
    width: 56px;
    height: 56px;
    min-width: 56px;
    border-radius: 18px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.06);
    color: #fff;
    cursor: pointer;
    display: grid;
    place-items: center;
}

.icon-button svg {
    width: 24px;
    height: 24px;
    fill: #fff;
    opacity: 0.85;
}

.notification-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 22px;
    height: 22px;
    border-radius: 999px;
    background: #d8b24d;
    color: #10261e;
    display: grid;
    place-items: center;
    font-size: 12px;
    font-weight: 800;
}

.profile-chip {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 18px;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.08);
    min-width: 260px;
}

.avatar {
    width: 48px;
    height: 48px;
    border-radius: 16px;
    background: linear-gradient(135deg, #d8b24d, #c79e3a);
    display: grid;
    place-items: center;
    font-weight: 800;
    color: #10261e;
}

.profile-text p {
    margin: 0;
    font-weight: 700;
    color: #f7f4e9;
}

.profile-text span {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.72);
}

.caret {
    margin-left: auto;
    color: rgba(255, 255, 255, 0.7);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
}

.stat-card {
    padding: 24px 22px;
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.06);
    box-shadow: 0 18px 48px rgba(0, 0, 0, 0.16);
    transition: transform 0.2s ease, border-color 0.2s ease, background 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
    background: rgba(255, 255, 255, 0.08);
}

.stat-card p {
    margin: 0 0 16px;
    color: rgba(255, 255, 255, 0.82);
    font-size: 13px;
}

.stat-card strong {
    display: block;
    margin-bottom: 10px;
    font-size: 36px;
}

.stat-card small {
    color: rgba(255, 255, 255, 0.7);
}

.accent-1 {
    border-color: rgba(216, 178, 77, 0.18);
}

.accent-2 {
    border-color: rgba(70, 182, 246, 0.18);
}

.accent-3 {
    border-color: rgba(24, 175, 132, 0.18);
}

.accent-4 {
    border-color: rgba(72, 126, 239, 0.18);
}

.accent-5 {
    border-color: rgba(113, 92, 255, 0.18);
}

.accent-6 {
    border-color: rgba(255, 163, 61, 0.18);
}

.accent-7 {
    border-color: rgba(79, 203, 183, 0.18);
}

.accent-8 {
    border-color: rgba(160, 113, 228, 0.18);
}

.dashboard-panels {
    display: grid;
    grid-template-columns: 1.3fr 0.9fr;
    gap: 24px;
}

.panel {
    padding: 28px;
    border-radius: 28px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.06);
    box-shadow: 0 18px 50px rgba(0, 0, 0, 0.16);
}

.panel-header {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    align-items: flex-start;
    margin-bottom: 26px;
}

.panel-header h2 {
    margin: 0;
    font-size: 18px;
    line-height: 1.2;
}

.panel-button {
    padding: 12px 18px;
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.08);
    color: #f7f4e9;
    cursor: pointer;
    min-width: 132px;
}

.panel-button.secondary {
    background: transparent;
}

.overview-chart {
    display: grid;
    grid-template-columns: 260px minmax(0, 1fr);
    gap: 24px;
    align-items: center;
}

.donut {
    width: 260px;
    height: 260px;
    border-radius: 50%;
    background: conic-gradient(rgba(217, 185, 104, 0.95) 0 50%,
            rgba(20, 205, 198, 0.85) 50% 86%,
            rgba(18, 115, 76, 0.85) 86% 100%);
    position: relative;
}

.donut::before {
    content: '';
    position: absolute;
    inset: 24px;
    border-radius: 50%;
    background: rgba(7, 19, 12, 0.94);
}

.overview-metrics {
    display: grid;
    gap: 16px;
}

.overview-metrics div {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    color: rgba(255, 255, 255, 0.82);
    font-size: 14px;
}

.metric-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 10px;
}

.metric-dot.lost {
    background: #d8b24d;
}

.metric-dot.found {
    background: #47b6f6;
}

.metric-dot.verified {
    background: #4ab46a;
}

.metric-dot.returned {
    background: #8cd8ca;
}

.activity-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: 16px;
}

.activity-list li {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 16px;
    padding: 18px;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.06);
    align-items: center;
}

.activity-badge {
    width: 14px;
    height: 14px;
    border-radius: 50%;
}

.activity-badge.blue {
    background: #47b6f6;
}

.activity-badge.orange {
    background: #f2a33d;
}

.activity-badge.green {
    background: #6bc07b;
}

.activity-badge.gold {
    background: #e4c167;
}

.activity-badge.purple {
    background: #9f7ce2;
}

.activity-badge.teal {
    background: #4dd5c4;
}

.activity-list p {
    margin: 0 0 6px;
    font-weight: 700;
}

.activity-list small {
    color: rgba(255, 255, 255, 0.72);
}

.activity-list span {
    color: rgba(255, 255, 255, 0.72);
    font-size: 13px;
}

.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
}

.action-card {
    min-height: 108px;
    padding: 24px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.04);
    color: #f7f4e9;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    transition: transform 0.2s ease, background 0.2s ease;
}

.action-card:hover {
    transform: translateY(-3px);
    background: rgba(255, 255, 255, 0.08);
}

@media (max-width: 1200px) {
    .stats-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .dashboard-panels {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 960px) {
    .dashboard-topbar {
        grid-template-columns: 1fr;
    }

    .topbar-actions {
        justify-content: stretch;
    }

    .search-box {
        min-width: 0;
        width: 100%;
    }

    .profile-chip {
        width: 100%;
    }

    .stats-grid,
    .dashboard-panels,
    .quick-actions-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .dashboard-page {
        padding: 20px 18px;
    }

    .panel,
    .stat-card {
        padding: 22px;
    }

    .dashboard-topbar {
        gap: 18px;
    }
}
</style>
