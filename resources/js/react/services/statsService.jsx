import axiosInstance from "../api/axiosInstance";

// Get public statistics
export const getStats = () => {
  return axiosInstance.get("/stats");
};

