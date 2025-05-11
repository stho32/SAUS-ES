---
description: Check the security of the internal application
---

This workflow audits every publicly reachable PHP entry point (pages and AJAX endpoints) and ensures that they validate the "master-code" session by calling `requireMasterLink()` from `php/includes/auth.php`. Any entry point that does not perform the check is reported.

### Requirements
This workflow is written for **Windows 10/11** and **PowerShell 7+** only. No external tooling is needed; every command uses built-in PowerShell cmdlets.

### Audit procedure
Follow these high-level steps—no specific scripts or external tools are required:

1. **Collect entry points**  
   Browse the `php/` directory (and its `php/api/` sub-folder) for all `.php` files **except** those inside `php/includes/` or `vendor/`. These files (pages and AJAX endpoints) are the publicly reachable entry points.

2. **Verify the guard**  
   A file is treated as **protected** if **any** of the following conditions is met:  
   • It calls `requireMasterLink();` directly (typically right after the strict-types declaration).  
   • It includes `php/includes/auth.php` ***and*** then calls `requireMasterLink();`.  
   • It includes `php/includes/auth_check.php` (or the correct relative variant such as `../includes/auth_check.php`). The helper loads `auth.php` and invokes the guard automatically, except for pages that are explicitly whitelisted in its `$publicPages` array (e.g. `error.php`, `logout.php`).  
   
   If none of these rules apply, the entry point is unprotected.

3. **Record issues**  
   List every entry point that fails the protection test above (no direct guard call *and* no inclusion of `auth_check.php`). Ignore files that are deliberately public and appear in the `$publicPages` list inside `auth_check.php`.  

4. **(Optional) API nuance**  
   For endpoints under `php/api/`, also confirm that a failed guard results in HTTP 401 (not a redirect) so XHR clients receive a proper error response.

5. **Remediate & re-audit**  
   For every file in your issue list, apply the fixes described below, then repeat steps 1-4 until no unprotected entry points remain.

### Remediation guidelines
* **UI pages** (`php/*.php`): include the auth module and call the guard immediately after the strict-types declaration.

  ```php
  <?php
  declare(strict_types=1);
  require_once __DIR__.'/includes/auth.php';
  requireMasterLink();
  ```

* **API endpoints** (`php/api/*.php`): call the guard and return HTTP 401 if it fails (redirects are unsuitable for XHR clients).

  ```php
  <?php
  declare(strict_types=1);
  require_once __DIR__.'/../includes/auth.php';
  if (!requireMasterLink()) {
      http_response_code(401);
      echo json_encode(['error' => 'Unauthorized']);
      exit;
  }
  ```

* **Internal CLI/cron scripts** may omit the guard if they are never exposed via HTTP.

Fix every file listed in `insecure.txt` by following the appropriate pattern above.