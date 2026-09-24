const normalizeValue = (value) =>
  (value || "")
    .toString()
    .trim()
    .toLowerCase();

const EXECUTIVE_KEYWORDS = ["executive", "sales", "split", "johnathon", "watson"];
const MODERN_PROFESSIONAL_KEYWORDS = ["modern", "professional", "freelance", "developer"];

export const deriveTemplateLayout = (template) => {
  const identifier = normalizeValue(template?.slug || template?.name);
  if (!identifier) {
    return "classic";
  }

  if (EXECUTIVE_KEYWORDS.some((keyword) => identifier.includes(keyword))) {
    return "executive-split";
  }

  if (MODERN_PROFESSIONAL_KEYWORDS.some((keyword) => identifier.includes(keyword))) {
    return "modern-professional";
  }

  return "classic";
};

export const TEMPLATE_LAYOUTS = {
  CLASSIC: "classic",
  EXECUTIVE_SPLIT: "executive-split",
  MODERN_PROFESSIONAL: "modern-professional",
};


