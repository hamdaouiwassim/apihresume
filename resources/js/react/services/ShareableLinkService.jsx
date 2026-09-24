import axiosInstance from "../api/axiosInstance";
import axios from 'axios';
import config from "../config";

export const generateShareableLink = (resumeId, expiresInDays = 7, slug = null) => {
    return axiosInstance.post(`/resumes/${resumeId}/shareable-link/generate`, {
        expires_in_days: expiresInDays,
        slug,
    });
};

export const getCurrentShareableLink = (resumeId) => {
    return axiosInstance.get(`/resumes/${resumeId}/shareable-link`);
};

export const deactivateShareableLink = (resumeId) => {
    return axiosInstance.post(`/resumes/${resumeId}/shareable-link/deactivate`);
};

export const viewSharedResume = (token) => {
    // Create a new axios instance without auth for public access
    return axios.get(`${config.API_URL}share/${token}`, {
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
        }
    });
};

export const viewPublicWebsiteResume = (slugOrToken) => {
    return axios.get(`${config.API_URL}website/${slugOrToken}`, {
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
        }
    });
};

export const downloadPublicProfilePdf = (slug, locale = "en") => {
    return axios.get(`${config.API_URL}public/profile/${encodeURIComponent(slug)}/pdf`, {
        params: { locale },
        responseType: "blob",
        headers: {
            Accept: "application/pdf",
        },
    });
};

