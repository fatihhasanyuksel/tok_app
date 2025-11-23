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

// --- Rich editor component (shared) ---
import RichEditor from './components/RichEditor.vue';

// -------------------------------------------------------------
// 1) ToK App workspace rich editors (existing behavior)
//    Mounts on elements with .js-rich-editor
// -------------------------------------------------------------
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

// -------------------------------------------------------------
// 2) ToK Learning Space rich editors
//    Mounts TipTap on LS forms only, using a v-model wrapper.
//    Look for: data-tok-ls-rich-editor + inner textarea[data-tok-ls-input]
// -------------------------------------------------------------
function mountLsRichEditors() {
  const containers = document.querySelectorAll('[data-tok-ls-rich-editor]');

  containers.forEach((container) => {
    const textarea = container.querySelector('textarea[data-tok-ls-input]');
    if (!textarea) return;

    const initialContent = textarea.value || '';

    const app = createApp({
      components: { RichEditor },
      data() {
        return {
          content: initialContent,
        };
      },
      watch: {
        content(newVal) {
          // Keep hidden textarea in sync for normal form POST
          textarea.value = newVal;
        },
      },
      template: `
        <RichEditor v-model="content" />
      `,
    });

    app.mount(container);
  });
}

// -------------------------------------------------------------
// 3) Boot both systems on DOM ready
// -------------------------------------------------------------
function bootEditors() {
  // Existing ToK App workspaces
  mountRichEditors();

  // New Learning Space lesson editors
  mountLsRichEditors();
}

// Mount when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bootEditors);
} else {
  bootEditors();
}