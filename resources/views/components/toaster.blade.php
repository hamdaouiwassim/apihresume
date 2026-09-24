{{-- Sonner-like toaster (richColors, bottom-center, expanded) backed by Alpine.store('toasts') --}}
<section
    x-data
    aria-label="Notifications"
    aria-live="polite"
    class="pointer-events-none fixed inset-x-0 bottom-4 sm:bottom-6 z-[10000] flex flex-col items-center gap-2 px-4"
>
    <template x-for="item in $store.toasts.items" :key="item.id">
        <div
            x-show="item.visible"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0 translate-y-2"
            role="status"
            class="pointer-events-auto flex w-full max-w-[356px] items-center gap-1.5 rounded-lg border px-4 py-4 text-[13px] shadow-[0_4px_12px_rgba(0,0,0,0.1)]"
            :class="{
                'bg-[#ecfdf3] border-[#d3fde5] text-[#008a2e]': item.type === 'success',
                'bg-[#fff0f0] border-[#ffe0e1] text-[#e60000]': item.type === 'error',
                'bg-[#f0f8ff] border-[#d3e0fd] text-[#0973dc]': item.type === 'info',
                'bg-[#fffcf0] border-[#fdf5d3] text-[#dc7609]': item.type === 'warning',
                'bg-white border-[#ededed] text-[#171717]': item.type === 'default' || item.type === 'loading',
            }"
        >
            <span class="flex h-5 w-5 shrink-0 items-center justify-center" x-show="item.type !== 'default'">
                <template x-if="item.type === 'loading'">
                    <svg class="h-4 w-4 animate-spin text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                </template>
                <template x-if="item.type === 'success'">
                    <svg viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                </template>
                <template x-if="item.type === 'error'">
                    <svg viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                </template>
                <template x-if="item.type === 'info'">
                    <svg viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/></svg>
                </template>
                <template x-if="item.type === 'warning'">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd"/></svg>
                </template>
            </span>
            <div class="flex-1">
                <p class="font-medium leading-snug" x-text="item.message"></p>
                <p x-show="item.description" class="mt-0.5 whitespace-pre-line leading-snug opacity-90" x-text="item.description"></p>
            </div>
        </div>
    </template>
</section>
