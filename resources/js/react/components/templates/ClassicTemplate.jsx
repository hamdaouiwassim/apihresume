import { useFontLoader } from "../../hooks/useFontLoader";

const ClassicTemplate = ({ resume = {}, labels = {} }) => {
  const contact = resume.contact || {};
  const experience = resume.experience || [];
  const education = resume.education || [];
  const skills = resume.skills || [];
  const interests = resume.interests || [];
  const certifications = resume.certifications || [];
  const languages = resume.languages || [];
  const projects = resume.projects || [];

  const contactLine = [
    contact.location,
    contact.email,
    contact.phone,
    contact.linkedin,
    contact.github,
    contact.website,
  ].filter(Boolean);

  // Default section order if not specified
  const sectionOrder = resume.section_order || ['personal', 'socialMedia', 'experience', 'education', 'skills', 'hobbies', 'certificates', 'languages', 'projects'];


  // Define sections with their data and render functions
  const availableSections = [
    {
      key: 'experience',
      hasData: experience.length > 0,
      render: () => (
        <>
          <h2>{labels.workExperience || "Work Experience"}</h2>
          {experience.map((role, idx) => (
            <div className="section" key={`exp-${idx}`}>
              <div className="role-row">
                <div>
                  {role.title && <h3>{role.title}</h3>}
                  {(role.company || role.location) && (
                    <p className="subheading">
                      {role.company}
                      {role.company && role.location ? ", " : ""}
                      {role.location}
                    </p>
                  )}
                </div>
                {(role.start || role.end) && (
                  <p className="subheading">
                    {role.start}
                    {role.start && role.end ? " – " : ""}
                    {role.end}
                  </p>
                )}
              </div>
              {role.summary && <p>{role.summary}</p>}
              {role.bullets && role.bullets.length > 0 && (
                <ul>
                  {role.bullets.map((bullet, bulletIdx) => (
                    <li key={`exp-${idx}-bullet-${bulletIdx}`}>{bullet}</li>
                  ))}
                </ul>
              )}
              {role.projects && role.projects.length > 0 && (
                <div className="mt-3 ml-2 border-l-2 border-slate-200 pl-4">
                  <p className="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">{labels.projects || "Projects"}</p>
                  {role.projects.map((project, pIdx) => (
                    <div key={`exp-${idx}-proj-${pIdx}`} className="mb-3">
                      <div className="role-row">
                        <div>
                          {project.name && <h4 className="text-sm font-semibold text-slate-800">{project.name}</h4>}
                          {project.technologies && <p className="subheading text-xs">{project.technologies}</p>}
                          {project.url && <p className="subheading" style={{ fontSize: '11px', color: '#6B7280' }}>{project.url}</p>}
                        </div>
                        {(project.start || project.end) && (
                          <p className="subheading text-xs">
                            {project.start}{project.start && project.end ? " – " : ""}{project.end}
                          </p>
                        )}
                      </div>
                      {project.description && <p className="text-sm">{project.description}</p>}
                      {project.bullets && project.bullets.length > 0 && (
                        <ul className="list-simple text-sm">
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
        </>
      )
    },
    {
      key: 'education',
      hasData: education.length > 0,
      render: () => (
        <>
          <h2>{labels.education || "Education"}</h2>
          {education.map((edu, idx) => (
            <div className="section" key={`edu-${idx}`}>
              <div className="role-row">
                <div>
                  {edu.degree && <h3>{edu.degree}</h3>}
                  {(edu.school || edu.location) && (
                    <p className="subheading">
                      {edu.school}
                      {edu.school && edu.location ? ", " : ""}
                      {edu.location}
                    </p>
                  )}
                </div>
                {edu.graduated && (
                  <p className="subheading">
                    {(labels.graduated || "Graduated") + ": "}{edu.graduated}
                  </p>
                )}
              </div>
            </div>
          ))}
        </>
      )
    },
    {
      key: 'skills',
      hasData: skills.length > 0,
      render: () => (
        <>
          <h2>{labels.skills || "Skills"}</h2>
          <div className="flex flex-wrap gap-1.5">
            {skills.map((skill, idx) => (
              <span key={`skill-${idx}`} className="inline-block bg-slate-100 text-slate-700 px-2.5 py-0.5 rounded text-sm">{skill}</span>
            ))}
          </div>
        </>
      )
    },
    {
      key: 'hobbies',
      hasData: interests.length > 0,
      render: () => (
        <>
          <h2>{labels.interests || "Interests"}</h2>
          <ul className="list-simple">
            {interests.map((interest, idx) => (
              <li key={`interest-${idx}`}>{interest}</li>
            ))}
          </ul>
        </>
      )
    },
    {
      key: 'certificates',
      hasData: certifications.length > 0,
      render: () => (
        <>
          <h2>{labels.certifications || "Certifications"}</h2>
          <ul className="list-simple">
            {certifications.map((cert, idx) => (
              <li key={`cert-${idx}`}>{cert}</li>
            ))}
          </ul>
        </>
      )
    },
    {
      key: 'languages',
      hasData: languages.length > 0,
      render: () => (
        <>
          <h2>{labels.languages || "Languages"}</h2>
          <div className="flex flex-wrap gap-1.5">
            {languages.map((lang, idx) => (
              <span key={`lang-${idx}`} className="inline-block bg-slate-100 text-slate-700 px-2.5 py-0.5 rounded text-sm">{lang}</span>
            ))}
          </div>
        </>
      )
    },
    {
      key: 'projects',
      hasData: projects.length > 0,
      render: () => (
        <>
          <h2>{labels.projects || "Projects"}</h2>
          {projects.map((project, idx) => (
            <div className="section" key={`project-${idx}`}>
              <div className="role-row">
                <div>
                  {project.name && <h3>{project.name}</h3>}
                  {project.technologies && (
                    <p className="subheading">{project.technologies}</p>
                  )}
                  {project.url && (
                    <p className="subheading" style={{ fontSize: '11px', color: '#6B7280' }}>
                      {project.url}
                    </p>
                  )}
                </div>
                {(project.start || project.end) && (
                  <p className="subheading">
                    {project.start}
                    {project.start && project.end ? " – " : ""}
                    {project.end}
                  </p>
                )}
              </div>
              {project.description && <p>{project.description}</p>}
              {project.bullets && project.bullets.length > 0 && (
                <ul>
                  {project.bullets.map((bullet, bulletIdx) => (
                    <li key={`project-${idx}-bullet-${bulletIdx}`}>{bullet}</li>
                  ))}
                </ul>
              )}
            </div>
          ))}
        </>
      )
    },
  ];

  // Get sections that have data, ordered by sectionOrder
  const orderedSections = sectionOrder
    .map(sectionKey => availableSections.find(section => section.key === sectionKey))
    .filter(section => section && section.hasData);


  const typo = resume.typography || {};
  const baseSize = typo.font_size || 14;
  const s = baseSize / 14;
  const fontFamily = typo.font_family || "'Source Sans Pro', 'Segoe UI', Arial, sans-serif";
  const fontUrl = useFontLoader(typo.font_id);

  const fontFormat = typo.font_format || "truetype";
  const fontFaceRule = fontUrl && typo.font_family
    ? `@font-face { font-family: '${typo.font_family}'; src: url(${fontUrl}) format('${fontFormat}'); }`
    : "";

  return (
    <div className="pdf-template-preview" data-cv-preview="true">
      <style>
        {`
          ${fontFaceRule}
          .pdf-template-preview {
            font-family: ${fontFamily};
            background: #ffffff;
            padding: 18px 24px 24px;
            color: #111;
            line-height: 1.5;
            font-size: ${baseSize}px;
          }
          .pdf-template-preview * {
            box-sizing: border-box;
          }
          .pdf-template-preview h1 {
            font-size: ${Math.round(32 * s)}px;
            letter-spacing: 0.08em;
            text-align: center;
            margin: 0 0 6px;
          }
          .pdf-template-preview .tagline {
            text-align: center;
            font-size: ${Math.round(14 * s)}px;
            font-weight: 600;
            text-transform: uppercase;
            margin: 0 0 4px;
          }
          .pdf-template-preview .contact-line {
            text-align: center;
            font-size: ${Math.round(12 * s)}px;
            color: #374151;
            margin: 0 0 20px;
          }
          .pdf-template-preview h2 {
            font-size: ${Math.round(15 * s)}px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            border-bottom: 2px solid #111;
            margin: 26px 0 12px;
            padding-bottom: 6px;
          }
          .pdf-template-preview h3 {
            font-size: ${Math.round(14 * s)}px;
            font-weight: 700;
            margin: 0;
          }
          .pdf-template-preview .subheading {
            font-size: ${Math.round(12 * s)}px;
            font-weight: 600;
            color: #374151;
            margin: 0;
          }
          .pdf-template-preview p {
            font-size: ${Math.round(14 * s)}px;
          }
          .pdf-template-preview ul {
            margin: 8px 0 12px 18px;
            padding: 0;
          }
          .pdf-template-preview ul li {
            margin-bottom: 4px;
            font-size: ${Math.round(14 * s)}px;
          }
          .pdf-template-preview .role-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 6px;
          }
          .pdf-template-preview .section {
            margin-bottom: 12px;
          }
          .pdf-template-preview .list-simple {
            margin-left: 14px;
          }
          
          @media (max-width: 640px) {
            .pdf-template-preview {
              padding: 12px 16px 16px;
            }
            .pdf-template-preview h1 {
              font-size: ${Math.round(24 * s)}px;
            }
            .pdf-template-preview .tagline {
              font-size: ${Math.round(12 * s)}px;
            }
            .pdf-template-preview .contact-line {
              font-size: ${Math.round(10 * s)}px;
            }
            .pdf-template-preview h2 {
              font-size: ${Math.round(13 * s)}px;
            }
            .pdf-template-preview h3 {
              font-size: ${Math.round(12 * s)}px;
            }
            .pdf-template-preview .subheading {
              font-size: ${Math.round(11 * s)}px;
            }
            .pdf-template-preview p {
              font-size: ${Math.round(12 * s)}px;
            }
            .pdf-template-preview ul li {
              font-size: ${Math.round(12 * s)}px;
            }
          }
        `}
      </style>

      <div className="flex flex-col items-center gap-3 mb-4">
        {contact.profile_picture && (
          <img
            src={contact.profile_picture}
            alt={resume.name || "Profile"}
            className="h-24 w-24 rounded-full object-cover border-2 border-slate-200"
          />
        )}
        <div className="text-center">
          {resume.name && <h1>{resume.name.toUpperCase()}</h1>}
          {resume.tagline && <p className="tagline">{resume.tagline}</p>}
          {contactLine.length > 0 && (
            <p className="contact-line">{contactLine.join(" | ")}</p>
          )}
        </div>
      </div>

      {resume.summary && (
        <>
          <h2>{labels.professionalSummary || "Professional Summary"}</h2>
          <p>{resume.summary}</p>
        </>
      )}

      {/* Render sections dynamically based on section_order */}
      {orderedSections.map((section) => (
        <div key={section.key}>{section.render()}</div>
            ))}
    </div>
  );
};

export default ClassicTemplate;


