// resources/js/app.js
import { createApp, h, ref, watch } from 'vue';
import axios from 'axios';
import RichEditor from './components/RichEditor.vue';

// ---------------------------------------------
// Axios defaults (Laravel session + CSRF)
// ---------------------------------------------
axios.defaults.withCredentials = true;

const tokenTag = document.querySelector('meta[name="csrf-token"]');
if (tokenTag) {
  axios.defaults.headers.common['X-CSRF-TOKEN'] = tokenTag.getAttribute('content');
}

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// ---------------------------------------------
// 1) ToK App workspace rich editors (existing)
//    Mounts on elements with .js-rich-editor
//    ➜ NO YouTube here (enableYoutube left as default: false)
// ---------------------------------------------
function mountTokWorkspaceEditors() {
  const nodes = document.querySelectorAll('.js-rich-editor');

  nodes.forEach((el) => {
    const ownerType = el.getAttribute('data-owner-type');        // "exhibition" | "essay"
    const ownerId   = Number(el.getAttribute('data-owner-id'));  // numeric id
    const placeholder = el.getAttribute('data-placeholder') || 'Start writing…';

    const app = createApp(RichEditor, {
      ownerType,
      ownerId,
      placeholder,
      // enableYoutube NOT set → stays false in workspaces
    });

    app.provide('axios', axios);
    app.mount(el);
  });
}

// ---------------------------------------------
// 2) ToK Learning Space – TEACHER lesson editors
//    Mount beside the textarea, keep textarea as form field
//    ➜ Here we turn ON YouTube (enableYoutube: true)
// ---------------------------------------------
function mountLsLessonEditors() {
  const containers = document.querySelectorAll('[data-tok-ls-rich-editor]');

  containers.forEach((container) => {
    const textarea = container.querySelector('textarea[data-tok-ls-input]');
    if (!textarea) return;

    const initialContent = textarea.value || '';

    // Hide textarea but keep it in DOM for form POST
    textarea.style.display = 'none';

    // Vue/TipTap mounts into a separate div
    const mountPoint = document.createElement('div');
    container.appendChild(mountPoint);

    const app = createApp({
      setup() {
        const content = ref(initialContent);

        // Keep textarea in sync so POST still sends HTML
        watch(content, (newVal) => {
          textarea.value = newVal;
        });

        // Render RichEditor with v-model (modelValue + update)
        // and YouTube enabled ONLY for LS teacher lesson editors
        return () =>
          h(RichEditor, {
            modelValue: content.value,
            'onUpdate:modelValue': (val) => {
              content.value = val;
            },
            enableYoutube: true, // 🔵 LS Create/Edit Lesson gets YT button
          });
      },
    });

    app.provide('axios', axios);
    app.mount(mountPoint);
  });
}

// ---------------------------------------------
// 3) ToK Learning Space – STUDENT response editors
//    Mount beside the textarea, keep textarea as form field
//    ➜ No YouTube here (leave enableYoutube default: false)
// ---------------------------------------------
function mountLsStudentResponseEditors() {
  const containers = document.querySelectorAll('[data-tok-ls-response-editor]');

  containers.forEach((container) => {
    const textarea = container.querySelector('textarea[data-tok-ls-response-input]');
    if (!textarea) return;

    const initialContent = textarea.value || '';

    // Hide textarea but keep it for form + autosave
    textarea.style.display = 'none';

    const mountPoint = document.createElement('div');
    container.appendChild(mountPoint);

    const app = createApp({
      setup() {
        const content = ref(initialContent);

        // Sync TipTap → textarea so:
        //  - normal POST sends latest HTML
        //  - existing autosave JS (listening on 'input') keeps working
        watch(content, (newVal) => {
          textarea.value = newVal;

          // 🔴 IMPORTANT: tell the old autosave script "input happened"
          const evt = new Event('input', { bubbles: true });
          textarea.dispatchEvent(evt);
        });

        return () =>
          h(RichEditor, {
            modelValue: content.value,
            'onUpdate:modelValue': (val) => {
              content.value = val;
            },
            // enableYoutube not passed → false for students
          });
      },
    });

    app.provide('axios', axios);
    app.mount(mountPoint);
  });
}

// ---------------------------------------------
// 4) Boot everything on DOM ready
// ---------------------------------------------
function bootEditors() {
  // Existing ToK App workspaces
  mountTokWorkspaceEditors();

  // LS teacher lesson content editors (with YouTube)
  mountLsLessonEditors();

  // LS student response editors (no YouTube)
  mountLsStudentResponseEditors();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bootEditors);
} else {
  bootEditors();
}