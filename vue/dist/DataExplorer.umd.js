(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory(require("CoreHome"), require("vue"), require("CorePluginsAdmin"));
	else if(typeof define === 'function' && define.amd)
		define(["CoreHome", , "CorePluginsAdmin"], factory);
	else if(typeof exports === 'object')
		exports["DataExplorer"] = factory(require("CoreHome"), require("vue"), require("CorePluginsAdmin"));
	else
		root["DataExplorer"] = factory(root["CoreHome"], root["Vue"], root["CorePluginsAdmin"]);
})((typeof self !== 'undefined' ? self : this), function(__WEBPACK_EXTERNAL_MODULE__19dc__, __WEBPACK_EXTERNAL_MODULE__8bbf__, __WEBPACK_EXTERNAL_MODULE_a5a2__) {
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
/******/ 		return __webpack_require__.p + "DataExplorer.umd." + ({"1":"codemirror","2":"vendors~codemirror"}[chunkId]||chunkId) + ".js"
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

/***/ "fae3":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
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
__webpack_require__.d(__webpack_exports__, "VIEW_BY_VISUALIZATION", function() { return /* reexport */ VIEW_BY_VISUALIZATION; });
__webpack_require__.d(__webpack_exports__, "VISUALIZATION_BY_VIEW", function() { return /* reexport */ VISUALIZATION_BY_VIEW; });
__webpack_require__.d(__webpack_exports__, "FILTER_OPERATORS", function() { return /* reexport */ FILTER_OPERATORS; });
__webpack_require__.d(__webpack_exports__, "DEFAULT_DIMENSIONS", function() { return /* reexport */ DEFAULT_DIMENSIONS; });
__webpack_require__.d(__webpack_exports__, "DEFAULT_METRICS", function() { return /* reexport */ DEFAULT_METRICS; });
__webpack_require__.d(__webpack_exports__, "defaultState", function() { return /* reexport */ defaultState; });
__webpack_require__.d(__webpack_exports__, "reportTypeOf", function() { return /* reexport */ reportTypeOf; });
__webpack_require__.d(__webpack_exports__, "hasRowDimensions", function() { return /* reexport */ hasRowDimensions; });
__webpack_require__.d(__webpack_exports__, "groupFilters", function() { return /* reexport */ groupFilters; });
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

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Explorer/Explorer.vue?vue&type=template&id=1fa11188

const _hoisted_1 = {
  class: "dataExplorer"
};
const _hoisted_2 = {
  class: "dataExplorer__toolbar"
};
const _hoisted_3 = ["aria-label"];
const _hoisted_4 = ["id", "data-tab", "aria-selected", "aria-controls", "onClick"];
const _hoisted_5 = {
  class: "dataExplorer__reports"
};
const _hoisted_6 = {
  class: "dataExplorer__reportsLabel"
};
const _hoisted_7 = {
  key: 0,
  class: "dataExplorer__modified"
};
const _hoisted_8 = ["disabled"];
const _hoisted_9 = ["disabled"];
const _hoisted_10 = {
  "aria-live": "polite"
};
const _hoisted_11 = ["href"];
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
const _hoisted_15 = {
  class: "ui-confirm"
};
const _hoisted_16 = ["value"];
const _hoisted_17 = ["value"];
function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_ReportPicker = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("ReportPicker");
  const _component_Notification = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Notification");
  const _component_ActivityIndicator = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("ActivityIndicator");
  const _component_VariablesPanel = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("VariablesPanel");
  const _component_TabSettings = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("TabSettings");
  const _component_Canvas = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Canvas");
  const _component_RawSqlEditor = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("RawSqlEditor");
  const _component_MatomoDialog = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("MatomoDialog");
  const _component_SaveDialog = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("SaveDialog");
  const _component_CustomReportDialog = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("CustomReportDialog");
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", _hoisted_1, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_2, [_ctx.isSuperUser ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
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
    }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(tab.label), 11, _hoisted_4);
  }), 128))], 8, _hoisted_3)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_5, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", _hoisted_6, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_OpenReport')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_ReportPicker, {
    "model-value": _ctx.pickedReport,
    groups: _ctx.reportGroups,
    "new-label": _ctx.translate('DataExplorer_NewExploration'),
    label: _ctx.translate('DataExplorer_OpenReport'),
    "onUpdate:modelValue": _cache[0] || (_cache[0] = $event => _ctx.onPickReport($event))
  }, null, 8, ["model-value", "groups", "new-label", "label"]), _ctx.isModified ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("span", _hoisted_7, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_Modified')), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), _ctx.canSaveAnything && _ctx.activeTab === 'explore' ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("button", {
    key: 1,
    ref: "saveButton",
    type: "button",
    class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["btn dataExplorer__save", {
      'btn-outline': !!_ctx.updatableCustomReport
    }]),
    disabled: !_ctx.isRunnable,
    onClick: _cache[1] || (_cache[1] = $event => _ctx.openSave('visual'))
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.isEditingVisual ? _ctx.translate('DataExplorer_EditReport') : _ctx.translate('DataExplorer_SaveReport')), 11, _hoisted_8)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.customReports && _ctx.activeTab === 'explore' ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("button", {
    key: 2,
    type: "button",
    class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["btn dataExplorer__saveCustomReport", {
      'btn-outline': !_ctx.updatableCustomReport
    }]),
    disabled: !_ctx.isRunnable,
    onClick: _cache[2] || (_cache[2] = $event => _ctx.customReportOpen = true)
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate(_ctx.updatableCustomReport ? 'DataExplorer_UpdateCustomReport' : 'DataExplorer_SaveAsCustomReport')), 11, _hoisted_9)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_10, [_ctx.notice ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_Notification, {
    key: 0,
    context: _ctx.noticeContext,
    noclear: true
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.notice), 1), _ctx.noticeLink && _ctx.noticeLink.notice === _ctx.notice ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("a", {
      key: 0,
      href: _ctx.noticeLink.url
    }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.noticeLink.label), 9, _hoisted_11)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]),
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
    "row-items": _ctx.rowZoneItems,
    "column-items": _ctx.columnItems,
    "value-items": _ctx.valueZoneItems,
    "segment-items": _ctx.segmentItems,
    filters: _ctx.filterViews,
    "dimension-options": _ctx.dimensionViews,
    "metric-options": _ctx.metricViews,
    "segment-options": _ctx.segmentViews,
    onSetVisualization: _cache[3] || (_cache[3] = $event => _ctx.state.visualization = $event),
    onAdd: _ctx.addToZone,
    onRemove: _ctx.removeFromZone,
    "table-view": _ctx.state.tableView,
    onSetTableView: _cache[4] || (_cache[4] = $event => _ctx.state.tableView = $event),
    onAddFilterToGroup: _ctx.addFilterToGroup,
    onMoveFilter: _ctx.moveFilter,
    onUpdateFilter: _ctx.updateFilter,
    onRemoveFilter: _ctx.removeFilter
  }, null, 8, ["visualization", "limits", "row-items", "column-items", "value-items", "segment-items", "filters", "dimension-options", "metric-options", "segment-options", "onAdd", "onRemove", "table-view", "onAddFilterToGroup", "onMoveFilter", "onUpdateFilter", "onRemoveFilter"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Canvas, {
    title: _ctx.canvasTitle,
    "period-text": _ctx.periodText,
    "period-dates": _ctx.periodDates,
    "segment-text": _ctx.segmentText,
    onOpenPeriodSelector: _ctx.openPeriodSelector,
    visualization: _ctx.state.visualization,
    hint: _ctx.hint,
    "widget-params": _ctx.previewParams,
    onViewChanged: _ctx.onCanvasView
  }, null, 8, ["title", "period-text", "period-dates", "segment-text", "onOpenPeriodSelector", "visualization", "hint", "widget-params", "onViewChanged"])])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)], 8, _hoisted_12), [[external_commonjs_vue_commonjs2_vue_root_Vue_["vShow"], _ctx.activeTab === 'explore']]), _ctx.isSuperUser && _ctx.sqlOpened ? Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])((Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", _hoisted_14, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_RawSqlEditor, {
    modelValue: _ctx.sql,
    "onUpdate:modelValue": _cache[5] || (_cache[5] = $event => _ctx.sql = $event),
    "id-site": _ctx.idSite,
    period: _ctx.period,
    date: _ctx.date,
    "setup-url": _ctx.setupUrl,
    "can-save": true,
    editing: _ctx.isEditingSql,
    visualization: _ctx.sqlVisualization,
    onSetVisualization: _cache[6] || (_cache[6] = $event => _ctx.sqlVisualization = $event),
    onSave: _cache[7] || (_cache[7] = $event => _ctx.openSave('sql'))
  }, null, 8, ["modelValue", "id-site", "period", "date", "setup-url", "editing", "visualization"])], 512)), [[external_commonjs_vue_commonjs2_vue_root_Vue_["vShow"], _ctx.activeTab === 'sql']]) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_MatomoDialog, {
    modelValue: _ctx.leaveConfirmOpen,
    "onUpdate:modelValue": _cache[8] || (_cache[8] = $event => _ctx.leaveConfirmOpen = $event),
    onYes: _ctx.confirmLeave
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_15, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h2", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_LeaveUnsavedConfirm')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("input", {
      role: "yes",
      type: "button",
      value: _ctx.translate('General_Yes')
    }, null, 8, _hoisted_16), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("input", {
      role: "no",
      type: "button",
      value: _ctx.translate('General_No')
    }, null, 8, _hoisted_17)])]),
    _: 1
  }, 8, ["modelValue", "onYes"]), _ctx.canSaveAnything ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_SaveDialog, {
    key: 1,
    modelValue: _ctx.saveOpen,
    "onUpdate:modelValue": _cache[9] || (_cache[9] = $event => _ctx.saveOpen = $event),
    "id-site": _ctx.idSite,
    "is-super-user": _ctx.isSuperUser,
    "writable-sites": _ctx.writableSites,
    type: _ctx.saveType,
    "report-type": _ctx.saveReportType,
    definition: _ctx.saveDefinition,
    editing: _ctx.editing && _ctx.editing.type === _ctx.saveType ? _ctx.editing : null,
    suggestion: _ctx.saveSuggestion,
    modified: _ctx.isModified,
    onSaved: _ctx.onSaved,
    onClosed: _ctx.onSaveClosed
  }, null, 8, ["modelValue", "id-site", "is-super-user", "writable-sites", "type", "report-type", "definition", "editing", "suggestion", "modified", "onSaved", "onClosed"])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.customReports ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_CustomReportDialog, {
    key: 2,
    modelValue: _ctx.customReportOpen,
    "onUpdate:modelValue": _cache[10] || (_cache[10] = $event => _ctx.customReportOpen = $event),
    definition: _ctx.definition,
    "default-name": _ctx.editing ? _ctx.editing.name : '',
    "default-description": _ctx.editing ? String(_ctx.editing.definition.description || '') : '',
    "custom-report": _ctx.updatableCustomReport,
    modified: _ctx.isModified,
    onCreated: _ctx.onCustomReportCreated,
    onUpdated: _ctx.onCustomReportUpdated,
    onFailed: _ctx.onCustomReportFailed
  }, null, 8, ["modelValue", "definition", "default-name", "default-description", "custom-report", "modified", "onCreated", "onUpdated", "onFailed"])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]);
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/Explorer.vue?vue&type=template&id=1fa11188

// EXTERNAL MODULE: external "CoreHome"
var external_CoreHome_ = __webpack_require__("19dc");

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Explorer/Canvas.vue?vue&type=template&id=4114a50a

const Canvasvue_type_template_id_4114a50a_hoisted_1 = {
  class: "dataExplorerColumn dataExplorerCanvas"
};
const Canvasvue_type_template_id_4114a50a_hoisted_2 = {
  class: "dataExplorerCanvas__header"
};
const Canvasvue_type_template_id_4114a50a_hoisted_3 = {
  class: "dataExplorerCanvas__tab"
};
const Canvasvue_type_template_id_4114a50a_hoisted_4 = {
  class: "dataExplorerCanvas__title"
};
const Canvasvue_type_template_id_4114a50a_hoisted_5 = {
  key: 0,
  class: "dataExplorerCanvas__context"
};
const Canvasvue_type_template_id_4114a50a_hoisted_6 = ["title"];
const Canvasvue_type_template_id_4114a50a_hoisted_7 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "icon-calendar",
  "aria-hidden": "true"
}, null, -1);
const Canvasvue_type_template_id_4114a50a_hoisted_8 = {
  class: "dataExplorerCanvas__pill"
};
const Canvasvue_type_template_id_4114a50a_hoisted_9 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "icon-segment",
  "aria-hidden": "true"
}, null, -1);
const Canvasvue_type_template_id_4114a50a_hoisted_10 = {
  class: "dataExplorerCanvas__status",
  "aria-live": "polite"
};
const Canvasvue_type_template_id_4114a50a_hoisted_11 = {
  key: 0,
  class: "dataExplorerCanvas__hint"
};
const Canvasvue_type_template_id_4114a50a_hoisted_12 = {
  key: 0,
  ref: "stage",
  class: "dataExplorerCanvas__stage"
};
function Canvasvue_type_template_id_4114a50a_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_WidgetLoader = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("WidgetLoader");
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", Canvasvue_type_template_id_4114a50a_hoisted_1, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", Canvasvue_type_template_id_4114a50a_hoisted_2, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", Canvasvue_type_template_id_4114a50a_hoisted_3, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
    class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["dataExplorerCanvas__tabIcon", _ctx.tabIcon]),
    "aria-hidden": "true"
  }, null, 2), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h3", Canvasvue_type_template_id_4114a50a_hoisted_4, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.title), 1)]), _ctx.periodText ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", Canvasvue_type_template_id_4114a50a_hoisted_5, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
    type: "button",
    class: "dataExplorerCanvas__pill dataExplorerCanvas__period",
    title: _ctx.translate('General_ChooseDate', _ctx.periodDates || _ctx.periodText),
    onClick: _cache[0] || (_cache[0] = $event => _ctx.$emit('openPeriodSelector'))
  }, [Canvasvue_type_template_id_4114a50a_hoisted_7, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.periodText), 1)], 8, Canvasvue_type_template_id_4114a50a_hoisted_6), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", Canvasvue_type_template_id_4114a50a_hoisted_8, [Canvasvue_type_template_id_4114a50a_hoisted_9, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.segmentText), 1)])])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", Canvasvue_type_template_id_4114a50a_hoisted_10, [_ctx.hint ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", Canvasvue_type_template_id_4114a50a_hoisted_11, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.hint), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), !_ctx.hint && _ctx.widgetParams ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", Canvasvue_type_template_id_4114a50a_hoisted_12, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_WidgetLoader, {
    class: "dataExplorerCanvas__widget",
    "widget-params": _ctx.widgetParams
  }, null, 8, ["widget-params"])], 512)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]);
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/Canvas.vue?vue&type=template&id=4114a50a

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
// the core visualization of each chart, and back
const VIEW_BY_VISUALIZATION = {
  table: 'table',
  bar: 'graphVerticalBar',
  pie: 'graphPie',
  line: 'graphEvolution',
  kpicard: 'sparklines'
};
// the table views of core footers that all read as a table here
const VISUALIZATION_BY_VIEW = {
  table: 'table',
  tableAllColumns: 'table',
  tableGoals: 'table',
  graphVerticalBar: 'bar',
  graphPie: 'pie',
  graphEvolution: 'line',
  sparklines: 'kpicard'
};
const FILTER_OPERATORS = ['equals', 'not_equals', 'contains', 'not_contains', 'starts_with', 'not_starts_with', 'ends_with', 'not_ends_with', 'regex', 'gt', 'gte', 'lt', 'lte'];
// a shared link is user input: bound every list so a crafted hash cannot blow up the page
const MAX_LIST_LENGTH = 100;
// a new exploration starts with these variables, ready to drag
const DEFAULT_DIMENSIONS = ['Actions.PageUrl', 'DevicesDetection.DeviceType'];
const DEFAULT_METRICS = ['nb_visits', 'pageviews'];
function emptyState() {
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
    tableView: 'flat',
    visualization: 'table',
    segment: ''
  };
}
function defaultState() {
  const state = emptyState();
  state.variables.dimensions = [...DEFAULT_DIMENSIONS];
  state.variables.metrics = [...DEFAULT_METRICS];
  return state;
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
/**
 * Filters ordered by group, groups numbered from 0 in their order of appearance, as the server
 * stores them.
 */
function groupFilters(filters) {
  const order = new Map();
  filters.forEach(filter => {
    if (!order.has(filter.group)) {
      order.set(filter.group, order.size);
    }
  });
  return filters.map(filter => Object.assign(Object.assign({}, filter), {}, {
    group: order.get(filter.group)
  })).sort((a, b) => a.group - b.group);
}
/**
 * Filters of a link or a definition. Without groups (saved before them), "any" puts every filter
 * in one group and anything else each filter in its own.
 */
function filterList(value, legacyMatch) {
  if (!Array.isArray(value)) {
    return [];
  }
  const result = [];
  let legacy = true;
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
    const hasGroup = typeof raw.group === 'number' && Number.isInteger(raw.group) && raw.group >= 0;
    legacy = legacy && !hasGroup;
    result.push({
      dimension: raw.dimension,
      operator,
      value: filterValue,
      group: hasGroup ? raw.group : result.length
    });
  });
  if (legacy && legacyMatch === 'any') {
    result.forEach(filter => {
      filter.group = 0;
    });
  }
  return groupFilters(result.slice(0, MAX_LIST_LENGTH));
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
  const state = emptyState();
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
  state.filters = filterList(value.filters, value.filterMatch);
  state.tableView = value.tableView === 'hierarchical' ? 'hierarchical' : 'flat';
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
    filters: groupFilters(state.filters.filter(isFilterComplete)),
    table_view: state.tableView,
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
    tableView: definition.table_view,
    segment: definition.segment,
    visualization
  });
}
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Explorer/Canvas.vue?vue&type=script&lang=ts



