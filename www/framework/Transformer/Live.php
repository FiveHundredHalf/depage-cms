<?php

namespace Depage\Transformer;

class Live extends Preview
{
    protected $previewType = "live";
    protected $isLive = true;

    // {{{ constructor
    public function __construct($pdo, $projectName, $template, $cacheOptions = array())
    {
        parent::__construct($pdo, $projectName, $template, $cacheOptions);

        // get cache instance for transforms
        $this->transformCache = \depage\cache\cache::factory("transform", $cacheOptions);
    }
    // }}}

    // {{{ initXmlGetter()
    public function initXmlGetter()
    {
        $this->xmlGetter = new \Depage\XmlDb\XmlDbHistory($this->prefix, $this->pdo);
    }
    // }}}
    // {{{ transformPage()
    protected function transformPage($pageId, $pagedataId)
    {
        // @todo add publishing id/domain/baseurl to cachePath
        $cachePath = $this->projectName . "/" . $this->template . "/" . $this->lang . $this->currentPath;

        if ($this->transformCache->exist($cachePath)) {
            $html = $this->transformCache->getFile($cachePath);
        } else {
            $html = parent::transformPage($pageId, $pagedataId);
            $this->transformCache->setFile($cachePath, $html);
        }

        return $html;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
