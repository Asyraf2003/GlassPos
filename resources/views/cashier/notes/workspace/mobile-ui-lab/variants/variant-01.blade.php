<main class="page">
    <header class="form-head">
        <div class="form-head__bar"></div>
        <div class="form-head__body">
            <h1>Buat Nota</h1>
            <p>Model form klasik satu kolom. Paling aman untuk user awam.</p>
        </div>
    </header>

    <section class="card">
        <h2>Informasi Customer</h2>
        <div class="field"><label>Nama customer</label><input placeholder="Jawaban Anda"></div>
        <div class="field"><label>No. HP</label><input placeholder="Jawaban Anda"></div>
        <div class="field"><label>Alasan nota</label><textarea placeholder="Jawaban Anda"></textarea></div>
    </section>

    <section class="card">
        <h2>Jenis Rincian</h2>
        <div class="options">
            <div class="option"><span class="radio"></span><span>Produk toko</span></div>
            <div class="option"><span class="radio"></span><span>Servis</span></div>
            <div class="option"><span class="radio"></span><span>Servis + sparepart toko</span></div>
            <div class="option"><span class="radio"></span><span>Servis + part luar</span></div>
        </div>
    </section>

    <section class="card">
        <h2>Detail Rincian</h2>
        <div class="field"><label>Nama servis / produk</label><input placeholder="Jawaban Anda"></div>
        <div class="field"><label>Total paket / harga</label><input placeholder="Jawaban Anda"></div>
        <div class="field"><label>Qty</label><input value="1"></div>
    </section>

    <div class="total-bar">
        <div><small>Total nota</small><span class="total" data-total data-seed="300000">Rp 300.000</span></div>
        <button class="btn" type="button">Kirim</button>
    </div>
</main>
