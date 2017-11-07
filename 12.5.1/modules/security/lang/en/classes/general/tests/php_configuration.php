<?
$MESS["SECURITY_SITE_CHECKER_PhpConfigurationTest_NAME"] = "PHP settings check";
$MESS["SECURITY_SITE_CHECKER_PHP_ENTROPY"] = "No additional entropy source for session ID is defined";
$MESS["SECURITY_SITE_CHECKER_PHP_ENTROPY_DETAIL"] = "The lack of additional entropy may be used to predict random numbers and session ID's.";
$MESS["SECURITY_SITE_CHECKER_PHP_ENTROPY_RECOMMENDATION"] = "Add the following line to the PHP settings:<br>session.entropy_file = /dev/urandom<br>session.entropy_length = 128";
$MESS["SECURITY_SITE_CHECKER_PHP_INCLUDE"] = "URL wrappers are enabled";
$MESS["SECURITY_SITE_CHECKER_PHP_INCLUDE_DETAIL"] = "This option is absolutely not recommended.";
$MESS["SECURITY_SITE_CHECKER_PHP_INCLUDE_RECOMMENDATION"] = "Add or edit the following line in the PHP settings:<br>allow_url_include = Off";
$MESS["SECURITY_SITE_CHECKER_PHP_FOPEN"] = "Read access for URL wrappers is enabled";
$MESS["SECURITY_SITE_CHECKER_PHP_FOPEN_DETAIL"] = "This option is not required, but may possibly be used by an attacker.";
$MESS["SECURITY_SITE_CHECKER_PHP_FOPEN_RECOMMENDATION"] = "Add or edit the following line in the PHP settings:<br>allow_url_fopen = Off";
$MESS["SECURITY_SITE_CHECKER_PHP_ASP"] = "ASP style tags are enabled";
$MESS["SECURITY_SITE_CHECKER_PHP_ASP_DETAIL"] = "Only a few developers know that this option exists. This option is redundant.";
$MESS["SECURITY_SITE_CHECKER_PHP_ASP_RECOMMENDATION"] = "Add or edit the following line in the PHP settings:<br>asp_tags = Off";
?>