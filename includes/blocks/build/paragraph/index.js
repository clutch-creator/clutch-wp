/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./blocks-src/paragraph/block.json":
/*!*****************************************!*\
  !*** ./blocks-src/paragraph/block.json ***!
  \*****************************************/
/***/ ((module) => {

module.exports = /*#__PURE__*/JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"clutch/paragraph","version":"1.0.0","title":"CLUTCH Paragraph","category":"text","icon":"smiley","description":"Start with the basic building block of all narrative.","keywords":["text"],"textdomain":"default","attributes":{"align":{"type":"string"},"content":{"type":"rich-text","source":"rich-text","selector":"p","role":"content"},"placeholder":{"type":"string"}},"supports":{"splitting":true,"anchor":true,"className":false,"__experimentalBorder":{"color":true,"radius":true,"style":true,"width":true},"color":{"gradients":true,"link":true,"__experimentalDefaultControls":{"background":true,"text":true}},"spacing":{"margin":true,"padding":true,"__experimentalDefaultControls":{"margin":false,"padding":false}},"typography":{"fontSize":true,"lineHeight":true,"__experimentalFontFamily":true,"__experimentalTextDecoration":true,"__experimentalFontStyle":true,"__experimentalFontWeight":true,"__experimentalLetterSpacing":true,"__experimentalTextTransform":true,"__experimentalWritingMode":true,"__experimentalDefaultControls":{"fontSize":true}},"__experimentalSelector":"p","__unstablePasteTextInline":true,"interactivity":{"clientNavigation":true}},"editorScript":"file:./index.js","editorStyle":"file:./index.css","style":"file:./style-index.css","viewScript":"file:./view.js"}');

/***/ }),

/***/ "./blocks-src/paragraph/edit.js":
/*!**************************************!*\
  !*** ./blocks-src/paragraph/edit.js ***!
  \**************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _use_enter_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./use-enter.js */ "./blocks-src/paragraph/use-enter.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/**
 * WordPress dependencies
 */





/**
 * Internal dependencies
 */


function ParagraphBlock({
  attributes,
  mergeBlocks,
  onReplace,
  onRemove,
  setAttributes,
  clientId,
  isSelected: isSingleSelected,
  name
}) {
  const [availableClasses, setAvailableClasses] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)([]);
  const {
    align,
    content,
    placeholder,
    className
  } = attributes;
  const blockProps = (0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__.useBlockProps)({
    ref: (0,_use_enter_js__WEBPACK_IMPORTED_MODULE_5__.useOnEnter)({
      clientId,
      content
    })
  });
  const blockEditingMode = (0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__.useBlockEditingMode)();
  const classesMap = availableClasses.reduce((acc, {
    className: clutchClass
  }) => {
    acc[clutchClass] = className?.split(' ').includes(clutchClass);
    return acc;
  }, {});

  // @todo: Pass these through PHP when the page loads instead, possibly through
  // the block editor context (may need to register a custom entity/store).
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useEffect)(() => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_4__({
      path: 'clutch/v1/block-styles'
    }).then(setAvailableClasses);
  }, []);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.Fragment, {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__.InspectorControls, {
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Panel, {
        header: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Settings'),
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.PanelBody, {
          title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Paragraph styles'),
          children: availableClasses.map(({
            label,
            className: clutchClass
          }) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.PanelRow, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.ToggleControl, {
              label: label,
              checked: classesMap[clutchClass],
              onChange: state => {
                classesMap[clutchClass] = state;
                setAttributes({
                  className: availableClasses.reduce((acc, {
                    className: clutchClass
                  }) => {
                    if (classesMap[clutchClass]) {
                      acc.push(clutchClass);
                    }
                    return acc;
                  }, []).join(' ')
                });
              }
            }, clutchClass)
          }))
        })
      })
    }), blockEditingMode === 'default' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__.BlockControls, {
      group: "block",
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__.AlignmentControl, {
        value: align,
        onChange: newAlign => setAttributes({
          align: newAlign
        })
      })
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__.RichText, {
      identifier: "content",
      tagName: "p",
      ...blockProps,
      value: content,
      onChange: newContent => setAttributes({
        content: newContent
      }),
      onMerge: mergeBlocks,
      onReplace: onReplace,
      onRemove: onRemove,
      "aria-label": _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__.RichText.isEmpty(content) ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Empty block; start writing or type forward slash to choose a block') : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Block: Paragraph'),
      "data-empty": _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__.RichText.isEmpty(content),
      placeholder: placeholder || (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Type / to choose a block'),
      "data-custom-placeholder": placeholder ? true : undefined,
      __unstableEmbedURLOnPaste: true,
      __unstableAllowPrefixTransformations: true
    })]
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (ParagraphBlock);

