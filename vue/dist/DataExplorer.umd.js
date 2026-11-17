(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory(require("CoreHome"), require("vue"), require("CorePluginsAdmin"), require("tslib"));
	else if(typeof define === 'function' && define.amd)
		define(["CoreHome", , "CorePluginsAdmin", "tslib"], factory);
	else if(typeof exports === 'object')
		exports["DataExplorer"] = factory(require("CoreHome"), require("vue"), require("CorePluginsAdmin"), require("tslib"));
	else
		root["DataExplorer"] = factory(root["CoreHome"], root["Vue"], root["CorePluginsAdmin"], root["tslib"]);
})((typeof self !== 'undefined' ? self : this), function(__WEBPACK_EXTERNAL_MODULE__19dc__, __WEBPACK_EXTERNAL_MODULE__8bbf__, __WEBPACK_EXTERNAL_MODULE_a5a2__, __WEBPACK_EXTERNAL_MODULE_d7bc__) {
return /******/ (function(modules) { // webpackBootstrap
/******/ 	// install a JSONP callback for chunk loading
/******/ 	function webpackJsonpCallback(data) {
/******/ 		var chunkIds = data[0];
/******/ 		var moreModules = data[1];
/******/
/******/
/******/ 		// add "moreModules" to the modules object,
/******/ 		// then flag all "chunkIds" as loaded and fire callback
/******/ 		var moduleId, chunkId, i = 0, resolves = [];
/******/ 		for(;i < chunkIds.length; i++) {
/******/ 			chunkId = chunkIds[i];
/******/ 			if(Object.prototype.hasOwnProperty.call(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 				resolves.push(installedChunks[chunkId][0]);
/******/ 			}
/******/ 			installedChunks[chunkId] = 0;
/******/ 		}
/******/ 		for(moduleId in moreModules) {
/******/ 			if(Object.prototype.hasOwnProperty.call(moreModules, moduleId)) {
/******/ 				modules[moduleId] = moreModules[moduleId];
/******/ 			}
/******/ 		}
/******/ 		if(parentJsonpFunction) parentJsonpFunction(data);
/******/
/******/ 		while(resolves.length) {
/******/ 			resolves.shift()();
/******/ 		}
/******/
/******/ 	};
/******/
/******/
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// object to store loaded and loading chunks
/******/ 	// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 	// Promise = chunk loading, 0 = chunk loaded
/******/ 	var installedChunks = {
/******/ 		0: 0
/******/ 	};
/******/
/******/
/******/
/******/ 	// script path function
/******/ 	function jsonpScriptSrc(chunkId) {
/******/ 		return __webpack_require__.p + "DataExplorer.umd." + ({"1":"codemirror","2":"echarts","3":"vendors~codemirror","4":"vendors~echarts"}[chunkId]||chunkId) + ".js"
/******/ 	}
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/ 	// This file contains only the entry chunk.
/******/ 	// The chunk loading function for additional chunks
/******/ 	__webpack_require__.e = function requireEnsure(chunkId) {
/******/ 		var promises = [];
/******/
/******/
/******/ 		// JSONP chunk loading for javascript
/******/
/******/ 		var installedChunkData = installedChunks[chunkId];
/******/ 		if(installedChunkData !== 0) { // 0 means "already installed".
/******/
/******/ 			// a Promise means "currently loading".
/******/ 			if(installedChunkData) {
/******/ 				promises.push(installedChunkData[2]);
/******/ 			} else {
/******/ 				// setup Promise in chunk cache
/******/ 				var promise = new Promise(function(resolve, reject) {
/******/ 					installedChunkData = installedChunks[chunkId] = [resolve, reject];
/******/ 				});
/******/ 				promises.push(installedChunkData[2] = promise);
/******/
/******/ 				// start chunk loading
/******/ 				var script = document.createElement('script');
/******/ 				var onScriptComplete;
/******/
/******/ 				script.charset = 'utf-8';
/******/ 				script.timeout = 120;
/******/ 				if (__webpack_require__.nc) {
/******/ 					script.setAttribute("nonce", __webpack_require__.nc);
/******/ 				}
/******/ 				script.src = jsonpScriptSrc(chunkId);
/******/
/******/ 				// create error before stack unwound to get useful stacktrace later
/******/ 				var error = new Error();
/******/ 				onScriptComplete = function (event) {
/******/ 					// avoid mem leaks in IE.
/******/ 					script.onerror = script.onload = null;
/******/ 					clearTimeout(timeout);
/******/ 					var chunk = installedChunks[chunkId];
/******/ 					if(chunk !== 0) {
/******/ 						if(chunk) {
/******/ 							var errorType = event && (event.type === 'load' ? 'missing' : event.type);
/******/ 							var realSrc = event && event.target && event.target.src;
/******/ 							error.message = 'Loading chunk ' + chunkId + ' failed.\n(' + errorType + ': ' + realSrc + ')';
/******/ 							error.name = 'ChunkLoadError';
/******/ 							error.type = errorType;
/******/ 							error.request = realSrc;
/******/ 							chunk[1](error);
/******/ 						}
/******/ 						installedChunks[chunkId] = undefined;
/******/ 					}
/******/ 				};
/******/ 				var timeout = setTimeout(function(){
/******/ 					onScriptComplete({ type: 'timeout', target: script });
/******/ 				}, 120000);
/******/ 				script.onerror = script.onload = onScriptComplete;
/******/ 				document.head.appendChild(script);
/******/ 			}
/******/ 		}
/******/ 		return Promise.all(promises);
/******/ 	};
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "plugins/DataExplorer/vue/dist/";
/******/
/******/ 	// on error function for async loading
/******/ 	__webpack_require__.oe = function(err) { console.error(err); throw err; };
/******/
/******/ 	var jsonpArray = (typeof self !== 'undefined' ? self : this)["webpackJsonpDataExplorer"] = (typeof self !== 'undefined' ? self : this)["webpackJsonpDataExplorer"] || [];
/******/ 	var oldJsonpFunction = jsonpArray.push.bind(jsonpArray);
/******/ 	jsonpArray.push = webpackJsonpCallback;
/******/ 	jsonpArray = jsonpArray.slice();
/******/ 	for(var i = 0; i < jsonpArray.length; i++) webpackJsonpCallback(jsonpArray[i]);
/******/ 	var parentJsonpFunction = oldJsonpFunction;
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "fae3");
/******/ })
/************************************************************************/
/******/ ({

/***/ "19dc":
/***/ (function(module, exports) {

module.exports = __WEBPACK_EXTERNAL_MODULE__19dc__;

/***/ }),

/***/ "8bbf":
/***/ (function(module, exports) {

module.exports = __WEBPACK_EXTERNAL_MODULE__8bbf__;

/***/ }),

/***/ "a5a2":
/***/ (function(module, exports) {

module.exports = __WEBPACK_EXTERNAL_MODULE_a5a2__;

/***/ }),

/***/ "d7bc":
/***/ (function(module, exports) {

module.exports = __WEBPACK_EXTERNAL_MODULE_d7bc__;

/***/ }),

/***/ "fae3":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, "useEChart", function() { return /* reexport */ useEChart; });
__webpack_require__.d(__webpack_exports__, "chartTheme", function() { return /* reexport */ chartTheme; });
__webpack_require__.d(__webpack_exports__, "EChartsLine", function() { return /* reexport */ EChartsLine; });
__webpack_require__.d(__webpack_exports__, "EChartsBar", function() { return /* reexport */ EChartsBar; });
__webpack_require__.d(__webpack_exports__, "EChartsPie", function() { return /* reexport */ EChartsPie; });
__webpack_require__.d(__webpack_exports__, "KpiCard", function() { return /* reexport */ KpiCard; });
__webpack_require__.d(__webpack_exports__, "Explorer", function() { return /* reexport */ Explorer; });
__webpack_require__.d(__webpack_exports__, "RawSqlEditor", function() { return /* reexport */ RawSqlEditor; });
__webpack_require__.d(__webpack_exports__, "SaveDialog", function() { return /* reexport */ SaveDialog; });
__webpack_require__.d(__webpack_exports__, "ReadOnlySetup", function() { return /* reexport */ ReadOnlySetup; });
__webpack_require__.d(__webpack_exports__, "ReportsManager", function() { return /* reexport */ ReportsManager; });
__webpack_require__.d(__webpack_exports__, "HASH_PARAM", function() { return /* reexport */ HASH_PARAM; });
__webpack_require__.d(__webpack_exports__, "REPORT_HASH_PARAM", function() { return /* reexport */ REPORT_HASH_PARAM; });
__webpack_require__.d(__webpack_exports__, "EXPLORE_SUBCATEGORY", function() { return /* reexport */ EXPLORE_SUBCATEGORY; });
__webpack_require__.d(__webpack_exports__, "VISUALIZATION_ICONS", function() { return /* reexport */ VISUALIZATION_ICONS; });
__webpack_require__.d(__webpack_exports__, "VISUALIZATIONS", function() { return /* reexport */ VISUALIZATIONS; });
__webpack_require__.d(__webpack_exports__, "FILTER_OPERATORS", function() { return /* reexport */ FILTER_OPERATORS; });
__webpack_require__.d(__webpack_exports__, "defaultState", function() { return /* reexport */ defaultState; });
__webpack_require__.d(__webpack_exports__, "reportTypeOf", function() { return /* reexport */ reportTypeOf; });
__webpack_require__.d(__webpack_exports__, "hasRowDimensions", function() { return /* reexport */ hasRowDimensions; });
__webpack_require__.d(__webpack_exports__, "normalize", function() { return /* reexport */ normalize; });
__webpack_require__.d(__webpack_exports__, "clampToLimits", function() { return /* reexport */ clampToLimits; });
__webpack_require__.d(__webpack_exports__, "encode", function() { return /* reexport */ encode; });
__webpack_require__.d(__webpack_exports__, "decode", function() { return /* reexport */ decode; });
__webpack_require__.d(__webpack_exports__, "isFilterComplete", function() { return /* reexport */ isFilterComplete; });
__webpack_require__.d(__webpack_exports__, "toDefinition", function() { return /* reexport */ toDefinition; });
__webpack_require__.d(__webpack_exports__, "fromDefinition", function() { return /* reexport */ fromDefinition; });

// CONCATENATED MODULE: ./node_modules/@vue/cli-service/lib/commands/build/setPublicPath.js
// This file is imported into lib/wc client bundles.

if (typeof window !== 'undefined') {
  var currentScript = window.document.currentScript
  if (false) { var getCurrentScript; }

  var src = currentScript && currentScript.src.match(/(.+\/)[^/]+\.js(\?.*)?$/)
  if (src) {
    __webpack_require__.p = src[1] // eslint-disable-line
  }
}

// Indicate to webpack that this file can be concatenated
/* harmony default export */ var setPublicPath = (null);

// EXTERNAL MODULE: external {"commonjs":"vue","commonjs2":"vue","root":"Vue"}
var external_commonjs_vue_commonjs2_vue_root_Vue_ = __webpack_require__("8bbf");

// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Charts/useEChart.ts
/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

let modules = null;
function loadECharts() {
  if (!modules) {
    modules = Promise.all(/* import() | echarts */[__webpack_require__.e(4), __webpack_require__.e(2)]).then(__webpack_require__.bind(null, "540c"));
  }
  return modules;
}
// core Morpheus graph series colors, used when the theme does not define its own
const FALLBACK_SERIES = ['#0277BD', '#FF8F00', '#AD1457', '#6A1B9A', '#558B2F', '#00838F', '#283593', '#D84315'];
// bumped when the user switches between light and dark mode, so chart options read the new colors
const themeVersion = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(0);
window.addEventListener('themeModeChange', () => {
  // the theme stylesheet applies on the next frame
  window.requestAnimationFrame(() => {
    themeVersion.value += 1;
  });
});
function cssVariable(style, name, fallback = '') {
  const value = style.getPropertyValue(name).trim();
  return value || fallback;
}
/**
 * Colors of the active Matomo theme (light, dark or custom), read from its CSS variables (Matomo
 * 5.10 or higher). Only the state colors, without a variable before Matomo 5.14.1, and the series
 * of a theme without its own keep the core Morpheus values.
 */
function chartTheme() {
  const style = window.getComputedStyle(document.documentElement);
  // the accent of the explorer (theme link color) leads, like the rest of the plugin surfaces
  const base = cssVariable(style, '--theme-color-link', FALLBACK_SERIES[0]);
  return {
    // read here so that computed chart options follow theme mode changes
    version: themeVersion.value,
    series: [base, ...FALLBACK_SERIES.filter(color => color.toLowerCase() !== base.toLowerCase())],
    text: cssVariable(style, '--theme-color-text'),
    textLight: cssVariable(style, '--theme-color-text-lighter'),
    border: cssVariable(style, '--theme-color-border'),
    background: cssVariable(style, '--theme-color-background-contrast'),
    success: cssVariable(style, '--theme-color-success', '#43a047'),
    danger: cssVariable(style, '--theme-color-danger', '#c0322e')
  };
}
/**
 * Draws option into el once ECharts is loaded, redraws when option changes, follows the size
 * of the container and frees the chart when the component is unmounted.
 */
function useEChart(el, option) {
  let chart = null;
  let observer = null;
  let unmounted = false;
  const resize = () => {
    if (chart) {
      chart.resize();
    }
  };
  Object(external_commonjs_vue_commonjs2_vue_root_Vue_["onMounted"])(() => {
    loadECharts().then(({
      init
    }) => {
      if (unmounted || !el.value) {
        return;
      }
      chart = init(el.value, null, {
        renderer: 'canvas'
      });
      chart.setOption(option.value, true);
      if (typeof ResizeObserver !== 'undefined') {
        observer = new ResizeObserver(resize);
        observer.observe(el.value);
      } else {
        window.addEventListener('resize', resize);
      }
    });
  });
  Object(external_commonjs_vue_commonjs2_vue_root_Vue_["watch"])(option, value => {
    if (chart) {
      chart.setOption(value, true);
    }
  });
  Object(external_commonjs_vue_commonjs2_vue_root_Vue_["onBeforeUnmount"])(() => {
    unmounted = true;
    if (observer) {
      observer.disconnect();
    } else {
      window.removeEventListener('resize', resize);
    }
    if (chart) {
      chart.dispose();
      chart = null;
    }
  });
}
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Charts/EChartsLine.vue?vue&type=template&id=72659c4d

const _hoisted_1 = ["aria-label"];
function render(_ctx, _cache, $props, $setup, $data, $options) {
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
    ref: "chart",
    class: "dataExplorerChart__canvas",
    role: "img",
    "aria-label": _ctx.ariaLabel
  }, null, 8, _hoisted_1);
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Charts/EChartsLine.vue?vue&type=template&id=72659c4d

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Charts/EChartsLine.vue?vue&type=script&lang=ts


/**
 * Evolution of one or more metrics, one point per period (DataExplorerLine visualization).
 */
/* harmony default export */ var EChartsLinevue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  name: 'EChartsLine',
  props: {
    labels: {
      type: Array,
      default: () => []
    },
    series: {
      type: Array,
      default: () => []
    }
  },
  setup(props) {
    const chart = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    const option = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      const theme = chartTheme();
      const axisLine = {
        lineStyle: {
          color: theme.border
        }
      };
      return {
        color: theme.series,
        textStyle: {
          color: theme.text
        },
        tooltip: {
          trigger: 'axis'
        },
        legend: {
          show: props.series.length > 1,
          top: 0,
          textStyle: {
            color: theme.text
          }
        },
        grid: {
          left: 8,
          right: 16,
          top: props.series.length > 1 ? 32 : 16,
          bottom: props.labels.length > 40 ? 48 : 8,
          containLabel: true
        },
        dataZoom: props.labels.length > 40 ? [{
          type: 'inside'
        }, {
          type: 'slider',
          height: 20
        }] : [],
        xAxis: {
          type: 'category',
          boundaryGap: false,
          data: props.labels,
          axisLine,
          axisLabel: {
            color: theme.textLight
          }
        },
        yAxis: {
          type: 'value',
          axisLabel: {
            color: theme.textLight
          },
          splitLine: {
            lineStyle: {
              color: theme.border
            }
          }
        },
        series: props.series.map(serie => ({
          name: serie.name,
          type: 'line',
          data: serie.data,
          showSymbol: props.labels.length <= 31,
          connectNulls: false
        }))
      };
    });
    useEChart(chart, option);
    const ariaLabel = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => props.series.map(serie => serie.name).join(', '));
    return {
      chart,
      ariaLabel
    };
  }
}));
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Charts/EChartsLine.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Charts/EChartsLine.vue



EChartsLinevue_type_script_lang_ts.render = render

/* harmony default export */ var EChartsLine = (EChartsLinevue_type_script_lang_ts);
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Charts/EChartsBar.vue?vue&type=template&id=d28eba44

const EChartsBarvue_type_template_id_d28eba44_hoisted_1 = ["aria-label"];
function EChartsBarvue_type_template_id_d28eba44_render(_ctx, _cache, $props, $setup, $data, $options) {
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
    ref: "chart",
    class: "dataExplorerChart__canvas",
    role: "img",
    "aria-label": _ctx.metricName
  }, null, 8, EChartsBarvue_type_template_id_d28eba44_hoisted_1);
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Charts/EChartsBar.vue?vue&type=template&id=d28eba44

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Charts/EChartsBar.vue?vue&type=script&lang=ts


/**
 * Top rows of a table by its first metric plus "Others", one bar per metric (DataExplorerBar).
 */
/* harmony default export */ var EChartsBarvue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  name: 'EChartsBar',
  props: {
    labels: {
      type: Array,
      default: () => []
    },
    values: {
      type: Array,
      default: () => []
    },
    metricName: {
      type: String,
      default: ''
    },
    // one series per metric, grouped bars; when empty the chart shows values only
    series: {
      type: Array,
      default: () => []
    }
  },
  setup(props) {
    const chart = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    const option = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      const theme = chartTheme();
      return {
        color: theme.series,
        textStyle: {
          color: theme.text
        },
        tooltip: {
          trigger: 'axis',
          axisPointer: {
            type: 'shadow'
          }
        },
        grid: {
          left: 8,
          right: 16,
          top: 16,
          bottom: props.series.length > 1 ? 36 : 8,
          containLabel: true
        },
        xAxis: {
          type: 'category',
          data: props.labels,
          axisLine: {
            lineStyle: {
              color: theme.border
            }
          },
          axisLabel: {
            color: theme.textLight,
            interval: 0,
            rotate: props.labels.length > 6 ? 30 : 0
          }
        },
        yAxis: {
          type: 'value',
          axisLabel: {
            color: theme.textLight
          },
          splitLine: {
            lineStyle: {
              color: theme.border
            }
          }
        },
        legend: props.series.length > 1 ? {
          bottom: 0,
          textStyle: {
            color: theme.textLight
          }
        } : undefined,
        series: (props.series.length ? props.series : [{
          name: props.metricName,
          data: props.values
        }]).map(line => ({
          name: line.name,
          type: 'bar',
          data: line.data
        }))
      };
    });
    useEChart(chart, option);
    return {
      chart
    };
  }
}));
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Charts/EChartsBar.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Charts/EChartsBar.vue



EChartsBarvue_type_script_lang_ts.render = EChartsBarvue_type_template_id_d28eba44_render

/* harmony default export */ var EChartsBar = (EChartsBarvue_type_script_lang_ts);
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Charts/EChartsPie.vue?vue&type=template&id=887eb74c

const EChartsPievue_type_template_id_887eb74c_hoisted_1 = ["aria-label"];
function EChartsPievue_type_template_id_887eb74c_render(_ctx, _cache, $props, $setup, $data, $options) {
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
    ref: "chart",
    class: "dataExplorerChart__canvas",
    role: "img",
    "aria-label": _ctx.metricName
  }, null, 8, EChartsPievue_type_template_id_887eb74c_hoisted_1);
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Charts/EChartsPie.vue?vue&type=template&id=887eb74c

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Charts/EChartsPie.vue?vue&type=script&lang=ts


/**
 * Share of the first metric of a table, top rows plus "Others" (DataExplorerPie visualization).
 */
/* harmony default export */ var EChartsPievue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  name: 'EChartsPie',
  props: {
    labels: {
      type: Array,
      default: () => []
    },
    values: {
      type: Array,
      default: () => []
    },
    metricName: {
      type: String,
      default: ''
    }
  },
  setup(props) {
    const chart = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    const option = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      const theme = chartTheme();
      return {
        color: theme.series,
        textStyle: {
          color: theme.text
        },
        tooltip: {
          trigger: 'item'
        },
        legend: {
          type: 'scroll',
          orient: 'vertical',
          right: 0,
          top: 'middle',
          textStyle: {
            color: theme.text
          }
        },
        series: [{
          name: props.metricName,
          type: 'pie',
          radius: ['35%', '70%'],
          center: ['40%', '50%'],
          itemStyle: {
            borderColor: theme.background,
            borderWidth: 1
          },
          label: {
            color: theme.text
          },
          data: props.labels.map((label, index) => ({
            name: label,
            value: props.values[index]
          }))
        }]
      };
    });
    useEChart(chart, option);
    return {
      chart
    };
  }
}));
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Charts/EChartsPie.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Charts/EChartsPie.vue



EChartsPievue_type_script_lang_ts.render = EChartsPievue_type_template_id_887eb74c_render

/* harmony default export */ var EChartsPie = (EChartsPievue_type_script_lang_ts);
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Charts/KpiCard.vue?vue&type=template&id=6112b2a6

const KpiCardvue_type_template_id_6112b2a6_hoisted_1 = {
  class: "dataExplorerKpi"
};
const _hoisted_2 = {
  class: "dataExplorerKpi__value"
};
const _hoisted_3 = ["title"];
const _hoisted_4 = ["aria-label"];
function KpiCardvue_type_template_id_6112b2a6_render(_ctx, _cache, $props, $setup, $data, $options) {
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", KpiCardvue_type_template_id_6112b2a6_hoisted_1, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_2, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.displayValue), 1), _ctx.evolution ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
    key: 0,
    class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["dataExplorerKpi__evolution", _ctx.trendClass]),
    title: _ctx.evolution.tooltip
  }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
    class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["dataExplorerKpi__arrow", _ctx.arrowClass]),
    "aria-hidden": "true"
  }, null, 2), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(" " + Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.evolution.percent), 1)], 10, _hoisted_3)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.sparkline.length ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
    key: 1,
    ref: "chart",
    class: "dataExplorerKpi__sparkline",
    role: "img",
    "aria-label": _ctx.metricName
  }, null, 8, _hoisted_4)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]);
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Charts/KpiCard.vue?vue&type=template&id=6112b2a6

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Charts/KpiCard.vue?vue&type=script&lang=ts


/**
 * KPI card (DataExplorerKpi visualization): the value of the selected period, its evolution
 * against the previous period and a sparkline of the last periods. Same layout as the Openmost
 * KPI widgets: large centered value, evolution pill below, so both fit side by side in a
 * dashboard.
 */
/* harmony default export */ var KpiCardvue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  name: 'KpiCard',
  props: {
    metricName: {
      type: String,
      default: ''
    },
    // formatted server side (number, money or duration)
    value: {
      type: [String, Number],
      default: ''
    },
    evolution: {
      type: Object,
      default: null
    },
    sparkline: {
      type: Array,
      default: () => []
    },
    sparklineLabels: {
      type: Array,
      default: () => []
    }
  },
  setup(props) {
    const chart = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    const displayValue = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => props.value === '' || props.value === null ? '-' : props.value);
    const trendClass = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      if (!props.evolution || props.evolution.trend === 0) {
        return 'is-flat';
      }
      return props.evolution.trend > 0 ? 'is-up' : 'is-down';
    });
    const arrowClass = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      if (!props.evolution || props.evolution.trend === 0) {
        return '';
      }
      return props.evolution.trend > 0 ? 'icon-arrow-top' : 'icon-arrow-bottom';
    });
    const option = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      const theme = chartTheme();
      return {
        color: [theme.series[0]],
        animation: false,
        tooltip: {
          trigger: 'axis',
          confine: true
        },
        grid: {
          left: 2,
          right: 2,
          top: 4,
          bottom: 2
        },
        xAxis: {
          type: 'category',
          show: false,
          boundaryGap: false,
          data: props.sparklineLabels
        },
        yAxis: {
          type: 'value',
          show: false,
          scale: true
        },
        series: [{
          name: props.metricName,
          type: 'line',
          data: props.sparkline,
          showSymbol: false,
          lineStyle: {
            width: 1.5
          },
          areaStyle: {
            opacity: 0.15
          }
        }]
      };
    });
    useEChart(chart, option);
    return {
      chart,
      displayValue,
      trendClass,
      arrowClass
    };
  }
}));
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Charts/KpiCard.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Charts/KpiCard.vue



KpiCardvue_type_script_lang_ts.render = KpiCardvue_type_template_id_6112b2a6_render

/* harmony default export */ var KpiCard = (KpiCardvue_type_script_lang_ts);
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Explorer/Explorer.vue?vue&type=template&id=720d1144

