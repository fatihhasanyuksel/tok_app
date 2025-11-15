import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'

export function mountEditor() {
  const el = document.getElementById('wk3-editor')
  if (!el) return console.error('[wk3] Editor mount element not found.')

  // Create new TipTap editor instance
  const editor = new Editor({
    element: el,
    extensions: [StarterKit],
    content: '<p>Start writing your ToK reflection here...</p>',
  })

  console.log('[wk3] TipTap v3 editor mounted', editor)
  return editor
}