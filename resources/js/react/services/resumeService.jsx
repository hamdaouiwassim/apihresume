import axiosInstance from "../api/axiosInstance";

const TEMPLATES_CACHE_TTL_MS = 5 * 60 * 1000;
let templatesCacheEntry = null;
let templatesInflight = null;

// store resume
export const store = (data) => axiosInstance.post("/resumes", data);

// update resume
export const update = (id, data) => axiosInstance.put(`/resumes/${id}`, data);

export const updatePublicProfile = (resumeId, data) =>
  axiosInstance.patch(`/resumes/${resumeId}/public-profile`, data);

// get resume by resume id
export const getByResumeId = (resumeId) => axiosInstance.get(`/resumes/${resumeId}`);

/** Merge OpenID profile (+ optional headline) into resume basic info / experiences */
export const importLinkedInProfileToResume = (resumeId) =>
  axiosInstance.post(`/resumes/${resumeId}/import/linkedin`);

// get all resumes
export const getMyResumes = () => axiosInstance.get("/resumes");

// delete resume
export const remove = (id) => axiosInstance.delete(`/resumes/${id}`);

// save new resume (wrapper for store)
export const saveNewResume = async (data) =>  axiosInstance.post("/resumes",data);

// export other resume-related services as needed

// resume models, templates, etc. can also be managed here

/**
 * List templates with short in-memory cache + in-flight deduplication
 * (reduces repeat calls from editor, create flow, template picker).
 */
export const getTemplates = () => {
  const now = Date.now();
  if (templatesCacheEntry && templatesCacheEntry.expiresAt > now) {
    return Promise.resolve(templatesCacheEntry.response);
  }
  if (templatesInflight) {
    return templatesInflight;
  }
  templatesInflight = axiosInstance
    .get("/templates")
    .then((response) => {
      templatesCacheEntry = { response, expiresAt: Date.now() + TEMPLATES_CACHE_TTL_MS };
      templatesInflight = null;
      return response;
    })
    .catch((err) => {
      templatesInflight = null;
      throw err;
    });
  return templatesInflight;
};

/** Call after changing templates in admin if you need fresh list immediately. */
export const invalidateTemplatesListCache = () => {
  templatesCacheEntry = null;
  templatesInflight = null;
};