const Explorervue_type_template_id_720d1144_hoisted_1 = {
  class: "dataExplorer"
};
const Explorervue_type_template_id_720d1144_hoisted_2 = {
  class: "dataExplorer__toolbar"
};
const Explorervue_type_template_id_720d1144_hoisted_3 = ["aria-label"];
const Explorervue_type_template_id_720d1144_hoisted_4 = ["id", "data-tab", "aria-selected", "aria-controls", "onClick"];
const _hoisted_5 = {
  key: 1,
  class: "dataExplorer__reports"
};
const _hoisted_6 = {
  for: "dataExplorerOpenReport",
  class: "dataExplorer__reportsLabel"
};
const _hoisted_7 = ["value"];
const _hoisted_8 = {
  value: ""
};
const _hoisted_9 = ["value"];
const _hoisted_10 = ["disabled"];
const _hoisted_11 = {
  "aria-live": "polite"
};
const _hoisted_12 = ["role", "aria-labelledby"];
const _hoisted_13 = {
  key: 1,
  class: "dataExplorer__columns"
};
const _hoisted_14 = {
  key: 0,
  id: "dataExplorerPanel-sql",
  role: "tabpanel",
  "aria-labelledby": "dataExplorerTab-sql",
  class: "dataExplorer__panel"
};
function Explorervue_type_template_id_720d1144_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Notification = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Notification");
  const _component_ActivityIndicator = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("ActivityIndicator");
  const _component_VariablesPanel = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("VariablesPanel");
  const _component_TabSettings = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("TabSettings");
  const _component_Canvas = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Canvas");
  const _component_RawSqlEditor = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("RawSqlEditor");
  const _component_SaveDialog = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("SaveDialog");
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", Explorervue_type_template_id_720d1144_hoisted_1, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", Explorervue_type_template_id_720d1144_hoisted_2, [_ctx.isSuperUser ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
    key: 0,
    class: "dataExplorer__tabs",
    role: "tablist",
    "aria-label": _ctx.translate('DataExplorer_DataExplorer')
  }, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.tabs, tab => {
    return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("button", {
      key: tab.id,
      id: `dataExplorerTab-${tab.id}`,
      type: "button",
      role: "tab",
      class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["dataExplorer__tab", {
        'is-active': _ctx.activeTab === tab.id
      }]),
      "data-tab": tab.id,
      "aria-selected": _ctx.activeTab === tab.id ? 'true' : 'false',
      "aria-controls": `dataExplorerPanel-${tab.id}`,
      onClick: $event => _ctx.selectTab(tab.id)
    }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(tab.label), 11, Explorervue_type_template_id_720d1144_hoisted_4);
  }), 128))], 8, Explorervue_type_template_id_720d1144_hoisted_3)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.activeTab === 'explore' ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", _hoisted_5, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("label", _hoisted_6, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_OpenReport')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("select", {
    id: "dataExplorerOpenReport",
    class: "browser-default",
    value: _ctx.editing ? String(_ctx.editing.idreport) : '',
    onChange: _cache[0] || (_cache[0] = $event => _ctx.onPickReport($event))
  }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("option", _hoisted_8, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_NewExploration')), 1), (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.openableReports, report => {
    return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("option", {
      key: report.idreport,
      value: String(report.idreport)
    }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(report.name), 9, _hoisted_9);
  }), 128))], 40, _hoisted_7)])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.canSaveAnything && _ctx.activeTab === 'explore' ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("button", {
    key: 2,
    ref: "saveButton",
    type: "button",
    class: "btn dataExplorer__save",
    disabled: !_ctx.isRunnable,
    onClick: _cache[1] || (_cache[1] = $event => _ctx.openSave('visual'))
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.editing && _ctx.editing.editable && _ctx.editing.type === 'visual' ? _ctx.translate('DataExplorer_EditReport') : _ctx.translate('DataExplorer_SaveReport')), 9, _hoisted_10)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_11, [_ctx.notice ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_Notification, {
    key: 0,
    context: _ctx.noticeContext,
    noclear: true
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.notice), 1)]),
    _: 1
  }, 8, ["context"])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", {
    id: "dataExplorerPanel-explore",
    role: _ctx.isSuperUser ? 'tabpanel' : undefined,
    "aria-labelledby": _ctx.isSuperUser ? 'dataExplorerTab-explore' : undefined,
    class: "dataExplorer__panel"
  }, [_ctx.catalogError ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_Notification, {
    key: 0,
    context: "error",
    noclear: true
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.catalogError), 1)]),
    _: 1
  })) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_ActivityIndicator, {
    loading: !_ctx.catalog && !_ctx.catalogError
  }, null, 8, ["loading"]), _ctx.catalog ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", _hoisted_13, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_VariablesPanel, {
    dimensions: _ctx.dimensionViews,
    metrics: _ctx.metricViews,
    segments: _ctx.segmentViews,
    "dimension-groups": _ctx.dimensionGroups,
    "metric-groups": _ctx.metricGroups,
    "segment-groups": _ctx.segmentGroups,
    onAddVariable: _ctx.addVariable,
    onRemoveVariable: _ctx.removeVariable,
    onUse: _ctx.useVariable
  }, null, 8, ["dimensions", "metrics", "segments", "dimension-groups", "metric-groups", "segment-groups", "onAddVariable", "onRemoveVariable", "onUse"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_TabSettings, {
    visualization: _ctx.state.visualization,
    limits: _ctx.catalog.limits,
    "row-items": _ctx.rowItems,
    "column-items": _ctx.columnItems,
    "value-items": _ctx.valueItems,
    "segment-items": _ctx.segmentItems,
    filters: _ctx.filterViews,
    "dimension-options": _ctx.dimensionViews,
    "metric-options": _ctx.metricViews,
    "segment-options": _ctx.segmentViews,
    onSetVisualization: _cache[2] || (_cache[2] = $event => _ctx.state.visualization = $event),
    onAdd: _ctx.addToZone,
    onRemove: _ctx.removeFromZone,
    "filter-match": _ctx.state.filterMatch,
    onSetFilterMatch: _cache[3] || (_cache[3] = $event => _ctx.state.filterMatch = $event),
    onUpdateFilter: _ctx.updateFilter,
    onRemoveFilter: _ctx.removeFilter
  }, null, 8, ["visualization", "limits", "row-items", "column-items", "value-items", "segment-items", "filters", "dimension-options", "metric-options", "segment-options", "onAdd", "onRemove", "filter-match", "onUpdateFilter", "onRemoveFilter"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Canvas, {
    title: _ctx.canvasTitle,
    "period-text": _ctx.periodText,
    "segment-text": _ctx.segmentText,
    onOpenPeriodSelector: _ctx.openPeriodSelector,
    visualization: _ctx.state.visualization,
    loading: _ctx.loading,
    error: _ctx.runError,
    hint: _ctx.hint,
    "dimension-names": _ctx.resultDimensionNames,
    columns: _ctx.resultColumnsList,
    rows: _ctx.resultRows,
    "metric-names": _ctx.metricNames,
    slice: _ctx.slice,
    "bar-series": _ctx.barSeries,
    "slice-metric-name": _ctx.metricNames[_ctx.state.values[0]] || '',
    evolution: _ctx.evolution,
    kpi: _ctx.kpi
  }, null, 8, ["title", "period-text", "segment-text", "onOpenPeriodSelector", "visualization", "loading", "error", "hint", "dimension-names", "columns", "rows", "metric-names", "slice", "bar-series", "slice-metric-name", "evolution", "kpi"])])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)], 8, _hoisted_12), [[external_commonjs_vue_commonjs2_vue_root_Vue_["vShow"], _ctx.activeTab === 'explore']]), _ctx.isSuperUser && _ctx.sqlOpened ? Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])((Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", _hoisted_14, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_RawSqlEditor, {
    modelValue: _ctx.sql,
    "onUpdate:modelValue": _cache[4] || (_cache[4] = $event => _ctx.sql = $event),
    "id-site": _ctx.idSite,
    period: _ctx.period,
    date: _ctx.date,
    "setup-url": _ctx.setupUrl,
    "can-save": true,
    onSave: _cache[5] || (_cache[5] = $event => _ctx.openSave('sql'))
  }, null, 8, ["modelValue", "id-site", "period", "date", "setup-url"])], 512)), [[external_commonjs_vue_commonjs2_vue_root_Vue_["vShow"], _ctx.activeTab === 'sql']]) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.canSaveAnything ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_SaveDialog, {
    key: 1,
    modelValue: _ctx.saveOpen,
    "onUpdate:modelValue": _cache[6] || (_cache[6] = $event => _ctx.saveOpen = $event),
    "id-site": _ctx.idSite,
    "is-super-user": _ctx.isSuperUser,
    "writable-sites": _ctx.writableSites,
    type: _ctx.saveType,
    "report-type": _ctx.saveReportType,
    definition: _ctx.saveDefinition,
    editing: _ctx.editing && _ctx.editing.type === _ctx.saveType ? _ctx.editing : null,
    onSaved: _ctx.onSaved,
    onClosed: _ctx.onSaveClosed
  }, null, 8, ["modelValue", "id-site", "is-super-user", "writable-sites", "type", "report-type", "definition", "editing", "onSaved", "onClosed"])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]);
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/Explorer.vue?vue&type=template&id=720d1144

// EXTERNAL MODULE: external "CoreHome"
var external_CoreHome_ = __webpack_require__("19dc");

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Explorer/Canvas.vue?vue&type=template&id=7878a49d

const Canvasvue_type_template_id_7878a49d_hoisted_1 = ["aria-busy"];
const Canvasvue_type_template_id_7878a49d_hoisted_2 = {
  class: "dataExplorerCanvas__header"
};
const Canvasvue_type_template_id_7878a49d_hoisted_3 = {
  class: "dataExplorerCanvas__tab"
};
const Canvasvue_type_template_id_7878a49d_hoisted_4 = {
  class: "dataExplorerCanvas__title"
};
const Canvasvue_type_template_id_7878a49d_hoisted_5 = {
  key: 0,
  class: "dataExplorerCanvas__context"
};
const Canvasvue_type_template_id_7878a49d_hoisted_6 = ["title"];
const Canvasvue_type_template_id_7878a49d_hoisted_7 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "icon-calendar",
  "aria-hidden": "true"
}, null, -1);
const Canvasvue_type_template_id_7878a49d_hoisted_8 = {
  class: "dataExplorerCanvas__pill"
};
const Canvasvue_type_template_id_7878a49d_hoisted_9 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "icon-segment",
  "aria-hidden": "true"
}, null, -1);
const Canvasvue_type_template_id_7878a49d_hoisted_10 = {
  key: 1,
  class: "dataExplorerCanvas__export"
};
const Canvasvue_type_template_id_7878a49d_hoisted_11 = ["title", "aria-label", "aria-expanded"];
const Canvasvue_type_template_id_7878a49d_hoisted_12 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "icon-download",
  "aria-hidden": "true"
}, null, -1);
const Canvasvue_type_template_id_7878a49d_hoisted_13 = [Canvasvue_type_template_id_7878a49d_hoisted_12];
const Canvasvue_type_template_id_7878a49d_hoisted_14 = ["onClick"];
const _hoisted_15 = {
  class: "dataExplorerCanvas__status",
  "aria-live": "polite"
};
const _hoisted_16 = {
  key: 1,
  class: "dataExplorerCanvas__hint"
};
const _hoisted_17 = {
  class: "dataExplorerCanvas__stage"
};
const _hoisted_18 = {
  key: 0,
  class: "dataTableVizHtmlTable dataExplorerCanvas__table"
};
const _hoisted_19 = {
  key: 0,
  class: "dataTableScroller"
};
const _hoisted_20 = {
  cellspacing: "0",
  class: "dataTable dataExplorerResult"
};
const _hoisted_21 = {
  key: 0
};
const _hoisted_22 = ["colspan"];
const _hoisted_23 = ["colspan"];
const _hoisted_24 = ["aria-sort"];
const _hoisted_25 = ["onClick"];
const _hoisted_26 = ["aria-sort"];
const _hoisted_27 = ["onClick"];
const _hoisted_28 = {
  key: 1,
  class: "dataExplorerCanvas__hint"
};
const _hoisted_29 = {
  key: 1,
  class: "dataExplorerChart"
};
const _hoisted_30 = {
  key: 2,
  class: "dataExplorerChart"
};
const _hoisted_31 = {
  key: 3,
  class: "dataExplorerCanvas__kpi"
};
const _hoisted_32 = {
  class: "dataExplorerCanvas__kpiName"
};
const _hoisted_33 = ["aria-label"];
function Canvasvue_type_template_id_7878a49d_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Notification = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Notification");
  const _component_EChartsBar = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("EChartsBar");
  const _component_EChartsPie = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("EChartsPie");
  const _component_EChartsLine = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("EChartsLine");
  const _component_KpiCard = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("KpiCard");
  const _component_MatomoLoader = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("MatomoLoader");
  const _directive_focus_anywhere_but_here = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveDirective"])("focus-anywhere-but-here");
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
    class: "dataExplorerColumn dataExplorerCanvas",
    "aria-busy": _ctx.loading ? 'true' : 'false'
  }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", Canvasvue_type_template_id_7878a49d_hoisted_2, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", Canvasvue_type_template_id_7878a49d_hoisted_3, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
    class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["dataExplorerCanvas__tabIcon", _ctx.tabIcon]),
    "aria-hidden": "true"
  }, null, 2), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h3", Canvasvue_type_template_id_7878a49d_hoisted_4, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.title), 1)]), _ctx.periodText ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", Canvasvue_type_template_id_7878a49d_hoisted_5, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
    type: "button",
    class: "dataExplorerCanvas__pill dataExplorerCanvas__period",
    title: _ctx.translate('General_ChooseDate', _ctx.periodText),
    onClick: _cache[0] || (_cache[0] = $event => _ctx.$emit('openPeriodSelector'))
  }, [Canvasvue_type_template_id_7878a49d_hoisted_7, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.periodText), 1)], 8, Canvasvue_type_template_id_7878a49d_hoisted_6), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", Canvasvue_type_template_id_7878a49d_hoisted_8, [Canvasvue_type_template_id_7878a49d_hoisted_9, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.segmentText), 1)])])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.canExport ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", Canvasvue_type_template_id_7878a49d_hoisted_10, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
    ref: "exportButton",
    type: "button",
    class: "dataExplorerIconButton dataExplorerCanvas__exportButton",
    title: _ctx.translate('DataExplorer_Export'),
    "aria-label": _ctx.translate('DataExplorer_Export'),
    "aria-haspopup": "menu",
    "aria-expanded": _ctx.exportOpen ? 'true' : 'false',
    onClick: _cache[1] || (_cache[1] = $event => _ctx.exportOpen = !_ctx.exportOpen)
  }, Canvasvue_type_template_id_7878a49d_hoisted_13, 8, Canvasvue_type_template_id_7878a49d_hoisted_11), _ctx.exportOpen ? Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])((Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("ul", {
    key: 0,
    class: "dataExplorerDropZone__menu dataExplorerCanvas__exportMenu",
    role: "menu",
    onKeydown: _cache[2] || (_cache[2] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withKeys"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])($event => _ctx.closeExport(true), ["stop", "prevent"]), ["esc"]))
  }, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.exportFormats, format => {
    return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("li", {
      key: format.id,
      role: "none"
    }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
      type: "button",
      role: "menuitem",
      onClick: $event => _ctx.exportAs(format.id)
    }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(format.label), 9, Canvasvue_type_template_id_7878a49d_hoisted_14)]);
  }), 128))], 32)), [[_directive_focus_anywhere_but_here, {
    blur: _ctx.closeExport
  }]]) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_15, [_ctx.error ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_Notification, {
    key: 0,
    context: "error",
    noclear: true
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.error), 1)]),
    _: 1
  })) : _ctx.hint ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", _hoisted_16, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.hint), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_17, [!_ctx.error && !_ctx.hint ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
    key: 0,
    class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["dataExplorerCanvas__result", {
      'is-stale': _ctx.loading,
      'is-table': _ctx.visualization === 'table'
    }])
  }, [_ctx.visualization === 'table' ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", _hoisted_18, [_ctx.rows.length ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", _hoisted_19, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("table", _hoisted_20, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("thead", null, [_ctx.hasPivot ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("tr", _hoisted_21, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", {
    colspan: _ctx.dimensionNames.length,
    class: "dataExplorerResult__corner"
  }, null, 8, _hoisted_22), (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.pivotGroups, group => {
    return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("th", {
      key: group.key,
      colspan: group.span,
      class: "dataExplorerResult__pivot",
      scope: "colgroup"
    }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(group.label), 9, _hoisted_23);
  }), 128))])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("tr", null, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.dimensionNames, (name, index) => {
    return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("th", {
      key: `d${index}`,
      scope: "col",
      class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["sortable label", {
        first: index === 0,
        columnSorted: _ctx.sortKey === `d${index}`
      }]),
      "aria-sort": _ctx.ariaSort(`d${index}`)
    }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
      type: "button",
      class: "thDIV dataExplorerResult__sort",
      onClick: $event => _ctx.sortBy(`d${index}`)
    }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(name), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
      class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(_ctx.sortIcon(`d${index}`)),
      "aria-hidden": "true"
    }, null, 2)], 8, _hoisted_25)], 10, _hoisted_24);
  }), 128)), (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.columns, column => {
    return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("th", {
      key: column.key,
      scope: "col",
      class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["sortable dataExplorerResult__metric", {
        columnSorted: _ctx.sortKey === column.key
      }]),
      "aria-sort": _ctx.ariaSort(column.key)
    }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
      type: "button",
      class: "thDIV dataExplorerResult__sort",
      onClick: $event => _ctx.sortBy(column.key)
    }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.metricNames[column.metric] || column.metric), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
      class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(_ctx.sortIcon(column.key)),
      "aria-hidden": "true"
    }, null, 2)], 8, _hoisted_27)], 10, _hoisted_26);
  }), 128))])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("tbody", null, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.sortedRows, (row, rowIndex) => {
    return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("tr", {
      key: rowIndex
    }, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(row.labels, (label, index) => {
      return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("td", {
        key: `d${index}`,
        class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["label", {
          first: index === 0
        }])
      }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(label), 3);
    }), 128)), (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.columns, column => {
      return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("td", {
        key: column.key,
        class: "column dataExplorerResult__metric"
      }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.format(row.values[column.key])), 1);
    }), 128))]);
  }), 128))])])])) : !_ctx.loading ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", _hoisted_28, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('CoreHome_ThereIsNoDataForThisReport')), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)])) : (_ctx.visualization === 'bar' || _ctx.visualization === 'pie') && _ctx.slice ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", _hoisted_29, [_ctx.visualization === 'bar' ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_EChartsBar, {
    key: 0,
    labels: _ctx.slice.labels,
    values: _ctx.slice.values,
    "metric-name": _ctx.sliceMetricName,
    series: _ctx.barSeries
  }, null, 8, ["labels", "values", "metric-name", "series"])) : (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_EChartsPie, {
    key: 1,
    labels: _ctx.slice.labels,
    values: _ctx.slice.values,
    "metric-name": _ctx.sliceMetricName
  }, null, 8, ["labels", "values", "metric-name"]))])) : _ctx.visualization === 'line' && _ctx.evolution ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", _hoisted_30, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_EChartsLine, {
    labels: _ctx.evolution.labels,
    series: _ctx.lineSeries
  }, null, 8, ["labels", "series"])])) : _ctx.visualization === 'kpicard' && _ctx.kpi ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", _hoisted_31, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_32, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.kpi.metricName), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_KpiCard, {
    "metric-name": _ctx.kpi.metricName,
    value: _ctx.kpi.value,
    evolution: _ctx.kpi.evolution,
    sparkline: _ctx.kpi.sparkline,
    "sparkline-labels": _ctx.kpi.sparklineLabels
  }, null, 8, ["metric-name", "value", "evolution", "sparkline", "sparkline-labels"])])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)], 2)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.loading ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
    key: 1,
    class: "dataExplorerCanvas__loading",
    role: "status",
    "aria-label": _ctx.translate('General_LoadingData')
  }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_MatomoLoader)], 8, _hoisted_33)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)])], 8, Canvasvue_type_template_id_7878a49d_hoisted_1);
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/Canvas.vue?vue&type=template&id=7878a49d

// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/explorationState.ts
/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
const HASH_PARAM = 'exploration';
const REPORT_HASH_PARAM = 'idReport';
// id of ExploreSubcategory, the reporting page of the explorer
const EXPLORE_SUBCATEGORY = 'DataExplorer_Explore';
const VISUALIZATION_ICONS = {
  table: 'icon-table',
  bar: 'icon-chart-bar',
  pie: 'icon-chart-pie',
  line: 'icon-chart-line',
  kpicard: 'dataExplorerKpiIcon'
};
const VISUALIZATIONS = ['table', 'bar', 'pie', 'line', 'kpicard'];
const FILTER_OPERATORS = ['equals', 'not_equals', 'contains', 'starts_with', 'ends_with', 'regex', 'gt', 'lt'];
// a shared link is user input: bound every list so a crafted hash cannot blow up the page
const MAX_LIST_LENGTH = 100;
function defaultState() {
  return {
    technique: 'freeform',
    variables: {
      dimensions: [],
      metrics: [],
      segments: []
    },
    rows: [],
    column: null,
    values: [],
    filters: [],
    filterMatch: 'all',
    visualization: 'table',
    segment: ''
  };
}
function reportTypeOf(visualization) {
  if (visualization === 'line') {
    return 'evolution';
  }
  if (visualization === 'kpicard') {
    return 'kpicard';
  }
  return 'table';
}
function hasRowDimensions(visualization) {
  return reportTypeOf(visualization) === 'table';
}
// UTF-8 bytes through percent-encoding: the Matomo 5 test runner has no TextEncoder
function toBase64Url(text) {
  const binary = encodeURIComponent(text).replace(/%([0-9A-F]{2})/g, (match, hex) => String.fromCharCode(parseInt(hex, 16)));
  return window.btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}
function fromBase64Url(encoded) {
  const base64 = encoded.replace(/-/g, '+').replace(/_/g, '/');
  const binary = window.atob(base64 + '='.repeat((4 - base64.length % 4) % 4));
  let percentEncoded = '';
  for (let i = 0; i < binary.length; i += 1) {
    percentEncoded += `%${binary.charCodeAt(i).toString(16).padStart(2, '0')}`;
  }
  return decodeURIComponent(percentEncoded);
}
function stringList(value) {
  if (!Array.isArray(value)) {
    return [];
  }
  const result = [];
  value.forEach(item => {
    if (typeof item === 'string' && item !== '' && !result.includes(item)) {
      result.push(item);
    }
  });
  return result.slice(0, MAX_LIST_LENGTH);
}
function filterList(value) {
  if (!Array.isArray(value)) {
    return [];
  }
  const result = [];
  value.forEach(item => {
    if (typeof item !== 'object' || item === null) {
      return;
    }
    const raw = item;
    if (typeof raw.dimension !== 'string' || raw.dimension === '') {
      return;
    }
    const operator = FILTER_OPERATORS.includes(raw.operator) ? raw.operator : 'equals';
    const filterValue = typeof raw.value === 'string' || typeof raw.value === 'number' ? String(raw.value) : '';
    result.push({
      dimension: raw.dimension,
      operator,
      value: filterValue
    });
  });
  return result.slice(0, MAX_LIST_LENGTH);
}
function withVariables(state) {
  const dimensions = [...state.variables.dimensions];
  const used = [...state.rows, ...(state.column ? [state.column] : []), ...state.filters.map(filter => filter.dimension)];
  used.forEach(id => {
    if (!dimensions.includes(id)) {
      dimensions.push(id);
    }
  });
  const metrics = [...state.variables.metrics];
  state.values.forEach(id => {
    if (!metrics.includes(id)) {
      metrics.push(id);
    }
  });
  const segments = [...state.variables.segments];
  if (state.segment !== '' && !segments.includes(state.segment)) {
    segments.push(state.segment);
  }
  return Object.assign(Object.assign({}, state), {}, {
    variables: {
      dimensions,
      metrics,
      segments
    }
  });
}
/**
 * Normalizes any value into a valid exploration: unknown keys dropped, lists deduplicated,
 * every dimension or metric in use also listed in the variables.
 */
function normalize(raw) {
  const state = defaultState();
  if (typeof raw !== 'object' || raw === null) {
    return state;
  }
  const value = raw;
  const variables = typeof value.variables === 'object' && value.variables !== null ? value.variables : {};
  state.variables = {
    dimensions: stringList(variables.dimensions),
    metrics: stringList(variables.metrics),
    segments: stringList(variables.segments)
  };
  state.rows = stringList(value.rows);
  state.column = typeof value.column === 'string' && value.column !== '' ? value.column : null;
  state.values = stringList(value.values);
  state.filters = filterList(value.filters);
  state.filterMatch = value.filterMatch === 'any' ? 'any' : 'all';
  state.visualization = VISUALIZATIONS.includes(value.visualization) ? value.visualization : 'table';
  state.segment = typeof value.segment === 'string' ? value.segment : '';
  return withVariables(state);
}
/**
 * A shared link may hold more row dimensions or metrics than this instance accepts: the extra ones
 * are dropped, the variables lists keep them.
 */
function clampToLimits(state, limits) {
  return Object.assign(Object.assign({}, state), {}, {
    rows: state.rows.slice(0, limits.maxDimensions),
    values: state.values.slice(0, limits.maxMetrics)
  });
}
/**
 * Exploration as the value of the URL hash parameter (base64url of its JSON).
 */
function encode(state) {
  return toBase64Url(JSON.stringify(normalize(state)));
}
/**
 * Exploration of a URL hash parameter value, the default exploration when it cannot be read.
 */
function decode(hash) {
  if (!hash) {
    return defaultState();
  }
  try {
    return normalize(JSON.parse(fromBase64Url(hash)));
  } catch (e) {
    return defaultState();
  }
}
function isFilterComplete(filter) {
  return filter.value.trim() !== '';
}
/**
 * Visual definition sent to DataExplorer.runVisual and saved by addReport / updateReport.
 */
function toDefinition(state) {
  const reportType = reportTypeOf(state.visualization);
  const isTable = reportType === 'table';
  return {
    technique: 'freeform',
    report_type: reportType,
    rows: isTable ? [...state.rows] : [],
    column: isTable ? state.column : null,
    metrics: reportType === 'kpicard' ? state.values.slice(0, 1) : [...state.values],
    filters: state.filters.filter(isFilterComplete).map(filter => Object.assign({}, filter)),
    filter_match: state.filterMatch,
    segment: state.segment
  };
}
/**
 * Exploration of a saved visual definition.
 */
