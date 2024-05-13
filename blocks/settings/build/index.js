/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/components/header.js":
/*!**********************************!*\
  !*** ./src/components/header.js ***!
  \**********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);


const Header = props => {
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "integration-toolkit-for-beehiiv-settings-header",
    key: "settings-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "integration-toolkit-for-beehiiv-settings-header__logo"
  }, "Integration Toolkit for beehiiv"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "integration-toolkit-for-beehiiv-settings-header__links"
  }));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Header);

/***/ }),

/***/ "./src/components/tabs.js":
/*!********************************!*\
  !*** ./src/components/tabs.js ***!
  \********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__);





const Tabs = props => {
  const [saving, setSaving] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [loading, setLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [apiKey, setApiKey] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(integration_toolkit_for_beehiiv_settings.api_key);
  const [publicationId, setPublicationId] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(integration_toolkit_for_beehiiv_settings.publication_id);
  const [status, setStatus] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(integration_toolkit_for_beehiiv_settings.api_status);
  const [onSaveMessage, setOnSaveMessage] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)("");
  const saveSettings = () => {
    const settings = {
      apiKey: apiKey,
      publicationId: publicationId,
      status: 'connected'
    };
    setSaving(true);
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
      path: "/rebeehiiv/v1/save_settings",
      method: "POST",
      data: settings
    }).then(response => {
      if (!response.success) {
        setSaving(false);
      } else {
        setStatus('connected');
        setSaving(false);
      }
      setOnSaveMessage(response.message);
    }).catch(error => {
      setSaving(false);
      console.log(error);
    });
  };
  const removeAPIKey = () => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
      path: "/rebeehiiv/v1/disconnect_api",
      method: "POST"
    }).then(response => {
      if (!response.success) {
        setLoading(false);
      } else {
        setStatus(false);
        setApiKey("");
        setPublicationId("");
      }
      setOnSaveMessage(response.message);
    }).catch(error => {
      setLoading(false);
      console.log(error);
    });
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "integration-toolkit-for-beehiiv-heading"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", null, "Settings"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Establish a connection between your WordPress website and by providing the necessary credentials.', 'integration-toolkit-for-beehiiv'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "integration-toolkit-for-beehiiv-tabs"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("nav", {
    className: "nav-tab-wrapper"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    className: "re-nav-tab re-nav-tab-active",
    "data-tab": "integration-toolkit-for-beehiiv-credentials",
    href: "#"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Beehiiv Credentials', 'integration-toolkit-for-beehiiv')))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "integration-toolkit-for-beehiiv-settings-tabs integration-toolkit-for-beehiiv-wrapper",
    key: "settings-tabs"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "integration-toolkit-for-beehiiv-settings-tabs-menu",
    key: "settings-tabs"
  }), onSaveMessage && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Snackbar, {
    className: "integration-toolkit-for-beehiiv-snackbar integration-toolkit-for-beehiiv-snackbar-settings",
    explicitDismiss: true,
    onDismiss: () => setOnSaveMessage(""),
    status: "success"
  }, onSaveMessage), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.PanelRow, {
    className: "mt-0"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.__experimentalGrid, {
    columns: 1,
    style: {
      width: "100%"
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.__experimentalInputControl, {
    type: "password",
    help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Enter the unique API key you received from. This key authorizes and facilitates the communication between your WordPress website and.", 'integration-toolkit-for-beehiiv'),
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("API Key", 'integration-toolkit-for-beehiiv'),
    onChange: value => setApiKey(value),
    placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Enter your API key", 'integration-toolkit-for-beehiiv'),
    value: apiKey
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.__experimentalInputControl, {
    type: "password",
    help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Input the specific ID related to the content or publication you intend to import. This helps in pinpointing the exact data you want to fetch from.", 'integration-toolkit-for-beehiiv'),
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Publication ID", 'integration-toolkit-for-beehiiv'),
    placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Enter your publication ID", 'integration-toolkit-for-beehiiv'),
    onChange: value => setPublicationId(value),
    value: publicationId
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "integration-toolkit-for-beehiiv-settings-tabs-contents-actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Button, {
    isPrimary: true,
    style: {
      marginRight: "1em"
    },
    onClick: () => saveSettings(),
    isBusy: saving,
    disabled: status == 'connected',
    className: "integration-toolkit-for-beehiiv-settings-save"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Save', 'integration-toolkit-for-beehiiv')), status && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Button, {
    style: {
      marginRight: "1em"
    },
    isDestructive: true,
    onClick: () => removeAPIKey(),
    className: "integration-toolkit-for-beehiiv-settings-disconnect"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Disconnect", 'integration-toolkit-for-beehiiv')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: "https://app.beehiiv.com/settings/integrations",
    target: "_blank"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Get your API key", 'integration-toolkit-for-beehiiv')))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Tabs);

/***/ }),

/***/ "./src/components/settings.css":
/*!*************************************!*\
  !*** ./src/components/settings.css ***!
  \*************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ ((module) => {

module.exports = window["React"];

/***/ }),

/***/ "@wordpress/api-fetch":
/*!**********************************!*\
  !*** external ["wp","apiFetch"] ***!
  \**********************************/
/***/ ((module) => {

module.exports = window["wp"]["apiFetch"];

/***/ }),

/***/ "@wordpress/components":
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wp"]["components"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ ((module) => {

module.exports = window["wp"]["element"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["i18n"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
(() => {
/*!**********************!*\
  !*** ./src/index.js ***!
  \**********************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _components_settings_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./components/settings.css */ "./src/components/settings.css");
/* harmony import */ var _components_header__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./components/header */ "./src/components/header.js");
/* harmony import */ var _components_tabs__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./components/tabs */ "./src/components/tabs.js");





const Settings = props => {
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "integration-toolkit-for-beehiiv-settings-wrap"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_header__WEBPACK_IMPORTED_MODULE_3__["default"], null), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_tabs__WEBPACK_IMPORTED_MODULE_4__["default"], null));
};
var rootElement = document.getElementById("integration-toolkit-for-beehiiv-settings");
if (rootElement) {
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.render)((0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Settings, {
    scope: "global"
  }), rootElement);
}
})();

/******/ })()
;
//# sourceMappingURL=index.js.map