/**
 * Right column: the exploration in the native Matomo visualization of its report type, loaded
 * from DataExplorer.preview like the saved report it would become.
 */
/* harmony default export */ var Canvasvue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  name: 'Canvas',
  components: {
    WidgetLoader: external_CoreHome_["WidgetLoader"]
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
    // the dates of the period, when the pill shows its name (Last 28 days)
    periodDates: {
      type: String,
      default: ''
    },
    visualization: {
      type: String,
      required: true
    },
    hint: {
      type: String,
      default: ''
    },
    widgetParams: {
      type: Object,
      default: null
    }
  },
  emits: ['openPeriodSelector', 'viewChanged'],
  setup(props, {
    emit
  }) {
    const tabIcon = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => VISUALIZATION_ICONS[props.visualization]);
    const stage = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    // the core footer (visualization icons, "Make it flat") reloads the table by itself: its new
    // view and layout are read from the DataTable instance once loaded, so Report settings follow
    function onLoaded(event) {
      const table = $(event.target).closest('.dataTable');
      // core triggers the event before it attaches the DataTable instance to the element
      setTimeout(() => {
        const control = table.data('uiControlObject');
        if (!control || !control.param) {
          return;
        }
        emit('viewChanged', {
          viewDataTable: String(control.param.viewDataTable || ''),
          flat: String(control.param.flat || '0') === '1'
        });
      });
    }
    let bound = null;
    function bind(element) {
      if (bound) {
        $(bound).off('piwik:dataTableLoaded', onLoaded);
      }
      bound = element;
      if (bound) {
        $(bound).on('piwik:dataTableLoaded', onLoaded);
      }
    }
    Object(external_commonjs_vue_commonjs2_vue_root_Vue_["watch"])(stage, bind);
    Object(external_commonjs_vue_commonjs2_vue_root_Vue_["onBeforeUnmount"])(() => bind(null));
    return {
      stage,
      tabIcon,
      translate: external_CoreHome_["translate"]
    };
  }
}));
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/Canvas.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/Canvas.vue



Canvasvue_type_script_lang_ts.render = Canvasvue_type_template_id_4114a50a_render

/* harmony default export */ var Canvas = (Canvasvue_type_script_lang_ts);
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Explorer/TabSettings.vue?vue&type=template&id=0319f18d

const TabSettingsvue_type_template_id_0319f18d_hoisted_1 = {
  class: "dataExplorerColumn dataExplorerSettings"
};
const TabSettingsvue_type_template_id_0319f18d_hoisted_2 = {
  class: "dataExplorerColumn__title"
};
const TabSettingsvue_type_template_id_0319f18d_hoisted_3 = {
  class: "dataExplorerSettings__section"
};
const TabSettingsvue_type_template_id_0319f18d_hoisted_4 = {
  id: "dataExplorerVisualizationTitle"
};
const TabSettingsvue_type_template_id_0319f18d_hoisted_5 = {
  class: "dataExplorerVisualizations",
  role: "group",
  "aria-labelledby": "dataExplorerVisualizationTitle"
};
const TabSettingsvue_type_template_id_0319f18d_hoisted_6 = ["data-visualization", "aria-pressed", "aria-label", "title", "onClick"];
const TabSettingsvue_type_template_id_0319f18d_hoisted_7 = {
  key: 0,
  class: "dataExplorerSettings__section"
};
const TabSettingsvue_type_template_id_0319f18d_hoisted_8 = {
  class: "dataExplorerSettings__tableView"
};
const TabSettingsvue_type_template_id_0319f18d_hoisted_9 = {
  id: "dataExplorerTableViewTitle"
};
const TabSettingsvue_type_template_id_0319f18d_hoisted_10 = {
  class: "dataExplorerSegmented",
  role: "radiogroup",
  "aria-labelledby": "dataExplorerTableViewTitle"
};
const TabSettingsvue_type_template_id_0319f18d_hoisted_11 = ["aria-checked", "onClick"];
const TabSettingsvue_type_template_id_0319f18d_hoisted_12 = {
  key: 1,
  class: "dataExplorerSettings__hint"
};
const TabSettingsvue_type_template_id_0319f18d_hoisted_13 = {
  key: 0,
  class: "dataExplorerFilterGroup__join",
  "aria-hidden": "true"
};
const TabSettingsvue_type_template_id_0319f18d_hoisted_14 = ["onDragover", "onDrop"];
const TabSettingsvue_type_template_id_0319f18d_hoisted_15 = ["aria-label"];
const TabSettingsvue_type_template_id_0319f18d_hoisted_16 = {
  key: 0,
  class: "dataExplorerFilters__or",
  "aria-hidden": "true"
};
const TabSettingsvue_type_template_id_0319f18d_hoisted_17 = {
  class: "dataExplorerFilterGroup__actions"
};
const _hoisted_18 = {
  key: 1,
  class: "dataExplorerFilterSummary"
};
function TabSettingsvue_type_template_id_0319f18d_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_DropZone = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("DropZone");
  const _component_FilterRow = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("FilterRow");
  const _component_FilterOrButton = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("FilterOrButton");
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", TabSettingsvue_type_template_id_0319f18d_hoisted_1, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h3", TabSettingsvue_type_template_id_0319f18d_hoisted_2, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_TabSettings')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", TabSettingsvue_type_template_id_0319f18d_hoisted_3, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h4", TabSettingsvue_type_template_id_0319f18d_hoisted_4, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_Visualization')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", TabSettingsvue_type_template_id_0319f18d_hoisted_5, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.visualizations, option => {
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
    }, null, 2)], 10, TabSettingsvue_type_template_id_0319f18d_hoisted_6);
  }), 128))])]), _ctx.visualization === 'table' && _ctx.rowItems.length > 1 ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", TabSettingsvue_type_template_id_0319f18d_hoisted_7, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", TabSettingsvue_type_template_id_0319f18d_hoisted_8, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h4", TabSettingsvue_type_template_id_0319f18d_hoisted_9, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_TableView')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", TabSettingsvue_type_template_id_0319f18d_hoisted_10, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.tableViews, view => {
    return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("button", {
      key: view.id,
      type: "button",
      role: "radio",
      class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["dataExplorerSegmented__button", {
        'is-active': _ctx.tableView === view.id
      }]),
      "aria-checked": _ctx.tableView === view.id ? 'true' : 'false',
      onClick: $event => _ctx.$emit('setTableView', view.id)
    }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(view.label), 11, TabSettingsvue_type_template_id_0319f18d_hoisted_11);
  }), 128))])])])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_DropZone, {
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
  }, null, 8, ["title", "items", "options", "max", "active-count", "inactive-reason", "placeholder", "empty-menu-text"]), _ctx.inactiveValueReason && _ctx.valueItems.length > 1 ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", TabSettingsvue_type_template_id_0319f18d_hoisted_12, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.inactiveValueReason), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_DropZone, {
    zone: "filters",
    accepts: "dimension",
    title: _ctx.translate('DataExplorer_Filters'),
    options: _ctx.dimensionOptions,
    unlimited: "",
    placeholder: _ctx.translate('DataExplorer_DropFilter'),
    "empty-menu-text": _ctx.translate('DataExplorer_NoDimensions'),
    onAdd: _cache[13] || (_cache[13] = $event => _ctx.$emit('add', 'filters', $event))
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.filterGroups, (group, groupIndex) => {
      return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], {
        key: groupIndex
      }, [groupIndex > 0 ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", TabSettingsvue_type_template_id_0319f18d_hoisted_13, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_FilterAnd')), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", {
        class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["dataExplorerFilterGroup", {
          'is-drop-target': _ctx.overGroup === group[0].filter.group
        }]),
        onDragover: $event => _ctx.onGroupDragOver($event, group[0].filter.group),
        onDragleave: _cache[9] || (_cache[9] = $event => _ctx.overGroup = null),
        onDrop: $event => _ctx.onGroupDrop($event, group[0].filter.group)
      }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("ul", {
        class: "dataExplorerFilters",
        "aria-label": group.length > 1 ? _ctx.translate('DataExplorer_FilterGroupAnyLabel', groupIndex + 1) : _ctx.translate('DataExplorer_FilterGroupLabel', groupIndex + 1)
      }, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(group, (view, position) => {
        return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], {
          key: _ctx.filterKey(view.filter)
        }, [position > 0 ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("li", TabSettingsvue_type_template_id_0319f18d_hoisted_16, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_FilterOr')), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_FilterRow, {
          filter: view.filter,
          "dimension-name": view.name,
          reason: view.reason,
          draggable: "true",
          class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])({
            'is-dragged': _ctx.draggedIndex === view.index
          }),
          onDragstart: $event => _ctx.startFilterDrag($event, view.index),
          onDragend: _ctx.endFilterDrag,
          onUpdate: $event => _ctx.$emit('updateFilter', view.index, $event),
          onRemove: $event => _ctx.$emit('removeFilter', view.index)
        }, null, 8, ["filter", "dimension-name", "reason", "class", "onDragstart", "onDragend", "onUpdate", "onRemove"])], 64);
      }), 128))], 8, TabSettingsvue_type_template_id_0319f18d_hoisted_15), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", TabSettingsvue_type_template_id_0319f18d_hoisted_17, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_FilterOrButton, {
        options: _ctx.dimensionOptions,
        onAdd: $event => _ctx.$emit('addFilterToGroup', group[0].filter.group, $event)
      }, null, 8, ["options", "onAdd"]), groupIndex === _ctx.filterGroups.length - 1 ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_FilterOrButton, {
        key: 0,
        options: _ctx.dimensionOptions,
        label: _ctx.translate('DataExplorer_FilterAddAnd'),
        "aria-label": _ctx.translate('DataExplorer_FilterAddAndLabel'),
        onAdd: _cache[8] || (_cache[8] = $event => _ctx.$emit('add', 'filters', $event))
      }, null, 8, ["options", "label", "aria-label"])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)])], 42, TabSettingsvue_type_template_id_0319f18d_hoisted_14)], 64);
    }), 128)), _ctx.draggedIndex !== null ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
      key: 0,
      class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["dataExplorerFilterGroup__newDrop", {
        'is-drop-target': _ctx.overGroup === _ctx.NEW_GROUP
      }]),
      onDragover: _cache[10] || (_cache[10] = $event => _ctx.onGroupDragOver($event, _ctx.NEW_GROUP)),
      onDragleave: _cache[11] || (_cache[11] = $event => _ctx.overGroup = null),
      onDrop: _cache[12] || (_cache[12] = $event => _ctx.onGroupDrop($event, _ctx.NEW_GROUP))
    }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_FilterDropNewGroup')), 35)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.filterSummary ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("p", _hoisted_18, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.filterSummary), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]),
    _: 1
  }, 8, ["title", "options", "placeholder", "empty-menu-text"])]);
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/TabSettings.vue?vue&type=template&id=0319f18d

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Explorer/DropZone.vue?vue&type=template&id=39ddc976

const DropZonevue_type_template_id_39ddc976_hoisted_1 = ["data-zone", "data-kind"];
const DropZonevue_type_template_id_39ddc976_hoisted_2 = {
  class: "dataExplorerDropZone__header"
};
const DropZonevue_type_template_id_39ddc976_hoisted_3 = ["id"];
const DropZonevue_type_template_id_39ddc976_hoisted_4 = {
  key: 0,
  class: "dataExplorerDropZone__count"
};
const DropZonevue_type_template_id_39ddc976_hoisted_5 = {
  key: 0,
  class: "dataExplorerDropZone__hint"
};
const DropZonevue_type_template_id_39ddc976_hoisted_6 = ["aria-labelledby"];
const DropZonevue_type_template_id_39ddc976_hoisted_7 = ["data-id", "title", "onKeydown"];
const DropZonevue_type_template_id_39ddc976_hoisted_8 = {
  key: 0,
  class: "icon-warning",
  "aria-hidden": "true"
};
const DropZonevue_type_template_id_39ddc976_hoisted_9 = {
  key: 1,
  class: "icon-info",
  "aria-hidden": "true"
};
const DropZonevue_type_template_id_39ddc976_hoisted_10 = {
  class: "dataExplorerChip__name"
};
const DropZonevue_type_template_id_39ddc976_hoisted_11 = {
  key: 2,
  class: "dataExplorerVisuallyHidden"
};
const DropZonevue_type_template_id_39ddc976_hoisted_12 = ["aria-label", "title", "onClick"];
const DropZonevue_type_template_id_39ddc976_hoisted_13 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "icon-close",
  "aria-hidden": "true"
}, null, -1);
const DropZonevue_type_template_id_39ddc976_hoisted_14 = [DropZonevue_type_template_id_39ddc976_hoisted_13];
const DropZonevue_type_template_id_39ddc976_hoisted_15 = {
  key: 1,
  class: "dataExplorerDropZone__slot"
};
const DropZonevue_type_template_id_39ddc976_hoisted_16 = ["aria-expanded"];
const DropZonevue_type_template_id_39ddc976_hoisted_17 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "icon-add",
  "aria-hidden": "true"
}, null, -1);
const DropZonevue_type_template_id_39ddc976_hoisted_18 = ["disabled", "title", "onClick"];
const _hoisted_19 = {
  key: 0,
  class: "dataExplorerDropZone__menuEmpty"
};
function DropZonevue_type_template_id_39ddc976_render(_ctx, _cache, $props, $setup, $data, $options) {
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
  }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", DropZonevue_type_template_id_39ddc976_hoisted_2, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h4", {
    id: _ctx.headingId
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.title), 9, DropZonevue_type_template_id_39ddc976_hoisted_3), _ctx.max > 1 ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("span", DropZonevue_type_template_id_39ddc976_hoisted_4, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_ZoneCount', _ctx.usedCount, _ctx.max)), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), _ctx.disabled && _ctx.disabledReason ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", DropZonevue_type_template_id_39ddc976_hoisted_5, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.disabledReason), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("ul", {
    class: "dataExplorerDropZone__chips",
    "aria-labelledby": _ctx.headingId
  }, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.items, (item, index) => {
    return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("li", {
      key: item.id,
      ref_for: true,
      ref: "chips",
      class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["dataExplorerChip", {
        'is-incompatible': !!item.reason,
        'is-inactive': _ctx.isInactive(index),
        'is-missing': !!item.missing
      }]),
      "data-id": item.id,
      tabindex: "0",
      title: item.reason || item.hint || (_ctx.isInactive(index) ? _ctx.inactiveReason : _ctx.translate('DataExplorer_ChipRemoveHint')),
      onKeydown: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withKeys"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])($event => _ctx.remove(item.id, index), ["self", "prevent"]), ["delete"])
    }, [item.reason ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("span", DropZonevue_type_template_id_39ddc976_hoisted_8)) : item.hint ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("span", DropZonevue_type_template_id_39ddc976_hoisted_9)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", DropZonevue_type_template_id_39ddc976_hoisted_10, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(item.name), 1), item.reason || item.hint ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("span", DropZonevue_type_template_id_39ddc976_hoisted_11, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(item.reason || item.hint), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
      type: "button",
      class: "dataExplorerIconButton dataExplorerChip__remove",
      "aria-label": _ctx.translate('DataExplorer_RemoveFromZone', item.name, _ctx.title),
      title: _ctx.translate('DataExplorer_RemoveFromZone', item.name, _ctx.title),
      onClick: $event => _ctx.remove(item.id, index)
    }, DropZonevue_type_template_id_39ddc976_hoisted_14, 8, DropZonevue_type_template_id_39ddc976_hoisted_12)], 42, DropZonevue_type_template_id_39ddc976_hoisted_7);
  }), 128))], 8, DropZonevue_type_template_id_39ddc976_hoisted_6), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderSlot"])(_ctx.$slots, "default"), !_ctx.isFull && !_ctx.disabled ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", DropZonevue_type_template_id_39ddc976_hoisted_15, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
    ref: "slotButton",
    type: "button",
    class: "dataExplorerDropZone__select",
    "aria-expanded": _ctx.menuOpen ? 'true' : 'false',
    onClick: _cache[0] || (_cache[0] = $event => _ctx.menuOpen = !_ctx.menuOpen)
  }, [DropZonevue_type_template_id_39ddc976_hoisted_17, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.placeholder), 1)], 8, DropZonevue_type_template_id_39ddc976_hoisted_16), _ctx.menuOpen ? Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])((Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("ul", {
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
    }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(option.name), 9, DropZonevue_type_template_id_39ddc976_hoisted_18)]);
  }), 128)), !_ctx.options.length ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("li", _hoisted_19, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.emptyMenuText), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)], 32)), [[_directive_focus_anywhere_but_here, {
    blur: _ctx.closeMenu
  }]]) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)], 42, DropZonevue_type_template_id_39ddc976_hoisted_1);
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/DropZone.vue?vue&type=template&id=39ddc976

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
    // items shown struck through take no place
    const usedCount = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => props.items.filter(item => !item.missing).length);
    const isFull = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => !props.unlimited && props.max > 0 && usedCount.value >= props.max);
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
      usedCount,
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



