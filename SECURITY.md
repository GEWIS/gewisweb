```
-----BEGIN PGP SIGNED MESSAGE-----
Hash: SHA512

```
# Security Policy of GEWIS
_Version 3.0, 2 September 2025_

## Scope of this Policy

### Assets
This policy applies to the GEWIS applications below

 * GEWIS website: the web application on https://gewis.nl
https://github.com/GEWIS/gewisweb
 * GEWIS database system: the member management system on https://database.gewis.nl
https://github.com/GEWIS/gewisdb
 * GEWIS member join application: https://join.gewis.nl
 * GEWIS Point of Sales (SudoSOS): https://sudosos.gewis.nl
 * GEWIS LaTeX environment (GeTeX): https://latex.gewis.nl
https://github.com/GEWIS/GeTeX
This excludes vulnerabilities that can also be found in Overleaf. Those can be reported to security@overleaf.com
 * GEWIS CRM system (ParelPracht): https://parelpracht.gewis.nl
https://github.com/GEWIS/parelpracht
 * Other GEWIS websites: web application on domains ending in .gewis.nl

### Out of Scope
Applications that are meant for testing or development (e.g. those that can be found on \*.test.gewis.nl or \*.personal.gewis.nl domains) are excluded from this policy.

The following issues are considered out of scope:
* Missing Best Practice, Configuration or Policy Suggestions, including SSL/TLS configurations
* Social engineering attacks, phishing
* Vulnerabilities involving stolen credentials or physical access to a device
* Vulnerabilities on third-party libraries without showing specific impact to the target application (e.g. a CVE with no exploit)
* Login/logout CSRF
* User Enumeration

### Additional Rules
The following is not allowed:
* Do not attempt to conduct post-exploitation, including modification or destruction of data, and interruption or degradation of GEWIS services
* Do not attempt to perform brute-force attacks, denial-of-service attacks
* Do not compromise or test GEWIS accounts that are not your own. In collaboration, additional accounts for testing purposes can be provided
* If you encounter user information that is not your own in the course of your research, please stop and report this activity using the email address above so we can investigate. Please report to us what information was accessed and delete the data. Do not save, copy, transfer, or otherwise use this data. Continuing to access another person’s data may be regarded as evidence of a lack of good faith

## Reporting Vulnerabilities
Vulnerabilities can be sent to security@gewis.nl, preferably encrypted with the key that can be found on
https://pgp.surfnet.nl/pks/lookup?op=get&search=0x10C778679AEA14D0. 

For all submissions, please include the following (as much as you can provide) to help us better understand the nature and scope of the possible issue:

* Full description of the vulnerability being reported, including the exploitability and impact
    * Type of issue (e.g. buffer overflow, SQL injection, cross-site scripting, etc.)
    * Impact of the issue, including how an attacker might exploit the issue
    * (Full) paths of source file(s) related to the manifestation of the issue
(The location of the affected source code (tag/branch/commit or direct URL))
    * Any special configuration required to reproduce the issue
    * Step-by-step instructions to reproduce the issue
* If relevant, screenshots or videos
* If relevant, traffic logs
* If relevant, exploit code
* IP address(es) used during testing

You will be informed when the vulnerability has been accepted or resolved. Extra communications are possible but not guaranteed.

The preferred language is English, although Dutch is also acceptable.

## Rewards for Disclosures
Study Association GEWIS is a not-for-profit organisation, and as such, compensation for found vulnerabilities is not applicable. 

## Legal Safe Harbour
GEWIS will not bring any legal action against anyone who makes a good-faith effort to comply with program policies, or for any accidental or good-faith violation of this policy. 

As long as you comply with this policy, we waive any restrictions in any of the policies of GEWIS that would prohibit security research in accordance with the terms of this policy, for the limited purpose of your security research under this policy.

To protect your privacy, we will not, unless served with legal process or to address a violation of this policy:
* Share your personal information with third parties
* Share your research without your permission

## Signatures
This policy was signed with the aforementioned key before being published on [https://github.com/GEWIS/.github/blob/main/SECURITY.md](https://github.com/GEWIS/.github/blob/main/SECURITY.md).
The policy is being referred to on [https://gewis.nl/.well-known/security.txt](https://gewis.nl/.well-known/security.txt)

```
-----BEGIN PGP SIGNATURE-----

iHUEARYKAB0WIQSCXUrehl0qqJZIP2oQx3hnmuoU0AUCaLdhrAAKCRAQx3hnmuoU
0HIUAP9X5tF9DBTNJuGFqZ/0bdcuSsMCEngEcHW87BfRkkZZxAEA6ITbjFO4l5U6
GK1C/MRYLXMAkj9SX74B7KIWLMHvwgI=
=LEhx
-----END PGP SIGNATURE-----

```
