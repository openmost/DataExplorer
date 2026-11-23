# DataExplorer

## Description

Explore your Matomo data the way you would in GA4 Explorations, build funnels on any dimension, then save the result as a report in the Matomo menu. Super users can also write raw SQL on the Matomo tables, run through a read-only database user.

### Features

- **Free-form explorer**: drag dimensions, metrics and segments into rows, columns, values and filters, and see the result as you build it. It opens on the last 7 days (today excluded) with Page URL and Device type by Visits and Pageviews ready to drop. The preview is always computed live, and follows the same row limits as the saved report, so a saved report shows what you previewed.
- **Funnels, far more flexible than Matomo's own**: a Funnel tab builds a funnel on the fly, on any past period, without waiting for archiving. Each step matches any visit, action or conversion dimension, with several conditions on the same hit (an event name and the custom dimension sent with it, a page URL and its title), OR inside a group and AND between groups. A step can be optional, directly followed (the next action of the visit) or indirectly followed, and reached within a time limit (seconds to days). The funnel is closed (entered at step 1) or open (entered at any step). A visit that meets step 1 again starts a new pass, so a visitor who goes through the funnel twice counts twice. The result shows the passes, passage rate, abandonments, rate from the start and average time from the previous step of each step, broken down by any visit or action dimension. A funnel saves as a report of the menu, archived like the others.
- **Every dimension and metric of your Matomo**: the core ones and those of the active plugins (goals, ecommerce, events, media, forms, crashes, campaigns, weather...), including the rates and averages Matomo computes from them, such as bounce rate or actions per visit. A dimension appears only while its plugin is active.
- **Value suggestions**: filters and funnel conditions offer the values of their dimension, like the segment editor.
- **Filters with AND and OR**: the filters of one group match any of their conditions, groups all match, for example (Device type is Desktop OR Smartphone) AND (Page URL contains /blog). Operators: is exactly, is not, contains, does not contain, starts with, does not start with, ends with, does not end with, matches a regular expression, and greater or less than (or equal to) for numbers.
- **Visualizations**: the Matomo table, evolution graph, bar chart, pie chart and sparkline (KPI card), and the treemap when the Treemap Visualization plugin is active, the same in the explorer and in the saved report. A table shows a totals row, and any report can apply one saved segment as a filter.
- **Raw SQL** for super users: a SQL editor with a table and column browser, a sample query to start from, a validity check before running, CSV export, and placeholders for the site and the period. The result is drawn as a Matomo table, bar chart, pie chart or KPI card. Queries run with a read-only database user that you create, and a saved raw SQL report can be reopened and edited.
- **Saved reports in the menu**: a saved report becomes a page of the Matomo reporting menu, in the category and subcategory you pick, for one website, a list of websites or all websites. It also works as a dashboard widget, in the API, in exports and in scheduled reports like any other report. A raw SQL report can be scheduled when every column of its select list has a name (`AS alias` or a plain column), `*` and unnamed expressions are only known once the query ran.
- **Segment processing per report**: a saved report can skip segment processing, so `core:archive` builds it for all visits only and finishes sooner when you pre-process many segments.
- **Open a report**: a searchable picker reopens any saved exploration. When a custom reports plugin is active, it also lists its reports: opening one brings its dimensions, metrics, filters and segment into the explorer, where you can update it in place or save it as an exploration in the same category and page. Parts that cannot be translated are listed, never dropped silently.
- **Descriptions**: a report can carry a description, shown as its documentation in the Matomo menu.
- **Export**: the explorer result has the Matomo export of reports (CSV, TSV, XML, JSON and more).
- **Manage explorations**: a page of the Data Explorer menu lists the reports saved on the website, with links to their page and to the explorer, a search and deletion.
- **Roles**: users with view access open the saved reports and explore the visual explorer (the anonymous user only opens saved reports), users with write access save reports on the websites they can write to, admins edit or delete any report of their websites, and only super users run raw SQL and save a report for all websites.
- Translated into 12 languages.

### Requirements

- Matomo 5.12.0 or later (below 6.0.0).
- MySQL 5.7 or later, or MariaDB, as required by Matomo. PHP version as required by your Matomo.
- For raw SQL only: a MySQL user able to create another read-only user.

### Privacy and data

Everything runs inside your Matomo and its database. The plugin sends no data to third parties.

### Need help with Matomo?

Openmost is an official Matomo Implementation Partner. We design, audit and extend Matomo setups, see [openmost.com](https://openmost.com/?utm_source=matomo_marketplace&utm_medium=referral&utm_campaign=services&utm_content=dataexplorer).

## Support

- Email: ronan@openmost.com
- Homepage: https://openmost.com/matomo/extensions/data-explorer
- Issues: https://github.com/openmost/DataExplorer/issues

## License

GPL v3 or later