function fromDefinition(definition, reportType) {
  let visualization = 'table';
  if (reportType === 'evolution') {
    visualization = 'line';
  } else if (reportType === 'kpicard') {
    visualization = 'kpicard';
  } else if (definition.chart === 'bar' || definition.chart === 'pie') {
    visualization = definition.chart;
  }
  return normalize({
    rows: definition.rows,
    column: definition.column,
    values: definition.metrics,
    filters: definition.filters,
    filterMatch: definition.filter_match,
    segment: definition.segment,
    visualization
  });
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/exportData.ts
/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
// a spreadsheet runs a cell starting with these as a formula, and a page title is visitor input
function safeText(value) {
  const text = value === null || value === undefined ? '' : String(value);
  return typeof value === 'string' && /^[=+\-@\t\r]/.test(text) ? `'${text}` : text;
}
function csvCell(value) {
  const text = safeText(value);
  return /[",\r\n]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
}
function tsvCell(value) {
  return safeText(value).replace(/[\t\r\n]+/g, ' ');
}
/**
 * The header and the rows as CSV (RFC 4180, comma) or TSV (tab, no quoting: tabs and line breaks
 * inside a value become spaces). Numbers stay raw so spreadsheets read them as numbers.
 */
function toDelimited(header, body, format) {
  const cell = format === 'csv' ? csvCell : tsvCell;
  const separator = format === 'csv' ? ',' : '\t';
  return [header, ...body].map(line => line.map(cell).join(separator)).join('\r\n');
}
/**
 * The result table as header and rows: the dimensions, then one column per metric, "metric | pivot
 * value" for the columns of a pivot.
 */
function tableData(dimensionNames, columns, rows, metricNames, totalsLabel) {
  const hasPivot = columns.some(column => column.pivot !== null);
  const header = [...dimensionNames, ...columns.map(column => {
    const name = metricNames[column.metric] || column.metric;
    if (!hasPivot) {
      return name;
    }
    return `${name} | ${column.pivot === null ? totalsLabel : column.pivot}`;
  })];
  const body = rows.map(row => [...row.labels, ...columns.map(column => row.values[column.key])]);
  return {
    header,
    body
  };
}
/**
 * A file name from the exploration title and the period, without characters file systems refuse.
 */
function exportFileName(title, periodText, extension) {
  const base = `${title} ${periodText}`.replace(/[/\\:*?"<>|]+/g, ' ').replace(/\s+/g, ' ').trim();
  return `${base || 'export'}.${extension}`;
}
function download(content, fileName, format) {
  // the byte order mark makes Excel read the file as UTF-8
  const blob = new Blob([String.fromCharCode(0xfeff), content], {
    type: format === 'csv' ? 'text/csv;charset=utf-8' : 'text/tab-separated-values;charset=utf-8'
  });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = fileName;
  document.body.appendChild(link);
  link.click();
  link.remove();
  setTimeout(() => URL.revokeObjectURL(url), 0);
}
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Explorer/Canvas.vue?vue&type=script&lang=ts








/**
 * Right column: the result of the exploration in the chosen visualization.
 */
/* harmony default export */ var Canvasvue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  name: 'Canvas',
  components: {
    MatomoLoader: external_CoreHome_["MatomoLoader"],
    Notification: external_CoreHome_["Notification"],
    EChartsBar: EChartsBar,
    EChartsLine: EChartsLine,
    EChartsPie: EChartsPie,
    KpiCard: KpiCard
  },
  directives: {
    FocusAnywhereButHere: external_CoreHome_["FocusAnywhereButHere"]
  },
  props: {
    title: {
      type: String,
      required: true
    },
    // the period and segment of the result, the period opens the native selector
    periodText: {
      type: String,
      default: ''
    },
    segmentText: {
      type: String,
      default: ''
    },
    visualization: {
      type: String,
      required: true
    },
    loading: {
      type: Boolean,
      default: false
    },
    error: {
      type: String,
      default: ''
    },
    hint: {
      type: String,
      default: ''
    },
    dimensionNames: {
      type: Array,
      default: () => []
    },
    columns: {
      type: Array,
      default: () => []
    },
    rows: {
      type: Array,
      default: () => []
    },
    metricNames: {
      type: Object,
      default: () => ({})
    },
    slice: {
      type: Object,
      default: null
    },
    sliceMetricName: {
      type: String,
      default: ''
    },
    barSeries: {
      type: Array,
      default: () => []
    },
    evolution: {
      type: Object,
      default: null
    },
    kpi: {
      type: Object,
      default: null
    }
  },
  emits: ['openPeriodSelector'],
  setup(props) {
    // '' keeps the server order (ranked by the first metric)
    const sortKey = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('');
    const sortDescending = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(true);
    const hasPivot = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => props.columns.some(column => column.pivot !== null));
    const pivotGroups = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      const groups = [];
      props.columns.forEach(column => {
        const key = column.pivot === null ? '' : `p${column.pivot}`;
        const last = groups[groups.length - 1];
        if (last && last.key === key) {
          last.span += 1;
        } else {
          groups.push({
            key,
            label: column.pivot === null ? Object(external_CoreHome_["translate"])('DataExplorer_Totals') : column.pivot,
            span: 1
          });
        }
      });
      return groups;
    });
    const sortedRows = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      if (!sortKey.value) {
        return props.rows;
      }
      const key = sortKey.value;
      const direction = sortDescending.value ? -1 : 1;
      const isDimension = /^d\d+$/.test(key) && !props.columns.some(column => column.key === key);
      return [...props.rows].sort((a, b) => {
        if (isDimension) {
          const index = parseInt(key.substring(1), 10);
          return direction * (a.labels[index] || '').localeCompare(b.labels[index] || '');
        }
        return direction * ((a.values[key] || 0) - (b.values[key] || 0));
      });
    });
    function sortBy(key) {
      if (sortKey.value === key) {
        sortDescending.value = !sortDescending.value;
      } else {
        sortKey.value = key;
        sortDescending.value = !/^d\d+$/.test(key);
      }
    }
    function ariaSort(key) {
      if (sortKey.value !== key) {
        return 'none';
      }
      return sortDescending.value ? 'descending' : 'ascending';
    }
    function sortIcon(key) {
      if (sortKey.value !== key) {
        return '';
      }
      return sortDescending.value ? 'icon-arrow-bottom' : 'icon-arrow-top';
    }
    function format(value) {
      if (typeof value !== 'number') {
        return '';
      }
      return external_CoreHome_["NumberFormatter"].formatNumber(value, Number.isInteger(value) ? 0 : 2);
    }
    const lineSeries = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => props.evolution ? props.evolution.series.map(line => ({
      name: props.metricNames[line.metric] || line.metric,
      data: line.data
    })) : []);
    const exportOpen = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(false);
    const exportButton = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    const exportFormats = [{
      id: 'tsv',
      label: 'TSV'
    }, {
      id: 'csv',
      label: 'CSV'
    }, {
      id: 'pdf',
      label: 'PDF'
    }];
    function exportRows() {
      if (props.visualization === 'line' && props.evolution) {
        const series = lineSeries.value;
        return {
          header: [Object(external_CoreHome_["translate"])('General_Date'), ...series.map(line => line.name)],
          body: props.evolution.labels.map((label, index) => [label, ...series.map(line => line.data[index])])
        };
      }
      if (props.visualization === 'kpicard' && props.kpi) {
        return {
          header: [props.kpi.metricName],
          body: [[props.kpi.value]]
        };
      }
      if (!props.rows.length) {
        return null;
      }
      return tableData(props.dimensionNames, props.columns, sortedRows.value, props.metricNames, Object(external_CoreHome_["translate"])('DataExplorer_Totals'));
    }
    const canExport = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => !props.error && !props.hint && exportRows() !== null);
    function closeExport(restoreFocus = false) {
      exportOpen.value = false;
      if (restoreFocus === true && exportButton.value) {
        exportButton.value.focus();
      }
    }
    function printCanvas() {
      const root = document.documentElement;
      const done = () => {
        root.classList.remove('dataExplorer-printing');
        window.removeEventListener('afterprint', done);
      };
      root.classList.add('dataExplorer-printing');
      window.addEventListener('afterprint', done);
      window.print();
    }
    function exportAs(formatId) {
      closeExport(true);
      if (formatId === 'pdf') {
        printCanvas();
        return;
      }
      const data = exportRows();
      if (!data) {
        return;
      }
      const fileFormat = formatId;
      download(toDelimited(data.header, data.body, fileFormat), exportFileName(props.title, props.periodText, fileFormat), fileFormat);
    }
    const tabIcon = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => VISUALIZATION_ICONS[props.visualization]);
    return {
      sortKey,
      exportOpen,
      exportButton,
      exportFormats,
      canExport,
      closeExport,
      exportAs,
      tabIcon,
      hasPivot,
      pivotGroups,
      sortedRows,
      sortBy,
      ariaSort,
      sortIcon,
      format,
      lineSeries
    };
  }
}));
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/Canvas.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/Canvas.vue



Canvasvue_type_script_lang_ts.render = Canvasvue_type_template_id_7878a49d_render

/* harmony default export */ var Canvas = (Canvasvue_type_script_lang_ts);
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Explorer/TabSettings.vue?vue&type=template&id=402c6cdf

const TabSettingsvue_type_template_id_402c6cdf_hoisted_1 = {
  class: "dataExplorerColumn dataExplorerSettings"
};
const TabSettingsvue_type_template_id_402c6cdf_hoisted_2 = {
  class: "dataExplorerColumn__title"
};
const TabSettingsvue_type_template_id_402c6cdf_hoisted_3 = {
  class: "dataExplorerSettings__section"
};
const TabSettingsvue_type_template_id_402c6cdf_hoisted_4 = {
  id: "dataExplorerVisualizationTitle"
};
const TabSettingsvue_type_template_id_402c6cdf_hoisted_5 = {
  class: "dataExplorerVisualizations",
  role: "group",
  "aria-labelledby": "dataExplorerVisualizationTitle"
};
const TabSettingsvue_type_template_id_402c6cdf_hoisted_6 = ["data-visualization", "aria-pressed", "aria-label", "title", "onClick"];
const TabSettingsvue_type_template_id_402c6cdf_hoisted_7 = {
  key: 0,
  class: "dataExplorerSettings__hint"
};
const TabSettingsvue_type_template_id_402c6cdf_hoisted_8 = ["aria-label"];
const TabSettingsvue_type_template_id_402c6cdf_hoisted_9 = ["aria-checked", "onClick"];
const TabSettingsvue_type_template_id_402c6cdf_hoisted_10 = ["aria-label"];
function TabSettingsvue_type_template_id_402c6cdf_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_DropZone = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("DropZone");
  const _component_FilterRow = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("FilterRow");
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", TabSettingsvue_type_template_id_402c6cdf_hoisted_1, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h3", TabSettingsvue_type_template_id_402c6cdf_hoisted_2, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_TabSettings')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", TabSettingsvue_type_template_id_402c6cdf_hoisted_3, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h4", TabSettingsvue_type_template_id_402c6cdf_hoisted_4, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_Visualization')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", TabSettingsvue_type_template_id_402c6cdf_hoisted_5, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.visualizations, option => {
    return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("button", {
      key: option.id,
      type: "button",
      class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["dataExplorerIconButton dataExplorerVisualizations__button", {
        'is-active': option.id === _ctx.visualization
      }]),
      "data-visualization": option.id,
      "aria-pressed": option.id === _ctx.visualization ? 'true' : 'false',
      "aria-label": option.label,
      title: option.label,
      onClick: $event => _ctx.$emit('setVisualization', option.id)
    }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
      class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(option.icon),
      "aria-hidden": "true"
    }, null, 2)], 10, TabSettingsvue_type_template_id_402c6cdf_hoisted_6);
  }), 128))])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_DropZone, {
    zone: "segment",
    accepts: "segment",
    title: _ctx.translate('DataExplorer_SegmentComparison'),
    items: _ctx.segmentItems,
    options: _ctx.segmentOptions,
    max: 1,
    placeholder: _ctx.translate('DataExplorer_DropSegment'),
    "empty-menu-text": _ctx.translate('DataExplorer_NoSegments'),
    onAdd: _cache[0] || (_cache[0] = $event => _ctx.$emit('add', 'segment', $event)),
    onRemove: _cache[1] || (_cache[1] = $event => _ctx.$emit('remove', 'segment', $event))
  }, null, 8, ["title", "items", "options", "placeholder", "empty-menu-text"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_DropZone, {
    zone: "rows",
    accepts: "dimension",
    title: _ctx.translate('DataExplorer_Rows'),
    items: _ctx.rowItems,
    options: _ctx.dimensionOptions,
    max: _ctx.limits.maxDimensions,
    disabled: !_ctx.hasRows,
    "disabled-reason": _ctx.translate('DataExplorer_RowsNotUsedByVisualization'),
    placeholder: _ctx.translate('DataExplorer_DropDimension'),
    "empty-menu-text": _ctx.translate('DataExplorer_NoDimensions'),
    onAdd: _cache[2] || (_cache[2] = $event => _ctx.$emit('add', 'rows', $event)),
    onRemove: _cache[3] || (_cache[3] = $event => _ctx.$emit('remove', 'rows', $event))
  }, null, 8, ["title", "items", "options", "max", "disabled", "disabled-reason", "placeholder", "empty-menu-text"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_DropZone, {
    zone: "column",
    accepts: "dimension",
    title: _ctx.translate('DataExplorer_Columns'),
    items: _ctx.columnItems,
    options: _ctx.dimensionOptions,
    max: _ctx.limits.maxColumns,
    disabled: !_ctx.hasRows,
    "disabled-reason": _ctx.translate('DataExplorer_RowsNotUsedByVisualization'),
    placeholder: _ctx.translate('DataExplorer_DropDimension'),
    "empty-menu-text": _ctx.translate('DataExplorer_NoDimensions'),
    onAdd: _cache[4] || (_cache[4] = $event => _ctx.$emit('add', 'column', $event)),
    onRemove: _cache[5] || (_cache[5] = $event => _ctx.$emit('remove', 'column', $event))
  }, null, 8, ["title", "items", "options", "max", "disabled", "disabled-reason", "placeholder", "empty-menu-text"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_DropZone, {
    zone: "values",
    accepts: "metric",
    title: _ctx.translate('DataExplorer_Values'),
    items: _ctx.valueItems,
    options: _ctx.metricOptions,
    max: _ctx.maxValues,
    "active-count": _ctx.activeValues,
    "inactive-reason": _ctx.inactiveValueReason,
    placeholder: _ctx.translate('DataExplorer_DropMetric'),
    "empty-menu-text": _ctx.translate('DataExplorer_NoMetrics'),
    onAdd: _cache[6] || (_cache[6] = $event => _ctx.$emit('add', 'values', $event)),
    onRemove: _cache[7] || (_cache[7] = $event => _ctx.$emit('remove', 'values', $event))
  }, null, 8, ["title", "items", "options", "max", "active-count", "inactive-reason", "placeholder", "empty-menu-text"]), _ctx.inactiveValueReason && _ctx.valueItems.length > 1 ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", TabSettingsvue_type_template_id_402c6cdf_hoisted_7, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.inactiveValueReason), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_DropZone, {
    zone: "filters",
    accepts: "dimension",
    title: _ctx.translate('DataExplorer_Filters'),
    options: _ctx.dimensionOptions,
    unlimited: "",
    placeholder: _ctx.translate('DataExplorer_DropFilter'),
    "empty-menu-text": _ctx.translate('DataExplorer_NoDimensions'),
    onAdd: _cache[8] || (_cache[8] = $event => _ctx.$emit('add', 'filters', $event))
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [_ctx.filters.length > 1 ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
      key: 0,
      class: "dataExplorerSegmented dataExplorerFilterMatch",
      role: "radiogroup",
      "aria-label": _ctx.translate('DataExplorer_FilterMatchLabel')
    }, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.filterMatches, match => {
      return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("button", {
        key: match.id,
        type: "button",
        role: "radio",
        class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["dataExplorerSegmented__button", {
          'is-active': _ctx.filterMatch === match.id
        }]),
        "aria-checked": _ctx.filterMatch === match.id ? 'true' : 'false',
        onClick: $event => _ctx.$emit('setFilterMatch', match.id)
      }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(match.label), 11, TabSettingsvue_type_template_id_402c6cdf_hoisted_9);
    }), 128))], 8, TabSettingsvue_type_template_id_402c6cdf_hoisted_8)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.filters.length ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("ul", {
      key: 1,
      class: "dataExplorerFilters",
      "aria-label": _ctx.translate('DataExplorer_Filters')
    }, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.filters, (filter, index) => {
      return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_FilterRow, {
        key: `${index}-${filter.filter.dimension}`,
        filter: filter.filter,
        "dimension-name": filter.name,
        reason: filter.reason,
        onUpdate: $event => _ctx.$emit('updateFilter', index, $event),
        onRemove: $event => _ctx.$emit('removeFilter', index)
      }, null, 8, ["filter", "dimension-name", "reason", "onUpdate", "onRemove"]);
    }), 128))], 8, TabSettingsvue_type_template_id_402c6cdf_hoisted_10)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]),
    _: 1
  }, 8, ["title", "options", "placeholder", "empty-menu-text"])]);
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/TabSettings.vue?vue&type=template&id=402c6cdf

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Explorer/DropZone.vue?vue&type=template&id=65f87e3e

const DropZonevue_type_template_id_65f87e3e_hoisted_1 = ["data-zone", "data-kind"];
const DropZonevue_type_template_id_65f87e3e_hoisted_2 = {
  class: "dataExplorerDropZone__header"
};
const DropZonevue_type_template_id_65f87e3e_hoisted_3 = ["id"];
const DropZonevue_type_template_id_65f87e3e_hoisted_4 = {
  key: 0,
  class: "dataExplorerDropZone__count"
};
const DropZonevue_type_template_id_65f87e3e_hoisted_5 = {
  key: 0,
  class: "dataExplorerDropZone__hint"
};
const DropZonevue_type_template_id_65f87e3e_hoisted_6 = ["aria-labelledby"];
const DropZonevue_type_template_id_65f87e3e_hoisted_7 = ["data-id", "title", "onKeydown"];
const DropZonevue_type_template_id_65f87e3e_hoisted_8 = {
  key: 0,
  class: "icon-warning",
  "aria-hidden": "true"
};
const DropZonevue_type_template_id_65f87e3e_hoisted_9 = {
  class: "dataExplorerChip__name"
};
const DropZonevue_type_template_id_65f87e3e_hoisted_10 = {
  key: 1,
  class: "dataExplorerVisuallyHidden"
};
const DropZonevue_type_template_id_65f87e3e_hoisted_11 = ["aria-label", "title", "onClick"];
const DropZonevue_type_template_id_65f87e3e_hoisted_12 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "icon-close",
  "aria-hidden": "true"
}, null, -1);
const DropZonevue_type_template_id_65f87e3e_hoisted_13 = [DropZonevue_type_template_id_65f87e3e_hoisted_12];
const DropZonevue_type_template_id_65f87e3e_hoisted_14 = {
  key: 1,
  class: "dataExplorerDropZone__slot"
};
const DropZonevue_type_template_id_65f87e3e_hoisted_15 = ["aria-expanded"];
const DropZonevue_type_template_id_65f87e3e_hoisted_16 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "icon-add",
  "aria-hidden": "true"
}, null, -1);
const DropZonevue_type_template_id_65f87e3e_hoisted_17 = ["disabled", "title", "onClick"];
const DropZonevue_type_template_id_65f87e3e_hoisted_18 = {
  key: 0,
  class: "dataExplorerDropZone__menuEmpty"
};
function DropZonevue_type_template_id_65f87e3e_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _directive_focus_anywhere_but_here = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveDirective"])("focus-anywhere-but-here");
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
    class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["dataExplorerDropZone", {
      'is-over': _ctx.isOver,
      'is-accepting': _ctx.isAccepting,
      'is-full': _ctx.isFull,
      'is-disabled': _ctx.disabled
    }]),
    "data-zone": _ctx.zone,
    "data-kind": _ctx.accepts,
    onDragover: _cache[2] || (_cache[2] = (...args) => _ctx.onDragOver && _ctx.onDragOver(...args)),
    onDragenter: _cache[3] || (_cache[3] = (...args) => _ctx.onDragOver && _ctx.onDragOver(...args)),
    onDragleave: _cache[4] || (_cache[4] = $event => _ctx.isOver = false),
    onDrop: _cache[5] || (_cache[5] = (...args) => _ctx.onDrop && _ctx.onDrop(...args))
  }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", DropZonevue_type_template_id_65f87e3e_hoisted_2, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h4", {
    id: _ctx.headingId
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.title), 9, DropZonevue_type_template_id_65f87e3e_hoisted_3), _ctx.max > 1 ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("span", DropZonevue_type_template_id_65f87e3e_hoisted_4, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_ZoneCount', _ctx.items.length, _ctx.max)), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), _ctx.disabled && _ctx.disabledReason ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", DropZonevue_type_template_id_65f87e3e_hoisted_5, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.disabledReason), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("ul", {
    class: "dataExplorerDropZone__chips",
    "aria-labelledby": _ctx.headingId
  }, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.items, (item, index) => {
    return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("li", {
      key: item.id,
      ref_for: true,
      ref: "chips",
      class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["dataExplorerChip", {
        'is-incompatible': !!item.reason,
        'is-inactive': _ctx.isInactive(index)
      }]),
      "data-id": item.id,
      tabindex: "0",
      title: item.reason || (_ctx.isInactive(index) ? _ctx.inactiveReason : _ctx.translate('DataExplorer_ChipRemoveHint')),
      onKeydown: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withKeys"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])($event => _ctx.remove(item.id, index), ["self", "prevent"]), ["delete"])
    }, [item.reason ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("span", DropZonevue_type_template_id_65f87e3e_hoisted_8)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", DropZonevue_type_template_id_65f87e3e_hoisted_9, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(item.name), 1), item.reason ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("span", DropZonevue_type_template_id_65f87e3e_hoisted_10, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(item.reason), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
      type: "button",
      class: "dataExplorerIconButton dataExplorerChip__remove",
      "aria-label": _ctx.translate('DataExplorer_RemoveFromZone', item.name, _ctx.title),
      title: _ctx.translate('DataExplorer_RemoveFromZone', item.name, _ctx.title),
      onClick: $event => _ctx.remove(item.id, index)
    }, DropZonevue_type_template_id_65f87e3e_hoisted_13, 8, DropZonevue_type_template_id_65f87e3e_hoisted_11)], 42, DropZonevue_type_template_id_65f87e3e_hoisted_7);
  }), 128))], 8, DropZonevue_type_template_id_65f87e3e_hoisted_6), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderSlot"])(_ctx.$slots, "default"), !_ctx.isFull && !_ctx.disabled ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", DropZonevue_type_template_id_65f87e3e_hoisted_14, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
    ref: "slotButton",
    type: "button",
    class: "dataExplorerDropZone__select",
    "aria-expanded": _ctx.menuOpen ? 'true' : 'false',
    onClick: _cache[0] || (_cache[0] = $event => _ctx.menuOpen = !_ctx.menuOpen)
  }, [DropZonevue_type_template_id_65f87e3e_hoisted_16, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.placeholder), 1)], 8, DropZonevue_type_template_id_65f87e3e_hoisted_15), _ctx.menuOpen ? Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])((Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("ul", {
    key: 0,
    class: "dataExplorerDropZone__menu",
    onKeydown: _cache[1] || (_cache[1] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withKeys"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])($event => _ctx.closeMenu(true), ["stop", "prevent"]), ["esc"]))
  }, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.options, option => {
    return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("li", {
      key: option.id
    }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
      type: "button",
      disabled: !!option.reason,
      title: option.reason,
      onClick: $event => _ctx.choose(option.id)
    }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(option.name), 9, DropZonevue_type_template_id_65f87e3e_hoisted_17)]);
  }), 128)), !_ctx.options.length ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("li", DropZonevue_type_template_id_65f87e3e_hoisted_18, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.emptyMenuText), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)], 32)), [[_directive_focus_anywhere_but_here, {
    blur: _ctx.closeMenu
  }]]) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)], 42, DropZonevue_type_template_id_65f87e3e_hoisted_1);
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/DropZone.vue?vue&type=template&id=65f87e3e

// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/dragState.ts
/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

const DRAG_MIME = 'application/x-dataexplorer-variable';
// browsers hide dataTransfer data until the drop: drop zones read the dragged kind from here to
// highlight while the pointer is over them
const dragState = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["reactive"])({
  current: null
});
function startDrag(event, payload) {
  dragState.current = payload;
  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'copy';
    event.dataTransfer.setData(DRAG_MIME, JSON.stringify(payload));
    event.dataTransfer.setData('text/plain', payload.id);
  }
}
function endDrag() {
  dragState.current = null;
}
function droppedPayload(event) {
  const raw = event.dataTransfer ? event.dataTransfer.getData(DRAG_MIME) : '';
  if (raw) {
    try {
      const payload = JSON.parse(raw);
      if (payload && typeof payload.id === 'string' && typeof payload.kind === 'string') {
        return payload;
      }
    } catch (e) {
      // not one of ours
    }
  }
  return dragState.current;
}
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Explorer/DropZone.vue?vue&type=script&lang=ts



/**
 * A drop zone of the tab settings (Rows, Columns, Values, Filters, Segment comparison): accepts
 * variables of one kind dragged from the variables column, or picked with the "select" button,
 * shows them as chips removable with their button or with Delete / Backspace.
 */
/* harmony default export */ var DropZonevue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  name: 'DropZone',
  props: {
    zone: {
      type: String,
      required: true
    },
    title: {
      type: String,
      required: true
    },
    accepts: {
      type: String,
      required: true
    },
    items: {
      type: Array,
      default: () => []
    },
    // variables of the accepted kind that can be picked with the select button
    options: {
      type: Array,
      default: () => []
    },
    max: {
      type: Number,
      default: 0
    },
    disabled: {
      type: Boolean,
      default: false
    },
    disabledReason: {
      type: String,
      default: ''
    },
    placeholder: {
      type: String,
      required: true
    },
    emptyMenuText: {
      type: String,
      required: true
    },
    // items after the first activeCount ones are kept but not shown by the visualization, 0 for all
    activeCount: {
      type: Number,
      default: 0
    },
    inactiveReason: {
      type: String,
      default: ''
    },
    // a zone that lists its items elsewhere (filters) keeps accepting
    unlimited: {
      type: Boolean,
      default: false
    }
  },
  emits: ['add', 'remove'],
  directives: {
    FocusAnywhereButHere: external_CoreHome_["FocusAnywhereButHere"]
  },
  setup(props, {
    emit
  }) {
    const isOver = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(false);
    const menuOpen = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(false);
    const chips = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])([]);
    const slotButton = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    const headingId = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => `dataExplorerZone-${props.zone}`);
    const isFull = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => !props.unlimited && props.max > 0 && props.items.length >= props.max);
    function isInactive(index) {
      return props.activeCount > 0 && index >= props.activeCount;
    }
    const isAccepting = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => !!dragState.current && dragState.current.kind === props.accepts && !props.disabled && !isFull.value);
    function onDragOver(event) {
      if (!isAccepting.value) {
        return;
      }
      event.preventDefault();
      if (event.dataTransfer) {
        event.dataTransfer.dropEffect = 'copy';
      }
      isOver.value = true;
    }
    function onDrop(event) {
      isOver.value = false;
      const payload = droppedPayload(event);
      endDrag();
      if (!payload || payload.kind !== props.accepts || props.disabled || isFull.value) {
        return;
      }
      event.preventDefault();
      emit('add', payload.id);
    }
    function focusAfterRemoval(index) {
      Object(external_commonjs_vue_commonjs2_vue_root_Vue_["nextTick"])(() => {
        const remaining = chips.value.filter(chip => !!chip && chip.isConnected);
        const next = remaining[Math.min(index, remaining.length - 1)];
        if (next) {
          next.focus();
        } else if (slotButton.value) {
          slotButton.value.focus();
        }
      });
    }
    function remove(id, index) {
      emit('remove', id);
      focusAfterRemoval(index);
    }
    function closeMenu(restoreFocus = false) {
      menuOpen.value = false;
      if (restoreFocus === true && slotButton.value) {
        slotButton.value.focus();
      }
    }
    function choose(id) {
      menuOpen.value = false;
      emit('add', id);
    }
    return {
      isOver,
      menuOpen,
      chips,
      slotButton,
      headingId,
      isFull,
      isInactive,
      isAccepting,
      onDragOver,
      onDrop,
      remove,
      closeMenu,
      choose
    };
  }
}));
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/DropZone.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/DropZone.vue



