---
description: Check the security of the internal application
---

Check the security of the application in the php-folder.

The application should be secured by a central login mechanism, the "master-code", so all pages and ajax requests should check if someone is logged on correctly. 

Otherwise the pages should either send a http 401 or show an error message instead of the content.
The majority of the pages should already check this correctly, but a recent research showed that some pages omit the security check. 

Please enhance .windsurf\workflows\check_security.md with information about how a correct security check should be implemented on the different types of php entry points, derived from the current implementation (you may remove this paragraph then).

