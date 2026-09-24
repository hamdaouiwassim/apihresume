import { useFontLoader } from "../../hooks/useFontLoader";

const ModernProfessionalTemplate = ({ resume = {}, labels = {} }) => {
  const contact = resume.contact || {};
  const experience = resume.experience || [];
  const education = resume.education || [];
  const skills = resume.skills || [];
  const certifications = resume.certifications || [];
  const languages = resume.languages || [];
  const hobbies = resume.hobbies || [];
  const projects = resume.projects || [];

  // Default section order if not specified
  const sectionOrder = resume.section_order || ['personal', 'socialMedia', 'experience', 'education', 'skills', 'hobbies', 'certificates', 'languages', 'projects'];


  // Define all sections with their data and render functions
  const availableSections = [
    {
      key: 'education',
      hasData: education.length > 0,
      isSidebar: false,
      render: () => (
        <section className="modern-section">
          <h3 className="modern-section-title">{labels.education || "Education"}</h3>
          {education.map((edu, idx) => (
            <div key={`edu-${idx}`} className="modern-item">
              <div className="modern-item-header">
                <strong className="modern-item-title">{edu.degree}</strong>
                {edu.graduated && (
                  <span className="modern-item-date">{edu.graduated}</span>
                )}
              </div>
              {(edu.school || edu.location) && (
                <p className="modern-item-subtitle">
                  {[edu.school, edu.location].filter(Boolean).join(' | ')}
                </p>
              )}
            </div>
          ))}
        </section>
      )
    },
    {
      key: 'skills',
      hasData: skills.length > 0,
      isSidebar: true,
      render: () => (
        <section className="modern-section">
          <h3 className="modern-section-title">{labels.skills || "Technical Skills"}</h3>
          <div className="flex flex-wrap gap-1.5">
            {skills.map((skill, idx) => (
              <span key={`skill-${idx}`} className="inline-block bg-slate-100 text-slate-700 px-2.5 py-0.5 rounded text-sm">{skill}</span>
            ))}
          </div>
        </section>
      )
    },
    {
      key: 'certificates',
      hasData: certifications.length > 0,
      isSidebar: true,
      render: () => (
        <section className="modern-section">
          <h3 className="modern-section-title">{labels.certifications || "Activity & Achievement"}</h3>
          <ul className="modern-list">
            {certifications.map((cert, idx) => (
              <li key={`cert-${idx}`}>{cert}</li>
            ))}
          </ul>
        </section>
      )
    },
    {
      key: 'languages',
      hasData: languages.length > 0,
      isSidebar: true,
      render: () => (
        <section className="modern-section">
          <h3 className="modern-section-title">{labels.languages || "Languages"}</h3>
          <div className="flex flex-wrap gap-1.5">
            {languages.map((lang, idx) => (
              <span key={`lang-${idx}`} className="inline-block bg-slate-100 text-slate-700 px-2.5 py-0.5 rounded text-sm">{lang}</span>
            ))}
          </div>
        </section>
      )
    },
    {
      key: 'hobbies',
      hasData: hobbies.length > 0,
      isSidebar: true,
      render: () => (
        <section className="modern-section">
          <h3 className="modern-section-title">{labels.hobbies || "Hobbies"}</h3>
          <ul className="modern-list">
            {hobbies.map((hobby, idx) => (
              <li key={`hobby-${idx}`}>{hobby}</li>
            ))}
          </ul>
        </section>
      )
    },
    {
      key: 'experience',
      hasData: experience.length > 0,
      isSidebar: false,
      render: () => (
        <section className="modern-section">
          <h3 className="modern-section-title">{labels.workExperience || "Freelance Experience"}</h3>
          {experience.map((role, idx) => (
            <div key={`exp-${idx}`} className="modern-item">
              <div className="modern-item-header">
                <strong className="modern-item-title">{role.title}</strong>
                {formatTimeline(role.start, role.end) && (
                  <span className="modern-item-date">{formatTimeline(role.start, role.end)}</span>
                )}
              </div>
              {(role.company || role.location) && (
                <p className="modern-item-subtitle">
                  {[role.company, role.location].filter(Boolean).join(' | ')}
                </p>
              )}
              {role.summary && <p className="modern-item-description">{role.summary}</p>}
              {role.bullets && role.bullets.length > 0 && (
                <ul className="modern-bullets">
                  {role.bullets.map((bullet, bulletIdx) => (
                    <li key={`exp-${idx}-bullet-${bulletIdx}`}>{bullet}</li>
                  ))}
                </ul>
              )}
              {role.projects && role.projects.length > 0 && (
                <div className="mt-3 ml-2 border-l-2 border-slate-200 pl-3">
                  <p className="modern-section-title text-xs mb-2">{labels.projects || "Projects"}</p>
                  {role.projects.map((project, pIdx) => (
                    <div key={`exp-${idx}-proj-${pIdx}`} className="modern-item mb-2">
                      <div className="modern-item-header">
                        <strong className="modern-item-title">{project.name}</strong>
                        {formatTimeline(project.start, project.end) && (
                          <span className="modern-item-date">{formatTimeline(project.start, project.end)}</span>
                        )}
                      </div>
                      {project.technologies && <p className="modern-item-subtitle">{project.technologies}</p>}
                      {project.url && <p className="text-xs" style={{ color: '#6B7280' }}>{project.url}</p>}
                      {project.description && <p className="modern-item-description">{project.description}</p>}
                      {project.bullets && project.bullets.length > 0 && (
                        <ul className="modern-bullets">
                          {project.bullets.map((bullet, bIdx) => (
                            <li key={`exp-${idx}-proj-${pIdx}-b-${bIdx}`}>{bullet}</li>
                          ))}
                        </ul>
                      )}
                    </div>
                  ))}
                </div>
              )}
            </div>
          ))}
        </section>
      )
    },
    {
      key: 'projects',
      hasData: projects.length > 0,
      isSidebar: false,
      render: () => (
        <section className="modern-section">
          <h3 className="modern-section-title">{labels.projects || "Projects"}</h3>
          {projects.map((project, idx) => (
            <div key={`project-${idx}`} className="modern-item">
              <div className="modern-item-header">
                <strong className="modern-item-title">{project.name}</strong>
                {formatTimeline(project.start, project.end) && (
                  <span className="modern-item-date">{formatTimeline(project.start, project.end)}</span>
                )}
              </div>
              {project.technologies && (
                <p className="modern-item-subtitle">{project.technologies}</p>
              )}
              {project.description && <p className="modern-item-description">{project.description}</p>}
              {project.bullets && project.bullets.length > 0 && (
                <ul className="modern-bullets">
                  {project.bullets.map((bullet, bulletIdx) => (
                    <li key={`project-${idx}-bullet-${bulletIdx}`}>{bullet}</li>
                  ))}
                </ul>
              )}
            </div>
          ))}
        </section>
      )
    },
  ];

  // Get sections that have data, ordered by sectionOrder
  const orderedSidebarSections = sectionOrder
    .map(sectionKey => availableSections.find(section => section.key === sectionKey))
    .filter(section => section && section.hasData && section.isSidebar);

  const orderedMainSections = sectionOrder
    .map(sectionKey => availableSections.find(section => section.key === sectionKey))
    .filter(section => section && section.hasData && !section.isSidebar);


  const formatTimelineLabel = (value) => {
    if (!value) return "";
    const normalized = value.toString().trim();
    if (!normalized) return "";
    if (/present/i.test(normalized)) return "Present";
    const date = new Date(normalized);
    if (Number.isNaN(date.getTime())) return normalized;
    return date.toLocaleDateString(undefined, {
      month: "short",
      year: "numeric",
    });
  };

  const formatTimeline = (start, end) => {
    const startLabel = formatTimelineLabel(start);
    const endLabel = formatTimelineLabel(end);
    if (!startLabel && !endLabel) return "";
    if (!endLabel) return startLabel;
    if (!startLabel) return endLabel;
    return `${startLabel} – ${endLabel}`;
  };

  const contactItems = [
    { label: "Phone", value: contact.phone },
    { label: "Email", value: contact.email },
    { label: "LinkedIn", value: contact.linkedin },
    { label: "GitHub", value: contact.github },
    { label: "Website", value: contact.website },
  ].filter((item) => item.value);

  const typo = resume.typography || {};
  const baseSize = typo.font_size || 13;
  const s = baseSize / 13;
  const fontFamily = typo.font_family || "'Segoe UI', Arial, sans-serif";
  const fontUrl = useFontLoader(typo.font_id);

  const fontFormat = typo.font_format || "truetype";
  const fontFaceRule = fontUrl && typo.font_family
    ? `@font-face { font-family: '${typo.font_family}'; src: url(${fontUrl}) format('${fontFormat}'); }`
    : "";

  return (
    <div className="modern-template" data-cv-preview="true">
      <style>
        {`
          ${fontFaceRule}
          .modern-template {
            font-family: ${fontFamily};
            color: #333;
            font-size: ${baseSize}px;
            line-height: 1.5;
          }
          .modern-template * {
            box-sizing: border-box;
          }
          .modern-body {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 24px;
            background: #fff;
            padding: 24px;
            border-radius: 4px;
          }
          .modern-sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
          }
          .modern-profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 16px;
            border: 3px solid #e0e0e0;
          }
          .modern-section {
            margin-bottom: 20px;
          }
          .modern-section-title {
            font-size: ${Math.round(12 * s)}px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 12px;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 6px;
          }
          .modern-contact-item {
            margin-bottom: 10px;
            font-size: ${Math.round(12 * s)}px;
          }
          .modern-contact-label {
            font-weight: 600;
            color: #666;
            display: block;
            margin-bottom: 2px;
          }
          .modern-contact-value {
            color: #333;
            word-break: break-word;
          }
          .modern-list {
            list-style: none;
            padding: 0;
            margin: 0;
          }
          .modern-list li {
            font-size: ${Math.round(12 * s)}px;
            margin-bottom: 6px;
            color: #333;
            padding-left: 0;
          }
          .modern-main {
            display: flex;
            flex-direction: column;
            gap: 20px;
          }
          .modern-header {
            margin-bottom: 16px;
          }
          .modern-name {
            font-size: ${Math.round(28 * s)}px;
            font-weight: 700;
            margin: 0 0 4px;
            color: #333;
          }
          .modern-title {
            font-size: ${Math.round(16 * s)}px;
            color: #666;
            margin: 0 0 8px;
          }
          .modern-location {
            font-size: ${Math.round(13 * s)}px;
            color: #666;
            display: flex;
            align-items: center;
            gap: 4px;
          }
          .modern-summary {
            font-size: ${Math.round(13 * s)}px;
            line-height: 1.6;
            color: #333;
            margin-bottom: 20px;
          }
          .modern-item {
            margin-bottom: 16px;
          }
          .modern-item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 4px;
          }
          .modern-item-title {
            font-size: ${Math.round(14 * s)}px;
            font-weight: 600;
            color: #333;
          }
          .modern-item-date {
            font-size: ${Math.round(12 * s)}px;
            color: #666;
            white-space: nowrap;
          }
          .modern-item-subtitle {
            font-size: ${Math.round(12 * s)}px;
            color: #666;
            margin: 0 0 8px;
          }
          .modern-item-description {
            font-size: ${Math.round(12 * s)}px;
            color: #333;
            line-height: 1.5;
            margin: 0 0 8px;
          }
          .modern-bullets {
            margin: 0 0 12px 16px;
            padding: 0;
            list-style: disc;
          }
          .modern-bullets li {
            font-size: ${Math.round(12 * s)}px;
            margin-bottom: 4px;
            color: #333;
          }
          @media (max-width: 900px) {
            .modern-body {
              grid-template-columns: 1fr;
            }
            .modern-profile-picture {
              width: 100px;
              height: 100px;
            }
            .modern-item-header {
              flex-direction: column;
              gap: 4px;
            }
          }
        `}
      </style>

      <div className="modern-body">
        <aside className="modern-sidebar">
          {/* Profile Picture */}
          {contact.profile_picture && (
            <img
              src={contact.profile_picture}
              alt={resume.name || "Profile"}
              className="modern-profile-picture"
            />
          )}

          {/* Contact Information */}
          {contactItems.length > 0 && (
            <section className="modern-section">
              <h3 className="modern-section-title">{labels.contact || "Contact"}</h3>
              {contactItems.map((item) => (
                <div className="modern-contact-item" key={item.label}>
                  <span className="modern-contact-label">{item.label}</span>
                  <span className="modern-contact-value">{item.value}</span>
                </div>
              ))}
            </section>
          )}

          {/* Render sidebar sections dynamically based on section_order */}
          {orderedSidebarSections.map((section) => (
            <div key={section.key}>{section.render()}</div>
          ))}
        </aside>

        <section className="modern-main">
          {/* Header with Name, Title, and Location */}
          <div className="modern-header">
            {resume.name && <h1 className="modern-name">{resume.name}</h1>}
            {resume.tagline && <p className="modern-title">{resume.tagline}</p>}
            {contact.location && (
              <p className="modern-location">
                <span>📍</span> {contact.location}
              </p>
            )}
          </div>

          {/* About Me / Professional Summary */}
          {resume.summary && (
            <section className="modern-section">
              <h3 className="modern-section-title">{labels.professionalSummary || "About Me"}</h3>
              <p className="modern-summary">{resume.summary}</p>
            </section>
          )}

          {/* Render main content sections dynamically based on section_order */}
          {orderedMainSections.map((section) => (
            <div key={section.key}>{section.render()}</div>
          ))}
        </section>
      </div>
    </div>
  );
};

export default ModernProfessionalTemplate;
