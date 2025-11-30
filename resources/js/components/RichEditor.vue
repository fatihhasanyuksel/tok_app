<template>
  <div class="rich-wrap">
    <div class="toolbar">
      <!-- Basic formatting -->
      <button class="btn" @click="cmd('toggleBold')" :class="{ on: isActive('bold') }"><b>B</b></button>
      <button class="btn" @click="cmd('toggleItalic')" :class="{ on: isActive('italic') }"><i>I</i></button>
      <button class="btn" @click="cmd('toggleUnderline')" :class="{ on: isActive('underline') }"><u>U</u></button>

      <!-- Lists -->
      <button class="btn" @click="cmd('toggleBulletList')" :class="{ on: isActive('bulletList') }">•</button>
      <button class="btn" @click="cmd('toggleOrderedList')" :class="{ on: isActive('orderedList') }">1.</button>

      <!-- Image -->
      <button class="btn" @click="insertImage">🖼️</button>

      <!-- YouTube: shown only when enableYoutube = true -->
      <button
        v-if="enableYoutube"
        class="btn"
        @click="insertYoutube"
      >
        ▶︎ YT
      </button>

      <!-- Undo / Redo -->
      <button class="btn" @click="cmd('undo')">⟲</button>
      <button class="btn" @click="cmd('redo')">⟳</button>

      <!-- Status (mainly meaningful for workspace API mode) -->
      <span class="status" v-if="loading">Loading…</span>
      <span class="status ok" v-else-if="loaded && !saving && !error">Loaded</span>
      <span class="status err" v-if="error">{{ error }}</span>
    </div>

    <div ref="editorEl" class="editor" />
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';

import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import Placeholder from '@tiptap/extension-placeholder';
import Youtube from '@tiptap/extension-youtube';

/**
 * Dual-mode editor:
 *
 * 1) ToK App workspaces (Essay / Exhibition)
 *    - Props: ownerType + ownerId
 *    - Loads initial content from /api/tok/docs/{ownerType}/{ownerId}
 *
 * 2) Learning Space (LS)
 *    - Props: modelValue + update:modelValue (v-model)
 *    - No API load, just uses existing textarea value
 */
const props = defineProps({
  // Workspace-only props (Exhibition / Essay). Optional for LS.
  ownerType:   { type: String, default: null },          // 'exhibition' | 'essay'
  ownerId:     { type: [String, Number], default: null },

  // LS mode: v-model
  modelValue:  { type: String, default: '' },

  placeholder: { type: String, default: 'Start typing…' },

  // Optional YouTube toggle
  // - false  → no YT button
  // - true   → show YT button
  enableYoutube: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const editorEl = ref(null);
let editor = null;

// simple UI state (mainly used in workspace mode)
const loading = ref(false);
const loaded  = ref(false);
const saving  = ref(false);
const error   = ref('');

// ---- helper: run a command by name
function cmd(name) {
  if (!editor) return;
  const chain = editor.chain().focus();
  if (name === 'toggleBold') chain.toggleBold().run();
  else if (name === 'toggleItalic') chain.toggleItalic().run();
  else if (name === 'toggleUnderline') chain.toggleUnderline().run();
  else if (name === 'toggleBulletList') chain.toggleBulletList().run();
  else if (name === 'toggleOrderedList') chain.toggleOrderedList().run();
  else if (name === 'undo') chain.undo().run();
  else if (name === 'redo') chain.redo().run();
}

function isActive(markOrNode) {
  return editor ? editor.isActive(markOrNode) : false;
}

// ---- image insert (simple URL prompt)
async function insertImage() {
  if (!editor) return;
  const url = window.prompt('Image URL (upload coming next)…');
  if (!url) return;
  editor.chain().focus().setImage({ src: url }).run();
}

// ---- YouTube insert (used only when enableYoutube = true)
async function insertYoutube() {
  if (!editor) return;
  const url = window.prompt('Paste YouTube URL:');
  if (!url) return;

  editor
    .chain()
    .focus()
    .setYoutubeVideo({
      src: url,
      width: 640,
      height: 360,
    })
    .run();
}

// ---- Workspace: load initial content from /api/tok/docs/{type}/{id}
async function loadInitialFromApi() {
  if (!props.ownerType || !props.ownerId) {
    // LS mode: no remote load
    return;
  }

  loading.value = true;
  error.value   = '';

  try {
    const res = await fetch(`/api/tok/docs/${props.ownerType}/${props.ownerId}`, {
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin',
    });

    if (!res.ok) {
      throw new Error(`HTTP ${res.status}`);
    }

    const json = await res.json();   // expects { body_html: string, ... }
    const html = json?.body_html || '<p></p>';

    // Set content without triggering extra updates
    editor.commands.setContent(html, false);
    loaded.value = true;

    // Also push into v-model for consistency (harmless if no listener)
    emit('update:modelValue', html);
  } catch (e) {
    console.error(e);
    error.value = 'Load failed';
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  // Base content:
  //  - Workspace mode: start empty, load from API
  //  - LS mode: start from modelValue (textarea value mirrored through app.js)
  const startingHtml =
    props.ownerType && props.ownerId
      ? '<p></p>'
      : (props.modelValue || '<p></p>');

  editor = new Editor({
    element: editorEl.value,
    extensions: [
      StarterKit,
      Underline,
      Link.configure({ openOnClick: false }),
      Image,
      Placeholder.configure({ placeholder: props.placeholder }),
      Youtube.configure({
        controls: true,
        nocookie: true,
      }),
    ],
    content: startingHtml,
  });

  // LS mode: keep v-model in sync on every change
  editor.on('update', () => {
    const html = editor.getHTML();
    emit('update:modelValue', html);
  });

  // Workspace mode: pull initial HTML from API
  if (props.ownerType && props.ownerId) {
    loadInitialFromApi();
  }
});

onBeforeUnmount(() => {
  if (editor) {
    editor.destroy();
    editor = null;
  }
});
</script>

<style scoped>
.rich-wrap {
  border: 1px solid #e5e5e5;
  border-radius: 12px;
  padding: 12px;
  background: #fff;
}
.toolbar {
  display: flex;
  gap: 6px;
  align-items: center;
  margin-bottom: 8px;
  flex-wrap: wrap;
}
.btn {
  padding: 6px 8px;
  border: 1px solid #ddd;
  border-radius: 8px;
  background: #fff;
  cursor: pointer;
  font-size: 0.85rem;
}
.btn.on {
  background: #f2f6ff;
  border-color: #b3c6ff;
}
.editor {
  min-height: 300px;
  padding: 10px;
  border: 1px solid #eee;
  border-radius: 10px;
}
.status {
  margin-left: 8px;
  font-size: 12px;
  color: #666;
}
.status.ok {
  color: #208a3c;
}
.status.err {
  color: #b00020;
}
</style>