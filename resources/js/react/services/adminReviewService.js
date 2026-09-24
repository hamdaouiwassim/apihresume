import axiosInstance from "../api/axiosInstance";

export const getAdminReviews = (params = {}) => {
  return axiosInstance.get("/admin/reviews", { params });
};

export const toggleReviewPublic = (id) => {
  return axiosInstance.patch(`/admin/reviews/${id}/toggle-public`);
};

export const deleteReview = (id) => {
  return axiosInstance.delete(`/admin/reviews/${id}`);
};
