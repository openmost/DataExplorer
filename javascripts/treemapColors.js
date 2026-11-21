/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

/**
 * The treemap of the Treemap Visualization plugin colors its rectangles by their evolution, and leaves
 * them grey when there is none (a date range). For the reports of Data Explorer only, a treemap
 * without evolution is shaded from light to dark with its values, in the first series color of the
 * core graphs. The plugin method is wrapped, never replaced: other reports keep its colors.
 */
(function ($, require) {
  var DARK = [81, 112, 174];
  var LIGHT = [146, 167, 210];

  function shade(ratio) {
    var channels = DARK.map(function (dark, i) {
      return Math.round(LIGHT[i] + (dark - LIGHT[i]) * ratio);
    });
    return 'rgb(' + channels.join(',') + ')';
  }

  function valueOf(node) {
    return (node.data && parseFloat(node.data.$area)) || 0;
  }

  function wrap() {
    var ui = require('piwik/UI');
    var Treemap = ui && ui.TreemapDataTable;
    if (!Treemap || Treemap.prototype.dataExplorerColors) {
      return;
    }

    var setColors = Treemap.prototype._setTreemapColors;
    Treemap.prototype._setTreemapColors = function (root) {
      setColors.call(this, root);

      var report = (this.$element && this.$element.attr('data-report')) || '';
      if (report.indexOf('DataExplorer.') !== 0 || !root || !root.children) {
        return;
      }
      var withEvolution = root.children.some(function (child) {
        return child.data && typeof child.data.evolution !== 'undefined';
      });
      if (withEvolution) {
        return;
      }

      var self = this;
      var max = Math.max.apply(null, root.children.map(valueOf).concat([1]));
      root.children.forEach(function (child) {
        var ratio = Math.sqrt(valueOf(child) / max);
        self.foreachNode(function (node) {
          node.data.$color = shade(ratio);
        }, child);
      });
      this.labelColor = '#fff';
    };
    Treemap.prototype.dataExplorerColors = true;
  }

  $(wrap);
}(jQuery, require));
