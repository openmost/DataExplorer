/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

// Subset of the CodeMirror API used by the SQL editor, see codeMirrorModules.js

/* eslint-disable max-classes-per-file */

import type { CodeTags, CodeTagStyle } from '../codeTheme';

export type Extension = { extension: Extension } | readonly Extension[];

export interface Text {
  readonly length: number;
  toString(): string;
}

export interface TransactionSpec {
  changes?: { from: number, to?: number, insert?: string };
  selection?: { anchor: number, head?: number };
  scrollIntoView?: boolean;
}

export declare class EditorState {
  readonly doc: Text;

  static create(config?: { doc?: string, extensions?: Extension }): EditorState;

  replaceSelection(text: string): TransactionSpec;
}

export interface ViewUpdate {
  readonly state: EditorState;
  readonly docChanged: boolean;
}

export interface Facet<Input> {
  of(value: Input): Extension;
}

export declare class EditorView {
  constructor(config?: { state?: EditorState, parent?: Element | DocumentFragment });

  readonly state: EditorState;

  readonly dom: HTMLElement;

  dispatch(spec: TransactionSpec): void;

  focus(): void;

  destroy(): void;

  static theme(spec: Record<string, Record<string, string>>): Extension;

  static readonly lineWrapping: Extension;

  static readonly updateListener: Facet<(update: ViewUpdate) => void>;

  static readonly editorAttributes: Facet<Record<string, string>>;

  static readonly contentAttributes: Facet<Record<string, string>>;
}

export declare const basicSetup: Extension;

export interface KeyBinding {
  key: string;
  run: (view: EditorView) => boolean;
}

export declare const keymap: Facet<readonly KeyBinding[]>;

export declare function placeholder(text: string): Extension;

export declare function tooltips(config?: { parent?: HTMLElement }): Extension;

export interface SQLDialect {
  readonly extension: Extension;
}

export declare const MySQL: SQLDialect;

export declare function sql(config?: {
  dialect?: SQLDialect,
  schema?: Record<string, readonly string[]>,
  upperCaseKeywords?: boolean,
}): Extension;

export interface Diagnostic {
  from: number;
  to: number;
  severity: 'hint' | 'info' | 'warning' | 'error';
  message: string;
}

export declare function linter(
  source: (view: EditorView) => readonly Diagnostic[] | Promise<readonly Diagnostic[]>,
  config?: { delay?: number },
): Extension;

export declare function lintGutter(): Extension;

export interface Tag {
  readonly id: number;
}

export interface Highlighter {
  readonly module: unknown;
}

export declare const HighlightStyle: {
  define(specs: CodeTagStyle<Tag>[]): Highlighter;
};

export declare function syntaxHighlighting(highlighter: Highlighter): Extension;

export declare const tags: CodeTags<Tag>;
