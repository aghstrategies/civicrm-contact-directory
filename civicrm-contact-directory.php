<?php
/*
Plugin Name: CiviCRM Contact Directory
Plugin URI: https://git.aghstrategies.com/
Description: Creates a shortcode to make a Directory of CiviCRM Contacts
Version: 3.1
Author: AGH Strategies, LLC
Author URI: http://aghstrategies.com/
 */
/*
 *	Copyright 2013-2015 AGH Strategies, LLC	(email : info@aghstrategies.com)
 *
 *	This program is free software; you can redistribute it and/or modify
 *	it under the terms of the GNU Affero General Public License as published by
 *	the Free Software Foundation; either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
 *	GNU Affero General Public License for more details.
 *
 *	You should have received a copy of the GNU Affero General Public License
 *	along with this program; if not, write to the Free Software
 *	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA	02110-1301	USA
 **/

// Shortcode [civicrm_contact_directory]
add_shortcode('civicrm_contact_directory', 'civicrm_contact_directory_shortcode');
wp_enqueue_style('civicrm-contact-directory-css', plugins_url('civicrm-contact-directory.css', __FILE__));

/**
 * Function to Create Shortcode for directory
 */
function civicrm_contact_directory_shortcode($atts) {
  civicrm_initialize();

  // Used to set the default for the display name field
  $locationDefault = $distanceDefault = $displayNameDefault = $specialtyFilterHTML = '';

  // Compile filters (if any are sent)
  $filters = [];

  // Parameter for group of contacts to be displayed in the directory
  if (!empty($atts['group'])) {
    $groupToDisplay = $atts['group'];
  }

  // Message Template to be used on the single contact view
  if (!empty($atts['singleview'])) {
    $singleView = $atts['singleview'];
  }

  // Include the specialty filter?
  if (!empty($atts['specialty'])) {
    $specialtyFilter = $atts['specialty'];
  }

  if (isset($specialtyFilter)) {
    // Get Specialty Filter Options
    $specialtyOptions = '';
    $specialty = civicrm_contact_directory_get_field_options($specialtyFilter);

    if (!empty($specialty)) {
      foreach ($specialty as $id => $label) {
        // If a search has been run using the specialty field default to the specialty selected
        if (isset($_POST['specialty']) && $_POST['specialty'] == $id) {
          $specialtyOptions .= "<option selected='selected' value={$id}>{$label}</option>";
        }
        else {
          $specialtyOptions .= "<option value={$id}>{$label}</option>";
        }
      }
    }
    $specialtyFilterHTML = '<label>Specialty</label></br>
    <select name="specialty" id="specialty">
      <option value="">Choose Specialty</option>' . $specialtyOptions . '
    </select>
    </br>';
  }

  if (isset($_POST['gg'])) {
    if (isset($_POST['display_name'])) {
      // Set the default for the display name and Send the display name filter
      $displayNameDefault = $filters['display_name'] = $_POST['display_name'];
    }
    if (isset($_POST['distance'])) {
      $distanceDefault = $filters['distance'] = $_POST['distance'];
    }
    if (isset($_POST['location'])) {
      $locationDefault = $filters['location'] = $_POST['location'];
    }
    // Send the specialty filter
    if (isset($_POST[$specialtyFilter])) {
      $filters[$specialtyFilter] = $_POST[$specialtyFilter];
    }
  }

  $searchForm = '<form class="civiDirectoryForm" method = "post">
  <h2>Search Filters</h2>
  <label>Name</label></br>
  <input class="displayName" type="text" size="50" name="display_name" value=' . $displayNameDefault . '>
  </br>' . $specialtyFilterHTML . '<label>Proximity</label>
  </br>
  <span>With in</span>
  <input size="7" type="text" name="distance" value=' . $distanceDefault . '>
  <span>miles of</span>
  <input size="20" type="text" name="location" value=' . $locationDefault . '>
  </br>
  <input class="searchButton" type="submit" name="gg" value="Search">
  </form>';

  if (isset($_GET['cid'])) {
    // If a cid is in the url DO NOT show the search form
    $searchForm = "";
    $filters['contact_id'] = $_GET['cid'];
  }

  $resultsDiv = civicrm_contact_directory_results($filters, $groupToDisplay, $singleView, $specialtyFilter);

  return "$searchForm $resultsDiv";
}

/**
 * Get results based on filters
 * @param  array $filters  filters to search on
 * @return string          formatted html to be displayed
 */
