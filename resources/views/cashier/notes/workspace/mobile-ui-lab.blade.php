<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1" name="viewport">
    <title>{{ $pageTitle }}</title>
    @include('cashier.notes.workspace.mobile-ui-lab.partials.styles')
</head>
<body class="ui-page ui-v{{ $activeVariant }}">
    @include("cashier.notes.workspace.mobile-ui-lab.variants.variant-{$activeVariant}")
    @include('cashier.notes.workspace.mobile-ui-lab.partials.dummy-scripts')
</body>
</html>