DropZonevue_type_script_lang_ts.render = DropZonevue_type_template_id_39ddc976_render

/* harmony default export */ var DropZone = (DropZonevue_type_script_lang_ts);
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Explorer/FilterRow.vue?vue&type=template&id=09e63f18

const FilterRowvue_type_template_id_09e63f18_hoisted_1 = ["data-dimension"];
const FilterRowvue_type_template_id_09e63f18_hoisted_2 = {
  class: "dataExplorerFilter__header"
};
const FilterRowvue_type_template_id_09e63f18_hoisted_3 = ["title", "aria-expanded"];
const FilterRowvue_type_template_id_09e63f18_hoisted_4 = {
  key: 0,
  class: "icon-warning",
  "aria-hidden": "true"
};
const FilterRowvue_type_template_id_09e63f18_hoisted_5 = {
  class: "dataExplorerFilter__name"
};
const FilterRowvue_type_template_id_09e63f18_hoisted_6 = {
  key: 1,
  class: "dataExplorerFilter__condition"
};
const FilterRowvue_type_template_id_09e63f18_hoisted_7 = ["aria-label", "title"];
const FilterRowvue_type_template_id_09e63f18_hoisted_8 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "icon-close",
  "aria-hidden": "true"
}, null, -1);
const FilterRowvue_type_template_id_09e63f18_hoisted_9 = [FilterRowvue_type_template_id_09e63f18_hoisted_8];
const FilterRowvue_type_template_id_09e63f18_hoisted_10 = {
  key: 0,
  class: "dataExplorerFilter__reason"
};
const FilterRowvue_type_template_id_09e63f18_hoisted_11 = {
  class: "dataExplorerFilter__label"
};
const FilterRowvue_type_template_id_09e63f18_hoisted_12 = {
  class: "dataExplorerFilter__actions"
};
const FilterRowvue_type_template_id_09e63f18_hoisted_13 = ["disabled"];
function FilterRowvue_type_template_id_09e63f18_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Field = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Field");
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("li", {
    class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["dataExplorerFilter", {
      'is-incompatible': !!_ctx.reason,
      'is-editing': _ctx.editing
    }]),
    "data-dimension": _ctx.filter.dimension
  }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", FilterRowvue_type_template_id_09e63f18_hoisted_2, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
    type: "button",
    class: "dataExplorerFilter__summary",
    title: _ctx.reason || _ctx.translate('DataExplorer_FilterEdit', _ctx.dimensionName),
    "aria-expanded": _ctx.editing ? 'true' : 'false',
    onClick: _cache[0] || (_cache[0] = (...args) => _ctx.toggle && _ctx.toggle(...args))
  }, [_ctx.reason ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("span", FilterRowvue_type_template_id_09e63f18_hoisted_4)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", FilterRowvue_type_template_id_09e63f18_hoisted_5, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.dimensionName), 1), _ctx.filter.value ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("span", FilterRowvue_type_template_id_09e63f18_hoisted_6, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.operatorLabel(_ctx.filter.operator)) + " " + Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.filter.value), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)], 8, FilterRowvue_type_template_id_09e63f18_hoisted_3), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
    type: "button",
    class: "dataExplorerIconButton",
    "aria-label": _ctx.translate('DataExplorer_RemoveFilter', _ctx.dimensionName),
    title: _ctx.translate('DataExplorer_RemoveFilter', _ctx.dimensionName),
    onClick: _cache[1] || (_cache[1] = $event => _ctx.$emit('remove'))
  }, FilterRowvue_type_template_id_09e63f18_hoisted_9, 8, FilterRowvue_type_template_id_09e63f18_hoisted_7)]), _ctx.reason ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", FilterRowvue_type_template_id_09e63f18_hoisted_10, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.reason), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.editing ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
    key: 1,
    class: "dataExplorerFilter__fields",
    onKeydown: [_cache[6] || (_cache[6] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withKeys"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])((...args) => _ctx.apply && _ctx.apply(...args), ["prevent"]), ["enter"])), _cache[7] || (_cache[7] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withKeys"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])((...args) => _ctx.cancel && _ctx.cancel(...args), ["stop", "prevent"]), ["esc"]))]
  }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", FilterRowvue_type_template_id_09e63f18_hoisted_11, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_FilterConditions')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
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
  }, null, 8, ["name", "placeholder", "model-value", "ui-control-attributes"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", FilterRowvue_type_template_id_09e63f18_hoisted_12, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
    type: "button",
    class: "btn-flat",
    onClick: _cache[4] || (_cache[4] = (...args) => _ctx.cancel && _ctx.cancel(...args))
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Cancel')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
    type: "button",
    class: "btn",
    disabled: !_ctx.draft.value.trim(),
    onClick: _cache[5] || (_cache[5] = (...args) => _ctx.apply && _ctx.apply(...args))
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_FilterApply')), 9, FilterRowvue_type_template_id_09e63f18_hoisted_13)])], 32)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)], 10, FilterRowvue_type_template_id_09e63f18_hoisted_1);
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/FilterRow.vue?vue&type=template&id=09e63f18

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
    const isNumeric = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => ['gt', 'gte', 'lt', 'lte'].includes(draft.operator));
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



FilterRowvue_type_script_lang_ts.render = FilterRowvue_type_template_id_09e63f18_render

/* harmony default export */ var FilterRow = (FilterRowvue_type_script_lang_ts);
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Explorer/FilterOrButton.vue?vue&type=template&id=0402f181

const FilterOrButtonvue_type_template_id_0402f181_hoisted_1 = {
  class: "dataExplorerFilterOr"
};
const FilterOrButtonvue_type_template_id_0402f181_hoisted_2 = ["aria-expanded", "aria-label"];
const FilterOrButtonvue_type_template_id_0402f181_hoisted_3 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "icon-add",
  "aria-hidden": "true"
}, null, -1);
const FilterOrButtonvue_type_template_id_0402f181_hoisted_4 = ["disabled", "title", "onClick"];
const FilterOrButtonvue_type_template_id_0402f181_hoisted_5 = {
  key: 0,
  class: "dataExplorerDropZone__menuEmpty"
};
function FilterOrButtonvue_type_template_id_0402f181_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _directive_focus_anywhere_but_here = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveDirective"])("focus-anywhere-but-here");
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", FilterOrButtonvue_type_template_id_0402f181_hoisted_1, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
    ref: "button",
    type: "button",
    class: "dataExplorerFilterOr__button",
    "aria-expanded": _ctx.menuOpen ? 'true' : 'false',
    "aria-label": _ctx.ariaLabel || _ctx.translate('DataExplorer_FilterAddOrLabel'),
    onClick: _cache[0] || (_cache[0] = $event => _ctx.menuOpen = !_ctx.menuOpen)
  }, [FilterOrButtonvue_type_template_id_0402f181_hoisted_3, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.label || _ctx.translate('DataExplorer_FilterAddOr')), 1)], 8, FilterOrButtonvue_type_template_id_0402f181_hoisted_2), _ctx.menuOpen ? Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])((Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("ul", {
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
    }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(option.name), 9, FilterOrButtonvue_type_template_id_0402f181_hoisted_4)]);
  }), 128)), !_ctx.options.length ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("li", FilterOrButtonvue_type_template_id_0402f181_hoisted_5, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_NoDimensions')), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)], 32)), [[_directive_focus_anywhere_but_here, {
    blur: _ctx.closeMenu
  }]]) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]);
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/FilterOrButton.vue?vue&type=template&id=0402f181

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Explorer/FilterOrButton.vue?vue&type=script&lang=ts


/**
 * "+ Or" under a group of filters (the dimension picked is ORed with the others), or "+ And" for
 * a new group ANDed with them.
 */
/* harmony default export */ var FilterOrButtonvue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  name: 'FilterOrButton',
  props: {
    options: {
      type: Array,
      default: () => []
    },
    // "Or" by default; "And" for the button that starts a new group
    label: {
      type: String,
      default: ''
    },
    ariaLabel: {
      type: String,
      default: ''
    }
  },
  emits: ['add'],
  directives: {
    FocusAnywhereButHere: external_CoreHome_["FocusAnywhereButHere"]
  },
  setup(props, {
    emit
  }) {
    const menuOpen = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(false);
    const button = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    function closeMenu(restoreFocus = false) {
      menuOpen.value = false;
      if (restoreFocus === true && button.value) {
        button.value.focus();
      }
    }
    function choose(id) {
      menuOpen.value = false;
      emit('add', id);
    }
    return {
      menuOpen,
      button,
      closeMenu,
      choose
    };
  }
}));
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/FilterOrButton.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/FilterOrButton.vue



FilterOrButtonvue_type_script_lang_ts.render = FilterOrButtonvue_type_template_id_0402f181_render

/* harmony default export */ var FilterOrButton = (FilterOrButtonvue_type_script_lang_ts);
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Explorer/TabSettings.vue?vue&type=script&lang=ts






/**
 * Middle column: visualization switcher and the drop zones of the exploration.
 */
/* harmony default export */ var TabSettingsvue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  name: 'TabSettings',
  components: {
    DropZone: DropZone,
    FilterRow: FilterRow,
    FilterOrButton: FilterOrButton
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
    tableView: {
      type: String,
      default: 'flat'
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
  emits: ['setVisualization', 'add', 'remove', 'updateFilter', 'removeFilter', 'addFilterToGroup', 'moveFilter', 'setTableView'],
  setup(props, {
    emit
  }) {
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
    // the filters of a group are ORed, the groups ANDed
    const filterGroups = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      const groups = new Map();
      props.filters.forEach(view => {
        const group = groups.get(view.filter.group) || [];
        group.push(view);
        groups.set(view.filter.group, group);
      });
      return [...groups.values()];
    });
    // "(Country is France or Belgium) and (Device type is Smartphone)", filters with a value only
    const filterSummary = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      const parts = filterGroups.value.map(group => group.filter(view => view.filter.value.trim() !== '')).filter(group => group.length > 0).map(group => {
        const text = group.map(view => {
          const operator = Object(external_CoreHome_["translate"])(`DataExplorer_FilterOperator_${view.filter.operator}`);
          return `${view.name} ${operator} ${view.filter.value}`;
        }).join(` ${Object(external_CoreHome_["translate"])('DataExplorer_FilterOr')} `);
        return group.length > 1 ? `(${text})` : text;
      });
      return parts.length > 1 || parts.length === 1 && parts[0].startsWith('(') ? parts.join(` ${Object(external_CoreHome_["translate"])('DataExplorer_FilterAnd')} `) : '';
    });
    // a filter dragged onto another group joins it (OR), onto the strip below the groups it starts
    // a new one (AND)
    const NEW_GROUP = -1;
    const FILTER_TYPE = 'application/x-dataexplorer-filter';
    const draggedIndex = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    const overGroup = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    function startFilterDrag(event, index) {
      draggedIndex.value = index;
      if (event.dataTransfer) {
        event.dataTransfer.setData(FILTER_TYPE, String(index));
        event.dataTransfer.effectAllowed = 'move';
      }
    }
    function endFilterDrag() {
      draggedIndex.value = null;
      overGroup.value = null;
    }
    function onGroupDragOver(event, group) {
      if (draggedIndex.value === null) {
        return;
      }
      event.preventDefault();
      event.stopPropagation();
      if (event.dataTransfer) {
        event.dataTransfer.dropEffect = 'move';
      }
      overGroup.value = group;
    }
    function onGroupDrop(event, group) {
      if (draggedIndex.value === null) {
        return;
      }
      event.preventDefault();
      event.stopPropagation();
      emit('moveFilter', draggedIndex.value, group);
      endFilterDrag();
    }
    // a filter keeps its row (and an open editor) when a filter is inserted or removed before it
    const keys = new WeakMap();
    let nextKey = 0;
    function filterKey(filter) {
      if (!keys.has(filter)) {
        nextKey += 1;
        keys.set(filter, nextKey);
      }
      return keys.get(filter);
    }
    const tableViews = [{
      id: 'flat',
      label: Object(external_CoreHome_["translate"])('DataExplorer_TableViewFlat')
    }, {
      id: 'hierarchical',
      label: Object(external_CoreHome_["translate"])('DataExplorer_TableViewHierarchical')
    }];
    return {
      tableViews,
      filterGroups,
      filterKey,
      filterSummary,
      NEW_GROUP,
      draggedIndex,
      overGroup,
      startFilterDrag,
      endFilterDrag,
      onGroupDragOver,
      onGroupDrop,
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



TabSettingsvue_type_script_lang_ts.render = TabSettingsvue_type_template_id_0319f18d_render

/* harmony default export */ var TabSettings = (TabSettingsvue_type_script_lang_ts);
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Explorer/ReportPicker.vue?vue&type=template&id=69ea3f39

const ReportPickervue_type_template_id_69ea3f39_hoisted_1 = ["aria-label", "aria-expanded"];
const ReportPickervue_type_template_id_69ea3f39_hoisted_2 = {
  class: "dataExplorerReportPicker__current"
};
const ReportPickervue_type_template_id_69ea3f39_hoisted_3 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "icon-chevron-down",
  "aria-hidden": "true"
}, null, -1);
const ReportPickervue_type_template_id_69ea3f39_hoisted_4 = ["id", "aria-label"];
const ReportPickervue_type_template_id_69ea3f39_hoisted_5 = ["id", "aria-selected"];
const ReportPickervue_type_template_id_69ea3f39_hoisted_6 = ["aria-label"];
const ReportPickervue_type_template_id_69ea3f39_hoisted_7 = {
  key: 0,
  class: "dataExplorerReportPicker__groupLabel",
  "aria-hidden": "true"
};
const ReportPickervue_type_template_id_69ea3f39_hoisted_8 = ["id", "aria-selected", "onMousemove", "onClick"];
const ReportPickervue_type_template_id_69ea3f39_hoisted_9 = {
  class: "dataExplorerReportPicker__name"
};
const ReportPickervue_type_template_id_69ea3f39_hoisted_10 = {
  key: 0,
  class: "dataExplorerReportPicker__place"
};
const ReportPickervue_type_template_id_69ea3f39_hoisted_11 = {
  key: 0,
  class: "dataExplorerReportPicker__empty"
};
function ReportPickervue_type_template_id_69ea3f39_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_SearchInput = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("SearchInput");
  const _directive_focus_anywhere_but_here = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveDirective"])("focus-anywhere-but-here");
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])((Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
    class: "dataExplorerReportPicker",
    onKeydown: _cache[9] || (_cache[9] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withKeys"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])($event => _ctx.close(true), ["stop", "prevent"]), ["esc"]))
  }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
    ref: "trigger",
    type: "button",
    class: "dataExplorerReportPicker__trigger",
    "aria-label": _ctx.label,
    "aria-expanded": _ctx.open ? 'true' : 'false',
    onClick: _cache[0] || (_cache[0] = (...args) => _ctx.toggle && _ctx.toggle(...args))
  }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", ReportPickervue_type_template_id_69ea3f39_hoisted_2, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.currentName), 1), ReportPickervue_type_template_id_69ea3f39_hoisted_3], 8, ReportPickervue_type_template_id_69ea3f39_hoisted_1), _ctx.open ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
    key: 0,
    class: "dataExplorerReportPicker__panel",
    onKeydown: [_cache[6] || (_cache[6] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withKeys"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])($event => _ctx.move(1), ["prevent"]), ["down"])), _cache[7] || (_cache[7] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withKeys"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])($event => _ctx.move(-1), ["prevent"]), ["up"])), _cache[8] || (_cache[8] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withKeys"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])((...args) => _ctx.pickActive && _ctx.pickActive(...args), ["prevent"]), ["enter"]))]
  }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_SearchInput, {
    ref: "search",
    modelValue: _ctx.query,
    "onUpdate:modelValue": _cache[1] || (_cache[1] = $event => _ctx.query = $event),
    placeholder: _ctx.translate('DataExplorer_ReportPickerSearch'),
    "aria-label": _ctx.translate('DataExplorer_ReportPickerSearch')
  }, null, 8, ["modelValue", "placeholder", "aria-label"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", {
    id: `${_ctx.uid}-list`,
    ref: "list",
    class: "dataExplorerReportPicker__list",
    role: "listbox",
    "aria-label": _ctx.label
  }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", {
    id: _ctx.optionId(''),
    role: "option",
    class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["dataExplorerReportPicker__item", {
      'is-selected': _ctx.modelValue === '',
      'is-active': _ctx.activeKey === ''
    }]),
    "aria-selected": _ctx.modelValue === '' ? 'true' : 'false',
    onMousedown: _cache[2] || (_cache[2] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(() => {}, ["prevent"])),
    onMousemove: _cache[3] || (_cache[3] = $event => _ctx.activeKey = ''),
    onClick: _cache[4] || (_cache[4] = $event => _ctx.pick(''))
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.newLabel), 43, ReportPickervue_type_template_id_69ea3f39_hoisted_5), (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.visibleGroups, (group, index) => {
    return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
      key: index,
      role: "group",
      "aria-label": group.label || undefined,
      class: "dataExplorerReportPicker__group"
    }, [group.label ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", ReportPickervue_type_template_id_69ea3f39_hoisted_7, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(group.label), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(group.items, item => {
      return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
        id: _ctx.optionId(item.key),
        key: item.key,
        role: "option",
        class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["dataExplorerReportPicker__item", {
          'is-selected': _ctx.modelValue === item.key,
          'is-active': _ctx.activeKey === item.key
        }]),
        "aria-selected": _ctx.modelValue === item.key ? 'true' : 'false',
        onMousedown: _cache[5] || (_cache[5] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(() => {}, ["prevent"])),
        onMousemove: $event => _ctx.activeKey = item.key,
        onClick: $event => _ctx.pick(item.key)
      }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", ReportPickervue_type_template_id_69ea3f39_hoisted_9, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(item.name), 1), item.place ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("span", ReportPickervue_type_template_id_69ea3f39_hoisted_10, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(item.place), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)], 42, ReportPickervue_type_template_id_69ea3f39_hoisted_8);
    }), 128))], 8, ReportPickervue_type_template_id_69ea3f39_hoisted_6);
  }), 128)), _ctx.query.trim() !== '' && !_ctx.visibleGroups.length ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", ReportPickervue_type_template_id_69ea3f39_hoisted_11, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_ManageNoMatch')), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)], 8, ReportPickervue_type_template_id_69ea3f39_hoisted_4)], 32)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)], 32)), [[_directive_focus_anywhere_but_here, {
    blur: _ctx.close
  }]]);
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/ReportPicker.vue?vue&type=template&id=69ea3f39

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Explorer/ReportPicker.vue?vue&type=script&lang=ts


