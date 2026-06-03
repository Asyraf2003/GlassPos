.card,
.step-box {
    display: grid;
    gap: 14px;
    margin-bottom: 12px;
    padding: 20px 22px;
    border-radius: 8px;
}

.card h2,
.step-box h2 {
    margin: 0;
    font-size: 1rem;
    font-weight: 500;
}

.help {
    margin: 0;
    color: #5f6368;
    font-size: .9rem;
    line-height: 1.4;
}

.field {
    display: grid;
    gap: 7px;
}

.field label {
    font-size: .88rem;
    font-weight: 600;
}

.field input,
.field textarea {
    width: 100%;
    min-height: 44px;
    padding: 8px 0;
    border: 0;
    border-bottom: 1px solid #dadce0;
    outline: 0;
    background: transparent;
    font: inherit;
}

.field textarea {
    min-height: 74px;
    resize: vertical;
}

.options {
    display: grid;
    gap: 13px;
}

.option {
    display: flex;
    gap: 12px;
    align-items: center;
}

.radio {
    width: 20px;
    height: 20px;
    border: 2px solid #5f6368;
    border-radius: 999px;
}

.btn {
    min-height: 44px;
    padding: 0 18px;
    border: 0;
    border-radius: 6px;
    background: #673ab7;
    color: #fff;
    font-weight: 700;
}

.btn.secondary {
    background: #ede7f6;
    color: #673ab7;
}
