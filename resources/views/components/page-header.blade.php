@props(['title', 'subtitle' => null])

<div class="mb-6 flex flex-wrap items-center justify-between gap-x-6 gap-y-3">
    <div class="min-w-0">
        @isset($breadcrumbs)
            <div class="mb-1 flex items-center gap-1.5 text-sm text-zinc-500">{{ $breadcrumbs }}</div>
        @endisset
        <h1 class="text-2xl font-semibold tracking-tight">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
