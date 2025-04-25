@php
    $colors = [
        'proses' => 'warning',
        'batal' => 'danger',
        'selesai' => 'success',
    ];
@endphp

<div class="grid auto-cols-fr gap-y-2">
    <div class="columns-[--cols-default] fi-fo-radio gap-3 flex flex-wrap">
        @foreach($getOptions() as $value => $label)
            <div>
                <input
                    type="radio"
                    id="{{ $getId() }}-{{ $value }}"
                    name="{{ $getName() }}"
                    value="{{ $value }}"
                    {{ $isDisabled() ? 'disabled' : '' }}
                    {{ $getState() === $value ? 'checked' : '' }}
                    class="peer pointer-events-none absolute opacity-0"
                />

                <label
                    for="{{ $getId() }}-{{ $value }}"
                    @class([
                        'fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg cursor-pointer',
                        'fi-color-custom fi-btn-color-' . $colors[$value] . ' fi-color-' . $colors[$value],
                        'fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm',
                        'bg-white text-gray-950 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:hover:bg-white/10',
                        'ring-1 ring-gray-950/10 dark:ring-white/20',
                        'peer-checked:bg-' . $colors[$value] . '-600 peer-checked:text-white peer-checked:ring-0 peer-checked:hover:bg-' . $colors[$value] . '-500',
                        'dark:peer-checked:bg-' . $colors[$value] . '-500 dark:peer-checked:hover:bg-' . $colors[$value] . '-400',
                        'peer-checked:focus-visible:ring-' . $colors[$value] . '-500/50 dark:peer-checked:focus-visible:ring-' . $colors[$value] . '-400/50',
                        'peer-focus-visible:z-10 peer-focus-visible:ring-2 peer-focus-visible:ring-gray-950/10 dark:peer-focus-visible:ring-white/20',
                    ])
                >
                    <span class="fi-btn-label">
                        {{ $label }}
                    </span>
                </label>
            </div>
        @endforeach
    </div>
</div> 