function civicrm_contact_directory_results($filters, $groupToDisplay = NULL, $singleView = NULL, $specialtyFilter = NULL) {
  $oopsSomethingDidNotWork = [];

  civicrm_initialize();

  // Do we need to do a proximity search
  if (!empty($filters['distance']) && !empty($filters['location'])) {
    // get lat/long of location (throw error if it cant be calculated)
    $proximityFromLocationData = [
      'street_address' => $filters['location'],
    ];
    CRM_Core_BAO_Address::addGeocoderData($proximityFromLocationData);
    if (!empty($proximityFromLocationData['geo_code_1']) && !empty($proximityFromLocationData['geo_code_2'])) {
      // Get all contacts in proximity lat, long
      $contactsInRange = civicrm_contact_directory_get_proximity($filters['distance'], $proximityFromLocationData['geo_code_1'], $proximityFromLocationData['geo_code_2']);
      if (empty($contactsInRange)) {
        $oopsSomethingDidNotWork['error_message'] = "No contacts found in range.";
      }
    }
    else {
      $oopsSomethingDidNotWork['error_message'] = "Could Not Calculate proximity based on the location entered.";
    }
  }
  // Validate that both location and distance have been entered
  if (!empty($filters['distance']) && empty($filters['location'])) {
    $oopsSomethingDidNotWork['error_message'] = "To calculate proximity please enter an address.";
  }
  if (empty($filters['distance']) && !empty($filters['location'])) {
    $oopsSomethingDidNotWork['error_message'] = "To calculate proximity please enter a number into the distance field.";
  }

  // Set empty results just in case
  $formattedResults = '';

  // Set the context as directory (as opposed to viewing a single contact)
  $context = 'directory';

  // Set the default return params
  $returnParams = [
    "display_name",
    "street_address",
    "supplemental_address_1",
    "city",
    "state_province_name",
    "postal_code",
    "phone",
    "state_province_id",
    "state_province",
  ];

  // Set the default Search Params
  $searchParams = [
    // 'contact_sub_type' => ['IN' => ["Provider", "Residential"]],
    'is_deleted' => 0,
    'options' => [
      'limit' => "",
      'sort' => "display_name asc",
    ],
  ];

  if ($groupToDisplay) {
    $searchParams['group'] = $groupToDisplay;
  }
  if (!empty($contactsInRange)) {
    $searchParams['id'] = ['IN' => $contactsInRange];
  }

  // Filter by Display NAme
  if (!empty($filters['display_name'])) {
    $searchParams['display_name'] = "%{$filters['display_name']}%";
  }

  // Filter by specialty
  if (!empty($filters[$specialtyFilter]) && isset($specialtyFilter)) {
    $searchParams[$specialtyFilter] = $filters[$specialtyFilter];
  }

  // Filter by contact id -> Used when showing a single card
  if (!empty($filters['contact_id'])) {
    $searchParams['contact_id'] = $filters['contact_id'];

    // Add custom fields to the list of params to return
    $customFieldsToInclude = civicrm_contact_directory_get_custom_fields();

    foreach ($customFieldsToInclude as $fieldId => $values) {
      $returnParams[] = 'custom_' . $fieldId;
    }
    $context = 'single';
  }

  $searchParams['return'] = $returnParams;

  // Get Relevant Contacts
  try {
    $contacts = civicrm_api3('Contact', 'get', $searchParams);
  }
  catch (CiviCRM_API3_Exception $e) {
    $error = $e->getMessage();
    CRM_Core_Error::debug_log_message(ts('API Error while finding contacts %1', array(
      'domain' => 'civicrm-contact-directory',
      1 => $error,
    )));
  }
  if (!empty($contacts['values'])) {
    foreach ($contacts['values'] as $contactId => $contactDetails) {
      $formattedResults .= civicrm_contact_directory_format_contact($contactDetails, $context, $singleView);
    }
  }
  else {
    $oopsSomethingDidNotWork['error_message'] = "No Results Found.";
  }
  if (!empty($oopsSomethingDidNotWork['error_message'])) {
    $formattedResults = "<div class='error'>{$oopsSomethingDidNotWork['error_message']}</div>";
  }
  $resultsDiv = "<div class='resultsDiv'>$formattedResults</div>";
  return $resultsDiv;
}

/**
 * Formats Contacts to be displayed
 * @param  array $contactDetails  array of information to display
 * @param  string $context        single (to display just one contact) or directory (for the full listing)
 * @return string                 formatted html to be displayed
 */
