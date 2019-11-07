<?php
use CRM_Mods_ExtensionUtil as E;

/**
 * A custom contact search
 */
class CRM_Mods_Form_Search_InterestSearch extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  const CUSTOM_GROUP_NAME_ZUSATZINFORMATIONEN = 'zusatzinformationen';

  private $criteria_fields = array(
    'medien_typen',
    'medien_themen',
    'medien_sonderverteiler',
    'themeninteressen',
    'personen_organisationen',
    'expertinnen',
    'metaverteiler',
  );

  private $filter_fields = array(
    'sprache',
    'land',
    'bundesland',
  );

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

    $selection_fields = array();
    foreach ($this->criteria_fields as $criteria_field_name) {
      $criteria_field = CRM_Mods_CustomData::getCustomField(
        self::CUSTOM_GROUP_NAME_ZUSATZINFORMATIONEN,
        $criteria_field_name
      );

      foreach ($include_exclude as $category => $category_label) {
        $criteria_field_name = $category . '_custom_' . $criteria_field['id'];
        $form->addSelect(
          $criteria_field_name,
          array(
            'field' => 'custom_' . $criteria_field['id'],
            // Get the label for the custom field, since this is not working
            // correctly in CRM_Core_Form::addSelect().
            'label' => CRM_Core_DAO::getFieldValue(
              'CRM_Core_DAO_CustomField',
              $criteria_field['id'],
              'label'
            ) . ' (' . $category_label . ')',
            'multiple' => TRUE,
          )
        );
        $selection_fields[] = $criteria_field_name;
      }
    }

    $form->assign('selection', $selection_fields);

    $filter_fields = array();
    foreach ($this->filter_fields as $filter_field_name) {
      $filter_field = CRM_Mods_CustomData::getCustomField(
        self::CUSTOM_GROUP_NAME_ZUSATZINFORMATIONEN,
        $filter_field_name
      );

      foreach ($include_exclude as $category => $category_label) {
        $filter_field_name = $category . '_custom_' . $filter_field['id'];
        $form->addSelect(
          $filter_field_name,
          array(
            'field' => 'custom_' . $filter_field['id'],
            // Get the label for the custom field, since this is not working
            // correctly in CRM_Core_Form::addSelect().
            'label' => CRM_Core_DAO::getFieldValue(
                'CRM_Core_DAO_CustomField',
                $filter_field['id'],
                'label'
              ) . ' (' . $category_label . ')',
            'multiple' => TRUE,
          )
        );
        $filter_fields[] = $filter_field_name;
      }
    }

    // Add postal code range filter field.
    $form->add(
      'text',
      'postal_code_range_start',
      E::ts('Postal code range from')
    );
    $filter_fields[] = 'postal_code_range_start';

    $form->add(
      'text',
      'postal_code_range_end',
      E::ts('Postal code range to')
    );
    $filter_fields[] = 'postal_code_range_end';

    // Add exclude mailing filter field.
    $form->add(
      'select',
      'mailings',
      E::ts('Exclude contacts in mailings'),
      self::getMailings(),
      FALSE,
      array(
        'multiple' => TRUE,
        'class' => 'crm-select2',
        'placeholder' => E::ts('- any -')
      )
    );
    $filter_fields[] = 'mailings';

    $form->assign('filters', $filter_fields);
  }

  /**
   * @return null|string
   */
  public function count() {
    return CRM_Core_DAO::singleValueQuery($this->sql('COUNT(DISTINCT c.id) AS total'));
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
      E::ts('Sort name') => 'sort_name',
    );

    if (!empty($this->_formValues['postal_code_range_start']) || !empty($this->_formValues['postal_code_range_end'])) {
      $columns[E::ts('Postal code')] = 'postal_code';
    }

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
    return $this->sql($this->select(), $offset, $rowcount, $sort, $includeContactIDs, $this->groupBy());
  }

  /**
   * Construct a GROUP BY clause.
   *
   * @return string
   */
  function groupBy() {
    $groupBy = "
      GROUP BY
        c.id
    ";

    return $groupBy;
  }

  /**
   * Construct a SQL SELECT clause
   *
   * @return string, sql fragment with SELECT arguments
   */
  function select() {
    $select = "
      c.id AS contact_id,
      c.sort_name AS sort_name
    ";

    if (!empty($this->_formValues['postal_code_range_start']) || !empty($this->_formValues['postal_code_range_end'])) {
      $select .= ", address.postal_code AS postal_code";
    }

    return $select;
  }

  /**
   * Construct a SQL FROM clause
   *
   * @return string, sql fragment with FROM and JOIN clauses
   */
  function from() {
    $from = "
      FROM
        civicrm_contact c
    ";

    // LEFT JOIN custom value table.
    $custom_group = CRM_Mods_CustomData::getCustomGroup(self::CUSTOM_GROUP_NAME_ZUSATZINFORMATIONEN);
    $from .= "
      LEFT JOIN
        {$custom_group['table_name']} v
        ON v.entity_id = c.id
    ";

    // LEFT JOIN address table (for postal code range comparison).
    if (!empty($this->_formValues['postal_code_range_start']) || !empty($this->_formValues['postal_code_range_end'])) {
      $from .= "
      LEFT JOIN
        civicrm_address address
        ON (address.contact_id = c.id AND address.is_primary = 1)
    ";
    }

    // LEFT JOIN mailing recipients.
    if (!empty($this->_formValues['mailings'])) {
      $from .= "
        LEFT JOIN
          civicrm_mailing_recipients recipients
          ON recipients.contact_id = c.id
        ";
    }

    return $from;
  }

  /**
   * Construct a SQL WHERE clause
   *
   * @param bool $includeContactIDs
   * @return string, sql fragment with conditional expressions
   */
  function where($includeContactIDs = FALSE) {
    $params = array();

    /**
     * Exclude:
     * - deleted contacts
     * - deceased contacts
     * - contacts opted out of mass mailings
     * - contacts opted out of e-mail
     */
    $where = "c.is_deleted != 1";
    $where .= " AND c.is_deceased != 1";
    $where .= " AND c.is_opt_out != 1";
    $where .= " AND c.do_not_email != 1";

    /**
     * Exclude criteria fields.
     */
    $exclude_clauses = array();
    foreach ($this->criteria_fields as $exclude_field_name) {
      $custom_field = CRM_Mods_CustomData::getCustomField(
        self::CUSTOM_GROUP_NAME_ZUSATZINFORMATIONEN,
        $exclude_field_name
      );
      $values = $this->_formValues['exclude_custom_' . $custom_field['id']];
      if (!empty($values)) {
        $padded_values = CRM_Utils_Array::implodePadded($values);
        $exclude_clauses[] =
          "(v.{$custom_field['column_name']} IS NULL"
          . " OR "
          . "v. {$custom_field['column_name']} NOT LIKE '{$padded_values}')";
      }
    }
    if (!empty($exclude_clauses)) {
      $where .= " AND " . implode(" AND ", $exclude_clauses);
    }

    /**
     * Include criteria fields.
     */
    $include_clauses = array();
    foreach ($this->criteria_fields as $include_field_name) {
      $custom_field = CRM_Mods_CustomData::getCustomField(
        self::CUSTOM_GROUP_NAME_ZUSATZINFORMATIONEN,
        $include_field_name
      );
      $values = $this->_formValues['include_custom_' . $custom_field['id']];
      if (!empty($values)) {
        $padded_values = CRM_Utils_Array::implodePadded($values);
        $include_clauses[] = "v.{$custom_field['column_name']} LIKE '{$padded_values}'";
      }
    }
    if (!empty($include_clauses)) {
      $where .= " AND " . implode(" AND ", $include_clauses);
    }

    /**
     * Include filter fields
     */
    $include_filter_clauses = array();
    foreach ($this->filter_fields as $include_filter_field_name) {
      $custom_field = CRM_Mods_CustomData::getCustomField(
        self::CUSTOM_GROUP_NAME_ZUSATZINFORMATIONEN,
        $include_filter_field_name
      );
      $values = $this->_formValues['include_custom_' . $custom_field['id']];
      if (!empty($values)) {
        $padded_values = CRM_Utils_Array::implodePadded($values);
        $include_filter_clauses[] = "v.{$custom_field['column_name']} LIKE '{$padded_values}'";
      }
    }
    if (!empty($include_filter_clauses)) {
      $where .= " AND " . implode(" AND ", $include_filter_clauses);
    }

    /**
     * Exclude filter fields
     */
    $exclude_filter_clauses = array();
    foreach ($this->filter_fields as $exclude_filter_field_name) {
      $custom_field = CRM_Mods_CustomData::getCustomField(
        self::CUSTOM_GROUP_NAME_ZUSATZINFORMATIONEN,
        $exclude_filter_field_name
      );
      $values = $this->_formValues['exclude_custom_' . $custom_field['id']];
      if (!empty($values)) {
        $padded_values = CRM_Utils_Array::implodePadded($values);
        $exclude_filter_clauses[] = "v.{$custom_field['column_name']} LIKE '{$padded_values}'";
      }
    }
    if (!empty($exclude_filter_clauses)) {
      $where .= " AND " . implode(" AND ", $exclude_filter_clauses);
    }

    /**
     * Add postal code range.
     *
     * Taken from @see CRM_Contact_Form_Search_Custom_ZipCodeRange::where()
     */
    if (!empty($this->_formValues['postal_code_range_start'])) {
      $where .= "
        AND ROUND(address.postal_code) >= %1
      ";
      $params[1] = array(trim($this->_formValues['postal_code_range_start']), 'Integer');
    }

    if (!empty($this->_formValues['postal_code_range_end'])) {
      $where .= "
        AND ROUND(address.postal_code) <= %2
      ";
      $params[2] = array(trim($this->_formValues['postal_code_range_end']), 'Integer');
    }

    /**
     * Add mailing contacts exclusion.
     */
    if (!empty($this->_formValues['mailings'])) {
      $exclude_mailing_ids = implode(',', $this->_formValues['mailings']);
      $where .= "
        AND (
          recipients.mailing_id IS NULL
          OR recipients.mailing_id NOT IN({$exclude_mailing_ids})
        )
      ";
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
   * Retrieves a list of Mailings to be used as select field options.
   */
  public static function getMailings() {
    $mailings = civicrm_api3('Mailing', 'get', array(
      // All mailings during the past 12 months
      'scheduled_date' => array(
        '>=' => date_format(date_create()->sub(new DateInterval('P12M')), 'Ymd'),
      ),
      'is_completed' => 1,
      'option.limit' => 0,
    ));
    array_walk($mailings['values'], function(&$mailing) {
      $mailing = $mailing['name'];
    });
    return $mailings['values'];
  }
}
