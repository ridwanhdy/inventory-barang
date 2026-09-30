<style>
    :root {
        color-scheme: light;
        --inv-primary: #7EC151;
        --inv-primary-hover: #6FAE45;
        --inv-primary-ink: #315A1F;
        --inv-blue: #92EEFF;
        --inv-blue-ink: #15586B;
        --inv-mint: #C4F7CA;
        --inv-mint-ink: #285A34;
        --inv-on-accent: #183322;
        --inv-lime-bg: #E2F1C9;
        --inv-lime-surface: #ECF6D9;
        --inv-lime-raised: #D5EAB0;
        --inv-lime-hover: #DDEDBE;
        --inv-lime-border: #B7D48F;
    }

    .fi-body.fi-panel-admin {
        background-color: var(--inv-lime-bg);
    }

    .fi-panel-admin .fi-topbar > nav,
    .fi-panel-admin .fi-sidebar,
    .fi-panel-admin .fi-sidebar-header {
        background-color: var(--inv-lime-raised);
    }

    .fi-panel-admin .fi-sidebar .fi-sidebar-nav {
        padding-block: .75rem;
        row-gap: .5rem;
    }

    .fi-panel-admin .fi-sidebar .fi-sidebar-nav-groups {
        row-gap: .5rem;
    }

    .fi-panel-admin .fi-sidebar .fi-sidebar-group,
    .fi-panel-admin .fi-sidebar .fi-sidebar-group-items {
        row-gap: .125rem;
    }

    .fi-panel-admin .fi-sidebar .fi-sidebar-group-button,
    .fi-panel-admin .fi-sidebar .fi-sidebar-item-button {
        padding-block: .25rem;
    }

    .fi-panel-admin .fi-simple-main,
    .fi-panel-admin .fi-section:not(.fi-aside),
    .fi-panel-admin .fi-section.fi-aside > .fi-section-content-ctn,
    .fi-panel-admin .fi-wi-stats-overview-stat,
    .fi-panel-admin .fi-ta-ctn,
    .fi-panel-admin .fi-ta-record,
    .fi-panel-admin .fi-modal-window,
    .fi-panel-admin .fi-modal-header.fi-sticky,
    .fi-panel-admin .fi-modal-footer.fi-sticky,
    .fi-panel-admin .fi-dropdown-panel,
    .fi-panel-admin .fi-global-search-results-ctn,
    .fi-panel-admin .fi-fo-repeater-item,
    .fi-panel-admin .fi-fo-repeater-add-between-action-ctn,
    .fi-panel-admin .fi-fo-builder-item,
    .fi-panel-admin .fi-fo-builder-block-picker-ctn,
    .fi-panel-admin .fi-fo-tabs.fi-contained,
    .fi-panel-admin .fi-tabs.bg-white,
    .fi-panel-admin .fi-fo-wizard.fi-contained,
    .fi-panel-admin .fi-fo-wizard-header.bg-white,
    .fi-panel-admin .fi-pagination-items,
    .fi-panel-admin .fi-form-actions.fi-sticky,
    .fi-panel-admin .fi-no-notification:not(.fi-inline).fi-color-gray,
    .fi-panel-admin .fi-btn-badge-ctn {
        background-color: var(--inv-lime-surface);
    }

    .fi-panel-admin .fi-ta-header-ctn,
    .fi-panel-admin .fi-ta-table > thead > tr,
    .fi-panel-admin .fi-ta-table > tfoot,
    .fi-panel-admin .fi-ta-pagination,
    .fi-panel-admin .fi-ta-summary-header-row,
    .fi-panel-admin .fi-ta-summary-row,
    .fi-panel-admin .fi-ta-selection-indicator,
    .fi-panel-admin .fi-ta-reorder-indicator,
    .fi-panel-admin .fi-ta-group-header,
    .fi-panel-admin .fi-ta-filter-indicators,
    .fi-panel-admin .fi-ta-group-selection-cell,
    .fi-panel-admin .fi-ta-panel,
    .fi-panel-admin .inv-payment-summary-card {
        background-color: var(--inv-lime-raised);
    }

    .fi-panel-admin .fi-ta-row.bg-gray-50,
    .fi-panel-admin .fi-ta-record.bg-gray-50,
    .fi-panel-admin .fi-ta-row[class~='hover:bg-gray-50']:hover,
    .fi-panel-admin .fi-ta-record[class~='hover:bg-gray-50']:hover,
    .fi-panel-admin .choices__item--choice.is-highlighted {
        background-color: var(--inv-lime-hover);
    }

    .fi-panel-admin .fi-input-wrp:not(.fi-disabled),
    .fi-panel-admin .choices__list--dropdown,
    .fi-panel-admin .choices__list[aria-expanded],
    .fi-panel-admin .fi-select-input :is(option, optgroup),
    .fi-panel-admin .fi-fo-date-time-picker-panel,
    .fi-panel-admin .filepond--root:not([data-disabled='disabled']),
    .fi-panel-admin .filepond--root:not([data-disabled='disabled']) .filepond--panel-root {
        background-color: var(--inv-lime-surface);
    }

    .fi-panel-admin .fi-input-wrp.fi-disabled {
        background-color: var(--inv-lime-hover);
    }

    .fi-panel-admin .fi-btn.fi-color-gray:not(.fi-btn-outlined):not(label),
    .fi-panel-admin input:not(:checked) + label.fi-btn {
        background-color: var(--inv-lime-surface);
    }

    .fi-panel-admin .fi-btn.fi-color-gray:not(.fi-btn-outlined):not(label):hover,
    .fi-panel-admin input:not(:checked) + label.fi-btn:hover,
    .fi-panel-admin .fi-dropdown-list-item.fi-color-gray:hover,
    .fi-panel-admin .fi-dropdown-list-item.fi-color-gray:focus-visible,
    .fi-panel-admin .fi-sidebar-item:not(.fi-active) > .fi-sidebar-item-button:hover {
        background-color: var(--inv-lime-hover);
    }

    .fi-color-primary {
        --inv-control-bg: var(--inv-primary);
        --inv-control-hover: var(--inv-primary-hover);
        --inv-control-soft: var(--inv-mint);
        --inv-control-ink: var(--inv-primary-ink);
        --inv-control-rgb: 126, 193, 81;
    }

    .fi-color-info {
        --inv-control-bg: var(--inv-blue);
        --inv-control-hover: #7EDCEC;
        --inv-control-soft: var(--inv-blue);
        --inv-control-ink: var(--inv-blue-ink);
        --inv-control-rgb: 146, 238, 255;
    }

    .fi-color-success {
        --inv-control-bg: var(--inv-mint);
        --inv-control-hover: #AFE9B6;
        --inv-control-soft: var(--inv-mint);
        --inv-control-ink: var(--inv-mint-ink);
        --inv-control-rgb: 196, 247, 202;
    }

    /* Filament uses shade 600 for filled buttons; keep their backgrounds at the exact brand colors. */
    .fi-btn:is(.fi-color-primary, .fi-color-info, .fi-color-success):not(.fi-btn-outlined):not(label) {
        background-color: var(--inv-control-bg);
        color: var(--inv-on-accent);
    }

    .fi-btn:is(.fi-color-primary, .fi-color-info, .fi-color-success):not(.fi-btn-outlined):not(label):hover {
        background-color: var(--inv-control-hover);
    }

    .fi-btn:is(.fi-color-primary, .fi-color-info, .fi-color-success):not(.fi-btn-outlined):not(label) .fi-btn-icon {
        color: inherit;
    }

    .fi-badge:is(.fi-color-primary, .fi-color-info, .fi-color-success) {
        background-color: var(--inv-control-soft);
        color: var(--inv-control-ink);
        --tw-ring-color: rgba(var(--inv-control-rgb), .3);
    }

    .fi-badge:is(.fi-color-primary, .fi-color-info, .fi-color-success) .fi-badge-icon {
        color: inherit;
    }

    .fi-sidebar-item.fi-active > .fi-sidebar-item-button {
        background-color: var(--inv-mint);
        box-shadow: inset 3px 0 var(--inv-primary);
    }

    .fi-sidebar-item.fi-active .fi-sidebar-item-label,
    .fi-sidebar-item.fi-active .fi-sidebar-item-icon {
        color: var(--inv-primary-ink);
    }

    .fi-tabs-item.fi-active {
        background-color: var(--inv-mint);
    }

    .fi-tabs-item.fi-active .fi-tabs-item-label,
    .fi-tabs-item.fi-active .fi-tabs-item-icon {
        color: var(--inv-primary-ink);
    }
</style>
