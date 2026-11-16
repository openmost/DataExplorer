/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

export interface NamedItem {
  id: string;
  name: string;
  scope: string;
}

/**
 * Display name of each catalogue item: plugins may give two dimensions the same name (the visit
 * "Actions" count and the action "Actions" type), those get their scope, and their id when the
 * scope does not tell them apart either.
 */
export function distinctNames(
  items: NamedItem[],
  scopeLabel: (scope: string) => string,
): Record<string, string> {
  const byName: Record<string, NamedItem[]> = {};
  items.forEach((item) => {
    (byName[item.name] = byName[item.name] || []).push(item);
  });

  const names: Record<string, string> = {};
  Object.values(byName).forEach((group) => {
    group.forEach((item) => {
      if (group.length === 1) {
        names[item.id] = item.name;
        return;
      }
      const sameScope = group.filter((other) => other.scope === item.scope).length > 1;
      const qualifier = sameScope
        ? `${scopeLabel(item.scope)}, ${item.id}`
        : scopeLabel(item.scope);
      names[item.id] = `${item.name} (${qualifier})`;
    });
  });
  return names;
}
