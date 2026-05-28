@props(['id', 'title'])

<dialog id="{{ $id }}" {{ $attributes->merge(['class' => 'app-modal app-card rounded-md']) }}>
    <div class="flex items-center justify-between gap-4 border-b border-[color-mix(in_srgb,var(--app-border)_68%,transparent)] px-4 py-3">
        <h2 class="text-base font-semibold">{{ $title }}</h2>
        <button type="button" class="rounded px-2 py-1 text-sm text-[var(--app-muted)] hover:bg-[var(--app-surface-strong)] hover:text-[var(--app-text)]" onclick="this.closest('dialog').close()" aria-label="Close modal">x</button>
    </div>
    <div class="app-modal-body p-4">
        {{ $slot }}
    </div>
</dialog>