DropZonevue_type_script_lang_ts.render = DropZonevue_type_template_id_65f87e3e_render

/* harmony default export */ var DropZone = (DropZonevue_type_script_lang_ts);
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Explorer/FilterRow.vue?vue&type=template&id=620333d2

const FilterRowvue_type_template_id_620333d2_hoisted_1 = ["data-dimension"];
const FilterRowvue_type_template_id_620333d2_hoisted_2 = {
  class: "dataExplorerFilter__header"
};
const FilterRowvue_type_template_id_620333d2_hoisted_3 = ["title", "aria-expanded"];
const FilterRowvue_type_template_id_620333d2_hoisted_4 = {
  key: 0,
  class: "icon-warning",
  "aria-hidden": "true"
};
const FilterRowvue_type_template_id_620333d2_hoisted_5 = {
  class: "dataExplorerFilter__name"
};
const FilterRowvue_type_template_id_620333d2_hoisted_6 = {
  key: 1,
  class: "dataExplorerFilter__condition"
};
const FilterRowvue_type_template_id_620333d2_hoisted_7 = ["aria-label", "title"];
const FilterRowvue_type_template_id_620333d2_hoisted_8 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "icon-close",
  "aria-hidden": "true"
}, null, -1);
const FilterRowvue_type_template_id_620333d2_hoisted_9 = [FilterRowvue_type_template_id_620333d2_hoisted_8];
const FilterRowvue_type_template_id_620333d2_hoisted_10 = {
  key: 0,
  class: "dataExplorerFilter__reason"
};
const FilterRowvue_type_template_id_620333d2_hoisted_11 = {
  class: "dataExplorerFilter__label"
};
const FilterRowvue_type_template_id_620333d2_hoisted_12 = {
  class: "dataExplorerFilter__actions"
};
const FilterRowvue_type_template_id_620333d2_hoisted_13 = ["disabled"];
function FilterRowvue_type_template_id_620333d2_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Field = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Field");
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("li", {
    class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["dataExplorerFilter", {
      'is-incompatible': !!_ctx.reason,
      'is-editing': _ctx.editing
    }]),
    "data-dimension": _ctx.filter.dimension
  }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", FilterRowvue_type_template_id_620333d2_hoisted_2, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
    type: "button",
    class: "dataExplorerFilter__summary",
    title: _ctx.reason || _ctx.translate('DataExplorer_FilterEdit', _ctx.dimensionName),
    "aria-expanded": _ctx.editing ? 'true' : 'false',
    onClick: _cache[0] || (_cache[0] = (...args) => _ctx.toggle && _ctx.toggle(...args))
  }, [_ctx.reason ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("span", FilterRowvue_type_template_id_620333d2_hoisted_4)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", FilterRowvue_type_template_id_620333d2_hoisted_5, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.dimensionName), 1), _ctx.filter.value ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("span", FilterRowvue_type_template_id_620333d2_hoisted_6, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.operatorLabel(_ctx.filter.operator)) + " " + Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.filter.value), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)], 8, FilterRowvue_type_template_id_620333d2_hoisted_3), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
    type: "button",
    class: "dataExplorerIconButton",
    "aria-label": _ctx.translate('DataExplorer_RemoveFilter', _ctx.dimensionName),
    title: _ctx.translate('DataExplorer_RemoveFilter', _ctx.dimensionName),
    onClick: _cache[1] || (_cache[1] = $event => _ctx.$emit('remove'))
  }, FilterRowvue_type_template_id_620333d2_hoisted_9, 8, FilterRowvue_type_template_id_620333d2_hoisted_7)]), _ctx.reason ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", FilterRowvue_type_template_id_620333d2_hoisted_10, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.reason), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.editing ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
    key: 1,
    class: "dataExplorerFilter__fields",
    onKeydown: [_cache[6] || (_cache[6] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withKeys"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])((...args) => _ctx.apply && _ctx.apply(...args), ["prevent"]), ["enter"])), _cache[7] || (_cache[7] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withKeys"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])((...args) => _ctx.cancel && _ctx.cancel(...args), ["stop", "prevent"]), ["esc"]))]
  }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", FilterRowvue_type_template_id_620333d2_hoisted_11, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_FilterConditions')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
    uicontrol: "select",
    name: `${_ctx.uid}-operator`,
    class: "dataExplorerFilter__operator",
    "full-width": true,
    options: _ctx.operatorOptions,
    "model-value": _ctx.draft.operator,
    "ui-control-attributes": {
      'aria-label': _ctx.translate('DataExplorer_FilterOperatorLabel', _ctx.dimensionName)
    },
    "onUpdate:modelValue": _cache[2] || (_cache[2] = $event => _ctx.draft.operator = $event)
  }, null, 8, ["name", "options", "model-value", "ui-control-attributes"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
    uicontrol: "text",
    name: `${_ctx.uid}-value`,
    class: "dataExplorerFilter__value",
    "full-width": true,
    placeholder: _ctx.translate('DataExplorer_FilterValuePlaceholder'),
    "model-value": _ctx.draft.value,
    "ui-control-attributes": {
      'aria-label': _ctx.translate('DataExplorer_FilterValueLabel', _ctx.dimensionName),
      inputmode: _ctx.isNumeric ? 'decimal' : 'text'
    },
    "onUpdate:modelValue": _cache[3] || (_cache[3] = $event => _ctx.draft.value = String($event))
  }, null, 8, ["name", "placeholder", "model-value", "ui-control-attributes"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", FilterRowvue_type_template_id_620333d2_hoisted_12, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
    type: "button",
    class: "btn-flat",
    onClick: _cache[4] || (_cache[4] = (...args) => _ctx.cancel && _ctx.cancel(...args))
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Cancel')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
    type: "button",
    class: "btn",
    disabled: !_ctx.draft.value.trim(),
    onClick: _cache[5] || (_cache[5] = (...args) => _ctx.apply && _ctx.apply(...args))
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_FilterApply')), 9, FilterRowvue_type_template_id_620333d2_hoisted_13)])], 32)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)], 10, FilterRowvue_type_template_id_620333d2_hoisted_1);
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/FilterRow.vue?vue&type=template&id=620333d2

// EXTERNAL MODULE: external "CorePluginsAdmin"
var external_CorePluginsAdmin_ = __webpack_require__("a5a2");

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Explorer/FilterRow.vue?vue&type=script&lang=ts




let nextId = 0;
/**
 * One filter of the Filters zone, like GA4: a summary line ("Device type is exactly Smartphone")
 * that opens the condition, applied with Apply, so the result does not run on every keystroke.
 * A new filter opens directly; cancelling it before any Apply removes it.
 */
/* harmony default export */ var FilterRowvue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  name: 'FilterRow',
  components: {
    Field: external_CorePluginsAdmin_["Field"]
  },
  props: {
    filter: {
      type: Object,
      required: true
    },
    dimensionName: {
      type: String,
      required: true
    },
    reason: {
      type: String,
      default: ''
    }
  },
  emits: ['update', 'remove'],
  setup(props, {
    emit
  }) {
    nextId += 1;
    const uid = `dataExplorerFilter${nextId}`;
    const editing = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(props.filter.value.trim() === '');
    const draft = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["reactive"])({
      operator: props.filter.operator,
      value: props.filter.value
    });
    const isNumeric = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => ['gt', 'lt'].includes(draft.operator));
    function operatorLabel(operator) {
      return Object(external_CoreHome_["translate"])(`DataExplorer_FilterOperator_${operator}`);
    }
    function toggle() {
      if (editing.value) {
        editing.value = false;
        return;
      }
      draft.operator = props.filter.operator;
      draft.value = props.filter.value;
      editing.value = true;
    }
    function apply() {
      if (!draft.value.trim()) {
        return;
      }
      emit('update', Object.assign(Object.assign({}, props.filter), {}, {
        operator: draft.operator,
        value: draft.value
      }));
      editing.value = false;
    }
    function cancel() {
      if (props.filter.value.trim() === '') {
        emit('remove');
        return;
      }
      draft.operator = props.filter.operator;
      draft.value = props.filter.value;
      editing.value = false;
    }
    return {
      uid,
      editing,
      draft,
      isNumeric,
      operatorOptions: FILTER_OPERATORS.map(operator => ({
        key: operator,
        value: operatorLabel(operator)
      })),
      operatorLabel,
      toggle,
      apply,
      cancel
    };
  }
}));
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/FilterRow.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/FilterRow.vue



FilterRowvue_type_script_lang_ts.render = FilterRowvue_type_template_id_620333d2_render

/* harmony default export */ var FilterRow = (FilterRowvue_type_script_lang_ts);
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Explorer/TabSettings.vue?vue&type=script&lang=ts





/**
 * Middle column: visualization switcher and the drop zones of the exploration.
 */
/* harmony default export */ var TabSettingsvue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  name: 'TabSettings',
  components: {
    DropZone: DropZone,
    FilterRow: FilterRow
  },
  props: {
    visualization: {
      type: String,
      required: true
    },
    limits: {
      type: Object,
      required: true
    },
    rowItems: {
      type: Array,
      required: true
    },
    columnItems: {
      type: Array,
      required: true
    },
    valueItems: {
      type: Array,
      required: true
    },
    segmentItems: {
      type: Array,
      required: true
    },
    filters: {
      type: Array,
      required: true
    },
    filterMatch: {
      type: String,
      default: 'all'
    },
    dimensionOptions: {
      type: Array,
      required: true
    },
    metricOptions: {
      type: Array,
      required: true
    },
    segmentOptions: {
      type: Array,
      required: true
    }
  },
  emits: ['setVisualization', 'add', 'remove', 'updateFilter', 'removeFilter', 'setFilterMatch'],
  setup(props) {
    const visualizations = VISUALIZATIONS.map(id => ({
      id,
      icon: VISUALIZATION_ICONS[id],
      label: Object(external_CoreHome_["translate"])(`DataExplorer_Visualization_${id}`)
    }));
    const hasRows = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => hasRowDimensions(props.visualization));
    // the pie and the KPI card show one metric: one more can't be added, those already there stay
    // (hatched) for the visualizations that show them all
    const singleValue = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => props.visualization === 'pie' || props.visualization === 'kpicard');
    const maxValues = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => singleValue.value ? 1 : props.limits.maxMetrics);
    const activeValues = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => singleValue.value ? 1 : 0);
    const inactiveValueReason = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      if (props.visualization === 'pie') {
        return Object(external_CoreHome_["translate"])('DataExplorer_PieFirstMetricOnly');
      }
      return props.visualization === 'kpicard' ? Object(external_CoreHome_["translate"])('DataExplorer_KpiFirstMetricOnly') : '';
    });
    const filterMatches = [{
      id: 'all',
      label: Object(external_CoreHome_["translate"])('DataExplorer_FilterMatchAll')
    }, {
      id: 'any',
      label: Object(external_CoreHome_["translate"])('DataExplorer_FilterMatchAny')
    }];
    return {
      filterMatches,
      visualizations,
      hasRows,
      maxValues,
      activeValues,
      inactiveValueReason
    };
  }
}));
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/TabSettings.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/TabSettings.vue



TabSettingsvue_type_script_lang_ts.render = TabSettingsvue_type_template_id_402c6cdf_render

/* harmony default export */ var TabSettings = (TabSettingsvue_type_script_lang_ts);
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Explorer/VariablesPanel.vue?vue&type=template&id=1bf0c0b0

const VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_1 = {
  class: "dataExplorerColumn dataExplorerVariables"
};
const VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_2 = {
  class: "dataExplorerColumn__title"
};
const VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_3 = ["data-kind"];
const VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_4 = {
  class: "dataExplorerVariables__header"
};
const VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_5 = ["id"];
const VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_6 = ["aria-label", "title", "aria-expanded", "onClick"];
const VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_7 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "icon-add",
  "aria-hidden": "true"
}, null, -1);
const VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_8 = [VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_7];
const VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_9 = ["aria-labelledby"];
const VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_10 = ["data-id", "title", "aria-description", "onDragstart", "onDblclick", "onKeydown"];
const VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_11 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "dataExplorerGrip",
  "aria-hidden": "true"
}, null, -1);
const VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_12 = {
  class: "dataExplorerVariable__name"
};
const VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_13 = ["aria-label", "title", "onClick"];
const VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_14 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "icon-close",
  "aria-hidden": "true"
}, null, -1);
const VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_15 = [VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_14];
const VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_16 = {
  key: 0,
  class: "dataExplorerVariables__empty"
};
function VariablesPanelvue_type_template_id_1bf0c0b0_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_CataloguePanel = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("CataloguePanel");
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_1, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h3", VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_2, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_Variables')), 1), (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.sections, section => {
    return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("section", {
      key: section.kind,
      class: "dataExplorerVariables__section",
      "data-kind": section.kind
    }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_4, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h4", {
      id: `dataExplorerVariables-${section.kind}`
    }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(section.title), 9, VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_5), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
      type: "button",
      class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["dataExplorerIconButton", `dataExplorerVariables__add-${section.kind}`]),
      "aria-label": section.addLabel,
      title: section.addLabel,
      "aria-haspopup": "dialog",
      "aria-expanded": _ctx.isOpen(section.kind) ? 'true' : 'false',
      onClick: $event => _ctx.open(section.kind, $event)
    }, VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_8, 10, VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_6)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("ul", {
      class: "dataExplorerVariables__list",
      "aria-labelledby": `dataExplorerVariables-${section.kind}`
    }, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(section.items, item => {
      return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("li", {
        key: item.id,
        class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["dataExplorerVariable", {
          'is-incompatible': !!item.reason,
          'is-used': item.used
        }]),
        "data-id": item.id,
        draggable: "true",
        tabindex: "0",
        title: item.reason || section.useHint,
        "aria-description": item.reason || section.useHint,
        onDragstart: $event => _ctx.onDragStart($event, section.kind, item.id),
        onDragend: _cache[0] || (_cache[0] = (...args) => _ctx.onDragEnd && _ctx.onDragEnd(...args)),
        onDblclick: $event => _ctx.$emit('use', section.kind, item.id),
        onKeydown: [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withKeys"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])($event => _ctx.$emit('use', section.kind, item.id), ["self", "prevent"]), ["enter"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withKeys"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])($event => _ctx.$emit('use', section.kind, item.id), ["self", "prevent"]), ["space"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withKeys"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])($event => _ctx.$emit('removeVariable', section.kind, item.id), ["self", "prevent"]), ["delete"])]
      }, [VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_11, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_12, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(item.name), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
        type: "button",
        class: "dataExplorerIconButton dataExplorerVariable__remove",
        "aria-label": _ctx.translate('DataExplorer_RemoveVariable', item.name),
        title: _ctx.translate('DataExplorer_RemoveVariable', item.name),
        onClick: $event => _ctx.$emit('removeVariable', section.kind, item.id)
      }, VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_15, 8, VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_13)], 42, VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_10);
    }), 128)), !section.items.length ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("li", VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_16, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(section.emptyText), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)], 8, VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_9)], 8, VariablesPanelvue_type_template_id_1bf0c0b0_hoisted_3);
  }), 128)), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_CataloguePanel, {
    title: _ctx.activeSection ? _ctx.activeSection.addLabel : '',
    groups: _ctx.activeSection ? _ctx.activeSection.groups : [],
    added: _ctx.activeSection ? _ctx.activeSection.added : [],
    "empty-text": _ctx.activeSection ? _ctx.activeSection.catalogueEmptyText : '',
    onAdd: _cache[1] || (_cache[1] = $event => _ctx.activeSection && _ctx.$emit('addVariable', _ctx.activeSection.kind, $event)),
    onRemove: _cache[2] || (_cache[2] = $event => _ctx.activeSection && _ctx.$emit('removeVariable', _ctx.activeSection.kind, $event))
  }, null, 8, ["title", "groups", "added", "empty-text"])]);
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/VariablesPanel.vue?vue&type=template&id=1bf0c0b0

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Explorer/CataloguePanel.vue?vue&type=template&id=98292f6c

const CataloguePanelvue_type_template_id_98292f6c_hoisted_1 = ["aria-labelledby"];
const CataloguePanelvue_type_template_id_98292f6c_hoisted_2 = {
  class: "dataExplorerPanel__header"
};
const CataloguePanelvue_type_template_id_98292f6c_hoisted_3 = ["id"];
const CataloguePanelvue_type_template_id_98292f6c_hoisted_4 = ["aria-label", "title"];
const CataloguePanelvue_type_template_id_98292f6c_hoisted_5 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "icon-close",
  "aria-hidden": "true"
}, null, -1);
const CataloguePanelvue_type_template_id_98292f6c_hoisted_6 = [CataloguePanelvue_type_template_id_98292f6c_hoisted_5];
const CataloguePanelvue_type_template_id_98292f6c_hoisted_7 = {
  class: "dataExplorerPanel__search"
};
const CataloguePanelvue_type_template_id_98292f6c_hoisted_8 = {
  class: "dataExplorerPanel__count",
  "aria-live": "polite"
};
const CataloguePanelvue_type_template_id_98292f6c_hoisted_9 = {
  class: "dataExplorerPanel__category"
};
const CataloguePanelvue_type_template_id_98292f6c_hoisted_10 = ["aria-expanded", "aria-controls", "onClick"];
const CataloguePanelvue_type_template_id_98292f6c_hoisted_11 = {
  class: "dataExplorerPanel__categoryName"
};
const CataloguePanelvue_type_template_id_98292f6c_hoisted_12 = {
  class: "dataExplorerPanel__categoryCount"
};
const CataloguePanelvue_type_template_id_98292f6c_hoisted_13 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "dataExplorerPanel__chevron icon-chevron-down",
  "aria-hidden": "true"
}, null, -1);
const CataloguePanelvue_type_template_id_98292f6c_hoisted_14 = ["id"];
const CataloguePanelvue_type_template_id_98292f6c_hoisted_15 = ["data-id", "title", "aria-pressed", "aria-describedby", "onClick"];
const CataloguePanelvue_type_template_id_98292f6c_hoisted_16 = {
  class: "dataExplorerPanel__name"
};
const CataloguePanelvue_type_template_id_98292f6c_hoisted_17 = {
  key: 0,
  class: "dataExplorerPanel__check icon-ok",
  "aria-hidden": "true"
};
const CataloguePanelvue_type_template_id_98292f6c_hoisted_18 = ["id"];
const CataloguePanelvue_type_template_id_98292f6c_hoisted_19 = {
  key: 0,
  class: "dataExplorerPanel__empty"
};
function CataloguePanelvue_type_template_id_98292f6c_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_SearchInput = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("SearchInput");
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Teleport"], {
    to: "body"
  }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Transition"], {
    name: "dataExplorerPanelBackdrop"
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", {
      class: "dataExplorerPanelBackdrop",
      onClick: _cache[0] || (_cache[0] = (...args) => _ctx.close && _ctx.close(...args))
    }, null, 512), [[external_commonjs_vue_commonjs2_vue_root_Vue_["vShow"], _ctx.isOpen]])]),
    _: 1
  }), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Transition"], {
    name: "dataExplorerPanel"
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("section", {
      ref: "panel",
      class: "dataExplorerPanel",
      role: "dialog",
      "aria-modal": "true",
      "aria-labelledby": _ctx.titleId,
      onKeydown: _cache[4] || (_cache[4] = (...args) => _ctx.onPanelKeydown && _ctx.onPanelKeydown(...args))
    }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("header", CataloguePanelvue_type_template_id_98292f6c_hoisted_2, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h2", {
      id: _ctx.titleId,
      class: "dataExplorerPanel__title"
    }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.title), 9, CataloguePanelvue_type_template_id_98292f6c_hoisted_3), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
      type: "button",
      class: "dataExplorerIconButton dataExplorerPanel__close",
      "aria-label": _ctx.translate('General_Close'),
      title: _ctx.translate('General_Close'),
      onClick: _cache[1] || (_cache[1] = (...args) => _ctx.close && _ctx.close(...args))
    }, CataloguePanelvue_type_template_id_98292f6c_hoisted_6, 8, CataloguePanelvue_type_template_id_98292f6c_hoisted_4)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", CataloguePanelvue_type_template_id_98292f6c_hoisted_7, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_SearchInput, {
      ref: "search",
      modelValue: _ctx.query,
      "onUpdate:modelValue": _cache[2] || (_cache[2] = $event => _ctx.query = $event),
      placeholder: _ctx.translate('DataExplorer_PickerSearch'),
      "aria-label": _ctx.translate('DataExplorer_PickerSearch')
    }, null, 8, ["modelValue", "placeholder", "aria-label"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", CataloguePanelvue_type_template_id_98292f6c_hoisted_8, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_PanelAddedCount', _ctx.added.length)), 1)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", {
      ref: "body",
      class: "dataExplorerPanel__body",
      onKeydown: _cache[3] || (_cache[3] = (...args) => _ctx.onBodyKeydown && _ctx.onBodyKeydown(...args))
    }, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.filteredGroups, (group, groupIndex) => {
      return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("section", {
        key: group.category,
        class: "dataExplorerPanel__group"
      }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h3", CataloguePanelvue_type_template_id_98292f6c_hoisted_9, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
        type: "button",
        class: "dataExplorerPanel__categoryToggle",
        "data-nav": "true",
        "aria-expanded": _ctx.isExpanded(group.category) ? 'true' : 'false',
        "aria-controls": `dataExplorerPanelGroup-${groupIndex}`,
        onClick: $event => _ctx.toggleCategory(group.category)
      }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", CataloguePanelvue_type_template_id_98292f6c_hoisted_11, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(group.category), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", CataloguePanelvue_type_template_id_98292f6c_hoisted_12, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(group.items.length), 1), CataloguePanelvue_type_template_id_98292f6c_hoisted_13], 8, CataloguePanelvue_type_template_id_98292f6c_hoisted_10)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("ul", {
        id: `dataExplorerPanelGroup-${groupIndex}`
      }, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(group.items, item => {
        return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("li", {
          key: item.id
        }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
          type: "button",
          class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["dataExplorerPanel__item", {
            'is-added': _ctx.added.includes(item.id),
            'is-incompatible': !!item.reason
          }]),
          "data-id": item.id,
          "data-nav": "true",
          title: item.reason || undefined,
          "aria-pressed": _ctx.added.includes(item.id) ? 'true' : 'false',
          "aria-describedby": item.reason ? _ctx.reasonId(item.id) : undefined,
          onClick: $event => _ctx.toggle(item.id)
        }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", CataloguePanelvue_type_template_id_98292f6c_hoisted_16, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(item.name), 1), _ctx.added.includes(item.id) ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("span", CataloguePanelvue_type_template_id_98292f6c_hoisted_17)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), item.reason ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("span", {
          key: 1,
          id: _ctx.reasonId(item.id),
          class: "dataExplorerVisuallyHidden"
        }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(item.reason), 9, CataloguePanelvue_type_template_id_98292f6c_hoisted_18)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)], 10, CataloguePanelvue_type_template_id_98292f6c_hoisted_15)]);
      }), 128))], 8, CataloguePanelvue_type_template_id_98292f6c_hoisted_14), [[external_commonjs_vue_commonjs2_vue_root_Vue_["vShow"], _ctx.isExpanded(group.category)]])]);
    }), 128)), !_ctx.filteredGroups.length ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", CataloguePanelvue_type_template_id_98292f6c_hoisted_19, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.query.trim() || !_ctx.emptyText ? _ctx.translate('DataExplorer_PickerNoMatch') : _ctx.emptyText), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)], 544)], 40, CataloguePanelvue_type_template_id_98292f6c_hoisted_1), [[external_commonjs_vue_commonjs2_vue_root_Vue_["vShow"], _ctx.isOpen]])]),
    _: 1
  })]);
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/CataloguePanel.vue?vue&type=template&id=98292f6c

// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/catalogueStore.ts
/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

const catalogueStore_state = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["reactive"])({
  open: false,
  kind: null
});
let returnFocusTarget = null;
const catalogueState = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["readonly"])(catalogueStore_state);
function openCatalogue(kind, trigger) {
  catalogueStore_state.kind = kind;
  catalogueStore_state.open = true;
  returnFocusTarget = trigger;
}
function closeCatalogue() {
  catalogueStore_state.open = false;
}
/**
 * The "+" button that opened the panel, when it is still in the page.
 */
function getReturnFocusTarget() {
  return returnFocusTarget && returnFocusTarget.isConnected ? returnFocusTarget : null;
}
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Explorer/CataloguePanel.vue?vue&type=script&lang=ts



const FOCUSABLE = ['button:not([disabled])', 'input:not([disabled])', '[tabindex]:not([tabindex="-1"])'].join(',');
const PAGE_SCROLL_LOCK_CLASS = 'dataExplorer-panel-open';
const SCROLLBAR_WIDTH_VAR = '--dataexplorer-scrollbar-width';
// hiding the page scrollbar widens the page: its width is padded back so the columns do not move
function lockPageScroll(lock) {
  const root = document.documentElement;
  if (lock) {
    root.style.setProperty(SCROLLBAR_WIDTH_VAR, `${window.innerWidth - root.clientWidth}px`);
  } else {
    root.style.removeProperty(SCROLLBAR_WIDTH_VAR);
  }
  root.classList.toggle(PAGE_SCROLL_LOCK_CLASS, lock);
}
/**
 * Side panel of the "+" buttons: slides over the page from the right, searches the catalogue by
 * name and category, adds or removes variables without closing. Escape, the close button or a
 * click on the backdrop close it and give the focus back to the "+" button.
 */
