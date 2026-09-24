import axiosInstance from "../api/axiosInstance";

export const getWorkCertificates = () => axiosInstance.get("/work-certificates");

export const getWorkCertificate = (id) => axiosInstance.get(`/work-certificates/${id}`);

export const createWorkCertificate = (data) => axiosInstance.post("/work-certificates", data);

export const updateWorkCertificate = (id, data) =>
  axiosInstance.put(`/work-certificates/${id}`, data);

export const deleteWorkCertificate = (id) => axiosInstance.delete(`/work-certificates/${id}`);

/**
 * @param {string|number} id
 * @param {"en"|"fr"} locale App UI language for PDF strings and date formatting.
 */
export const downloadWorkCertificatePdf = (id, locale = "en") =>
  axiosInstance.get(`/work-certificates/${id}/pdf`, {
    responseType: "blob",
    params: { locale: locale === "fr" ? "fr" : "en" },
  });
