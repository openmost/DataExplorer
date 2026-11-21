/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

// visit, action, conversion, or the name of another log table of an active plugin (log_media)
export type Scope = string;

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
  additiveScopes: Scope[];
  // scopes whose dimensions give a row of this metric several rows of a report
  repeatedBy?: Scope[];
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
  // why the item does not fit the current exploration, empty when it does
  reason?: string;
}

export interface PickerGroup {
  category: string;
  items: PickerItem[];
}

export type Visualization = 'table' | 'line' | 'bar' | 'pie' | 'treemap' | 'kpicard';

// the charts of a table report
export type Chart = 'bar' | 'pie' | 'treemap';

export type ReportType = 'table' | 'evolution' | 'kpicard';

export type FilterOperator = 'equals' | 'not_equals' | 'contains' | 'not_contains'
  | 'starts_with' | 'not_starts_with' | 'ends_with' | 'not_ends_with'
  | 'regex' | 'gt' | 'gte' | 'lt' | 'lte';

// definitions and links saved before filter groups: every filter applies (all) or any of them (any)
export type FilterMatch = 'all' | 'any';

// a table of several row dimensions: one row per combination, or parents that open their children
export type TableView = 'flat' | 'hierarchical';

// filters of the same group are ORed, the groups are ANDed
export interface Filter {
  dimension: string;
  operator: FilterOperator;
  value: string;
  group: number;
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
  tableView: TableView;
  visualization: Visualization;
  // definition of the applied segment, '' for none (one segment in v1)
  segment: string;
}

export interface VisualDefinition {
  technique: 'freeform';
  report_type: ReportType;
  rows: string[];
  column: string|null;
  metrics: string[];
  filters: Filter[];
  filter_match?: FilterMatch;
  table_view?: TableView;
  segment: string;
  // the view a saved table report opens with
  chart?: '' | Chart;
}

export type FunnelFollow = 'indirect' | 'direct';

export type FunnelTimeUnit = 'seconds' | 'minutes' | 'hours' | 'days';

// conditions of the same group are ORed, the groups are ANDed, all tested on one action or conversion
export interface FunnelStep {
  name: string;
  conditions: Filter[];
  optional: boolean;
  // how the step follows the previous one
  follow: FunnelFollow;
  within: { value: number, unit: FunnelTimeUnit }|null;
}

export interface FunnelState {
  steps: FunnelStep[];
  open: boolean;
  breakdown: string|null;
  breakdownRows: number;
}

export interface FunnelDefinition {
  type: 'funnel';
  technique: 'funnel';
  report_type: 'funnel';
  steps: FunnelStep[];
  open: boolean;
  breakdown: string|null;
  breakdown_rows: number;
  segment: string;
}

export interface FunnelValues {
  label: string;
  nb_started: number;
  nb_entered: number;
  nb_abandoned: number;
  abandon_rate: number;
  step_rate: number;
  overall_rate: number;
  avg_time_to_step: number;
}

export interface FunnelStepResult extends FunnelValues {
  breakdown: FunnelValues[];
}

export interface SavedReport {
  idreport: number;
  name: string;
  type: 'visual' | 'sql' | 'funnel';
  report_type: ReportType | 'funnel';
  definition: Partial<VisualDefinition> & Partial<Omit<FunnelDefinition, 'type' | 'technique' | 'report_type'>>
    & { sql?: string, description?: string };
  placement: { all: boolean, sites: number[] };
  category: string;
  subcategory: string;
  // "Category > Page" in the reporting menu
  place?: string;
  order: number;
  editable: boolean;
  created_by?: string;
  created_date?: string;
  updated_date?: string;
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

export interface DragPayload {
  kind: VariableKind;
  id: string;
}

export interface ZoneItem {
  id: string;
  name: string;
  // why the item cannot be used with the rest of the exploration, '' when it can
  reason: string;
  // how a metric counts its rows when a dimension of the exploration repeats them
  hint?: string;
  // struck through: an item of an imported custom report the explorer cannot read, never queried
  missing?: boolean;
}

export interface VariableView extends ZoneItem {
  used: boolean;
}

export interface FilterView {
  filter: Filter;
  // position in ExplorationState.filters
  index: number;
  name: string;
  reason: string;
}

// a titled group of the report picker, its keys are what the picker emits
export interface ReportPickerGroup {
  label: string;
  // place: where the report sits in the reporting menu, "Category > Page"
  items: { key: string, name: string, place?: string }[];
}

// a dimension or metric of an imported custom report the explorer cannot read
export interface UnsupportedEntry {
  id: string;
  name: string;
  kind: 'dimension' | 'metric';
  reason: string;
}

// a custom report opened in the explorer: saving it again keeps its place, sites and children
export interface ImportedCustomReport {
  idCustomReport: number;
  // 0 for a report of all websites
  idSite: number;
  name: string;
  description: string;
  category: string;
  // '' when the report has its own page
  subcategory: string;
  multipleIdSites: number[];
  childReportIds: number[];
  editable: boolean;
}

// what the save dialog starts from when no saved report is open
export interface SaveSuggestion {
  name: string;
  description: string;
  category: string;
  subcategory: string;
}
