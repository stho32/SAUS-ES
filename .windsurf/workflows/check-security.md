---
description: Check the security of the internal application
---

This workflow audits every publicly reachable PHP entry point (pages and AJAX endpoints) and ensures that they validate the "master-code" session by calling `requireMasterLink()` from `php/includes/auth.php`. Any entry point that does not perform the check is reported.

### Prerequisites
This workflow is written for **Windows 10/11** using **PowerShell 7+**.

Install the single extra dependency, **ripgrep**, with one of these commands (run once):

```powershell
# Chocolatey
choco install ripgrep -y

# – or –

# Scoop
scoop install ripgrep
```

After installation make sure `rg.exe` is in `%PATH%` (restart the shell if required).

### Steps
1. Identify all PHP entry points (exclude libraries and includes):

   ```powershell
   # Move to the project root first (adjust if necessary)
   Set-Location "C:/Projekte/SAUS-ES"

   # Create a list of PHP files that can be requested directly
   rg -l --iglob "!php/includes/*" --iglob "!vendor/*" --type php "" php > entrypoints.txt
   ```

2. Detect missing security checks:

   ```powershell
   # List files that do NOT contain the call to requireMasterLink()
   rg -L "requireMasterLink" -f entrypoints.txt > insecure.txt
   ```

3. (Optional) Double-check inclusion of the auth module:

   ```powershell
   rg -L "includes/auth.php" -f insecure.txt >> insecure.txt
   ```

4. Review results:

   ```powershell
   if (Test-Path insecure.txt -and (Get-Content insecure.txt).Length -gt 0) {
       Write-Host "Unprotected entry points:" -ForegroundColor Yellow
       Get-Content insecure.txt
   } else {
       Write-Host 'All entry points secured.' -ForegroundColor Green
   }
   ```

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
