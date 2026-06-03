.gform-total {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: center;
    margin-bottom: 12px;
    padding: 18px 24px;
    border: 1px solid #dadce0;
    border-radius: 8px;
    background: #fff;
}

.gform-total span {
    display: block;
    color: #5f6368;
    font-size: .82rem;
}

.gform-total strong {
    display: block;
    margin-top: 4px;
    font-size: 1.25rem;
    font-weight: 500;
}

.gform-button {
    min-height: 40px;
    padding: 0 24px;
    border: 0;
    border-radius: 4px;
    background: #673ab7;
    color: #fff;
    font-weight: 500;
}

.gform-footer-note {
    margin: 14px 4px 0;
    color: #5f6368;
    font-size: .78rem;
    text-align: center;
}

@media (max-width: 520px) {
    .gform-header__body,
    .gform-card,
    .gform-total {
        padding-left: 18px;
        padding-right: 18px;
    }

    .gform-header h1 {
        font-size: 1.65rem;
    }
}
