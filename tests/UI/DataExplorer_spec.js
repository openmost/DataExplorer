/*!
 * Matomo - free/libre analytics platform
 *
 * The explorer page: free-form exploration, raw SQL tab, save dialog.
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

describe('DataExplorer', function () {
  this.fixture = 'Piwik\\Plugins\\DataExplorer\\tests\\Fixtures\\ExplorerFixture';

  const params = 'idSite=1&period=range&date=2024-03-01,2024-03-03';
  const pageUrl = `?module=CoreHome&action=index&${params}#?${params}&category=DataExplorer_DataExplorer&subcategory=DataExplorer_Explore`;

  const waitForExplorer = async function () {
    await page.waitForSelector('.dataExplorer__columns');
    await page.waitForNetworkIdle();
  };

  const waitForCanvas = async function () {
    await page.waitForFunction(() => !document.querySelector('.dataExplorerCanvas[aria-busy="true"]'));
    await page.waitForNetworkIdle();
  };

  // adds a catalogue item with the "+" picker of a variables list
  const addVariable = async function (kind, name) {
    await page.click(`.dataExplorerVariables__add-${kind}`);
    await page.type('.dataExplorerPicker__search', name);
    await page.evaluate((label) => {
      const button = Array.prototype.find.call(
        document.querySelectorAll('.dataExplorerPicker__item'),
        (element) => element.textContent.trim() === label,
      );
      button.click();
    }, name);
    await page.click('.dataExplorerPicker__footer .btn');
  };

  // native HTML5 drag and drop of a variable onto a drop zone
  const dragVariable = async function (name, zone) {
    await page.evaluate((label, zoneName) => {
      const source = Array.prototype.find.call(
        document.querySelectorAll('.dataExplorerVariable'),
        (element) => element.querySelector('.dataExplorerVariable__name').textContent.trim() === label,
      );
      const target = document.querySelector(`.dataExplorerDropZone[data-zone="${zoneName}"]`);
      const dataTransfer = new DataTransfer();
      source.dispatchEvent(new DragEvent('dragstart', { bubbles: true, dataTransfer }));
      target.dispatchEvent(new DragEvent('dragenter', { bubbles: true, cancelable: true, dataTransfer }));
      target.dispatchEvent(new DragEvent('dragover', { bubbles: true, cancelable: true, dataTransfer }));
      target.dispatchEvent(new DragEvent('drop', { bubbles: true, cancelable: true, dataTransfer }));
      source.dispatchEvent(new DragEvent('dragend', { bubbles: true, dataTransfer }));
    }, name, zone);
  };

  const tableText = async function () {
    return page.evaluate(() => {
      const table = document.querySelector('.dataExplorerResult');
      return table ? table.textContent.replace(/\s+/g, ' ') : '';
    });
  };

  afterEach(async function () {
    testEnvironment.idSitesViewAccess = [];
    testEnvironment.save();
  });

  it('should show the explorer without the SQL tab to a view user', async function () {
    testEnvironment.idSitesViewAccess = [1];
    testEnvironment.save();

    await page.goto(pageUrl);
    await waitForExplorer();

    expect(await page.$('.dataExplorer__tab[data-tab="sql"]')).to.equal(null);
    expect(await page.$('.dataExplorer__save')).to.equal(null);
    expect(await page.screenshotSelector('.dataExplorer')).to.matchImage('view_user');
  });

  it('should show the SQL tab to a super user', async function () {
    await page.goto(pageUrl);
    await waitForExplorer();

    expect(await page.$('.dataExplorer__tab[data-tab="sql"]')).to.be.ok;
    await page.click('.dataExplorer__tab[data-tab="sql"]');
    await page.waitForSelector('.dataExplorerSql');

    // the test instance has no read-only user: the tab links to the setup page
    expect(await page.$('.dataExplorerSql__setupLink')).to.be.ok;
    expect(await page.screenshotSelector('.dataExplorer')).to.matchImage('sql_tab_not_configured');

    await page.click('.dataExplorer__tab[data-tab="explore"]');
  });

  it('should render the table after dragging Country to Rows and Visits to Values', async function () {
    await addVariable('dimension', 'Country');
    await addVariable('metric', 'Visits');
    await dragVariable('Country', 'rows');
    await dragVariable('Visits', 'values');
    await waitForCanvas();

    const text = await tableText();
    expect(text).to.contain('France');
    expect(text).to.contain('Germany');
    expect(await page.screenshotSelector('.dataExplorer')).to.matchImage('country_visits');
  });

  it('should render the pivot after adding Device type to Columns', async function () {
    await addVariable('dimension', 'Device type');
    await dragVariable('Device type', 'column');
    await waitForCanvas();

    expect(await page.$('.dataExplorerResult__pivot')).to.be.ok;
    expect(await tableText()).to.contain('Desktop');
    expect(await page.screenshotSelector('.dataExplorer')).to.matchImage('country_by_device');
  });

  it('should render the bar chart', async function () {
    await page.click('.dataExplorerVisualizations__button[data-visualization="bar"]');
    await waitForCanvas();
    await page.waitForFunction(() => !!document.querySelector('.dataExplorerCanvas .dataExplorerChart__canvas canvas'));

    expect(await page.screenshotSelector('.dataExplorer')).to.matchImage('bar_chart');
  });

  it('should restore the exploration from the URL hash after a reload', async function () {
    await page.reload();
    await waitForExplorer();
    await waitForCanvas();

    const state = await page.evaluate(() => ({
      rows: Array.prototype.map.call(
        document.querySelectorAll('.dataExplorerDropZone[data-zone="rows"] .dataExplorerChip__name'),
        (element) => element.textContent.trim(),
      ),
      column: Array.prototype.map.call(
        document.querySelectorAll('.dataExplorerDropZone[data-zone="column"] .dataExplorerChip__name'),
        (element) => element.textContent.trim(),
      ),
      bar: !!document.querySelector('.dataExplorerVisualizations__button[data-visualization="bar"].is-active'),
    }));
    expect(state).to.deep.equal({ rows: ['Country'], column: ['Device type'], bar: true });
  });

  it('should save the exploration as a report', async function () {
    await page.click('.dataExplorerVisualizations__button[data-visualization="table"]');
    await waitForCanvas();
    await page.click('.dataExplorer__save');
    await page.waitForSelector('.modal.open #dataExplorerSaveName');

    await page.type('#dataExplorerSaveName', 'Countries by device');
    expect(await page.screenshotSelector('.modal.open')).to.matchImage('save_dialog');

    await page.click('.modal.open .dataExplorerSaveDialog__create input');
    await page.waitForFunction(() => !document.querySelector('.modal.open'));
    await page.waitForNetworkIdle();

    const notice = await page.evaluate(() => document.querySelector('.dataExplorer .notification').textContent);
    expect(notice).to.match(/saved/i);
    const selected = await page.evaluate(() => {
      const select = document.querySelector('#dataExplorerOpenReport');
      return select.options[select.selectedIndex].textContent.trim();
    });
    expect(selected).to.equal('Countries by device');
  });
});
