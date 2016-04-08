<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Logvolunteertime_Form_LogVolHours extends CRM_Core_Form {
  public function buildQuickForm() {

    // add form elements
    $this->add(
      // field type
      'select',
      // field name
      'favorite_color',
      // field label
      'Favorite Color',
      // list of options
      $this->getColorOptions(),
        // is required
      TRUE
    );
    //select2 for contacts
    $this->addEntityRef('field_1', ts('Select Contact'));
    $this->addEntityRef('field_5', ts('Volunteer Projects'), array(
      'entity' => 'option_value',
      'api' => array(
        'params' => array('option_group_id' => 'volunteer_projects'),
      ),
      'select' => array('minimumInputLength' => 0),
    ));

    //captcha
    // $captcha = CRM_Utils_ReCAPTCHA::singleton();
    // $captcha->add($this);
    // $this->assign("isCaptcha", TRUE);
    //
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();
    $options = $this->getColorOptions();
    CRM_Core_Session::setStatus(ts('You picked color "%1"', array(
      1 => $options[$values['favorite_color']]
    )));
    parent::postProcess();
  }

  public function getColorOptions() {
    $options = array(
      '' => ts('- select -'),
      '#f00' => ts('Red'),
      '#0f0' => ts('Green'),
      '#00f' => ts('Blue'),
      '#f0f' => ts('Purple'),
    );
    foreach (array('1','2','3','4','5','6','7','8','9','a','b','c','d','e') as $f) {
      $options["#{$f}{$f}{$f}"] = ts('Grey (%1)', array(1 => $f));
    }
    return $options;
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

}
