
import axios from 'axios';

window.axios = axios;

axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const csrfToken = document.head.querySelector('meta[name="csrf-token"]');

if (csrfToken) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.content;
}
