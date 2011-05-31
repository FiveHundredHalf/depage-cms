<?php
/**
 * Option list examples
 *
 * Multiple, single and text input elements have option lists. Here are some
 * more detailed examples.
 **/

/**
 * Load the library...
 **/
require_once('../../htmlform.php');

/**
 * Create the example form 'listsExampleForm'
 **/
$form = new depage\htmlform\htmlform('listsExampleForm');

/**
 * Multiple input element with checkbox skin, set required
 *
 * Contrary to HTML5 specs, a required multiple-checkbox only needs one item
 * checked to be valid.
 **/
$form->addMultiple('multipleCheckbox', array(
    'required' => true,
    'label' => 'Multiple-checkbox',
    'list' => array(
        'option one'    => 'label one',
        'option two'    => 'label two',
        'option three'  => 'label three'
    ),
));

/**
 * Non-associative lists arrays
 *
 * The list parameter can also be a normal array (it doesn't have to be
 * associative). In that case it returns the array key ("0", "1", "2"). This
 * works for every element with an option list ie. text, single and multiple.
 **/
$form->addMultiple('multipleSelect', array(
    'label' => 'Multiple-select',
    'skin' => 'select',
    'list' => array(
        'label one',
        'label two',
        'label three'
    )
));

/**
 * Optgroups
 *
 * 'select'-skin elements support optgroups. To use them simply add another
 * array dimension. This works for single and multiple elements with the
 * 'select' skin.
 **/
$form->addSingle('optgroupSingle', array(
    'label' => 'Optgroup-single',
    'skin' => 'select',
    'list' => array(
        'group one' => array(
            'option one'    => 'label one',
            'option two'    => 'label two',
            'option three'  => 'label three'
        ),
        'group two' => array(
            'option four'   => 'label four',
            'option five'   => 'label five'
        )
    )
));

/**
 * Datalists
 *
 * Modern browsers support datalists for text fields as input examples. Usage
 * is similar to single or multiple element lists.
 * Associative arrays (option => label) also work.
 **/
$form->addText('datalistText', array(
    'label' => 'Text with datalist',
    'list' => array(
        'option one',
        'option two',
        'option three'
    ),
));

$form->process();

if ($form->validate()) {
    echo('<pre>');
    var_dump($form->getValues());
    echo('</pre>');
} else {
    echo ('<link type="text/css" rel="stylesheet" href="test.css">');
    echo ($form);
}
