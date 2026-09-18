import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import './index.css';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);

app.mount('#app');

// Register Service Worker for PWA
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    const swUrl = window.location.pathname.startsWith('/simpelkesrsig') ? '/simpelkesrsig/sw.js' : '/sw.js';
    navigator.serviceWorker.register(swUrl).then(reg => {
      console.log('SIMPELKES PWA ServiceWorker registered with scope:', reg.scope);
    }).catch(err => {
      console.warn('SIMPELKES PWA ServiceWorker registration failed:', err);
    });
  });
}