/* harmony default export */ var CataloguePanelvue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  name: 'CataloguePanel',
  components: {
    SearchInput: external_CoreHome_["SearchInput"]
  },
  props: {
    title: {
      type: String,
      default: ''
    },
    groups: {
      type: Array,
      default: () => []
    },
    added: {
      type: Array,
      default: () => []
    },
    // shown when the catalogue itself is empty, before any search
    emptyText: {
      type: String,
      default: ''
    }
  },
  emits: ['add', 'remove'],
  setup(props, {
    emit
  }) {
    const panel = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    const body = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    const search = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    const query = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('');
    const titleId = 'dataExplorerPanelTitle';
    const expanded = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["reactive"])({});
    const isOpen = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => catalogueState.open);
    // a search shows every category with a match, opened
    function isExpanded(category) {
      return query.value.trim() !== '' || !!expanded[category];
    }
    function toggleCategory(category) {
      expanded[category] = !isExpanded(category);
    }
    // on open, only the categories already used in the exploration are expanded
    function resetExpanded() {
      Object.keys(expanded).forEach(category => {
        delete expanded[category];
      });
      props.groups.forEach(group => {
        if (group.items.some(item => props.added.includes(item.id))) {
          expanded[group.category] = true;
        }
      });
    }
    function reasonId(id) {
      return `dataExplorerPanelReason-${id.replace(/[^\w-]/g, '_')}`;
    }
    // arrow keys move between the category headers and the visible items
    function onBodyKeydown(event) {
      const keys = ['ArrowDown', 'ArrowUp', 'Home', 'End'];
      if (keys.indexOf(event.key) === -1 || !body.value) {
        return;
      }
      const rows = Array.from(body.value.querySelectorAll('[data-nav="true"]')).filter(row => row.offsetParent !== null);
      if (!rows.length) {
        return;
      }
      event.preventDefault();
      const current = rows.indexOf(document.activeElement);
      let next = 0;
      if (event.key === 'End') {
        next = rows.length - 1;
      } else if (event.key === 'ArrowDown') {
        next = current === -1 ? 0 : Math.min(current + 1, rows.length - 1);
      } else if (event.key === 'ArrowUp') {
        next = current <= 0 ? 0 : current - 1;
      }
      rows[next].focus();
    }
    const filteredGroups = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      const needle = query.value.trim().toLowerCase();
      if (!needle) {
        return props.groups;
      }
      return props.groups.map(group => {
        if (group.category.toLowerCase().includes(needle)) {
          return group;
        }
        return {
          category: group.category,
          items: group.items.filter(item => item.name.toLowerCase().includes(needle))
        };
      }).filter(group => group.items.length > 0);
    });
    function toggle(id) {
      emit(props.added.includes(id) ? 'remove' : 'add', id);
    }
    function close() {
      closeCatalogue();
    }
    function onDocumentKeydown(event) {
      if (event.key === 'Escape' && !event.defaultPrevented) {
        event.preventDefault();
        close();
      }
    }
    // keeps the Tab focus inside the panel
    function onPanelKeydown(event) {
      if (event.key !== 'Tab' || !panel.value) {
        return;
      }
      const focusable = Array.from(panel.value.querySelectorAll(FOCUSABLE));
      if (!focusable.length) {
        return;
      }
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    }
    function restoreFocus() {
      const active = document.activeElement;
      // a click elsewhere in the page already moved the focus, leave it there
      if (active && active !== document.body && panel.value && !panel.value.contains(active)) {
        return;
      }
      const target = getReturnFocusTarget();
      if (target) {
        target.focus();
      }
    }
    Object(external_commonjs_vue_commonjs2_vue_root_Vue_["watch"])(isOpen, open => {
      lockPageScroll(open);
      if (open) {
        query.value = '';
        resetExpanded();
        document.addEventListener('keydown', onDocumentKeydown);
        Object(external_commonjs_vue_commonjs2_vue_root_Vue_["nextTick"])(() => {
          const input = search.value ? search.value.$el.querySelector('input') : null;
          if (input) {
            input.focus();
          }
        });
        return;
      }
      document.removeEventListener('keydown', onDocumentKeydown);
      restoreFocus();
    });
    Object(external_commonjs_vue_commonjs2_vue_root_Vue_["onBeforeUnmount"])(() => {
      document.removeEventListener('keydown', onDocumentKeydown);
      lockPageScroll(false);
      closeCatalogue();
    });
    return {
      panel,
      body,
      search,
      query,
      titleId,
      isOpen,
      filteredGroups,
      isExpanded,
      toggleCategory,
      reasonId,
      onBodyKeydown,
      toggle,
      close,
      onPanelKeydown
    };
  }
}));
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/CataloguePanel.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/CataloguePanel.vue



CataloguePanelvue_type_script_lang_ts.render = CataloguePanelvue_type_template_id_98292f6c_render

/* harmony default export */ var CataloguePanel = (CataloguePanelvue_type_script_lang_ts);
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Explorer/VariablesPanel.vue?vue&type=script&lang=ts





/**
 * Left column: the dimensions, metrics and segments added to this exploration, each list with a
 * "+" picker over the catalogue. Items are dragged onto the drop zones, or used with a double
 * click, Enter or Space.
 */
/* harmony default export */ var VariablesPanelvue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  name: 'VariablesPanel',
  components: {
    CataloguePanel: CataloguePanel
  },
  props: {
    dimensions: {
      type: Array,
      required: true
    },
    metrics: {
      type: Array,
      required: true
    },
    segments: {
      type: Array,
      required: true
    },
    dimensionGroups: {
      type: Array,
      required: true
    },
    metricGroups: {
      type: Array,
      required: true
    },
    segmentGroups: {
      type: Array,
      required: true
    }
  },
  emits: ['addVariable', 'removeVariable', 'use'],
  setup(props) {
    const sections = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => [{
      kind: 'dimension',
      title: Object(external_CoreHome_["translate"])('DataExplorer_Dimensions'),
      addLabel: Object(external_CoreHome_["translate"])('DataExplorer_AddDimensions'),
      emptyText: Object(external_CoreHome_["translate"])('DataExplorer_NoDimensions'),
      catalogueEmptyText: '',
      useHint: Object(external_CoreHome_["translate"])('DataExplorer_DimensionUseHint'),
      items: props.dimensions,
      groups: props.dimensionGroups,
      added: props.dimensions.map(item => item.id)
    }, {
      kind: 'metric',
      title: Object(external_CoreHome_["translate"])('DataExplorer_Metrics'),
      addLabel: Object(external_CoreHome_["translate"])('DataExplorer_AddMetrics'),
      emptyText: Object(external_CoreHome_["translate"])('DataExplorer_NoMetrics'),
      catalogueEmptyText: '',
      useHint: Object(external_CoreHome_["translate"])('DataExplorer_MetricUseHint'),
      items: props.metrics,
      groups: props.metricGroups,
      added: props.metrics.map(item => item.id)
    }, {
      kind: 'segment',
      title: Object(external_CoreHome_["translate"])('DataExplorer_Segments'),
      addLabel: Object(external_CoreHome_["translate"])('DataExplorer_AddSegments'),
      emptyText: Object(external_CoreHome_["translate"])('DataExplorer_NoSegments'),
      catalogueEmptyText: Object(external_CoreHome_["translate"])('DataExplorer_PanelNoSegments'),
      useHint: Object(external_CoreHome_["translate"])('DataExplorer_SegmentUseHint'),
      items: props.segments,
      groups: props.segmentGroups,
      added: props.segments.map(item => item.id)
    }]);
    const activeSection = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => sections.value.find(section => section.kind === catalogueState.kind) || null);
    function isOpen(kind) {
      return catalogueState.open && catalogueState.kind === kind;
    }
    function open(kind, event) {
      openCatalogue(kind, event.currentTarget);
    }
    function onDragStart(event, kind, id) {
      startDrag(event, {
        kind,
        id
      });
    }
    return {
      sections,
      activeSection,
      isOpen,
      open,
      onDragStart,
      onDragEnd: endDrag
    };
  }
}));
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/VariablesPanel.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/VariablesPanel.vue



VariablesPanelvue_type_script_lang_ts.render = VariablesPanelvue_type_template_id_1bf0c0b0_render

/* harmony default export */ var VariablesPanel = (VariablesPanelvue_type_script_lang_ts);
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/RawSql/RawSqlEditor.vue?vue&type=template&id=85bf64c0

const RawSqlEditorvue_type_template_id_85bf64c0_hoisted_1 = {
  class: "dataExplorerSql"
};
const RawSqlEditorvue_type_template_id_85bf64c0_hoisted_2 = ["href"];
const RawSqlEditorvue_type_template_id_85bf64c0_hoisted_3 = {
  key: 1,
  class: "dataExplorerSql__layout"
};
const RawSqlEditorvue_type_template_id_85bf64c0_hoisted_4 = {
  class: "dataExplorerColumn dataExplorerSql__main"
};
const RawSqlEditorvue_type_template_id_85bf64c0_hoisted_5 = {
  id: "dataExplorerSqlEditorLabel",
  class: "dataExplorerColumn__title"
};
const RawSqlEditorvue_type_template_id_85bf64c0_hoisted_6 = {
  class: "dataExplorerSql__help"
};
const RawSqlEditorvue_type_template_id_85bf64c0_hoisted_7 = {
  ref: "container",
  class: "dataExplorerSql__editor"
};
const RawSqlEditorvue_type_template_id_85bf64c0_hoisted_8 = {
  class: "dataExplorerSql__validation",
  "aria-live": "polite"
};
const RawSqlEditorvue_type_template_id_85bf64c0_hoisted_9 = {
  key: 0,
  class: "dataExplorerSql__checking"
};
const RawSqlEditorvue_type_template_id_85bf64c0_hoisted_10 = {
  key: 1,
  class: "dataExplorerSql__problems"
};
const RawSqlEditorvue_type_template_id_85bf64c0_hoisted_11 = {
  key: 2,
  class: "dataExplorerSql__valid"
};
const RawSqlEditorvue_type_template_id_85bf64c0_hoisted_12 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "icon-ok",
  "aria-hidden": "true"
}, null, -1);
const RawSqlEditorvue_type_template_id_85bf64c0_hoisted_13 = {
  class: "dataExplorerSql__actions"
};
const RawSqlEditorvue_type_template_id_85bf64c0_hoisted_14 = ["disabled", "title"];
const RawSqlEditorvue_type_template_id_85bf64c0_hoisted_15 = ["disabled"];
const RawSqlEditorvue_type_template_id_85bf64c0_hoisted_16 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "icon-export",
  "aria-hidden": "true"
}, null, -1);
const RawSqlEditorvue_type_template_id_85bf64c0_hoisted_17 = {
  "aria-live": "polite"
};
const RawSqlEditorvue_type_template_id_85bf64c0_hoisted_18 = {
  key: 1,
  class: "dataExplorerCanvas__hint"
};
const RawSqlEditorvue_type_template_id_85bf64c0_hoisted_19 = {
  key: 2,
  class: "dataExplorerSql__count"
};
const RawSqlEditorvue_type_template_id_85bf64c0_hoisted_20 = {
  key: 0,
  class: "dataExplorerCanvas__table"
};
const RawSqlEditorvue_type_template_id_85bf64c0_hoisted_21 = {
  class: "entityTable dataTable dataExplorerResult dataExplorerSql__result"
};
function RawSqlEditorvue_type_template_id_85bf64c0_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_ActivityIndicator = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("ActivityIndicator");
  const _component_Notification = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Notification");
  const _component_ContentBlock = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("ContentBlock");
  const _component_SchemaBrowser = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("SchemaBrowser");
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", RawSqlEditorvue_type_template_id_85bf64c0_hoisted_1, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_ActivityIndicator, {
    loading: _ctx.statusLoading
  }, null, 8, ["loading"]), !_ctx.statusLoading && !_ctx.isReady ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_ContentBlock, {
    key: 0,
    "content-title": _ctx.translate('DataExplorer_SqlTab')
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Notification, {
      context: "warning",
      noclear: true
    }, {
      default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.status ? _ctx.status.message : ''), 1)]),
      _: 1
    }), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
      class: "btn dataExplorerSql__setupLink",
      href: _ctx.setupUrl
    }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_SqlOpenSetup')), 9, RawSqlEditorvue_type_template_id_85bf64c0_hoisted_2)])]),
    _: 1
  }, 8, ["content-title"])) : _ctx.isReady ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", RawSqlEditorvue_type_template_id_85bf64c0_hoisted_3, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_SchemaBrowser, {
    class: "dataExplorerColumn",
    schema: _ctx.schema,
    loading: _ctx.schemaLoading,
    onInsert: _ctx.insert
  }, null, 8, ["schema", "loading", "onInsert"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", RawSqlEditorvue_type_template_id_85bf64c0_hoisted_4, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h3", RawSqlEditorvue_type_template_id_85bf64c0_hoisted_5, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_SqlEditor')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", RawSqlEditorvue_type_template_id_85bf64c0_hoisted_6, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_SqlEditorHelp')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_ActivityIndicator, {
    loading: !_ctx.editorReady
  }, null, 8, ["loading"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", RawSqlEditorvue_type_template_id_85bf64c0_hoisted_7, null, 512), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", RawSqlEditorvue_type_template_id_85bf64c0_hoisted_8, [_ctx.validity === 'pending' ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", RawSqlEditorvue_type_template_id_85bf64c0_hoisted_9, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_SqlChecking')), 1)) : _ctx.problems.length ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("ul", RawSqlEditorvue_type_template_id_85bf64c0_hoisted_10, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.problems, (problem, index) => {
    return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("li", {
      key: index,
      class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(`is-${problem.severity}`)
    }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
      class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(problem.severity === 'error' ? 'icon-error' : 'icon-warning'),
      "aria-hidden": "true"
    }, null, 2), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(" " + Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(problem.message), 1)], 2);
  }), 128))])) : _ctx.validity === 'valid' ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", RawSqlEditorvue_type_template_id_85bf64c0_hoisted_11, [RawSqlEditorvue_type_template_id_85bf64c0_hoisted_12, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(" " + Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_SqlValid')), 1)])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", RawSqlEditorvue_type_template_id_85bf64c0_hoisted_13, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
    type: "button",
    class: "btn dataExplorerSql__run",
    disabled: !_ctx.canRun,
    title: _ctx.runTitle,
    onClick: _cache[0] || (_cache[0] = (...args) => _ctx.run && _ctx.run(...args))
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_SqlRun')), 9, RawSqlEditorvue_type_template_id_85bf64c0_hoisted_14), _ctx.canSave ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("button", {
    key: 0,
    type: "button",
    class: "btn btn-flat dataExplorerSql__save",
    disabled: _ctx.validity !== 'valid',
    onClick: _cache[1] || (_cache[1] = $event => _ctx.$emit('save'))
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_SaveReport')), 9, RawSqlEditorvue_type_template_id_85bf64c0_hoisted_15)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.columns.length ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("button", {
    key: 1,
    type: "button",
    class: "btn btn-flat dataExplorerSql__csv",
    onClick: _cache[2] || (_cache[2] = (...args) => _ctx.exportCsv && _ctx.exportCsv(...args))
  }, [RawSqlEditorvue_type_template_id_85bf64c0_hoisted_16, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(" " + Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_ExportCsv')), 1)])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_ActivityIndicator, {
    loading: _ctx.running
  }, null, 8, ["loading"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", RawSqlEditorvue_type_template_id_85bf64c0_hoisted_17, [_ctx.runError ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_Notification, {
    key: 0,
    context: "error",
    noclear: true
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.runError), 1)]),
    _: 1
  })) : _ctx.hasRun && !_ctx.rows.length && !_ctx.running ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", RawSqlEditorvue_type_template_id_85bf64c0_hoisted_18, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_SqlNoRows')), 1)) : _ctx.rows.length ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", RawSqlEditorvue_type_template_id_85bf64c0_hoisted_19, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_SqlRowCount', _ctx.rows.length)), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), _ctx.rows.length ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", RawSqlEditorvue_type_template_id_85bf64c0_hoisted_20, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("table", RawSqlEditorvue_type_template_id_85bf64c0_hoisted_21, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("thead", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("tr", null, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.columns, column => {
    return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("th", {
      key: column,
      scope: "col"
    }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(column), 1);
  }), 128))])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("tbody", null, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.rows, (row, index) => {
    return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("tr", {
      key: index
    }, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.columns, column => {
      return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("td", {
        key: column
      }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.cell(row[column])), 1);
    }), 128))]);
  }), 128))])])])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)])])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]);
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/RawSql/RawSqlEditor.vue?vue&type=template&id=85bf64c0

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/RawSql/SchemaBrowser.vue?vue&type=template&id=661d7b97

const SchemaBrowservue_type_template_id_661d7b97_hoisted_1 = {
  class: "dataExplorerSchema"
};
const SchemaBrowservue_type_template_id_661d7b97_hoisted_2 = {
  class: "dataExplorerColumn__title"
};
const SchemaBrowservue_type_template_id_661d7b97_hoisted_3 = {
  class: "dataExplorerSchema__help"
};
const SchemaBrowservue_type_template_id_661d7b97_hoisted_4 = {
  class: "dataExplorerSchema__tables"
};
const SchemaBrowservue_type_template_id_661d7b97_hoisted_5 = ["open"];
const SchemaBrowservue_type_template_id_661d7b97_hoisted_6 = {
  class: "dataExplorerSchema__table"
};
const SchemaBrowservue_type_template_id_661d7b97_hoisted_7 = ["aria-label", "onClick"];
const SchemaBrowservue_type_template_id_661d7b97_hoisted_8 = {
  class: "dataExplorerSchema__columns"
};
const SchemaBrowservue_type_template_id_661d7b97_hoisted_9 = ["aria-label", "onClick"];
function SchemaBrowservue_type_template_id_661d7b97_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_SearchInput = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("SearchInput");
  const _component_ActivityIndicator = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("ActivityIndicator");
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", SchemaBrowservue_type_template_id_661d7b97_hoisted_1, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h3", SchemaBrowservue_type_template_id_661d7b97_hoisted_2, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_SqlSchema')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", SchemaBrowservue_type_template_id_661d7b97_hoisted_3, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_SqlSchemaHelp')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_SearchInput, {
    modelValue: _ctx.query,
    "onUpdate:modelValue": _cache[0] || (_cache[0] = $event => _ctx.query = $event),
    class: "dataExplorerSchema__search",
    placeholder: _ctx.translate('DataExplorer_SqlSchemaSearch'),
    "aria-label": _ctx.translate('DataExplorer_SqlSchemaSearch')
  }, null, 8, ["modelValue", "placeholder", "aria-label"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_ActivityIndicator, {
    loading: _ctx.loading
  }, null, 8, ["loading"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("ul", SchemaBrowservue_type_template_id_661d7b97_hoisted_4, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.filteredTables, table => {
    return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("li", {
      key: table.name
    }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("details", {
      open: !!_ctx.query
    }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("summary", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", SchemaBrowservue_type_template_id_661d7b97_hoisted_6, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(table.name), 1)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
      type: "button",
      class: "dataExplorerSchema__insert",
      "aria-label": _ctx.translate('DataExplorer_SqlInsertName', table.name),
      onClick: $event => _ctx.$emit('insert', table.name)
    }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_SqlInsertTable')), 9, SchemaBrowservue_type_template_id_661d7b97_hoisted_7), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("ul", SchemaBrowservue_type_template_id_661d7b97_hoisted_8, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(table.columns, column => {
      return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("li", {
        key: column
      }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
        type: "button",
        class: "dataExplorerSchema__column",
        "aria-label": _ctx.translate('DataExplorer_SqlInsertName', column),
        onClick: $event => _ctx.$emit('insert', column)
      }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(column), 9, SchemaBrowservue_type_template_id_661d7b97_hoisted_9)]);
    }), 128))])], 8, SchemaBrowservue_type_template_id_661d7b97_hoisted_5)]);
  }), 128))])]);
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/RawSql/SchemaBrowser.vue?vue&type=template&id=661d7b97

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/RawSql/SchemaBrowser.vue?vue&type=script&lang=ts


/**
 * Tables raw SQL can read and their columns; a click inserts the name at the cursor.
 */
/* harmony default export */ var SchemaBrowservue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  name: 'SchemaBrowser',
  components: {
    ActivityIndicator: external_CoreHome_["ActivityIndicator"],
    SearchInput: external_CoreHome_["SearchInput"]
  },
  props: {
    schema: {
      type: Object,
      default: () => ({})
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  emits: ['insert'],
  setup(props) {
    const query = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('');
    const filteredTables = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      const needle = query.value.trim().toLowerCase();
      return Object.keys(props.schema).map(name => ({
        name,
        columns: needle && !name.toLowerCase().includes(needle) ? props.schema[name].filter(column => column.toLowerCase().includes(needle)) : props.schema[name]
      })).filter(table => !needle || table.columns.length > 0 || table.name.toLowerCase().includes(needle));
    });
    return {
      query,
      filteredTables
    };
  }
}));
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/RawSql/SchemaBrowser.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/RawSql/SchemaBrowser.vue



SchemaBrowservue_type_script_lang_ts.render = SchemaBrowservue_type_template_id_661d7b97_render

/* harmony default export */ var SchemaBrowser = (SchemaBrowservue_type_script_lang_ts);
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/RawSql/sqlDiagnostics.ts
/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
function diagnostic(problem, severity, docLength) {
  if (problem.offsetUtf16 === null) {
    return {
      from: 0,
      to: docLength,
      severity,
      message: problem.message
    };
  }
  // CodeMirror positions are UTF-16 code units, like the *Utf16 values of validateSql
  const from = Math.min(Math.max(0, problem.offsetUtf16), docLength);
  const to = Math.min(from + Math.max(1, problem.lengthUtf16), docLength);
  return {
    from,
    to,
    severity,
    message: problem.message
  };
}
/**
 * Editor diagnostics of a DataExplorer.validateSql result: guard errors, site scope warnings.
 */
