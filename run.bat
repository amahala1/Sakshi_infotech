@echo off
title Sakshi Infotech - Computer & Stationery E-Commerce Platform
echo ======================================================================
echo    SAKSHI INFOTECH - COMPUTER HARDWARE & OFFICE STATIONERY STORE
echo    E-Commerce Portal with Cryptographic Hash Sealed Database & Multi-Role
echo ======================================================================
echo.
echo Starting local PHP development server on http://localhost:8080 ...
echo Press Ctrl+C to stop the server anytime.
echo.
echo Demo Credentials:
echo   Super Admin:    admin      / admin123
echo   Accounts Staff: staff_acc  / accounts123
echo   Order Checker:  staff_chk  / checker123
echo   Dispatch Staff: staff_dsp  / dispatch123
echo   Customer:       customer1  / customer123
echo.
set PATH=C:\Users\ac\php;%PATH%
php -S localhost:8080 -t public
pause
