@props([
    'message' => null,
    'variant' => 'info',
])

<div
    id="app-snackbar"
    class="app-snackbar fixed bottom-4 left-4 right-4 z-50 rounded-lg p-4 sm:left-auto sm:w-[24rem]"
    role="status"
    aria-live="polite"
    data-open="false"
    data-variant="{{ $variant }}"
    data-session-message="{{ $message }}"
    data-session-variant="{{ $variant }}"
>
    <div class="flex items-start gap-3">
        <div data-snackbar-icon class="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full bg-[var(--app-surface-strong)] text-xs font-bold">i</div>
        <p data-snackbar-message class="text-sm leading-6">{{ $message }}</p>
        <button type="button" data-snackbar-close class="ml-auto rounded px-2 text-sm text-[var(--app-muted)] hover:bg-[var(--app-surface-strong)] hover:text-[var(--app-text)]" aria-label="Close notification">x</button>
    </div>
</div>
