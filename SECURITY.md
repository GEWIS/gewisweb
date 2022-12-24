```
-----BEGIN PGP SIGNED MESSAGE-----
Hash: SHA256

```
# Security Policy of GEWIS
_Version 2.0, 25 October 2022_

## Scope of this Policy

### Assets
This policy applies to the GEWIS applications below

 * GEWIS website: the web application on https://gewis.nl<br/>https://github.com/GEWIS/gewisweb
 * GEWIS database system: the member management system on https://database.gewis.nl<br/>https://github.com/GEWIS/gewisdb
 * GEWIS member join application: https://join.gewis.nl
 * GEWIS Point of Sales (SudoSOS): https://sudosos.gewis.nl
 * GEWIS LaTeX environment (GeTeX): https://latex.gewis.nl<br/>https://github.com/GEWIS/GeTeX<br/>This excludes vulnerabilities that can also be found in Overleaf Those can be reported to security@overleaf.com
 * GEWIS CRM system (Parelpracht): https://parelpracht.gewis.nl<br/>https://github.com/GEWIS/parelpracht-client and https://github.com/GEWIS/parelpracht-server
 * Other GEWIS websites: web application on domains ending in .gewis.nl

### Out of Scope
Applications that are meant for testing or development (e.g. those that can be found on \*.test.gewis.nl or \*.personal.gewis.nl domains) are excluded from this policy.

The following issues are considered out of scope:
* Missing Best Practice, Configuration or Policy Suggestions including SSL/TLS configurations.
* Social engineering attacks, phishing
* Vulnerabilities involving stolen credentials or physical access to a device
* Vulnerabilities on third party libraries without showing specific impact to the target application (e.g. a CVE with no exploit)
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
https://pgp.surfnet.nl/pks/lookup?op=get&search=0x701F3551437E221A. 

For all submissions, please include the following (as much as you can provide) to help us better understand the nature and scope of the possible issue:

* Full description of the vulnerability being reported, including the exploitability and impact
	* Type of issue (e.g. buffer overflow, SQL injection, cross-site scripting, etc.)
	* Impact of the issue, including how an attacker might exploit the issue
	* (Full) paths of source file(s) related to the manifestation of the issue<br/>(The location of the affected source code (tag/branch/commit or direct URL))
	* Any special configuration required to reproduce the issue
	* Step-by-step instructions to reproduce the issue
* If relevant, screenshots or videos
* If relevant, traffic logs
* If relevant, exploit code
* IP address(es) used during testing

You will be informed when the vulnerability has been accepted or resolved. Extra communications are possible but not guaranteed.

The preferred language is English although Dutch is also acceptable.

## Rewards for Disclosures
Study Association GEWIS is a not-for-profit organization and as such compensation for found vulnerabilities is not applicable. 

## Legal Safe Harbor
GEWIS will not bring any legal action against anyone who makes a good faith effort to comply with program policies, or for any accidental or good faith violation of this policy. 

As long as you comply with this policy we waive any restrictions in any of the policies of GEWIS that would prohibit security resarch in accordance with the terms of, for the limited purpose of your security research under this policy.

To protect your privacy, we will not, unless served with legal process or to address a violation of this policy:
*   Share your personal information with third parties
*   Share your research without your permission

## Signatures
This policy was signed with the aforementioned key before being published on [https://github.com/GEWIS/GEWIS/README.MD](https://github.com/GEWIS/GEWIS/README.MD).
The policy is being referred to on [https://gewis.nl/.well-known/security.txt](https://gewis.nl/.well-known/security.txt)

```
-----BEGIN PGP SIGNATURE-----

iQIzBAEBCAAdFiEEmdeXXWSwTqSB7tHJfwZbKcXCCAcFAmOks4cACgkQfwZbKcXC
CAd1JA/+J6lA2jvMd235FXEToViWIS0TgfXax9nxclvBoQ6T86J6in3Va+4+BqAo
VLMy7mPWnxcMaSQwstDNb9RfNvUjfpq9qLXEUBRR/G79TYUrHP/XSVPJkrkH4EhT
iAFxo/Z/26Z5HJxF3U00liAIV28K20qxGiNpQrgef1LU4LFfEo+x6JL8sgCXLoAB
2i3NX4QLlrVUL46HxymIiNYfR9PKuue6Hc1AUr1NWWEj6Apge/kTPTS0xKLEy1fH
GZsD4y8udpGa81NOFz7Ea/OjEA3OIxiGrTzrJ1YkdRr2Fk10ZqYQqKOayvPx7/aA
+qEWuYrSZmIoFL/OG7yt8+m+jkk7aAp0BYtWmnhnbJoFRnWgzDG+YvSgfYZK2c1x
M3PRXFwJyg8ROWjKIwtXSkWiLrnzwy2KDFjA4xK+w7W2/Pm9ylvb3L/cBcOGN3DZ
tiOf54YRpRNx//r6AL27X6BwnyeXNTwc1j2q45K2Xr3LekQgDBtXQ4QkcJe0HklG
aqx/w3rJZufAsiHgY4xHX+uWY5wofqhIOr8pTZaF7iC9K3veFCdcHWMih/Hk7IyK
atf8FXlsv3mdAT2KM2L+idIuPHthtbXXnAF8un1iKoq1nVGwqHhVRCMBiYUz96bF
0cDOi7SSH7K/qBIagSvv6iG7TGij24sh9H4vp8SEytPUXb/Ry2Y=
=TCLv
-----END PGP SIGNATURE-----

```

