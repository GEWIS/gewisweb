## [v2.6.1](https://github.com/GEWIS/gewisweb/tree/v2.6.1) (2022-02-13)

* Changed default site language to English.
* Added functionality to automatically infer the user's language preference.
* Added alternate URLs for pages.
* Changed default encoding of images from `jpg/png` to `webp`.
* Improved caching of resources (images, fonts, etc.).
* Fixed issue where an error was displayed even though an authorization was successful.
* Fixed issue where it was possible to authorize multiple people.
* Fixed issue where the frontpage panorama would not be the correct resolution.

---

## [v2.6](https://github.com/GEWIS/gewisweb/tree/v2.6) (2022-02-06)

* Added a button to the photo viewer to set a photo as your profile photo.
* Changed the activity option calendar, with improved controls for approving/deleting proposed options.
* Improved the layout of the course overview page.
* Preloaded required resources to improve future load time.
* Fixed issue where an activity could start after it had ended.
* Fixed issue where the member search page was visible to non-logged in members.
* Fixed issue where trying to set a non-existent photo as your profile picture would cause a crash.
* Fixed issue where loading fonts would be blocking, now swapped when ready.

---

## [v2.5.4](https://github.com/GEWIS/gewisweb/tree/v2.5.4) (2022-02-04)

- Changed Google Fonts to be hosted locally, to prevent violating GDPR.
- Removed final remnants from the old OASE integration.
- Fixed issue where the option calendar would not be visible in Chromium-based browsers.
- Fixed issue where "My Activities" was not accessible.
- Fixed issue where "Today's Birthdays" would be empty.
- Fixed issue where viewing a non-existent poll would fail.

---

## [v2.5.3](https://github.com/GEWIS/gewisweb/tree/v2.5.3) (2022-01-28)

