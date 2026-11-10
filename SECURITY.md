# Security policy

## Supported versions

Only the latest release of Épure receives security fixes. Épure supports Moodle 4.5 LTS to 5.3 and IOMAD 4.5 to 5.2.

## Reporting a vulnerability

Please do not open a public issue for a security problem. Report it privately with GitHub's private vulnerability
reporting: on this repository, **Security** › **Report a vulnerability**. Describe the version of Épure and of Moodle
or IOMAD, the steps to reproduce, and what an attacker could do.

You will get an answer within a week. Once fixed, the vulnerability is described in [CHANGES.md](CHANGES.md) with the
version that fixes it, and credited to you if you wish.

## What Épure does to stay safe

- Every parameter is read with `optional_param()` or `required_param()` and a type; every query is parameterised.
- Every action that changes something checks the session key (`require_sesskey()`) and the capability of the user.
- Texts written by administrators and IOMAD company managers are cleaned by Moodle (`format_text()`) before display.
- The documents checked by the accessibility guide are read with budgets against zip bombs, and their XML without
  network access (`LIBXML_NONET`).
- The page of verification of « My summary » (`/theme/epure/verify.php`, open without logging in) shows a document only
  to whoever has its code (about 59 bits of chance), and stops an address that tries 20 codes not found within ten
  minutes.
- The service worker of the web app covers the whole site on purpose: it only answers navigations whose network fails,
  with the « offline » page, and caches nothing else.
- No external service is called; no secret is stored in the code.
