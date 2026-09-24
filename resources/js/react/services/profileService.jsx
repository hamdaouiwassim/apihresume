import axiosInstance from "../api/axiosInstance";

export const updateProfile = (data) => {
    // Use POST with _method=PUT for file uploads (PUT doesn't work well with FormData in some servers)
    if (data instanceof FormData) {
        data.append('_method', 'PUT');
        return axiosInstance.post("/profile", data);
    }
    // Regular object: use PUT
    return axiosInstance.put("/profile", data);
};

export const getProfile = () => {
    return axiosInstance.get("/me");
};

