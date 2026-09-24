/**
 * Promise-based confirm dialog (port of components/ConfirmDialog.jsx).
 *
 *   if (await window.confirmDialog({ title, message, itemName, confirmText, cancelText })) { ... }
 */
export function registerConfirm(Alpine) {
    Alpine.store('confirm', {
        open: false,
        title: 'Confirm Delete',
        message: 'Are you sure you want to delete this item?',
        itemName: null,
        confirmText: 'Yes',
        cancelText: 'No',
        busy: false,
        _resolve: null,
        ask(options = {}) {
            this.title = options.title ?? 'Confirm Delete';
            this.message = options.message ?? 'Are you sure you want to delete this item?';
            this.itemName = options.itemName ?? null;
            this.confirmText = options.confirmText ?? 'Yes';
            this.cancelText = options.cancelText ?? 'No';
            this.busy = false;
            this.open = true;
            document.body.style.overflow = 'hidden';
            return new Promise((resolve) => {
                this._resolve = resolve;
            });
        },
        close(result) {
            this.open = false;
            document.body.style.overflow = '';
            if (this._resolve) {
                this._resolve(result);
                this._resolve = null;
            }
        },
    });

    window.confirmDialog = (options) => Alpine.store('confirm').ask(options);
}
