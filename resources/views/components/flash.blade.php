@if (session('status'))
    <div role="status" class="mb-6 flex items-start gap-2.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3.5 py-2.5 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-100">
        <flux:icon.check-circle variant="mini" class="mt-px shrink-0 text-emerald-600 dark:text-emerald-400" />
        <span>{{ session('status') }}</span>
    </div>
@endif

@if (session('error'))
    <div role="alert" class="mb-6 flex items-start gap-2.5 rounded-lg border border-red-200 bg-red-50 px-3.5 py-2.5 text-sm text-red-900 dark:border-red-900 dark:bg-red-950/50 dark:text-red-100">
        <flux:icon.exclamation-circle variant="mini" class="mt-px shrink-0 text-red-600 dark:text-red-400" />
        <span>{{ session('error') }}</span>
    </div>
@endif
