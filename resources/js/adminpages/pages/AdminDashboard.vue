<template>
    <div class="dashboard-page">
        <div class="page-bg"></div>

        <div class="dashboard-content">
            <header class="dashboard-topbar">
                <div class="hero-copy">
                    <p class="page-label">Analytics Dashboard</p>
                    <h1>Super Admin Insights</h1>
                    <p class="page-description">
                        Monitor report flow, verification progress, and the most active item categories in one polished
                        view.
                    </p>
                </div>

                <div class="topbar-actions">
                    <router-link class="panel-button" :to="{ name: 'superadmin.reports' }">View Reports</router-link>
                </div>
            </header>

            <section class="count-grid">
                <article class="count-card">
                    <div class="count-top">
                        <div class="icon-holder icon-gold">
                            <svg viewBox="0 0 24 24">
                                <path
                                    d="M20 6H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2zM4 8h16v2H4V8zm0 4h10v6H4v-6zm12 0h4v6h-4v-6z" />
                            </svg>
                        </div>
                        <strong>12</strong>
                    </div>
                    <p>Pending surrender</p>
                    <router-link class="count-link" :to="{ name: 'superadmin.reports' }">
                        View reports <svg viewBox="0 0 24 24">
                            <path d="M13 5l7 7-7 7M5 12h14" />
                        </svg>
                    </router-link>
                </article>

                <article class="count-card">
                    <div class="count-top">
                        <div class="icon-holder icon-blue">
                            <svg viewBox="0 0 24 24">
                                <path d="M6 3l12 5v10l-12 5L4 17V7l2-4zm1.5 3.2v11.6L15 18V6L7.5 6.2z" />
                            </svg>
                        </div>
                        <strong>8</strong>
                    </div>
                    <p>Items received</p>
                    <router-link class="count-link" :to="{ name: 'superadmin.items' }">
                        View items <svg viewBox="0 0 24 24">
                            <path d="M13 5l7 7-7 7M5 12h14" />
                        </svg>
                    </router-link>
                </article>

                <article class="count-card">
                    <div class="count-top">
                        <div class="icon-holder icon-purple">
                            <svg viewBox="0 0 24 24">
                                <path
                                    d="M12 2l7 4v6c0 5-4 9-7 9s-7-4-7-9V6l7-4zm0 2.18L6 7.05V12c0 3.78 2.69 6.95 6 7.9 3.31-.95 6-4.12 6-7.9V7.05l-6-2.87z" />
                            </svg>
                        </div>
                        <strong>6</strong>
                    </div>
                    <p>Awaiting verification</p>
                    <router-link class="count-link" :to="{ name: 'superadmin.item-verification' }">
                        View items <svg viewBox="0 0 24 24">
                            <path d="M13 5l7 7-7 7M5 12h14" />
                        </svg>
                    </router-link>
                </article>

                <article class="count-card">
                    <div class="count-top">
                        <div class="icon-holder icon-green">
                            <svg viewBox="0 0 24 24">
                                <path d="M9 12l2 2 4-4M4 12a8 8 0 1 1 16 0 8 8 0 0 1-16 0z" />
                            </svg>
                        </div>
                        <strong>45</strong>
                    </div>
                    <p>Verified items</p>
                    <router-link class="count-link" :to="{ name: 'superadmin.items' }">
                        View items <svg viewBox="0 0 24 24">
                            <path d="M13 5l7 7-7 7M5 12h14" />
                        </svg>
                    </router-link>
                </article>

                <article class="count-card">
                    <div class="count-top">
                        <div class="icon-holder icon-teal">
                            <svg viewBox="0 0 24 24">
                                <path d="M5 12l4 4 10-10" />
                            </svg>
                        </div>
                        <strong>16</strong>
                    </div>
                    <p>Potential matches</p>
                    <router-link class="count-link" :to="{ name: 'superadmin.matches' }">
                        View matches <svg viewBox="0 0 24 24">
                            <path d="M13 5l7 7-7 7M5 12h14" />
                        </svg>
                    </router-link>
                </article>

                <article class="count-card">
                    <div class="count-top">
                        <div class="icon-holder icon-orange">
                            <svg viewBox="0 0 24 24">
                                <path d="M6 4h12v4H6V4zm0 6h12v10H6V10zm2 2v6h2v-6H8zm4 0v6h2v-6h-2z" />
                            </svg>
                        </div>
                        <strong>10</strong>
                    </div>
                    <p>Pending claims</p>
                    <router-link class="count-link" :to="{ name: 'superadmin.claims' }">
                        Review claims <svg viewBox="0 0 24 24">
                            <path d="M13 5l7 7-7 7M5 12h14" />
                        </svg>
                    </router-link>
                </article>

                <article class="count-card">
                    <div class="count-top">
                        <div class="icon-holder icon-sky">
                            <svg viewBox="0 0 24 24">
                                <path d="M12 2l3 3H9l3-3zm-3 7h6l-3 9-3-9z" />
                            </svg>
                        </div>
                        <strong>5</strong>
                    </div>
                    <p>Ready for release</p>
                    <router-link class="count-link" :to="{ name: 'superadmin.items' }">
                        View items <svg viewBox="0 0 24 24">
                            <path d="M13 5l7 7-7 7M5 12h14" />
                        </svg>
                    </router-link>
                </article>

                <article class="count-card">
                    <div class="count-top">
                        <div class="icon-holder icon-lime">
                            <svg viewBox="0 0 24 24">
                                <path d="M12 4a8 8 0 1 0 8 8h-2a6 6 0 1 1-6-6V4z" />
                            </svg>
                        </div>
                        <strong>32</strong>
                    </div>
                    <p>Returned items</p>
                    <router-link class="count-link" :to="{ name: 'superadmin.items' }">
                        View history <svg viewBox="0 0 24 24">
                            <path d="M13 5l7 7-7 7M5 12h14" />
                        </svg>
                    </router-link>
                </article>
            </section>

            <section class="grid-panels">
                <div class="panel">
                    <div class="panel-header">
                        <div>
                            <p class="panel-label">Reports Overview</p>
                            <h2>This month</h2>
                        </div>
                        <button class="panel-button secondary">This Month</button>
                    </div>

                    <div class="chart-row">
                        <div class="pie-chart">
                            <div class="pie-inner">
                                <strong>128</strong>
                                <span>Total reports</span>
                            </div>
                        </div>
                        <div class="chart-legend">
                            <div class="legend-item">
                                <span><i class="legend-dot lost"></i>Lost reports</span>
                                <strong>64 (50%)</strong>
                            </div>
                            <div class="legend-item">
                                <span><i class="legend-dot found"></i>Found reports</span>
                                <strong>54 (42%)</strong>
                            </div>
                            <div class="legend-item">
                                <span><i class="legend-dot verified"></i>Verified items</span>
                                <strong>45 (35%)</strong>
                            </div>
                            <div class="legend-item">
                                <span><i class="legend-dot returned"></i>Returned items</span>
                                <strong>32 (25%)</strong>
                            </div>
                        </div>
                    </div>

                    <div class="report-actions">
                        <router-link class="action-card" :to="{ name: 'superadmin.reports' }">View full
                            analytics</router-link>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-header">
                        <div>
                            <p class="panel-label">Top Item Categories</p>
                            <h2>This month</h2>
                        </div>
                        <button class="panel-button secondary">This Month</button>
                    </div>

                    <div class="bar-chart">
                        <div class="bar-row">
                            <div class="bar-meta"><span>Wallet</span><span>28</span></div>
                            <div class="bar-track"><span class="bar-fill" style="width: 90%"></span></div>
                        </div>
                        <div class="bar-row">
                            <div class="bar-meta"><span>Phone</span><span>24</span></div>
                            <div class="bar-track"><span class="bar-fill" style="width: 75%"></span></div>
                        </div>
                        <div class="bar-row">
                            <div class="bar-meta"><span>ID / Card</span><span>18</span></div>
                            <div class="bar-track"><span class="bar-fill" style="width: 55%"></span></div>
                        </div>
                        <div class="bar-row">
                            <div class="bar-meta"><span>Keys</span><span>15</span></div>
                            <div class="bar-track"><span class="bar-fill" style="width: 45%"></span></div>
                        </div>
                        <div class="bar-row">
                            <div class="bar-meta"><span>Earphones</span><span>12</span></div>
                            <div class="bar-track"><span class="bar-fill" style="width: 35%"></span></div>
                        </div>
                    </div>

                    <div class="report-actions">
                        <router-link class="action-card" :to="{ name: 'superadmin.reports' }">View full
                            report</router-link>
                    </div>
                </div>
            </section>

            <section class="actions-activity">
                <div class="quick-actions-panel">
                    <div class="panel-header">
                        <div>
                            <p class="panel-label">Quick Actions</p>
                            <h2>Operate faster</h2>
                        </div>
                    </div>

                    <div class="quick-actions-grid">
                        <router-link class="action-card" :to="{ name: 'superadmin.list' }">
                            <svg viewBox="0 0 24 24">
                                <path
                                    d="M15 14c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-6 2v2h12v-2c0-2.21-3.58-3.25-6-3.25S9 13.79 9 16z" />
                            </svg>
                            <span>Add student</span>
                        </router-link>
                        <router-link class="action-card" :to="{ name: 'superadmin.item-verification' }">
                            <svg viewBox="0 0 24 24">
                                <path
                                    d="M12 1L3 5v6c0 5 3.58 9 9 9s9-4 9-9V5l-9-4zm0 2.18l6 2.67v4.15c0 4.05-2.95 7.61-6 8.13-3.05-.52-6-4.08-6-8.13V5.85l6-2.67zM10 14.5l-2.5-2.5L9.5 10 10 10.5l4-4L16 7l-6 7.5z" />
                            </svg>
                            <span>Verify item</span>
                        </router-link>
                        <router-link class="action-card" :to="{ name: 'superadmin.claims' }">
                            <svg viewBox="0 0 24 24">
                                <path d="M6 2h9l5 5v15H6V2zm5 1.5V8h5.5L11 3.5zM8 12h8v2H8v-2zm0 4h8v2H8v-2z" />
                            </svg>
                            <span>Review claims</span>
                        </router-link>
                        <router-link class="action-card" :to="{ name: 'superadmin.notifications' }">
                            <svg viewBox="0 0 24 24">
                                <path
                                    d="M12 22a2 2 0 0 0 2-2h-4a2 2 0 0 0 2 2zm6-6V11c0-3.07-1.63-5.64-4.5-6.32V4a1.5 1.5 0 0 0-3 0v.68C7.62 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z" />
                            </svg>
                            <span>Send announcement</span>
                        </router-link>
                        <router-link class="action-card" :to="{ name: 'superadmin.recognition' }">
                            <svg viewBox="0 0 24 24">
                                <path
                                    d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
                            </svg>
                            <span>Award points</span>
                        </router-link>
                        <router-link class="action-card" :to="{ name: 'superadmin.items' }">
                            <svg viewBox="0 0 24 24">
                                <path d="M4 4h16v2H4V4zm0 4h16v2H4V8zm0 4h10v2H4v-2zm0 4h16v2H4v-2z" />
                            </svg>
                            <span>View inventory</span>
                        </router-link>
                    </div>
                </div>

                <div class="activity-panel">
                    <div class="panel-header">
                        <div>
                            <p class="panel-label">Recent Activity</p>
                            <h2>Latest admin events</h2>
                        </div>
                        <button class="panel-button secondary">View all</button>
                    </div>

                    <ul class="activity-list">
                        <li>
                            <router-link class="activity-link" :to="{ name: 'superadmin.items' }">
                                <div class="activity-badge badge-lost">📦</div>
                                <div class="activity-body">
                                    <p>Item received from Juan Dela Cruz</p>
                                    <small>Black Wallet · INV-2025-006</small>
                                </div>
                                <div class="activity-action">10:24 AM</div>
                            </router-link>
                        </li>
                        <li>
                            <router-link class="activity-link" :to="{ name: 'superadmin.claims' }">
                                <div class="activity-badge badge-claim">📄</div>
                                <div class="activity-body">
                                    <p>New claim submitted by Maria Santos</p>
                                    <small>ITEM-2025-0045</small>
                                </div>
                                <div class="activity-action">09:15 AM</div>
                            </router-link>
                        </li>
                        <li>
                            <router-link class="activity-link" :to="{ name: 'superadmin.claims' }">
                                <div class="activity-badge badge-user">✅</div>
                                <div class="activity-body">
                                    <p>Claim approved for John Paul</p>
                                    <small>ITEM-2025-0032</small>
                                </div>
                                <div class="activity-action">Yesterday</div>
                            </router-link>
                        </li>
                        <li>
                            <router-link class="activity-link" :to="{ name: 'superadmin.recognition' }">
                                <div class="activity-badge badge-audit">⭐</div>
                                <div class="activity-body">
                                    <p>Points awarded to Ana Reyes</p>
                                    <small>+100 Points</small>
                                </div>
                                <div class="activity-action">Yesterday</div>
                            </router-link>
                        </li>
                        <li>
                            <router-link class="activity-link" :to="{ name: 'superadmin.matches' }">
                                <div class="activity-badge badge-match">🔗</div>
                                <div class="activity-body">
                                    <p>New potential match found</p>
                                    <small>ITEM-2025-0048</small>
                                </div>
                                <div class="activity-action">May 23</div>
                            </router-link>
                        </li>
                        <li>
                            <router-link class="activity-link" :to="{ name: 'superadmin.notifications' }">
                                <div class="activity-badge badge-announcement">📣</div>
                                <div class="activity-body">
                                    <p>Announcement sent to all students</p>
                                    <small>General Assembly Reminder</small>
                                </div>
                                <div class="activity-action">May 22</div>
                            </router-link>
                        </li>
                    </ul>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup>
