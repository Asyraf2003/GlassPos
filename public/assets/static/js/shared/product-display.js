(() => {
  const present = (value) => value !== null && value !== undefined && String(value).trim() !== "";
  const identity = (row = {}) => {
    const parts = [row.code ?? row.kode_barang, row.name ?? row.nama_barang ?? row.product_name ?? row.label,
      row.brand ?? row.merek, row.size ?? row.ukuran].filter(present);
    if (present(row.available_stock)) parts.push(`sisa: ${row.available_stock} stok`);
    return parts.join(" | ");
  };
  const price = (row = {}) => {
    const value = row.default_unit_price_rupiah ?? row.price_rupiah ?? row.unit_price_rupiah;
    return present(value) ? `Rp${Number(value).toLocaleString("id-ID")} / pcs` : "";
  };
  window.ProductDisplay = { identity, price };
})();
