<?php
/**
 * @author Jaap Jansma (CiviCooP) <jaap.jansma@civicoop.org>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */

class CRM_Casecontribution_Form_AddToCase extends CRM_Core_Form {

  private $contributionId;

  private $currentCaseId;

  /**
   * Build all the data structures needed to build the form.
   *
   * @return void
   */
  public function preProcess() {
    $this->contributionId = CRM_Utils_Request::retrieve('id', 'Positive', CRM_Core_DAO::$_nullObject);
    if (!$this->contributionId) {
      CRM_Core_Error::fatal('required contribution id is missing.');
    }

    $this->currentCaseId = CRM_Utils_Request::retrieve('caseId', 'Positive', CRM_Core_DAO::$_nullObject);
    $this->assign('currentCaseId', $this->currentCaseId);
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    $this->add('text', 'file_on_case_unclosed_case_id', ts('Select Case'), array('class' => 'huge'), TRUE);

    $this->addButtons(array(
        array(
          'type' => 'upload',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  /**
   * Set default values for the form. For edit/view mode
   * the default values are retrieved from the database
   *
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = array();
    $contribution = array();
    $params = array('id' => $this->contributionId);
    $ids = array($this->contributionId);
    CRM_Contribute_BAO_Contribution::retrieve($params, $contribution, $ids);
    //$defaults['file_on_case_target_contact_id'] = $defaults['target_contact'];

    // If this contact has an open case, supply it as a default
    $cid = $contribution['contact_id'];
    if ($cid) {
      $cases = civicrm_api3('Case', 'get', array(
        'contact_id' => $cid,
        'return' => ['case_type_id.title', 'subject', 'contact_id']
      ));

      if ($cases['is_error'] || !$cases['count']) {
        return $defaults;
      }

      // CRM_Core_DAO::commonRetrieveAll('CRM_Case_DAO_Case', 'contact_id', $cid, $cases);
      foreach ($cases['values'] as $id => $details) {
        $defaults['file_on_case_unclosed_case_id'] = $id;
        $value = array(
          'label' => $details['subject'] . ' - ' . $details['case_type_id.title'],
          'extra' => array('contact_id' => $cid),
        );
        $this->updateElementAttr('file_on_case_unclosed_case_id', array('data-value' => json_encode($value)));
        break;
      }
    }

    return $defaults;
  }

  public function postProcess() {
    $params['contribution_id'] = $this->contributionId;
    $params['case_id'] = $this->_submitValues['file_on_case_unclosed_case_id'];
    civicrm_api3('CaseContribution', 'create', $params);
  }

}
