/**
 * Island wrapper around components/admin/AdminResumePreviewModal.jsx for Blade admin pages.
 * Open it from Alpine with:  window.dispatchEvent(new CustomEvent('admin-resume-preview', { detail: { id } }))
 */
import { useEffect, useState } from "react";
import AdminResumePreviewModal from "../components/admin/AdminResumePreviewModal";
import axiosInstance from "../api/axiosInstance";
import { toast } from "sonner";

const formatDate = (value) => {
  if (!value) return "N/A";
  try {
    return new Date(value).toLocaleDateString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  } catch {
    return "N/A";
  }
};

export default function AdminResumePreviewIsland() {
  const [resume, setResume] = useState(null);

  useEffect(() => {
    const open = async (event) => {
      const id = event.detail?.id;
      if (!id) return;
      setResume({ id, _loading: true });
      try {
        const response = await axiosInstance.get(`/admin/resumes/${id}`);
        if (response.data.status) {
          setResume(response.data.data);
        } else {
          toast.error("Failed to load resume");
          setResume(null);
        }
      } catch {
        toast.error("Failed to load resume");
        setResume(null);
      }
    };
    const close = () => setResume(null);
    window.addEventListener("admin-resume-preview", open);
    window.addEventListener("admin-resume-preview-close", close);
    return () => {
      window.removeEventListener("admin-resume-preview", open);
      window.removeEventListener("admin-resume-preview-close", close);
    };
  }, []);

  return <AdminResumePreviewModal resume={resume} onClose={() => setResume(null)} formatDate={formatDate} />;
}