</script>

<style scoped>
.dashboard-page {
    position: relative;
    min-height: 100%;
    height: 100%;
    padding: 24px 28px;
    background: radial-gradient(circle at top left, rgba(6, 32, 18, 0.45), transparent 24%),
        radial-gradient(circle at bottom right, rgba(23, 74, 42, 0.35), transparent 28%),
        linear-gradient(180deg, #08160f 0%, #0d2518 40%, #102f20 100%);
    color: #eef4e8;
    overflow: auto;
}

.page-bg {
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 15% 20%, rgba(148, 213, 166, 0.14), transparent 22%),
        radial-gradient(circle at 85% 15%, rgba(102, 217, 194, 0.1), transparent 18%);
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
    grid-template-columns: 1.35fr 0.65fr;
    gap: 24px;
    align-items: center;
}

.hero-copy {
    display: grid;
    gap: 16px;
}

.page-label {
    margin: 0;
    color: rgba(146, 219, 170, 0.9);
    letter-spacing: 0.24em;
    text-transform: uppercase;
    font-size: 12px;
}

.dashboard-topbar h1 {
    margin: 0;
    font-size: clamp(34px, 3vw, 52px);
    line-height: 1.06;
}

.page-description {
    margin: 0;
    max-width: 700px;
    color: rgba(244, 250, 241, 0.82);
    font-size: 15px;
    line-height: 1.75;
}

