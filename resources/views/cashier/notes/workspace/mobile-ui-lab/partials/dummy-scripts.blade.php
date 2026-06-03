<script>
(() => {
    const products = [
        ['Oli Mesin', 65000],
        ['Kampas Rem', 120000],
        ['Busi', 85000],
    ];

    const cart = [];
    const money = (value) => `Rp ${Number(value || 0).toLocaleString('id-ID')}`;
    const total = () => cart.reduce((sum, item) => sum + item.price, 0);

    const render = () => {
        document.querySelectorAll('[data-total]').forEach((node) => {
            node.textContent = money(total() || Number(node.dataset.seed || 0));
        });

        document.querySelectorAll('[data-cart]').forEach((node) => {
            node.innerHTML = cart.length
                ? cart.map((item) => `<div>${item.name} - ${money(item.price)}</div>`).join('')
                : '<div>Belum ada item.</div>';
        });
    };

    document.querySelectorAll('[data-products]').forEach((node) => {
        node.innerHTML = products.map((item, index) => `
            <button class="product-btn" type="button" data-add="${index}">
                <span>${item[0]}</span><span>${money(item[1])}</span>
            </button>
        `).join('');
    });

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-add]');
        if (!button) return;

        const item = products[Number(button.dataset.add)];
        cart.push({ name: item[0], price: item[1] });
        render();
    });

    render();
})();
</script>
