/*!
 * Matomo - free/libre analytics platform
 *
 * Saved DataExplorer reports in the reporting menu and in a dashboard.
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

describe('DataExplorerSavedReports', function () {
  this.fixture = 'Piwik\\Plugins\\DataExplorer\\tests\\Fixtures\\SavedReportsFixture';

  const params = 'idSite=1&period=day&date=2024-03-03';
  const pageUrl = `?module=CoreHome&action=index&${params}#?${params}&category=General_Visitors&subcategory=Explorations`;
  const kpiWidgetUrl = `?module=Widgetize&action=iframe&moduleToWidgetize=DataExplorer&actionToWidgetize=getReport&idReport=3&disableLink=1&widget=1&${params}`;

  const setThemeMode = async function (themeMode) {
    await page.evaluate((mode) => {
      window.piwik.setThemeMode(mode);
    }, themeMode);
    await page.waitForFunction((mode) => window.piwik.getThemeMode() === mode, {}, themeMode);
  };

  const waitForCharts = async function () {
    await page.waitForNetworkIdle();
    await page.waitForFunction(() => Array.prototype.every.call(
      document.querySelectorAll('.dataExplorerChart__canvas, .dataExplorerKpi__sparkline'),
      (element) => !!element.querySelector('canvas'),
    ));
  };

  after(async function () {
    await setThemeMode('light');
  });

  it('should show the saved reports in their menu place', async function () {
    await page.goto(pageUrl);
    await waitForCharts();

    const titles = await page.evaluate(() => Array.prototype.map.call(
      document.querySelectorAll('.matomo-widget .widgetTop .widgetName, .reporting-page .card-title'),
      (element) => element.textContent.trim(),
    ));
    expect(titles.join('|')).to.match(/Countries by device/);
    expect(titles.join('|')).to.match(/Visits over time/);
    expect(titles.join('|')).to.match(/Visits card/);

    expect(await page.screenshotSelector('.pageWrap')).to.matchImage('reporting_page');
  });

  it('should switch the table to the bar chart', async function () {
    const table = '.dataTable[data-report="DataExplorer.getReport"].dataTableVizHtmlTable';
    await page.click(`${table} .activateVisualizationSelection`);
    await page.click(`${table} [data-footer-icon-id="DataExplorerBar"]`);
    await waitForCharts();

    expect(await page.$('[vue-entry="DataExplorer.EChartsBar"], .dataExplorerChart__canvas canvas')).to.be.ok;
    expect(await page.screenshotSelector('.pageWrap')).to.matchImage('table_as_bar');
  });

  it('should switch the table to the pie chart', async function () {
    await page.click('.dataTable[data-report="DataExplorer.getReport"] .activateVisualizationSelection');
    await page.click('[data-footer-icon-id="DataExplorerPie"]');
    await waitForCharts();

    expect(await page.screenshotSelector('.pageWrap')).to.matchImage('table_as_pie');
  });

  it('should render the reporting page in dark mode', async function () {
    await setThemeMode('dark');
    await waitForCharts();

    expect(await page.screenshotSelector('.pageWrap')).to.matchImage('reporting_page_dark');
  });

  it('should render a KPI card as a dashboard widget', async function () {
    await setThemeMode('light');
    await page.goto(kpiWidgetUrl);
    await waitForCharts();

    expect(await page.$('.dataExplorerKpi__value')).to.be.ok;
    expect(await page.screenshot({ fullPage: true })).to.matchImage('kpi_widget');
  });

  it('should render the KPI card widget in dark mode', async function () {
    await setThemeMode('dark');
    await waitForCharts();

    expect(await page.screenshot({ fullPage: true })).to.matchImage('kpi_widget_dark');
  });
});
