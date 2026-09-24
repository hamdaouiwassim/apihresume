import axiosInstance from '../api/axiosInstance';

export async function fetchBillingConfig() {
  return axiosInstance.get('/billing/config');
}

export async function createCheckoutSession(region) {
  const body = region ? { region } : {};
  return axiosInstance.post('/billing/checkout', body);
}

export async function confirmCheckout({ sessionId, transactionId }) {
  const body = {};
  if (sessionId) body.session_id = sessionId;
  if (transactionId) body.transaction_id = transactionId;
  return axiosInstance.post('/billing/checkout/confirm', body);
}
