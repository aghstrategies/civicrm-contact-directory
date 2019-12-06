CiviCRM Contact Directory
--------------
## Set Up
1. Install the plugin.
2. create a page with the shortcode [civicrm_contact_directory]

### Optional Attributes
+ group - IF you would like to limit the results to a specific group enter that group id.
+ singleview - IF you would like to display additional information when viewing a single contact create a message template formatted as you would like it to be and add the id with this attribute.
+ specialty - IF you would like to include a Specialty filter include the custom_field options list you are interested in having end users filter on.

## Directory Description

### filters that should be available for use include:
* Name (assuming to search on first OR last for Individuals and Org Name for Organizations)
* Proximity search
* IF a specialty attribute is included in the shortcode - Specialty - This only appears to apply to Providers. This field set has the Specialty field: (https://www.pwsausa.org/wp-admin/admin.php?page=CiviCRM&q=civicrm%2Fadmin%2Fcustom%2Fgroup%2Ffield&action=preview&reset=1&gid=11&id=60) but I can't find an equivalent for Residential organizations.

### Results listing should include:
* Org name (Residential) / Display name (Provider)
* Street Address
* Supplemental Address 1
* City
* State
* Postal Code
* Phone (primary)

### Name field should click through to more details about the contact:
[repeat all above]
IF a singleview attribute is included in the shortcode then additionally, the contents of the message template id passed should be displayed with contact tokens populated.
