<main class="page">
    <section class="step-shell">
        <div class="step-box">
            <span class="step-badge">Step 1</span>
            <h2>Info Nota</h2>
            <p class="help">Isi identitas customer dan alasan nota.</p>
            <div class="field"><label>Customer</label><input placeholder="Nama customer"></div>
            <div class="field"><label>Alasan nota</label><textarea placeholder="Keluhan atau instruksi"></textarea></div>
        </div>

        <div class="step-box">
            <span class="step-badge">Step 2</span>
            <h2>Rincian</h2>
            <p class="help">Pilih item dummy untuk melihat rasa alur create.</p>
            <div class="product-grid" data-products></div>
            <div class="cart-list" data-cart></div>
        </div>

        <div class="step-box">
            <span class="step-badge">Step 3</span>
            <h2>Pembayaran</h2>
            <div class="options">
                <div class="option"><span class="radio"></span><span>Tanpa pembayaran</span></div>
                <div class="option"><span class="radio"></span><span>Bayar penuh</span></div>
                <div class="option"><span class="radio"></span><span>Bayar sebagian</span></div>
            </div>
        </div>
    </section>

    <div class="total-bar">
        <div><small>Total nota</small><span class="total" data-total>Rp 0</span></div>
        <button class="btn" type="button">Proses</button>
    </div>
</main>
