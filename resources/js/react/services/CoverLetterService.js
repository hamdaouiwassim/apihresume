import axiosInstance from "../api/axiosInstance";

export const getCoverLetters = () => {
    return axiosInstance.get('/cover-letters');
};

export const getCoverLetter = (id) => {
    return axiosInstance.get(`/cover-letters/${id}`);
};

export const createCoverLetter = (data) => {
    return axiosInstance.post('/cover-letters', data);
};

export const updateCoverLetter = (id, data) => {
    return axiosInstance.put(`/cover-letters/${id}`, data);
};

export const deleteCoverLetter = (id) => {
    return axiosInstance.delete(`/cover-letters/${id}`);
};

export const getAvailableTemplates = (params = {}) => {
    const queryString = new URLSearchParams(params).toString();
    return axiosInstance.get(`/cover-letter-templates?${queryString}`);
};

export const downloadCoverLetterPDF = (id) => {
    return axiosInstance.get(`/cover-letters/${id}/pdf`, {
        responseType: 'blob'
    });
};