let ReportPickervue_type_script_lang_ts_nextId = 0;
/**
 * The report the explorer opens: a new exploration, then the groups (saved explorations, custom
 * reports), filtered by a search on the name.
 */
/* harmony default export */ var ReportPickervue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  name: 'ReportPicker',
  components: {
    SearchInput: external_CoreHome_["SearchInput"]
  },
  directives: {
    FocusAnywhereButHere: external_CoreHome_["FocusAnywhereButHere"]
  },
  props: {
    modelValue: {
      type: String,
      default: ''
    },
    groups: {
      type: Array,
      default: () => []
    },
    newLabel: {
      type: String,
      required: true
    },
    label: {
      type: String,
      required: true
    }
  },
  emits: ['update:modelValue'],
  setup(props, {
    emit
  }) {
    const open = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(false);
    const query = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('');
    const trigger = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    const search = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    const list = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    // the option the arrow keys are on: the focus stays in the search, like a combobox
    const activeKey = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    ReportPickervue_type_script_lang_ts_nextId += 1;
    const uid = `dataExplorerReportPicker${ReportPickervue_type_script_lang_ts_nextId}`;
    const optionId = key => `${uid}-${key === '' ? 'new' : key}`;
    const currentName = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      const found = props.groups.flatMap(group => group.items).find(item => item.key === props.modelValue);
      return found ? found.name : props.newLabel;
    });
    const visibleGroups = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      const needle = query.value.trim().toLowerCase();
      return props.groups.map(group => ({
        label: group.label,
        items: needle === '' ? group.items : group.items.filter(item => item.name.toLowerCase().includes(needle))
      })).filter(group => group.items.length > 0);
    });
    const optionKeys = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => ['', ...visibleGroups.value.flatMap(group => group.items.map(item => item.key))]);
    function searchInput() {
      return search.value ? search.value.$el.querySelector('input') : null;
    }
    Object(external_commonjs_vue_commonjs2_vue_root_Vue_["watch"])(activeKey, key => {
      Object(external_commonjs_vue_commonjs2_vue_root_Vue_["nextTick"])(() => {
        const input = searchInput();
        if (input) {
          input.setAttribute('role', 'combobox');
          input.setAttribute('aria-controls', `${uid}-list`);
          input.setAttribute('aria-expanded', 'true');
          if (key === null) {
            input.removeAttribute('aria-activedescendant');
          } else {
            input.setAttribute('aria-activedescendant', optionId(key));
          }
        }
        const element = key === null ? null : document.getElementById(optionId(key));
        if (element) {
          element.scrollIntoView({
            block: 'nearest'
          });
        }
      });
    });
    Object(external_commonjs_vue_commonjs2_vue_root_Vue_["watch"])(query, () => {
      activeKey.value = null;
    });
    function move(step) {
      const keys = optionKeys.value;
      const position = activeKey.value === null ? -1 : keys.indexOf(activeKey.value);
      let next = (position + step + keys.length) % keys.length;
      if (position < 0) {
        next = step > 0 ? 0 : keys.length - 1;
      }
      activeKey.value = keys[next];
    }
    function close(restoreFocus = false) {
      if (!open.value) {
        return;
      }
      open.value = false;
      query.value = '';
      activeKey.value = null;
      if (restoreFocus === true && trigger.value) {
        trigger.value.focus();
      }
    }
    function toggle() {
      if (open.value) {
        close();
        return;
      }
      open.value = true;
      activeKey.value = props.modelValue;
      Object(external_commonjs_vue_commonjs2_vue_root_Vue_["nextTick"])(() => {
        const input = searchInput();
        if (input) {
          input.focus();
        }
      });
    }
    function pick(key) {
      close(true);
      emit('update:modelValue', key);
    }
    function pickActive() {
      if (activeKey.value !== null && optionKeys.value.includes(activeKey.value)) {
        pick(activeKey.value);
      }
    }
    return {
      open,
      query,
      trigger,
      search,
      list,
      uid,
      activeKey,
      optionId,
      move,
      pickActive,
      currentName,
      visibleGroups,
      close,
      toggle,
      pick
    };
  }
}));
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/ReportPicker.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/ReportPicker.vue



ReportPickervue_type_script_lang_ts.render = ReportPickervue_type_template_id_69ea3f39_render

/* harmony default export */ var ReportPicker = (ReportPickervue_type_script_lang_ts);
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

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Explorer/CataloguePanel.vue?vue&type=template&id=27b053aa

const CataloguePanelvue_type_template_id_27b053aa_hoisted_1 = ["aria-labelledby"];
const CataloguePanelvue_type_template_id_27b053aa_hoisted_2 = {
  class: "dataExplorerPanel__header"
};
const CataloguePanelvue_type_template_id_27b053aa_hoisted_3 = ["id"];
const CataloguePanelvue_type_template_id_27b053aa_hoisted_4 = ["aria-label", "title"];
const CataloguePanelvue_type_template_id_27b053aa_hoisted_5 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "icon-close",
  "aria-hidden": "true"
}, null, -1);
const CataloguePanelvue_type_template_id_27b053aa_hoisted_6 = [CataloguePanelvue_type_template_id_27b053aa_hoisted_5];
const CataloguePanelvue_type_template_id_27b053aa_hoisted_7 = {
  class: "dataExplorerPanel__search"
};
const CataloguePanelvue_type_template_id_27b053aa_hoisted_8 = {
  class: "dataExplorerPanel__count",
  "aria-live": "polite"
};
const CataloguePanelvue_type_template_id_27b053aa_hoisted_9 = {
  key: 0,
  class: "dataExplorerPanel__category"
};
const CataloguePanelvue_type_template_id_27b053aa_hoisted_10 = ["aria-expanded", "aria-controls", "onClick"];
const CataloguePanelvue_type_template_id_27b053aa_hoisted_11 = {
  class: "dataExplorerPanel__categoryName"
};
const CataloguePanelvue_type_template_id_27b053aa_hoisted_12 = {
  class: "dataExplorerPanel__categoryCount"
};
const CataloguePanelvue_type_template_id_27b053aa_hoisted_13 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "dataExplorerPanel__chevron icon-chevron-down",
  "aria-hidden": "true"
}, null, -1);
const CataloguePanelvue_type_template_id_27b053aa_hoisted_14 = ["id"];
const CataloguePanelvue_type_template_id_27b053aa_hoisted_15 = ["data-id", "title", "aria-pressed", "aria-describedby", "onClick"];
const CataloguePanelvue_type_template_id_27b053aa_hoisted_16 = {
  class: "dataExplorerPanel__name"
};
const CataloguePanelvue_type_template_id_27b053aa_hoisted_17 = {
  key: 0,
  class: "dataExplorerPanel__check icon-ok",
  "aria-hidden": "true"
};
const CataloguePanelvue_type_template_id_27b053aa_hoisted_18 = ["id"];
const CataloguePanelvue_type_template_id_27b053aa_hoisted_19 = {
  key: 0,
  class: "dataExplorerPanel__empty"
};
function CataloguePanelvue_type_template_id_27b053aa_render(_ctx, _cache, $props, $setup, $data, $options) {
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
    }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("header", CataloguePanelvue_type_template_id_27b053aa_hoisted_2, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h2", {
      id: _ctx.titleId,
      class: "dataExplorerPanel__title"
    }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.title), 9, CataloguePanelvue_type_template_id_27b053aa_hoisted_3), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
      type: "button",
      class: "dataExplorerIconButton dataExplorerPanel__close",
      "aria-label": _ctx.translate('General_Close'),
      title: _ctx.translate('General_Close'),
      onClick: _cache[1] || (_cache[1] = (...args) => _ctx.close && _ctx.close(...args))
    }, CataloguePanelvue_type_template_id_27b053aa_hoisted_6, 8, CataloguePanelvue_type_template_id_27b053aa_hoisted_4)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", CataloguePanelvue_type_template_id_27b053aa_hoisted_7, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_SearchInput, {
      ref: "search",
      modelValue: _ctx.query,
      "onUpdate:modelValue": _cache[2] || (_cache[2] = $event => _ctx.query = $event),
      placeholder: _ctx.translate('DataExplorer_PickerSearch'),
      "aria-label": _ctx.translate('DataExplorer_PickerSearch')
    }, null, 8, ["modelValue", "placeholder", "aria-label"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", CataloguePanelvue_type_template_id_27b053aa_hoisted_8, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_PanelAddedCount', _ctx.added.length)), 1)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", {
      ref: "body",
      class: "dataExplorerPanel__body",
      onKeydown: _cache[3] || (_cache[3] = (...args) => _ctx.onBodyKeydown && _ctx.onBodyKeydown(...args))
    }, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.filteredGroups, (group, groupIndex) => {
      return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("section", {
        key: group.category,
        class: "dataExplorerPanel__group"
      }, [!_ctx.singleGroup ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("h3", CataloguePanelvue_type_template_id_27b053aa_hoisted_9, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
        type: "button",
        class: "dataExplorerPanel__categoryToggle",
        "data-nav": "true",
        "aria-expanded": _ctx.isExpanded(group.category) ? 'true' : 'false',
        "aria-controls": `dataExplorerPanelGroup-${groupIndex}`,
        onClick: $event => _ctx.toggleCategory(group.category)
      }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", CataloguePanelvue_type_template_id_27b053aa_hoisted_11, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(group.category), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", CataloguePanelvue_type_template_id_27b053aa_hoisted_12, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(group.items.length), 1), CataloguePanelvue_type_template_id_27b053aa_hoisted_13], 8, CataloguePanelvue_type_template_id_27b053aa_hoisted_10)])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("ul", {
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
        }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", CataloguePanelvue_type_template_id_27b053aa_hoisted_16, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(item.name), 1), _ctx.added.includes(item.id) ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("span", CataloguePanelvue_type_template_id_27b053aa_hoisted_17)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), item.reason ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("span", {
          key: 1,
          id: _ctx.reasonId(item.id),
          class: "dataExplorerVisuallyHidden"
        }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(item.reason), 9, CataloguePanelvue_type_template_id_27b053aa_hoisted_18)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)], 10, CataloguePanelvue_type_template_id_27b053aa_hoisted_15)]);
      }), 128))], 8, CataloguePanelvue_type_template_id_27b053aa_hoisted_14), [[external_commonjs_vue_commonjs2_vue_root_Vue_["vShow"], _ctx.singleGroup || _ctx.isExpanded(group.category)]])]);
    }), 128)), !_ctx.filteredGroups.length ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", CataloguePanelvue_type_template_id_27b053aa_hoisted_19, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.query.trim() || !_ctx.emptyText ? _ctx.translate('DataExplorer_PickerNoMatch') : _ctx.emptyText), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)], 544)], 40, CataloguePanelvue_type_template_id_27b053aa_hoisted_1), [[external_commonjs_vue_commonjs2_vue_root_Vue_["vShow"], _ctx.isOpen]])]),
    _: 1
  })]);
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Explorer/CataloguePanel.vue?vue&type=template&id=27b053aa

// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/pageScrollLock.ts
/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
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
    // one category only (the saved segments): its items without a header to open
    const singleGroup = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => props.groups.length === 1);
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
      singleGroup,
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



CataloguePanelvue_type_script_lang_ts.render = CataloguePanelvue_type_template_id_27b053aa_render

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
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/RawSql/RawSqlEditor.vue?vue&type=template&id=ce781de6

const RawSqlEditorvue_type_template_id_ce781de6_hoisted_1 = {
  class: "dataExplorerSql"
};
const RawSqlEditorvue_type_template_id_ce781de6_hoisted_2 = ["href"];
const RawSqlEditorvue_type_template_id_ce781de6_hoisted_3 = {
  key: 1,
  class: "dataExplorerSql__layout"
};
const RawSqlEditorvue_type_template_id_ce781de6_hoisted_4 = {
  class: "dataExplorerColumn dataExplorerSql__main"
};
const RawSqlEditorvue_type_template_id_ce781de6_hoisted_5 = {
  id: "dataExplorerSqlEditorLabel",
  class: "dataExplorerColumn__title"
};
const RawSqlEditorvue_type_template_id_ce781de6_hoisted_6 = {
  class: "dataExplorerSql__help"
};
const RawSqlEditorvue_type_template_id_ce781de6_hoisted_7 = {
  ref: "container",
  class: "dataExplorerSql__editor"
};
const RawSqlEditorvue_type_template_id_ce781de6_hoisted_8 = {
  class: "dataExplorerSql__validation",
  "aria-live": "polite"
};
const RawSqlEditorvue_type_template_id_ce781de6_hoisted_9 = {
  key: 0,
  class: "dataExplorerSql__checking"
};
const RawSqlEditorvue_type_template_id_ce781de6_hoisted_10 = {
  key: 1,
  class: "dataExplorerSql__problems"
};
const RawSqlEditorvue_type_template_id_ce781de6_hoisted_11 = {
  key: 2,
  class: "dataExplorerSql__valid"
};
const RawSqlEditorvue_type_template_id_ce781de6_hoisted_12 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "icon-ok",
  "aria-hidden": "true"
}, null, -1);
const RawSqlEditorvue_type_template_id_ce781de6_hoisted_13 = {
  class: "dataExplorerSql__actions"
};
const RawSqlEditorvue_type_template_id_ce781de6_hoisted_14 = ["disabled", "title"];
const RawSqlEditorvue_type_template_id_ce781de6_hoisted_15 = ["disabled"];
const RawSqlEditorvue_type_template_id_ce781de6_hoisted_16 = {
  key: 0,
  class: "dataExplorerSql__resultBlock"
};
const RawSqlEditorvue_type_template_id_ce781de6_hoisted_17 = ["aria-label"];
const RawSqlEditorvue_type_template_id_ce781de6_hoisted_18 = ["aria-pressed", "aria-label", "title", "onClick"];
const RawSqlEditorvue_type_template_id_ce781de6_hoisted_19 = {
  key: 0,
  class: "dataExplorerSql__hint"
};
const _hoisted_20 = {
  ref: "resultElement",
  class: "dataExplorerSql__resultWidget"
};
function RawSqlEditorvue_type_template_id_ce781de6_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_ActivityIndicator = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("ActivityIndicator");
  const _component_Notification = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Notification");
  const _component_ContentBlock = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("ContentBlock");
  const _component_SchemaBrowser = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("SchemaBrowser");
  const _component_WidgetLoader = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("WidgetLoader");
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", RawSqlEditorvue_type_template_id_ce781de6_hoisted_1, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_ActivityIndicator, {
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
    }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_SqlOpenSetup')), 9, RawSqlEditorvue_type_template_id_ce781de6_hoisted_2)])]),
    _: 1
  }, 8, ["content-title"])) : _ctx.isReady ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", RawSqlEditorvue_type_template_id_ce781de6_hoisted_3, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_SchemaBrowser, {
    class: "dataExplorerColumn",
    schema: _ctx.schema,
    loading: _ctx.schemaLoading,
    onInsert: _ctx.insert
  }, null, 8, ["schema", "loading", "onInsert"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", RawSqlEditorvue_type_template_id_ce781de6_hoisted_4, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h3", RawSqlEditorvue_type_template_id_ce781de6_hoisted_5, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_SqlEditor')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", RawSqlEditorvue_type_template_id_ce781de6_hoisted_6, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_SqlEditorHelp')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_ActivityIndicator, {
    loading: !_ctx.editorReady
  }, null, 8, ["loading"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", RawSqlEditorvue_type_template_id_ce781de6_hoisted_7, null, 512), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", RawSqlEditorvue_type_template_id_ce781de6_hoisted_8, [_ctx.validity === 'pending' ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", RawSqlEditorvue_type_template_id_ce781de6_hoisted_9, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_SqlChecking')), 1)) : _ctx.problems.length ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("ul", RawSqlEditorvue_type_template_id_ce781de6_hoisted_10, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.problems, (problem, index) => {
    return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("li", {
      key: index,
      class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(`is-${problem.severity}`)
    }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
      class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(problem.severity === 'error' ? 'icon-error' : 'icon-warning'),
      "aria-hidden": "true"
    }, null, 2), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(" " + Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(problem.message), 1)], 2);
  }), 128))])) : _ctx.validity === 'valid' ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", RawSqlEditorvue_type_template_id_ce781de6_hoisted_11, [RawSqlEditorvue_type_template_id_ce781de6_hoisted_12, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(" " + Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_SqlValid')), 1)])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", RawSqlEditorvue_type_template_id_ce781de6_hoisted_13, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
    type: "button",
    class: "btn dataExplorerSql__run",
    disabled: !_ctx.canRun,
    title: _ctx.runTitle,
    onClick: _cache[0] || (_cache[0] = (...args) => _ctx.run && _ctx.run(...args))
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_SqlRun')), 9, RawSqlEditorvue_type_template_id_ce781de6_hoisted_14), _ctx.canSave ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("button", {
    key: 0,
    type: "button",
    class: "btn btn-outline dataExplorerSql__save",
    disabled: _ctx.validity !== 'valid',
    onClick: _cache[1] || (_cache[1] = $event => _ctx.$emit('save'))
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.editing ? _ctx.translate('DataExplorer_EditReport') : _ctx.translate('DataExplorer_SaveReport')), 9, RawSqlEditorvue_type_template_id_ce781de6_hoisted_15)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), _ctx.resultParams ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", RawSqlEditorvue_type_template_id_ce781de6_hoisted_16, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", {
    class: "dataExplorerVisualizations dataExplorerSql__visualizations",
    role: "group",
    "aria-label": _ctx.translate('DataExplorer_Visualization')
  }, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.visualizations, option => {
    return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("button", {
      key: option.id,
      type: "button",
      class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["dataExplorerIconButton dataExplorerVisualizations__button", {
        'is-active': option.id === _ctx.visualization
      }]),
      "aria-pressed": option.id === _ctx.visualization ? 'true' : 'false',
      "aria-label": option.label,
      title: option.label,
      onClick: $event => _ctx.$emit('setVisualization', option.id)
    }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
      class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(option.icon),
      "aria-hidden": "true"
    }, null, 2)], 10, RawSqlEditorvue_type_template_id_ce781de6_hoisted_18);
  }), 128))], 8, RawSqlEditorvue_type_template_id_ce781de6_hoisted_17), _ctx.visualization === 'kpicard' ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("p", RawSqlEditorvue_type_template_id_ce781de6_hoisted_19, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_SqlKpiHint')), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_20, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_WidgetLoader, {
    "widget-params": _ctx.resultParams
  }, null, 8, ["widget-params"])], 512)])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)])])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]);
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/RawSql/RawSqlEditor.vue?vue&type=template&id=ce781de6

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
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/RawSql/RawSqlEditor.vue?vue&type=script&lang=ts