function toDiagnostics(validation, docLength) {
  return [...validation.errors.map(problem => diagnostic(problem, 'error', docLength)), ...validation.warnings.map(problem => diagnostic(problem, 'warning', docLength))];
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/RawSql/runKeymap.ts
/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
/**
 * Ctrl or Cmd + Enter runs the query. The default keymap of basicSetup binds the same keys to
 * insertBlankLine, so this binding needs the highest precedence.
 */
function runKeymap(cm, run) {
  return cm.Prec.highest(cm.keymap.of([{
    key: 'Mod-Enter',
    run: () => {
      run();
      return true;
    }
  }]));
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/codeTheme.ts
/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
const lintUnderline = color => ({
  backgroundImage: 'none',
  textDecoration: `underline wavy ${color}`,
  textDecorationSkipInk: 'none',
  textUnderlineOffset: '3px'
});
// selectors mirror the CodeMirror base theme ones, so they win on specificity in both modes
const SELECTION_SELECTOR = ['&.cm-focused > .cm-scroller > .cm-selectionLayer .cm-selectionBackground', '.cm-selectionBackground', '.cm-content ::selection'].join(', ');
const editorTheme = {
  '&': {
    color: 'var(--om-code-fg)',
    backgroundColor: 'var(--om-code-bg)',
    border: '1px solid var(--om-code-border)',
    borderRadius: 'var(--om-code-radius)'
  },
  '.cm-scroller': {
    fontFamily: 'var(--om-code-font)'
  },
  '.cm-content': {
    caretColor: 'var(--om-code-fg)'
  },
  '.cm-cursor, .cm-dropCursor': {
    borderLeftColor: 'var(--om-code-fg)'
  },
  [SELECTION_SELECTOR]: {
    backgroundColor: 'var(--om-code-selection)'
  },
  '.cm-activeLine': {
    backgroundColor: 'var(--om-code-active-line)'
  },
  '.cm-gutters': {
    backgroundColor: 'var(--om-code-bg)',
    color: 'var(--om-code-comment)',
    borderRight: '1px solid var(--om-code-border)'
  },
  '.cm-activeLineGutter': {
    backgroundColor: 'var(--om-code-active-line)',
    color: 'var(--om-code-fg)'
  },
  '.cm-foldPlaceholder': {
    backgroundColor: 'var(--om-code-active-line)',
    border: '1px solid var(--om-code-border)',
    color: 'var(--om-code-comment)'
  },
  '&.cm-focused .cm-matchingBracket': {
    backgroundColor: 'var(--om-code-selection)',
    outline: '1px solid var(--om-code-number)'
  },
  '&.cm-focused .cm-nonmatchingBracket': {
    backgroundColor: 'transparent',
    outline: '1px solid var(--om-code-error)'
  },
  '.cm-selectionMatch, .cm-searchMatch, .cm-lintRange-active': {
    backgroundColor: 'var(--om-code-highlight)'
  },
  '.cm-searchMatch.cm-searchMatch-selected': {
    backgroundColor: 'var(--om-code-selection)',
    outline: '1px solid var(--om-code-number)'
  },
  '.cm-lintRange-error': lintUnderline('var(--om-code-error)'),
  '.cm-lintRange-warning': lintUnderline('var(--om-code-warning)'),
  '.cm-lintRange-info, .cm-lintRange-hint': lintUnderline('var(--om-code-comment)'),
  '.cm-diagnostic-error': {
    borderLeftColor: 'var(--om-code-error)'
  },
  '.cm-diagnostic-warning': {
    borderLeftColor: 'var(--om-code-warning)'
  },
  '.cm-diagnostic-info, .cm-diagnostic-hint': {
    borderLeftColor: 'var(--om-code-comment)'
  },
  '.cm-tooltip, .cm-panels': {
    backgroundColor: 'var(--om-code-surface)',
    color: 'var(--om-code-fg)',
    border: '1px solid var(--om-code-border)'
  },
  '.cm-tooltip': {
    borderRadius: '6px',
    overflow: 'hidden'
  },
  '.cm-panels.cm-panels-bottom': {
    borderTop: '1px solid var(--om-code-border)'
  },
  '.cm-tooltip-autocomplete ul li[aria-selected], .cm-panel.cm-panel-lint ul [aria-selected]': {
    backgroundColor: 'var(--om-code-selection)',
    color: 'var(--om-code-fg)'
  },
  '.cm-textfield, .cm-button': {
    backgroundColor: 'var(--om-code-bg)',
    backgroundImage: 'none',
    color: 'var(--om-code-fg)',
    border: '1px solid var(--om-code-border)'
  }
};
function highlightRules(tags) {
  return [{
    tag: tags.comment,
    color: 'var(--om-code-comment)',
    fontStyle: 'italic'
  }, {
    tag: [tags.keyword, tags.self],
    color: 'var(--om-code-keyword)'
  }, {
    tag: [tags.string, tags.regexp, tags.escape, tags.special(tags.string), tags.attributeValue],
    color: 'var(--om-code-string)'
  }, {
    tag: tags.number,
    color: 'var(--om-code-number)'
  }, {
    tag: [tags.bool, tags.null, tags.atom],
    color: 'var(--om-code-literal)'
  }, {
    tag: [tags.propertyName, tags.definition(tags.propertyName), tags.attributeName],
    color: 'var(--om-code-property)'
  }, {
    tag: tags.tagName,
    color: 'var(--om-code-tag)'
  }, {
    tag: [tags.function(tags.variableName), tags.function(tags.propertyName), tags.className, tags.typeName],
    color: 'var(--om-code-function)'
  }, {
    tag: tags.invalid,
    color: 'var(--om-code-error)'
  }];
}
let isSchemeWatched = false;
// Matomo 5 theme plugins such as DarkTheme force dark colours whatever the theme mode: the token
// palette then follows the luminance of the rendered code background instead of the mode alone.
function syncCodeScheme() {
  const probe = document.createElement('span');
  probe.style.position = 'absolute';
  probe.style.visibility = 'hidden';
  probe.style.backgroundColor = 'var(--om-code-bg)';
  document.body.appendChild(probe);
  const channels = (window.getComputedStyle(probe).backgroundColor.match(/[\d.]+/g) || []).map(Number);
  probe.remove();
  const root = document.documentElement;
  if (channels.length < 3 || channels[3] === 0) {
    delete root.dataset.omCodeScheme;
    return;
  }
  const [red, green, blue] = channels;
  const luminance = (0.299 * red + 0.587 * green + 0.114 * blue) / 255;
  root.dataset.omCodeScheme = luminance < 0.5 ? 'dark' : 'light';
}
function watchCodeScheme() {
  if (isSchemeWatched || !document.body) {
    return;
  }
  isSchemeWatched = true;
  syncCodeScheme();
  new MutationObserver(syncCodeScheme).observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['data-theme-mode']
  });
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', syncCodeScheme);
}
// one style module for every editor of the page, instead of one per editor
let cachedTheme = null;
function createCodeTheme(modules) {
  watchCodeScheme();
  if (!cachedTheme || cachedTheme.view !== modules.EditorView) {
    cachedTheme = {
      view: modules.EditorView,
      extensions: [modules.EditorView.theme(editorTheme), modules.syntaxHighlighting(modules.HighlightStyle.define(highlightRules(modules.tags)))]
    };
  }
  return cachedTheme.extensions;
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/resultTable.ts
/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
// pivot cells are "<metric id> | <column value>" columns, see VisualQueryRunner
const PIVOT_SEPARATOR = ' | ';
function toNumber(value) {
  if (typeof value === 'number') {
    return value;
  }
  if (typeof value === 'string' && value.trim() !== '' && !Number.isNaN(Number(value))) {
    return Number(value);
  }
  return 0;
}
/**
 * Metric columns of a runVisual table, in the server order: each metric, then each metric of
 * each column value. Other keys (label, row metadata, subtable ids) are left out.
 */
function resultColumns(rows, metrics) {
  const columns = [];
  const seen = new Set();
  const visit = list => {
    list.forEach(row => {
      Object.keys(row).forEach(key => {
        if (seen.has(key)) {
          return;
        }
        if (metrics.includes(key)) {
          seen.add(key);
          columns.push({
            key,
            metric: key,
            pivot: null
          });
          return;
        }
        const separator = key.indexOf(PIVOT_SEPARATOR);
        if (separator > 0 && metrics.includes(key.substring(0, separator))) {
          seen.add(key);
          columns.push({
            key,
            metric: key.substring(0, separator),
            pivot: key.substring(separator + PIVOT_SEPARATOR.length)
          });
        }
      });
    });
  };
  visit(rows);
  // totals first, then the column values in their first-seen order, metrics in exploration order
  const pivots = [null];
  columns.forEach(column => {
    if (!pivots.includes(column.pivot)) {
      pivots.push(column.pivot);
    }
  });
  const ordered = [];
  pivots.forEach(pivot => {
    metrics.forEach(metric => {
      const found = columns.find(column => column.pivot === pivot && column.metric === metric);
      if (found) {
        ordered.push(found);
      }
    });
  });
  return ordered;
}
/**
 * One line per combination of row dimension values (GA4 layout): nested subtables become the
 * following label columns. A row without subtable before the last level (the "Others" row of a
 * level) keeps empty labels for the levels below.
 */
function flattenRows(rows, depth, columns) {
  const result = [];
  const visit = (list, parents) => {
    list.forEach(row => {
      const labels = [...parents, typeof row.label === 'undefined' ? '' : String(row.label)];
      if (labels.length < depth && Array.isArray(row.subtable) && row.subtable.length) {
        visit(row.subtable, labels);
        return;
      }
      const values = {};
      columns.forEach(column => {
        values[column.key] = toNumber(row[column.key]);
      });
      while (labels.length < depth) {
        labels.push('');
      }
      result.push({
        labels,
        values
      });
    });
  };
  visit(rows, []);
  return result;
}
/**
 * First metric of the first row dimension for the bar and pie charts: the largest rows, the rest
 * summed into one "others" slice when the metric is additive. A distinct count (unique visitors)
 * cannot be summed across rows, so it shows the largest rows only.
 */
function chartSlice(rows, metric, top, othersLabel, additive = true) {
  const sorted = rows.map(row => {
    var _row$label;
    return {
      label: String((_row$label = row.label) !== null && _row$label !== void 0 ? _row$label : ''),
      value: toNumber(row[metric])
    };
  }).sort((a, b) => b.value - a.value);
  const kept = sorted.slice(0, top);
  const rest = sorted.slice(top);
  if (additive && rest.length) {
    kept.push({
      label: othersLabel,
      value: rest.reduce((sum, row) => sum + row.value, 0)
    });
  }
  return {
    labels: kept.map(row => row.label),
    values: kept.map(row => row.value)
  };
}
/**
 * One bar series per metric over the labels of chartSlice(): the rows kept by the first metric and,
 * when the slice has one, "Others" as the sum of the remaining rows, null for a metric that does
 * not add up across rows.
 */
function chartSeries(rows, slice, metrics, othersLabel, additive) {
  const hasOthers = slice.labels.length > 0 && slice.labels[slice.labels.length - 1] === othersLabel && !rows.some(row => {
    var _row$label2;
    return String((_row$label2 = row.label) !== null && _row$label2 !== void 0 ? _row$label2 : '') === othersLabel;
  });
  const keptLabels = hasOthers ? slice.labels.slice(0, -1) : slice.labels;
  const byLabel = {};
  rows.forEach(row => {
    var _row$label3;
    byLabel[String((_row$label3 = row.label) !== null && _row$label3 !== void 0 ? _row$label3 : '')] = row;
  });
  const rest = rows.filter(row => {
    var _row$label4;
    return keptLabels.indexOf(String((_row$label4 = row.label) !== null && _row$label4 !== void 0 ? _row$label4 : '')) === -1;
  });
  return metrics.map(metric => {
    const data = keptLabels.map(label => toNumber(byLabel[label] ? byLabel[label][metric] : 0));
    if (hasOthers) {
      data.push(additive(metric) ? rest.reduce((sum, row) => sum + toNumber(row[metric]), 0) : null);
    }
    return {
      metric,
      data
    };
  });
}
/**
 * One series per metric of an evolution result, keyed by period like the core API output of
 * several periods.
 */
function evolutionSeries(result, metrics) {
  const labels = [];
  const series = metrics.map(metric => ({
    metric,
    data: []
  }));
  if (typeof result !== 'object' || result === null || Array.isArray(result)) {
    return {
      labels,
      series
    };
  }
  Object.entries(result).forEach(([period, table]) => {
    labels.push(period);
    const row = Array.isArray(table) ? table[0] : table;
    series.forEach(line => {
      line.data.push(toNumber(row ? row[line.metric] : 0));
    });
  });
  return {
    labels,
    series
  };
}
/**
 * Value of a metric in a table without row dimension (KPI card).
 */
function totalValue(result, metric) {
  const row = Array.isArray(result) ? result[0] : result;
  if (!row || typeof row !== 'object' || !(metric in row)) {
    return null;
  }
  return toNumber(row[metric]);
}
/**
 * CSV of a result (RFC 4180 quoting). Cells that a spreadsheet would run as a formula are
 * prefixed with a quote.
 */
function toCsv(header, lines) {
  const cell = value => {
    let text = value === null ? '' : String(value);
    if (/^[=+\-@\t\r]/.test(text) && typeof value !== 'number') {
      text = `'${text}`;
    }
    return /[",\r\n]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
  };
  return [header, ...lines].map(line => line.map(cell).join(',')).join('\r\n');
}
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/RawSql/RawSqlEditor.vue?vue&type=script&lang=ts







let RawSqlEditorvue_type_script_lang_ts_modules = null;
function loadCodeMirror() {
  if (!RawSqlEditorvue_type_script_lang_ts_modules) {
    RawSqlEditorvue_type_script_lang_ts_modules = Promise.all(/* import() | codemirror */[__webpack_require__.e(3), __webpack_require__.e(1)]).then(__webpack_require__.bind(null, "85d2"));
  }
  return RawSqlEditorvue_type_script_lang_ts_modules;
}
/**
 * Raw SQL tab (super users): CodeMirror editor with MySQL highlighting, completion of the allowed
 * tables and columns, live validation by the server guard, the schema of the allowed tables, the
 * result table and its CSV export. Run stays disabled while the query is not valid.
 */
/* harmony default export */ var RawSqlEditorvue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  name: 'RawSqlEditor',
  components: {
    ActivityIndicator: external_CoreHome_["ActivityIndicator"],
    ContentBlock: external_CoreHome_["ContentBlock"],
    Notification: external_CoreHome_["Notification"],
    SchemaBrowser: SchemaBrowser
  },
  props: {
    modelValue: {
      type: String,
      default: ''
    },
    idSite: {
      type: Number,
      required: true
    },
    period: {
      type: String,
      required: true
    },
    date: {
      type: String,
      required: true
    },
    setupUrl: {
      type: String,
      default: ''
    },
    canSave: {
      type: Boolean,
      default: false
    }
  },
  emits: ['update:modelValue', 'save', 'validity'],
  setup(props, {
    emit
  }) {
    const container = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    const editorReady = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(false);
    const schema = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])({});
    const schemaLoading = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(false);
    const validity = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(props.modelValue.trim() ? 'pending' : 'empty');
    const problems = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])([]);
    const running = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(false);
    const hasRun = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(false);
    const runError = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('');
    const rows = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])([]);
    // CodeMirror objects must not be wrapped in Vue proxies
    let view = null;
    let isApplyingModelValue = false;
    let validationSequence = 0;
    let runSequence = 0;
    let unmounted = false;
    // asked when the tab opens: the status connects to the read-only database user
    const status = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    const statusLoading = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(true);
    const isReady = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => !!status.value && status.value.state === 'ready');
    const canRun = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => editorReady.value && validity.value === 'valid' && !running.value);
    const runTitle = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => canRun.value ? Object(external_CoreHome_["translate"])('DataExplorer_SqlRunShortcut') : Object(external_CoreHome_["translate"])('DataExplorer_SqlRunDisabled'));
    const columns = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      const keys = [];
      rows.value.forEach(row => {
        Object.keys(row).forEach(key => {
          if (!keys.includes(key)) {
            keys.push(key);
          }
        });
      });
      return keys;
    });
    Object(external_commonjs_vue_commonjs2_vue_root_Vue_["watch"])(validity, value => {
      emit('validity', value);
    }, {
      immediate: true
    });
    function validate(sql) {
      validationSequence += 1;
      const sequence = validationSequence;
      if (!sql.trim()) {
        validity.value = 'empty';
        problems.value = [];
        return Promise.resolve([]);
      }
      return external_CoreHome_["AjaxHelper"].post({
        method: 'DataExplorer.validateSql'
      }, {
        sql
      }, {
        createErrorNotification: false
      }).then(result => {
        const diagnostics = toDiagnostics(result, sql.length);
        if (sequence === validationSequence) {
          validity.value = result.valid ? 'valid' : 'invalid';
          problems.value = diagnostics;
        }
        return diagnostics;
      }).catch(e => {
        if (sequence === validationSequence) {
          validity.value = 'invalid';
          problems.value = [{
            from: 0,
            to: sql.length,
            severity: 'error',
            message: e.message
          }];
        }
        return problems.value;
      });
    }
    function run() {
      if (!canRun.value || !view) {
        return;
      }
      runSequence += 1;
      const sequence = runSequence;
      running.value = true;
      runError.value = '';
      external_CoreHome_["AjaxHelper"].post({
        method: 'DataExplorer.runSql',
        idSite: props.idSite,
        period: props.period,
        date: props.date,
        filter_limit: -1,
        format_metrics: 0
      }, {
        sql: view.state.doc.toString()
      }, {
        createErrorNotification: false
      }).then(result => {
        if (sequence !== runSequence) {
          return;
        }
        rows.value = Array.isArray(result) ? result : [];
      }).catch(e => {
        if (sequence !== runSequence) {
          return;
        }
        rows.value = [];
        runError.value = e.message;
      }).finally(() => {
        if (sequence === runSequence) {
          running.value = false;
          hasRun.value = true;
        }
      });
    }
    function insert(text) {
      if (!view) {
        return;
      }
      view.dispatch(Object.assign(Object.assign({}, view.state.replaceSelection(text)), {}, {
        scrollIntoView: true
      }));
      view.focus();
    }
    function cell(value) {
      if (value === null || typeof value === 'undefined') {
        return '';
      }
      return typeof value === 'object' ? JSON.stringify(value) : String(value);
    }
    function exportCsv() {
      const csv = toCsv(columns.value, rows.value.map(row => columns.value.map(column => {
        const value = row[column];
        return typeof value === 'number' ? value : cell(value);
      })));
      const blob = new Blob([`${String.fromCharCode(0xfeff)}${csv}`], {
        type: 'text/csv;charset=utf-8'
      });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = 'data-explorer-query.csv';
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(link.href);
    }
    function createEditor(cm) {
      if (unmounted || !container.value) {
        return;
      }
      view = new cm.EditorView({
        parent: container.value,
        state: cm.EditorState.create({
          doc: props.modelValue,
          extensions: [cm.basicSetup, cm.sql({
            dialect: cm.MySQL,
            schema: schema.value,
            upperCaseKeywords: true
          }), createCodeTheme({
            EditorView: cm.EditorView,
            HighlightStyle: cm.HighlightStyle,
            syntaxHighlighting: cm.syntaxHighlighting,
            tags: cm.tags
          }), cm.linter(editor => validate(editor.state.doc.toString()), {
            delay: 400
          }), cm.lintGutter(),
          // the editor clips its overflow: tooltips go to the body
          cm.tooltips({
            parent: document.body
          }), cm.placeholder(Object(external_CoreHome_["translate"])('DataExplorer_SqlPlaceholder')), runKeymap(cm, run), cm.EditorView.lineWrapping,
          // CodeMirror owns the class attribute of its root element
          cm.EditorView.editorAttributes.of({
            class: 'dataExplorerSql__codemirror'
          }), cm.EditorView.contentAttributes.of({
            spellcheck: 'false',
            'aria-labelledby': 'dataExplorerSqlEditorLabel'
          }), cm.EditorView.updateListener.of(update => {
            if (!update.docChanged || isApplyingModelValue) {
              return;
            }
            const value = update.state.doc.toString();
            validationSequence += 1;
            validity.value = value.trim() ? 'pending' : 'empty';
            problems.value = [];
            emit('update:modelValue', value);
          })]
        })
      });
      editorReady.value = true;
    }
    function start() {
      if (!isReady.value || view) {
        return;
      }
      schemaLoading.value = true;
      const schemaRequest = external_CoreHome_["AjaxHelper"].fetch({
        method: 'DataExplorer.getSqlSchema'
      }, {
        createErrorNotification: false
      }).then(result => {
        schema.value = result && typeof result === 'object' && !Array.isArray(result) ? result : {};
      }).catch(() => {
        // completion falls back to keywords, the guard still validates every query
        schema.value = {};
      }).finally(() => {
        schemaLoading.value = false;
      });
      Promise.all([loadCodeMirror(), schemaRequest]).then(([cm]) => createEditor(cm));
    }
    Object(external_commonjs_vue_commonjs2_vue_root_Vue_["onMounted"])(() => {
      external_CoreHome_["AjaxHelper"].fetch({
        method: 'DataExplorer.getSqlStatus'
      }, {
        createErrorNotification: false
      }).then(result => {
        status.value = result;
      }).catch(e => {
        status.value = {
          state: 'error',
          message: e.message
        };
      }).finally(() => {
        statusLoading.value = false;
        Object(external_commonjs_vue_commonjs2_vue_root_Vue_["nextTick"])(start);
      });
    });
    // a saved query opened in the explorer replaces the text
    Object(external_commonjs_vue_commonjs2_vue_root_Vue_["watch"])(() => props.modelValue, value => {
      if (!view || value === view.state.doc.toString()) {
        return;
      }
      isApplyingModelValue = true;
      view.dispatch({
        changes: {
          from: 0,
          to: view.state.doc.length,
          insert: value
        }
      });
      isApplyingModelValue = false;
      validity.value = value.trim() ? 'pending' : 'empty';
    });
    Object(external_commonjs_vue_commonjs2_vue_root_Vue_["onBeforeUnmount"])(() => {
      unmounted = true;
      if (view) {
        view.destroy();
        view = null;
      }
    });
    return {
      container,
      editorReady,
      schema,
      schemaLoading,
      validity,
      problems,
      running,
      hasRun,
      runError,
      rows,
      columns,
      isReady,
      status,
      statusLoading,
      canRun,
      runTitle,
      run,
      insert,
      cell,
      exportCsv
    };
  }
}));
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/RawSql/RawSqlEditor.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/RawSql/RawSqlEditor.vue



RawSqlEditorvue_type_script_lang_ts.render = RawSqlEditorvue_type_template_id_85bf64c0_render

/* harmony default export */ var RawSqlEditor = (RawSqlEditorvue_type_script_lang_ts);
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/SaveDialog/SaveDialog.vue?vue&type=template&id=319c0b0b

const SaveDialogvue_type_template_id_319c0b0b_hoisted_1 = {
  class: "ui-confirm dataExplorerSaveDialog"
};
const SaveDialogvue_type_template_id_319c0b0b_hoisted_2 = {
  id: "dataExplorerSaveDialogTitle"
};
const SaveDialogvue_type_template_id_319c0b0b_hoisted_3 = {
  class: "dataExplorerSaveDialog__form"
};
const SaveDialogvue_type_template_id_319c0b0b_hoisted_4 = {
  class: "dataExplorerSaveDialog__error",
  "aria-live": "assertive"
};
const SaveDialogvue_type_template_id_319c0b0b_hoisted_5 = {
  key: 1,
  class: "dataExplorerSaveDialog__reportType"
};
const SaveDialogvue_type_template_id_319c0b0b_hoisted_6 = {
  class: "dataExplorerSaveDialog__actions"
};
const SaveDialogvue_type_template_id_319c0b0b_hoisted_7 = ["value"];
function SaveDialogvue_type_template_id_319c0b0b_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Notification = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Notification");
  const _component_Field = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Field");
  const _component_SaveButton = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("SaveButton");
  const _component_MatomoDialog = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("MatomoDialog");
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_MatomoDialog, {
    "model-value": _ctx.modelValue,
    options: _ctx.dialogOptions,
    "onUpdate:modelValue": _cache[11] || (_cache[11] = $event => _ctx.$emit('update:modelValue', $event)),
    onCloseEnd: _cache[12] || (_cache[12] = $event => _ctx.$emit('closed'))
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", SaveDialogvue_type_template_id_319c0b0b_hoisted_1, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h2", SaveDialogvue_type_template_id_319c0b0b_hoisted_2, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.title), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", SaveDialogvue_type_template_id_319c0b0b_hoisted_3, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", SaveDialogvue_type_template_id_319c0b0b_hoisted_4, [_ctx.error ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_Notification, {
      key: 0,
      context: "error",
      noclear: true
    }, {
      default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.error), 1)]),
      _: 1
    })) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
      uicontrol: "text",
      name: "dataExplorerSaveName",
      modelValue: _ctx.form.name,
      "onUpdate:modelValue": _cache[0] || (_cache[0] = $event => _ctx.form.name = $event),
      title: _ctx.translate('DataExplorer_SaveName'),
      maxlength: 255,
      required: true
    }, null, 8, ["modelValue", "title"]), _ctx.type === 'sql' ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_Field, {
      key: 0,
      uicontrol: "select",
      name: "dataExplorerSaveReportType",
      modelValue: _ctx.form.reportType,
      "onUpdate:modelValue": _cache[1] || (_cache[1] = $event => _ctx.form.reportType = $event),
      title: _ctx.translate('DataExplorer_SaveReportType'),
      options: _ctx.reportTypeOptions,
      "inline-help": _ctx.translate('DataExplorer_SaveReportTypeHelp')
    }, null, 8, ["modelValue", "title", "options", "inline-help"])) : (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", SaveDialogvue_type_template_id_319c0b0b_hoisted_5, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_SavedAs', _ctx.reportTypeLabel)), 1)), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
      uicontrol: "radio",
      name: "dataExplorerSavePlacement",
      modelValue: _ctx.form.placement,
      "onUpdate:modelValue": _cache[2] || (_cache[2] = $event => _ctx.form.placement = $event),
      title: _ctx.translate('DataExplorer_SavePlacement'),
      options: _ctx.placementOptions
    }, null, 8, ["modelValue", "title", "options"]), _ctx.form.placement === 'sites' && _ctx.isSuperUser ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_Field, {
      key: 2,
      uicontrol: "text",
      name: "dataExplorerSaveSitesSearch",
      modelValue: _ctx.siteSearch,
      "onUpdate:modelValue": _cache[3] || (_cache[3] = $event => _ctx.siteSearch = $event),
      title: _ctx.translate('DataExplorer_SaveSitesSearch')
    }, null, 8, ["modelValue", "title"])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.form.placement === 'sites' ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_Field, {
      key: 3,
      uicontrol: "multiselect",
      name: "dataExplorerSaveSites",
      modelValue: _ctx.form.sites,
      "onUpdate:modelValue": _cache[4] || (_cache[4] = $event => _ctx.form.sites = $event),
      title: _ctx.translate('DataExplorer_SaveSites'),
      options: _ctx.siteOptions
    }, null, 8, ["modelValue", "title", "options"])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
      uicontrol: "select",
      name: "dataExplorerSaveCategory",
      modelValue: _ctx.form.category,
      "onUpdate:modelValue": _cache[5] || (_cache[5] = $event => _ctx.form.category = $event),
      title: _ctx.translate('DataExplorer_SaveCategory'),
      options: _ctx.categoryOptions
    }, null, 8, ["modelValue", "title", "options"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
      uicontrol: "select",
      name: "dataExplorerSaveSubcategory",
      modelValue: _ctx.form.subcategory,
      "onUpdate:modelValue": _cache[6] || (_cache[6] = $event => _ctx.form.subcategory = $event),
      title: _ctx.translate('DataExplorer_SaveSubcategory'),
      options: _ctx.subcategoryOptions
    }, null, 8, ["modelValue", "title", "options"]), _ctx.form.subcategory === _ctx.newSubcategoryKey ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_Field, {
      key: 4,
      uicontrol: "text",
      name: "dataExplorerSaveNewSubcategory",
      modelValue: _ctx.form.newSubcategory,
      "onUpdate:modelValue": _cache[7] || (_cache[7] = $event => _ctx.form.newSubcategory = $event),
      title: _ctx.translate('DataExplorer_SaveNewSubcategory'),
      maxlength: 255
    }, null, 8, ["modelValue", "title"])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
      uicontrol: "text",
      "var-type": "integer",
      name: "dataExplorerSaveOrder",
      modelValue: _ctx.form.order,
      "onUpdate:modelValue": _cache[8] || (_cache[8] = $event => _ctx.form.order = $event),
      title: _ctx.translate('DataExplorer_SaveOrder'),
      "inline-help": _ctx.translate('DataExplorer_SaveOrderHelp')
    }, null, 8, ["modelValue", "title", "inline-help"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", SaveDialogvue_type_template_id_319c0b0b_hoisted_6, [_ctx.canUpdate ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_SaveButton, {
      key: 0,
      class: "dataExplorerSaveDialog__update",
      saving: _ctx.saving,
      value: _ctx.translate('General_Save'),
      onConfirm: _cache[9] || (_cache[9] = $event => _ctx.save(false))
    }, null, 8, ["saving", "value"])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_SaveButton, {
      class: "dataExplorerSaveDialog__create",
      saving: _ctx.saving,
      value: _ctx.editing ? _ctx.translate('DataExplorer_SaveAsNew') : _ctx.translate('General_Save'),
      onConfirm: _cache[10] || (_cache[10] = $event => _ctx.save(true))
    }, null, 8, ["saving", "value"])])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("input", {
      type: "button",
      role: "cancel",
      value: _ctx.translate('General_Cancel')
    }, null, 8, SaveDialogvue_type_template_id_319c0b0b_hoisted_7)])]),
    _: 1
  }, 8, ["model-value", "options"]);
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/SaveDialog/SaveDialog.vue?vue&type=template&id=319c0b0b

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/SaveDialog/SaveDialog.vue?vue&type=script&lang=ts



const DEFAULT_CATEGORY = 'DataExplorer_DataExplorer';
const NEW_SUBCATEGORY = '__dataexplorer_new__';
const DEFAULT_ORDER = 100;
// super users can write to every website: the list is searched on demand, never loaded whole
const SITE_SEARCH_LIMIT = 50;
const SITE_SEARCH_DELAY_MS = 300;
/**
 * Saves an exploration or a raw SQL query as a report: name, placement (this site, several
 * sites, all websites for super users), menu category and page, order. An opened report is
 * updated, or saved as a new one.
 */
