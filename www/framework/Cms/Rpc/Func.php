<?php
/**
 * @file    framework/cms/ui_base.php
 *
 * base class for cms-ui modules
 *
 *
 * copyright (c) 2011-2012 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace depage\Cms\Rpc;

class Func {
    // {{{ variables
    public $contentType = "text/xml";
    public $charset = "UTF-8";
    public $name;
    public $args;
    public $invisibleFuncs = [
        'send_message_to_clients',
        'send_message_to_client',
        'keepAlive',
    ];
    // }}}

    // {{{ constructor
    /**
     * constructor, creates new rpc-func-object
     *
     * @public
     *
     * @param    $name (string)
     * @param    $args (array)
     * @param    $funcObj (object)
     */
    function __construct($name, $args = []) {
        $this->name = $name;
        $this->args = $args;
    }
    // }}}
    // {{{ setFuncObj()
    function set_func_obj(&$funcObj) {
        $this->funcObj = &$funcObj;
    }
    // }}}
    // {{{ __toString()
    /**
     * creates message by func-obj
     *
     * @public
     *
     * @return    (string) $xml_data
     */
    function __toString() {
        $data = "<rpc:func name=\"$this->name\">";
        foreach ($this->args as $key => $val) {
            $data .= "<rpc:param name=\"$key\">";
            $data .= $val;
            $data .= "</rpc:param>";
        }
        $data .= "</rpc:func>";

        return $data;
    }
    // }}}
    // {{{ add_args()
    /**
     * adds new argument to argument list
     *
     * @public
     *
     * @param    $args (array)
     */
    function add_args($args) {
        $this->args = array_merge($this->args, $args);
    }
    // }}}
    // {{{ call()
    /**
     * calls function in func-obj with given arguments
     *
     * @public
     *
     * @return    $value (mixed)
     */
    function call() {
        $val = call_user_func_array([&$this->funcObj, $this->name], [$this->args]);

        if (!in_array($this->name, $this->invisibleFuncs)) {
            $log = new \Depage\Log\Log();
            $log->log("calling $this->name");

            foreach ($this->args as $id => $value) {
                if (is_array($value)) {
                    $val = "";
                    foreach($value as $v) {
                        $val .= $v->ownerDocument->saveXML($v, false);
                    }
                    $log->log("    $id: $val");
                } else {
                    $log->log("    $id: $value");
                }
            }
        }

        return $val;
    }
    // }}}
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
