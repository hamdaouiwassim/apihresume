import { buildResumeTemplateData } from './resumeTemplateMapper';
import { deriveTemplateLayout } from './templateStyles';

/** Map admin API resume payload to editor form shape for template preview. */
export function formatAdminResumeForPreview(resume) {
  if (!resume) {
    return {
      full_name: '',
      email: '',
      phone: '',
      location: '',
      job_title: '',
      professional_summary: '',
      avatar: '',
      experiences: [],
      educations: [],
      skills: [],
      hobbies: [],
      certificates: [],
      languages: [],
      template_layout: 'classic',
      template_id: null,
    };
  }

  const basicInfo = resume.basicInfo || resume.basic_info || {};
  const templateLayout =
    resume.template_layout || deriveTemplateLayout(resume.template) || 'classic';
  const templateId = resume.template_id || resume.template?.id || null;

  return {
    full_name: basicInfo.full_name || resume.name || '',
    email: basicInfo.email || '',
    phone: basicInfo.phone || '',
    location: basicInfo.location || '',
    job_title: basicInfo.job_title || '',
    professional_summary: basicInfo.professional_summary || basicInfo.summary || '',
    avatar: basicInfo.avatar || '',
    linkedin: basicInfo.linkedin || '',
    website: basicInfo.website || basicInfo.github || '',
    experiences: resume.experiences || [],
    educations: resume.educations || [],
    skills: resume.skills || [],
    hobbies: resume.hobbies || resume.interests || [],
    certificates: resume.certificates || [],
    languages: resume.languages || [],
    projects: resume.projects || [],
    template_layout: templateLayout,
    template_id: templateId,
  };
}

export function buildAdminResumePreviewData(resume, locale, { present } = {}) {
  const formData = formatAdminResumeForPreview(resume);
  return buildResumeTemplateData(formData, locale, { present });
}
