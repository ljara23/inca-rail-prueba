import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import { router } from './router'
import { initializeSession } from './session'

await initializeSession()

const app = createApp(App)
app.use(router)
await router.isReady()
app.mount('#app')