/* harmony default export */ var SaveDialogvue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  name: 'SaveDialog',
  components: {
    Field: external_CorePluginsAdmin_["Field"],
    MatomoDialog: external_CoreHome_["MatomoDialog"],
    Notification: external_CoreHome_["Notification"],
    SaveButton: external_CorePluginsAdmin_["SaveButton"]
  },
  props: {
    modelValue: {
      type: Boolean,
      required: true
    },
    idSite: {
      type: Number,
      required: true
    },
    isSuperUser: {
      type: Boolean,
      default: false
    },
    writableSites: {
      type: Array,
      default: () => []
    },
    type: {
      type: String,
      required: true
    },
    // report type of a visual exploration, chosen in the dialog for raw SQL
    reportType: {
      type: String,
      default: 'table'
    },
    definition: {
      type: Object,
      required: true
    },
    editing: {
      type: Object,
      default: null
    }
  },
  emits: ['update:modelValue', 'saved', 'closed'],
  setup(props, {
    emit
  }) {
    const categories = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])([]);
    const saving = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(false);
    const error = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('');
    const form = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["reactive"])({
      name: '',
      reportType: 'table',
      placement: 'site',
      sites: [],
      category: DEFAULT_CATEGORY,
      subcategory: NEW_SUBCATEGORY,
      newSubcategory: '',
      order: DEFAULT_ORDER
    });
    const dialogOptions = {
      focusSelector: '#dataExplorerSaveName'
    };
    const title = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => Object(external_CoreHome_["translate"])(props.editing ? 'DataExplorer_SaveDialogEditTitle' : 'DataExplorer_SaveDialogTitle'));
    const canUpdate = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => !!props.editing && props.editing.editable && props.editing.type === props.type);
    const reportTypeOptions = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => ['table', 'evolution', 'kpicard'].map(key => ({
      key,
      value: Object(external_CoreHome_["translate"])(`DataExplorer_ReportType_${key}`)
    })));
    const reportTypeLabel = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => Object(external_CoreHome_["translate"])(`DataExplorer_ReportType_${props.reportType}`));
    const placementOptions = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      const options = [{
        key: 'site',
        value: Object(external_CoreHome_["translate"])('DataExplorer_PlacementThisSite')
      }, {
        key: 'sites',
        value: Object(external_CoreHome_["translate"])('DataExplorer_PlacementSites')
      }];
      if (props.isSuperUser) {
        options.push({
          key: 'all',
          value: Object(external_CoreHome_["translate"])('DataExplorer_PlacementAll')
        });
      }
      return options;
    });
    const siteNames = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["reactive"])({});
    const foundSites = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])([]);
    const siteSearch = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('');
    let siteSearchTimer = null;
    let siteSearchRun = 0;
    function rememberSites(sites) {
      return sites.map(site => {
        const id = String(site.idsite !== undefined ? site.idsite : site.id);
        siteNames[id] = String(site.name);
        return id;
      });
    }
    function searchSites() {
      siteSearchRun += 1;
      const run = siteSearchRun;
      external_CoreHome_["AjaxHelper"].fetch({
        // getPatternMatchSites refuses an empty pattern (HTTP 400): the dialog lists
        // the sites before any search
        method: 'SitesManager.getSitesWithMinimumAccess',
        permission: 'write',
        pattern: siteSearch.value.trim(),
        limit: SITE_SEARCH_LIMIT
      }, {
        createErrorNotification: false
      }).then(result => {
        if (run === siteSearchRun) {
          foundSites.value = rememberSites(Array.isArray(result) ? result : []);
        }
      }).catch(e => {
        error.value = e.message;
      });
    }
    // names of selected sites outside the search results (an opened report)
    function loadSelectedSiteNames() {
      form.sites.filter(id => !siteNames[id]).forEach(id => {
        external_CoreHome_["AjaxHelper"].fetch({
          method: 'SitesManager.getSiteFromId',
          idSite: id
        }, {
          createErrorNotification: false
        }).then(site => {
          rememberSites([site]);
        }).catch(() => {
          siteNames[id] = id;
        });
      });
    }
    Object(external_commonjs_vue_commonjs2_vue_root_Vue_["watch"])(siteSearch, () => {
      if (siteSearchTimer) {
        clearTimeout(siteSearchTimer);
      }
      siteSearchTimer = setTimeout(searchSites, SITE_SEARCH_DELAY_MS);
    });
    const siteOptions = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      if (!props.isSuperUser) {
        return props.writableSites.map(site => ({
          key: String(site.id),
          value: site.name
        }));
      }
      const ids = form.sites.concat(foundSites.value.filter(id => form.sites.indexOf(id) === -1));
      return ids.map(id => ({
        key: id,
        value: siteNames[id] || id
      }));
    });
    const categoryOptions = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => categories.value.map(category => ({
      key: category.id,
      value: category.name
    })));
    const subcategoryOptions = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      const category = categories.value.find(item => item.id === form.category);
      const options = (category ? category.subcategories : []).map(subcategory => ({
        key: subcategory.id,
        value: subcategory.name
      }));
      options.push({
        key: NEW_SUBCATEGORY,
        value: Object(external_CoreHome_["translate"])('DataExplorer_SaveNewSubcategoryOption')
      });
      return options;
    });
    function subcategoryExists(category, subcategory) {
      const found = categories.value.find(item => item.id === category);
      return !!found && found.subcategories.some(item => item.id === subcategory);
    }
    function isOnlyThisSite(sites) {
      return sites.length === 1 && sites[0] === props.idSite;
    }
    function prefill() {
      error.value = '';
      const report = props.editing;
      form.name = report ? report.name : '';
      form.reportType = report ? report.report_type : props.reportType;
      form.order = report ? report.order : DEFAULT_ORDER;
      if (report && report.placement.all) {
        form.placement = props.isSuperUser ? 'all' : 'site';
        form.sites = [String(props.idSite)];
      } else if (report && !isOnlyThisSite(report.placement.sites)) {
        form.placement = 'sites';
        form.sites = report.placement.sites.map(String);
      } else {
        form.placement = 'site';
        form.sites = [String(props.idSite)];
      }
      form.category = report ? report.category : DEFAULT_CATEGORY;
      if (report && subcategoryExists(report.category, report.subcategory)) {
        form.subcategory = report.subcategory;
        form.newSubcategory = '';
      } else {
        form.subcategory = NEW_SUBCATEGORY;
        form.newSubcategory = report ? report.subcategory : Object(external_CoreHome_["translate"])('DataExplorer_DefaultSubcategory');
      }
    }
    function loadCategories() {
      if (categories.value.length) {
        return Promise.resolve();
      }
      return external_CoreHome_["AjaxHelper"].fetch({
        method: 'DataExplorer.getCategories',
        idSite: props.idSite
      }, {
        createErrorNotification: false
      }).then(result => {
        categories.value = Array.isArray(result) ? result : [];
      }).catch(e => {
        error.value = e.message;
      });
    }
    Object(external_commonjs_vue_commonjs2_vue_root_Vue_["watch"])(() => props.modelValue, open => {
      if (open) {
        loadCategories().then(prefill).then(() => {
          if (props.isSuperUser) {
            loadSelectedSiteNames();
            searchSites();
          }
        });
      }
    }, {
      immediate: true
    });
    // a new category starts on its first page
    Object(external_commonjs_vue_commonjs2_vue_root_Vue_["watch"])(() => form.category, (category, previous) => {
      if (previous && category !== previous && !subcategoryExists(category, form.subcategory)) {
        form.subcategory = NEW_SUBCATEGORY;
      }
    });
    function placementJson() {
      if (form.placement === 'all') {
        return JSON.stringify({
          all: true
        });
      }
      if (form.placement === 'sites') {
        return JSON.stringify({
          sites: form.sites.map(id => parseInt(id, 10))
        });
      }
      return JSON.stringify({
        sites: [props.idSite]
      });
    }
    function save(asNew) {
      error.value = '';
      saving.value = true;
      const isUpdate = !asNew && canUpdate.value && props.editing;
      const reportType = props.type === 'sql' ? form.reportType : props.reportType;
      const postParams = {
        name: form.name,
        type: props.type,
        reportType,
        definition: JSON.stringify(Object.assign(Object.assign({}, props.definition), {}, {
          report_type: reportType
        })),
        placement: placementJson(),
        category: form.category,
        subcategory: form.subcategory === NEW_SUBCATEGORY ? form.newSubcategory : form.subcategory,
        order: parseInt(String(form.order), 10) || 0
      };
      if (isUpdate && props.editing) {
        postParams.idReport = props.editing.idreport;
      }
      external_CoreHome_["AjaxHelper"].post({
        method: isUpdate ? 'DataExplorer.updateReport' : 'DataExplorer.addReport',
        idSite: props.idSite
      }, postParams, {
        createErrorNotification: false
      }).then(result => {
        const idReport = isUpdate && props.editing ? props.editing.idreport : Number(result && typeof result === 'object' ? result.value : result);
        emit('saved', {
          idReport,
          isNew: !isUpdate
        });
        emit('update:modelValue', false);
      }).catch(e => {
        error.value = e.message;
      }).finally(() => {
        saving.value = false;
      });
    }
    return {
      form,
      saving,
      error,
      dialogOptions,
      title,
      canUpdate,
      reportTypeOptions,
      reportTypeLabel,
      placementOptions,
      siteOptions,
      siteSearch,
      categoryOptions,
      subcategoryOptions,
      newSubcategoryKey: NEW_SUBCATEGORY,
      save
    };
  }
}));
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/SaveDialog/SaveDialog.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/SaveDialog/SaveDialog.vue



SaveDialogvue_type_script_lang_ts.render = SaveDialogvue_type_template_id_319c0b0b_render

/* harmony default export */ var SaveDialog = (SaveDialogvue_type_script_lang_ts);
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/catalogNames.ts
/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
/**
 * Display name of each catalogue item: plugins may give two dimensions the same name (the visit
 * "Actions" count and the action "Actions" type), those get their scope, and their id when the
 * scope does not tell them apart either.
 */
function distinctNames(items, scopeLabel) {
  const byName = {};
  items.forEach(item => {
    (byName[item.name] = byName[item.name] || []).push(item);
  });
  const names = {};
  Object.values(byName).forEach(group => {
    group.forEach(item => {
      if (group.length === 1) {
        names[item.id] = item.name;
        return;
      }
      const sameScope = group.filter(other => other.scope === item.scope).length > 1;
      const qualifier = sameScope ? `${scopeLabel(item.scope)}, ${item.id}` : scopeLabel(item.scope);
      names[item.id] = `${item.name} (${qualifier})`;
    });
  });
  return names;
}
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Explorer/Explorer.vue?vue&type=script&lang=ts










const RUN_DELAY_MS = 300;
const CHART_TOP_ROWS = 10;
/**
 * The explorer of the "Data Explorer > Explore" page: free-form exploration in three columns
 * (variables, tab settings, canvas) like GA4 Explorations, the raw SQL tab for super users and
 * the save dialog. The exploration lives in the URL hash so a reload or a shared link restores
 * it; a saved report is reopened with idReport in the hash.
 */
/* harmony default export */ var Explorervue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  name: 'Explorer',
  components: {
    ActivityIndicator: external_CoreHome_["ActivityIndicator"],
    Canvas: Canvas,
    Notification: external_CoreHome_["Notification"],
    RawSqlEditor: RawSqlEditor,
    SaveDialog: SaveDialog,
    TabSettings: TabSettings,
    VariablesPanel: VariablesPanel
  },
  props: {
    isSuperUser: {
      type: Boolean,
      default: false
    },
    canSave: {
      type: Boolean,
      default: false
    },
    writableSites: {
      type: Array,
      default: () => []
    },
    setupUrl: {
      type: String,
      default: ''
    }
  },
  setup(props) {
    const idSite = parseInt(String(external_CoreHome_["Matomo"].idSite), 10);
    const period = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => String(external_CoreHome_["MatomoUrl"].parsed.value.period || external_CoreHome_["Matomo"].period || 'day'));
    const date = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => String(external_CoreHome_["MatomoUrl"].parsed.value.date || external_CoreHome_["Matomo"].currentDateString || 'today'));
    const segment = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => String(external_CoreHome_["MatomoUrl"].parsed.value.segment || ''));
    const catalog = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    const catalogError = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('');
    const reports = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])([]);
    const editing = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    const state = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["reactive"])(defaultState());
    const sql = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('');
    const activeTab = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('explore');
    const sqlOpened = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(false);
    const loading = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(false);
    const runError = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('');
    const tableRows = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])([]);
    const evolutionResult = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    const kpiResult = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    const notice = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('');
    const noticeContext = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('success');
    const saveOpen = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(false);
    const saveType = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('visual');
    const saveButton = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    const canSaveAnything = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => props.canSave || props.isSuperUser);
    const tabs = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => [{
      id: 'explore',
      label: Object(external_CoreHome_["translate"])('DataExplorer_ExploreTab')
    }, {
      id: 'sql',
      label: Object(external_CoreHome_["translate"])('DataExplorer_SqlTab')
    }]);
    // catalogue lookups
    const dimensionsById = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      const map = {};
      (catalog.value ? catalog.value.dimensions : []).forEach(group => {
        group.items.forEach(item => {
          map[item.id] = item;
        });
      });
      return map;
    });
    const metricsById = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      const map = {};
      (catalog.value ? catalog.value.metrics : []).forEach(group => {
        group.items.forEach(item => {
          map[item.id] = item;
        });
      });
      return map;
    });
    const segmentNames = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      const map = {};
      (catalog.value ? catalog.value.segments : []).forEach(item => {
        map[item.definition] = item.name;
      });
      return map;
    });
    const metricNames = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      const map = {};
      Object.values(metricsById.value).forEach(metric => {
        map[metric.id] = metric.name;
      });
      return map;
    });
    const periodText = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      try {
        return external_CoreHome_["Periods"].parse(period.value, date.value).getPrettyString();
      } catch (e) {
        return `${period.value} ${date.value}`;
      }
    });
    const segmentText = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      if (!segment.value) {
        return Object(external_CoreHome_["translate"])('SegmentEditor_DefaultAllVisits');
      }
      let decoded = segment.value;
      try {
        decoded = decodeURIComponent(segment.value);
      } catch (e) {
        // keeps the raw value
      }
      return segmentNames.value[segment.value] || segmentNames.value[decoded] || Object(external_CoreHome_["translate"])('SegmentEditor_CustomSegment');
    });
    // the period line of the canvas opens the period selector of the page
    function openPeriodSelector() {
      const toggle = document.querySelector('#periodString #date');
      if (toggle) {
        toggle.click();
        toggle.focus();
      }
    }
    const scopeLabel = scope => Object(external_CoreHome_["translate"])(`DataExplorer_Scope_${scope}`);
    // the catalogue can hold several items with the same name, the scope tells them apart
    const dimensionLabels = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => distinctNames(Object.values(dimensionsById.value), scopeLabel));
    const metricLabels = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => distinctNames(Object.values(metricsById.value), scopeLabel));
    function pickerGroups(groups, labels, reasonOf) {
      return groups.map(group => ({
        category: group.category,
        items: group.items.map(({
          id,
          name
        }) => ({
          id,
          name: labels[id] || name,
          reason: reasonOf(id)
        }))
      }));
    }
    const segmentGroups = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      const items = (catalog.value ? catalog.value.segments : []).map(item => ({
        id: item.definition,
        name: item.name
      }));
      return items.length ? [{
        category: Object(external_CoreHome_["translate"])('DataExplorer_SavedSegments'),
        items
      }] : [];
    });
    // compatibility of the variables with the rest of the exploration
    const usedDimensions = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => [...(hasRowDimensions(state.visualization) ? state.rows : []), ...(hasRowDimensions(state.visualization) && state.column ? [state.column] : []), ...state.filters.map(filter => filter.dimension)]);
    function dimensionName(id) {
      return dimensionLabels.value[id] || id;
    }
    function metricName(id) {
      return metricLabels.value[id] || id;
    }
    function metricReason(id) {
      const metric = metricsById.value[id];
      if (!metric) {
        return Object(external_CoreHome_["translate"])('DataExplorer_UnknownVariable');
      }
      const conflict = usedDimensions.value.map(dimensionId => dimensionsById.value[dimensionId]).find(dimension => dimension && !metric.allowedScopes.includes(dimension.scope));
      return conflict ? Object(external_CoreHome_["translate"])('DataExplorer_IncompatibleWithDimension', conflict.name) : '';
    }
    function dimensionReason(id) {
      const dimension = dimensionsById.value[id];
      if (!dimension) {
        return Object(external_CoreHome_["translate"])('DataExplorer_UnknownVariable');
      }
      const conflict = state.values.map(metricId => metricsById.value[metricId]).find(metric => metric && !metric.allowedScopes.includes(dimension.scope));
      return conflict ? Object(external_CoreHome_["translate"])('DataExplorer_IncompatibleWithMetric', conflict.name) : '';
    }
    const dimensionGroups = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => pickerGroups(catalog.value ? catalog.value.dimensions : [], dimensionLabels.value, dimensionReason));
    const metricGroups = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => pickerGroups(catalog.value ? catalog.value.metrics : [], metricLabels.value, metricReason));
    const dimensionViews = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => state.variables.dimensions.map(id => ({
      id,
      name: dimensionName(id),
      reason: dimensionReason(id),
      used: usedDimensions.value.includes(id)
    })));
    const metricViews = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => state.variables.metrics.map(id => ({
      id,
      name: metricName(id),
      reason: metricReason(id),
      used: state.values.includes(id)
    })));
    const segmentViews = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => state.variables.segments.map(id => ({
      id,
      name: segmentNames.value[id] || id,
      reason: '',
      used: state.segment === id
    })));
    const rowItems = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => state.rows.map(id => ({
      id,
      name: dimensionName(id),
      reason: dimensionReason(id)
    })));
    const columnItems = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => state.column ? [{
      id: state.column,
      name: dimensionName(state.column),
      reason: dimensionReason(state.column)
    }] : []);
    const valueItems = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => state.values.map(id => ({
      id,
      name: metricName(id),
      reason: metricReason(id)
    })));
    const segmentItems = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => state.segment ? [{
      id: state.segment,
      name: segmentNames.value[state.segment] || state.segment,
      reason: ''
    }] : []);
    const filterViews = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => state.filters.map(filter => ({
      filter,
      name: dimensionName(filter.dimension),
      reason: dimensionReason(filter.dimension)
    })));
    // what the canvas needs before a run
    const hint = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      if (!state.values.length) {
        return Object(external_CoreHome_["translate"])('DataExplorer_HintAddMetric');
      }
      if (hasRowDimensions(state.visualization) && !state.rows.length) {
        return Object(external_CoreHome_["translate"])('DataExplorer_HintAddRow');
      }
      const withRows = hasRowDimensions(state.visualization);
      const sent = [...(withRows ? [...rowItems.value, ...columnItems.value] : []), ...(state.visualization === 'kpicard' ? valueItems.value.slice(0, 1) : valueItems.value), ...filterViews.value.filter(view => isFilterComplete(view.filter))];
      const blocked = sent.some(item => !!item.reason);
      if (blocked) {
        return Object(external_CoreHome_["translate"])('DataExplorer_HintIncompatible');
      }
      return '';
    });
    const canvasTitle = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => editing.value && editing.value.type === 'visual' ? editing.value.name : Object(external_CoreHome_["translate"])('DataExplorer_Canvas'));
    const isRunnable = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => !!catalog.value && hint.value === '');
    const definition = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => toDefinition(state));
    // what the canvas requests: the bar and pie charts read the first row dimension only
    const requestDefinition = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      const def = definition.value;
      if (state.visualization === 'bar' || state.visualization === 'pie') {
        return Object.assign(Object.assign({}, def), {}, {
          rows: def.rows.slice(0, 1),
          column: null
        });
      }
      return def;
    });
    // results
    const resultDimensionNames = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => requestDefinition.value.rows.map(dimensionName));
    const resultColumnsList = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => resultColumns(tableRows.value, requestDefinition.value.metrics));
    const resultRows = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => flattenRows(tableRows.value, requestDefinition.value.rows.length, resultColumnsList.value));
    // an "Others" slice is the sum of its rows: never for a distinct count that the rows share
    function addsUpAcrossRows(metricId) {
      const metric = metricsById.value[metricId];
      const dimension = dimensionsById.value[requestDefinition.value.rows[0]];
      if (!metric || !dimension) {
        return true;
      }
      return (metric.additiveScopes || []).indexOf(dimension.scope) !== -1;
    }
    const slice = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => state.values.length ? chartSlice(tableRows.value, state.values[0], CHART_TOP_ROWS, Object(external_CoreHome_["translate"])('General_Others'), addsUpAcrossRows(state.values[0])) : null);
    const barSeries = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => slice.value ? chartSeries(tableRows.value, slice.value, state.values, Object(external_CoreHome_["translate"])('General_Others'), addsUpAcrossRows).map(line => ({
      name: metricNames.value[line.metric] || line.metric,
      data: line.data
    })) : []);
    const evolution = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => evolutionResult.value);
    const kpi = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      if (!kpiResult.value || !state.values.length) {
        return null;
      }
      const metric = state.values[0];
      const {
        value
      } = kpiResult.value;
      const [firstSeries] = kpiResult.value.evolution.series;
      const points = firstSeries ? firstSeries.data : [];
      let evolutionView = null;
      if (period.value !== 'range' && points.length >= 2) {
        const current = points[points.length - 1];
        const previous = points[points.length - 2];
        evolutionView = {
          percent: external_CoreHome_["NumberFormatter"].calculateAndFormatEvolution(current, previous),
          trend: Math.sign(current - previous),
          tooltip: Object(external_CoreHome_["translate"])('DataExplorer_KpiPreviousPeriod', external_CoreHome_["NumberFormatter"].formatNumber(previous))
        };
      }
      return {
        metricName: metricName(metric),
        value: value === null ? '' : external_CoreHome_["NumberFormatter"].formatNumber(value, Number.isInteger(value) ? 0 : 2),
        evolution: evolutionView,
        sparkline: points,
        sparklineLabels: kpiResult.value.evolution.labels
      };
    });
    // runs
    let runTimer = null;
    let runController = null;
    let runSequence = 0;
    function runVisual(def, controller) {
      return external_CoreHome_["AjaxHelper"].post({
        method: 'DataExplorer.runVisual',
        idSite,
        period: period.value,
        date: date.value,
        expanded: 1,
        filter_limit: -1,
        format_metrics: 0
      }, {
        definition: JSON.stringify(def)
      }, {
        createErrorNotification: false,
        abortController: controller
      });
    }
    function run() {
      if (runController) {
        runController.abort();
        runController = null;
      }
      runSequence += 1;
      const sequence = runSequence;
      if (!isRunnable.value) {
        loading.value = false;
        runError.value = '';
        return;
      }
      const controller = new AbortController();
      runController = controller;
      loading.value = true;
      runError.value = '';
      const def = requestDefinition.value;
      let request;
      if (def.report_type === 'evolution') {
        request = runVisual(def, controller).then(result => {
          if (sequence === runSequence) {
            evolutionResult.value = evolutionSeries(result, def.metrics);
          }
        });
      } else if (def.report_type === 'kpicard') {
        request = Promise.all([runVisual(def, controller), runVisual(Object.assign(Object.assign({}, def), {}, {
          report_type: 'evolution'
        }), controller)]).then(([value, series]) => {
          if (sequence === runSequence) {
            kpiResult.value = {
              value: totalValue(value, def.metrics[0]),
              evolution: evolutionSeries(series, def.metrics.slice(0, 1))
            };
          }
        });
      } else {
        request = runVisual(def, controller).then(result => {
          if (sequence === runSequence) {
            tableRows.value = Array.isArray(result) ? result : [];
          }
        });
      }
      request.catch(e => {
        if (sequence === runSequence) {
          runError.value = e.message;
        }
      }).finally(() => {
        if (sequence === runSequence) {
          loading.value = false;
          runController = null;
        }
      });
    }
    function scheduleRun() {
      if (runTimer) {
        clearTimeout(runTimer);
      }
      runTimer = setTimeout(() => {
        runTimer = null;
        run();
      }, RUN_DELAY_MS);
    }
    // URL hash
    let lastEncoded = '';
    function syncHash() {
      if (!external_CoreHome_["Matomo"].helper.isReportingPage()) {
        return;
      }
      const encoded = encode(state);
      const current = external_CoreHome_["MatomoUrl"].hashParsed.value;
      if (encoded === lastEncoded && current[HASH_PARAM] === encoded) {
        return;
      }
      lastEncoded = encoded;
      const params = Object.assign(Object.assign({}, current), {}, {
        [HASH_PARAM]: encoded
      });
      if (editing.value) {
        params[REPORT_HASH_PARAM] = String(editing.value.idreport);
      } else {
        delete params[REPORT_HASH_PARAM];
      }
      external_CoreHome_["MatomoUrl"].updateHash(params);
    }
    function applyState(next) {
      Object.assign(state, catalog.value ? clampToLimits(next, catalog.value.limits) : next);
      lastEncoded = encode(state);
    }
    function openReport(report, keepState) {
      editing.value = report;
      if (report.type === 'sql') {
        sql.value = report.definition.sql || '';
        sqlOpened.value = true;
        activeTab.value = 'sql';
        return;
      }
      if (!keepState) {
        applyState(fromDefinition(report.definition, report.report_type));
      }
      activeTab.value = 'explore';
    }
    const openableReports = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => reports.value.filter(report => report.type === 'visual' || props.isSuperUser));
    function readHash() {
      const hash = external_CoreHome_["MatomoUrl"].parsed.value;
      const idReport = parseInt(String(hash[REPORT_HASH_PARAM] || ''), 10);
      const encoded = typeof hash[HASH_PARAM] === 'string' ? hash[HASH_PARAM] : '';
      if (idReport > 0) {
        const report = openableReports.value.find(item => item.idreport === idReport);
        if (report && (!editing.value || editing.value.idreport !== idReport)) {
          openReport(report, !!encoded);
        }
      } else if (editing.value) {
        editing.value = null;
      }
      if (encoded && encoded !== lastEncoded) {
        applyState(decode(encoded));
      }
    }
    function loadReports() {
      return external_CoreHome_["AjaxHelper"].fetch({
        method: 'DataExplorer.getReports',
        idSite,
        filter_limit: -1
      }, {
        createErrorNotification: false
      }).then(result => {
        reports.value = Array.isArray(result) ? result : [];
      }).catch(() => {
        reports.value = [];
      });
    }
    function onPickReport(event) {
      const id = parseInt(event.target.value, 10);
      const report = reports.value.find(item => item.idreport === id) || null;
      notice.value = '';
      if (!report) {
        editing.value = null;
        applyState(defaultState());
        sql.value = '';
        syncHash();
        scheduleRun();
        return;
      }
      openReport(report, false);
      lastEncoded = '';
      syncHash();
    }
    function selectTab(tab) {
      activeTab.value = tab;
      if (tab === 'sql') {
        sqlOpened.value = true;
      }
    }
    // variables and drop zones
    function addVariable(kind, id) {
      let list = state.variables.segments;
      if (kind === 'dimension') {
        list = state.variables.dimensions;
      } else if (kind === 'metric') {
        list = state.variables.metrics;
      }
      if (!list.includes(id)) {
        list.push(id);
      }
    }
    function removeVariable(kind, id) {
      if (kind === 'dimension') {
        state.variables.dimensions = state.variables.dimensions.filter(item => item !== id);
        state.rows = state.rows.filter(item => item !== id);
        if (state.column === id) {
          state.column = null;
        }
        state.filters = state.filters.filter(filter => filter.dimension !== id);
      } else if (kind === 'metric') {
        state.variables.metrics = state.variables.metrics.filter(item => item !== id);
        state.values = state.values.filter(item => item !== id);
      } else {
        state.variables.segments = state.variables.segments.filter(item => item !== id);
        if (state.segment === id) {
          state.segment = '';
        }
      }
    }
    // incompatible items are refused the same way whether they are dropped, double clicked or
    // picked in a zone menu
    function refusal(zone, id) {
      if (zone === 'segment') {
        return '';
      }
      return zone === 'values' ? metricReason(id) : dimensionReason(id);
    }
    function addToZone(zone, id) {
      const limits = catalog.value ? catalog.value.limits : null;
      if (!limits) {
        return;
      }
      const reason = refusal(zone, id);
      if (reason) {
        noticeContext.value = 'warning';
        notice.value = Object(external_CoreHome_["translate"])('DataExplorer_ItemRefused', zone === 'values' ? metricName(id) : dimensionName(id), reason);
        return;
      }
      if (noticeContext.value === 'warning') {
        notice.value = '';
      }
      if (zone === 'rows') {
        if (!state.rows.includes(id) && state.rows.length < limits.maxDimensions) {
          state.rows.push(id);
          if (state.column === id) {
            state.column = null;
          }
        }
      } else if (zone === 'column') {
        state.column = id;
        state.rows = state.rows.filter(item => item !== id);
      } else if (zone === 'values') {
        if (!state.values.includes(id) && state.values.length < limits.maxMetrics) {
          state.values.push(id);
        }
      } else if (zone === 'filters') {
        state.filters.push({
          dimension: id,
          operator: 'equals',
          value: ''
        });
      } else {
        state.segment = id;
      }
    }
    function removeFromZone(zone, id) {
      if (zone === 'rows') {
        state.rows = state.rows.filter(item => item !== id);
      } else if (zone === 'column') {
        state.column = null;
      } else if (zone === 'values') {
        state.values = state.values.filter(item => item !== id);
      } else if (zone === 'segment') {
        state.segment = '';
      }
    }
    function useVariable(kind, id) {
      if (kind === 'dimension') {
        if (hasRowDimensions(state.visualization)) {
          addToZone('rows', id);
        } else {
          addToZone('filters', id);
        }
      } else if (kind === 'metric') {
        addToZone('values', id);
      } else {
        addToZone('segment', id);
      }
    }
    function updateFilter(index, filter) {
      state.filters.splice(index, 1, filter);
    }
    function removeFilter(index) {
      state.filters.splice(index, 1);
    }
    // saving
    const saveReportType = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => reportTypeOf(state.visualization));
    const saveDefinition = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      if (saveType.value === 'sql') {
        return {
          sql: sql.value
        };
      }
      const def = Object.assign({}, definition.value);
      delete def.report_type;
      def.chart = state.visualization === 'bar' || state.visualization === 'pie' ? state.visualization : '';
      return def;
    });
    function openSave(type) {
      notice.value = '';
      saveType.value = type;
      saveOpen.value = true;
    }
    function onSaved(event) {
      noticeContext.value = 'success';
      notice.value = event.isNew ? Object(external_CoreHome_["translate"])('DataExplorer_ReportCreated') : Object(external_CoreHome_["translate"])('DataExplorer_ReportUpdated');
      // the reporting menu is loaded once per page, a new report page would wait for a reload
      external_CoreHome_["ReportingMenuStore"].reloadMenuItems();
      loadReports().then(() => {
        const report = reports.value.find(item => item.idreport === event.idReport);
        if (report) {
          editing.value = report;
          lastEncoded = '';
          syncHash();
        }
      });
    }
    function onSaveClosed() {
      Object(external_commonjs_vue_commonjs2_vue_root_Vue_["nextTick"])(() => {
        const trigger = saveType.value === 'sql' ? document.querySelector('.dataExplorerSql__save') : saveButton.value;
        if (trigger) {
          trigger.focus();
        }
      });
    }
    // lifecycle
    const stopWatchers = [];
    // A new period, date or segment makes the reporting page render the Explore page again, which
    // reloads the widget and remounts the explorer. The explorer already follows those URL
    // parameters, so it takes over the reload of its own page and only runs the query again.
    function keepPageOnUrlChange(args) {
      const parsed = external_CoreHome_["MatomoUrl"].parsed.value;
      if (args.category === parsed.category && args.subcategory === parsed.subcategory && args.subcategory === EXPLORE_SUBCATEGORY) {
        args.promise = Promise.resolve();
      }
    }
    Object(external_commonjs_vue_commonjs2_vue_root_Vue_["onMounted"])(() => {
      external_CoreHome_["Matomo"].on('ReportingPage.loadPage', keepPageOnUrlChange);
      Promise.all([external_CoreHome_["AjaxHelper"].fetch({
        method: 'DataExplorer.getCatalog',
        idSite
      }, {
        createErrorNotification: false
      }).then(result => {
        catalog.value = result;
      }).catch(e => {
        catalogError.value = e.message;
      }), loadReports()]).then(() => {
        readHash();
        stopWatchers.push(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["watch"])(() => [JSON.stringify(requestDefinition.value), period.value, date.value, segment.value], () => scheduleRun()));
        stopWatchers.push(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["watch"])(() => JSON.stringify(state), () => syncHash()));
        stopWatchers.push(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["watch"])(() => [external_CoreHome_["MatomoUrl"].parsed.value[HASH_PARAM], external_CoreHome_["MatomoUrl"].parsed.value[REPORT_HASH_PARAM]], () => readHash()));
        run();
      });
    });
    Object(external_commonjs_vue_commonjs2_vue_root_Vue_["onBeforeUnmount"])(() => {
      external_CoreHome_["Matomo"].off('ReportingPage.loadPage', keepPageOnUrlChange);
      stopWatchers.forEach(stop => stop());
      if (runTimer) {
        clearTimeout(runTimer);
      }
      if (runController) {
        runController.abort();
      }
    });
    return {
      periodText,
      segmentText,
      openPeriodSelector,
      idSite,
      period,
      date,
      catalog,
      catalogError,
      editing,
      state,
      sql,
      activeTab,
      sqlOpened,
      tabs,
      loading,
      runError,
      notice,
      noticeContext,
      saveOpen,
      saveType,
      saveButton,
      canSaveAnything,
      dimensionGroups,
      metricGroups,
      segmentGroups,
      dimensionViews,
      metricViews,
      segmentViews,
      rowItems,
      columnItems,
      valueItems,
      segmentItems,
      filterViews,
      hint,
      isRunnable,
      canvasTitle,
      metricNames,
      resultDimensionNames,
      resultColumnsList,
      resultRows,
      slice,
      barSeries,
      evolution,
      kpi,
      openableReports,
      onPickReport,
      selectTab,
      addVariable,
      removeVariable,
      addToZone,
      removeFromZone,
      useVariable,
      updateFilter,
      removeFilter,
      saveReportType,
      saveDefinition,
      openSave,
      onSaved,
      onSaveClosed
    };
  }
}));
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/Explorer.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/Explorer.vue



