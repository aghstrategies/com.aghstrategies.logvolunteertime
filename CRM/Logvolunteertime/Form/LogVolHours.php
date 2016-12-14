<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Logvolunteertime_Form_LogVolHours extends CRM_Core_Form {

  // public function preProcess() {
  //   $session = CRM_Core_Session::singleton();
  //   $userID = $session->get('userID');
  // }

  /**
   * Build Form
   * @return [type] [description]
   */
  public function buildQuickForm() {
    $this->add(
      'text',
      'first_name',
      ts('First Name')
    );
    $this->addRule(
      'first_name',
      'Please enter your first name',
      'required'
    );
    $this->add(
      'text',
      'last_name',
      ts('Last Name')
    );
    $this->addRule(
      'last_name',
      'Please enter your last name',
      'required'
    );
    $this->add(
      'text',
      'email',
      ts('Email')
    );
    $this->addRule(
      'email',
      'Please enter a valid email',
      'email'
    );
    $this->addRule(
      'email',
      'Please enter your email',
      'required'
    );
    $this->addEntityRef('volunteer_project_select', ts('Volunteer Project'), array(
      'entity' => 'volunteer_project',
      'api' => array(
        'params' => array('is_active'=> 1),
        'description_field' => '',
        'label_field' => 'title',
      ),
      // 'placeholder' => ts(' - Select A Volunteer Project - '),
      'select' => array(
        'minimumInputLength' => 0,
        'minimumResultsForSearch' => -1,
      ),
    ),
    TRUE
    );

    $this->add(
      'hidden',
      'volunteer_need_text',
      ts('Volunteer Need')
    );
    $this->addRule(
      'volunteer_need_text',
      'A Volunteer need is required',
      'required'
    );
    $this->add(
      'text',
      'hours_logged',
      ts('Time Volunteered in minutes')
    );
    $this->addRule(
      'hours_logged',
      'Please enter time volunterred in minutes',
      'required'
    );
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    // captcha
    // $captcha = CRM_Utils_ReCAPTCHA::singleton();
    // $captcha->add($this);
    // $this->assign("isCaptcha", TRUE);

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());

    //Populate Contact info if logged in
    $defaults = array();
    $session = CRM_Core_Session::singleton();
    $userID = $session->get('userID');
    if (!empty($userID)) {
      try {
        $currentUser = civicrm_api3('Contact', 'get', array(
          'sequential' => 1,
          'id' => $userID,
        ));
      }
      catch (CiviCRM_API3_Exception $e) {
        $error = $e->getMessage();
        CRM_Core_Error::debug_log_message(ts('API Error %1', array(
          'domain' => 'com.aghstrategies.logvolunteertime',
          1 => $error,
        )));
      }
      $userInfo = $currentUser['values'][0];
      if (!empty($userInfo['first_name'])) {
        $defaults['first_name'] = $userInfo['first_name'];
      }
      if (!empty($userInfo['last_name'])) {
        $defaults['last_name'] = htmlspecialchars($userInfo['last_name']);
      }
      if (!empty($userInfo['email'])) {
        $defaults['email'] = htmlspecialchars($userInfo['email']);
      }
    }
    //set default Project if vid exsisits
    if (!empty($_REQUEST['vid'])) {
      $vid = $_REQUEST['vid'];
    }
    else {
      $vid = 0;
    }
    CRM_Core_Resources::singleton()->addVars('LogVolHours', array('vid' => $vid));;

    $this->setDefaults($defaults);

    parent::buildQuickForm();

  }

  public function postProcess() {
    $values = $this->exportValues();
    $individualParams = array();
    $individualFields = array(
      'first_name',
      'last_name',
      'email',
    );
    foreach ($individualFields as $field) {
      if (!empty($values[$field])) {
        $individualParams[$field] = $values[$field];
      }
    }
    //Dedupe contact
    $dedupeParams = CRM_Dedupe_Finder::formatParams($individualParams, 'Individual');
    $dedupeParams['check_permission'] = FALSE;
    $dupeIDs = CRM_Dedupe_Finder::dupesByParams($dedupeParams, 'Individual', 'Unsupervised');
    if (is_array($dupeIDs) && !empty($dupeIDs)) {
      $individualParams['id'] = array_shift($dupeIDs);
    }
    $individualParams['contact_type'] = 'Individual';
    try {
      $individual = civicrm_api3('Contact', 'create', $individualParams);
    }
    catch (CiviCRM_API3_Exception $e) {
      $error = $e->getMessage();
      CRM_Core_Error::debug_log_message(ts('API Error %1', array(
        'domain' => 'com.aghstrategies.logvolunteertime',
        1 => $error,
      )));
    }
    // check if volunteer assignment exists
    try {
      $assignment = civicrm_api3('VolunteerAssignment', 'get', array(
        'volunteer_need_id' => $values['volunteer_need_text'],
        'assignee_contact_id' => $individual['id'],
      ));
    }
    catch (CiviCRM_API3_Exception $e) {
      $error = $e->getMessage();
      CRM_Core_Error::debug_log_message(ts('API Error %1', array(
        'domain' => 'com.aghstrategies.logvolunteertime',
        1 => $error,
      )));
    }
    //Create or Update volunteer assingment record
    // TODO: what if there is more than one assignment
    $assignmentParams = array(
      'assignee_contact_id' => $individual['id'],
      'time_completed_minutes' => $values['hours_logged'],
      'status_id' => 2,
      'activity_type_id' => 65,
    );
    if ($values['volunteer_need_text'] == 'none') {
      try {
        $newNeed = civicrm_api3('VolunteerNeed', 'create', array(
          'project_id' => $values['volunteer_project_select'],
          'role_id' => "Logged Thru Vol Hours Form",
          'visibility_id' => "admin",
        ));
      }
      catch (CiviCRM_API3_Exception $e) {
        $error = $e->getMessage();
        CRM_Core_Error::debug_log_message(ts('API Error %1', array(
          'domain' => 'com.aghstrategies.logvolunteertime',
          1 => $error,
        )));
      }
      if (!empty($newNeed['id'])) {
        $assignmentParams['volunteer_need_id'] = $newNeed['id'];
      }
    }
    else {
      $assignmentParams['volunteer_need_id'] = $values['volunteer_need_text'];
    }
    if (!empty($assignment['values'])) {
      $assignmentParams['id'] = $assignment['id'];
    }
    else {
      $assignmentParams['source_contact_id'] = $individual['id'];
    }
    try {
      $result = civicrm_api3('VolunteerAssignment', 'create', $assignmentParams);
    }
    catch (CiviCRM_API3_Exception $e) {
      $error = $e->getMessage();
      CRM_Core_Error::debug_log_message(ts('API Error %1', array(
        'domain' => 'com.aghstrategies.logvolunteertime',
        1 => $error,
      )));
    }

    $url = CRM_Utils_System::url('civicrm/LogVolHours/thanks');
    CRM_Utils_System::redirect($url);

    parent::postProcess();
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
      if ($element->getName() == 'g-recaptcha-response') {
        continue;
      }
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

}