- Added automatic generation of code documentation at [gewis.github.io/gewisweb](https://gewis.github.io/gewisweb/).
- Upgraded `laminas/laminas-form` to protect against a [potential reflected XSS vector](https://getlaminas.org/security/advisory/LP-2022-01).

---

## [v2.5.2](https://github.com/GEWIS/gewisweb/tree/v2.5.2) (2022-01-03)

- Added `Referrer-Policy` to restrict referrer information on cross-origin requests.
- Added `Permissions-Policy` to restrict access to web APIs which are not used by the website.
- Fixed issue where the activity notice on career activities would link to the CIB instead of the CEB.
- Fixed issue where the activity notice would show information about unsubscribing when no sign-up list was available.
- Updated dependencies.
- Updated the `$year` variable to `2022`.

---

## [v2.5.1](https://github.com/GEWIS/gewisweb/tree/v2.5.1) (2021-12-28)

- Added GitHub Actions for automated analysis and feedback of proposed changes.
- Added initial framework for (automated) unit testing.
- Updated the Public Archive to use the new file server.
- Switched to stricter type checking to set a baseline for code style and improved error checking.
- Fixed issue where pulsing attention dot would not get removed while navigating between photos.
- Fixed issue where albums with long names would create unnatural layouts.
- Fixed issue where albums with children would not be accessible.
- Fixed several other bugs while adding strict type checks.

---

## [v2.5](https://github.com/GEWIS/gewisweb/tree/v2.5) (2021-12-22)

- Added a privacy widget where users can set their tracking preferences.
- Improved the new photo viewer.
- Upgraded to PHP 8.1.
- Removed the old photo viewer.

---

## [v2.4.12](https://github.com/GEWIS/gewisweb/tree/v2.4.12) (2021-12-03)

- Added the ability to change sub-albums to normal albums.
- Added button to Supremum website in Members section.
- Remove defunct Microsoft Dreamspark/Imagine integration.

---

## [v2.4.11](https://github.com/GEWIS/gewisweb/tree/v2.4.11) (2021-11-29)

- Added security policy and [security.txt](https://gewis.nl/.well-known/security.txt).
- General improvements. 

---

## [v2.4.10](https://github.com/GEWIS/gewisweb/tree/v2.4.10) (2021-11-22)

- Improved accessibility by replacing `<i></i>` and `<b></b>` tags with more appropriate alternatives where necessary.
- Fixed issue where old, already approved, options would be considered 'overdue'.

---

## [v2.4.9](https://github.com/GEWIS/gewisweb/tree/v2.4.9) (2021-11-17)

- Fixed issue where it was not possible to create or edit job or banner packages for companies.

---

## [v2.4.8](https://github.com/GEWIS/gewisweb/tree/v2.4.8) (2021-11-17)

- Fixed issue where it was impossible to comment on polls. 
- Fixed issue where it was impossible to subscribe to a sign-up list when it contained sign-up options.

---

## [v2.4.7](https://github.com/GEWIS/gewisweb/tree/v2.4.7) (2021-11-16)

- Added admin interface to allow for management of activity calendar option periods.
- Added approve button to the activity calendar slide-out and fixed colour of original approve button.
- Improved option data in the activity calendar by displaying option type instead of creation time.
- Updated dependencies and removed the jQuery datetimepicker.
- Changed "lunch lecture" option type to "lunch break".
- Fixed issue where "My Activities" was shown to users who were not logged in.
- Fixed issue where the background of approved options in the activity calendar was not correctly coloured.
- Fixed issue where incorrectly encoded characters where displayed in imported or old data.

---

## [v2.4.6](https://github.com/GEWIS/gewisweb/tree/v2.4.6) (2021-11-06)

- Updated design and layout of the administration pages.
- Improved distinguishability of options in the activity option calendar.
- Switched from `utf8` to `utf8mb4`, allowing proper use of the full Unicode range (including new emojis).
- Privileges from old boards are automatically removed after July 1.
- Updated dependencies and removed unused dependencies.
- Fixed issue where visiting non-existent meetings would result in an exception.
- Fixed issue where the public archive would not be up-to-date.

---

## [v2.4.5](https://github.com/GEWIS/gewisweb/tree/v2.4.5) (2021-10-19)

- Improved layout of the main navbar, fixes overlap between members link and user's name.
- Fixed issue where most pages under `/member` were very slow. This significantly speeds up member lookups.

---

## [v2.4.4](https://github.com/GEWIS/gewisweb/tree/v2.4.4) (2021-10-12)

* Fixed issue where creation of an organ profile was not possible.

---

## [v2.4.3](https://github.com/GEWIS/gewisweb/tree/v2.4.3) (2021-10-09)

* Added the activity language system to companies, this allows for easier management of companies and vacancies.
* Updated layout and styling of company admin pages (forms).
* Updated layout and styling of company profiles.
* Fixed issue where organ pages did not show mutations.
* Several bug fixes and speed improvements.

---

## [v2.4.2](https://github.com/GEWIS/gewisweb/tree/v2.4.2) (2021-09-30)

* Added watermarking system to give downloaded exams and summaries a unique watermark.
* Added some more 🌈
* Changed datepicker to always start on Monday instead of Sunday.
* Fixed issue where videos on company profile did not work due to restrictive CSP.

---

## [v2.4.1](https://github.com/GEWIS/gewisweb/tree/v2.4.1) (2021-08-17)

* Fixed issue where users were unable to log out.
* Fixed issue where updating a company profile would delete it's language profiles.
* Several bug fixes and stability improvements.

---

## [v2.4](https://github.com/GEWIS/gewisweb/tree/v2.4) (2021-07-21)

* Upgraded to PHP 8.0 🎉.
* Several bug fixes.

---

## [v2.3](https://github.com/GEWIS/gewisweb/tree/v2.3) (2021-07-16)

* Upgraded to Laminas MVC.
* Several bug fixes.

---

## [v2.2.3](https://github.com/GEWIS/gewisweb/tree/v2.2.3) (2021-07-16)

* Re-added Infima.
* Fixed issue where sessions were not correctly persisted.

---

## [v2.2.2](https://github.com/GEWIS/gewisweb/tree/v2.2.2) (2021-07-07)

* Person who (dis)approved an activity is now shown in the admin details page.
* Fixed issue with activities breaking organ pages.
* Fixed issue when trying to upload large photos.
* Fixed issue with Decisions not actively syncing.
* Fixed issue with Photo of the Week generation.

---

## [v2.2.1](https://github.com/GEWIS/gewisweb/tree/v2.2.1) (2021-06-29)

* Patched ORM to fix issues with Decisions not showing.
* Several bug fixes.

---

## [v2.2](https://github.com/GEWIS/gewisweb/tree/v2.2) (2021-06-27)

* Added Matomo to gather website analytics (tracking and performance).

---

## [v2.1](https://github.com/GEWIS/gewisweb/tree/v2.1) (2021-06-25)

* Hotfix for several major bugs after Docker deployment and Activity module update.

---

## [v2.0](https://github.com/GEWIS/gewisweb/tree/v2.0) (2021-06-24)

* Moved to Docker for deployment.
* Updated the Activity module; includes sign-up lists, activity categories, and bug fixes.
* Bug fixes.

---

## [v1.8](https://github.com/GEWIS/gewisweb/tree/v1.8) (2020-08-21)

* Made the new photo viewer the main photo viewer
* Allowed for adding labels to jobs
* Various other improvements to the Company module
* Added activity policy agreement to activity signup
* Various UI improvements
* Various bugfixes

---

## [v1.7-beta](https://github.com/GEWIS/gewisweb/tree/v1.7-beta) (2020-02-10)

* Changed website icons to fontawesome
* Added functionality for manually sorting meeting documents
* Various UI improvements
* Various bugfixes

---

## [v1.6-beta](https://github.com/GEWIS/gewisweb/tree/v1.6-beta) (2019-11-02)

* Implemented a new photo viewer next to the old photo viewer
* Improved the jobs overview
* Improved user registration
* Improved exception logging
* Various UI improvements
* Various bugfixes

---

## [v1.5.3](https://github.com/GEWIS/gewisweb/tree/v1.5.3) (2019-09-26)

* Various UI improvements
* Various bugfixes

---

## [v1.5.2](https://github.com/GEWIS/gewisweb/tree/v1.5.2) (2019-09-19)

* Improved option calendar
* Used more informative title for emails to GEFLITST
* Various bugfixes

---

## [v1.5.1](https://github.com/GEWIS/gewisweb/tree/v1.5.1) (2019-09-02)

* Implemented RFC2324
* Updated dependencies
* Various UI improvements
* Various bugfixes

---

## [v1.5.0](https://github.com/GEWIS/gewisweb/tree/v1.5.0) (2019-06-25)

* Revised the option calendar system
* Addded option for users to select profile pictures
* Added social media references
* Various UI improvements
* Various bugfixes

---

## [v1.4.2](https://github.com/GEWIS/gewisweb/tree/v1.4.2) (2019-05-10)

* Added ability to add documents to VV's
* Addded option for users to select profile pictures
* Profile pictures are now cached
* Automatic profile picture selection has been improved
* Various UI improvements
* Various bugfixes

---

## [v1.4.1](https://github.com/GEWIS/gewisweb/tree/v1.4.1) (2019-03-21)

* Sort meeting documents on number
* Add profile picture to user profile
* Add overview of users subscribed activities
* Various UI improvements
* Various bugfixes

---

## [v1.4.0](https://github.com/GEWIS/gewisweb/tree/v1.4.0) (2019-02-06)

* Redesigned the members page!
* Authorizations now send confirmation emails
* A warning will be displayed when authorizing someone with 2 authorizations
* Disclaimer on educational material
* Various UI improvements
* Various bugfixes

---

## [v1.3.2](https://github.com/GEWIS/gewisweb/tree/v1.3.2) (2018-03-02)

* Added page to browse the Public Archive to the website
* Various UI improvements
* Various bugfixes

---

## [v1.3.1](https://github.com/GEWIS/gewisweb/tree/v1.2.3) (2017-12-23)

* Added ability to browse member photos as an album
* Added job categories
* Show GPS location for photos
* Automatically email GEFLITST for new activities
* Various UI improvements
* Various bugfixes

---

## [v1.3](https://github.com/GEWIS/gewisweb/tree/v1.2.3) (2017-12-09)

* Completely redesigned homepage!
* Added an activity archive
* Company banners can now be shown on the homepage
* Automatically email GEFLITST for new activities
* Various UI improvements
* Various bugfixes

---

## [v1.2.3](https://github.com/GEWIS/gewisweb/tree/v1.2.3) (2017-04-26)

* Added a page for career related activities

---

## [v1.2.2](https://github.com/GEWIS/gewisweb/tree/v1.2.2) (2017-04-21)

* External participants can now subscribe to activities on their own
* Photos can now only be viewed by logged in members
* The list of activity participants can now be hidden for non-members
* The changelog is now displayed in the website admin
* Various bugfixes and UI improvements

---

## [v1.2.1](https://github.com/GEWIS/gewisweb/tree/v1.2.1) (2017-01-10)

* Various bugfixes

---

## [v1.2](https://github.com/GEWIS/gewisweb/tree/v1.2) (2017-01-09)

* External participants can now be subscribed to activites through the activity admin
* Images on committee/fraternity pages now look normal
* Activities can now be edited :tada: (#594)
* Improved the activity administrator interface (#642)
* Made the navigation on the members page less confusing (#676)
* Improved the activity list view (#685)
* Allow empty poll options to be deleted (#599)
* Add a contact name to companies (#654)
* Clarified the activity creation UI
* Improved the photo UI on the members page
* Emails are sent on the creation of polls and organ information (#512)
* Various bugfixes and improvements (#700, #487, #506, #648, #529, #599, #683, #578, #610, #498)

---

## [v1.1](https://github.com/GEWIS/gewisweb/tree/v1.1) (2016-09-12)

* Many bugfixes and stability improvements

---

## [v1.0](https://github.com/GEWIS/gewisweb/tree/v1.0) (2016-02-06)

* Initial release, lots of great features