/***/ }),

/***/ "./blocks-src/paragraph/save.js":
/*!**************************************!*\
  !*** ./blocks-src/paragraph/save.js ***!
  \**************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ save)
/* harmony export */ });
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! clsx */ "./node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/**
 * External dependencies
 */


/**
 * WordPress dependencies
 */



function save({
  attributes
}) {
  const {
    align,
    content,
    dropCap,
    direction
  } = attributes;
  const className = align ? `has-text-align-${align}` : '';
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("p", {
    ..._wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__.useBlockProps.save({
      className,
      dir: direction
    }),
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__.RichText.Content, {
      value: content
    })
  });
}

/***/ }),

/***/ "./blocks-src/paragraph/transforms.js":
/*!********************************************!*\
  !*** ./blocks-src/paragraph/transforms.js ***!
  \********************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _block_json__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./block.json */ "./blocks-src/paragraph/block.json");
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */

const transforms = {
  from: [{
    type: 'raw',
    // Paragraph is a fallback and should be matched last.
    priority: 20,
    selector: 'p',
    schema: ({
      phrasingContentSchema,
      isPaste
    }) => ({
      p: {
        children: phrasingContentSchema,
        attributes: isPaste ? [] : ['style', 'id']
      }
    }),
    transform(node) {
      const attributes = (0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__.getBlockAttributes)(_block_json__WEBPACK_IMPORTED_MODULE_1__.name, node.outerHTML);
      const {
        textAlign
      } = node.style || {};
      if (textAlign === 'left' || textAlign === 'center' || textAlign === 'right') {
        attributes.align = textAlign;
      }
      return (0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_block_json__WEBPACK_IMPORTED_MODULE_1__.name, attributes);
    }
  }]
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (transforms);

/***/ }),

/***/ "./blocks-src/paragraph/use-enter.js":
/*!*******************************************!*\
  !*** ./blocks-src/paragraph/use-enter.js ***!
  \*******************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   useOnEnter: () => (/* binding */ useOnEnter)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_keycodes__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/keycodes */ "@wordpress/keycodes");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/**
 * WordPress dependencies
 */






