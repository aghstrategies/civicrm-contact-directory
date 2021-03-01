CiviCRM Contact Directory
--------------
## Set Up
1. Install the plugin.
2. create a page with the shortcode [civicrm_contact_directory]

### Optional Attributes
+ group - IF you would like to limit the results to a specific group enter that group id.
+ singleview - IF you would like to display additional information when viewing a single contact create a message template formatted as you would like it to be and add the id with this attribute.
+ specialty - IF you would like to include a Specialty filter include the name of the custom field ex: 'custom_9'
+ mainview - IF you would like to customize how results are displayed you can configure a message template using contact tokens and set the id here. If you do not, it defaults to the display name and address
+ search - If you set the search to "no" no search filters will be displayed
+ displaynamefilter - Include a filter by name
+ proximityfilter - Include a proximity filter

## Directory Description

### filters that should be available for use include:
* Name (assuming to search on first OR last for Individuals and Org Name for Organizations)
* Proximity search
* IF a specialty attribute is included in the shortcode

### Default Results listing should include:
* Display Name
* Street Address
* Supplemental Address 1
* City
* State
* Postal Code
* Phone (primary)

### Name field should click through to more details about the contact:
[repeat all above]
IF a singleview attribute is included in the shortcode then additionally, the contents of the message template id passed should be displayed with contact tokens populated.
