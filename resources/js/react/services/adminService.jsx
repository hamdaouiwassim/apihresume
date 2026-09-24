import axiosInstance from "../api/axiosInstance";
import { invalidateTemplatesListCache } from "./resumeService";

const bustPublicResumeTemplatesCache = () => {
  try {
    invalidateTemplatesListCache();
  } catch {
    /* ignore */
  }
};

// Dashboard
export const getDashboardStats = () => axiosInstance.get("/admin/dashboard");

export const getAdminAiUsageSummary = (params = {}) => {
  const queryString = new URLSearchParams(
    Object.fromEntries(Object.entries(params).filter(([, v]) => v !== "" && v != null))
  ).toString();
  return axiosInstance.get(`/admin/ai-usage/summary${queryString ? `?${queryString}` : ""}`);
};

export const getAdminAiUsageLogs = (params = {}) => {
  const queryString = new URLSearchParams(
    Object.fromEntries(Object.entries(params).filter(([, v]) => v !== "" && v != null))
  ).toString();
  return axiosInstance.get(`/admin/ai-usage/logs${queryString ? `?${queryString}` : ""}`);
};

export const getAdminAiUserLimits = (params = {}) => {
  const queryString = new URLSearchParams(
    Object.fromEntries(Object.entries(params).filter(([, v]) => v !== "" && v != null))
  ).toString();
  return axiosInstance.get(`/admin/ai-usage/user-limits${queryString ? `?${queryString}` : ""}`);
};

export const updateAdminUserTokenLimit = (userId, ai_monthly_token_limit) =>
  axiosInstance.patch(`/admin/ai-usage/users/${userId}/token-limit`, {
    ai_monthly_token_limit,
  });

// Users
export const getAdminUsers = (params = {}) => {
    const queryString = new URLSearchParams(params).toString();
    return axiosInstance.get(`/admin/users?${queryString}`);
};

export const getAdminUser = (id) => axiosInstance.get(`/admin/users/${id}`);
export const updateAdminUser = (id, data) => axiosInstance.put(`/admin/users/${id}`, data);
export const deleteAdminUser = (id) => axiosInstance.delete(`/admin/users/${id}`);
export const restoreAdminUser = (id) => axiosInstance.post(`/admin/users/${id}/restore`);
export const forceDeleteAdminUser = (id) => axiosInstance.delete(`/admin/users/${id}/force`);

export const banAdminUser = (userId, data) => axiosInstance.post(`/admin/users/${userId}/ban`, data);

export const unbanAdminUser = (userId) => axiosInstance.post(`/admin/users/${userId}/unban`);
export const restoreAdminResume = (id) => axiosInstance.post(`/admin/resumes/${id}/restore`);
export const forceDeleteAdminResume = (id) => axiosInstance.delete(`/admin/resumes/${id}/force`);
export const sendAdminUserMessage = (userId, data) => axiosInstance.post(`/admin/users/${userId}/message`, data);

export const getAdminOutboundEmails = (params = {}) => {
  const queryString = new URLSearchParams(
    Object.fromEntries(Object.entries(params).filter(([, v]) => v !== "" && v != null))
  ).toString();
  return axiosInstance.get(`/admin/outbound-emails${queryString ? `?${queryString}` : ""}`);
};

export const getAdminOutboundEmailSummary = () => axiosInstance.get("/admin/outbound-emails/summary");

export const sendAdminResumeReminder = (userId) =>
  axiosInstance.post(`/admin/users/${userId}/emails/resume-reminder`);

export const sendAdminVerificationReminder = (userId) =>
  axiosInstance.post(`/admin/users/${userId}/emails/verification-reminder`);

export const sendAdminBulkEmails = (data) => axiosInstance.post("/admin/outbound-emails/bulk", data);

export const sendAdminNewFeaturesToUser = (userId, data) =>
  axiosInstance.post(`/admin/users/${userId}/emails/new-features`, data);

export const sendAdminNewFeaturesBulk = (data) =>
  axiosInstance.post("/admin/outbound-emails/new-features", data);

// Templates
export const getAdminTemplates = (params = {}) => {
    const queryString = new URLSearchParams(params).toString();
    return axiosInstance.get(`/admin/templates?${queryString}`);
};

export const getAdminTemplate = (id) => axiosInstance.get(`/admin/templates/${id}`);
export const createAdminTemplate = (formData) =>
  axiosInstance
    .post("/admin/templates", formData, {
      headers: { "Content-Type": "multipart/form-data" },
    })
    .then((response) => {
      bustPublicResumeTemplatesCache();
      return response;
    });

export const updateAdminTemplate = (id, formData) =>
  axiosInstance
    .post(`/admin/templates/${id}?_method=PUT`, formData, {
      headers: { "Content-Type": "multipart/form-data" },
    })
    .then((response) => {
      bustPublicResumeTemplatesCache();
      return response;
    });

export const deleteAdminTemplate = (id) =>
  axiosInstance.delete(`/admin/templates/${id}`).then((response) => {
    bustPublicResumeTemplatesCache();
    return response;
  });

// Resumes
export const getAdminResumes = (params = {}) => {
    const queryString = new URLSearchParams(params).toString();
    return axiosInstance.get(`/admin/resumes?${queryString}`);
};

export const getAdminResume = (id) => axiosInstance.get(`/admin/resumes/${id}`);
export const deleteAdminResume = (id) => axiosInstance.delete(`/admin/resumes/${id}`);

// Cover Letters
export const getAdminCoverLetters = (params = {}) => {
    const queryString = new URLSearchParams(params).toString();
    return axiosInstance.get(`/admin/cover-letters?${queryString}`);
};
export const getAdminCoverLetter = (id) => axiosInstance.get(`/admin/cover-letters/${id}`);
export const deleteAdminCoverLetter = (id) => axiosInstance.delete(`/admin/cover-letters/${id}`);

// Work certificates (employment attestations)
export const getAdminWorkCertificates = (params = {}) => {
    const queryString = new URLSearchParams(params).toString();
    return axiosInstance.get(`/admin/work-certificates?${queryString}`);
};

export const getAdminWorkCertificate = (id) => axiosInstance.get(`/admin/work-certificates/${id}`);

export const deleteAdminWorkCertificate = (id) => axiosInstance.delete(`/admin/work-certificates/${id}`);

// PDF Fonts
export const getAdminFonts = () => axiosInstance.get("/admin/fonts");
export const uploadAdminFont = (formData) =>
    axiosInstance.post("/admin/fonts", formData, {
        headers: { "Content-Type": "multipart/form-data" },
    });
export const toggleAdminFont = (id) => axiosInstance.post(`/admin/fonts/${id}/toggle`);
export const deleteAdminFont = (id) => axiosInstance.delete(`/admin/fonts/${id}`);

// Active PDF fonts (for user font dropdown)
export const getActivePdfFonts = () => axiosInstance.get("/pdf-fonts/active");

// Cover Letter Templates
export const getAdminCoverLetterTemplates = (params = {}) => {
    const queryString = new URLSearchParams(params).toString();
    return axiosInstance.get(`/admin/cover-letter-templates?${queryString}`);
};

export const createAdminCoverLetterTemplate = (data) => axiosInstance.post("/admin/cover-letter-templates", data);
export const updateAdminCoverLetterTemplate = (id, data) => axiosInstance.put(`/admin/cover-letter-templates/${id}`, data);
export const deleteAdminCoverLetterTemplate = (id) => axiosInstance.delete(`/admin/cover-letter-templates/${id}`);

