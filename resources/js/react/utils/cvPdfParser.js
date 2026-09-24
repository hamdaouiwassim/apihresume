import * as pdfjsLib from 'pdfjs-dist'
// Bundled locally (same version as pdfjs-dist) instead of loading the worker from unpkg.
import pdfWorkerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url'

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfWorkerUrl

export async function extractTextFromPdf(file) {
  const arrayBuffer = await file.arrayBuffer()
  const pdf = await pdfjsLib.getDocument({ data: arrayBuffer.slice(0) }).promise
  let fullText = ''

  for (let i = 1; i <= pdf.numPages; i++) {
    const page = await pdf.getPage(i)
    const content = await page.getTextContent()
    const pageText = content.items.map(item => item.str).join(' ')
    fullText += pageText + '\n'
  }

  return fullText
}

export async function extractPagesFromPdf(file) {
  const arrayBuffer = await file.arrayBuffer()
  const pdf = await pdfjsLib.getDocument({ data: arrayBuffer.slice(0) }).promise
  const pages = []

  for (let i = 1; i <= pdf.numPages; i++) {
    const page = await pdf.getPage(i)
    const content = await page.getTextContent()
    const pageText = content.items.map(item => item.str).join(' ')
    pages.push(pageText)
  }

  return pages
}

export function mergeParsedPages(results) {
  const merged = {
    full_name: '',
    email: '',
    phone: '',
    location: '',
    job_title: '',
    linkedin: '',
    github: '',
    website: '',
    professional_summary: '',
    experiences: [],
    educations: [],
    skills: [],
    languages: [],
    certificates: [],
    projects: [],
    hobbies: [],
  }

  for (const page of results) {
    if (!page) continue
    if (page.full_name && !merged.full_name) merged.full_name = page.full_name
    if (page.email && !merged.email) merged.email = page.email
    if (page.phone && !merged.phone) merged.phone = page.phone
    if (page.location && !merged.location) merged.location = page.location
    if (page.job_title && !merged.job_title) merged.job_title = page.job_title
    if (page.linkedin && !merged.linkedin) merged.linkedin = page.linkedin
    if (page.github && !merged.github) merged.github = page.github
    if (page.website && !merged.website) merged.website = page.website
    if (page.professional_summary && !merged.professional_summary) merged.professional_summary = page.professional_summary
    if (Array.isArray(page.experiences)) merged.experiences.push(...page.experiences)
    if (Array.isArray(page.educations)) merged.educations.push(...page.educations)
    if (Array.isArray(page.skills)) merged.skills.push(...page.skills)
    if (Array.isArray(page.languages)) merged.languages.push(...page.languages)
    if (Array.isArray(page.certificates)) merged.certificates.push(...page.certificates)
    if (Array.isArray(page.projects)) merged.projects.push(...page.projects)
    if (Array.isArray(page.hobbies)) merged.hobbies.push(...page.hobbies)
  }

  return merged
}
