@if ($note['can_show_correction_actions'])
    <div class="card">
        <div class="card-body">
            <div class="fw-bold mb-1">Koreksi Nota Lunas</div>
            <div class="text-muted small mb-3">
                Gunakan koreksi hanya untuk nota yang sudah lunas. Pilih jenis koreksi yang sesuai agar perubahan tetap jelas.
            </div>

            <div class="row g-3">
                <div class="col-12 col-xl-6">
                    <div class="border rounded p-3 h-100">
                        <div class="fw-bold mb-1">Koreksi Status</div>
                        <div class="small text-muted mb-3">
                            Ubah status pekerjaan pada baris yang sudah dibayar tanpa mengubah nominal servis.
                        </div>

                        @if (
                            old('_correction_form') === 'status'
                            && (
                                $errors->has('line_no')
                                || $errors->has('target_status')
                                || $errors->has('reason')
                                || $errors->has('correction')
                            )
                        )
                            <div class="alert alert-danger py-2 px-3 mb-3">
                                @if ($errors->has('line_no'))
                                    <div>{{ $errors->first('line_no') }}</div>
                                @endif
                                @if ($errors->has('target_status'))
                                    <div>{{ $errors->first('target_status') }}</div>
                                @endif
                                @if ($errors->has('reason'))
                                    <div>{{ $errors->first('reason') }}</div>
                                @endif
                                @if ($errors->has('correction'))
                                    <div>{{ $errors->first('correction') }}</div>
                                @endif
                            </div>
                        @endif

                        <form method="POST" action="{{ $statusCorrectionAction }}">
                            @csrf
                            <input type="hidden" name="_correction_form" value="status">

                            <div class="mb-3">
                                <label class="form-label">Baris</label>
                                <select class="form-select" name="line_no">
                                    @foreach ($note['rows'] as $row)
                                        <option
                                            value="{{ $row['line_no'] }}"
                                            {{
                                                old('_correction_form') === 'status'
                                                && (string) old('line_no') === (string) $row['line_no']
                                                    ? 'selected'
                                                    : ''
                                            }}
                                        >
                                            Baris {{ $row['line_no'] }} - {{ $row['type_label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Target Status</label>
                                <select class="form-select" name="target_status">
                                    @foreach ($statusOptions as $option)
                                        <option
                                            value="{{ $option['value'] }}"
                                            {{
                                                old('_correction_form') === 'status'
                                                && old('target_status') === $option['value']
                                                    ? 'selected'
                                                    : ''
                                            }}
                                        >
                                            {{ $option['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Alasan Koreksi</label>
                                <textarea class="form-control" name="reason" rows="3" required>{{ old('_correction_form') === 'status' ? old('reason') : '' }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-warning">
                                Simpan Koreksi Status
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-12 col-xl-6">
                    <div class="border rounded p-3 h-100">
                        <div class="fw-bold mb-1">Koreksi Nominal Servis</div>
                        <div class="small text-muted mb-3">
                            Gunakan hanya untuk rincian servis ketika nominal servis perlu disesuaikan setelah pembayaran.
                        </div>

                        @if (
                            old('_correction_form') === 'service_only'
                            && (
                                $errors->has('line_no')
                                || $errors->has('service_name')
                                || $errors->has('service_price_rupiah')
                                || $errors->has('part_source')
                                || $errors->has('reason')
                                || $errors->has('correction')
                            )
                        )
                            <div class="alert alert-danger py-2 px-3 mb-3">
                                @if ($errors->has('line_no'))
                                    <div>{{ $errors->first('line_no') }}</div>
                                @endif
                                @if ($errors->has('service_name'))
                                    <div>{{ $errors->first('service_name') }}</div>
                                @endif
                                @if ($errors->has('service_price_rupiah'))
                                    <div>{{ $errors->first('service_price_rupiah') }}</div>
                                @endif
                                @if ($errors->has('part_source'))
                                    <div>{{ $errors->first('part_source') }}</div>
                                @endif
                                @if ($errors->has('reason'))
                                    <div>{{ $errors->first('reason') }}</div>
                                @endif
                                @if ($errors->has('correction'))
                                    <div>{{ $errors->first('correction') }}</div>
                                @endif
                            </div>
                        @endif

                        <form method="POST" action="{{ $serviceOnlyCorrectionAction }}">
                            @csrf
                            <input type="hidden" name="_correction_form" value="service_only">
                            <input type="hidden" name="base_revision_id" value="{{ old('base_revision_id', $note['current_revision_id'] ?? '') }}">

                            <div class="mb-3">
                                <label class="form-label">Baris Service</label>
                                <select class="form-select" name="line_no">
                                    @foreach ($note['rows'] as $row)
                                        @if ($row['can_correct_service_only'])
                                            <option
                                                value="{{ $row['line_no'] }}"
                                                {{
                                                    old('_correction_form') === 'service_only'
                                                    && (string) old('line_no') === (string) $row['line_no']
                                                        ? 'selected'
                                                        : ''
                                                }}
                                            >
                                                Baris {{ $row['line_no'] }} - {{ $row['type_label'] }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nama Servis</label>
                                <input
                                    class="form-control"
                                    name="service_name"
                                    value="{{ old('_correction_form') === 'service_only' ? old('service_name') : '' }}"
                                    required
                                >
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Harga Servis</label>
                                <input
                                    type="number"
                                    min="1"
                                    class="form-control"
                                    name="service_price_rupiah"
                                    value="{{ old('_correction_form') === 'service_only' ? old('service_price_rupiah') : '' }}"
                                    required
                                >
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Sumber Part</label>
                                <select class="form-select" name="part_source">
                                    @foreach ($partSourceOptions as $option)
                                        <option
                                            value="{{ $option['value'] }}"
                                            {{
                                                old('_correction_form') === 'service_only'
                                                && old('part_source') === $option['value']
                                                    ? 'selected'
                                                    : ($option['value'] === 'none' ? 'selected' : '')
                                            }}
                                        >
                                            {{ $option['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Alasan Koreksi</label>
                                <textarea class="form-control" name="reason" rows="3" required>{{ old('_correction_form') === 'service_only' ? old('reason') : '' }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-warning">
                                Simpan Koreksi Nominal
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
