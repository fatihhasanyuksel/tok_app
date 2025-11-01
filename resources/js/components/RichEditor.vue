<template>
  <div class="rich-wrap">
    <div class="toolbar">
      <button class="btn" @click="cmd('toggleBold')"    :class="{ on: isActive('bold') }"><b>B</b></button>
      <button class="btn" @click="cmd('toggleItalic')"  :class="{ on: isActive('italic') }"><i>I</i></button>
      <button class="btn" @click="cmd('toggleUnderline')" :class="{ on: isActive('underline') }"><u>U</u></button>
      <button class="btn" @click="cmd('toggleBulletList')" :class="{ on: isActive('bulletList') }">•</button>
      <button class="btn" @click="cmd('toggleOrderedList')" :class="{ on: isActive('orderedList') }">1.</button>
      <button class="btn" @click="insertImage">🖼️</button>
      <button class="btn" @click="cmd('undo')">⟲</button>
      <button class="btn" @click="cmd('redo')">⟳</button>

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

// Props are passed from app.js using data-attributes on the mount node
const props = defineProps({
  ownerType: { type: String, required: true },   // 'exhibition' | 'essay'
  ownerId:   { type: [String, Number], required: true },
  placeholder: { type: String, default: 'Start typing…' },
});

const editorEl = ref(null);
let editor = null;

// simple UI state
const loading = ref(true);
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

// ---- image insert (will wire upload in next step)
async function insertImage() {
  const url = prompt('Image URL (upload coming next)…');
  if (!url) return;
  editor.chain().focus().setImage({ src: url }).run();
}

// ---- load initial content from /api/tok/docs/{type}/{id}
async function loadInitial() {
  loading.value = true;
  error.value = '';
  try {
    const res = await fetch(`/api/tok/docs/${props.ownerType}/${props.ownerId}`, {
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin',
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const json = await res.json(); // expects { body_html: string, ... }
    const html = json?.body_html || '<p></p>';
    editor.commands.setContent(html, false); // false = don’t emit update
    loaded.value = true;
  } catch (e) {
    console.error(e);
    error.value = 'Load failed';
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  editor = new Editor({
    element: editorEl.value,
    extensions: [
      StarterKit,
      Underline,
      Link.configure({ openOnClick: false }),
      Image,
      Placeholder.configure({ placeholder: props.placeholder }),
    ],
    content: '<p></p>',
  });

  // fetch from backend and set content
  loadInitial();
});

onBeforeUnmount(() => {
  if (editor) {
    editor.destroy();
    editor = null;
  }
});
</script>

<style scoped>
.rich-wrap { border:1px solid #e5e5e5; border-radius:12px; padding:12px; background:#fff; }
.toolbar { display:flex; gap:6px; align-items:center; margin-bottom:8px; }
.btn { padding:6px 8px; border:1px solid #ddd; border-radius:8px; background:#fff; cursor:pointer; }
.btn.on { background:#f2f6ff; border-color:#b3c6ff; }
.editor { min-height:300px; padding:10px; border:1px solid #eee; border-radius:10px; }
.status { margin-left:8px; font-size:12px; color:#666; }
.status.ok { color:#208a3c; }
.status.err { color:#b00020; }
</style>