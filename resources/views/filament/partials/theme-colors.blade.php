<style>
    :root {
        color-scheme: light;
        --inv-navy-deep: #010736;
        --inv-navy: #0D1C42;
        --inv-blue: #22396F;
        --inv-cream: #FCF1D0;
        --inv-bg: #F5F4EF;
        --inv-surface: #FFFDF7;
        --inv-surface-raised: #F6EFDE;
        --inv-surface-hover: #F0EADB;
        --inv-border: #DFDFD7;
        --inv-ink: #0D1C42;
        --inv-muted: #5F6A80;
        --inv-primary: #22396F;
        --inv-primary-hover: #0D1C42;
        --inv-primary-ink: #22396F;
        --inv-primary-soft: #E8EDF6;
        --inv-on-primary: #FCF1D0;
    }

    .fi-body.fi-panel-admin {
        background-color: var(--inv-bg);
        color: var(--inv-ink);
    }

    .fi-panel-admin .fi-topbar > nav {
        background-color: var(--inv-surface);
        --tw-ring-color: var(--inv-border);
        box-shadow: 0 1px 8px rgb(13 28 66 / .04);
    }

    .fi-panel-admin .fi-sidebar {
        background: linear-gradient(180deg, var(--inv-navy-deep), var(--inv-navy));
    }

    .fi-panel-admin .fi-sidebar-header {
        background-color: var(--inv-navy-deep);
        --tw-ring-color: rgb(252 241 208 / .12);
    }

    .fi-panel-admin .fi-sidebar .fi-logo {
        color: var(--inv-cream);
    }

    .fi-panel-admin .fi-sidebar .fi-sidebar-nav {
        padding-block: .75rem;
        row-gap: .5rem;
        scrollbar-color: #52658D var(--inv-navy);
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

    .fi-panel-admin .fi-sidebar .fi-sidebar-group-label,
    .fi-panel-admin .fi-sidebar .fi-sidebar-group-icon,
    .fi-panel-admin .fi-sidebar .fi-icon-btn-icon {
        color: #AEBBD5;
    }

    .fi-panel-admin .fi-sidebar .fi-sidebar-item-label {
        color: #DCE3F0;
    }

    .fi-panel-admin .fi-sidebar .fi-sidebar-item-icon {
        color: #AEBBD5;
    }

    .fi-panel-admin .fi-sidebar-item:not(.fi-active) > .fi-sidebar-item-button:is(:hover, :focus-visible),
    .fi-panel-admin .fi-sidebar .fi-icon-btn:is(:hover, :focus-visible) {
        background-color: var(--inv-blue);
    }

    .fi-panel-admin .fi-sidebar-item.fi-active > .fi-sidebar-item-button {
        background-color: var(--inv-cream);
        box-shadow: inset 3px 0 #D5BC7E;
    }

    .fi-panel-admin .fi-sidebar-item.fi-active > .fi-sidebar-item-button .fi-sidebar-item-label,
    .fi-panel-admin .fi-sidebar-item.fi-active > .fi-sidebar-item-button .fi-sidebar-item-icon {
        color: var(--inv-navy);
    }

    .fi-panel-admin .fi-sidebar .fi-sidebar-item-button:focus-visible {
        outline: 2px solid var(--inv-cream);
        outline-offset: 3px;
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
        background-color: var(--inv-surface);
    }

    .fi-panel-admin .fi-section:not(.fi-aside),
    .fi-panel-admin .fi-wi-stats-overview-stat,
    .fi-panel-admin .fi-ta-ctn,
    .fi-panel-admin .fi-dropdown-panel {
        --tw-ring-color: var(--inv-border);
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
        background-color: var(--inv-surface-raised);
    }

    .fi-panel-admin .fi-ta-row.bg-gray-50,
    .fi-panel-admin .fi-ta-record.bg-gray-50,
    .fi-panel-admin .fi-ta-row[class~='hover:bg-gray-50']:hover,
    .fi-panel-admin .fi-ta-record[class~='hover:bg-gray-50']:hover,
    .fi-panel-admin .choices__item--choice.is-highlighted {
        background-color: var(--inv-surface-hover);
    }

    .fi-panel-admin .fi-input-wrp:not(.fi-disabled),
    .fi-panel-admin .choices__list--dropdown,
    .fi-panel-admin .choices__list[aria-expanded],
    .fi-panel-admin .fi-select-input :is(option, optgroup),
    .fi-panel-admin .fi-fo-date-time-picker-panel,
    .fi-panel-admin .filepond--root:not([data-disabled='disabled']),
    .fi-panel-admin .filepond--root:not([data-disabled='disabled']) .filepond--panel-root {
        background-color: #FFFEFA;
    }

    .fi-panel-admin .fi-input-wrp:not(.fi-invalid):not(:focus-within) {
        --tw-ring-color: #A6AEBD;
    }

    .fi-panel-admin .fi-input-wrp.fi-disabled {
        background-color: #ECEDEB;
    }

    .fi-panel-admin .fi-btn.fi-color-gray:not(.fi-btn-outlined):not(label),
    .fi-panel-admin input:not(:checked) + label.fi-btn {
        background-color: var(--inv-surface);
        color: var(--inv-ink);
    }

    .fi-panel-admin .fi-btn.fi-color-gray:not(.fi-btn-outlined):not(label):hover,
    .fi-panel-admin input:not(:checked) + label.fi-btn:hover,
    .fi-panel-admin .fi-dropdown-list-item.fi-color-gray:is(:hover, :focus-visible),
    .fi-panel-admin .fi-tabs-item:not(.fi-active):is(:hover, :focus-visible) {
        background-color: var(--inv-surface-hover);
    }

    .fi-panel-admin .fi-btn:is(.fi-color-primary, .fi-color-info):not(.fi-btn-outlined):not(label),
    .fi-panel-admin input:checked + label.fi-btn:is(.fi-color-primary, .fi-color-info) {
        background-color: var(--inv-primary);
        color: var(--inv-on-primary);
    }

    .fi-panel-admin .fi-btn:is(.fi-color-primary, .fi-color-info):not(.fi-btn-outlined):not(label):hover,
    .fi-panel-admin input:checked + label.fi-btn:is(.fi-color-primary, .fi-color-info):hover {
        background-color: var(--inv-primary-hover);
    }

    .fi-panel-admin .fi-btn:is(.fi-color-primary, .fi-color-info):not(.fi-btn-outlined):not(label) .fi-btn-icon,
    .fi-panel-admin input:checked + label.fi-btn:is(.fi-color-primary, .fi-color-info) .fi-btn-icon {
        color: inherit;
    }

    .fi-panel-admin .fi-badge:is(.fi-color-primary, .fi-color-info) {
        background-color: var(--inv-primary-soft);
        color: var(--inv-primary-ink);
        --tw-ring-color: rgb(34 57 111 / .18);
    }

    .fi-panel-admin .fi-badge:is(.fi-color-primary, .fi-color-info) .fi-badge-icon {
        color: inherit;
    }

    .fi-panel-admin .fi-tabs-item.fi-active,
    .fi-panel-admin .fi-pagination-item.fi-active .fi-pagination-item-button {
        background-color: var(--inv-cream);
    }

    .fi-panel-admin .fi-tabs-item.fi-active .fi-tabs-item-label,
    .fi-panel-admin .fi-tabs-item.fi-active .fi-tabs-item-icon {
        color: var(--inv-primary-ink);
    }

    .fi-panel-admin .fi-simple-layout {
        background: radial-gradient(ellipse at top right, var(--inv-blue), transparent 65%),
            linear-gradient(145deg, var(--inv-navy-deep), var(--inv-navy));
    }

    .fi-panel-admin .fi-simple-main {
        border-top: 4px solid var(--inv-cream);
        box-shadow: 0 24px 70px rgb(1 7 54 / .28);
    }
</style>
