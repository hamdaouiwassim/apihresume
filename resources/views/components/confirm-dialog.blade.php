{{-- Port of components/ConfirmDialog.jsx, driven by window.confirmDialog() --}}
<div x-data x-show="$store.confirm.open" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto" @keydown.escape.window="$store.confirm.open && $store.confirm.close(false)">
    <div class="fixed inset-0 bg-black/20 backdrop-blur-sm transition-opacity" @click="$store.confirm.close(false)"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6" x-trap.noscroll="$store.confirm.open">
            <h3 class="text-lg font-semibold text-gray-900 mb-4" x-text="$store.confirm.title"></h3>
            <p class="text-sm text-gray-600 mb-6">
                <span x-text="$store.confirm.message"></span>
                <template x-if="$store.confirm.itemName">
                    <span class="block mt-2 font-semibold text-gray-900" x-text="'&quot;' + $store.confirm.itemName + '&quot;'"></span>
                </template>
            </p>
            <div class="flex justify-end gap-3">
                <button
                    type="button"
                    @click="$store.confirm.close(false)"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                    x-text="$store.confirm.cancelText"
                ></button>
                <button
                    type="button"
                    @click="$store.confirm.close(true)"
                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors"
                    x-text="$store.confirm.confirmText"
                ></button>
            </div>
        </div>
    </div>
</div>
