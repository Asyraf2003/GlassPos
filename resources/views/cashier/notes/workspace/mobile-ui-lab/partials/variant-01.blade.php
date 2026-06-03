<main class="gform-page">
    <header class="gform-header">
        <div class="gform-header__bar"></div>
        <div class="gform-header__body">
            <h1>Buat Nota</h1>
            <p>
                Form mobile untuk transaksi bengkel. Versi ini meniru pola Google Form:
                ringan, satu kolom, dan fokus ke isi tanpa tampilan dashboard.
            </p>
        </div>
    </header>

    <section class="gform-card">
        <h2>Nama Customer <span class="gform-required">*</span></h2>
        <p class="gform-help">Isi nama pelanggan yang datang ke bengkel.</p>

        <div class="gform-field">
            <input type="text" placeholder="Jawaban Anda">
        </div>
    </section>

    <section class="gform-card">
        <h2>No. HP Customer</h2>
        <p class="gform-help">Boleh dikosongkan jika pelanggan tidak memberi nomor.</p>

        <div class="gform-field">
            <input type="tel" placeholder="Jawaban Anda">
        </div>
    </section>

    <section class="gform-card">
        <h2>Tanggal Nota <span class="gform-required">*</span></h2>

        <div class="gform-field">
            <input type="date">
        </div>
    </section>

    <section class="gform-card">
        <h2>Alasan Nota</h2>
        <p class="gform-help">Keluhan, instruksi, atau catatan umum transaksi.</p>

        <div class="gform-field">
            <textarea placeholder="Jawaban Anda"></textarea>
        </div>
    </section>

    <section class="gform-card">
        <h2>Jenis Rincian <span class="gform-required">*</span></h2>
        <p class="gform-help">Pilih jenis transaksi yang akan dibuat.</p>

        <div class="gform-option-list">
            <div class="gform-option">
                <i class="gform-radio"></i>
                <span>Produk toko</span>
            </div>

            <div class="gform-option">
                <i class="gform-radio"></i>
                <span>Servis</span>
            </div>

            <div class="gform-option">
                <i class="gform-radio"></i>
                <span>Servis + sparepart toko</span>
            </div>

            <div class="gform-option">
                <i class="gform-radio"></i>
                <span>Servis + part luar</span>
            </div>
        </div>
    </section>

    <section class="gform-card">
        <h2>Rincian Aktif</h2>
        <p class="gform-help">Contoh tampilan untuk flow servis + sparepart toko.</p>

        <div class="gform-field">
            <label>Nama servis</label>
            <input type="text" placeholder="Jawaban Anda">
        </div>

        <div class="gform-field">
            <label>Total paket</label>
            <input type="text" inputmode="numeric" placeholder="Jawaban Anda">
        </div>

        <div class="gform-field">
            <label>Sparepart</label>
            <input type="search" placeholder="Jawaban Anda">
        </div>

        <div class="gform-field">
            <label>Qty</label>
            <input type="number" min="1" value="1">
        </div>
    </section>

    <section class="gform-card">
        <h2>Pembayaran <span class="gform-required">*</span></h2>

        <div class="gform-option-list">
            <div class="gform-option">
                <i class="gform-radio"></i>
                <span>Simpan tanpa pembayaran</span>
            </div>

            <div class="gform-option">
                <i class="gform-radio"></i>
                <span>Bayar penuh</span>
            </div>

            <div class="gform-option">
                <i class="gform-radio"></i>
                <span>Bayar sebagian</span>
            </div>
        </div>
    </section>

    <div class="gform-total">
        <div>
            <span>Total nota</span>
            <strong>Rp 300.000</strong>
        </div>

        <button type="button" class="gform-button">Kirim</button>
    </div>

    <p class="gform-footer-note">
        UI preview only. Tidak terhubung ke backend.
    </p>
</main>
