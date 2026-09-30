<style>
    .inv-dashboard-page {
        --inv-accent: var(--inv-primary);
        --inv-muted: #64748b;
        --inv-border: var(--inv-lime-border);
        --inv-soft: var(--inv-lime-surface);
    }

    .inv-dashboard-page .inv-dashboard-grid,
    .inv-dashboard-page .fi-wi-stats-overview-stats-ctn { gap: 1.1rem; }
    .inv-dashboard-page .fi-wi-widget { min-width: 0; }
    .inv-dashboard-page > section { gap: 1.25rem; padding-block: 1.5rem; }
    .inv-dashboard-page > section > div > .grid { row-gap: 1.25rem; }
    .inv-dashboard-page .fi-section { border-radius: 1rem; }
    .inv-dashboard-page .fi-section-header { padding: 1.1rem 1.25rem; }
    .inv-dashboard-page .fi-section-content { padding: 1.1rem 1.25rem; }
    .inv-dashboard-page .fi-section-header-description { font-size: .75rem; }
    .inv-dashboard-page .fi-ta { border-radius: 1rem; overflow: hidden; }
    .inv-dashboard-page .fi-ta-header { padding: 1.1rem 1.25rem; }

    .inv-hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1.5rem;
        padding: 1.4rem 1.6rem;
        border: 1px solid var(--inv-mint);
        border-radius: 1rem;
        background: linear-gradient(110deg, color-mix(in srgb, var(--inv-mint) 45%, var(--inv-lime-surface)), color-mix(in srgb, var(--inv-blue) 30%, var(--inv-lime-surface)) 75%);
    }
    .inv-eyebrow { color: var(--inv-primary-ink); font-size: .6rem; font-weight: 700; letter-spacing: .14em; }
    .inv-hero h2 { margin: .35rem 0; color: #1e293b; font-size: clamp(1.1rem, 2vw, 1.4rem); font-weight: 700; }
    .inv-hero p { color: var(--inv-muted); font-size: .78rem; line-height: 1.6; }
    .inv-hero-side { display: grid; flex-shrink: 0; justify-items: end; gap: .75rem; }
    .inv-date { display: inline-flex; align-items: center; gap: .4rem; color: var(--inv-muted); font-size: .7rem; }
    .inv-icon { width: 1rem; height: 1rem; flex-shrink: 0; }
    .inv-hero-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
    .inv-hero-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        min-height: 2.35rem;
        padding: .55rem .85rem;
        border: 1px solid var(--inv-mint);
        border-radius: .65rem;
        background: var(--inv-lime-surface);
        color: #334155;
        font-size: .72rem;
        font-weight: 600;
        transition: background .15s, box-shadow .15s;
    }
    .inv-hero-action:hover { background: var(--inv-lime-hover); }
    .inv-hero-action-primary { border-color: var(--inv-primary); background: var(--inv-primary); color: var(--inv-on-accent); }
    .inv-hero-action-primary:hover { background: var(--inv-primary-hover); }
    .inv-hero-action:focus-visible, .inv-stock-link:focus-visible { outline: 3px solid var(--inv-primary); outline-offset: 3px; }

    .inv-dashboard-page .inv-stat { padding: 1.15rem; border-radius: 1rem; border-top: 3px solid var(--inv-stat-accent, var(--inv-mint)); }
    .inv-dashboard-page .inv-stat--sales { --inv-stat-accent: var(--inv-mint); --inv-stat-ink: var(--inv-primary-ink); }
    .inv-dashboard-page .inv-stat--orders { --inv-stat-accent: var(--inv-mint); --inv-stat-ink: var(--inv-blue-ink); }
    .inv-dashboard-page .inv-stat--stock { --inv-stat-accent: var(--inv-mint); --inv-stat-ink: var(--inv-mint-ink); }
    .inv-dashboard-page .inv-stat--payment { --inv-stat-accent: var(--inv-mint); --inv-stat-ink: #b45309; }
    .inv-dashboard-page .fi-wi-stats-overview-stat-icon { color: var(--inv-stat-ink, var(--inv-primary-ink)); }
    .inv-dashboard-page .fi-wi-stats-overview-stat-label { font-size: .72rem; }
    .inv-dashboard-page .fi-wi-stats-overview-stat-value {
        font-size: clamp(1.2rem, 1.75vw, 1.65rem);
        line-height: 1.35;
        overflow-wrap: anywhere;
        font-variant-numeric: tabular-nums;
    }
    .inv-dashboard-page .fi-wi-stats-overview-stat-description { font-size: .65rem; }
    .inv-dashboard-page .fi-wi-stats-overview-stat-description-icon { width: .85rem; height: .85rem; flex-shrink: 0; }
    .inv-dashboard-page .inv-stat .fi-wi-stats-overview-stat-chart { background-color: var(--inv-lime-surface); }
    .inv-dashboard-page .inv-stat .fi-wi-stats-overview-stat-chart [x-ref="backgroundColorElement"],
    .inv-dashboard-page .inv-stat .fi-wi-stats-overview-stat-chart [x-ref="borderColorElement"] { color: var(--inv-lime-surface); }
    .inv-dashboard-page .fi-wi-chart canvas { width: 100% !important; height: 260px !important; max-height: 260px; }

    .inv-stock-summary { display: flex; align-items: center; gap: .8rem; padding-bottom: .9rem; }
    .inv-stock-count {
        display: grid;
        place-items: center;
        min-width: 2.6rem;
        min-height: 2.6rem;
        border-radius: .8rem;
        background: #fff7ed;
        color: #c2410c;
        font-size: 1.2rem;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
    }
    .inv-stock-copy { color: var(--inv-muted); font-size: .73rem; line-height: 1.5; }
    .inv-stock-copy strong { display: block; color: #334155; font-weight: 600; }
    .inv-stock-summary-icon { width: 1.35rem; height: 1.35rem; flex-shrink: 0; margin-left: auto; color: #f97316; }
    .inv-stock-summary-safe .inv-stock-count { background: var(--inv-mint); color: var(--inv-mint-ink); }
    .inv-stock-summary-safe .inv-stock-summary-icon { color: var(--inv-primary-ink); }
    .inv-stock-list { display: grid; gap: .8rem; }
    .inv-stock-item { min-width: 0; }
    .inv-stock-item-heading { display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap; gap: .3rem .75rem; margin-bottom: .35rem; }
    .inv-stock-name { color: #334155; font-size: .74rem; font-weight: 500; overflow-wrap: anywhere; }
    .inv-stock-quantity { display: flex; justify-content: space-between; flex-wrap: wrap; gap: .3rem; color: var(--inv-muted); font-size: .62rem; font-variant-numeric: tabular-nums; }
    .inv-stock-status { padding: .1rem .45rem; border-radius: 999px; color: #c2410c; background: #fff7ed; font-size: .6rem; font-weight: 600; }
    .inv-stock-more { color: var(--inv-muted); font-size: .65rem; margin-top: .7rem; }
    .inv-stock-track { height: .3rem; margin-top: .35rem; overflow: hidden; border-radius: 999px; background: var(--inv-lime-raised); }
    .inv-stock-fill { height: 100%; border-radius: inherit; background: #fb923c; }
    .inv-stock-empty { display: grid; justify-items: center; gap: .4rem; padding: 1rem .5rem; color: var(--inv-muted); text-align: center; font-size: .73rem; }
    .inv-stock-empty-icon { width: 2.2rem; height: 2.2rem; color: var(--inv-primary-ink); }
    .inv-stock-empty strong { color: #334155; }
    .inv-stock-footer { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: .6rem; margin-top: 1rem; padding-top: .85rem; border-top: 1px solid var(--inv-border); }
    .inv-stock-production { display: inline-flex; align-items: center; gap: .4rem; color: var(--inv-muted); font-size: .67rem; }
    .inv-stock-link { display: inline-flex; align-items: center; gap: .25rem; color: var(--inv-primary-ink); font-size: .7rem; font-weight: 600; }
    .inv-stock-link:hover { text-decoration: underline; }
    .inv-stock-production-icon, .inv-stock-link-icon { width: .9rem; height: .9rem; flex-shrink: 0; }

    @media (min-width: 1280px) {
        .inv-dashboard-page .fi-wi-chart > .fi-section,
        .inv-dashboard-page .inv-stock-card { min-height: 350px; height: 100%; }
    }
    @media (max-width: 900px) {
        .inv-hero { align-items: flex-start; flex-direction: column; gap: 1rem; }
        .inv-hero-side { justify-items: start; }
    }
    @media (max-width: 640px) {
        .inv-hero { padding: 1.15rem; }
        .inv-dashboard-page .fi-section-header, .inv-dashboard-page .fi-section-content, .inv-dashboard-page .fi-ta-header { padding: 1rem; }
        .inv-dashboard-page .fi-wi-stats-overview-stats-ctn { gap: .85rem; }
        .inv-dashboard-page .fi-wi-chart canvas { height: 230px !important; max-height: 230px; }
        .inv-hero-actions { width: 100%; }
        .inv-hero-side { width: 100%; }
        .inv-hero-action { flex: 1; }
    }
    @media (max-width: 767px) {
        .inv-dashboard-page .fi-ta-table { table-layout: fixed; }
        .inv-dashboard-page .fi-ta-text { padding: .75rem .5rem; }
        .inv-dashboard-page .fi-ta-header-cell { padding-inline: .5rem; }
        .inv-dashboard-page .fi-ta-text-item-label { font-size: .7rem; overflow-wrap: anywhere; }
        .inv-dashboard-page .fi-ta-header-cell-label, .inv-dashboard-page .fi-ta-text .fi-badge { font-size: .65rem; }
    }
</style>
