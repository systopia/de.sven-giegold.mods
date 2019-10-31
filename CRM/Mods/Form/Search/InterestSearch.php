<?php
use CRM_Mods_ExtensionUtil as E;

/**
 * A custom contact search
 */
class CRM_Mods_Form_Search_InterestSearch extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  const CUSTOM_GROUP_NAME_ZUSATZINFORMATIONEN = 'zusatzinformationen';

  function __construct(&$formValues) {
    parent::__construct($formValues);
  }

  /**
   * Prepare a set of search fields
   *
   * @param CRM_Core_Form $form modifiable
   * @return void
   */
  function buildForm(&$form) {
    CRM_Utils_System::setTitle('Suche nach Interessen');

    $include_exclude = array(
      'include' => E::ts('Include'),
      'exclude' => E::ts('Exclude'),
    );

    $criteria_fields = array(
      'medien_typen',
      'medien_themen',
      'medien_sonderverteiler',
      'themeninteressen',
      'personen_organisationen',
      'expertinnen',
      'metaverteiler',
    );

    $selection_fields = array();
    foreach ($criteria_fields as $field_name) {
      $custom_field = CRM_Mods_CustomData::getCustomField(
        self::CUSTOM_GROUP_NAME_ZUSATZINFORMATIONEN,
        $field_name
      );

      foreach ($include_exclude as $category => $category_label) {
        $field_name = $category . '_custom_' . $custom_field['id'];
        $form->addSelect(
          $field_name,
          array(
            'field' => 'custom_' . $custom_field['id'],
            // Get the label for the custom field, since this is not working
            // correctly in CRM_Core_Form::addSelect().
            'label' => CRM_Core_DAO::getFieldValue(
              'CRM_Core_DAO_CustomField',
              $custom_field['id'],
              'label'
            ) . ' (' . $category_label . ')',
            'multiple' => TRUE,
          )
        );
        $selection_fields[] = $field_name;
      }
    }

    $filter_fields = array();

    $language_field = CRM_Mods_CustomData::getCustomField(
      self::CUSTOM_GROUP_NAME_ZUSATZINFORMATIONEN,
      'sprache'
    );
    foreach ($include_exclude as $category => $category_label) {
      $filter_fields[] = $form->addSelect(
        $category . '_sprache',
        array(
          'field' => 'custom_' . $language_field['id'],
          // Get the label for the custom field, since this is not working
          // correctly in CRM_Core_Form::addSelect().
          'label' => CRM_Core_DAO::getFieldValue(
              'CRM_Core_DAO_CustomField',
              $language_field['id'],
              'label'
            ) . ' (' . $category_label . ')',
          'multiple' => TRUE,
        )
      )->getName();
    }

    // Optionally define default search values
//    $form->setDefaults(array(
//      'household_name' => '',
//      'state_province_id' => NULL,
//    ));

    /**
     * This array tells the template what elements are part of the search
     * criteria.
     */
    $form->assign('selection', $selection_fields);
    $form->assign('filters', $filter_fields);
  }

  /**
   * Get a list of summary data points
   *
   * @return mixed; NULL or array with keys:
   *  - summary: string
   *  - total: numeric
   */
  function summary() {
    return NULL;
    // return array(
    //   'summary' => 'This is a summary',
    //   'total' => 50.0,
    // );
  }

  /**
   * Get a list of displayable columns
   *
   * @return array, keys are printable column headers and values are SQL column names
   */
  function &columns() {
    // return by reference
    $columns = array(
      E::ts('Contact Id') => 'contact_id',
      E::ts('Contact Type') => 'contact_type',
      E::ts('Name') => 'sort_name',
      E::ts('State') => 'state_province',
    );
    return $columns;
  }

  /**
   * Construct a full SQL query which returns one page worth of results
   *
   * @param int $offset
   * @param int $rowcount
   * @param null $sort
   * @param bool $includeContactIDs
   * @param bool $justIDs
   * @return string, sql
   */
  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
    // delegate to $this->sql(), $this->select(), $this->from(), $this->where(), etc.
    return $this->sql($this->select(), $offset, $rowcount, $sort, $includeContactIDs, NULL);
  }

  /**
   * Construct a SQL SELECT clause
   *
   * @return string, sql fragment with SELECT arguments
   */
  function select() {
    return "
      contact_a.id           as contact_id  ,
      contact_a.contact_type as contact_type,
      contact_a.sort_name    as sort_name,
      state_province.name    as state_province
    ";
  }

  /**
   * Construct a SQL FROM clause
   *
   * @return string, sql fragment with FROM and JOIN clauses
   */
  function from() {
    return "
      FROM      civicrm_contact contact_a
      LEFT JOIN civicrm_address address ON ( address.contact_id       = contact_a.id AND
                                             address.is_primary       = 1 )
      LEFT JOIN civicrm_email           ON ( civicrm_email.contact_id = contact_a.id AND
                                             civicrm_email.is_primary = 1 )
      LEFT JOIN civicrm_state_province state_province ON state_province.id = address.state_province_id
    ";
  }

  /**
   * Construct a SQL WHERE clause
   *
   * @param bool $includeContactIDs
   * @return string, sql fragment with conditional expressions
   */
  function where($includeContactIDs = FALSE) {
    $params = array();
    $where = "contact_a.contact_type   = 'Household'";

    $count  = 1;
    $clause = array();
    $name   = CRM_Utils_Array::value('household_name',
      $this->_formValues
    );
    if ($name != NULL) {
      if (strpos($name, '%') === FALSE) {
        $name = "%{$name}%";
      }
      $params[$count] = array($name, 'String');
      $clause[] = "contact_a.household_name LIKE %{$count}";
      $count++;
    }

    $state = CRM_Utils_Array::value('state_province_id',
      $this->_formValues
    );
    if (!$state &&
      $this->_stateID
    ) {
      $state = $this->_stateID;
    }

    if ($state) {
      $params[$count] = array($state, 'Integer');
      $clause[] = "state_province.id = %{$count}";
    }

    if (!empty($clause)) {
      $where .= ' AND ' . implode(' AND ', $clause);
    }

    return $this->whereClause($where, $params);
  }

  /**
   * Determine the Smarty template for the search screen
   *
   * @return string, template path (findable through Smarty template path)
   */
  function templateFile() {
    return 'CRM/Mods/Form/Search/InterestSearch.tpl';
  }

  /**
   * Modify the content of each row
   *
   * @param array $row modifiable SQL result row
   * @return void
   */
  function alterRow(&$row) {
    $row['sort_name'] .= ' ( altered )';
  }
}