let modules = null;
function loadCodeMirror() {
  if (!modules) {
    modules = Promise.all(/* import() | codemirror */[__webpack_require__.e(2), __webpack_require__.e(1)]).then(__webpack_require__.bind(null, "85d2"));
  }
  return modules;
}
/**
 * Raw SQL tab (super users): CodeMirror editor with MySQL highlighting, completion of the allowed
 * tables and columns, live validation by the server guard, the schema of the allowed tables, the
 * result table and its CSV export. Run stays disabled while the query is not valid.
 */
function starterQuery(visitTable) {
  return ['SELECT', '  DATE(visit_last_action_time) AS day,', '  COUNT(*) AS visits,', '  SUM(visit_total_actions) AS actions', `FROM ${visitTable}`, 'WHERE idsite = {idsite}', '  AND visit_last_action_time BETWEEN {date_start} AND {date_end}', 'GROUP BY day', 'ORDER BY day'].join('\n');
}
/* harmony default export */ var RawSqlEditorvue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  name: 'RawSqlEditor',
  components: {
    ActivityIndicator: external_CoreHome_["ActivityIndicator"],
    ContentBlock: external_CoreHome_["ContentBlock"],
    Notification: external_CoreHome_["Notification"],
    SchemaBrowser: SchemaBrowser,
    WidgetLoader: external_CoreHome_["WidgetLoader"]
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
    },
    // a saved query this user may change is open: the button opens it for an update
    editing: {
      type: Boolean,
      default: false
    },
    visualization: {
      type: String,
      default: 'table'
    }
  },
  emits: ['update:modelValue', 'save', 'validity', 'setVisualization'],
  setup(props, {
    emit
  }) {
    const container = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    const editorReady = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(false);
    const schema = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])({});
    const schemaLoading = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(false);
    const validity = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(props.modelValue.trim() ? 'pending' : 'empty');
    const problems = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])([]);
    // the result of Run: the native visualization of the query, like a saved raw SQL report
    const resultParams = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    const ranSql = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('');
    const visualizations = VISUALIZATIONS.map(id => ({
      id,
      icon: VISUALIZATION_ICONS[id],
      label: Object(external_CoreHome_["translate"])(`DataExplorer_Visualization_${id}`)
    }));
    // CodeMirror objects must not be wrapped in Vue proxies
    let view = null;
    let isApplyingModelValue = false;
    let validationSequence = 0;
    let unmounted = false;
    // asked when the tab opens: the status connects to the read-only database user
    const status = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    const statusLoading = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(true);
    const isReady = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => !!status.value && status.value.state === 'ready');
    const canRun = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => editorReady.value && validity.value === 'valid');
    const runTitle = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => canRun.value ? Object(external_CoreHome_["translate"])('DataExplorer_SqlRunShortcut') : Object(external_CoreHome_["translate"])('DataExplorer_SqlRunDisabled'));
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
    function load() {
      const chart = props.visualization === 'bar' || props.visualization === 'pie' ? props.visualization : '';
      resultParams.value = {
        module: 'DataExplorer',
        action: 'preview',
        definition: JSON.stringify({
          type: 'sql',
          name: 'preview',
          sql: ranSql.value,
          report_type: reportTypeOf(props.visualization),
          chart
        }),
        period: props.period,
        date: props.date,
        viewDataTable: VIEW_BY_VISUALIZATION[props.visualization]
      };
    }
    function run() {
      if (!canRun.value || !view) {
        return;
      }
      ranSql.value = view.state.doc.toString();
      load();
    }
    // a chart picked in the footer of the result becomes the one of the query, as in the Free
    // form canvas; core triggers the event before it attaches the DataTable instance
    const resultElement = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    function onLoaded(event) {
      const table = $(event.target).closest('.dataTable');
      setTimeout(() => {
        const control = table.data('uiControlObject');
        const picked = control && control.param ? VISUALIZATION_BY_VIEW[String(control.param.viewDataTable || '')] : undefined;
        if (picked && picked !== props.visualization) {
          emit('setVisualization', picked);
        }
      });
    }
    let bound = null;
    Object(external_commonjs_vue_commonjs2_vue_root_Vue_["watch"])(resultElement, element => {
      if (bound) {
        $(bound).off('piwik:dataTableLoaded', onLoaded);
      }
      bound = element;
      if (bound) {
        $(bound).on('piwik:dataTableLoaded', onLoaded);
      }
    });
    // the chart, period or date of a result already shown applies at once; the query waits for Run
    Object(external_commonjs_vue_commonjs2_vue_root_Vue_["watch"])(() => [props.visualization, props.period, props.date], () => {
      if (resultParams.value) {
        load();
      }
    });
    function insert(text) {
      if (!view) {
        return;
      }
      view.dispatch(Object.assign(Object.assign({}, view.state.replaceSelection(text)), {}, {
        scrollIntoView: true
      }));
      view.focus();
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
    // an empty editor (nothing opened, nothing typed: a text the user clears comes back from the
    // editor itself and never gets here) starts on a query that runs as is, to show the way
    function offerStarter(value) {
      const visits = Object.keys(schema.value).find(table => /(^|_)log_visit$/.test(table));
      if (value.trim() !== '' || !visits) {
        return false;
      }
      emit('update:modelValue', starterQuery(visits));
      return true;
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
        offerStarter(props.modelValue);
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
      // another query opened: the result of the previous one no longer belongs here
      resultParams.value = null;
      if (offerStarter(value)) {
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
      resultParams,
      resultElement,
      visualizations,
      isReady,
      status,
      statusLoading,
      canRun,
      runTitle,
      run,
      insert
    };
  }
}));
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/RawSql/RawSqlEditor.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/RawSql/RawSqlEditor.vue



RawSqlEditorvue_type_script_lang_ts.render = RawSqlEditorvue_type_template_id_ce781de6_render

/* harmony default export */ var RawSqlEditor = (RawSqlEditorvue_type_script_lang_ts);
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/SaveDialog/SaveDialog.vue?vue&type=template&id=f246864e

const SaveDialogvue_type_template_id_f246864e_hoisted_1 = {
  class: "dataExplorerSaveDialog"
};
const SaveDialogvue_type_template_id_f246864e_hoisted_2 = {
  class: "dataExplorerSaveDialog__form"
};
const SaveDialogvue_type_template_id_f246864e_hoisted_3 = {
  class: "dataExplorerSaveDialog__error",
  "aria-live": "assertive"
};
const SaveDialogvue_type_template_id_f246864e_hoisted_4 = {
  class: "dataExplorerSaveDialog__reportType"
};
const SaveDialogvue_type_template_id_f246864e_hoisted_5 = ["aria-label"];
const SaveDialogvue_type_template_id_f246864e_hoisted_6 = {
  class: "dataExplorerSitePicker__label"
};
const SaveDialogvue_type_template_id_f246864e_hoisted_7 = {
  key: 0,
  class: "dataExplorerSitePicker__chips"
};
const SaveDialogvue_type_template_id_f246864e_hoisted_8 = ["title", "aria-label", "onClick"];
const SaveDialogvue_type_template_id_f246864e_hoisted_9 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "icon-close",
  "aria-hidden": "true"
}, null, -1);
const SaveDialogvue_type_template_id_f246864e_hoisted_10 = [SaveDialogvue_type_template_id_f246864e_hoisted_9];
const SaveDialogvue_type_template_id_f246864e_hoisted_11 = {
  class: "dataExplorerSitePicker__list"
};
const SaveDialogvue_type_template_id_f246864e_hoisted_12 = {
  class: "dataExplorerSitePicker__option"
};
const SaveDialogvue_type_template_id_f246864e_hoisted_13 = ["checked", "onChange"];
const SaveDialogvue_type_template_id_f246864e_hoisted_14 = {
  key: 0,
  class: "dataExplorerSitePicker__empty"
};
function SaveDialogvue_type_template_id_f246864e_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Notification = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Notification");
  const _component_Field = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Field");
  const _component_SearchInput = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("SearchInput");
  const _component_SaveButton = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("SaveButton");
  const _component_SidePanel = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("SidePanel");
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_SidePanel, {
    "model-value": _ctx.modelValue,
    title: _ctx.title,
    "focus-selector": "#dataExplorerSaveName",
    "onUpdate:modelValue": _cache[10] || (_cache[10] = $event => _ctx.$emit('update:modelValue', $event)),
    onClosed: _cache[11] || (_cache[11] = $event => _ctx.$emit('closed'))
  }, {
    actions: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [_ctx.canUpdate ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_SaveButton, {
      key: 0,
      class: "dataExplorerSaveDialog__update",
      saving: _ctx.saving,
      disabled: !_ctx.modified && !_ctx.formChanged,
      value: _ctx.translate('General_Save'),
      onConfirm: _cache[8] || (_cache[8] = $event => _ctx.save(false))
    }, null, 8, ["saving", "disabled", "value"])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_SaveButton, {
      class: "dataExplorerSaveDialog__create",
      saving: _ctx.saving,
      value: _ctx.editing ? _ctx.translate('DataExplorer_SaveAsNew') : _ctx.translate('General_Save'),
      onConfirm: _cache[9] || (_cache[9] = $event => _ctx.save(true))
    }, null, 8, ["saving", "value"])]),
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", SaveDialogvue_type_template_id_f246864e_hoisted_1, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", SaveDialogvue_type_template_id_f246864e_hoisted_2, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", SaveDialogvue_type_template_id_f246864e_hoisted_3, [_ctx.error ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_Notification, {
      key: 0,
      context: "error",
      noclear: true
    }, {
      default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.error), 1)]),
      _: 1
    })) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
      "full-width": true,
      uicontrol: "text",
      name: "dataExplorerSaveName",
      modelValue: _ctx.form.name,
      "onUpdate:modelValue": _cache[0] || (_cache[0] = $event => _ctx.form.name = $event),
      title: _ctx.translate('DataExplorer_SaveName'),
      maxlength: 255,
      required: true
    }, null, 8, ["modelValue", "title"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
      "full-width": true,
      uicontrol: "textarea",
      name: "dataExplorerSaveDescription",
      modelValue: _ctx.form.description,
      "onUpdate:modelValue": _cache[1] || (_cache[1] = $event => _ctx.form.description = $event),
      title: _ctx.translate('DataExplorer_SaveDescription'),
      "inline-help": _ctx.translate('DataExplorer_SaveDescriptionHelp'),
      maxlength: 1000
    }, null, 8, ["modelValue", "title", "inline-help"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", SaveDialogvue_type_template_id_f246864e_hoisted_4, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_SavedAs', _ctx.reportTypeLabel)), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
      "full-width": true,
      uicontrol: "radio",
      name: "dataExplorerSavePlacement",
      modelValue: _ctx.form.placement,
      "onUpdate:modelValue": _cache[2] || (_cache[2] = $event => _ctx.form.placement = $event),
      title: _ctx.translate('DataExplorer_SavePlacement'),
      options: _ctx.placementOptions
    }, null, 8, ["modelValue", "title", "options"]), _ctx.form.placement === 'sites' ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
      key: 0,
      class: "dataExplorerSitePicker",
      role: "group",
      "aria-label": _ctx.translate('DataExplorer_SaveSites')
    }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", SaveDialogvue_type_template_id_f246864e_hoisted_6, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_SaveSites')), 1), _ctx.form.sites.length ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("ul", SaveDialogvue_type_template_id_f246864e_hoisted_7, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.form.sites, id => {
      return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("li", {
        key: id,
        class: "dataExplorerSitePicker__chip"
      }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.siteNames[id] || id), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
        type: "button",
        class: "dataExplorerSitePicker__remove",
        title: _ctx.translate('General_Remove'),
        "aria-label": `${_ctx.translate('General_Remove')} ${_ctx.siteNames[id] || id}`,
        onClick: $event => _ctx.toggleSite(id)
      }, SaveDialogvue_type_template_id_f246864e_hoisted_10, 8, SaveDialogvue_type_template_id_f246864e_hoisted_8)]);
    }), 128))])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_SearchInput, {
      modelValue: _ctx.siteSearch,
      "onUpdate:modelValue": _cache[3] || (_cache[3] = $event => _ctx.siteSearch = $event),
      placeholder: _ctx.translate('DataExplorer_SaveSitesSearch'),
      "aria-label": _ctx.translate('DataExplorer_SaveSitesSearch')
    }, null, 8, ["modelValue", "placeholder", "aria-label"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("ul", SaveDialogvue_type_template_id_f246864e_hoisted_11, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.siteOptions, site => {
      return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("li", {
        key: site.key
      }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("label", SaveDialogvue_type_template_id_f246864e_hoisted_12, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("input", {
        type: "checkbox",
        checked: _ctx.form.sites.indexOf(site.key) !== -1,
        onChange: $event => _ctx.toggleSite(site.key)
      }, null, 40, SaveDialogvue_type_template_id_f246864e_hoisted_13), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(site.value), 1)])]);
    }), 128)), !_ctx.siteOptions.length ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("li", SaveDialogvue_type_template_id_f246864e_hoisted_14, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_SearchNoResults')), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)])], 8, SaveDialogvue_type_template_id_f246864e_hoisted_5)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
      "full-width": true,
      uicontrol: "select",
      name: "dataExplorerSaveCategory",
      modelValue: _ctx.form.category,
      "onUpdate:modelValue": _cache[4] || (_cache[4] = $event => _ctx.form.category = $event),
      title: _ctx.translate('DataExplorer_SaveCategory'),
      options: _ctx.categoryOptions
    }, null, 8, ["modelValue", "title", "options"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
      "full-width": true,
      uicontrol: "select",
      name: "dataExplorerSaveSubcategory",
      modelValue: _ctx.form.subcategory,
      "onUpdate:modelValue": _cache[5] || (_cache[5] = $event => _ctx.form.subcategory = $event),
      title: _ctx.translate('DataExplorer_SaveSubcategory'),
      options: _ctx.subcategoryOptions
    }, null, 8, ["modelValue", "title", "options"]), _ctx.form.subcategory === _ctx.newSubcategoryKey ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_Field, {
      key: 1,
      "full-width": true,
      uicontrol: "text",
      name: "dataExplorerSaveNewSubcategory",
      modelValue: _ctx.form.newSubcategory,
      "onUpdate:modelValue": _cache[6] || (_cache[6] = $event => _ctx.form.newSubcategory = $event),
      title: _ctx.translate('DataExplorer_SaveNewSubcategory'),
      maxlength: 255
    }, null, 8, ["modelValue", "title"])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
      "full-width": true,
      uicontrol: "text",
      "var-type": "integer",
      name: "dataExplorerSaveOrder",
      modelValue: _ctx.form.order,
      "onUpdate:modelValue": _cache[7] || (_cache[7] = $event => _ctx.form.order = $event),
      title: _ctx.translate('DataExplorer_SaveOrder'),
      "inline-help": _ctx.translate('DataExplorer_SaveOrderHelp')
    }, null, 8, ["modelValue", "title", "inline-help"])])])]),
    _: 1
  }, 8, ["model-value", "title"]);
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/SaveDialog/SaveDialog.vue?vue&type=template&id=f246864e

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/SidePanel/SidePanel.vue?vue&type=template&id=3da7ca23

