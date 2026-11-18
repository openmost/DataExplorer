## Changelog

## 5.0.0

First release of Data Explorer.

- Free-form explorer for dimensions, metrics, segments and filters, with a live preview that follows the same row limits as the saved report.
- Visualizations: the Matomo table, evolution graph, bar and pie charts and sparkline (KPI card), in the explorer as in the saved report. Any report can apply one saved segment as a filter.
- Raw SQL for super users, run with a dedicated read-only database user created from the setup page in *Administration > System > Data Explorer*. Guarded SELECT subset, allowlist of 11 tables, row, size, subquery and time limits, cache.
- Saved reports appear in the Matomo reporting menu, for one website, several websites or all websites, and work as dashboard widgets, in the API, in exports and in scheduled reports (raw SQL reports when every select column has a name).
- Saved visual reports are archived by `core:archive`, the last 6 months of history are rebuilt when what a report computes changes, a rename or a move keeps it.
- Export of the explorer result with the Matomo export of reports, and a *Manage explorations* page to find, open and delete the saved reports of a website.
- Roles: view opens, write saves, admin manages the reports of its websites, super user runs raw SQL.
- Limits and the read-only connection are configurable in the `[DataExplorer]` section of `config/config.ini.php`.
- Translated into 12 languages.
