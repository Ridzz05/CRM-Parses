@props(['variant' => 'neutral', 'accent' => false])

@php
    $variants = [
        'neutral' => 'border-[color-mix(in_srgb,var(--app-border)_70%,transparent)] bg-transparent text-[var(--app-muted)]',
        'success' => 'border-[color-mix(in_srgb,var(--app-success)_50%,var(--app-border))] bg-[color-mix(in_srgb,var(--app-success)_18%,var(--app-surface))] text-[var(--app-success)]',
        'danger' => 'border-[color-mix(in_srgb,var(--app-danger)_50%,var(--app-border))] bg-[color-mix(in_srgb,var(--app-danger)_14%,var(--app-surface))] text-[var(--app-danger)]',
        'info' => 'border-[color-mix(in_srgb,var(--app-info)_50%,var(--app-border))] bg-[color-mix(in_srgb,var(--app-info)_16%,var(--app-surface))] text-[var(--app-info)]',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded border px-2 py-0.5 text-xs font-medium '.($accent ? 'app-accent-label ' : '').$variants[$variant]]) }}>
    {{ $slot }}
</span>
