.total-bar {
    position: sticky;
    bottom: 10px;
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: center;
    padding: 16px 18px;
    border-radius: 14px;
}

.total-bar small {
    display: block;
    color: #5f6368;
}

.total {
    display: block;
    font-size: 1.35rem;
    font-weight: 800;
}

.step-shell {
    display: grid;
    gap: 12px;
}

.step-badge {
    width: max-content;
    padding: 6px 10px;
    border-radius: 999px;
    background: #dbeafe;
    color: #1d4ed8;
    font-size: .75rem;
    font-weight: 800;
}

.pos-page {
    width: min(100%, 520px);
    margin: 0 auto;
    padding: 12px;
    color: #e5e7eb;
}

.pos-panel {
    display: grid;
    gap: 14px;
    padding: 16px;
    border-radius: 24px;
    color: #172033;
}

.product-grid {
    display: grid;
    gap: 10px;
}

.product-btn {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    min-height: 52px;
    padding: 0 14px;
    border: 0;
    border-radius: 16px;
    background: #f1f5f9;
    font-weight: 800;
}

.cart-list {
    display: grid;
    gap: 8px;
    color: #475569;
}

@media (max-width: 520px) {
    .form-head__body,
    .card,
    .step-box {
        padding-left: 18px;
        padding-right: 18px;
    }
}
