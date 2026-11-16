(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory(require("vue"), require("tslib"));
	else if(typeof define === 'function' && define.amd)
		define([, "tslib"], factory);
	else if(typeof exports === 'object')
		exports["DataExplorer"] = factory(require("vue"), require("tslib"));
	else
		root["DataExplorer"] = factory(root["Vue"], root["tslib"]);
})((typeof self !== 'undefined' ? self : this), function(__WEBPACK_EXTERNAL_MODULE__8bbf__, __WEBPACK_EXTERNAL_MODULE_d7bc__) {
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
/******/ 		return __webpack_require__.p + "DataExplorer.umd." + ({"1":"echarts","2":"vendors~echarts"}[chunkId]||chunkId) + ".js"
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

/***/ "8bbf":
/***/ (function(module, exports) {

module.exports = __WEBPACK_EXTERNAL_MODULE__8bbf__;

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
    modules = Promise.all(/* import() | echarts */[__webpack_require__.e(2), __webpack_require__.e(1)]).then(__webpack_require__.bind(null, "540c"));
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
function cssVariable(style, name, fallback) {
  const value = style.getPropertyValue(name).trim();
  return value || fallback;
}
/**
 * Colors of the active Matomo theme (light, dark or custom), read from its CSS variables with
 * the core defaults as fallback for Matomo versions that do not expose them.
 */
function chartTheme() {
  const style = window.getComputedStyle(document.documentElement);
  const base = cssVariable(style, '--theme-color-base-series', FALLBACK_SERIES[0]);
  return {
    // read here so that computed chart options follow theme mode changes
    version: themeVersion.value,
    series: [base, ...FALLBACK_SERIES.filter(color => color.toLowerCase() !== base.toLowerCase())],
    text: cssVariable(style, '--theme-color-text', '#212121'),
    textLight: cssVariable(style, '--theme-color-text-light', '#444444'),
    border: cssVariable(style, '--theme-color-border-light', '#dddddd'),
    background: cssVariable(style, '--theme-color-background-base', '#ffffff'),
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
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Charts/EChartsBar.vue?vue&type=template&id=65e2a4d3

const EChartsBarvue_type_template_id_65e2a4d3_hoisted_1 = ["aria-label"];
function EChartsBarvue_type_template_id_65e2a4d3_render(_ctx, _cache, $props, $setup, $data, $options) {
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
    ref: "chart",
    class: "dataExplorerChart__canvas",
    role: "img",
    "aria-label": _ctx.metricName
  }, null, 8, EChartsBarvue_type_template_id_65e2a4d3_hoisted_1);
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Charts/EChartsBar.vue?vue&type=template&id=65e2a4d3

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Charts/EChartsBar.vue?vue&type=script&lang=ts


/**
 * First metric of a table, top rows plus "Others" (DataExplorerBar visualization).
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
          bottom: 8,
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
        series: [{
          name: props.metricName,
          type: 'bar',
          data: props.values
        }]
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



EChartsBarvue_type_script_lang_ts.render = EChartsBarvue_type_template_id_65e2a4d3_render

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