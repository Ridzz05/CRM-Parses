@props(['title' => null, 'description' => null])

<section {{ $attributes->merge(['class' => 'app-card rounded-md']) }}>
    @if ($title || $description)
        <div class="border-b border-[color-mix(in_srgb,var(--app-border)_68%,transparent)] px-4 py-3">
            @if ($title)
                <h2 class="text-base font-semibold text-[var(--app-text)]">{{ $title }}</h2>
            @endif
            @if ($description)
                <p class="mt-1 text-sm text-[var(--app-muted)]">{{ $description }}</p>
            @endif
        </div>
    @endif

    <div class="p-4">
        {{ $slot }}
    </div>
</section>
