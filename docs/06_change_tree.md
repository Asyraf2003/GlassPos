# 🗺️ Central Documentation Map (Standard Hybrid)

Daftar ini adalah peta navigasi untuk seluruh dokumentasi sistem. Struktur ini menggunakan pendekatan **Hybrid**: **Struktural (Nomor)** untuk aturan tetap, dan **Kronologis (Tanggal)** untuk catatan kejadian.

## 📂 Struktur Folder (L1)

### ⚖️ [01_standards](./01_standards/)
Berisi "Kitab Suci" proyek. Aturan yang bersifat statis dan wajib dipatuhi.
*   **AI_RULES**: Protokol interaksi dengan asisten AI.
*   **DOD**: *Definition of Done* untuk validasi kualitas fitur.
*   **AI_USAGE_GUIDE**: Panduan penggunaan tools AI dalam pengembangan.

### 🏛️ [02_architecture](./02_architecture/)
Keputusan fundamental sistem yang bersifat jangka panjang.
*   **adr/**: *Architecture Decision Records*. Gunakan penomoran urut (`0001`, `0002`, dst). Jika ada perubahan kemauan user di tanggal berbeda, buat ADR baru yang me-refer nomor lama (Supercede).

### 📐 [03_blueprints](./03_blueprints/)
Rancangan teknis dan peta jalan sistem sebelum diimplementasikan.
*   Berisi skema database, kontrak API, dan alur bisnis per domain (Finance, Inventory, dsb).
*   **workflow/**: Alur kerja teknis spesifik.

### 🔄 [04_lifecycle](./04_lifecycle/)
Rekam jejak operasional dan perkembangan harian. Menggunakan format **Tanggal (YYYY-MM-DD)**.
*   **handoff/**: Catatan transisi antar sesi kerja agar konteks tidak hilang.
*   **error_log/**: Daftar bug, audit keamanan, dan catatan remedi (perbaikan).

### 🔍 [05_audits](./05_audits/)
Bukti nyata (Proof of Work) bahwa sistem berjalan sesuai data.
*   Laporan audit keamanan, stress test, dan validasi fungsional.

### 📦 [99_archive](./99_archive/)
Gudang penyimpanan untuk file legacy atau proses yang sudah selesai/merged.

---

## 🛠️ Aturan Penamaan File (Naming Convention)

1.  **Keputusan/Aturan**: Gunakan **Nomor 4 Digit** (Contoh: `0024-implementasi-rbac.md`).
2.  **Kejadian/Log/Audit**: Gunakan **Tanggal ISO** (Contoh: `2026-05-11-audit-security.md`).
3.  **Huruf Kecil**: Semua nama file menggunakan `snake-case` atau `kebab-case`.

## 📜 Log Perubahan Struktur
*   **2026-05-11**: Migrasi dari struktur Flat-Legacy ke Standard Global Hybrid L1 untuk meningkatkan scannability dan auditability.
