import axios from 'axios';

/**
 * Create an axios instance configured for the app's API
 * @param {Object} opts
 * @param {string} opts.baseUrl - Base URL for API requests
 * @param {string} opts.apiKey - API key to send in X-API-KEY header
 * @returns {import('axios').AxiosInstance}
 */
export function createApi({ baseUrl = '/api', apiKey = '' } = {}) {
  const instance = axios.create({
    baseURL: baseUrl,
    headers: {
      'X-API-KEY': apiKey,
      'Accept': 'application/json'
    },
    withCredentials: false,
  });

  // Optional: request interceptor for debugging
  instance.interceptors.request.use((config) => {
    // You can add more headers or logging here
    return config;
  }, (error) => Promise.reject(error));

  // Optional: response interceptor for centralised error handling
  instance.interceptors.response.use((resp) => resp, (error) => {
    // For now, just forward the error
    return Promise.reject(error);
  });

  return instance;
}

