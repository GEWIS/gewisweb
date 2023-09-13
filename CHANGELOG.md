## [v4.0.2](https://github.com/GEWIS/gewisweb/tree/v4.0.2) (2023-09-13)

* Fixed issue where (sub)decisions removed in GEWISDB were not removed during synchronisation.

---

## [v4.0.1](https://github.com/GEWIS/gewisweb/tree/v4.0.1) (2023-09-11)

* Added the option to mark a sign-up list field as sensitive. Sensitive fields are only viewable by the organiser or the board.
* Added tooltip to meeting documents to show when the meeting document was uploaded.
* Improved activity admin approval view by preserving structure of activity descriptions.
* Improved sign-up form by adding asterisks to denote required fields.
* Fixed issue where the navbar was grey instead of GEWIS red.
* Fixed issue where it was possible to use special path characters for custom routes.
* Fixed issue where sub-albums did not display the 'NEW' tag if they were recently created.
* Fixed issue where photos in the admin album overview would not load.
* Fixed issue where the 'Text' sign-up list field was never validated when signing up.
* Fixed issue where activities that were not yet approved could be viewed by everyone.

---

## [v4.0](https://github.com/GEWIS/gewisweb/tree/v4.0) (2023-08-30)

* Added notice for `administrator`s to warn them about their powers.
* Added history of board positions to member profiles of board members.
* Added option to mark course documents as `scanned` to improve quality of watermarked PDF.
* Added option for organisers of activities to view sign-up list details up to a month after the activity ended.
* Added more detailed suggestions to failed searches to help with getting results.
* Added notice to polls to prevent personal data from appearing in polls.
* Added horizontal watermark to course documents to help with automatic OCR detection.
* Added button to historical poll overview to go to the current poll.
* Added language aware router for localised URLs (e.g. [gewis.nl/en/](https://gewis.nl/en/)).
* Added localisable routes to custom pages.
* Changed how historical polls are displayed and interacted with.
* Changed coding standard to catch issues before they make it to production.
* Changed map provider for photo locations from Google Maps to OpenStreetMap.
* Changed how translations are compiled.
* Changed localisation of polls.
* Changed validation of poll questions to always require them to end with a question mark.
* Improved support for password managers to autofill and change passwords.
* Improved `diff` display for proposed updates to activities and vacancies.
* Improved selecting required viewing privileges for custom pages by exchanging the text field with a list.
* Improved consistency of page headings for custom pages.
* Improved synchronisation script for GEWISDB by replacing not dropping all data at once.
* Improved layout of album overview when albums have long titles.
* Fixed issue where long poll options were not split across multiple lines.
* Fixed issue where it was not possible to update a `JobCategory`.
* Fixed issue where poll question was not shown on the frontpage.
* Fixed issue where renaming a `MeetingDocument` would redirect away from the current page.
* Fixed issue where it was not possible to unsubscribe from an activity.
* Fixed issue where organ functions where displayed for the wrong organ due to incorrect deduplication and ordering of organ hashes.
* Fixed issue where e-mails with a `Reply-To` with special characters resulted in an exception.
* Fixed issue where MariaDB healthcheck did no longer work.
* Fixed issue where birthdays of expired memberships/graduate statuses were shown on July 1. 
* Fixed issue where it was not possible to view activity update proposals when the organiser was removed in the update.
* Fixed issue where it was possible to approve activity update proposals without having the proper privileges.
* Fixed issue where it was possible to comment on old or unapproved polls.
* Fixed issue where certain sign-up list fields would not show when selected.
* Fixed issue where errors in the synchronisation script for GEWISDB could result in (temporary) loss of data.
* Fixed issue where it was not possible to close a dropdown that was open by default on mobile devices.
* Fixed issue where (un)collapsing the main navbar would also (un)collapse the admin navbar.
* Updated dependencies.

---

## [v3.0.5](https://github.com/GEWIS/gewisweb/tree/v3.0.5) (2023-04-08)

* Changed text under active polls on the frontpage to be more descriptive.
* Removed notices regarding the changed password requirements.
* Fixed issue where adding spaces around poll content (e.g., comments) would circumvent length checks.

---

## [v3.0.4](https://github.com/GEWIS/gewisweb/tree/v3.0.4) (2023-03-28)

* Added bylaws and internal regulations to list of policies on the members page.
* Fixed issue where uploading a meeting document for a specific meeting would be uploaded to another meeting.

---

## [v3.0.3](https://github.com/GEWIS/gewisweb/tree/v3.0.3) (2023-03-25)

* Added sender and recipient names to e-mails.
* Fixed issue where keyholders were not correctly synced.
* Fixed issue where the 90-day reminder logic was inverted preventing external authentication.
* Fixed issue where the approver of an activity would be lost preventing being able to reset the approval status of the activity.
* Fixed issue where e-mails would not be sent if the recipient's name contains unicode characters.

---

## [v3.0.2](https://github.com/GEWIS/gewisweb/tree/v3.0.2) (2023-02-28)

* Added support for keyholders.
* Added `base-uri` to Content Security Policy to prevent hijacking of relative URLs.
* Changed website title from `GEWIS Website` to `Study Association GEWIS` (`Studievereniging GEWIS` when Dutch is selected as language).
* Changed sender of e-mails to `Study Association GEWIS`.
* Removed unused `photo_guest` role.
* Fixed issue where viewing retired fraternities could result in an error in certain cases.
* Fixed issue where going to an external application would fail if the 90-day reminder dialog was shown.

---

## [v3.0.1](https://github.com/GEWIS/gewisweb/tree/v3.0.1) (2023-02-14)

* Changed title of the "My Information"-page to prevent being able to track users through collected analytics.
* Changed login form validation messages to prevent account enumeration attacks.
* Changed login form redirects to prevent open redirects.
* Fixed issue where `graduate`s could be incorrectly assigned `active_member` privileges.

---

## [v3.0](https://github.com/GEWIS/gewisweb/tree/v3.0) (2023-02-10)

* Added support for marking sign-up lists as having limited capacity.
* Added support for adding a representative to a company (this is different from a company contact).
* Added `CompanyUser`s (i.e. representatives) that can manage company profiles.
* Added the GEWIS Career Platform where company representatives can log in to manage their company.
* Added support for company representatives to propose new jobs in the company's job package(s).
* Added support for company representatives to propose updates to existing jobs in the company's job package(s).
* Added support for company representatives to transfer jobs from expired job packages to non-expired job packages.
* Added support for company representatives to delete jobs.
* Added elementary support for company representatives to update their company's profile.
* Added the option to add a contract number to company packages.
* Added an approval queue for company profile and job (update) proposals.
* Added support for approving or rejecting job proposals (rejections may include a message that is shown to the company representative).
* Added support for applying or cancelling job update proposals (cancellations may include a message that is shown to the company representative).
* Added checks for passwords against the [GEWIS-hosted version of Pwned Passwords](https://pwned-passwords.gewis.nl). If a password is leaked in a public data breach, the user must reset their password before they can log in. When (re)setting passwords, this check is also performed and "pwned" passwords cannot be used.
* Added the Alcohol Policy to publicly available policies.
* Added timestamps to `SignUp`s to track when people signed up to a sign-up list.
* Added support for searching for specific decisions.
* Added timestamps to `Album`s to add a "NEW"-tag to recently uploaded albums.
* Added support for recording when a user has changed their password, this is used to see which users comply with new password requirements.
* Added support for renaming `MeetingDocument`s after being uploaded.
* Added timestamps to `MeetingDocument`s and `MeetingMinutes` to track when they are uploaded.
* Changed `AV` to `ALV` to adhere to the terminology from the bylaws.
* Changed the minimum required length of passwords to `12` for `User`s.
* Changed the career admin to move job categories and labels to separate sections, leaving more space to interact with companies.
* Changed most of the e-mail templates to use the new e-mail template from Stijl.
* Changed the default state of new jobs to be `published` (when approved).
* Changed the agreement text when subscribing to an activity to include the Alcohol Policy in accordance with changes to the Activity Policy.
* Changed the maximum number of decisions returned when searching to `100` (from `50`).
* Changed how decisions are displayed after searching or on meeting pages to improve readability.
* Changed the default duration of activation and password reset links to `24h` (from `∞`).
* Changed the default cookie `SameSite` directive to `Lax`.
* Improved several translations.
* Upgraded to PHP 8.2.
* Fixed issue where exams and summaries would still be inaccessible from the university's NAT'd Wi-Fi network.
* Fixed issue where the Content Security Policy was too lenient on what content was allowed.
* Fixed issue where cookies where incorrectly shared with sub-domains.
* Fixed issue where the privacy widget could appear after it was already dismissed.
* Fixed issue where a (limited) SQL injection was possible through the decision search field.
* Fixed issue where searching for decisions using only a meeting number would not return any decisions.
* Fixed issue where form validation on the login form was not applied.
* Fixed issue where proposing an update to an activity could silently fail.
* Fixed issue where selecting a meeting that shares its meeting number with another meeting of another type would prevent uploads of `MeetingDocument`s.
* Fixed issue where `deleted`, `expired`, or `hidden` members could still request a password reset.
* Updated dependencies.

---

## [v2.9.1](https://github.com/GEWIS/gewisweb/tree/v2.9.1) (2022-12-18)

* Added generation of members to the admin sign-up list participants overview.
* Improved the way upcoming meetings are displayed when multiple are planned.
* Improved several translations.
* Fixed issue where exams and summaries would be inaccessible from the university's NAT'd Wi-Fi network.
* Fixed issue where e-mails could be incorrectly classified as spam due to a missing `Message-Id` value.
* Updated dependencies.

---

## [v2.9](https://github.com/GEWIS/gewisweb/tree/v2.9) (2022-11-19)

* Added elementary support for remote member information update requests.
* Added support for editable courses.
* Added support for the deletion of courses and course documents.
* Changed option calendar to always start on Mondays regardless of used locale.
* Changed how options for activities can be proposed (this includes the ability to propose in different periods at the same time).
* Improved associations between members and resources to allow for easier removal of member data.
* Improved translations of all things related to meetings.
* Improved validation of the option proposal form.
* Improved ordering of album years in the photo admin dashboard.
* Improved distinction between normal folders and `Archive`d folders in the public archive.
* Fixed issue where the generation of an album cover would fail it the album only contained sub-sub-albums with photos.
* Fixed issue where course documents could not be downloaded due timeouts.
* Fixed issue where incorrectly filling out the option proposal form resulted in an error.

---

## [v2.8.9](https://github.com/GEWIS/gewisweb/tree/v2.8.9) (2022-10-12)

* Added the option to revoke an authorization that was made on the website.
* Improved the hiding of `deleted` members.
* Removed references to the `Web Commissie` and replaced them with `ApplicatieBeheerCommissie`.
* Removed last reference to `SuSOS`.
* Fixed issue where an invalid JWT cookie could lead to unauthenticated loops.

---

## [v2.8.8](https://github.com/GEWIS/gewisweb/tree/v2.8.8) (2022-10-02)

* Added a historical overview of organ memberships on a member's page.
* Added breadcrumbs for organs that are not listable.
* Added a button to GEWIKI on the Members page.
* Improved separation between sub-albums and photos in an album.
* Improved the performance of the `/career` page by reducing the number of executed queries.
* Improved loading of infima on the home page.
* Fixed issue where the privacy widget would not work as expected.
* Fixed issue where hidden members would appear in the results of a member search.
* Updated CKEditor.

---

## [v2.8.7](https://github.com/GEWIS/gewisweb/tree/v2.8.7) (2022-09-12)

* Added a switch to hide members from birthdays, search results, and logins.
* Added functionality to allow `graduates` to see their own photos and albums they are tagged in.
* Added a default cover for non-existing covers.
* Fixed issue where it was not possible to enable translatable fields in forms.
* Fixed issue where the Content Security Policy would break in production.
* Fixed issue where the Glide cache would need to be repopulated.
* Fixed issue where viewing organs with inactive members resulted in an error.
* Fixed issue where viewing an album without start and/or end date resulted in an error.
* Fixed issue where execution of automated tasks was delayed.
* Updated CKEditor.

---

## [v2.8.6](https://github.com/GEWIS/gewisweb/tree/v2.8.6) (2022-08-30)

* Added a stricter Content Security Policy (CSP), mitigating multiple cross-site scripting (XSS) attacks.
* Changed `SuSOS` to `SudoSOS` and updated the associated links.
* Updated CKEditor.

---

## [v2.8.5](https://github.com/GEWIS/gewisweb/tree/v2.8.5) (2022-08-17)

* Added support for inactive organ members.
* Changed abbreviation of audit committee to `KCC`.
* Removed gender from members.
* Fixed issue where uploading meeting documents could fail.
* Fixed issue where text that was supposed to be localised was not actually being localised.
* Fixed issue where custom pages could be deleted without the required privileges.
* Fixed issue where images could be uploaded without the proper privileges.

---

## [v2.8.4](https://github.com/GEWIS/gewisweb/tree/v2.8.4) (2022-08-03)

* Fixed issue where an incorrect runtime configuration was used, resulting in reduced performance.
* Fixed issue where performing a password reset resulted in an error.
* Updated dependencies.

---


## [v2.8.3](https://github.com/GEWIS/gewisweb/tree/v2.8.3) (2022-07-25)

* Added a link to the Housing page in the useful information menu.
* Fixed issue where a photo could not be deleted if it was used as a profile photo.

---

## [v2.8.2](https://github.com/GEWIS/gewisweb/tree/v2.8.2) (2022-07-13)

* Updated membership types.
* Updated rate limit lockouts.
* Changed `chairman` to `chair`.
* Added additional ACL checks for `graduate` members, access to member information and photos is now limited.
* Improved logic to determine if a board member is currently a board member.
* Removed duplicate e-mail addresses for `User`s.
* Removed legacy login service.
* Removed legacy member API for REX.
* Fixed issue where using the authentication process for external applications failed if the membership type claim was used.
* Fixed issue where `Board` was incorrectly translated as a specific board.
* Fixed issue where the menu would not switch between English and Dutch when changing languages.
* Fixed issue where logging in was not possible due to improper rate limit lockout configuration.
* Fixed issue where a CSP violation in Chromium based browsers broke the initial authorization with external applications.
* Fixed issue where redirecting after a failed login would not redirect to the correct page.
* Fixed issue where board members would not have `admin` privileges in the first year of the association year.
* Fixed issue where viewing specific organ type without providing an organ abbreviation could result in an error.
* Fixed issue where using two trailing slashes could result in an error.
* Fixed issue where viewing a job category for a specific company without a job slug provided could result in an error.
* Updated dependencies.

---

## [v2.8.1](https://github.com/GEWIS/gewisweb/tree/v2.8.1) (2022-06-07)

* Added the link to an activity in the description of the Google Calendar event creation tool.
* Added all internal regulations to the members page.
* Added link to the Confidential Contact Person (CCP) page in the useful information menu.
* Improved retrieval of photo aspect ratios by calculating them on persistence instead of on-the-fly.
* Removed COVID-19 information for activities in the activity menu.
* Fixed issue where newly elected board members would already have `admin` privileges while their term had not started.
* Fixed issue where the date of a normal photo album would incorrectly be shown as just a year instead of the full date.
* Fixed issue where the tree navigation for pages and photo albums would not behave as expected.

---

## [v2.8](https://github.com/GEWIS/gewisweb/tree/v2.8) (2022-05-29)

* Added a pop-up in the photo viewer for Photos of the Week to shown when they were voted 'Photo of the Week'.
* Added membership type to profile page for administrative purposes.
* Added membership type to the activity admin sign-up list overview.
* Added more claims to the JWT authentication for external applications to use; `email`, `family_name`, `given_name`, `is_18_plus`, `membership_typ`, and `middle_name`.
* Added an overview of external application authentications to the user's profile.
* Improved external authentication process by showing which claims will be available to the external application and allowing the user to deny the authorization. If the user has not used an external application for more than 90 days, they will get a reminder of what information is shared with the external application when trying to authenticate.
* Improved the warning and confirmation process when a member tries to grant an authorisation to another member who already has received 2 or more authorizations.
* Improved the 'Photos of the Week' page by creating virtual albums for each association year.
* Improved performance of album pages by dynamically loading tags and voted status for each photo.
* Improved performance of album pages by not repeatedly performing ACL checks for viewing metadata.
* Fixed issue where the button the button to set a photo as your profile photo would always be shown, even when you were not tagged in a photo.
* Fixed issue where anonymous votes on polls were not preserved.
* Fixed issue where external participants of an activity could not be signed off.
* Fixed issue where thumbnails of photos with EXIF rotation would not be correctly rotated resulting in incorrect aspect ratios.
* Fixed issue where sharing an already shared image would incorrectly structure the URL, resulting in unexpected behaviour.
* Fixed issue where album pages would fail to load if there existed a tag that belonged to an old member.
* Fixed issue where organ names would be incorrectly capitalised.
* Fixed issue where long activity names could overflow the agenda panel of the frontpage.
* Fixed issue where viewing a non-existent organ could result in a crash.
* Fixed issue where albums with sub-albums that did not have a cover photo could not be viewed.
* Fixed issue where sub-albums could not be made full albums again.
* Fixed issue where meeting minutes were called meeting "notes".
* Fixed issue where the ordering of functions within an organ was wrong in English.
* Updated dependencies.

---

## [v2.7](https://github.com/GEWIS/gewisweb/tree/v2.7) (2022-03-27)

* Added a button to go from a photo in a member album to the actual album.
* Added automatic scrolling to the last viewed photo when you close the photo viewer.
* Added global error page to improve UX when an unrecoverable failure has occurred.
* Improved the ordering of the activity archive.
* Fixed issue where an error occurred while trying to view companies and jobs without a slug.
* Fixed issue where the album overview would not be accessible if an album did not have a cover photo.
* Fixed issue where an album could not be deleted if it did not have a cover photo.
* Fixed issue where it was impossible to type the letter `z` in the tag field.
* Fixed issue where using the arrow keys while tagging would inadvertently switch to the previous/next photo.
* Fixed issue where tag suggestions would not automatically have the first option selected.
* Fixed issue where a fully matching tag suggestion would not be automatically tagged.
* Fixed issue where tags would not be persisted in the DOM between PhotoSwipe sessions.
* Fixed issue where the user's identity was loaded for each photo in an album resulting in performance issues.
* Fixed issue where certain fonts and images were not cacheable.
* Fixed issue where Matomo was not accessible.
* Updated dependencies.

---

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
