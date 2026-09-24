import axiosInstance from "../api/axiosInstance";

// store education
export const storeEducation = (data) => axiosInstance.post("/educations", data);

// update education
export const updateEducation = (data, id) => axiosInstance.put("/educations/" + id, data);

// delete education
export const deleteEducation = (id) => axiosInstance.delete("/educations/" + id);