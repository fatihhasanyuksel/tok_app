// resources/js/app.js
import { createApp } from 'vue';
import axios from 'axios';

// --- Axios: Laravel-friendly defaults (session auth + CSRF) ---
axios.defaults.withCredentials = true;
const tokenTag = document.querySelector('meta[name="csrf-token"]');
if (tokenTag) {
  axios.defaults.headers.common['X-CSRF-TOKEN'] = tokenTag.getAttribute('content');
}
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// --- Lazy import of the editor component (we'll create it next) ---
import RichEditor from './components/RichEditor.vue';

// Helper: mount a Vue app for each editor placeholder on the page
function mountRichEditors() {
  const nodes = document.querySelectorAll('.js-rich-editor');
  nodes.forEach((el) => {
    const ownerType = el.getAttribute('data-owner-type'); // "exhibition" | "essay"
    const ownerId = Number(el.getAttribute('data-owner-id')); // numeric id
    const placeholder = el.getAttribute('data-placeholder') || 'Start writing…';

    const app = createApp(RichEditor, {
      ownerType,
      ownerId,
      placeholder,
    });

    // Provide axios to the component (optional, convenient)
    app.provide('axios', axios);

    app.mount(el);
  });
}

// Mount when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', mountRichEditors);
} else {
  mountRichEditors();
}