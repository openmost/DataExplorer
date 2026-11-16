/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

import type { Diagnostic } from './codeMirrorModules';
import type { SqlProblem, SqlValidation } from '../types';

function diagnostic(
  problem: SqlProblem,
  severity: 'error'|'warning',
  docLength: number,
): Diagnostic {
  if (problem.offsetUtf16 === null) {
    return {
      from: 0,
      to: docLength,
      severity,
      message: problem.message,
    };
  }

  // CodeMirror positions are UTF-16 code units, like the *Utf16 values of validateSql
  const from = Math.min(Math.max(0, problem.offsetUtf16), docLength);
  const to = Math.min(from + Math.max(1, problem.lengthUtf16), docLength);

  return {
    from,
    to,
    severity,
    message: problem.message,
  };
}

/**
 * Editor diagnostics of a DataExplorer.validateSql result: guard errors, site scope warnings.
 */
export function toDiagnostics(validation: SqlValidation, docLength: number): Diagnostic[] {
  return [
    ...validation.errors.map((problem) => diagnostic(problem, 'error', docLength)),
    ...validation.warnings.map((problem) => diagnostic(problem, 'warning', docLength)),
  ];
}
