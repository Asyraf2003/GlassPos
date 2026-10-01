<div class="form-group mb-3">
    <label for="{{ $prefix }}-bank-name" class="form-label">Bank (optional)</label>
    <input type="text" id="{{ $prefix }}-bank-name" name="bank_name" maxlength="255" class="form-control"
        value="{{ old('bank_name', $bankName ?? '') }}">
    @error('bank_name')<div class="text-danger">{{ $message }}</div>@enderror
</div>
<div class="form-group mb-3">
    <label for="{{ $prefix }}-bank-account-number" class="form-label">Nomor Rekening (optional)</label>
    <input type="text" id="{{ $prefix }}-bank-account-number" name="bank_account_number" maxlength="255" class="form-control"
        value="{{ old('bank_account_number', $bankAccountNumber ?? '') }}">
    @error('bank_account_number')<div class="text-danger">{{ $message }}</div>@enderror
</div>
