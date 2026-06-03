<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1" name="viewport">
    <title>{{ $pageTitle }}</title>
    @include('cashier.notes.workspace.mobile-ui-lab.partials.styles')
</head>
<body>
    <main class="gform-page">
        <header class="gform-preview-head">
            <span>UI Preview</span>
            <h1>10 Create Transaction Mobile Forms</h1>
            <p>Semua tampilan standalone, mirip Google Form, tanpa Mazer dan tanpa backend.</p>
        </header>

        <nav class="gform-nav">
            @foreach (range(1, 10) as $variantNumber)
                <a href="#variant-{{ str_pad((string) $variantNumber, 2, '0', STR_PAD_LEFT) }}">
                    {{ str_pad((string) $variantNumber, 2, '0', STR_PAD_LEFT) }}
                </a>
            @endforeach
        </nav>

        @include('cashier.notes.workspace.mobile-ui-lab.partials.variant-01')
        @include('cashier.notes.workspace.mobile-ui-lab.partials.variant-02')
        @include('cashier.notes.workspace.mobile-ui-lab.partials.variant-03')
        @include('cashier.notes.workspace.mobile-ui-lab.partials.variant-04')
        @include('cashier.notes.workspace.mobile-ui-lab.partials.variant-05')
        @include('cashier.notes.workspace.mobile-ui-lab.partials.variant-06')
        @include('cashier.notes.workspace.mobile-ui-lab.partials.variant-07')
        @include('cashier.notes.workspace.mobile-ui-lab.partials.variant-08')
        @include('cashier.notes.workspace.mobile-ui-lab.partials.variant-09')
        @include('cashier.notes.workspace.mobile-ui-lab.partials.variant-10')
    </main>
</body>
</html>
