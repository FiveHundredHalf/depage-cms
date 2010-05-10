<?php
/**
 * @file    preview.php
 *
 * Preview script
 *
 * This file handles all calls for previews and transform
 * the xml data into the wanted format.
 *
 *
 * copyright (c) 2002-2010 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 *
 * @todo    change preview behaviour in the following manner:
 *            don't save xml data after every edit. instead work
 *            with a temporary copy of the data until another page
 *            is selected or the client logs out.
 *            deny editing of the same page at the same time, show
 *            only the current copy of it.
 *
 */

// {{{ define and require
define('IS_IN_CONTOOL', true);

require_once('lib/lib_global.php');
require_once('lib_html.php');
require_once('lib_auth.php');
require_once('lib_xmldb.php');
require_once('lib_tpl.php');
require_once('lib_pocket_server.php');
require_once('lib_files.php');
// }}}

// {{{ getParameterByUrl()
/**
 * gets given parameter by calling url
 *
 * @param    $url (string) url of call
 * @param    $project (string) name of project
 * @param    $type (string) name of template set
 * @param    $access (string) 'browse' or 'preview'
 */
function getParameterByUrl($url, $project = "", $type = "", $access = "") {
    global $conf;
    
    $param = array();
    
    if ($project == "") {
        $url = parse_url($url);
        $path = explode('/', substr($url['path'], strpos($url['path'], 'projects')));
        $param['project'] = $path[1];
        $param['type'] = $path[3];
        if ($path[4] == 'cached') {
            $param['cached'] = true;
        } else {
            $param['cached'] = false;
        }
        $pathinfo = pathinfo($url['path']);
        $param['file_name'] = $pathinfo['basename'];
        $param['file_extension'] = $pathinfo['extension'];
        foreach ($conf->output_file_types AS $file_type_name => $file_type) {
            if ($file_type['extension'] == $pathinfo['extension']) {
                $param['file_type'] = $file_type_name;
            }
        }
        $param['id_file_path'] = '/' . implode('/', array_slice($path, 6));
        $param['file_path'] = '/' . implode('/', array_slice($path, 5));
        if ($param['id_file_path'] == "/atom.xml") {
            $param['access'] = 'atom';
        } else {
            $param['access'] = 'preview';
        }
        $param['path'] = '/' . implode('/', array_slice($path, 5, -1));
        $param['lang'] = $path[5];
    } else {
        $param['project'] = $project;
        $param['type'] = $type;
        $param['sid'] = '';
        $param['wid'] = '';
        $param['cached'] = true;
        
        $url = parse_url($url);
        if (strpos($url['path'], 'dyn') === false) { 
            $param['access'] = 'index';
        } else {
            $path = explode('/', substr($url['path'], strpos($url['path'], 'dyn')));
            $pathinfo = pathinfo($url['path']);
            $param['lang'] = $path[1];
            if ($param['lang'] == "css") {
                $param['access'] = "css";
                $param['file_name'] = substr($pathinfo['basename'], 0, strlen($pathinfo['basename']) - 4);
                $param['file_extension'] = $pathinfo['extension'];
                $param['file_path'] = '/' . implode('/', array_slice($path, 0));
                $param['path'] = '/' . implode('/', array_slice($path, 0, -1));
            } else {
                $param['access'] = $access;
                $param['file_name'] = $pathinfo['basename'];
                $param['file_extension'] = $pathinfo['extension'];
                foreach ($conf->output_file_types AS $file_type_name => $file_type) {
                    if ($file_type['extension'] == $pathinfo['extension']) {
                        $param['file_type'] = $file_type_name;
                    }
                }
                $param['file_path'] = '/' . implode('/', array_slice($path, 0));
                $param['path'] = '/' . implode('/', array_slice($path, 0, -1));
            }
        }
        
    }
    
    return $param;
}
// }}}

/**
 * ----------------------------------------------
 */ 
$user = new ttUser();
$user->auth_http();

headerNoCache();
set_time_limit(60);