function useOnEnter(props) {
  const {
    batch
  } = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.useRegistry)();
  const {
    moveBlocksToPosition,
    replaceInnerBlocks,
    duplicateBlocks,
    insertBlock
  } = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.useDispatch)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_4__.store);
  const {
    getBlockRootClientId,
    getBlockIndex,
    getBlockOrder,
    getBlockName,
    getBlock,
    getNextBlockClientId,
    canInsertBlockType
  } = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.useSelect)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_4__.store);
  const propsRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(props);
  propsRef.current = props;
  return (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_1__.useRefEffect)(element => {
    function onKeyDown(event) {
      if (event.defaultPrevented) {
        return;
      }
      if (event.keyCode !== _wordpress_keycodes__WEBPACK_IMPORTED_MODULE_2__.ENTER) {
        return;
      }
      const {
        content,
        clientId
      } = propsRef.current;

      // The paragraph should be empty.
      if (content.length) {
        return;
      }
      const wrapperClientId = getBlockRootClientId(clientId);
      if (!(0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_5__.hasBlockSupport)(getBlockName(wrapperClientId), '__experimentalOnEnter', false)) {
        return;
      }
      const order = getBlockOrder(wrapperClientId);
      const position = order.indexOf(clientId);

      // If it is the last block, exit.
      if (position === order.length - 1) {
        let newWrapperClientId = wrapperClientId;
        while (!canInsertBlockType(getBlockName(clientId), getBlockRootClientId(newWrapperClientId))) {
          newWrapperClientId = getBlockRootClientId(newWrapperClientId);
        }
        if (typeof newWrapperClientId === 'string') {
          event.preventDefault();
          moveBlocksToPosition([clientId], wrapperClientId, getBlockRootClientId(newWrapperClientId), getBlockIndex(newWrapperClientId) + 1);
        }
        return;
      }
      const defaultBlockName = (0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_5__.getDefaultBlockName)();
      if (!canInsertBlockType(defaultBlockName, getBlockRootClientId(wrapperClientId))) {
        return;
      }
      event.preventDefault();

      // If it is in the middle, split the block in two.
      const wrapperBlock = getBlock(wrapperClientId);
      batch(() => {
        duplicateBlocks([wrapperClientId]);
        const blockIndex = getBlockIndex(wrapperClientId);
        replaceInnerBlocks(wrapperClientId, wrapperBlock.innerBlocks.slice(0, position));
        replaceInnerBlocks(getNextBlockClientId(wrapperClientId), wrapperBlock.innerBlocks.slice(position + 1));
        insertBlock((0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_5__.createBlock)(defaultBlockName), blockIndex + 1, getBlockRootClientId(wrapperClientId), true);
      });
    }
    element.addEventListener('keydown', onKeyDown);
    return () => {
      element.removeEventListener('keydown', onKeyDown);
    };
  }, []);
}

/***/ }),

/***/ "./blocks-src/utils/init-block.js":
/*!****************************************!*\
  !*** ./blocks-src/utils/init-block.js ***!
  \****************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ initBlock)
/* harmony export */ });
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/**
 * WordPress dependencies
 */


/**
 * Function to register an individual block.
 *
 * @param {Object} block The block to be registered.
 *
 * @return {WPBlockType | undefined} The block, if it has been successfully registered;
 *                        otherwise `undefined`.
 */
function initBlock(block) {
  if (!block) {
    return;
  }
  const {
    metadata,
    settings,
    name
  } = block;
  return (0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__.registerBlockType)({
    name,
    ...metadata
  }, settings);
}

/***/ }),

/***/ "./node_modules/@wordpress/icons/build-module/library/paragraph.js":
/*!*************************************************************************!*\
  !*** ./node_modules/@wordpress/icons/build-module/library/paragraph.js ***!
  \*************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/primitives */ "@wordpress/primitives");
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__);
/**
 * WordPress dependencies
 */


const paragraph = /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_0__.SVG, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24",
  children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_0__.Path, {
    d: "m9.99609 14v-.2251l.00391.0001v6.225h1.5v-14.5h2.5v14.5h1.5v-14.5h3v-1.5h-8.50391c-2.76142 0-5 2.23858-5 5 0 2.7614 2.23858 5 5 5z"
  })
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (paragraph);
//# sourceMappingURL=paragraph.js.map

/***/ }),

/***/ "./node_modules/clsx/dist/clsx.mjs":
/*!*****************************************!*\
  !*** ./node_modules/clsx/dist/clsx.mjs ***!
  \*****************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   clsx: () => (/* binding */ clsx),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
function r(e){var t,f,n="";if("string"==typeof e||"number"==typeof e)n+=e;else if("object"==typeof e)if(Array.isArray(e)){var o=e.length;for(t=0;t<o;t++)e[t]&&(f=r(e[t]))&&(n&&(n+=" "),n+=f)}else for(f in e)e[f]&&(n&&(n+=" "),n+=f);return n}function clsx(){for(var e,t,f=0,n="",o=arguments.length;f<o;f++)(e=arguments[f])&&(t=r(e))&&(n&&(n+=" "),n+=t);return n}/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (clsx);

/***/ }),

/***/ "@wordpress/api-fetch":
/*!**********************************!*\
  !*** external ["wp","apiFetch"] ***!
  \**********************************/
