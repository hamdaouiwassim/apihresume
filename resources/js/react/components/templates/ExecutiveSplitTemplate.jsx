import { useFontLoader } from "../../hooks/useFontLoader";

const ExecutiveSplitTemplate = ({ resume = {}, labels = {} }) => {
  const contact = resume.contact || {};
  const experience = resume.experience || [];
  const education = resume.education || [];
  const skills = resume.skills || [];
  const interests = resume.interests || [];
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
      isSidebar: true,
      render: () => (
        <section className="exec-section">
          <h3>{labels.education || "Education"}</h3>
          <ul className="exec-list">
            {education.map((edu, idx) => (
              <li key={`edu-${idx}`}>
                <strong>{edu.degree}</strong>
                {edu.school && ` | ${edu.school}`}
                {edu.location && ` | ${edu.location}`}
                {edu.graduated && ` (${labels.graduated || "Graduated"} ${edu.graduated})`}
              </li>
            ))}
          </ul>
        </section>
      )
    },
    {
      key: 'skills',
      hasData: skills.length > 0,
      isSidebar: true,
      render: () => (
        <section className="exec-section">
          <h3>{labels.skills || "Skills"}</h3>
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
        <section className="exec-section">
          <h3>{labels.certifications || "Certifications"}</h3>
          <ul className="exec-list">
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
        <section className="exec-section">
          <h3>{labels.languages || "Languages"}</h3>
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
        <section className="exec-section">
          <h3>{labels.hobbies || "Hobbies"}</h3>
          <ul className="exec-list">
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
        <div className="exec-section">
          <h3 className="section-title">
            {labels.workExperience || "Professional Experience"}
          </h3>
          {experience.map((role, idx) => (
            <div key={`exp-${idx}`}>
              <div className="exec-role">
                <span className="exec-role-title">{role.title}</span>
              </div>
              {formatTimeline(role.start, role.end) && (
                <p className="exec-timeline">
                  {formatTimeline(role.start, role.end)}
                </p>
              )}
              {(role.company || role.location) && (
                <p className="exec-company">
                  {[role.company, role.location].filter(Boolean).join(" | ")}
                </p>
              )}
              {role.summary && <p className="exec-summary">{role.summary}</p>}
              {role.bullets && role.bullets.length > 0 && (
                <ul className="exec-bullets">
                  {role.bullets.map((bullet, bulletIdx) => (
                    <li key={`exp-${idx}-bullet-${bulletIdx}`}>{bullet}</li>
                  ))}
                </ul>
              )}
              {role.projects && role.projects.length > 0 && (
                <div className="mt-3 ml-2 border-l-2 border-slate-200 pl-3">
                  <p className="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">{labels.projects || "Projects"}</p>
                  {role.projects.map((project, pIdx) => (
                    <div key={`exp-${idx}-proj-${pIdx}`} className="mb-2">
                      <div className="exec-role">
                        <span className="exec-role-title">{project.name}</span>
                      </div>
                      {formatTimeline(project.start, project.end) && (
                        <p className="exec-timeline">{formatTimeline(project.start, project.end)}</p>
                      )}
                      {project.technologies && <p className="exec-company text-xs">{project.technologies}</p>}
                      {project.url && <p className="text-xs" style={{ color: '#6B7280' }}>{project.url}</p>}
                      {project.description && <p className="exec-summary text-xs">{project.description}</p>}
                      {project.bullets && project.bullets.length > 0 && (
                        <ul className="exec-bullets text-xs">
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
        </div>
      )
    },
    {
      key: 'projects',
      hasData: projects.length > 0,
      isSidebar: false,
      render: () => (
        <div className="exec-section">
          <h3 className="section-title">
            {labels.projects || "Projects"}
          </h3>
          {projects.map((project, idx) => (
            <div key={`project-${idx}`}>
              <div className="exec-role">
                <span className="exec-role-title">{project.name}</span>
              </div>
              {formatTimeline(project.start, project.end) && (
                <p className="exec-timeline">
                  {formatTimeline(project.start, project.end)}
                </p>
              )}
              {project.technologies && (
                <p className="exec-company">{project.technologies}</p>
              )}
              {project.url && (
                <p className="exec-company" style={{ fontSize: '11px', color: '#6B7280' }}>
                  {project.url}
                </p>
              )}
              {project.description && <p className="exec-summary">{project.description}</p>}
              {project.bullets && project.bullets.length > 0 && (
                <ul className="exec-bullets">
                  {project.bullets.map((bullet, bulletIdx) => (
                    <li key={`project-${idx}-bullet-${bulletIdx}`}>{bullet}</li>
                  ))}
                </ul>
              )}
            </div>
          ))}
        </div>
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
    { label: "Email", value: contact.email },
    { label: "Phone", value: contact.phone },
    { label: "Location", value: contact.location },
    { label: "LinkedIn", value: contact.linkedin },
    { label: "GitHub", value: contact.github },
    { label: "Website", value: contact.website },
  ].filter((item) => item.value);

  const typo = resume.typography || {};
  const baseSize = typo.font_size || 13;
  const s = baseSize / 13;
  const fontFamily = typo.font_family || "'Source Sans Pro', 'Segoe UI', Arial, sans-serif";
  const fontUrl = useFontLoader(typo.font_id);

  const fontFormat = typo.font_format || "truetype";
  const fontFaceRule = fontUrl && typo.font_family
    ? `@font-face { font-family: '${typo.font_family}'; src: url(${fontUrl}) format('${fontFormat}'); }`
    : "";

  return (
    <div className="exec-template" data-cv-preview="true">
      <style>
        {`
          ${fontFaceRule}
          .exec-template {
            font-family: ${fontFamily};
            background: #fff;
            padding: 32px;
            color: #0f172a;
            font-size: ${baseSize}px;
            line-height: 1.45;
          }
          .exec-template * {
            box-sizing: border-box;
          }
          .exec-header {
            text-align: center;
            margin-bottom: 24px;
          }
          .exec-name {
            font-size: ${Math.round(32 * s)}px;
            letter-spacing: 0.08em;
            margin: 0;
          }
          .exec-role {
            margin-top: 6px;
            font-size: ${Math.round(13 * s)}px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #475569;
          }
          .exec-body {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 32px;
          }
          .exec-sidebar {
            border-right: 1px solid #e2e8f0;
            padding-right: 24px;
          }
          .exec-section {
            margin-bottom: 24px;
          }
          .exec-section h3 {
            font-size: ${Math.round(12 * s)}px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            margin: 0 0 10px;
            color: #1e293b;
          }
          .exec-contact-item {
            margin-bottom: 8px;
          }
          .exec-contact-label {
            font-weight: 600;
            font-size: ${Math.round(11 * s)}px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            display: block;
          }
          .exec-contact-value {
            font-size: ${Math.round(12 * s)}px;
            color: #0f172a;
          }
          .exec-list {
            list-style: none;
            padding: 0;
            margin: 0;
          }
          .exec-list li {
            font-size: ${Math.round(12 * s)}px;
            margin-bottom: 6px;
            color: #0f172a;
          }
          .exec-main .section-title {
            font-size: ${Math.round(12 * s)}px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            margin: 0 0 10px;
            color: #1e293b;
          }
          .exec-summary {
            font-size: ${Math.round(12.5 * s)}px;
            line-height: 1.5;
            color: #1e293b;
            margin-bottom: 24px;
          }
          .exec-role {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 4px;
          }
          .exec-role-title {
            font-size: ${Math.round(14 * s)}px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            text-align: left;
            width: 100%;
          }
          .exec-timeline {
            font-size: ${Math.round(11 * s)}px;
            color: #64748b;
            margin: 0 0 6px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
          }
          .exec-company {
            font-size: ${Math.round(12 * s)}px;
            color: #475569;
            margin-bottom: 8px;
            text-align: left;
            width: 100%;
          }
          .exec-bullets {
            margin: 0 0 18px 20px;
            padding: 0;
          }
          .exec-bullets li {
            font-size: ${Math.round(12 * s)}px;
            margin-bottom: 6px;
            color: #0f172a;
          }
          @media (max-width: 900px) {
            .exec-body {
              grid-template-columns: 1fr;
            }
            .exec-sidebar {
              border-right: none;
              border-bottom: 1px solid #e2e8f0;
              padding-bottom: 24px;
              margin-bottom: 24px;
            }
            .exec-role {
              flex-direction: column;
              align-items: flex-start;
              gap: 4px;
            }
            .exec-role-meta {
              text-align: left;
            }
          }
        `}
      </style>

      <div className="exec-header">
        {resume.name && <h1 className="exec-name">{resume.name.toUpperCase()}</h1>}
        {resume.tagline && (
          <p className="exec-role">{resume.tagline.toUpperCase()}</p>
        )}
      </div>

      <div className="exec-body">
        <aside className="exec-sidebar">
          {contact.profile_picture && (
            <div className="flex justify-center mb-4">
              <img
                src={contact.profile_picture}
                alt={resume.name || "Profile"}
                className="w-24 h-24 rounded-full object-cover border-2 border-slate-200"
              />
            </div>
          )}
          {contactItems.length > 0 && (
            <section className="exec-section">
              <h3>{labels.contact || "Contact"}</h3>
              {contactItems.map((item) => (
                <div className="exec-contact-item" key={item.label}>
                  <span className="exec-contact-label">{item.label}</span>
                  <span className="exec-contact-value">{item.value}</span>
                </div>
              ))}
            </section>
          )}

          {/* Render sidebar sections dynamically based on section_order */}
          {orderedSidebarSections.map((section) => (
            <div key={section.key}>{section.render()}</div>
                ))}
        </aside>

        <section className="exec-main">
          {resume.summary && (
            <div className="exec-section">
              <h3 className="section-title">
                {labels.professionalSummary || "Professional Summary"}
              </h3>
              <p className="exec-summary">{resume.summary}</p>
            </div>
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

export default ExecutiveSplitTemplate;


