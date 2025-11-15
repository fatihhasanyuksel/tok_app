// resources/js/workspace-v3-boot.ts
import { mountEditor } from './workspace/editor'

let editorInstance: any = null

document.addEventListener('DOMContentLoaded', () => {
  // Mount TipTap into #wk3-editor (in workspace_v3.blade.php)
  editorInstance = mountEditor()

  // (Optional) expose for quick console inspection during dev
  // @ts-ignore
  window.wk3 = { editor: editorInstance }

  // Wire placeholder buttons (no save logic yet)
  const saveBtn = document.getElementById('wk3-btn-save')
  const submitBtn = document.getElementById('wk3-btn-submit')
  const backBtn = document.getElementById('wk3-btn-back')

  saveBtn?.addEventListener('click', () => console.debug('[wk3] Save Draft (stub)'))
  submitBtn?.addEventListener('click', () => console.debug('[wk3] Submit (stub)'))
  backBtn?.addEventListener('click', () => console.debug('[wk3] Back (stub)'))
})