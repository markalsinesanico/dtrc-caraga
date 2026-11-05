import axios from 'axios';

window.axios = axios;

axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';