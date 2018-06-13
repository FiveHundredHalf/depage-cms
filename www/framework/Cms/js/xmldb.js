/*
 * @require framework/shared/jquery-1.12.3.min.js
 *
 *
 * @file    js/xmldb.js
 *
 * copyright (c) 2015 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

var DepageXmldb = (function() {
    "use strict";
    /*jslint browser: true*/
    /*global $:false */

    function Xmldb(baseUrl, projectName, docName) {
        this.projectName = projectName;
        this.docName = docName;
        this.baseUrl = baseUrl;
        this.postUrl = baseUrl + "project/" + this.projectName + "/tree/" + this.docName;
    }
    Xmldb.prototype = {
        // {{{ createNode()
        createNode: function() {
        },
        // }}}
        // {{{ copyNode()
        copyNode: function(nodeId, targetId, position) {
            this.ajaxCall("copyNode", {
                "id" : nodeId,
                "target_id" : targetId,
                "position" : position
            });
        },
        // }}}
        // {{{ copyNodeIn()
        copyNodeIn: function(nodeId, targetId) {
            this.ajaxCall("copyNodeIn", {
                "id" : nodeId,
                "target_id" : targetId
            });
        },
        // }}}
        // {{{ copyNodeBefore()
        copyNodeBefore: function(nodeId, targetId) {
            this.ajaxCall("copyNodeBefore", {
                "id" : nodeId,
                "target_id" : targetId
            });
        },
        // }}}
        // {{{ copyNodeAfter()
        copyNodeAfter: function(nodeId, targetId) {
            this.ajaxCall("copyNodeAfter", {
                "id" : nodeId,
                "target_id" : targetId
            });
        },
        // }}}
        // {{{ moveNode()
        moveNode: function(nodeId, targetId, position) {
            this.ajaxCall("moveNode", {
                "id" : nodeId,
                "target_id" : targetId,
                "position" : position
            });
        },
        // }}}
        // {{{ moveNodeIn()
        moveNodeIn: function(nodeId, targetId) {
            this.ajaxCall("moveNodeIn", {
                "id" : nodeId,
                "target_id" : targetId
            });
        },
        // }}}
        // {{{ moveNodeBefore()
        moveNodeBefore: function(nodeId, targetId) {
            this.ajaxCall("moveNodeBefore", {
                "id" : nodeId,
                "target_id" : targetId
            });
        },
        // }}}
        // {{{ moveNodeAfter()
        moveNodeAfter: function(nodeId, targetId) {
            this.ajaxCall("moveNodeAfter", {
                "id" : nodeId,
                "target_id" : targetId
            });
        },
        // }}}
        // {{{ renameNode()
        renameNode: function(nodeId, name) {
            this.ajaxCall("renameNode", {
                "id" : nodeId,
                "name" : name
            });
        },
        // }}}
        // {{{ deleteNode()
        deleteNode: function(nodeId) {
            this.ajaxCall("deleteNode", {
                "id" : nodeId
            });
        },
        // }}}
        // {{{ duplicateNode()
        duplicateNode: function() {
        },
        // }}}
        // {{{ deleteDocument()
        deleteDocument: function() {
            this.ajaxCall("deleteDocument", {
                "docName": this.docName
            });
        },
        // }}}
        // {{{ setAttribute()
        setAttribute: function(nodeId, name, value) {
            this.ajaxCall("setAttribute", {
                "id" : nodeId,
                "name" : name,
                "value" : value
            });
        },
        // }}}

        // {{{ ajaxCall()
        ajaxCall: function(operation, data) {
            $.ajax({
                async: true,
                type: 'POST',
                url: this.postUrl + '/' +  operation + '/',
                data: data,
                dataType: 'json',
                error: function(e) {
                    console.log("error");
                    console.log(e);
                },
                success: function(data, status) {
                    console.log(status);
                    console.log(data);
                }
            });
        },
        // }}}
    };

    return Xmldb;
})();

// vim:set ft=javascript sw=4 sts=4 fdm=marker :
