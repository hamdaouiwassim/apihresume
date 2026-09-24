import axios from "axios";
import { toast } from "sonner";
import config from "../config";
import { createPrepareSpaRequest } from "./sanctumPrep";

const axiosInstance = axios.create({
  baseURL: config.API_URL,
  withCredentials: true,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
    "X-Requested-With": "XMLHttpRequest",
  },
});

// Same-origin Blade page: reuse the CSRF token Laravel rendered into <meta name="csrf-token">.
const pageCsrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
if (pageCsrfToken) {
  axiosInstance.defaults.headers.common["X-CSRF-TOKEN"] = pageCsrfToken;
}

export const prepareSpaRequest = createPrepareSpaRequest(axiosInstance);

axiosInstance.interceptors.request.use(
  async (req) => {
    const method = (req.method || "get").toLowerCase();
    const reqUrl = (req.url || "").toLowerCase();

    // Session can rotate after social auth/login flows; always refresh CSRF before logout.
    // This avoids stale token mismatches that surface as 419 in production.
    if (method === "post" && (reqUrl.endsWith("/logout") || reqUrl === "logout")) {
      await prepareSpaRequest(true);
    }

    if (["post", "put", "patch", "delete"].includes(method)) {
      const skipCsrf =
        req.url?.includes("csrf-token") || req.headers?.["X-Skip-Csrf-Prep"];
      if (!skipCsrf && !req.headers["X-CSRF-TOKEN"]) {
        await prepareSpaRequest(false);
      }

      // Ensure the current request always carries the latest CSRF token header.
      const csrfHeader = axiosInstance.defaults.headers.common["X-CSRF-TOKEN"];
      if (csrfHeader && !req.headers["X-CSRF-TOKEN"]) {
        req.headers["X-CSRF-TOKEN"] = csrfHeader;
      }
    }

    const token = localStorage.getItem("token");
    if (token) {
      req.headers.Authorization = `Bearer ${token}`;
    }
    if (req.data instanceof FormData) {
      delete req.headers["Content-Type"];
    }
    return req;
  },
  (error) => Promise.reject(error)
);

const formatValidationErrors = (errors) => {
  if (!errors || typeof errors !== "object") {
    return [];
  }

  const errorMessages = [];
  Object.keys(errors).forEach((field) => {
    const fieldErrors = Array.isArray(errors[field])
      ? errors[field]
      : [errors[field]];
    fieldErrors.forEach((errorMsg) => {
      if (errorMsg) {
        const fieldLabel = field
          .replace(/_/g, " ")
          .replace(/\b\w/g, (l) => l.toUpperCase());
        errorMessages.push(`${fieldLabel}: ${errorMsg}`);
      }
    });
  });

  return errorMessages;
};

axiosInstance.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response) {
      const { status, data } = error.response;
      const reqUrl = error.config?.url || "";

      if (status === 419 && !error.config?._csrfRetry) {
        delete axiosInstance.defaults.headers.common["X-CSRF-TOKEN"];
        delete axiosInstance.defaults.headers.common["X-XSRF-TOKEN"];
        try {
          await prepareSpaRequest(true);
          const retryConfig = {
            ...error.config,
            _csrfRetry: true,
          };
          return axiosInstance.request(retryConfig);
        } catch {
          return Promise.reject(error);
        }
      }

      if (status === 401) {
        const isSilentAuthCheck =
          reqUrl.includes("/me") || reqUrl.endsWith("me");
        const currentToken = localStorage.getItem("token");
        const requestAuthHeader =
          error.config?.headers?.Authorization ||
          error.config?.headers?.authorization;
        const requestToken = requestAuthHeader?.startsWith("Bearer ")
          ? requestAuthHeader.slice(7)
          : null;

        // Do not clear a token that was refreshed/set after this request started.
        if (!requestToken || currentToken === requestToken) {
          localStorage.removeItem("token");
        }
        if (!isSilentAuthCheck) {
          window.location.href = "/login";
        }
        return Promise.reject(error);
      }

      if (status === 422) {
        const errorData = data || {};
        const errors = errorData.errors || {};
        const message = errorData.message || "Validation failed";

        const formattedErrors = formatValidationErrors(errors);

        if (formattedErrors.length > 0) {
          if (formattedErrors.length === 1) {
            toast.error(formattedErrors[0], {
              duration: 5000,
            });
          } else {
            const errorList = formattedErrors
              .map((err, idx) => `${idx + 1}. ${err}`)
              .join("\n");
            toast.error(message, {
              description: errorList,
              duration: 6000,
            });
          }
        } else {
          toast.error(
            message || "Validation failed. Please check your input.",
            {
              duration: 5000,
            }
          );
        }
      }
    }

    return Promise.reject(error);
  }
);

export default axiosInstance;
