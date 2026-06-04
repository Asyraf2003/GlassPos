@once
    @push('styles')
        <style>
            #cashier-note-workspace-form .card,
            #cashier-note-workspace-form .card-header,
            #cashier-note-workspace-form .card-body,
            #cashier-note-workspace-form #workspace-line-items,
            #cashier-note-workspace-form [data-line-item],
            #cashier-note-workspace-form .position-relative {
                overflow: visible !important;
            }

            #cashier-note-workspace-form [data-product-results],
            #cashier-note-workspace-form [data-service-results] {
                z-index: 1085 !important;
                max-height: min(18rem, 45vh);
                overflow-y: auto;
                overscroll-behavior: contain;
            }
        </style>
    @endpush
@endonce
