.gform-field {
    display: grid;
    gap: 8px;
    margin-top: 18px;
}

.gform-field label {
    font-size: .94rem;
    font-weight: 400;
}

.gform-field input,
.gform-field textarea {
    width: 100%;
    padding: 8px 0;
    border: 0;
    border-bottom: 1px solid #dadce0;
    border-radius: 0;
    outline: 0;
    background: transparent;
    color: #202124;
    font: inherit;
}

.gform-field input:focus,
.gform-field textarea:focus {
    border-bottom: 2px solid #673ab7;
}

.gform-field textarea {
    min-height: 74px;
    resize: vertical;
}

.gform-option-list {
    display: grid;
    gap: 14px;
    margin-top: 14px;
}

.gform-option {
    display: flex;
    gap: 12px;
    align-items: center;
    min-height: 32px;
    color: #202124;
}

.gform-radio {
    width: 20px;
    height: 20px;
    flex: 0 0 auto;
    border: 2px solid #5f6368;
    border-radius: 999px;
}

.gform-option span {
    font-size: .95rem;
}
