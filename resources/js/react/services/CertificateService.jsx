import axiosInstance from "../api/axiosInstance";

// store certificate
export const storeCertificate = (data) => axiosInstance.post("/certificates", data);

// update certificate
export const updateCertificate = (data, id) => axiosInstance.put("/certificates/" + id, data);

// delete certificate
export const deleteCertificate = (id) => axiosInstance.delete("/certificates/" + id);