const SidePanelvue_type_template_id_3da7ca23_hoisted_1 = ["aria-labelledby"];
const SidePanelvue_type_template_id_3da7ca23_hoisted_2 = {
  class: "dataExplorerPanel__header"
};
const SidePanelvue_type_template_id_3da7ca23_hoisted_3 = ["id"];
const SidePanelvue_type_template_id_3da7ca23_hoisted_4 = ["aria-label", "title"];
const SidePanelvue_type_template_id_3da7ca23_hoisted_5 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "icon-close",
  "aria-hidden": "true"
}, null, -1);
const SidePanelvue_type_template_id_3da7ca23_hoisted_6 = [SidePanelvue_type_template_id_3da7ca23_hoisted_5];
const SidePanelvue_type_template_id_3da7ca23_hoisted_7 = {
  class: "dataExplorerPanel__body dataExplorerFormPanel__body"
};
const SidePanelvue_type_template_id_3da7ca23_hoisted_8 = {
  key: 0,
  class: "dataExplorerFormPanel__actions"
};
function SidePanelvue_type_template_id_3da7ca23_render(_ctx, _cache, $props, $setup, $data, $options) {
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Teleport"], {
    to: "body"
  }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Transition"], {
    name: "dataExplorerPanelBackdrop"
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [_ctx.modelValue ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
      key: 0,
      class: "dataExplorerPanelBackdrop",
      onClick: _cache[0] || (_cache[0] = (...args) => _ctx.close && _ctx.close(...args))
    })) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]),
    _: 1
  }), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Transition"], {
    name: "dataExplorerPanel",
    onAfterLeave: _ctx.onAfterLeave
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [_ctx.modelValue ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("section", {
      key: 0,
      ref: "panel",
      class: "dataExplorerPanel dataExplorerFormPanel",
      role: "dialog",
      "aria-modal": "true",
      "aria-labelledby": _ctx.titleId,
      onKeydown: [_cache[2] || (_cache[2] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withKeys"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])((...args) => _ctx.close && _ctx.close(...args), ["stop", "prevent"]), ["esc"])), _cache[3] || (_cache[3] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withKeys"])((...args) => _ctx.trapFocus && _ctx.trapFocus(...args), ["tab"]))]
    }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("header", SidePanelvue_type_template_id_3da7ca23_hoisted_2, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h2", {
      id: _ctx.titleId,
      class: "dataExplorerPanel__title"
    }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.title), 9, SidePanelvue_type_template_id_3da7ca23_hoisted_3), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
      type: "button",
      class: "dataExplorerIconButton dataExplorerPanel__close",
      "aria-label": _ctx.translate('General_Close'),
      title: _ctx.translate('General_Close'),
      onClick: _cache[1] || (_cache[1] = (...args) => _ctx.close && _ctx.close(...args))
    }, SidePanelvue_type_template_id_3da7ca23_hoisted_6, 8, SidePanelvue_type_template_id_3da7ca23_hoisted_4)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", SidePanelvue_type_template_id_3da7ca23_hoisted_7, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderSlot"])(_ctx.$slots, "default")]), _ctx.$slots.actions ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("footer", SidePanelvue_type_template_id_3da7ca23_hoisted_8, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderSlot"])(_ctx.$slots, "actions")])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)], 40, SidePanelvue_type_template_id_3da7ca23_hoisted_1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]),
    _: 3
  }, 8, ["onAfterLeave"])]);
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/SidePanel/SidePanel.vue?vue&type=template&id=3da7ca23

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/SidePanel/SidePanel.vue?vue&type=script&lang=ts



let panelCount = 0;
const SidePanelvue_type_script_lang_ts_FOCUSABLE = ['a[href]', 'button:not([disabled])', 'input:not([disabled]):not([type="hidden"])', 'select:not([disabled])', 'textarea:not([disabled])', '[tabindex]:not([tabindex="-1"])'].join(',');
/**
 * A form panel sliding in from the right edge over the page, like the catalogue panel, the
 * explorer staying visible on its left. Modal: focus stays inside, Escape and the backdrop close
 * it, focus goes back to the element that opened it.
 */
/* harmony default export */ var SidePanelvue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  name: 'SidePanel',
  props: {
    modelValue: {
      type: Boolean,
      required: true
    },
    title: {
      type: String,
      required: true
    },
    // selector of the element focused on open, the first focusable element otherwise
    focusSelector: {
      type: String,
      default: ''
    }
  },
  emits: ['update:modelValue', 'closed'],
  setup(props, {
    emit
  }) {
    panelCount += 1;
    const titleId = `dataExplorerFormPanelTitle${panelCount}`;
    const panel = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    let opener = null;
    function focusables() {
      if (!panel.value) {
        return [];
      }
      return Array.from(panel.value.querySelectorAll(SidePanelvue_type_script_lang_ts_FOCUSABLE)).filter(element => element.offsetParent !== null);
    }
    Object(external_commonjs_vue_commonjs2_vue_root_Vue_["watch"])(() => props.modelValue, (open, wasOpen) => {
      // a closed panel never unlocks the scroll another panel locked
      if (open || wasOpen) {
        lockPageScroll(open);
      }
      if (!open) {
        return;
      }
      opener = document.activeElement instanceof HTMLElement ? document.activeElement : null;
      Object(external_commonjs_vue_commonjs2_vue_root_Vue_["nextTick"])(() => {
        const preferred = props.focusSelector && panel.value ? panel.value.querySelector(props.focusSelector) : null;
        const target = preferred || focusables()[0];
        if (target) {
          target.focus();
        }
      });
    }, {
      immediate: true
    });
    Object(external_commonjs_vue_commonjs2_vue_root_Vue_["onBeforeUnmount"])(() => {
      if (props.modelValue) {
        lockPageScroll(false);
      }
    });
    function close() {
      emit('update:modelValue', false);
    }
    function trapFocus(event) {
      const list = focusables();
      if (!list.length) {
        return;
      }
      const first = list[0];
      const last = list[list.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    }
    function onAfterLeave() {
      if (opener && document.body.contains(opener)) {
        opener.focus();
      }
      opener = null;
      emit('closed');
    }
    return {
      titleId,
      panel,
      close,
      trapFocus,
      onAfterLeave,
      translate: external_CoreHome_["translate"]
    };
  }
}));
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/SidePanel/SidePanel.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/SidePanel/SidePanel.vue



SidePanelvue_type_script_lang_ts.render = SidePanelvue_type_template_id_3da7ca23_render

/* harmony default export */ var SidePanel = (SidePanelvue_type_script_lang_ts);
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/SaveDialog/SaveDialog.vue?vue&type=script&lang=ts




const DEFAULT_CATEGORY = 'DataExplorer_DataExplorer';
const NEW_SUBCATEGORY = '__dataexplorer_new__';
// saved with no page: the report gets a page of its own, named after it
const OWN_PAGE = '__dataexplorer_own__';
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
    Notification: external_CoreHome_["Notification"],
    SaveButton: external_CorePluginsAdmin_["SaveButton"],
    SearchInput: external_CoreHome_["SearchInput"],
    SidePanel: SidePanel
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
    },
    // the exploration or the query differs from the opened report
    modified: {
      type: Boolean,
      default: false
    },
    // where a report opened from elsewhere (a custom report) goes when nothing saved is open
    suggestion: {
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
      description: '',
      reportType: 'table',
      placement: 'site',
      sites: [],
      category: DEFAULT_CATEGORY,
      subcategory: OWN_PAGE,
      newSubcategory: '',
      order: DEFAULT_ORDER
    });
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
    props.writableSites.forEach(site => {
      siteNames[String(site.id)] = site.name;
    });
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
      if (!props.isSuperUser) {
        return;
      }
      if (siteSearchTimer) {
        clearTimeout(siteSearchTimer);
      }
      siteSearchTimer = setTimeout(searchSites, SITE_SEARCH_DELAY_MS);
    });
    // the sites to tick: the writable sites matching the search (super users search them on the
    // server, every website would be too many to list)
    const siteOptions = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      if (!props.isSuperUser) {
        const query = siteSearch.value.trim().toLowerCase();
        return props.writableSites.filter(site => !query || site.name.toLowerCase().indexOf(query) !== -1).map(site => ({
          key: String(site.id),
          value: site.name
        }));
      }
      return foundSites.value.map(id => ({
        key: id,
        value: siteNames[id] || id
      }));
    });
    function toggleSite(id) {
      const index = form.sites.indexOf(id);
      if (index === -1) {
        form.sites.push(id);
      } else {
        form.sites.splice(index, 1);
      }
    }
    const categoryOptions = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => categories.value.map(category => ({
      key: category.id,
      value: category.name
    })));
    const subcategoryOptions = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      const category = categories.value.find(item => item.id === form.category);
      const options = [{
        key: OWN_PAGE,
        value: Object(external_CoreHome_["translate"])('DataExplorer_SaveOwnPageOption')
      }];
      (category ? category.subcategories : []).forEach(subcategory => {
        options.push({
          key: subcategory.id,
          value: subcategory.name
        });
      });
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
    // the settings as prefilled: an update needs the report or its settings to change
    const prefilled = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('');
    const formChanged = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => prefilled.value !== '' && JSON.stringify(form) !== prefilled.value);
    function prefill() {
      error.value = '';
      prefilled.value = '';
      const report = props.editing;
      const {
        suggestion
      } = props;
      form.name = '';
      form.description = '';
      if (report) {
        form.name = report.name;
        form.description = String(report.definition.description || '');
      } else if (suggestion) {
        form.name = suggestion.name;
        form.description = suggestion.description;
      }
      form.reportType = props.reportType;
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
      let place = {
        category: DEFAULT_CATEGORY,
        subcategory: ''
      };
      if (report) {
        place = report;
      } else if (suggestion && categories.value.some(item => item.id === suggestion.category)) {
        place = suggestion;
      }
      form.category = place.category;
      form.newSubcategory = '';
      if (place.subcategory === '') {
        form.subcategory = OWN_PAGE;
      } else if (subcategoryExists(place.category, place.subcategory)) {
        form.subcategory = place.subcategory;
      } else {
        form.subcategory = NEW_SUBCATEGORY;
        form.newSubcategory = place.subcategory;
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
          prefilled.value = JSON.stringify(form);
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
      if (previous && category !== previous && form.subcategory !== OWN_PAGE && !subcategoryExists(category, form.subcategory)) {
        form.subcategory = OWN_PAGE;
      }
    });
    function pageOf(subcategory) {
      if (subcategory === OWN_PAGE) {
        return '';
      }
      return subcategory === NEW_SUBCATEGORY ? form.newSubcategory : subcategory;
    }
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
      // the visualization picked above the result or in Report settings, both tabs alike
      const {
        reportType
      } = props;
      const postParams = {
        name: form.name,
        type: props.type,
        reportType,
        definition: JSON.stringify(Object.assign(Object.assign({}, props.definition), {}, {
          report_type: reportType,
          description: form.description.trim()
        })),
        placement: placementJson(),
        category: form.category,
        subcategory: pageOf(form.subcategory),
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
      title,
      canUpdate,
      formChanged,
      reportTypeOptions,
      reportTypeLabel,
      placementOptions,
      siteOptions,
      siteSearch,
      siteNames,
      toggleSite,
      categoryOptions,
      subcategoryOptions,
      newSubcategoryKey: NEW_SUBCATEGORY,
      save
    };
  }
}));
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/SaveDialog/SaveDialog.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/SaveDialog/SaveDialog.vue



SaveDialogvue_type_script_lang_ts.render = SaveDialogvue_type_template_id_f246864e_render

/* harmony default export */ var SaveDialog = (SaveDialogvue_type_script_lang_ts);
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/CustomReport/CustomReportDialog.vue?vue&type=template&id=4402303d

const CustomReportDialogvue_type_template_id_4402303d_hoisted_1 = {
  class: "dataExplorerCustomReport"
};
const CustomReportDialogvue_type_template_id_4402303d_hoisted_2 = {
  class: "dataExplorerCustomReport__error",
  "aria-live": "assertive"
};
const CustomReportDialogvue_type_template_id_4402303d_hoisted_3 = {
  class: "dataExplorerCustomReport__help"
};
const CustomReportDialogvue_type_template_id_4402303d_hoisted_4 = {
  key: 0,
  class: "dataExplorerCustomReport__warning"
};
function CustomReportDialogvue_type_template_id_4402303d_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Notification = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Notification");
  const _component_Field = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Field");
  const _component_SaveButton = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("SaveButton");
  const _component_SidePanel = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("SidePanel");
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_SidePanel, {
    "model-value": _ctx.modelValue,
    title: _ctx.translate(_ctx.customReport ? 'DataExplorer_UpdateCustomReport' : 'DataExplorer_SaveAsCustomReport'),
    "focus-selector": "#dataExplorerCustomReportName",
    "onUpdate:modelValue": _cache[4] || (_cache[4] = $event => _ctx.$emit('update:modelValue', $event))
  }, {
    actions: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_SaveButton, {
      saving: _ctx.saving,
      disabled: !_ctx.ready || !_ctx.name.trim() || !!_ctx.customReport && !_ctx.modified && !_ctx.formChanged,
      value: _ctx.translate('General_Save'),
      onConfirm: _ctx.save
    }, null, 8, ["saving", "disabled", "value", "onConfirm"])]),
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", CustomReportDialogvue_type_template_id_4402303d_hoisted_1, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", CustomReportDialogvue_type_template_id_4402303d_hoisted_2, [_ctx.error ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_Notification, {
      key: 0,
      context: "error",
      noclear: true
    }, {
      default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.error), 1)]),
      _: 1
    })) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("p", CustomReportDialogvue_type_template_id_4402303d_hoisted_3, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate(_ctx.customReport ? 'DataExplorer_CustomReportUpdateHelp' : 'DataExplorer_CustomReportHelp')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
      modelValue: _ctx.name,
      "onUpdate:modelValue": _cache[0] || (_cache[0] = $event => _ctx.name = $event),
      uicontrol: "text",
      name: "dataExplorerCustomReportName",
      title: _ctx.translate('General_Name'),
      "full-width": true
    }, null, 8, ["modelValue", "title"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
      modelValue: _ctx.description,
      "onUpdate:modelValue": _cache[1] || (_cache[1] = $event => _ctx.description = $event),
      uicontrol: "textarea",
      name: "dataExplorerCustomReportDescription",
      title: _ctx.translate('DataExplorer_SaveDescription'),
      "full-width": true,
      maxlength: 1000
    }, null, 8, ["modelValue", "title"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
      modelValue: _ctx.category,
      "onUpdate:modelValue": _cache[2] || (_cache[2] = $event => _ctx.category = $event),
      uicontrol: "select",
      name: "dataExplorerCustomReportCategory",
      title: _ctx.translate('DataExplorer_SaveCategory'),
      options: _ctx.categoryOptions,
      "full-width": true
    }, null, 8, ["modelValue", "title", "options"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
      modelValue: _ctx.subcategory,
      "onUpdate:modelValue": _cache[3] || (_cache[3] = $event => _ctx.subcategory = $event),
      uicontrol: "select",
      name: "dataExplorerCustomReportSubcategory",
      title: _ctx.translate('DataExplorer_SaveSubcategory'),
      options: _ctx.subcategoryOptions,
      "full-width": true
    }, null, 8, ["modelValue", "title", "options"]), _ctx.unsupported.length ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", CustomReportDialogvue_type_template_id_4402303d_hoisted_4, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_CustomReportUnsupported', _ctx.unsupported.join(', '))), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)])]),
    _: 1
  }, 8, ["model-value", "title"]);
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/CustomReport/CustomReportDialog.vue?vue&type=template&id=4402303d

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/CustomReport/CustomReportDialog.vue?vue&type=script&lang=ts




// the reporting page Custom Reports creates for the report when none is picked
const NEW_PAGE = '';
const CustomReportDialogvue_type_script_lang_ts_DEFAULT_CATEGORY = 'CustomReports_CustomReports';
/**
 * Saves the exploration as a report of the Custom Reports plugin, when it is installed: the
 * explorer becomes a way to build custom reports. The parameters (same dimension and metric ids,
 * filters as a segment) come from DataExplorer.getCustomReportParameters.
 */
