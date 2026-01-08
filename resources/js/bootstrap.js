import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

// Utiliser le cookie XSRF-TOKEN automatiquement (plus fiable que la meta tag)
// Axios va automatiquement lire le cookie et l'envoyer dans X-XSRF-TOKEN
window.axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
window.axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';

// NE PAS utiliser X-CSRF-TOKEN de la meta tag car elle peut être périmée
// si le CDN cache la page HTML. Le cookie XSRF-TOKEN est toujours frais.