.topbar-actions {
    display: flex;
    align-items: center;
    justify-content: end;
    gap: 14px;
    flex-wrap: wrap;
}

.panel-button {
    padding: 12px 20px;
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    background: rgba(255, 255, 255, 0.08);
    color: #f7f4e9;
    cursor: pointer;
    font-weight: 700;
    transition: background 0.2s ease, border-color 0.2s ease;
}

.panel-button:hover {
    background: rgba(255, 255, 255, 0.14);
}

.panel-button.secondary {
    background: transparent;
}

.count-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
}

.count-card {
    padding: 24px;
    border-radius: 24px;
    background: rgba(18, 38, 28, 0.92);
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 20px 55px rgba(0, 0, 0, 0.28);
    transition: transform 0.2s ease, border-color 0.2s ease;
}

.count-card:hover {
    transform: translateY(-2px);
    border-color: rgba(146, 219, 170, 0.22);
}

.count-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 20px;
}

.icon-holder {
    width: 48px;
    height: 48px;
    border-radius: 16px;
    display: grid;
    place-items: center;
    background: rgba(255, 255, 255, 0.08);
}

.icon-holder svg {
    width: 26px;
    height: 26px;
    fill: #eef4e8;
}

.icon-gold {
    background: rgba(212, 180, 103, 0.18);
}

