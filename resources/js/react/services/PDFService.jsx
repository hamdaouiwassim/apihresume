import axiosInstance from "../api/axiosInstance";

export const generatePDF = async ({
  resume,
  filename = "resume.pdf",
  locale = "en",
}) => {
  return axiosInstance.post(
    "/generate-pdf",
    { resume, filename, locale },
    {
      responseType: "blob",
    }
  );
};
