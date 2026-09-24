import axiosInstance, { prepareSpaRequest } from "../api/axiosInstance";

export { prepareSpaRequest };

export const register = async (payload) => {
  await prepareSpaRequest(true);
  const res = await axiosInstance.post("/register", payload);
  await prepareSpaRequest(true);
  return res;
};

export const login = async (payload) => {
  await prepareSpaRequest(true);
  const res = await axiosInstance.post("/login", payload);
  await prepareSpaRequest(true);
  return res;
};

export const getGoogleAuthUrl = () => axiosInstance.get("/auth/google/url");
export const getLinkedInAuthUrl = () => axiosInstance.get("/auth/linkedin/url");

export const getGitHubImportUrl = (config) =>
  axiosInstance.get("/auth/github/import/url", config);

export const disconnectGitHubImport = async () => {
  await prepareSpaRequest(true);
  return axiosInstance.post("/auth/github/import/disconnect");
};
export const exchangeSocialAuthCode = (code) =>
  axiosInstance.post("/auth/social/exchange", { code });

export const resendVerificationEmail = async () => {
  await prepareSpaRequest(true);
  return axiosInstance.post("/email/verification-notification");
};

export const getMe = () => axiosInstance.get("/me");

export const logout = async () => {
  await prepareSpaRequest(true);
  return axiosInstance.post("/logout");
};