.icon-blue {
    background: rgba(84, 153, 255, 0.14);
}

.icon-purple {
    background: rgba(167, 125, 241, 0.14);
}

.icon-green {
    background: rgba(136, 212, 166, 0.18);
}

.icon-teal {
    background: rgba(57, 182, 140, 0.16);
}

.icon-orange {
    background: rgba(244, 158, 66, 0.16);
}

.icon-sky {
    background: rgba(100, 179, 223, 0.16);
}

.icon-lime {
    background: rgba(143, 212, 122, 0.16);
}

.count-card strong {
    margin: 0;
    font-size: 38px;
    line-height: 1;
    color: #f8f8f2;
}

.count-card p {
    margin: 0;
    color: rgba(255, 255, 255, 0.78);
    font-size: 14px;
    line-height: 1.5;
}

.count-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 16px;
    font-size: 13px;
    font-weight: 700;
    color: #b4e386;
    text-decoration: none;
}

.count-link svg {
    width: 14px;
    height: 14px;
    fill: #b4e386;
}

.grid-panels {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr;
    gap: 24px;
}

.panel {
    padding: 28px;
    border-radius: 28px;
    background: rgba(12, 32, 23, 0.94);
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 20px 55px rgba(0, 0, 0, 0.24);
}

.panel-header {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    align-items: flex-start;
    margin-bottom: 24px;
}

.panel-label {
    margin: 0;
    font-size: 12px;
    text-transform: uppercase;
    color: rgba(146, 219, 170, 0.9);
    letter-spacing: 0.22em;
}

.panel h2 {
    margin: 0;
    font-size: 20px;
    line-height: 1.2;
}

.chart-row {
    display: grid;
    grid-template-columns: 220px minmax(0, 1fr);
    gap: 22px;
    align-items: center;
}

