<div class="step-shell">
    <div class="step-head">
        <small>Variant 02</small>
        <strong>Step Cards</strong>
        <span>Flow nota dibuat seperti langkah kerja, bukan form panjang yang menumpuk.</span>
    </div>

    <div class="step-list">
        <section class="step-card">
            <span class="step-card__badge">Step 1</span>

            <div>
                <h2>Informasi Nota</h2>
                <p>Isi data customer dan alasan nota dulu agar kasir punya konteks awal.</p>
            </div>

            <div class="ui-field">
                <label>Customer</label>
                <input type="text" placeholder="Nama customer">
            </div>

            <div class="ui-field">
                <label>No. HP</label>
                <input type="tel" placeholder="Nomor customer">
            </div>

            <div class="ui-field">
                <label>Alasan Nota</label>
                <textarea placeholder="Keluhan atau instruksi singkat"></textarea>
            </div>
        </section>

        <section class="step-card">
            <span class="step-card__badge">Step 2</span>

            <div>
                <h2>Rincian Nota</h2>
                <p>Kasir memilih jenis transaksi lalu mengisi rincian aktif.</p>
            </div>

            <div class="ui-choice">
                <button type="button">+ Produk Toko</button>
                <button type="button">+ Servis</button>
                <button type="button">+ Servis + Sparepart</button>
            </div>

            <div class="ui-field">
                <label>Rincian aktif</label>
                <input type="text" placeholder="Ganti oli + oli mesin">
            </div>

            <div class="ui-field">
                <label>Total Paket</label>
                <input type="text" inputmode="numeric" placeholder="250.000">
            </div>
        </section>

        <section class="step-card">
            <span class="step-card__badge">Step 3</span>

            <div>
                <h2>Pembayaran</h2>
                <p>Pilihan akhir dibuat besar agar keputusan pembayaran jelas.</p>
            </div>

            <div class="step-action-row">
                <button type="button" class="step-action">Tanpa Bayar</button>
                <button type="button" class="step-action">Sebagian</button>
            </div>

            <button type="button" class="step-action step-action--primary">
                Bayar Penuh
            </button>

            <div class="ui-total-bar">
                <div>
                    <span class="ui-muted">Estimasi Total</span>
                    <strong>Rp 250.000</strong>
                </div>

                <button type="button" class="ui-primary">Simpan</button>
            </div>
        </section>
    </div>
</div>
