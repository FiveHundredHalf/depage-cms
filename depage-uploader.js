/**
 * @require framework/shared/jquery-1.4.2.js
 *
 * @file    depage-uploader.js
 *
 * Adds an upload progress bar to a file input field.
 * 
 * Note that the file input base element must have an ID supplied.
 * 
 * Supports: 
 *  - XHR2 upload (supported in IE10 & Safari 5)
 *  - XHR1 upload (https://developer.mozilla.org/En/XMLHttpRequest/Using_XMLHttpRequest#In_Firefox_3.5_and_later) 
 *  - PHP server side upload progress with APC - http://php.net/manual/en/book.apc.php or
 *  - NGINX server with upload-progress-module - http://wiki.nginx.org/NginxHttpUploadProgressModule
 *  - Fallsback to iframe with gif loader
 * 
 * copyright (c) 2006-2012 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Ben Wallis
 */
(function($){
    if(!$.depage){
        $.depage = {};
    };
    
    /**
     * Uploader
     * 
     * @param el - file input
     * @param index
     * @param options
     */
    $.depage.uploader = function(el, index, options){
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;
        
        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;
        
        // require unique element id
        if (base.el.id == ''){
            // throw 'Base element has no ID';
        }
        
        // Add a reverse reference to the DOM object
        base.$el.data("depage.uploader", base);
        
        // cache the form selector
        base.$form = base.$el.closest('form');
        
        // store the APC_UPLOAD_PROGRESS hidden field // TODO insert dynamically from plugin?
        base.$upload_id = null;
        
        // remember the initial form target
        base.target = base.$form.attr('target');
        
        // plugin mode according to browser support - iframe / apc / xhr
        base.mode = 'iframe';
        
        // Cache the XHR object 
        base.xhrHttpRequest = new XMLHttpRequest();
        
        // {{{ init
        /**
         * Init
         * 
         * Get the plugin options.
         * Setup onchange handlers for file inputs.
         * Add the progress bar elements.
         * 
         * @return void
         */
        base.init = function(){
            
            base.options = $.extend({}, $.depage.uploader.defaultOptions, options);
            
            if ( base.support() === 'iframe') {
                // make iframe id unique // TODO enforce
                base.options.iframe = base.el.name + '_' + index + '_' + base.options.iframe;
                base.iframe.build();
            };
            
            base.addProgress();
            
            // add file click handlers
            base.$el.change(function() {
                base.upload(this);
            });
        };
        // }}}
        
        /**
         * Suport
         * 
         * Determines if the browser XHR object supports upload.
         * Sends a server request to the AJAX lander which should return an apc_enabled field.
         * 
         * @returns {String}
         */
        base.support = function(){
            if (typeof(base.xhrHttpRequest.upload) !== 'undefined'){
                base.mode = 'xhr';
            } else {
                $.ajax({
                    url : base.options.server_src + '?X-Progress-ID=EMPTY',
                    dataType: 'json',
                    success : function(data) {
                        if (data.apc_enabled){
                            base.mode = 'apc';
                        } else if (data.state == "starting"){
                            base.mode = 'nginx';
                        }
                    }
                });
            }
            return base.mode;
        },
        
        // {{{ upload()
        /**
         * Upload
         * 
         * Fascade for the upload call, wraps the iframe or xhr upload functions.
         * 
         * @param fileinput
         * @return void
         */
        base.upload = function(fileinput){
            switch(base.mode){
                case 'iframe' :
                case 'nginx' :
                case 'apc' :
                    base.iframe.upload();
                    break;
                case 'xhr' :
                    base.xhr.upload(fileinput);
                    break;
            }
        };
        // }}}
        
        // {{{ IFRAME
        /**
         * Iframe Function Namespacing
         */
        base.iframe = {
            
            /*
             * Hold iframe selector
             */
             $iframe : null,
             
            /*
             * id for the ajax progress call  
             */
            timeout_id : null,	
            
            // {{{ iframe.build()
            /**
             * Build
             * 
             * Creates the iframe.
             * Adds a handler for the (2nd) iframe load (upload complete).
             * Caches the hidden upload id field.
             * 
             * @return null
             */
            build : function() {
                
                var $input = $('input[name=' + base.options.server_upload_key + ']');
                
                if (!$input.length) {
                     $input = $('<input type="hidden" class="input-hidden" />').attr( {
                        name: base.options.server_upload_key,
                        id: base.options.server_upload_key,
                        value: base.options.getUniqueId()
                    });
                     
                     // must be before file input in dom for apc to work
                     base.$form.prepend($input);
                }
                
                // add iframe if not present
                base.iframe.$iframe = $('#' + base.options.iframe);
                
                if (!base.iframe.$iframe.length) {
                    // ie name bug - http://webbugtrack.blogspot.de/2007/10/bug-235-createelement-is-broken-in-ie.html
                    base.iframe.$iframe = $('<iframe name="' + base.options.iframe + '" />').attr('id', base.options.iframe);
                    base.$form.append(base.iframe.$iframe);
                }
                
                var iframeUrl = base.options.src + (base.options.src.indexOf("?") == -1 ? "?" : "&") + "iframe=true";
                base.iframe.$iframe.attr({
                    //name:base.options.iframe,
                    frameborder:0,
                    border:0,
                    src: iframeUrl,
                    scrolling:'no',
                    scrollbar:'no',
                    width: 0,
                    height: 0,
                    style: 'display: none'
                });
                
                base.iframe.$iframe.unbind().load(function(){ // ignore first frame load
                     base.iframe.$iframe.unbind().load(function(){
                        base.iframe.removeDepageInputs();
                        base.iframe.reset();
                        // clear ajax request timeouts
                        clearTimeout(base.iframe.timeout_id);
                        
                        base.complete(base.iframe.$iframe.contents().find('body').html());
                    });
                });
                
                base.$upload_id = $('input[name=' + base.options.server_upload_key + ']');
                
                var oldAction = base.$form.attr("action");
                if (oldAction.indexOf("X-Progress-ID") == -1) {
                    base.$form.attr('action', oldAction + (oldAction.indexOf("?") == -1 ? "?" : "&") + "X-Progress-ID=" + base.$upload_id[0].value);
                }
            },
            // }}}
            
            // {{{ iframe.upload ()
            /**
             * Upload
             * 
             * Submits the form to the iframe so that uploading begins.
             * 
             * @return void
             */
            upload : function() {
                base.iframe.addDepageInputs();
                
                base.$form
                    .attr('target', base.options.iframe)
                    .submit(); // TODO disable client side validation ?
                
                base.start();
                base.iframe.getProgress();
            },
            // }}}
            
            // {{{ iframe.addDepageInputs()
            /**
             * addDepageInputs
             * 
             * Adds the autosave inputs.
             * 
             * @return void
             */
            addDepageInputs : function() {
                base.$form.append('<input type="hidden" name="formAutosave" value="true" />');
                base.$form.append('<input type="hidden" name="ajax" value="true" />');
            },
            // }}}
            
            // {{{ iframe.removeDepageInputs
            /**
             * removeDepageInputs
             * 
             * Removes depage autosave inputs
             * 
             * @return void
             */
            removeDepageInputs : function() {
                $('input[name="formAutosave"]', base.$form).remove();
                $('input[name="ajax"]', base.$form).remove();
            },
            // }}}
            
            // {{{ iframe.reset()
            /**
             * Reset
             * 
             * Reset the frame target.
             * 
             * @return void
             */
            reset : function() {
                base.$form.attr('target', base.target);
            },
            // }}}
            
            
            // {{{ getProgress()
            /**
             * Get Progress
             * 
             * If in "apc" mode starts sending the AJAX upload progress requests
             * to the server AJAX lander specified by base.options.server_src,
             * the lander should return a JSON percent field. If not set, or in iframe
             * only mode the fallback loader is activated. 
             * 
             * @return void
             */
            getProgress : function(){
                if (base.mode=='apc' || base.mode=='nginx'){
                    $.ajax({
                        url : base.options.server_src + '?' + base.$upload_id.serialize() + "&X-Progress-ID=" + base.$upload_id[0].value,
                        dataType: 'json',
                        success : function(data) {
                            if (data.state == 'uploading') {
                                var percent = data.received * 100 / data.size;
                                base.setProgress(percent, data.received, data.size);
                                if (percent < 100){
                                    base.iframe.timeout_id = setTimeout(base.iframe.getProgress, 250);
                                }
                            } else {
                                // 1st call in IE is not returning a percentage ? 
                                base.iframe.timeout_id = setTimeout(base.iframe.getProgress, 250);
                                //base.fallback();
                            }
                        }
                    });
                } else {
                    base.fallback();
                }
            },
            // }}}
            
            // {{{ iframe.cancel()
            /**
             * Cancel
             * 
             * Cancels the iframe load
             * 
             * TODO untested!
             * 
             * @return void
             */
            cancel : function(){
                if (base.iframe.$iframe[0].contentWindow.document.execCommand) {
                    // ie
                    base.iframe.$iframe[0].contentWindow.document.execCommand('Stop');
                } else {
                    // other browsers
                    base.iframe.$iframe[0].contentWindow.stop();
                }
            }
            // }}}
        };
        // }}}
        
        
        // {{{ XHR
        /**
         * XHR Function Namespacing
         */
        base.xhr = {
           
            // {{{ xhr.upload()
            /**
             * Upload
             * 
             * Begin the XHR Upload. Handles progress and load events.
             * 
             * @param fileinput - file to upload
             * 
             * return bool
             */
            upload : function(fileinput){
                if (base.options.max_filesize && base.options.max_filesize > fileinput.filesize) {
                    base.error('max file size exceeded');
                    return false;
                }
                base.xhrHttpRequest.open('POST', base.options.src, true);
                base.xhrHttpRequest.upload.onprogress = function(e) {
                    // TODO x-browser test and fallback 
                    if (e.lengthComputable) {
                        base.setProgress( e.loaded * 100 / e.total, e.loaded, e.total);
                        // base.setProgress( e.position * 100 / e.totalSize );
                    }
                };
                base.xhrHttpRequest.onload = function(e) {  
                    if (base.xhrHttpRequest.status == 200) {  
                        base.complete(e.target.response);
                    } else {  
                        base.error(base.xhrHttpRequest.status);
                        return false;
                    }
                };
                base.start();
                
                var formData = new FormData();
                formData.append('formName', $('input[name="formName"]', base.$form).val());
                formData.append('formAutosave', 'true');
                formData.append('ajax', 'true');
                formData.append(fileinput.name, fileinput.files[0]);
                base.xhrHttpRequest.send(formData);
            }
            // }}}
        };
        
        // {{{ cancel() 
        /**
         * Cancel
         * 
         * @return
         */
        base.cancel = function(){
            switch (base.mode) {
                case 'xhr':
                    base.xhrHttpRequest.abort();
                    break;
                case 'apc':
                case 'nginx':
                case 'iframe' :
                    base.iframe.cancel();
                    base.iframe.removeDepageInputs();
                    break;
            }
            base.clear();
        };
        // }}}
        
        // {{{ error()
        /** 
         * Error
         * 
         * @return
         */
        base.error = function(message){
            switch (base.mode) {
                case 'apc':
                case 'nginx':
                case 'iframe' :
                    base.iframe.removeDepageInputs();
                    break;
            }
            base.clear();
            base.$el.trigger('error', message);
        };
        // }}}
        
        // {{{ start()
        /**
         * Start
         * 
         * Setup the upload
         * 
         * @return void
         */
        base.start = function(){
            $(window).bind('unload.uploader', function() {
                if (confirm(base.options.unload_message)) {
                    base.cancel();
                }
            });
            base.controls.progress.show();
            base.$el.trigger('start');
        };
        // }}}
        
        // {{{ complete() 
        /**
         * Complete
         * 
         * Upload complete
         * 
         * @return void
         */
        base.complete = function(response){
            if (base.mode == "iframe") {
                base.iframe.removeDepageInputs();
            }

            base.$el.trigger(base.options.complete_event, [response]);
            base.setProgress(100);
            base.clear();
        };
        // }}}
        
        // {{{ clear
        /**
         * Clear
         * 
         * Called after an upload ends for whatever reason.
         * 
         * - Clear the file input
         * - Set progress to 0
         * - Unbind the cancel dialogue
         * 
         * @return void
         */
        base.clear = function () {
            // replace input with copy to clear input on Internet Explorer
            var $originalInput = base.$el;
            base.$el = $originalInput.clone(true);
            base.el = base.$el[0];
            $originalInput.replaceWith(base.$el);
            base.$el.val('');

            base.$el.change(function() {
                base.upload(this);
            });

            base.setProgress(0);
            base.controls.progress.hide();
            $(window).unbind('unload.uploader');
        };
        // }}}
        
        // {{{ progress()
        /**
         * Set Progress
         * 
         * Sets the progress percent width.
         * 
         * @param percent
         * 
         * @return void
         */
        base.setProgress = function(percent, loaded, total){
            var text = "";
            
            base.controls.percent.width(percent + '%');
            
            if (loaded != undefined && total != undefined) {
                text += Math.floor(percent * 10) / 10;
                text += " % uploaded (";
                text += base.bytesToSize(loaded, 1);
                text += "/";
                text += base.bytesToSize(total, 1);
                text += ")";
            }
            base.controls.textinfo.text(text);
        };
        // }}}
        
        // {{{ baseToSize()
        /**
         * Set Progress
         * 
         * Sets the progress percent width.
         * 
         * @param percent
         * 
         * @return void
         */
        base.bytesToSize = function(bytes, precision) {  
            var kilobyte = 1024;
            var megabyte = kilobyte * 1024;
            var gigabyte = megabyte * 1024;
            var terabyte = gigabyte * 1024;
            
            if ((bytes >= 0) && (bytes < kilobyte)) {
                return bytes + ' B';
            } else if ((bytes >= kilobyte) && (bytes < megabyte)) {
                return (bytes / kilobyte).toFixed(precision) + ' KB';
            } else if ((bytes >= megabyte) && (bytes < gigabyte)) {
                return (bytes / megabyte).toFixed(precision) + ' MB';
            } else if ((bytes >= gigabyte) && (bytes < terabyte)) {
                return (bytes / gigabyte).toFixed(precision) + ' GB';
            } else if (bytes >= terabyte) {
                return (bytes / terabyte).toFixed(precision) + ' TB';
            } else {
                return bytes + ' B';
            }
        };
        // }}}
        
        // {{{ fallback()
        /**
         * Fallback 
         * 
         * No upload progress can be calculated.
         * 
         * Show a generic image gif loader.
         * 
         * @return void
         */
        
        base.fallback = function(){
            var $img = $('<img />')
                .attr({
                    'id' : base.options.iframe + '-loader',
                    'src' : base.options.loader_img,
                    'alt' : "uploading"
                }).error(function(){
                    $img.replaceWith("uploading..."); // replace with text if not found
                });
            
            base.controls.percent.html($img);
        };
        // }}}
        
        // {{{ addProgress()
        /**
         * addProgress
         * 
         * Add the progress elements
         * 
         * @return
         */ 
        base.addProgress = function() {
            base.controls = {
                progress : $('<span />').attr({
                    'id': base.el.id + '-progress',
                    //'style' : 'display:none;'
                    'class' : base.options.classes.progress
                }),
                percent : $('<span />').attr({
                    'id': base.el.id + '-percent',
                    'class' : base.options.classes.percent
                }).width('0%'),
                textinfo : $('<span />').attr({
                    'id': base.el.id + '-textinfo',
                    'class' : base.options.classes.textinfo
                })
            };
            
            base.$el.after(base.controls.textinfo);
            
            base.controls.progress.append(base.controls.percent);
            base.$el.after(base.controls.progress);
        };
        // }}}
        
        base.init();
    };
    
    /**
     * Options
     * 
     * @param classes : progress and percent element classes.
     * @param iframe : iframe id / name.
     * @param src : iframe source - form posted to.
     * @param server_src: the AJAX lander for the APC upload progress calculation
     * @param server_upload_key: the hidden file element name matches APC upload key.
     * @param loader_img: the fallback image - animated gif or similar.
     * @param getUniqueId: function for getting the unique APC upload ID.
     * @param unload_message
     * @param complete event
     * @param max_filesize - max file size test used in xhr upload
     */
    $.depage.uploader.defaultOptions = {
        classes : {
           progress: 'progress',
           percent:  'percent',
           textinfo: 'textinfo'
        },
        src: document.location.href,
        iframe: 'upload_target',
        server_src : 'framework/media/uploadprogress.php',
        server_upload_key : 'APC_UPLOAD_PROGRESS',
        loader_img : 'lib/global/images/loader.gif',
        getUniqueId: function() { return new Date().getTime(); },
        unload_message: 'Navigating away from the page will cancel the file upload. Do you want to continue?',
        complete_event: 'complete',
        max_filesize: false
    };
    
    $.fn.depageUploader = function(options){
        return this.each(function(index){
            (new $.depage.uploader(this, index, options));
        });
    };
    
})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
