# v2.1.7
##  13/02/2019

1. [](#new)
    * Serbian translation (Thanks: https://github.com/tomaja-linuxo PR #47)
    * Russian translation (Thanks: https://github.com/Lufog-git PR #50)
    * Ukrainian translation (Thanks: https://github.com/Lufog-git PR #51)

2. [](#improved)
    * Fixed some non-translatable strings (Thanks: https://github.com/Lufog-git PR #52)

# v2.1.6
##  06/06/2018

1. [](#bugfix)
    * Fixed error when using 'Login As' feature with an user without admin permissions (#43)

# v2.1.5
##  09/04/2018

1. [](#bugfix)
    * Fixed error when rendering front-end (#40)

# v2.1.4
##  09/04/2018

1. [](#improved)
    * Moved 'site.login' permission to the front of permission list. (#36)

# v2.1.3
##  02/04/2018

1. [](#improved)
    * Validate user object on save

2. [](#bugfix)
    * Fixed unset user permissions being pushed into the access array with an empty string value. Causing inherited permissions to be overwritten. (#38)

# v2.1.2
##  29/03/2018

1. [](#new)
    * Norwegian translation (Thanks: https://github.com/achwell PR #37)

# v2.1.1
##  22/03/2018

1. [](#improved)
    * Added 'site.login' permission to the permission list. (#36)

# v2.1.0
##  14/03/2018

1. [](#new)
    * Czech translation (Thanks: https://github.com/07pepa Issue #29)
    * Spanish translation (Thanks: https://github.com/filisko PR #31)

2. [](#bugfix)
    * Fixed user editor using wrong task when saving, which caused save error when you didn't have 'admin.super'. (#34)
    * Added a temporary fix for user editor's permission area. The toggles moved below the permission's name at a specific width. (#22)
    * Minor bugfixes

# v2.0.3
##  27/02/2018

1. [](#improved)
    * Added missing translations

# v2.0.2
##  27/01/2018

1. [](#bugfix)
    * Fixed wrong redirection after deleting an user (#28)
    * Added missing translation for user delete confirmation

# v2.0.1
##  01/01/2018

1. [](#bugfix)
    * Fixed admin links not working when something is changed in the form (#27)

# v2.0.0
##  29/12/2017

1. [](#new)
    * 'Login As' button

2. [](#bugfix)
    * Fixed being redirected to the deleted user, now redirects to the user manager
    * The delete button now shows up when editing the user
    * Avatar upload now works

# v1.9.1
##  29/12/2017

1. [](#bugfix)
    * Fixed 'Memory leak when using non-ascii character (?) to create group' (#26)
    * Fixed being redirected to the deleted group, now redirects to the group manager

# v1.9.0
##  02/12/2017

1. [](#improved)
    * Using custom blueprint for user editing (#23)
    * Using custom request handler for saving user data (#23)

# v1.8.1
##  18/09/2017

1. [](#improved)
    * Added username validating (#21)

# v1.8.0
##  14/08/2017

1. [](#new)
    * Custom permissions (#18)
    * User Expert editor (#19)

# v1.7.1
##  08/08/2017

1. [](#new)
    * Permissions input in the bulk modal now accepts new values too

# v1.7.0
##  08/08/2017

1. [](#new)
    * User permissions bulk actions

# v1.6.1
##  08/06/2017

1. [](#bugfix)
    * Fixed removing of user from group not working. (#16, #17 Moonlight63 <https://github.com/Moonlight63>)

# v1.6.0
##  07/31/2017

1. [](#new)
    * User bulk actions
    * Group bulk actions

# v1.5.4
##  07/28/2017

1. [](#bugfix)
    * Fixed groups.yaml is not created when saving a group for the first time

# v1.5.3
##  07/28/2017

1. [](#bugfix)
    * Fixed group creating not working properly

# v1.5.2
##  07/28/2017

1. [](#bugfix)
    * Fixed an error which appeared when there are no groups.yaml

# v1.5.1
##  07/28/2017

1. [](#bugfix)
    * Better PHP compatibility

# v1.5.0
##  07/28/2017

1. [](#new)
    * Filter users
    * Filter groups

# v1.4.3
##  07/27/2017

1. [](#new)
    * Users count are now shown at group manager
    * Users now can be added and/or removed from the group you are currently editing

# v1.4.2
##  07/27/2017

1. [](#improved)
    * Pagination performance improvement

2. [](#bugfix)
    * Fixed group name is not shown when editing a group

# v1.4.1
##  07/27/2017

1. [](#improved)
    * Permissions support

# v1.4.0
##  07/27/2017

1. [](#feature)
    * Groups management!

# v1.3.4
##  07/24/2017

1. [](#improved)
    * Plugin is now compatible with Grav Admin Styles Plugin

# v1.3.3
##  07/20/2017

1. [](#new)
    * Users to be shown per page is now configurable
    * Default list style is now configurable

2. [](#improved)
    * Refactored code a bit

# v1.3.2
##  07/20/2017

1. [](#improved)
    * Performance is improved when admin cache is disabled

1. [](#bugfix)
    * Fixed plugin not working when admin cache is disabled

# v1.3.1
##  07/19/2017

1. [](#improved)
    * Redirects to last URL after user delete
    * Prevents cache refresh after user delete (performance improvement)

2. [](#bugfix)
    * Params are now validated

# v1.3.0
##  07/19/2017

1. [](#improved)
    * Pagination is now more user friendly
    * Users are cached (better performance)

# v1.2.0
##  07/19/2017

1. [](#feature)
    * Added pagination
    * Added list style

# v1.1.2
##  07/19/2017

1. [](#improved)
    * No more page jumping because of avatars loading

# v1.1.1
##  07/06/2017

1. [](#bugfix)
    * Plugin is now compatible with PHP 5.5

# v1.1.0
##  07/03/2017

1. [](#new)
    * Delete users from the UI

2. [](#improved)
    * Revamped UI

# v1.0.0
##  06/27/2017

1. [](#new)
    * ChangeLog started...
