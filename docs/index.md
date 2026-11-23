## Documentation

### Read-only SQL user setup

Raw SQL never runs with the Matomo database user. Open *Administration > System > Data Explorer* (super user): the page shows the statements to create a user that can only read the allowed tables, and the section to add to `config/config.ini.php`.

1. Run the displayed `CREATE USER` and `GRANT SELECT` statements as a MySQL administrator, with your own strong password instead of the placeholder. There is one `GRANT SELECT` per table, never a grant on the whole database.
2. Add the displayed block to `config/config.ini.php` with the same password:

```
[DataExplorer]
readonly_username = "matomo_readonly"
readonly_password = "your-password"
```

Add `readonly_host`, `readonly_port`, `readonly_dbname`, `readonly_unix_socket` or `readonly_tables_prefix` only when this user connects somewhere else than Matomo. Anything that is not set follows the `[database]` section.

The setup page then shows the state: ready, missing grants (with the lines to run), a user that can write or reads too much (raw SQL stays disabled), or a failed connection.

The 11 tables that raw SQL can read are `log_visit`, `log_link_visit_action`, `log_conversion`, `log_conversion_item`, `log_action`, `log_page_view_time`, `site`, `site_url`, `goal`, `custom_dimensions` and `segment`. Every other table is refused, including the archive tables and the user, option and plugin tables.

### Configuration

All keys go in a `[DataExplorer]` section of `config/config.ini.php`. At install, only the row cap keys and `dataexplorer_sql_row_limit` are written there, with their current values. The per-dimension keys `_dimensions_N` are written as concrete numbers, so a later edit of a `_default` key does not change them: edit the `_dimensions_N` keys directly. Every other key is optional and uses its default when absent.

| Key | Default | Meaning |
|---|---|---|
| `datatable_archiving_maximum_rows_dataexplorer_default` | core value (500) | Rows kept in the main table of a report, the rest is summed into "Others" |
| `datatable_archiving_maximum_rows_dataexplorer_dimensions_N` | the default above | Same cap for reports with N row dimensions (1 to 9) |
| `datatable_archiving_maximum_rows_subtable_dataexplorer_default` | 100 | Rows kept in each subtable |
| `datatable_archiving_maximum_rows_subtable_dataexplorer_dimensions_N` | the default above | Same for subtables of reports with N row dimensions (2 to 9) |
| `dataexplorer_max_dimensions` | 3 | Row dimensions allowed in a report, from 1 to 9 |
| `dataexplorer_sql_row_limit` | core value (50000) | Hard cap on the rows one SQL statement of the visual explorer reads |
| `dataexplorer_tuple_chunk_size` | 1000 | Most label combinations one SQL statement of the visual explorer filters on: deeper levels of a report run in batches of this size |
| `raw_sql_row_limit` | 10000 | Rows a raw SQL query returns |
| `max_execution_time_seconds` | 30 | Time limit of a raw SQL query |
| `raw_sql_cache_ttl` | 300 | Seconds a raw SQL result is cached, 0 disables the cache |
| `raw_sql_max_points` | 30 | Points of a raw SQL line chart or KPI sparkline (the query runs once per point) |
| `dataexplorer_sql_max_length` | 65536 | Longest accepted query, in bytes |
| `dataexplorer_sql_max_subqueries` | 50 | Most subqueries and UNION branches in one query |
| `invalidation_months` | 6 | Months of history invalidated when a report is saved |
| `readonly_*` | none | Connection of the read-only user, see above |
| `readonly_charset`, `readonly_collation`, `readonly_adapter` | follow `[database]` | Advanced, optional: set only if the read-only connection needs different values |

Raising the row caps or the number of dimensions makes the archives bigger and slower. A higher number of dimensions multiplies the subtables of each report. Lowering `dataexplorer_max_dimensions` disables the saved reports that use more row dimensions: they leave the menu and stop being archived until the value is raised again.

### Archiving

Saved visual reports are archived by `core:archive` like the core reports, so they open fast and work with segments, periods, the API and scheduled reports. When you save a report that computes something new (dimensions, metrics, filters, segment, visualization), the last `invalidation_months` months of history are invalidated and rebuilt by the next archiving run. A rename or a move in the menu keeps the history, and placing a report on more websites only builds the history of the added websites. When browser archiving is on, a newly saved or edited report is archived the first time it is displayed.

A report that nobody opens with a segment can skip segment processing: uncheck *Process this report for segments* when you save it. Archiving then builds it for all visits only, which saves time on instances with many pre-processed segments. Opened with a segment, the report says it is not processed for segments. Turning the option off keeps the archived history; turning it back on rebuilds it, so the segments get the report too.

The explorer itself never reads archives: what you build in it is computed live from the log tables, only the reports saved in the menu are archived.

Raw SQL reports are not archived: they run live on the read-only connection, with the time limit, the row limit and the cache above.

Each raw SQL run is logged at the `INFO` level (query hash, user, site, status, duration, row count, never the values) and triggers the `DataExplorer.rawSqlExecuted` event. Set `log_level = INFO` in the `[log]` section to see these lines, or let an activity log plugin listen to the event.

### Limits

- Raw SQL accepts a strict subset of SELECT. `WITH` (common table expressions) is refused, and so are statements other than one SELECT, and some valid MySQL forms that the guard cannot prove safe. The refusal message points at the part of the query it could not accept.
- The placeholders `{idsite}`, `{date_start}` and `{date_end}` work in `WHERE`, `ON` and `HAVING` conditions and inside select expressions, for example `SUM(v.visit_last_action_time >= {date_start})` or `CASE WHEN v.idsite = {idsite} THEN 1 END`. A bare placeholder as a whole select column or in `GROUP BY` is refused: wrap it in parentheses, `({date_start}) AS start`.
- A query that is not provably limited to the website of the report is flagged as a warning in the editor. It cannot be saved on a site, and a saved one is not run, until every per-site table is filtered with `{idsite}`.
- Only the 11 tables listed above can be read. Archive tables and plugin tables are not readable.
- A report has at most 10 metrics, and a KPI card exactly one.
- The visual preview uses the same row caps as saved reports (500 rows per table by default, see the Configuration table).
- The Explore and Manage explorations pages are reserved to the plugin: a report cannot be placed on them.
- A funnel has 2 to 10 steps, and its last step cannot be optional. Steps read visit, action and conversion dimensions (goals, ecommerce), the breakdown a visit or action dimension. A Page URL or Page title condition matches page views only: an event or a content interaction carries the URL of its page but is not a view of it.
