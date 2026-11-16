/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

// Loaded on demand by RawSqlEditor, so the explorer never downloads CodeMirror for users who do
// not open the SQL tab. Plain JavaScript typed by codeMirrorModules.d.ts: the TypeScript version
// of the Matomo 5 build cannot parse the CodeMirror declarations.

export { basicSetup, EditorView } from 'codemirror';
export { EditorState } from '@codemirror/state';
export { keymap, placeholder, tooltips } from '@codemirror/view';
export { HighlightStyle, syntaxHighlighting } from '@codemirror/language';
export { MySQL, sql } from '@codemirror/lang-sql';
export { linter, lintGutter } from '@codemirror/lint';
export { tags } from '@lezer/highlight';
