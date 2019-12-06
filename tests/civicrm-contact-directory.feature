FEATURE CiviCRM Contact Directory

Scenario: Visiting the Directory

WHEN a user goes to a page
AND the page has a shortcode [civicrm_contact_directory group='20']
THEN they should see a listing of all Contacts in the group id 20

Scenario: Searching by name

WHEN a user goes to a page
AND the page has a shortcode [civicrm_contact_directory group='20']
AND enters the string "AGH" into the filter Name
THEN they should see a listing of all Contacts in group 20 with AGH in their display name

Scenario: Viewing a contact

WHEN a user goes to a page
AND the page has a shortcode [civicrm_contact_directory group='20' singleview='69']
AND searches
AND clicks on an indivudual
THEN they should see the contents of message template 69 populated with the tokens for that contact

Scenario: Specialty Filter

WHEN a user goes to a page
AND the page has a shortcode [civicrm_contact_directory specialty="custom_60"]
THEN a specialty filter should be displayed with the options for custom_60
