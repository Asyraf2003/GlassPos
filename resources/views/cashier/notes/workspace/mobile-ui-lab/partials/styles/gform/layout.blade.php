.gform-nav {
    position: sticky;
    top: 0;
    z-index: 10;
    display: flex;
    gap: 8px;
    overflow-x: auto;
    margin-bottom: 14px;
    padding: 10px 0;
    background: #f0ebf8;
}

.gform-nav a,
.gform-chip {
    display: inline-flex;
    min-height: 34px;
    align-items: center;
    border-radius: 999px;
    font-weight: 700;
    text-decoration: none;
}

.gform-nav a {
    padding: 0 13px;
    background: #fff;
    color: #673ab7;
    border: 1px solid #dadce0;
}

.gform-chip {
    width: max-content;
    padding: 0 10px;
    background: #ede7f6;
    color: #673ab7;
    font-size: .78rem;
}

.gform-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.gform-sticky {
    position: sticky;
    bottom: 12px;
    box-shadow: 0 12px 32px rgba(60, 64, 67, .24);
}

.gform-review {
    display: grid;
    gap: 10px;
    margin-bottom: 12px;
}

details.gform-card summary {
    cursor: pointer;
    font-weight: 500;
}

@media (max-width: 520px) {
    .gform-header__body,
    .gform-card,
    .gform-total,
    .gform-review,
    .gform-preview-head {
        padding-left: 18px;
        padding-right: 18px;
    }

    .gform-header h1 {
        font-size: 1.58rem;
    }

    .gform-grid {
        grid-template-columns: 1fr;
    }
}
