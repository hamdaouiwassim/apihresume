/**
 * Island: live template preview with sample data (the React part of TemplatePreviewPage.jsx).
 * Page chrome (back link, title, 404) is rendered by Blade: resources/views/pages/templates/preview.blade.php
 */
import { useMemo, lazy, Suspense } from "react";
import ResumePreviewSkeleton from "../components/ResumePreviewSkeleton";
import { deriveTemplateLayout } from "../utils/templateStyles";

const ResumeTemplatePreview = lazy(() => import("../components/ResumeTemplatePreview"));

const SAMPLE_RESUME = {
  name: "Alex Morgan",
  tagline: "Senior Product Manager",
  contact: {
    location: "Paris, France",
    email: "alex.morgan@email.com",
    phone: "+33 6 12 34 56 78",
    linkedin: "linkedin.com/in/alexmorgan",
    github: "github.com/alexmorgan",
    website: "alexmorgan.dev",
    profile_picture: "/images/avatars/alex-morgan.svg",
  },
  summary:
    "Product leader with 8+ years of experience delivering B2B SaaS platforms. Strong track record in cross-functional execution, roadmap prioritization, and measurable business impact.",
  experience: [
    {
      title: "Senior Product Manager",
      company: "NovaTech",
      location: "Paris, France",
      start: "Jan 2022",
      end: "Present",
      bullets: [
        "Led platform redesign that increased activation by 27%.",
        "Aligned engineering and design squads across 3 product lines.",
      ],
    },
    {
      title: "Product Manager",
      company: "Bright Labs",
      location: "Lyon, France",
      start: "May 2019",
      end: "Dec 2021",
      bullets: [
        "Shipped self-serve onboarding and reduced churn by 14%.",
        "Introduced product analytics framework used by leadership.",
      ],
    },
  ],
  education: [
    {
      degree: "MSc in Management",
      school: "ESSEC Business School",
      location: "Cergy, France",
      graduated: "2018",
    },
  ],
  skills: [
    "Product strategy",
    "Roadmapping",
    "A/B testing",
    "Stakeholder management",
    "SQL & Analytics",
  ],
  certifications: ["PSPO I", "Google Analytics Certified"],
  languages: ["English (Fluent)", "French (Native)"],
  interests: ["Mentoring", "Trail running", "Photography"],
  hobbies: ["Mentoring", "Trail running", "Photography"],
  projects: [
    {
      name: "Lifecycle Optimization Program",
      description: "Cross-team initiative focused on trial-to-paid conversion.",
      technologies: "Amplitude, HubSpot, Segment",
      start: "2023",
      end: "2024",
      bullets: ["Improved conversion rate by 19% over two quarters."],
    },
  ],
  section_order: [
    "personal",
    "socialMedia",
    "experience",
    "education",
    "skills",
    "projects",
    "languages",
    "hobbies",
    "certifications",
  ],
  typography: {
    font_family: "sans-serif",
    font_size: 14,
  },
};

export default function TemplateSamplePreview({ template }) {
  const templateLayout = useMemo(() => deriveTemplateLayout(template), [template]);
  const previewResume = useMemo(
    () => ({ ...SAMPLE_RESUME, template_id: template?.id || null, template_layout: templateLayout }),
    [template, templateLayout]
  );

  return (
    <Suspense fallback={<ResumePreviewSkeleton />}>
      <ResumeTemplatePreview resume={previewResume} templateKey={templateLayout} />
    </Suspense>
  );
}
