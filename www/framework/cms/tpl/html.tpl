<!DOCTYPE html>
<html>
    <head>
        <title><?php 
            html::t($this->title); 
            if ($this->subtitle != null) {
                html::t(" // " . $this->subtitle);
            }
        ?></title>

        <base href="<?php html::base(); ?>">

        <?php $this->include_js("interface", array(
            "framework/cms/js/interface.js",
            "framework/shared/jquery.cookie.js",
            "framework/shared/jquery.hotkeys.js",
        )); ?>
        <?php $this->include_js("jstree", array(
            "framework/cms/js/jquery.jstree.js",
            "framework/cms/js/jquery.jstree.plugins.js",
            "framework/shared/jquery.json-2.2.js",
            "framework/shared/jquery.gracefulWebSocket.js",
            "framework/cms/js/jstree.js",
        )); ?>
        <?php $this->include_css("interface", array(
            "framework/htmlform/lib/css/depage-forms.css",
            "framework/cms/css/interface.css",
        )); ?>
    </head>
    <body>
        <?php html::e($this->content); ?>
    </body>
</html>
<?php // vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et :