/* harmony default export */ var CustomReportDialogvue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  name: 'CustomReportDialog',
  components: {
    Field: external_CorePluginsAdmin_["Field"],
    Notification: external_CoreHome_["Notification"],
    SaveButton: external_CorePluginsAdmin_["SaveButton"],
    SidePanel: SidePanel
  },
  props: {
    modelValue: {
      type: Boolean,
      required: true
    },
    definition: {
      type: Object,
      required: true
    },
    defaultName: {
      type: String,
      default: ''
    },
    defaultDescription: {
      type: String,
      default: ''
    },
    // the exploration differs from the custom report it was opened from
    modified: {
      type: Boolean,
      default: false
    },
    // the custom report the exploration was opened from: saving updates it
    customReport: {
      type: Object,
      default: null
    }
  },
  emits: ['update:modelValue', 'created', 'updated', 'failed'],
  setup(props, {
    emit
  }) {
    const name = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('');
    const description = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('');
    // the settings as prefilled: an update needs the report or its settings to change
    const prefilled = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('');
    const unsupported = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])([]);
    const error = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('');
    const saving = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(false);
    const ready = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(false);
    const categories = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])([]);
    const category = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(CustomReportDialogvue_type_script_lang_ts_DEFAULT_CATEGORY);
    const subcategory = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(NEW_PAGE);
    let parameters = null;
    const categoryOptions = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => categories.value.map(item => ({
      key: item.uniqueId,
      value: item.name
    })));
    const subcategoryOptions = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      const found = categories.value.find(item => item.uniqueId === category.value);
      return [{
        key: NEW_PAGE,
        value: Object(external_CoreHome_["translate"])('DataExplorer_SaveNewSubcategoryOption')
      }, ...(found ? found.subcategories : []).map(item => ({
        key: item.uniqueId,
        value: item.name
      }))];
    });
    // a page belongs to its category
    Object(external_commonjs_vue_commonjs2_vue_root_Vue_["watch"])(category, () => {
      subcategory.value = NEW_PAGE;
    });
    function loadCategories() {
      external_CoreHome_["AjaxHelper"].fetch({
        method: 'CustomReports.getAvailableCategories',
        idSite: external_CoreHome_["Matomo"].idSite,
        filter_limit: -1
      }, {
        createErrorNotification: false
      }).then(result => {
        categories.value = Array.isArray(result) ? result : [];
        // the select shows its value once its options exist
        category.value = props.customReport && props.customReport.category ? props.customReport.category : CustomReportDialogvue_type_script_lang_ts_DEFAULT_CATEGORY;
        // after the category watcher, which resets the page
        Object(external_commonjs_vue_commonjs2_vue_root_Vue_["nextTick"])(() => {
          subcategory.value = props.customReport ? props.customReport.subcategory : NEW_PAGE;
          prefilled.value = JSON.stringify({
            name: name.value,
            description: description.value,
            category: category.value,
            subcategory: subcategory.value
          });
        });
      }).catch(() => {
        categories.value = [];
      });
    }
    function load() {
      parameters = null;
      ready.value = false;
      error.value = '';
      unsupported.value = [];
      external_CoreHome_["AjaxHelper"].post({
        method: 'DataExplorer.getCustomReportParameters',
        idSite: external_CoreHome_["Matomo"].idSite,
        period: external_CoreHome_["MatomoUrl"].parsed.value.period || external_CoreHome_["Matomo"].period,
        date: external_CoreHome_["MatomoUrl"].parsed.value.date || external_CoreHome_["Matomo"].currentDateString
      }, {
        definition: JSON.stringify(props.definition)
      }, {
        createErrorNotification: false
      }).then(result => {
        parameters = result;
        ready.value = true;
        unsupported.value = result.unsupported || [];
      }).catch(e => {
        emit('failed', e.message);
      });
    }
    Object(external_commonjs_vue_commonjs2_vue_root_Vue_["watch"])(() => props.modelValue, open => {
      if (open) {
        prefilled.value = '';
        name.value = props.customReport ? props.customReport.name : props.defaultName;
        description.value = props.customReport ? props.customReport.description : props.defaultDescription;
        category.value = '';
        subcategory.value = NEW_PAGE;
        load();
        loadCategories();
      }
    });
    function save() {
      if (!parameters || !name.value.trim()) {
        return;
      }
      error.value = '';
      saving.value = true;
      const report = props.customReport;
      const common = Object.assign({
        name: name.value.trim(),
        reportType: parameters.reportType,
        description: description.value.trim(),
        segmentFilter: encodeURIComponent(parameters.segmentFilter),
        categoryId: category.value || CustomReportDialogvue_type_script_lang_ts_DEFAULT_CATEGORY
      }, subcategory.value !== NEW_PAGE ? {
        subcategoryId: subcategory.value
      } : {});
      const lists = {
        dimensionIds: parameters.dimensionIds,
        metricIds: parameters.metricIds
      };
      if (report) {
        // an update replaces every field: what the explorer does not edit is sent back as it was
        lists.subCategoryReportIds = report.childReportIds;
        if (report.idSite !== 0 && report.multipleIdSites.length) {
          lists.multipleIdSites = report.multipleIdSites;
        }
      }
      external_CoreHome_["AjaxHelper"].post(report ? Object.assign({
        method: 'CustomReports.updateCustomReport',
        idSite: report.idSite === 0 ? 'all' : report.idSite,
        idCustomReport: report.idCustomReport
      }, common) : Object.assign({
        method: 'CustomReports.addCustomReport',
        idSite: external_CoreHome_["Matomo"].idSite
      }, common), lists, {
        withTokenInUrl: true,
        createErrorNotification: false
      }).then(response => {
        emit(report ? 'updated' : 'created', {
          idCustomReport: report ? report.idCustomReport : Number(response.value),
          name: common.name,
          description: common.description,
          category: common.categoryId,
          subcategory: subcategory.value
        });
        emit('update:modelValue', false);
      }).catch(e => {
        error.value = e.message;
      }).finally(() => {
        saving.value = false;
      });
    }
    const formChanged = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => prefilled.value !== '' && JSON.stringify({
      name: name.value,
      description: description.value,
      category: category.value,
      subcategory: subcategory.value
    }) !== prefilled.value);
    return {
      name,
      description,
      formChanged,
      unsupported,
      error,
      saving,
      ready,
      category,
      subcategory,
      categoryOptions,
      subcategoryOptions,
      save,
      translate: external_CoreHome_["translate"]
    };
  }
}));
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/CustomReport/CustomReportDialog.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/CustomReport/CustomReportDialog.vue



CustomReportDialogvue_type_script_lang_ts.render = CustomReportDialogvue_type_template_id_4402303d_render

/* harmony default export */ var CustomReportDialog = (CustomReportDialogvue_type_script_lang_ts);
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
/**
 * The explorer of the "Data Explorer > Explore" page: free-form exploration in three columns
 * (variables, tab settings, canvas) like GA4 Explorations, the raw SQL tab for super users and
 * the save dialog. The exploration lives in the URL hash so a reload or a shared link restores
 * it; a saved report is reopened with idReport in the hash.
 */
