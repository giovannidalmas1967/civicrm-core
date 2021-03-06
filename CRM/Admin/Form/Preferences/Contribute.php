<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * This class generates form components for the display preferences.
 */
class CRM_Admin_Form_Preferences_Contribute extends CRM_Admin_Form_Preferences {
  protected $_settings = array(
    'cvv_backoffice_required' => CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME,
    'update_contribution_on_membership_type_change' => CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME,
    'acl_financial_type' => CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME,
    'always_post_to_accounts_receivable' => CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME,
    'deferred_revenue_enabled' => CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME,
    'default_invoice_page' => CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME,
    'invoicing' => CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME,
  );

  /**
   * Process the form submission.
   */
  public function preProcess() {
    $config = CRM_Core_Config::singleton();
    CRM_Utils_System::setTitle(ts('CiviContribute Component Settings'));
    $this->_varNames = array(
      CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME => array(
        'invoice_prefix' => array(
          'html_type' => 'text',
          'title' => ts('Invoice Prefix'),
          'weight' => 1,
          'description' => ts('Enter prefix to be display on PDF for invoice'),
        ),
        'credit_notes_prefix' => array(
          'html_type' => 'text',
          'title' => ts('Credit Notes Prefix'),
          'weight' => 2,
          'description' => ts('Enter prefix to be display on PDF for credit notes.'),
        ),
        'due_date' => array(
          'html_type' => 'text',
          'title' => ts('Due Date'),
          'weight' => 3,
        ),
        'due_date_period' => array(
          'html_type' => 'select',
          'title' => ts('For transmission'),
          'weight' => 4,
          'description' => ts('Select the interval for due date.'),
          'option_values' => array(
            'select' => ts('- select -'),
            'days' => ts('Days'),
            'months' => ts('Months'),
            'years' => ts('Years'),
          ),
        ),
        'notes' => array(
          'html_type' => 'wysiwyg',
          'title' => ts('Notes or Standard Terms'),
          'weight' => 5,
          'description' => ts('Enter note or message to be displayed on PDF invoice or credit notes '),
          'attributes' => array('rows' => 2, 'cols' => 40),
        ),
        'is_email_pdf' => array(
          'html_type' => 'checkbox',
          'title' => ts('Automatically email invoice when user purchases online'),
          'weight' => 6,
        ),
        'tax_term' => array(
          'html_type' => 'text',
          'title' => ts('Tax Term'),
          'weight' => 7,
        ),
        'tax_display_settings' => array(
          'html_type' => 'select',
          'title' => ts('Tax Display Settings'),
          'weight' => 8,
          'option_values' => array(
            'Do_not_show' => ts('Do not show breakdown, only show total -i.e ' .
              $config->defaultCurrencySymbol . '120.00'),
            'Inclusive' => ts('Show [tax term] inclusive price - i.e. ' .
              $config->defaultCurrencySymbol .
              '120.00 (includes [tax term] of ' .
              $config->defaultCurrencySymbol . '20.00)'),
            'Exclusive' => ts('Show [tax term] exclusive price - i.e. ' .
              $config->defaultCurrencySymbol . '100.00 + ' .
              $config->defaultCurrencySymbol . '20.00 [tax term]'),
          ),
        ),
      ),
    );
    parent::preProcess();
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $htmlFields = array();
    foreach ($this->_settings as $setting => $group) {
      // @todo - remove this whole loop! The parent form does this - it's just because of the werid handling
      // of $htmlFields for this form that needs to be unwound that we have it atm.
      // The 'basicform' has been contaminated with processing $htlFields
      // to cater to this form - probably due to the way invoicing settings were handled as
      // an array not a bunch of keys.
      $settingMetaData = civicrm_api3('setting', 'getfields', array('name' => $setting));
      $props = $settingMetaData['values'][$setting];
      if (isset($props['quick_form_type'])) {
        $add = 'add' . $props['quick_form_type'];
        if ($add == 'addElement') {
          if (in_array($props['html_type'], array('checkbox', 'textarea'))) {
          }
          else {
            if ($props['html_type'] == 'select') {
              $functionName = CRM_Utils_Array::value('name', CRM_Utils_Array::value('pseudoconstant', $props));
              if ($functionName) {
                $props['option_values'] = array('' => ts('- select -')) + CRM_Contribute_PseudoConstant::$functionName();
              }
            }
          }
        }
      }
      $htmlFields[$setting] = ts($props['description']);
    }
    $this->assign('htmlFields', $htmlFields);
    parent::buildQuickForm();
  }

  /**
   * Set default values for the form.
   *
   * default values are retrieved from the database
   */
  public function setDefaultValues() {
    $defaults = Civi::settings()->get('contribution_invoice_settings');
    //CRM-16691: Changes made related to settings of 'CVV'.
    foreach (array('cvv_backoffice_required') as $setting) {
      $defaults[$setting] = civicrm_api3('setting', 'getvalue',
        array(
          'name' => $setting,
          'group' => CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME,
        )
      );
    }
    return $defaults;
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);
    unset($params['qfKey']);
    unset($params['entryURL']);
    Civi::settings()->set('contribution_invoice_settings', $params);

    // to set default value for 'Invoices / Credit Notes' checkbox on display preferences
    $values = CRM_Core_BAO_Setting::getItem("CiviCRM Preferences");
    $optionValues = CRM_Core_OptionGroup::values('user_dashboard_options', FALSE, FALSE, FALSE, NULL, 'name');
    $setKey = array_search('Invoices / Credit Notes', $optionValues);

    if (isset($params['invoicing'])) {
      $value = array($setKey => $optionValues[$setKey]);
      $setInvoice = CRM_Core_DAO::VALUE_SEPARATOR .
        implode(CRM_Core_DAO::VALUE_SEPARATOR, array_keys($value)) .
        CRM_Core_DAO::VALUE_SEPARATOR;
      Civi::settings()->set('user_dashboard_options', $values['user_dashboard_options'] . $setInvoice);
    }
    else {
      $setting = explode(CRM_Core_DAO::VALUE_SEPARATOR, substr($values['user_dashboard_options'], 1, -1));
      $invoiceKey = array_search($setKey, $setting);
      if ($invoiceKey !== FALSE) {
        unset($setting[$invoiceKey]);
      }
      $settingName = CRM_Core_DAO::VALUE_SEPARATOR .
        implode(CRM_Core_DAO::VALUE_SEPARATOR, array_values($setting)) .
        CRM_Core_DAO::VALUE_SEPARATOR;
      Civi::settings()->set('user_dashboard_options', $settingName);
    }
    //CRM-16691: Changes made related to settings of 'CVV'.
    $settings = array_intersect_key($params, array('cvv_backoffice_required' => 1));
    $result = civicrm_api3('setting', 'create', $settings);
    CRM_Core_Session::setStatus(ts('Your changes have been saved.'), ts('Changes Saved'), "success");
  }

}
