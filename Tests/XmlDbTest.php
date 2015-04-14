<?php

class XmlDbTest extends Generic_Tests_DatabaseTestCase
{
    protected $xmldb;

    // {{{ setUp
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        parent::setUp();

        // get cache instance
        $cache = Depage\Cache\Cache::factory("xmldb", array("disposition" => "uncached"));

        // get xmldb instance
        $this->xmldb = new Depage\XmlDb\XmlDb($this->pdo->prefix . "_proj_test", $this->pdo, $cache, array(
            "root",
            "child",
        ));
    }
    // }}}

    // {{{ testGetDocuments
    public function testGetDocuments() {
        // get list for one document
        $docs = $this->xmldb->getDocuments("pages");

        $this->assertEquals(array(
            'pages' => (object) array(
                'name' => 'pages',
                'id' => '1',
                'rootid' => '1',
                'permissions' => 'a:2:{i:0;a:2:{s:7:"pg:page";a:1:{i:0;s:3:"all";}s:9:"pg:folder";a:1:{i:0;s:3:"all";}}i:1;a:0:{}}',
            ),
        ), $docs);

        // get list of all documents
        $docs = $this->xmldb->getDocuments();

        $this->assertEquals(array(
            'pages' => (object) array(
                'name' => 'pages',
                'id' => '1',
                'rootid' => '1',
                'permissions' => 'a:2:{i:0;a:2:{s:7:"pg:page";a:1:{i:0;s:3:"all";}s:9:"pg:folder";a:1:{i:0;s:3:"all";}}i:1;a:0:{}}',
            ),
            'tpl_newnodes' => (object) array(
                'name' => 'tpl_newnodes',
                'id' => '3',
                'rootid' => '5',
                'permissions' => '',
            ),
            'tpl_templates' => (object) array(
                'name' => 'tpl_templates',
                'id' => '2',
                'rootid' => '3',
                'permissions' => '',
            ),
        ), $docs);
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
