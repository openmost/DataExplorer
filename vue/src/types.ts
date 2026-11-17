/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

export type Scope = 'visit' | 'action' | 'conversion';

export interface DimensionItem {
  id: string;
  name: string;
  category: string;
  scope: Scope;
  segment: string;
  type: string;
}

export interface MetricItem {
  id: string;
  name: string;
  category: string;
  scope: Scope;
  aggregation: string;
  isNumeric: boolean;
  allowedScopes: Scope[];
  additiveScopes: Scope[];
}

export interface CatalogGroup<T> {
  category: string;
  items: T[];
}

export interface SegmentItem {
  definition: string;
  name: string;
}

export interface CatalogLimits {
  maxDimensions: number;
  maxMetrics: number;
  maxColumns: number;
}

export interface Catalog {
  dimensions: CatalogGroup<DimensionItem>[];
  metrics: CatalogGroup<MetricItem>[];
  segments: SegmentItem[];
  limits: CatalogLimits;
}

export type VariableKind = 'dimension' | 'metric' | 'segment';

export interface PickerItem {
  id: string;
  name: string;
}

export interface PickerGroup {
  category: string;
  items: PickerItem[];
}

export type Visualization = 'table' | 'line' | 'bar' | 'pie' | 'kpicard';

export type ReportType = 'table' | 'evolution' | 'kpicard';

export type CellType = 'plain' | 'bar' | 'heatmap';

export type FilterOperator = 'equals' | 'not_equals' | 'contains' | 'starts_with' | 'regex' | 'gt' | 'lt';

export interface Filter {
  dimension: string;
  operator: FilterOperator;
  value: string;
}

export interface ExplorationState {
  technique: 'freeform';
  variables: {
    dimensions: string[];
    metrics: string[];
    segments: string[];
  };
  rows: string[];
  column: string|null;
  values: string[];
  filters: Filter[];
  visualization: Visualization;
  // definition of the applied segment, '' for none (one segment in v1)
  segment: string;
  // how the table draws its metric cells
  cellType: CellType;
}

export interface VisualDefinition {
  technique: 'freeform';
  report_type: ReportType;
  rows: string[];
  column: string|null;
  metrics: string[];
  filters: Filter[];
  segment: string;
}

export interface SavedReport {
  idreport: number;
  name: string;
  type: 'visual' | 'sql';
  report_type: ReportType;
  definition: Partial<VisualDefinition> & { sql?: string };
  placement: { all: boolean, sites: number[] };
  category: string;
  subcategory: string;
  order: number;
  editable: boolean;
}

export interface Subcategory {
  id: string;
  name: string;
}

export interface ReportCategory {
  id: string;
  name: string;
  subcategories: Subcategory[];
}

export interface WritableSite {
  id: number;
  name: string;
}

export interface SqlStatus {
  state: string;
  message: string;
}

export interface SqlProblem {
  offset: number|null;
  offsetUtf16: number|null;
  length: number;
  lengthUtf16: number;
  message: string;
}

export interface SqlValidation {
  valid: boolean;
  errors: SqlProblem[];
  warnings: SqlProblem[];
}

export type ApiRow = Record<string, unknown> & {
  label?: string;
  subtable?: ApiRow[];
};

export interface DragPayload {
  kind: VariableKind;
  id: string;
}

export interface ZoneItem {
  id: string;
  name: string;
  // why the item cannot be used with the rest of the exploration, '' when it can
  reason: string;
}

export interface VariableView extends ZoneItem {
  used: boolean;
}

export interface FilterView {
  filter: Filter;
  name: string;
  reason: string;
}
