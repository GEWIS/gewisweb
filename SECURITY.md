# Security Policy

## Discovering vulnerabilities

It is forbidden to do any testing for, scanning for, or exploitation of vulnerabilities on the [live website](https://gewis.nl).

The [wiki](https://github.com/GEWIS/gewisweb/wiki) contains sufficient guidance on how to set up your own deployment for such activities.

## Supported Versions

Only vulnerabilities in the most recently released version and in the version currently on the master branch are considered to be significant.
This does always include the currently deployed version (testing on the website is, however, not allowed).

If you discover a vulnerability in an earlier version that may still be present in the most recently released version, you can still report it.

## Supported Configuration

Only vulnerabilities that apply to the application running in `production` mode are considered significant.

## Reporting a Vulnerability

If you would like to report a vulnerability, mention this to [web@gewis.nl](mailto:web@gewis.nl).
From here on you will receive further instructions on how to proceed with reporting the vulnerability.

When providing further information in a secure manner, please include the requested information listed below (as much as you can provide) to help us better understand the nature and scope of the possible issue:
- Type of issue (e.g. buffer overflow, SQL injection, cross-site scripting, etc.)
- Full paths of source file(s) related to the manifestation of the issue
- The location of the affected source code (tag/branch/commit or direct URL)
- Any special configuration required to reproduce the issue
- Step-by-step instructions to reproduce the issue
- Proof-of-concept or exploit code (if possible)
- Impact of the issue, including how an attacker might exploit the issue

You will be informed when the vulnerability has been accepted or resolved.
Extra communications are possible but not guaranteed.

## Preferred Languages

The preferred language is English although Dutch is also acceptable.

## Financial Compensation

Financial compensation for reporting vulnerabilities is not in place.
