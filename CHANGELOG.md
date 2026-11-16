## Changelog

## 5.0.0

First release of Data Explorer.

- Free-form explorer for dimensions, metrics, segments and filters, with a live preview that follows the same row limits as the saved report.
- Visualizations: table with a totals row and one compared segment, line, bar and pie charts, and KPI card.
- Raw SQL for super users, run with a dedicated read-only database user created from the setup page in *Administration > System > Data Explorer*. Guarded SELECT subset, allowlist of 11 tables, row, size, subquery and time limits, cache.
- Saved reports appear in the Matomo reporting menu, for one website, several websites or all websites, and work as dashboard widgets, in the API, in exports and in scheduled reports.
- Saved visual reports are archived by `core:archive`, the last 6 months of history are invalidated when a report is saved.
- Roles: view opens, write saves, admin manages the reports of its websites, super user runs raw SQL.
- Limits and the read-only connection are configurable in the `[DataExplorer]` section of `config/config.ini.php`.
- Translated into 12 languages.
