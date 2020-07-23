#!/bin/bash
# php -l does not return an error code, just text on failure
rtn=$(php -l admin/class-webchangedetector-admin.php)
if [[ $rtn != *"No syntax errors detected"* ]];
then
echo $rtn;
exit 1 # exit with failure
fi

rtn=$(php -l admin/partials/webchangedetector-admin-display.php)
if [[ $rtn != *"No syntax errors detected"* ]];
then
echo $rtn;
exit 1 # exit with failure
fi

eslint admin/js/webchangedetector-admin.js # this will return 1 on error

# csslint admin/css/webchangedetector-admin.css # not done yet