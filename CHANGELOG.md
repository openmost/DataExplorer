## Changelog

### 6.5.1

- Funnel steps keep a condition whose value was typed or picked from the suggestions without clicking its own Apply: the Apply of the steps panel takes it.

### 6.5.1

- Unused translation strings removed.

### 6.5.0

- Segment processing per saved report: turned off, the report is only available for All visits and `core:archive` skips it for every segment, which shortens archiving on instances with many pre-processed segments.
- Usable on tablets and phones: the columns of the explorer stack, the funnel chart and table and the list of explorations scroll sideways instead of widening the page.
- The chart buttons of a raw SQL result appear with the result, not above a loading table.
- The explorer opens on the last 7 days.
- Filters imported from a custom report keep their slashes and other special characters, and filters written back to a custom report are encoded like the segment editor does.

### 6.4.0

- Funnel technique, between Free form and Raw SQL. Steps match any visit, action or conversion dimension, several conditions on the same hit (event name and its custom dimension), and can be optional, directly or indirectly followed, within a time limit. Closed or open funnel, breakdown by a visit or action dimension. A visit makes a new pass each time it meets the first step again. Computed live on any past period, saved funnels are archived like the other reports.
- Filters and funnel conditions suggest the values of their dimension, like the segment editor.
- Charts offer the chosen metrics in their metric picker: several for the bar and line charts, one for the pie and the treemap.
- A treemap draws the first chosen metric instead of visits, and without evolution (a date range) it is shaded from light to dark with its values.
- Event Name and Events join the default variables of a new exploration.
- Data Explorer is listed with the insights plugins of the Marketplace.

### 6.3.0

- Treemap visualization when the Treemap Visualization plugin is active, for explorations, raw SQL results and saved reports. On a day, week, month or year its rectangles are colored by their evolution against the previous period.
- New icons for the treemap and the line chart.

### 6.2.1

- A filter on a dimension stored as a code (country, device type, browser...) also matches the English name, so a report filtered by one user keeps its rows for a user reading Matomo in another language.
- Adding or removing a variable no longer reloads the result, only a change of the report settings does.
- An empty drop zone shows its colored edge dashed too.

### 6.2.0

- Filters combine with AND and OR: filters in one group match any of their conditions, groups all match. New operators: does not contain, does not start with, does not end with, greater than or equal to, less than or equal to.
- *Open a report* picker with a search field, listing the explorations and, when the custom reports plugin is active, the custom reports of the website. An opened custom report keeps its dimensions, metrics, filters and segment, and can be updated in place or saved as an exploration in the same category and subcategory.
- Reports take a description, shown as the report documentation.
- The metric and dimension lists follow the core catalogue: archived and processed metrics of every log table of the active plugins, dimensions shown only when their plugin is active.
- The explorer opens on the last 28 days, today excluded, with Page URL and Device type by Visits and Pageviews. Its preview is always computed live, only saved reports are archived.
- Raw SQL reports can be edited, render as a table, bar chart, pie chart or KPI card, and start from a sample query.
- The visualization and table layout stay in sync between the preview and the report settings.
- A report can no longer be placed on the Explore or Manage pages of the plugin.

### 6.1.0

- Every metric can be read next to every dimension, as in a custom report. Under a dimension of another table a visit, an action or a conversion counts once in each row it has a value of: *Total events* by event name are the events of the visits having that event name, *Conversions* by page URL the conversions of the visits that viewed that page. A hint on the metric says so.
- Action and conversion data combine in one exploration, through their visit.
- New *Events* and *Event value* metrics, the columns of the Events reports, to count the events of each event name, category or action.

### 6.0.1

- A newly saved or edited report shows its data right away when browser archiving is on, instead of staying empty until the next `core:archive` run.

### 6.0.0

First release of Data Explorer for Matomo 6.

- Free-form explorer for dimensions, metrics, segments and filters, with a live preview that follows the same row limits as the saved report.
- Visualizations: the Matomo table, evolution graph, bar and pie charts and sparkline (KPI card), in the explorer as in the saved report. Any report can apply one saved segment as a filter.
- Raw SQL for super users, run with a dedicated read-only database user created from the setup page in *Administration > System > Data Explorer*. Guarded SELECT subset, allowlist of 11 tables, row, size, subquery and time limits, cache.
- Saved reports appear in the Matomo reporting menu, for one website, several websites or all websites, and work as dashboard widgets, in the API, in exports and in scheduled reports (raw SQL reports when every select column has a name).
- Saved visual reports are archived by `core:archive`, the last 6 months of history are rebuilt when what a report computes changes, a rename or a move keeps it.
- Export of the explorer result with the Matomo export of reports, and a *Manage explorations* page to find, open and delete the saved reports of a website.
- Roles: view opens, write saves, admin manages the reports of its websites, super user runs raw SQL.
- Limits and the read-only connection are configurable in the `[DataExplorer]` section of `config/config.ini.php`.
- Translated into 12 languages.
