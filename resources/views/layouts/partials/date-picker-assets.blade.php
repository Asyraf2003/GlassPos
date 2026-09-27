@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/extensions/flatpickr/flatpickr.css') }}?v={{ config('app.asset_version') }}">
    <link rel="stylesheet" href="{{ asset('assets/extensions/flatpickr/plugins/monthSelect/style.css') }}?v={{ config('app.asset_version') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/extensions/flatpickr/flatpickr.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/extensions/flatpickr/l10n/id.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/extensions/flatpickr/plugins/monthSelect/index.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/shared/admin-date-input.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/shared/admin-month-input.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