$param = getParameterByUrl($_SERVER['REQUEST_URI'], $_GET['project'], $_GET['type'], $_GET['access']);

if ($param['project'] != "") {
    $project_name = $param['project'];
    $project->user->project = $project_name;

    $xml_proc = tpl_engine::factory('xslt', $param);
    // {{{ browse or preview
    if ($param['id_file_path'] == "/") {
        $param['access'] = "redirect";
    }
    if ($param['access'] == 'browse' || $param['access'] == 'preview') { 
        $id = $xml_proc->get_id_by_path($param['id_file_path'], $project_name);
        if ($project_name && $id != null) {
            if ($param['lang'] == "") {
                $languages = $project->get_languages($project_name);
                $languages = array_keys($languages);
                $data['lang'] = $languages[0];
            } else {
                $data['lang'] = $param['lang'];
            }
            if (!$param['cached']) {
                $transformed = $xml_proc->transform($project_name, $param['type'], $id, $param['lang'], $param['cached']);
            } else if (($transformed = $xml_proc->get_from_transform_cache($project_name, $param['type'], $id, $param['lang'], $param['access'])) === false) {
                $transformed = $xml_proc->transform($project_name, $param['type'], $id, $param['lang'], $param['cached']);
                if ($transformed != false) {
                    $xml_proc->add_to_transform_cache($project_name, $param['type'], $id, $param['lang'], $param['access'], $transformed, $xml_proc->ids_used);
                }
            }

            if ($param['disableCallback'] != true) {
                $data['id'] = $id;
            } else {
                $data['id'] = 'false';
            }
            $data['error'] = $xml_proc->error;
            
            if ($param['access'] == 'preview') {
                $func = new ttRpcFunc('preview_loaded', $data);
                if (strpos($_SERVER['HTTP_REFERER'], 'http://' . $_SERVER['HTTP_HOST'] . $conf->path_projects . '/') !== false) {
                    $pocket_client = new PocketClient('127.0.0.1', $conf->pocket_port);
                    if ($pocket_client->connect()) {
                        $pocket_client->send_to_client($func, $param['sid'], $param['wid']);
                    }
                }
            }
            
            if ($transformed) {
                if (!$conf->output_file_types[$param['file_type']]['dynamic']) {
                    headerType($transformed['content_type'], $transformed['content_encoding']);
                    echo($transformed['value']);
                } else {
                    headerType($transformed['content_type'], $transformed['content_encoding']);
                    if (strpos($param['path'], '/', 2)) {
                        $cache_path = substr($param['path'], strpos($param['path'], '/', 2));
                    } else {
                        $cache_path = "";
                    }
                    $file_path = $project->get_project_path($project_name) . "/cache_{$param['type']}_{$param['lang']}{$cache_path}/{$param['file_name']}";
                    $file_access = fs::factory('local');
                    $file_access->f_write_string($file_path, $transformed['value']);
                    $file_access->ch_dir($project->get_project_path($project_name) . "/cache_{$param['type']}_{$param['lang']}{$cache_path}");
                    // replace virtual if not available
                    if (is_callable("virtual")) {
                        virtual("{$conf->path_projects}/" . str_replace(' ', '_', strtolower($project_name)) . "/cache_{$param['type']}_{$param['lang']}{$cache_path}/{$param['file_name']}");
                    } else {
                       $host = $_SERVER['HTTP_HOST'];
                       if ($host == "") {
                           $host = $_SERVER['SERVER_NAME'];
                       }
                       if ($host == "") {
                           $host = $_SERVER['SERVER_ADDR'];
                       }
                       if ($_SERVER['QUERY_STRING'] != "") {
                           $get_query = "?{$_SERVER['QUERY_STRING']}";
                       } else {
                           $get_query = "";
                       }

                       //@todo add options to give POST and COOKIES to called script
                       readfile("http://$host{$conf->path_projects}/" . str_replace(' ', '_', strtolower($project_name)) . "/cache_{$param['type']}_{$param['lang']}{$cache_path}/{$param['file_name']}{$get_query}");
                    }
                    //$file_access->rm($file_path);
                }
            } else {
                $settings = $conf->getScheme($conf->interface_scheme);
                $lang = $conf->getTexts($conf->interface_language, 'inhtml');
                
                $html = new html();

                $html->head();
                echo("<body bgcolor=\"" . $settings['color_background'] . "\">");
                    $html->message($lang['inhtml_preview_error'], $data['error']);
                echo("</body>");
                $html->end();
            }
        } else {
            die_error("not a valid id");
        }
        // }}}
    // {{{ css
    } else if ($param['access'] == 'css') { 
        $xml_proc->actual_path = '/';
        $id = 0;
        if (!$param['cached']) {
            $transformed = $xml_proc->generate_page_css($project_name, $param['type'], $param['file_name'], $param['cached']);
        } else if (($transformed = $xml_proc->get_from_transform_cache($project_name, $param['type'], 0, $param['file_name'], $param['access'])) === false) {
            $transformed = $xml_proc->generate_page_css($project_name, $param['type'], $param['file_name'], $param['cached']);
            $xml_proc->add_to_transform_cache($project_name, $param['type'], 0, $param['file_name'], $param['access'], $transformed, array());
        }
        headerType($transformed['content_type'], $transformed['content_encoding']);
        echo("<pre>");
        echo($transformed['value']);
        echo("</pre>");
    // }}}
    // {{{ atom
    } else if ($param['access'] == 'atom') { 
        $xml_proc->actual_path = '/';
        $id = 0;
        $baseurl = 'http://' . $_SERVER['HTTP_HOST'] . $conf->path_projects . '/' . $project_name . '/';
        if (!$param['cached']) {
            $transformed = $xml_proc->generate_page_atom($project_name, $param['type'], $param['lang'], $baseurl, $param['cached']);
        } else if (($transformed = $xml_proc->get_from_transform_cache($project_name, $param['type'], 0, $param['file_name'], $param['access'])) === false) {
            $transformed = $xml_proc->generate_page_atom($project_name, $param['type'], $param['file_name'], $baseurl, $param['cached']);
            $xml_proc->add_to_transform_cache($project_name, $param['type'], 0, $param['file_name'], $param['access'], $transformed, array());
        }
        headerType($transformed['content_type'], $transformed['content_encoding']);
        echo($transformed['value']);
    // }}}
    // {{{ index
    } else if ($param['access'] == 'index') { 
        $xml_proc->actual_path = '/';
        $id = 0;
        if (!$param['cached']) {
            $transformed = $xml_proc->generate_page_redirect($project_name, $param['type'], $param['lang'], $param['cached']);
        } else if (($transformed = $xml_proc->get_from_transform_cache($project_name, $param['type'], $id, $param['lang'], $param['access'])) === false) {
            $transformed = $xml_proc->generate_page_redirect($project_name, $param['type'], $param['lang'], $param['cached']);
            $xml_proc->add_to_transform_cache($project_name, $param['type'], $id, $param['lang'], $param['access'], $transformed, $xml_proc->ids_used);
        }
        headerType($transformed['content_type'], $transformed['content_encoding']);
        echo($transformed['value']);
    // }}}
    // {{{ redirect
    } else if ($param['access'] == 'redirect') { 
        
        //headerType($transformed['content_type'], $transformed['content_encoding']);
        //var_dump($param);
        $languages = $project->get_languages($param['project']);
        $languages = array_keys($languages);
        $page_struct = $project->get_page_struct($param['project']);

        $node = $page_struct->document_element();
        while ($node != null && $node->tagname != "page") {
            $node = $node->first_child();
        }
        
        $url = $conf->path_base . "projects/" . $param['project'] . "/preview/" . $param['type'] . "/cached/" . $languages[0] . $node->get_attribute("url");

        header("Location: $url");
        echo("redirect to <a href=\"$url\">$url</a>");
    }
    // }}}
} else {
    die_error('you are not logged in' );
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
