import "./bootstrap";
import router from "./router.js";
import { createApp } from "vue";
import { initWebPushNotifications } from "./utils/webPush.js";

import App from "./App.vue";
import '../../public/css/student-style.css';

const app = createApp(App).use(router);

window.addEventListener('load', () => {
    const hasStudentToken = !!localStorage.getItem('studentToken');
    if (hasStudentToken) {
        initWebPushNotifications();
    }
});

app.mount("#app");