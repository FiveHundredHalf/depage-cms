/**
* ### toolbar plugin
*
* Adds toolbar functionality to jsTree
*/
/*globals jQuery, define, exports, require, document */
(function (factory) {
    "use strict";
    if (typeof define === 'function' && define.amd) {
            define('jstree.toolbar', ['jquery','jstree'], factory);
    }
    else if(typeof exports === 'object') {
            factory(require('jquery'), require('jstree'));
    }
    else {
            factory(jQuery, jQuery.jstree);
    }
}(function ($, jstree, undefined) {
    "use strict";

    if($.jstree.plugins.toolbar) { return; }

    /**
     * toolbar configuration
     *
     * @name $.jstree.defaults.toolbar
     * @plugin toolbar
     */
    $.jstree.defaults.toolbar = null;
    $.jstree.plugins.toolbar = function (options, parent) {
        // {{{ init()
        this.init = function (el, options) {
            this._data.toolbar = {};
            parent.init.call(this, el, options);
            this._data.toolbar.inst = this.element.jstree(true);
            this._data.toolbar.$el = $("<span></span>").appendTo("#toolbarmain .tree-actions");


            var $toolbar = this._data.toolbar.$el;

            if (this._data.focus.focused) {
                $toolbar.addClass("visible");
            }

            this.element
                .on("focus.jstree", function() {
                    $toolbar.addClass("visible");
                })
                .on("blur.jstree", function() {
                    $toolbar.removeClass("visible");
                });
        };
        // }}}
        // {{{ activate_node()
        this.activate_node = function(obj, e) {
            var inst = this._data.toolbar.inst;

            parent.activate_node.call(this, obj, e);

            var node = inst.get_node(inst.get_selected());
            this._data.toolbar.$el.empty();

            var nodesForSelf = inst.getAvailableNodesFor(node);

            this.addToolbarButton("create", "button-create", function() {
                $.vakata.context.show($(this), {x: 160, y: 28 }, inst.getCreateMenu(inst, nodesForSelf, inst.insertCallback(node, "last")));
            });
            this.addToolbarButton("delete", "button-delete", function() {
                inst.askDelete(node);
            });
            this.addToolbarButton("duplicate", "button-duplicate", function() {
                inst.copy_node(node, node, "after");
            });
            this.addToolbarButton("reload", "button-reload", function() {
                inst.refresh(true);
            });
            this.addToolbarButton("rnode", "button-reload", function() {
                inst.refresh_node(node);
            });
        };
        // }}}
        // {{{ destroy()
        this.destroy = function(keep_html) {
            this._data.toolbar.$el.remove();

            parent.destroy.call(this, keep_html);
        };
        // }}}
        // {{{ addToolbarButton()
        this.addToolbarButton = function(name, className, callback) {
            var $button = $("<a></a>");
            $button
                .text(name)
                .addClass("button")
                .addClass(className).
                on("click", callback);

            $button.appendTo(this._data.toolbar.$el);
        };
        // }}}
    };
}));

// vim:set ft=javascript sw=4 sts=4 fdm=marker :
