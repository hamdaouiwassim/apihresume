/**
 * Island shim for "sonner": forwards to the page-wide Alpine toaster (window.toast),
 * so Blade pages and React islands share one toaster with the same look.
 */
const call = (type, message, options) => {
  if (window.toast) {
    return type === 'default' ? window.toast(message, options) : window.toast[type](message, options);
  }
  (window.__toastQueue = window.__toastQueue || []).push([type === 'default' ? 'info' : type, message]);
  return undefined;
};

export const toast = (message, options) => call('default', message, options);
toast.success = (m, o) => call('success', m, o);
toast.error = (m, o) => call('error', m, o);
toast.info = (m, o) => call('info', m, o);
toast.warning = (m, o) => call('warning', m, o);
toast.message = (m, o) => call('default', m, o);
toast.loading = (m, o) => call('loading', m, o);
toast.dismiss = (id) => window.toast?.dismiss(id);

export function Toaster() {
  return null;
}

export default toast;