// the chart of the Raw SQL result in the URL, beside the exploration
const SQL_VIEW_PARAM = 'sqlView';
// period of a new visit of the explorer: the last 28 complete days, today excluded, like GA4
const DEFAULT_PERIOD = 'range';
const DEFAULT_DATE = 'previous28';
/* harmony default export */ var Explorervue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  name: 'Explorer',
  components: {
    ActivityIndicator: external_CoreHome_["ActivityIndicator"],
    Canvas: Canvas,
    MatomoDialog: external_CoreHome_["MatomoDialog"],
    Notification: external_CoreHome_["Notification"],
    RawSqlEditor: RawSqlEditor,
    SaveDialog: SaveDialog,
    CustomReportDialog: CustomReportDialog,
    ReportPicker: ReportPicker,
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
    },
    // the Custom Reports plugin is activated and the user can write: the exploration can become one
    customReports: {
      type: Boolean,
      default: false
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
    // the chart of the Raw SQL result, saved with the query like the one of an exploration
    const sqlVisualization = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('table');
    const activeTab = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('explore');
    // what an opened report computes and shows, to tell whether the exploration changed since
    const snapshotOf = value => JSON.stringify({
      definition: toDefinition(value),
      visualization: value.visualization
    });
    // '' when nothing saved is open
    const openedSnapshot = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('');
    // the query of an opened raw SQL report, null when none is open
    const openedSql = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    const openedSqlVisualization = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('table');
    const isModified = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      if (openedSql.value !== null && editing.value && editing.value.type === 'sql') {
        return sql.value.trim() !== openedSql.value.trim() || sqlVisualization.value !== openedSqlVisualization.value;
      }
      return openedSnapshot.value !== '' && snapshotOf(state) !== openedSnapshot.value;
    });
    const isEditingVisual = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => !!editing.value && editing.value.editable && editing.value.type === 'visual');
    const isEditingSql = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => !!editing.value && editing.value.editable && editing.value.type === 'sql');
    // the page of a custom report in the reporting menu: Custom Reports names its own page after
    // the report id in its default category, after the report name elsewhere
    function customReportUrl(report) {
      let {
        subcategory
      } = report;
      if (subcategory === '') {
        subcategory = report.category === 'CustomReports_CustomReports' ? String(report.idCustomReport) : report.name;
      }
      const hash = external_CoreHome_["MatomoUrl"].stringify(Object.assign(Object.assign({}, external_CoreHome_["MatomoUrl"].hashParsed.value), {}, {
        category: report.category,
        subcategory,
        [HASH_PARAM]: undefined,
        [REPORT_HASH_PARAM]: undefined
      }));
      return `?${external_CoreHome_["MatomoUrl"].stringify(external_CoreHome_["MatomoUrl"].urlParsed.value)}#?${hash}`;
    }
    const sqlOpened = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(false);
    // what the canvas loads: the native visualization of the exploration, null while it cannot run
    const previewParams = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    const notice = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('');
    // what the opened custom report has that the explorer cannot read, shown struck through
    const importedUnsupported = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])([]);
    // a link shown with one notice only: any other notice replaces it
    const noticeLink = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
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
    const periodDates = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      try {
        return external_CoreHome_["Periods"].parse(period.value, date.value).getPrettyString();
      } catch (e) {
        return `${period.value} ${date.value}`;
      }
    });
    // a keyword range reads as its name, like the GA4 date picker; its dates stay in the tooltip
    const periodText = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      const keyword = period.value === 'range' ? /^(last|previous)(\d+)$/.exec(date.value) : null;
      if (!keyword) {
        return periodDates.value;
      }
      return keyword[1] === 'previous' ? Object(external_CoreHome_["translate"])('General_LastDaysShort', keyword[2]) : Object(external_CoreHome_["translate"])('General_LastDays', keyword[2]);
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
    const KNOWN_SCOPES = ['visit', 'action', 'conversion'];
    // the scope of a plugin table is its name: log_media reads "media"
    const scopeLabel = scope => KNOWN_SCOPES.includes(scope) ? Object(external_CoreHome_["translate"])(`DataExplorer_Scope_${scope}`) : scope.replace(/^log_/, '').replace(/_/g, ' ');
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
    // dimensions read by the exploration, for the per row counting hint of the metrics
    const usedDimensions = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => [...(hasRowDimensions(state.visualization) ? state.rows : []), ...(hasRowDimensions(state.visualization) && state.column ? [state.column] : []), ...state.filters.map(filter => filter.dimension)]);
    function dimensionName(id) {
      return dimensionLabels.value[id] || id;
    }
    function metricName(id) {
      return metricLabels.value[id] || id;
    }
    function metricReason(id) {
      return metricsById.value[id] ? '' : Object(external_CoreHome_["translate"])('DataExplorer_UnknownVariable');
    }
    function dimensionReason(id) {
      return dimensionsById.value[id] ? '' : Object(external_CoreHome_["translate"])('DataExplorer_UnknownVariable');
    }
    // a visit has several actions and conversions, an action shares its visit with the
    // conversions and the other way round: under such a dimension the metric counts each of
    // its rows once in every row of the report
    const perRowHints = {
      visit: 'DataExplorer_MetricPerVisitHint',
      action: 'DataExplorer_MetricPerActionHint',
      conversion: 'DataExplorer_MetricPerConversionHint'
    };
    function metricHint(id) {
      const metric = metricsById.value[id];
      if (!metric) {
        return '';
      }
      const repeats = usedDimensions.value.map(dimensionId => dimensionsById.value[dimensionId]).some(dimension => dimension && (metric.repeatedBy || []).includes(dimension.scope));
      return repeats ? Object(external_CoreHome_["translate"])(perRowHints[metric.scope] || 'DataExplorer_MetricPerRowHint') : '';
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
      reason: metricReason(id),
      hint: metricHint(id)
    })));
    const missingOf = kind => importedUnsupported.value.filter(entry => entry.kind === kind).map(entry => ({
      id: entry.id,
      name: entry.name,
      reason: entry.reason,
      missing: true
    }));
    const rowZoneItems = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => [...rowItems.value, ...missingOf('dimension')]);
    const valueZoneItems = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => [...valueItems.value, ...missingOf('metric')]);
    const segmentItems = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => state.segment ? [{
      id: state.segment,
      name: segmentNames.value[state.segment] || state.segment,
      reason: ''
    }] : []);
    const filterViews = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => state.filters.map((filter, index) => ({
      filter,
      index,
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
          column: null,
          chart: state.visualization
        });
      }
      return def;
    });
    // the view and layout are sent explicitly: core would otherwise reuse the view this user last
    // picked in a table footer, and Report settings would no longer describe the canvas
    function viewParams() {
      const params = {
        viewDataTable: VIEW_BY_VISUALIZATION[state.visualization]
      };
      if (state.visualization === 'table' && state.rows.length > 1) {
        const flat = state.tableView === 'flat';
        params.flat = flat ? '1' : '0';
        params.show_dimensions = flat ? '1' : '0';
      }
      return params;
    }
    function run() {
      previewParams.value = isRunnable.value ? Object.assign({
        module: 'DataExplorer',
        action: 'preview',
        definition: JSON.stringify(requestDefinition.value),
        period: period.value,
        date: date.value
      }, viewParams()) : null;
    }
    // a view or layout picked in the canvas footer becomes the one of the exploration
    function onCanvasView(event) {
      const visualization = VISUALIZATION_BY_VIEW[event.viewDataTable];
      if (visualization && visualization !== state.visualization) {
        state.visualization = visualization;
      }
      if (state.visualization === 'table' && state.rows.length > 1) {
        const tableView = event.flat ? 'flat' : 'hierarchical';
        if (tableView !== state.tableView) {
          state.tableView = tableView;
        }
      }
    }
    let runTimer = null;
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
      if (encoded === lastEncoded && current[HASH_PARAM] === encoded && (current[SQL_VIEW_PARAM] || 'table') === sqlVisualization.value) {
        return;
      }
      lastEncoded = encoded;
      const params = Object.assign(Object.assign({}, current), {}, {
        [HASH_PARAM]: encoded
      });
      if (sqlVisualization.value !== 'table') {
        params[SQL_VIEW_PARAM] = sqlVisualization.value;
      } else {
        delete params[SQL_VIEW_PARAM];
      }
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
      openedSql.value = null;
      if (report.type === 'sql') {
        sql.value = report.definition.sql || '';
        sqlVisualization.value = fromDefinition({
          chart: report.definition.chart
        }, report.report_type).visualization;
        openedSqlVisualization.value = sqlVisualization.value;
        openedSql.value = sql.value;
        openedSnapshot.value = '';
        sqlOpened.value = true;
        activeTab.value = 'sql';
        return;
      }
      if (!keepState) {
        applyState(fromDefinition(report.definition, report.report_type));
      }
      openedSnapshot.value = snapshotOf(fromDefinition(report.definition, report.report_type));
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
        openedSnapshot.value = '';
        openedSql.value = null;
      }
      if (encoded && encoded !== lastEncoded) {
        applyState(decode(encoded));
      }
      const sqlView = String(hash[SQL_VIEW_PARAM] || 'table');
      const known = !!VIEW_BY_VISUALIZATION[sqlView];
      if (known && sqlView !== sqlVisualization.value) {
        sqlVisualization.value = sqlView;
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
    const customReportOpen = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(false);
    function onCustomReportCreated(event) {
      noticeContext.value = 'success';
      notice.value = Object(external_CoreHome_["translate"])('DataExplorer_CustomReportCreated', event.name);
      external_CoreHome_["ReportingMenuStore"].reloadMenuItems();
    }
    function onCustomReportFailed(message) {
      noticeContext.value = 'error';
      notice.value = message;
    }
    // custom reports open as a new exploration, their key prefixed so it never matches a report id
    const CUSTOM_REPORT_KEY = 'customReport-';
    const customReportList = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])([]);
    const importedCustomReport = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    // the custom report button updates the opened custom report when this user may edit it
    const updatableCustomReport = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => !editing.value && importedCustomReport.value && importedCustomReport.value.editable ? importedCustomReport.value : null);
    // saved as an exploration, an opened custom report keeps its name, description and place
    const saveSuggestion = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      const report = importedCustomReport.value;
      return !editing.value && report ? {
        name: report.name,
        description: report.description,
        category: report.category,
        subcategory: report.subcategory
      } : null;
    });
    function loadCustomReports() {
      if (!props.customReports) {
        return Promise.resolve();
      }
      return external_CoreHome_["AjaxHelper"].fetch({
        method: 'DataExplorer.getCustomReports',
        idSite,
        filter_limit: -1
      }, {
        createErrorNotification: false
      }).then(result => {
        customReportList.value = Array.isArray(result) ? result : [];
      }).catch(() => {
        customReportList.value = [];
      });
    }
    function onCustomReportUpdated(event) {
      if (importedCustomReport.value) {
        importedCustomReport.value = Object.assign(Object.assign({}, importedCustomReport.value), event);
      }
      noticeContext.value = 'success';
      notice.value = Object(external_CoreHome_["translate"])('DataExplorer_CustomReportUpdated', event.name);
      noticeLink.value = importedCustomReport.value ? {
        notice: notice.value,
        url: customReportUrl(importedCustomReport.value),
        label: Object(external_CoreHome_["translate"])('DataExplorer_OpenCustomReport')
      } : null;
      openedSnapshot.value = snapshotOf(state);
      external_CoreHome_["ReportingMenuStore"].reloadMenuItems();
      loadCustomReports();
    }
    const pickedReport = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      if (editing.value) {
        return String(editing.value.idreport);
      }
      return importedCustomReport.value ? `${CUSTOM_REPORT_KEY}${importedCustomReport.value.idCustomReport}` : '';
    });
    // without Custom Reports the explorations are the only group, and need no title
    const reportGroups = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => [{
      label: props.customReports ? Object(external_CoreHome_["translate"])('DataExplorer_ExplorationsGroup') : '',
      items: openableReports.value.map(report => ({
        key: String(report.idreport),
        name: report.name,
        place: report.place || ''
      }))
    }, {
      label: Object(external_CoreHome_["translate"])('DataExplorer_CustomReportsGroup'),
      items: customReportList.value.map(report => ({
        key: `${CUSTOM_REPORT_KEY}${report.idcustomreport}`,
        name: report.name,
        place: report.place || ''
      }))
    }]);
    function openCustomReport(idCustomReport) {
      const report = customReportList.value.find(item => item.idcustomreport === idCustomReport);
      external_CoreHome_["AjaxHelper"].fetch({
        method: 'DataExplorer.getCustomReportDefinition',
        idSite,
        idCustomReport
      }, {
        createErrorNotification: false
      }).then(result => {
        editing.value = null;
        importedCustomReport.value = result.customReport;
        importedUnsupported.value = result.unsupportedEntries || [];
        activeTab.value = 'explore';
        applyState(fromDefinition(result.definition, result.definition.report_type));
        openedSnapshot.value = snapshotOf(state);
        noticeContext.value = result.unsupported.length ? 'warning' : 'info';
        notice.value = [Object(external_CoreHome_["translate"])(result.customReport.editable ? 'DataExplorer_CustomReportOpened' : 'DataExplorer_CustomReportOpenedReadOnly', report ? report.name : String(idCustomReport)), result.unsupported.length ? Object(external_CoreHome_["translate"])('DataExplorer_CustomReportNotOpenedHint') : ''].filter(text => text !== '').join(' ');
        syncHash();
        scheduleRun();
      }).catch(e => {
        noticeContext.value = 'error';
        notice.value = e.message;
      });
    }
    function pickReport(value) {
      notice.value = '';
      if (String(value).startsWith(CUSTOM_REPORT_KEY)) {
        openCustomReport(parseInt(String(value).slice(CUSTOM_REPORT_KEY.length), 10));
        return;
      }
      importedCustomReport.value = null;
      importedUnsupported.value = [];
      const id = parseInt(String(value), 10);
      const report = reports.value.find(item => item.idreport === id) || null;
      if (!report) {
        editing.value = null;
        openedSnapshot.value = '';
        openedSql.value = null;
        applyState(defaultState());
        sql.value = '';
        sqlVisualization.value = 'table';
        syncHash();
        scheduleRun();
        return;
      }
      openReport(report, false);
      lastEncoded = '';
      syncHash();
    }
    const leaveConfirmOpen = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(false);
    let pendingPick = null;
    // another report, or a new exploration, replaces the changes not saved yet: asked first
    function onPickReport(value) {
      if (isModified.value && String(value) !== pickedReport.value) {
        pendingPick = String(value);
        leaveConfirmOpen.value = true;
        return;
      }
      pickReport(value);
    }
    function confirmLeave() {
      if (pendingPick !== null) {
        pickReport(pendingPick);
      }
      pendingPick = null;
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
        const group = state.filters.reduce((max, filter) => Math.max(max, filter.group + 1), 0);
        state.filters.push({
          dimension: id,
          operator: 'equals',
          value: '',
          group
        });
      } else {
        state.segment = id;
      }
    }
    function removeFromZone(zone, id) {
      if (importedUnsupported.value.some(entry => entry.id === id)) {
        importedUnsupported.value = importedUnsupported.value.filter(entry => entry.id !== id);
        return;
      }
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
    // in place: the rows of the Filters zone are keyed by their filter object
    function updateFilter(index, filter) {
      Object.assign(state.filters[index], filter);
    }
    function removeFilter(index) {
      state.filters.splice(index, 1);
      const order = new Map();
      state.filters.forEach(filter => {
        if (!order.has(filter.group)) {
          order.set(filter.group, order.size);
        }
        filter.group = order.get(filter.group);
      });
    }
    // a filter dragged to another group (OR) or to a new one (AND, group -1), groups renumbered
    function moveFilter(index, group) {
      const filter = state.filters[index];
      if (!filter) {
        return;
      }
      const target = group < 0 ? state.filters.reduce((max, item) => Math.max(max, item.group + 1), 0) : group;
      if (target === filter.group) {
        return;
      }
      state.filters.splice(index, 1);
      const last = state.filters.map(item => item.group).lastIndexOf(target);
      const at = last < 0 ? state.filters.length : last + 1;
      state.filters.splice(at, 0, Object.assign(Object.assign({}, filter), {}, {
        group: target
      }));
      const order = new Map();
      state.filters.forEach(item => {
        if (!order.has(item.group)) {
          order.set(item.group, order.size);
        }
        item.group = order.get(item.group);
      });
    }
    // a filter ORed with those of the group, placed after them
    function addFilterToGroup(group, id) {
      const filter = {
        dimension: id,
        operator: 'equals',
        value: '',
        group
      };
      const last = state.filters.map(item => item.group).lastIndexOf(group);
      state.filters.splice(last + 1, 0, filter);
    }
    // saving
    const saveReportType = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => reportTypeOf(saveType.value === 'sql' ? sqlVisualization.value : state.visualization));
    const saveDefinition = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      if (saveType.value === 'sql') {
        return {
          sql: sql.value,
          chart: sqlVisualization.value === 'bar' || sqlVisualization.value === 'pie' ? sqlVisualization.value : ''
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
          importedCustomReport.value = null;
          importedUnsupported.value = [];
          openedSnapshot.value = report.type === 'visual' ? snapshotOf(state) : '';
          openedSql.value = report.type === 'sql' ? sql.value : null;
          openedSqlVisualization.value = sqlVisualization.value;
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
    // entering the explorer (no exploration nor report in the URL: not a reload, not a shared link)
    // looks at the last 28 days; a period picked inside the explorer is then kept
    function startOnLast28Days() {
      const current = external_CoreHome_["MatomoUrl"].hashParsed.value;
      if (!external_CoreHome_["Matomo"].helper.isReportingPage() || current[HASH_PARAM] || current[REPORT_HASH_PARAM]) {
        return;
      }
      if (current.period === DEFAULT_PERIOD && current.date === DEFAULT_DATE) {
        return;
      }
      external_CoreHome_["MatomoUrl"].updateHash(Object.assign(Object.assign({}, current), {}, {
        period: DEFAULT_PERIOD,
        date: DEFAULT_DATE
      }));
    }
    Object(external_commonjs_vue_commonjs2_vue_root_Vue_["onMounted"])(() => {
      external_CoreHome_["Matomo"].on('ReportingPage.loadPage', keepPageOnUrlChange);
      startOnLast28Days();
      Promise.all([external_CoreHome_["AjaxHelper"].fetch({
        method: 'DataExplorer.getCatalog',
        idSite
      }, {
        createErrorNotification: false
      }).then(result => {
        catalog.value = result;
      }).catch(e => {
        catalogError.value = e.message;
      }), loadReports(), loadCustomReports()]).then(() => {
        readHash();
        stopWatchers.push(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["watch"])(() => [JSON.stringify(requestDefinition.value), period.value, date.value, segment.value], () => scheduleRun()));
        stopWatchers.push(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["watch"])(() => JSON.stringify(state), () => syncHash()));
        stopWatchers.push(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["watch"])(sqlVisualization, () => syncHash()));
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
    });
    return {
      definition,
      customReportOpen,
      onCustomReportCreated,
      onCustomReportFailed,
      reportGroups,
      pickedReport,
      sqlVisualization,
      onCanvasView,
      updatableCustomReport,
      isModified,
      isEditingVisual,
      isEditingSql,
      leaveConfirmOpen,
      confirmLeave,
      noticeLink,
      rowZoneItems,
      valueZoneItems,
      saveSuggestion,
      onCustomReportUpdated,
      periodText,
      periodDates,
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
      previewParams,
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
      addFilterToGroup,
      moveFilter,
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



Explorervue_type_script_lang_ts.render = render

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
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/DataExplorer/vue/src/Manage/ReportsManager.vue?vue&type=template&id=cab14fb4

const ReportsManagervue_type_template_id_cab14fb4_hoisted_1 = {
  class: "dataExplorerManage"
};
const ReportsManagervue_type_template_id_cab14fb4_hoisted_2 = {
  class: "dataExplorerManage__intro"
};
const ReportsManagervue_type_template_id_cab14fb4_hoisted_3 = {
  class: "dataExplorerManage__search"
};
const ReportsManagervue_type_template_id_cab14fb4_hoisted_4 = {
  class: "first"
};
const ReportsManagervue_type_template_id_cab14fb4_hoisted_5 = {
  class: "dataExplorerManage__actions"
};
const ReportsManagervue_type_template_id_cab14fb4_hoisted_6 = {
  key: 0
};
const ReportsManagervue_type_template_id_cab14fb4_hoisted_7 = {
  colspan: "8"
};
const ReportsManagervue_type_template_id_cab14fb4_hoisted_8 = {
  key: 1
};
const ReportsManagervue_type_template_id_cab14fb4_hoisted_9 = {
  colspan: "8"
};
const ReportsManagervue_type_template_id_cab14fb4_hoisted_10 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("br", null, null, -1);
const ReportsManagervue_type_template_id_cab14fb4_hoisted_11 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("br", null, null, -1);
const ReportsManagervue_type_template_id_cab14fb4_hoisted_12 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("br", null, null, -1);
const ReportsManagervue_type_template_id_cab14fb4_hoisted_13 = {
  class: "first"
};
const ReportsManagervue_type_template_id_cab14fb4_hoisted_14 = {
  class: "entityTable_ActionCell entityTable_ActionCell-3 dataExplorerManage__actions"
};
const ReportsManagervue_type_template_id_cab14fb4_hoisted_15 = ["href", "title", "aria-label"];
const ReportsManagervue_type_template_id_cab14fb4_hoisted_16 = ["href", "title", "aria-label"];
const ReportsManagervue_type_template_id_cab14fb4_hoisted_17 = ["title", "aria-label", "onClick"];
const ReportsManagervue_type_template_id_cab14fb4_hoisted_18 = {
  class: "tableActionBar"
};
const ReportsManagervue_type_template_id_cab14fb4_hoisted_19 = ["href"];
const ReportsManagervue_type_template_id_cab14fb4_hoisted_20 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "icon-add",
  "aria-hidden": "true"
}, null, -1);
const _hoisted_21 = {
  class: "ui-confirm"
};
const _hoisted_22 = ["value"];
const _hoisted_23 = ["value"];
function ReportsManagervue_type_template_id_cab14fb4_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Notification = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Notification");
  const _component_Field = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Field");
  const _component_ActivityIndicator = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("ActivityIndicator");
  const _component_MatomoDialog = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("MatomoDialog");
  const _directive_content_table = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveDirective"])("content-table");
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", ReportsManagervue_type_template_id_cab14fb4_hoisted_1, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", ReportsManagervue_type_template_id_cab14fb4_hoisted_2, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_ManageIntro')), 1), _ctx.error ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_Notification, {
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
  })) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", ReportsManagervue_type_template_id_cab14fb4_hoisted_3, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
    modelValue: _ctx.search,
    "onUpdate:modelValue": _cache[0] || (_cache[0] = $event => _ctx.search = $event),
    uicontrol: "text",
    name: "dataExplorerManageSearch",
    title: _ctx.translate('General_Search')
  }, null, 8, ["modelValue", "title"]), [[external_commonjs_vue_commonjs2_vue_root_Vue_["vShow"], _ctx.reports.length > 0]])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])((Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("table", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("thead", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("tr", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", ReportsManagervue_type_template_id_cab14fb4_hoisted_4, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Id')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Name')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_ManageColumnType')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_ManageColumnLocation')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_SaveSites')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_ManageColumnAuthor')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_ManageColumnUpdated')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", ReportsManagervue_type_template_id_cab14fb4_hoisted_5, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Actions')), 1)])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("tbody", null, [_ctx.loading ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("tr", ReportsManagervue_type_template_id_cab14fb4_hoisted_6, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", ReportsManagervue_type_template_id_cab14fb4_hoisted_7, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_ActivityIndicator, {
    loading: true
  })])])) : !_ctx.rows.length ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("tr", ReportsManagervue_type_template_id_cab14fb4_hoisted_8, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", ReportsManagervue_type_template_id_cab14fb4_hoisted_9, [ReportsManagervue_type_template_id_cab14fb4_hoisted_10, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(" " + Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.reports.length ? _ctx.translate('DataExplorer_ManageNoMatch') : _ctx.translate('DataExplorer_ManageEmpty')) + " ", 1), ReportsManagervue_type_template_id_cab14fb4_hoisted_11, ReportsManagervue_type_template_id_cab14fb4_hoisted_12])])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.rows, report => {
    return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("tr", {
      key: report.idreport
    }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", ReportsManagervue_type_template_id_cab14fb4_hoisted_13, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(report.idreport), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(report.name), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.typeText(report)), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.locationText(report)), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.sitesText(report)), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(report.created_by || _ctx.translate('DataExplorer_ManageAuthorDeleted')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(report.updated_date || report.created_date), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", ReportsManagervue_type_template_id_cab14fb4_hoisted_14, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
      class: "table-action icon-show",
      href: _ctx.reportUrl(report),
      title: _ctx.translate('DataExplorer_ManageView', report.name),
      "aria-label": _ctx.translate('DataExplorer_ManageView', report.name)
    }, null, 8, ReportsManagervue_type_template_id_cab14fb4_hoisted_15), _ctx.canOpen(report) ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("a", {
      key: 0,
      class: "table-action icon-edit",
      href: _ctx.explorerUrl(report),
      title: _ctx.translate('DataExplorer_ManageEdit', report.name),
      "aria-label": _ctx.translate('DataExplorer_ManageEdit', report.name)
    }, null, 8, ReportsManagervue_type_template_id_cab14fb4_hoisted_16)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), report.editable ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("button", {
      key: 1,
      type: "button",
      class: "table-action icon-delete",
      title: _ctx.translate('DataExplorer_ManageDelete', report.name),
      "aria-label": _ctx.translate('DataExplorer_ManageDelete', report.name),
      onClick: $event => _ctx.askDelete(report)
    }, null, 8, ReportsManagervue_type_template_id_cab14fb4_hoisted_17)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)])]);
  }), 128))])])), [[_directive_content_table]]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", ReportsManagervue_type_template_id_cab14fb4_hoisted_18, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    class: "dataExplorerManage__create",
    href: _ctx.explorerUrl(null)
  }, [ReportsManagervue_type_template_id_cab14fb4_hoisted_20, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(" " + Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_NewExploration')), 1)], 8, ReportsManagervue_type_template_id_cab14fb4_hoisted_19)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_MatomoDialog, {
    modelValue: _ctx.confirmOpen,
    "onUpdate:modelValue": _cache[1] || (_cache[1] = $event => _ctx.confirmOpen = $event),
    onYes: _ctx.confirmDelete
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_21, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h2", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('DataExplorer_ManageDeleteConfirm', _ctx.toDelete ? _ctx.toDelete.name : '')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("input", {
      role: "yes",
      type: "button",
      value: _ctx.translate('General_Yes')
    }, null, 8, _hoisted_22), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("input", {
      role: "no",
      type: "button",
      value: _ctx.translate('General_No')
    }, null, 8, _hoisted_23)])]),
    _: 1
  }, 8, ["modelValue", "onYes"])]);
}
// CONCATENATED MODULE: ./plugins/DataExplorer/vue/src/Manage/ReportsManager.vue?vue&type=template&id=cab14fb4

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
      // a report saved without a page has a page of its own, named after it
      const pageName = report.subcategory === '' ? report.name : report.subcategory;
      return `${categoryName} > ${subcategory ? subcategory.name : pageName}`;
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



ReportsManagervue_type_script_lang_ts.render = ReportsManagervue_type_template_id_cab14fb4_render

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