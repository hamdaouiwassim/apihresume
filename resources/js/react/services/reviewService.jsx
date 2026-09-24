import axiosInstance from "../api/axiosInstance";

// Submit a review
export const submitReview = (data) => axiosInstance.post("/reviews", data);

// Get user's review
export const getUserReview = () => axiosInstance.get("/reviews/my-review");

// Update user's review
export const updateReview = (id, data) => axiosInstance.put(`/reviews/${id}`, data);

// Get all reviews (for display)
export const getAllReviews = (params = {}) => {
  const queryString = new URLSearchParams(params).toString();
  return axiosInstance.get(`/reviews?${queryString}`);
};

