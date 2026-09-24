import axiosInstance from "../api/axiosInstance";

// store hobby
export const storeHobby = (data) => axiosInstance.post("/hobbies", data);

// update hobby
export const updateHobby = (data, id) => axiosInstance.put("/hobbies/" + id, data);

// delete hobby
export const deleteHobby = (id) => axiosInstance.delete("/hobbies/" + id);

