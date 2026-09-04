@php
    $directUploadAssetPath = 'assets/static/js/shared/supplier-payment-proof-direct-upload.js';
    $useAppOrigin = app()->environment(['local', 'testing'])
        || in_array(request()->getHost(), ['127.0.0.1', 'localhost'], true);
    $directUploadAssetUrl = $useAppOrigin ? '/'.$directUploadAssetPath : asset($directUploadAssetPath);
@endphp
<script src="{{ $directUploadAssetUrl }}?v={{ config('app.asset_version') }}"></script>
