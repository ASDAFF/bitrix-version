<?
$MESS["SECURITY_SITE_CHECKER_EnvironmentTest_NAME"] = "Environment check";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_TMP"] = "PHP temp directory is available to anyone";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_TMP_DETAIL"] = "In theory, an attacker can use the temp folder to view the uploaded files.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_TMP_RECOMMENDATION"] = "Configure access permissions for the temp directory.";
$MESS["SECURITY_SITE_CHECKER_SESSION"] = "Session storage directory is available to anyone";
$MESS["SECURITY_SITE_CHECKER_SESSION_DETAIL"] = "This may compromise your project completely.";
$MESS["SECURITY_SITE_CHECKER_SESSION_RECOMMENDATION"] = "Configure access permissions for this directory.";
$MESS["SECURITY_SITE_CHECKER_COLLECTIVE_SESSION"] = "The session storage directory contains sessions of different projects.";
$MESS["SECURITY_SITE_CHECKER_COLLECTIVE_SESSION_DETAIL"] = "A situation may happen when this compromises your project completely.";
$MESS["SECURITY_SITE_CHECKER_COLLECTIVE_SESSION_RECOMMENDATION"] = "Use an individual storage for each project.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PHP"] = "PHP scripts are executed in the uploaded files directory.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PHP_DETAIL"] = "Sometimes developers don't pay enough attention to proper file name filters. An attacker may exploit this vulnerability to take full control of your  project.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PHP_RECOMMENDATION"] = "Configure your web server correctly.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PHP_DOUBLE"] = "PHP scripts with the double extension (e.g. php.lala) are executed in the uploaded files directory.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PHP_DOUBLE_DETAIL"] = "Sometimes developers don't pay enough attention to proper file name filters. An attacker may exploit this vulnerability to take full control of your  project.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PHP_DOUBLE_RECOMMENDATION"] = "Configure your web server correctly.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PY"] = "Python scripts are executed in the uploaded files directory.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PY_DETAIL"] = "Sometimes developers don't pay enough attention to proper file name filters. An attacker may exploit this vulnerability to take full control of your  project.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PY_RECOMMENDATION"] = "Configure your web server correctly.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_HTACCESS"] = "Apache must not process the .htaccess files in the uploaded files directory";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_HTACCESS_DETAIL"] = "Sometimes developers don't pay enough attention to proper file name filters. An attacker may exploit this vulnerability to take full control of your  project.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_HTACCESS_RECOMMENDATION"] = "Configure your web server correctly.";
?>