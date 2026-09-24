import axiosInstance from "../api/axiosInstance";

// store project
export const storeProject = (data) => axiosInstance.post("/projects", data);

/** Preview project fields from a public GitHub repo URL (does not persist). */
export const previewGitHubProject = (resumeId, data) =>
  axiosInstance.post(`/resumes/${resumeId}/github-repo-preview`, data);

// update project
export const updateProject = (data, id) => axiosInstance.put("/projects/" + id, data);

// delete project
export const deleteProject = (id) => axiosInstance.delete("/projects/" + id);

