import axiosInstance from "../api/axiosInstance";

// store basic info
export const storeBasicInfo = (data) => axiosInstance.post("/basic-info", data);

// upload avatar for resume basic info
export const uploadResumeAvatar = (resumeId, file) => {
  const formData = new FormData();
  formData.append("avatar", file, file.name);
  return axiosInstance.post(`/resumes/${resumeId}/basic-info/avatar`, formData);
};

// update basic info
export const updateBasicInfo = (id, data) => axiosInstance.put(`/basic-info/${id}`, data);

// get basic info by resume id
export const getBybasicInfoId = (resumeId) => axiosInstance.get(`/basic-info/${resumeId}`);

// delete basic info
export const removeBasicInfo = (id) => axiosInstance.delete(`/basic-info/${id}`);