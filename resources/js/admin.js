import './bootstrap'
import { createApp } from 'vue'
import router from './router.js'
import '../css/admin-theme.css'

import AdminApp from './AdminApp.vue'

const app = createApp(AdminApp)
app.use(router)
app.mount('#admin-app')
