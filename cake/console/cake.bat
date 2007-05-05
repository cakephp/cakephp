:: In order for this script to work as intended, the cake\scripts\ folder must be in your PATH

@echo.
@echo off

SET app=%0
SET lib=%~dp0

php -q %lib%cake.php %* -working "%CD%\"

echo.