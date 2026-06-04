<div
    id="workspace-item-type-menu"
    class="workspace-item-type-menu d-none position-absolute end-0 mt-2"
>
    <div class="workspace-item-type-menu-body">
        @foreach ($itemTypeOptions as $option)
            <button
                type="button"
                class="workspace-item-type-option"
                data-add-item-type="{{ $option['type'] }}"
            >
                {{ $option['label'] }}
            </button>
        @endforeach
    </div>
</div>
