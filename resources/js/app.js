import "./bootstrap";
import router from "./router.js";
import { createApp } from "vue";

import App from "./App.vue";
import '../../public/css/student-style.css';

createApp(App).use(router).mount("#app");