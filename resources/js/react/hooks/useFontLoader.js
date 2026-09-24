import { useState, useEffect } from "react";
import axiosInstance from "../api/axiosInstance";

/**
 * Fetches a custom font file and returns a blob URL for use in @font-face.
 * Used to load uploaded fonts in the CV preview.
 */
export function useFontLoader(fontId) {
  const [fontUrl, setFontUrl] = useState(null);

  useEffect(() => {
    if (!fontId) {
      setFontUrl(null);
      return;
    }

    let objectUrl = null;

    const load = async () => {
      try {
        const response = await axiosInstance.get(`fonts/${fontId}/file`, {
          responseType: "blob",
        });
        objectUrl = URL.createObjectURL(response.data);
        setFontUrl(objectUrl);
      } catch (err) {
      }
    };

    load();

    return () => {
      if (objectUrl) {
        URL.revokeObjectURL(objectUrl);
      }
    };
  }, [fontId]);

  return fontUrl;
}
