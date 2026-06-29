@echo off
REM ============================================================
REM Deploy Script for Sales Rep Assignment Fix
REM ============================================================

echo.
echo ╔══════════════════════════════════════════════════════════╗
echo ║     Sales Rep Assignment Fix - Deployment Script        ║
echo ╚══════════════════════════════════════════════════════════╝
echo.

echo Step 1: Staging files...
git add api/projects/assigned.php
git add static/css/projects.css
git add check-assignments.php
git add fix-sales-tracking-sync.php
git add SALES_REP_FIX_DEPLOYMENT.md
git add GAWIN_MO_TO.txt

echo.
echo Step 2: Committing changes...
git commit -m "Fix: Sales rep assignment filtering and modal width" ^
           -m "" ^
           -m "Changes:" ^
           -m "- Fixed api/projects/assigned.php JOIN to match sales_rep_id" ^
           -m "- Updated modal width to full screen (projects.css)" ^
           -m "- Added diagnostic script (check-assignments.php)" ^
           -m "- Added auto-fix script (fix-sales-tracking-sync.php)" ^
           -m "" ^
           -m "Issue: Projects assigned to Dennis appearing in Melody's account" ^
           -m "Cause: Mismatch between projects.assigned_to and sales_tracking.sales_rep_id" ^
           -m "Fix: Added AND st.sales_rep_id = p.assigned_to to JOIN condition"

echo.
echo Step 3: Pushing to remote...
git push

echo.
echo ╔══════════════════════════════════════════════════════════╗
echo ║                    Deployment Complete!                  ║
echo ╚══════════════════════════════════════════════════════════╝
echo.
echo Next steps on PRODUCTION SERVER:
echo.
echo 1. SSH to server:
echo    ssh user@production-server
echo.
echo 2. Pull changes:
echo    cd /var/www/html/datas
echo    git pull
echo.
echo 3. Run diagnostic:
echo    php check-assignments.php
echo.
echo 4. Fix data mismatches:
echo    php fix-sales-tracking-sync.php
echo.
echo 5. Test login as:
echo    - Melody Nool (cmnool@tdtpowersteel.com.ph)
echo    - Dennis Espinar (despinar@tdtpowersteel.com.ph)
echo.
echo 6. Hard refresh browsers (Ctrl+F5)
echo.
echo See SALES_REP_FIX_DEPLOYMENT.md for full instructions
echo.
pause