.pie-chart {
    width: 220px;
    height: 220px;
    border-radius: 50%;
    background: conic-gradient(rgba(75, 184, 169, 0.9) 0 45%,
            rgba(171, 99, 96, 0.88) 45% 78%,
            rgba(191, 214, 109, 0.9) 78% 100%);
    position: relative;
}

.pie-chart::before {
    content: '';
    position: absolute;
    inset: 24px;
    border-radius: 50%;
    background: rgba(11, 27, 19, 0.96);
}

.pie-inner {
    position: absolute;
    inset: 64px;
    border-radius: 50%;
    background: rgba(11, 27, 19, 0.96);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: #eef4e8;
}

.pie-inner strong {
    font-size: 34px;
    margin-bottom: 6px;
}

.pie-inner span {
    font-size: 13px;
    color: rgba(255, 255, 255, 0.72);
}

.chart-legend {
    display: grid;
    gap: 12px;
}

.legend-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.04);
}

.legend-item span {
    display: flex;
    align-items: center;
    gap: 10px;
    color: rgba(255, 255, 255, 0.86);
    font-size: 14px;
}

.legend-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.legend-dot.lost {
    background: #af635f;
}

.legend-dot.found {
    background: #4fb8a9;
}

.legend-dot.verified {
    background: #c0d66d;
}

.legend-dot.returned {
    background: #7dd9c2;
}

.bar-chart {
    display: grid;
    gap: 18px;
}

.bar-row {
    display: grid;
    gap: 12px;
}

.bar-meta {
    display: flex;
    justify-content: space-between;
    color: rgba(255, 255, 255, 0.82);
    font-size: 14px;
}

.bar-track {
    height: 12px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.08);
    overflow: hidden;
}

.bar-fill {
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, rgba(146, 219, 170, 0.95), rgba(89, 177, 136, 0.9));
}

.report-actions {
    margin-top: 20px;
}

.actions-activity {
    display: grid;
    grid-template-columns: 1.25fr 0.9fr;
    gap: 24px;
}

.quick-actions-panel,
.activity-panel {
    padding: 28px;
    border-radius: 28px;
    background: rgba(12, 32, 23, 0.94);
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 20px 55px rgba(0, 0, 0, 0.24);
}

.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
}

.action-card {
    min-height: 118px;
    padding: 20px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 22px;
    background: rgba(255, 255, 255, 0.03);
    color: #eef4e8;
    display: grid;
    place-items: center;
    text-align: center;
    gap: 12px;
    transition: transform 0.2s ease, background 0.2s ease;
}

.action-card:hover {
    transform: translateY(-2px);
    background: rgba(255, 255, 255, 0.08);
}

.action-card svg {
    width: 30px;
    height: 30px;
    fill: #b4e386;
}

.action-card span {
    font-weight: 700;
    color: #f7f4e9;
}

.activity-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: 12px;
}

.activity-list li {
    border-radius: 20px;
    overflow: hidden;
}

.activity-link {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 16px;
    align-items: center;
    padding: 16px 18px;
    text-decoration: none;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.06);
    color: inherit;
    transition: background 0.2s ease, transform 0.2s ease;
}

.activity-link:hover {
    background: rgba(255, 255, 255, 0.08);
    transform: translateY(-1px);
}

.activity-badge {
    width: 44px;
    height: 44px;
    border-radius: 16px;
    display: grid;
    place-items: center;
    color: #eef4e8;
    font-size: 18px;
}

.badge-lost {
    background: #af635f;
}

.badge-claim {
    background: #d09f5d;
}

.badge-user {
    background: #6bb68d;
}

.badge-audit {
    background: #5c9fc7;
}

.badge-match {
    background: #8f6dd0;
}

.badge-announcement {
    background: #4fb8a9;
}

.activity-body p {
    margin: 0 0 4px;
    font-weight: 700;
    color: #f7f4e9;
}

.activity-body small {
    color: rgba(255, 255, 255, 0.72);
}

.activity-action {
    color: #a7d7a8;
    font-size: 13px;
    text-align: right;
    white-space: nowrap;
}

@media (max-width: 1200px) {
    .count-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .grid-panels,
    .actions-activity {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 900px) {
    .dashboard-topbar {
        grid-template-columns: 1fr;
    }

    .topbar-actions {
        justify-content: flex-start;
    }

    .count-grid,
    .grid-panels,
    .actions-activity {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .dashboard-page {
        padding: 20px 18px;
    }

    .panel,
    .quick-actions-panel,
    .activity-panel,
    .count-card {
        padding: 22px;
    }
}
</style>
