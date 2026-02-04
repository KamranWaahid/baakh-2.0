import axios from 'axios';

const api = axios.create({
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
    },
    withCredentials: true,
});

export default api;
