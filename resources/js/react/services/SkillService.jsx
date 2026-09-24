import axiosInstance from "../api/axiosInstance";

// store skill
export const storeSkill = (data) => axiosInstance.post("/skills", data);

// update skill
export const updateSkill = (data, id) => axiosInstance.put("/skills/" + id, data);

// delete skill
export const deleteSkill = (id) => axiosInstance.delete("/skills/" + id);

