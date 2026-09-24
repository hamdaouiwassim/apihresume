import axiosInstance from "../api/axiosInstance";

export const inviteCollaborator = (resumeId, email, allowedSections = null) => {
    const payload = { email };
    if (allowedSections !== null && Array.isArray(allowedSections)) {
        payload.allowed_sections = allowedSections;
    }
    return axiosInstance.post(`/resumes/${resumeId}/collaborators/invite`, payload);
};

export const getCollaborators = (resumeId) => {
    return axiosInstance.get(`/resumes/${resumeId}/collaborators`);
};

export const removeCollaborator = (resumeId, collaboratorId) => {
    return axiosInstance.delete(`/resumes/${resumeId}/collaborators/${collaboratorId}`);
};

export const acceptCollaborationInvitation = (token) => {
    return axiosInstance.post(`/collaborate/accept/${token}`);
};

export const getPendingInvitations = () => {
    return axiosInstance.get('/collaborations/pending');
};

export const acceptInvitation = (invitationId) => {
    return axiosInstance.post(`/collaborations/${invitationId}/accept`);
};

export const refuseInvitation = (invitationId) => {
    return axiosInstance.post(`/collaborations/${invitationId}/refuse`);
};




