<?php

/**
 * @file    framework/Cms/Ui/Newsletter.php
 *
 * depage cms ui module
 *
 *
 * copyright (c) 2016 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms\Forms;

class NewsletterPublish extends \Depage\HtmlForm\HtmlForm
{
    // {{{ __construct()
    /**
     * @brief   HtmlForm class constructor
     *
     * @param  string $name       form name
     * @param  array  $parameters form parameters, HTML attributes
     * @param  object $form       parent form object reference (not used in this case)
     * @return void
     **/
    public function __construct($name, $parameters = array(), $form = null)
    {
        $parameters['label'] = _("Send Now");
        $parameters['class'] = "newsletter publish";

        $this->newsletter = $parameters['newsletter'];

        parent::__construct($name, $parameters, $this);
    }
    // }}}
    // {{{ addChildElements()
    /**
     * @brief addChildElements
     *
     * @return void
     **/
    public function addChildElements()
    {
        $this->addSingle("to", array(
            'label' => _("Empfänger"),
            'list' => [
                'subscriber' => _("Subscribers"),
                'custom' => _("Custom Recipients"),
            ],
        ));
        $this->addTextarea("emails", [
            'label' => _("Emails"),
        ]);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