/***/ ((module) => {

module.exports = window["wp"]["apiFetch"];

/***/ }),

/***/ "@wordpress/block-editor":
/*!*************************************!*\
  !*** external ["wp","blockEditor"] ***!
  \*************************************/
/***/ ((module) => {

module.exports = window["wp"]["blockEditor"];

/***/ }),

/***/ "@wordpress/blocks":
/*!********************************!*\
  !*** external ["wp","blocks"] ***!
  \********************************/
/***/ ((module) => {

module.exports = window["wp"]["blocks"];

/***/ }),

/***/ "@wordpress/components":
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wp"]["components"];

/***/ }),

/***/ "@wordpress/compose":
/*!*********************************!*\
  !*** external ["wp","compose"] ***!
  \*********************************/
/***/ ((module) => {

module.exports = window["wp"]["compose"];

/***/ }),

/***/ "@wordpress/data":
/*!******************************!*\
  !*** external ["wp","data"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["data"];

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

/***/ }),

/***/ "@wordpress/keycodes":
/*!**********************************!*\
  !*** external ["wp","keycodes"] ***!
  \**********************************/
/***/ ((module) => {

module.exports = window["wp"]["keycodes"];

/***/ }),

/***/ "@wordpress/primitives":
/*!************************************!*\
  !*** external ["wp","primitives"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wp"]["primitives"];

/***/ }),

/***/ "react/jsx-runtime":
/*!**********************************!*\
  !*** external "ReactJSXRuntime" ***!
  \**********************************/
/***/ ((module) => {

module.exports = window["ReactJSXRuntime"];

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
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!***************************************!*\
  !*** ./blocks-src/paragraph/index.js ***!
  \***************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   metadata: () => (/* reexport default export from named module */ _block_json__WEBPACK_IMPORTED_MODULE_3__),
/* harmony export */   name: () => (/* binding */ name),
/* harmony export */   settings: () => (/* binding */ settings)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/icons */ "./node_modules/@wordpress/icons/build-module/library/paragraph.js");
/* harmony import */ var _utils_init_block_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/init-block.js */ "./blocks-src/utils/init-block.js");
/* harmony import */ var _edit_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./edit.js */ "./blocks-src/paragraph/edit.js");
/* harmony import */ var _block_json__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./block.json */ "./blocks-src/paragraph/block.json");
/* harmony import */ var _save_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./save.js */ "./blocks-src/paragraph/save.js");
/* harmony import */ var _transforms_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./transforms.js */ "./blocks-src/paragraph/transforms.js");
/**
 * Based off of https://github.com/WordPress/gutenberg/tree/v20.3.0/packages/block-library/src/paragraph
 */

/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */





const {
  name
} = _block_json__WEBPACK_IMPORTED_MODULE_3__;

const settings = {
  icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_6__["default"],
  example: {
    attributes: {
      content: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('In a village of La Mancha, the name of which I have no desire to call to mind, there lived not long since one of those gentlemen that keep a lance in the lance-rack, an old buckler, a lean hack, and a greyhound for coursing.')
    }
  },
  __experimentalLabel(attributes, {
    context
  }) {
    const customName = attributes?.metadata?.name;
    if (context === 'list-view' && customName) {
      return customName;
    }
    if (context === 'accessibility') {
      if (customName) {
        return customName;
      }
      const {
        content
      } = attributes;
      return !content || content.length === 0 ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Empty') : content;
    }
  },
  transforms: _transforms_js__WEBPACK_IMPORTED_MODULE_5__["default"],
  merge(attributes, attributesToMerge) {
    return {
      content: (attributes.content || '') + (attributesToMerge.content || '')
    };
  },
  edit: _edit_js__WEBPACK_IMPORTED_MODULE_2__["default"],
  save: _save_js__WEBPACK_IMPORTED_MODULE_4__["default"]
};
(0,_utils_init_block_js__WEBPACK_IMPORTED_MODULE_1__["default"])({
  name,
  metadata: _block_json__WEBPACK_IMPORTED_MODULE_3__,
  settings
});
})();

/******/ })()
;
//# sourceMappingURL=index.js.map