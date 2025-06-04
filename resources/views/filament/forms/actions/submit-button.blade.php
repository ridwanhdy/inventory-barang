@php
    $buttonLabel = $getLabel();
@endphp

<x-filament::button
    type="submit"
    :form="$getForm()"
    :color="$getColor()"
    :size="$getSize()"
    :icon="$getIcon()"
    :icon-position="$getIconPosition()"
    :disabled="$isDisabled()"
    :wire:loading.attr="'disabled'"
    :wire:target="$getLivewireTarget()"
>
    {{ $buttonLabel }}
</x-filament::button> 