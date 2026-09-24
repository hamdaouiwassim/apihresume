import axiosInstance from "../api/axiosInstance";

// store language
export const storeLanguage = (data) => axiosInstance.post("/languages", data);

// update language
export const updateLanguage = (data, id) => axiosInstance.put("/languages/" + id, data);

// delete language
export const deleteLanguage = (id) => axiosInstance.delete("/languages/" + id);

