## FAQ

**Can a user with view access see raw SQL reports?** Yes, a saved report is a report like the others: view access opens it. Only super users write or edit the SQL. The anonymous user (public dashboards) is served the cached result only: a raw SQL report shows its data once a signed-in user has opened it for the same period, so a public page never triggers a query on the database.

**Does raw SQL see other websites?** A query is checked against the website of the report. When the guard cannot prove that every per-site table is filtered with `{idsite}`, the editor flags it and the query cannot be saved on a site or run as a saved report.

**Can I use it without raw SQL?** Yes. The visual explorer needs no extra database user.

**How do these funnels differ from the funnels of Matomo?** A Matomo funnel is defined in advance and reports from the day it is set up. A Data Explorer funnel is computed on any past period as soon as you build it, its steps can read any dimension (several conditions on the same hit), be optional, directly followed or reached within a time limit, it can be open or closed, broken down by a dimension, and a visit that starts it again counts as a new pass. Saved, it is archived and shows in the menu like any report.