function civicrm_contact_directory_format_contact($contactDetails, $context, $singleView = NULL) {
  $displayName = "<h3>{$contactDetails['display_name']}</h3>";
  $singleViewDetails = '';

  // Format Single card entries
  if ($context == 'single' && $singleView) {
    try {
      $msgTemplate = civicrm_api3('MessageTemplate', 'getsingle', ['id' => $singleView]);
    }
    catch (CiviCRM_API3_Exception $e) {
      $error = $e->getMessage();
      CRM_Core_Error::debug_log_message(ts('API Error while finding contacts %1', array(
        'domain' => 'civicrm-contact-directory',
        1 => $error,
      )));
    }
    if (!empty(!empty($msgTemplate['msg_html']))) {
      $mailing = new CRM_Mailing_BAO_Mailing();
      $mailing->body_html = $msgTemplate['msg_html'];
      $tokens = $mailing->getTokens();
      $returnProperties = [];
      $contactParams = ['contact_id' => $contactDetails['contact_id']];
      foreach ($tokens['html']['contact'] as $name) {
        $returnProperties[$name] = 1;
      }

      list($contact) = CRM_Utils_Token::getTokenDetails($contactParams,
        $returnProperties,
        FALSE, FALSE, NULL,
        CRM_Utils_Token::flattenTokens($tokens),
        // we should consider adding groupName and valueName here
        'CRM_Core_BAO_MessageTemplate'
      );
      $singleViewDetails .= CRM_Utils_Token::replaceContactTokens($msgTemplate['msg_html'], $contact, FALSE, $tokens['html'], FALSE, TRUE);
    }

    $url = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    $singleViewDetails .= "<div><a href='$url'>Back to directory listing</a></div>";
  }
  else {
    $displayName = "<a href='{$_SERVER['REQUEST_URI']}?cid={$contactDetails['contact_id']}'>$displayName</a>";
  }
  if (!empty($contactDetails['city']) && !empty($contactDetails['state_province'])) {
    $addressLine = "{$contactDetails['city']}, {$contactDetails['state_province']} {$contactDetails['postal_code']}";
  }
  else {
    $addressLine = "{$contactDetails['city']} {$contactDetails['state_province']} {$contactDetails['postal_code']}";
  }

  //Format Address line
  return "<div class='civicontact'>
    <div>{$displayName}</div>
    <div>{$contactDetails['street_address']}</div>
    <div>{$contactDetails['supplemental_address_1']}</div>
    <div>{$addressLine}</div>
    <div>{$contactDetails['phone']}</div>
    $singleViewDetails
  </div>";
}

/**
 * Get the custom fields to display on cards
 * @return array data about the relevant custom fields
 */
function civicrm_contact_directory_get_custom_fields() {
  $fieldsToInclude = [];
  try {
    $customFields = civicrm_api3('CustomField', 'get', [
      'custom_group_id' => ['IN' => ["Specialty", "Residential_Options"]],
    ]);
  }
  catch (CiviCRM_API3_Exception $e) {
    $error = $e->getMessage();
    CRM_Core_Error::debug_log_message(ts('API Error while finding custom values %1', array(
      'domain' => 'civicrm-contact-directory',
      1 => $error,
    )));
  }
  if (!empty($customFields['values'])) {
    $fieldsToInclude = $customFields['values'];
  }
  return $fieldsToInclude;
}

/**
 * Returns the field options for a given field keyed id => Label
 * @param  string $fieldName  field name
 * @return array            options for the field
 */
function civicrm_contact_directory_get_field_options($fieldName) {
  try {
    $fieldOptions = civicrm_api3('Contact', 'getoptions', [
      'field' => $fieldName,
    ]);
  }
  catch (CiviCRM_API3_Exception $e) {
    $error = $e->getMessage();
    CRM_Core_Error::debug_log_message(ts('API Error while finding custom field options %1', array(
      'domain' => 'civicrm-contact-directory',
      1 => $error,
    )));
  }
  return $fieldOptions['values'];
}

/**
 * Get Contacts in range
 * @param  int $distance    number of miles
 * @param  float $lat       lattitude of location
 * @param  float $long      longitude of location
 * @return array            array of contact ids of contacts in range
 */
function civicrm_contact_directory_get_proximity($distance, $lat, $long) {
  $contactsInRange = [];
  try {
    $contactsInProximity = civicrm_api3('Contact', 'proximity', [
      'latitude' => $lat,
      'longitude' => $long,
      'unit' => "miles",
      'distance' => $distance,
    ]);
  }
  catch (CiviCRM_API3_Exception $e) {
    $error = $e->getMessage();
    CRM_Core_Error::debug_log_message(ts('API Error while finding proximity %1', array(
      'domain' => 'civicrm-contact-directory',
      1 => $error,
    )));
  }
  if (!empty($contactsInProximity['values'])) {
    foreach ($contactsInProximity['values'] as $key => $contactDetails) {
      $contactsInRange[] = $contactDetails['contact_id'];
    }
  }
  return $contactsInRange;
}
