import axiosInstance from "../api/axiosInstance";

// store experience
export const storeExperience = (data) => axiosInstance.post("/experiences", data);

// update experience
export const updateExperience = (data, id) => axiosInstance.put("/experiences/" + id, data);

// delete experience
export const deleteExperience = (id) => axiosInstance.delete("/experiences/" + id);