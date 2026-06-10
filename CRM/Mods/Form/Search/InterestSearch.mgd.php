<?php

declare(strict_types = 1);

// This file declares a managed database record of type "CustomSearch".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return [
  0 =>
  [
    'name' => 'CRM_Mods_Form_Search_InterestSearch',
    'entity' => 'CustomSearch',
    'params' =>
    [
      'version' => 3,
      'label' => 'Suche nach Interessen',
      'description' => 'Sucht Kontakte nach Interessen.',
      'class_name' => 'CRM_Mods_Form_Search_InterestSearch',
    ],
  ],
];
