import axiosInstance from "../api/axiosInstance";
import axios from 'axios';
import config from "../config";

/**
 * Subscribe an email to the newsletter
 * @param {string} email - Email address to subscribe
 * @returns {Promise} Axios response
 */
export const subscribe = (email) => {
    return axiosInstance.post('/subscribers/subscribe', {
        email: email.trim().toLowerCase()
    });
};

/**
 * Unsubscribe an email from the newsletter
 * @param {string} email - Email address to unsubscribe
 * @returns {Promise} Axios response
 */
export const unsubscribe = (email) => {
    return axiosInstance.post('/subscribers/unsubscribe', {
        email: email.trim().toLowerCase()
    });
};