Explorervue_type_script_lang_ts.render = Explorervue_type_template_id_720d1144_render

/* harmony default export */ var Explorer = (Explorervue_type_script_lang_ts);
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Setup/ReadOnlySetup.vue?vue&type=template&id=60b448be

const ReadOnlySetupvue_type_template_id_60b448be_hoisted_1 = {
  class: "dataExplorerSetup"
};
const ReadOnlySetupvue_type_template_id_60b448be_hoisted_2 = {
  class: "dataExplorerSetup__text"
};
const ReadOnlySetupvue_type_template_id_60b448be_hoisted_3 = {
  key: 0,
  class: "dataExplorerSetup__code"
};
const ReadOnlySetupvue_type_template_id_60b448be_hoisted_4 = {
  class: "dataExplorerSetup__text"
};
const ReadOnlySetupvue_type_template_id_60b448be_hoisted_5 = {
  key: 0,
  class: "dataExplorerSetup__text"
};
const ReadOnlySetupvue_type_template_id_60b448be_hoisted_6 = {
  key: 1,
  class: "dataExplorerSetup__code"
};
const ReadOnlySetupvue_type_template_id_60b448be_hoisted_7 = {
  class: "dataExplorerSetup__text"
};
const ReadOnlySetupvue_type_template_id_60b448be_hoisted_8 = {
  class: "dataExplorerSetup__code"
};
function ReadOnlySetupvue_type_template_id_60b448be_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Notification = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Notification");
  const _component_ContentBlock = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("ContentBlock");
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", ReadOnlySetupvue_type_template_id_60b448be_hoisted_1, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_ContentBlock, {
    "content-title": _ctx.translate('DataExplorer_SetupTitle')
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", ReadOnlySetupvue_type_template_id_60b448be_hoisted_2, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_SetupIntro')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Notification, {
      context: _ctx.notificationContext,
      noclear: true
    }, {
      default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.status.message), 1)]),
      _: 1
    }, 8, ["context"]), _ctx.status.state === 'missing_grants' && _ctx.missingGrantLines.length ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("pre", ReadOnlySetupvue_type_template_id_60b448be_hoisted_3, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.missingGrantLines.join('\n')), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]),
    _: 1
  }, 8, ["content-title"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_ContentBlock, {
    "content-title": _ctx.translate('DataExplorer_SetupCreateUserTitle')
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", ReadOnlySetupvue_type_template_id_60b448be_hoisted_4, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_SetupCreateUserHelp')), 1), _ctx.isAnyHost ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", ReadOnlySetupvue_type_template_id_60b448be_hoisted_5, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_SetupAnyHostHelp')), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.hasTables ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("pre", ReadOnlySetupvue_type_template_id_60b448be_hoisted_6, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.sqlSnippet), 1)) : (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_Notification, {
      key: 2,
      context: "warning",
      noclear: true
    }, {
      default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_SetupNoTables')), 1)]),
      _: 1
    }))]),
    _: 1
  }, 8, ["content-title"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_ContentBlock, {
    "content-title": _ctx.translate('DataExplorer_SetupConfigTitle')
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", ReadOnlySetupvue_type_template_id_60b448be_hoisted_7, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_SetupConfigHelp')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("pre", ReadOnlySetupvue_type_template_id_60b448be_hoisted_8, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.iniBlock), 1)]),
    _: 1
  }, 8, ["content-title"])]);
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Setup/ReadOnlySetup.vue?vue&type=template&id=60b448be

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Setup/ReadOnlySetup.vue?vue&type=script&lang=ts


const CONTEXTS = {
  ready: 'success',
  not_configured: 'info',
  missing_grants: 'warning'
};
/**
 * Admin page of the read-only database user that raw SQL runs with: its status, the SQL that
 * creates it and the config.ini.php block that declares it.
 */
/* harmony default export */ var ReadOnlySetupvue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  name: 'ReadOnlySetup',
  components: {
    ContentBlock: external_CoreHome_["ContentBlock"],
    Notification: external_CoreHome_["Notification"]
  },
  props: {
    status: {
      type: Object,
      required: true
    },
    sqlSnippet: {
      type: String,
      default: ''
    },
    iniBlock: {
      type: String,
      default: ''
    },
    missingGrantLines: {
      type: Array,
      default: () => []
    },
    isAnyHost: {
      type: Boolean,
      default: false
    },
    hasTables: {
      type: Boolean,
      default: false
    }
  },
  setup(props) {
    const notificationContext = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => CONTEXTS[props.status.state] || 'error');
    return {
      notificationContext
    };
  }
}));
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Setup/ReadOnlySetup.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Setup/ReadOnlySetup.vue



ReadOnlySetupvue_type_script_lang_ts.render = ReadOnlySetupvue_type_template_id_60b448be_render

/* harmony default export */ var ReadOnlySetup = (ReadOnlySetupvue_type_script_lang_ts);
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Manage/ReportsManager.vue?vue&type=template&id=5adf789d

const ReportsManagervue_type_template_id_5adf789d_hoisted_1 = {
  class: "dataExplorerManage"
};
const ReportsManagervue_type_template_id_5adf789d_hoisted_2 = {
  class: "dataExplorerManage__intro"
};
const ReportsManagervue_type_template_id_5adf789d_hoisted_3 = {
  class: "dataExplorerManage__search"
};
const ReportsManagervue_type_template_id_5adf789d_hoisted_4 = {
  class: "first"
};
const ReportsManagervue_type_template_id_5adf789d_hoisted_5 = {
  class: "dataExplorerManage__actions"
};
const ReportsManagervue_type_template_id_5adf789d_hoisted_6 = {
  key: 0
};
const ReportsManagervue_type_template_id_5adf789d_hoisted_7 = {
  colspan: "8"
};
const ReportsManagervue_type_template_id_5adf789d_hoisted_8 = {
  key: 1
};
const ReportsManagervue_type_template_id_5adf789d_hoisted_9 = {
  colspan: "8"
};
const ReportsManagervue_type_template_id_5adf789d_hoisted_10 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("br", null, null, -1);
const ReportsManagervue_type_template_id_5adf789d_hoisted_11 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("br", null, null, -1);
const ReportsManagervue_type_template_id_5adf789d_hoisted_12 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("br", null, null, -1);
const ReportsManagervue_type_template_id_5adf789d_hoisted_13 = {
  class: "first"
};
const ReportsManagervue_type_template_id_5adf789d_hoisted_14 = {
  class: "entityTable_ActionCell entityTable_ActionCell-3 dataExplorerManage__actions"
};
const ReportsManagervue_type_template_id_5adf789d_hoisted_15 = ["href", "title", "aria-label"];
const ReportsManagervue_type_template_id_5adf789d_hoisted_16 = ["href", "title", "aria-label"];
const ReportsManagervue_type_template_id_5adf789d_hoisted_17 = ["title", "aria-label", "onClick"];
const ReportsManagervue_type_template_id_5adf789d_hoisted_18 = {
  class: "tableActionBar"
};
const ReportsManagervue_type_template_id_5adf789d_hoisted_19 = ["href"];
const ReportsManagervue_type_template_id_5adf789d_hoisted_20 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "icon-add",
  "aria-hidden": "true"
}, null, -1);
const ReportsManagervue_type_template_id_5adf789d_hoisted_21 = {
  class: "ui-confirm"
};
const ReportsManagervue_type_template_id_5adf789d_hoisted_22 = ["value"];
const ReportsManagervue_type_template_id_5adf789d_hoisted_23 = ["value"];
function ReportsManagervue_type_template_id_5adf789d_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Notification = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Notification");
  const _component_Field = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Field");
  const _component_ActivityIndicator = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("ActivityIndicator");
  const _component_MatomoDialog = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("MatomoDialog");
  const _directive_content_table = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveDirective"])("content-table");
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", ReportsManagervue_type_template_id_5adf789d_hoisted_1, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", ReportsManagervue_type_template_id_5adf789d_hoisted_2, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_ManageIntro')), 1), _ctx.error ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_Notification, {
    key: 0,
    context: "error",
    noclear: true
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.error), 1)]),
    _: 1
  })) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.notice ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_Notification, {
    key: 1,
    context: "success",
    noclear: true
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.notice), 1)]),
    _: 1
  })) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", ReportsManagervue_type_template_id_5adf789d_hoisted_3, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
    modelValue: _ctx.search,
    "onUpdate:modelValue": _cache[0] || (_cache[0] = $event => _ctx.search = $event),
    uicontrol: "text",
    name: "dataExplorerManageSearch",
    title: _ctx.translate('General_Search')
  }, null, 8, ["modelValue", "title"]), [[external_commonjs_vue_commonjs2_vue_root_Vue_["vShow"], _ctx.reports.length > 0]])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])((Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("table", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("thead", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("tr", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", ReportsManagervue_type_template_id_5adf789d_hoisted_4, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Id')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Name')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_ManageColumnType')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_ManageColumnLocation')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_SaveSites')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_ManageColumnAuthor')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_ManageColumnUpdated')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", ReportsManagervue_type_template_id_5adf789d_hoisted_5, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Actions')), 1)])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("tbody", null, [_ctx.loading ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("tr", ReportsManagervue_type_template_id_5adf789d_hoisted_6, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", ReportsManagervue_type_template_id_5adf789d_hoisted_7, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_ActivityIndicator, {
    loading: true
  })])])) : !_ctx.rows.length ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("tr", ReportsManagervue_type_template_id_5adf789d_hoisted_8, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", ReportsManagervue_type_template_id_5adf789d_hoisted_9, [ReportsManagervue_type_template_id_5adf789d_hoisted_10, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(" " + Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.reports.length ? _ctx.translate('DataExplorer_ManageNoMatch') : _ctx.translate('DataExplorer_ManageEmpty')) + " ", 1), ReportsManagervue_type_template_id_5adf789d_hoisted_11, ReportsManagervue_type_template_id_5adf789d_hoisted_12])])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.rows, report => {
    return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("tr", {
      key: report.idreport
    }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", ReportsManagervue_type_template_id_5adf789d_hoisted_13, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(report.idreport), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(report.name), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.typeText(report)), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.locationText(report)), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.sitesText(report)), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(report.created_by || _ctx.translate('DataExplorer_ManageAuthorDeleted')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(report.updated_date || report.created_date), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", ReportsManagervue_type_template_id_5adf789d_hoisted_14, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
      class: "table-action icon-show",
      href: _ctx.reportUrl(report),
      title: _ctx.translate('DataExplorer_ManageView', report.name),
      "aria-label": _ctx.translate('DataExplorer_ManageView', report.name)
    }, null, 8, ReportsManagervue_type_template_id_5adf789d_hoisted_15), _ctx.canOpen(report) ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("a", {
      key: 0,
      class: "table-action icon-edit",
      href: _ctx.explorerUrl(report),
      title: _ctx.translate('DataExplorer_ManageEdit', report.name),
      "aria-label": _ctx.translate('DataExplorer_ManageEdit', report.name)
    }, null, 8, ReportsManagervue_type_template_id_5adf789d_hoisted_16)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), report.editable ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("button", {
      key: 1,
      type: "button",
      class: "table-action icon-delete",
      title: _ctx.translate('DataExplorer_ManageDelete', report.name),
      "aria-label": _ctx.translate('DataExplorer_ManageDelete', report.name),
      onClick: $event => _ctx.askDelete(report)
    }, null, 8, ReportsManagervue_type_template_id_5adf789d_hoisted_17)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)])]);
  }), 128))])])), [[_directive_content_table]]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", ReportsManagervue_type_template_id_5adf789d_hoisted_18, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    class: "dataExplorerManage__create",
    href: _ctx.explorerUrl(null)
  }, [ReportsManagervue_type_template_id_5adf789d_hoisted_20, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(" " + Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_NewExploration')), 1)], 8, ReportsManagervue_type_template_id_5adf789d_hoisted_19)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_MatomoDialog, {
    modelValue: _ctx.confirmOpen,
    "onUpdate:modelValue": _cache[1] || (_cache[1] = $event => _ctx.confirmOpen = $event),
    onYes: _ctx.confirmDelete
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", ReportsManagervue_type_template_id_5adf789d_hoisted_21, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h2", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_ManageDeleteConfirm', _ctx.toDelete ? _ctx.toDelete.name : '')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("input", {
      role: "yes",
      type: "button",
      value: _ctx.translate('General_Yes')
    }, null, 8, ReportsManagervue_type_template_id_5adf789d_hoisted_22), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("input", {
      role: "no",
      type: "button",
      value: _ctx.translate('General_No')
    }, null, 8, ReportsManagervue_type_template_id_5adf789d_hoisted_23)])]),
    _: 1
  }, 8, ["modelValue", "onYes"])]);
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Manage/ReportsManager.vue?vue&type=template&id=5adf789d

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Manage/ReportsManager.vue?vue&type=script&lang=ts




const EXPLORE_CATEGORY = 'DataExplorer_DataExplorer';
/**
 * "Data Explorer > Manage explorations": the reports saved on this website, with links to their
 * page and to the explorer, and deletion for the users who can edit them.
 */
/* harmony default export */ var ReportsManagervue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  name: 'ReportsManager',
  components: {
    ActivityIndicator: external_CoreHome_["ActivityIndicator"],
    Field: external_CorePluginsAdmin_["Field"],
    MatomoDialog: external_CoreHome_["MatomoDialog"],
    Notification: external_CoreHome_["Notification"]
  },
  directives: {
    ContentTable: external_CoreHome_["ContentTable"]
  },
  props: {
    isSuperUser: {
      type: Boolean,
      default: false
    }
  },
  setup(props) {
    const idSite = Number(external_CoreHome_["Matomo"].idSite);
    const reports = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])([]);
    const categories = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])([]);
    const loading = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(true);
    const error = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('');
    const notice = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('');
    const search = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('');
    const confirmOpen = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(false);
    const toDelete = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    function load() {
      loading.value = true;
      return Promise.all([external_CoreHome_["AjaxHelper"].fetch({
        method: 'DataExplorer.getReports',
        idSite,
        filter_limit: -1
      }, {
        createErrorNotification: false
      }), external_CoreHome_["AjaxHelper"].fetch({
        method: 'DataExplorer.getCategories',
        idSite,
        filter_limit: -1
      }, {
        createErrorNotification: false
      }).catch(() => [])]).then(([loadedReports, loadedCategories]) => {
        reports.value = loadedReports;
        categories.value = loadedCategories;
      }).catch(e => {
        error.value = e.message;
      }).finally(() => {
        loading.value = false;
      });
    }
    const rows = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      const needle = search.value.trim().toLowerCase();
      if (!needle) {
        return reports.value;
      }
      return reports.value.filter(report => report.name.toLowerCase().indexOf(needle) !== -1);
    });
    function typeText(report) {
      const technique = report.type === 'sql' ? Object(external_CoreHome_["translate"])('DataExplorer_SqlTab') : Object(external_CoreHome_["translate"])('DataExplorer_ExploreTab');
      return `${technique} > ${Object(external_CoreHome_["translate"])(`DataExplorer_ReportType_${report.report_type}`)}`;
    }
    function locationText(report) {
      const categoryId = report.category || EXPLORE_CATEGORY;
      const category = categories.value.find(item => item.id === categoryId);
      const subcategory = category ? category.subcategories.find(item => item.id === report.subcategory) : undefined;
      const categoryName = category ? category.name : categoryId;
      return `${categoryName} > ${subcategory ? subcategory.name : report.subcategory}`;
    }
    function sitesText(report) {
      if (report.placement.all) {
        return Object(external_CoreHome_["translate"])('DataExplorer_PlacementAll');
      }
      if (report.placement.sites.length === 1 && report.placement.sites[0] === idSite) {
        return Object(external_CoreHome_["translate"])('DataExplorer_PlacementThisSite');
      }
      return Object(external_CoreHome_["translate"])('DataExplorer_ManageSitesCount', String(report.placement.sites.length));
    }
    function pageUrl(params) {
      const query = external_CoreHome_["MatomoUrl"].stringify(Object.assign({
        idSite: String(idSite),
        period: String(external_CoreHome_["MatomoUrl"].parsed.value.period || external_CoreHome_["Matomo"].period),
        date: String(external_CoreHome_["MatomoUrl"].parsed.value.date || external_CoreHome_["Matomo"].currentDateString)
      }, params));
      return `?${external_CoreHome_["MatomoUrl"].stringify(external_CoreHome_["MatomoUrl"].urlParsed.value)}#?${query}`;
    }
    function reportUrl(report) {
      return pageUrl({
        category: report.category || EXPLORE_CATEGORY,
        subcategory: report.subcategory
      });
    }
    function explorerUrl(report) {
      const params = {
        category: EXPLORE_CATEGORY,
        subcategory: EXPLORE_SUBCATEGORY
      };
      if (report) {
        params[REPORT_HASH_PARAM] = String(report.idreport);
      }
      return pageUrl(params);
    }
    function canOpen(report) {
      return report.editable && (report.type === 'visual' || props.isSuperUser);
    }
    function askDelete(report) {
      notice.value = '';
      toDelete.value = report;
      confirmOpen.value = true;
    }
    function confirmDelete() {
      const report = toDelete.value;
      if (!report) {
        return;
      }
      external_CoreHome_["AjaxHelper"].post({
        method: 'DataExplorer.deleteReport'
      }, {
        idReport: report.idreport
      }, {
        createErrorNotification: false
      }).then(() => {
        notice.value = Object(external_CoreHome_["translate"])('DataExplorer_ManageDeleted', report.name);
        external_CoreHome_["ReportingMenuStore"].reloadMenuItems();
        return load();
      }).catch(e => {
        error.value = e.message;
      });
    }
    Object(external_commonjs_vue_commonjs2_vue_root_Vue_["onMounted"])(() => {
      load();
    });
    return {
      reports,
      rows,
      loading,
      error,
      notice,
      search,
      confirmOpen,
      toDelete,
      typeText,
      locationText,
      sitesText,
      reportUrl,
      explorerUrl,
      canOpen,
      askDelete,
      confirmDelete
    };
  }
}));
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Manage/ReportsManager.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Manage/ReportsManager.vue



ReportsManagervue_type_script_lang_ts.render = ReportsManagervue_type_template_id_5adf789d_render

/* harmony default export */ var ReportsManager = (ReportsManagervue_type_script_lang_ts);
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/index.ts
/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */











// CONCATENATED MODULE: ./node_modules/@vue/cli-service/lib/commands/build/entry-lib-no-default.js




/***/ })

/******/ });
});
//# sourceMappingURL=DataExplorer.umd.js.map