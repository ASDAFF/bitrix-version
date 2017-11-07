<?
$MESS["SC_TITLE"] = "Site Check";
$MESS["SC_NOTE"] = "The values shown in <font color=\"#009900\"><b>green</b></font> meet the system requirements.<br>
The values shown in <font color=\"#FF0000\"><b>red</b></font> <u><b>don't</b></u> meet the system requirements.<br>
The values shown in <font color=\"#000000\"><b>black</b></font> may require your attention.<br>
<br>
If authorization does not function properly, you can verify the website without authentication. To do so, create an empty file <b>#VAL#</b>. Then you can open <b>/bitrix/admin/site_checker.php</b> page without authorization.
<br><b>Attention!</b> Always delete the file <b>site_checker_debug</b> once the website check is complete.
";
$MESS["SC_SUBTITLE_REQUIED"] = "Mandatory system parameters";
$MESS["SC_T_BACKTRACK_LIMIT"] = "pcre.backtrack_limit parameter value";
$MESS["SC_SUBTITLE_REQUIED_DESC"] = "Your system should meet the mandatory conditions. If any parameter are being displayed in red, you need to check and fix it. Otherwise, proper functioning of the site is not guaranteed.";
$MESS["SC_SUBTITLE_DISK"] = "Checking the disk access";
$MESS["SC_SUBTITLE_DISK_DESC"] = "The site scripts must have write access to site files. This is required for proper functioning of the file manager, file upload and the update system that is used to keep the site kernel up-to-date.";
$MESS["SC_VER_ERR"] = "The PHP version is #CUR#, but #REQ# or higher is required.";
$MESS["SC_MOD_XML"] = "XML support";
$MESS["SC_MOD_PERL_REG"] = "Regular Expression support (Perl-Compatible)";
$MESS["SC_MOD_GD"] = "GD Library";
$MESS["SC_MOD_GD_JPEG"] = "GD JPEG support";
$MESS["SC_UPDATE_ACCESS"] = "Access to update server";
$MESS["SC_UPDATE_ERROR"] = "Not connected to update server";
$MESS["SC_TMP_FOLDER_PERMS"] = "Insufficient permission to write to temporary folder.";
$MESS["SC_NO_TMP_FOLDER"] = "Temporary folder does not exist.";
$MESS["ERR_NO_MODS"] = "The required extensions are not installed:";
$MESS["SC_CYR_SYMBOLS_RU_ONLY"] = "a-zA-Z";
$MESS["ERR_NO_SSL"] = "SSL support is not enabled for PHP";
$MESS["SC_SUBTITLE_SITE_MODULES"] = "Site modules";
$MESS["SC_RUS_L1"] = "Site ticket";
$MESS["SC_TIK_SEND_SUCCESS"] = "The message has been sent successfully. Please check your inbox #EMAIL# after some time for confirmation of the message receipt from the technical support system.";
$MESS["SC_TIK_TITLE"] = "Send message to the technical support system";
$MESS["SC_TIK_DESCR"] = "Problem description";
$MESS["SC_TIK_DESCR_DESCR"] = "sequence of operations that caused the error, error description,...";
$MESS["SC_TIK_LAST_ERROR"] = "Last error text";
$MESS["SC_TIK_LAST_ERROR_ADD"] = "attached";
$MESS["SC_TIK_SEND_MESS"] = "Send message";
$MESS["SC_TIK_HELP_BACK"] = "Back...";
$MESS["SC_TAB_2"] = "Access check";
$MESS["SC_TAB_4"] = "Modules";
$MESS["SC_TAB_5"] = "Technical support";
$MESS["SC_ERROR"] = "Error";
$MESS["SC_CHECK_FILES"] = "Check file permissions";
$MESS["SC_CHECK_FILES_WARNING"] = "File permissions check script can generate a large load on the server.";
$MESS["SC_CHECK_FILES_ATTENTION"] = "Attention!";
$MESS["SC_TEST_CONFIG"] = "Configuration Check";
$MESS["SC_TESTING"] = "Now checking...";
$MESS["SC_FILES_CHECKED"] = "Files checked: <b>#NUM#</b><br>Current path: <i>#PATH#</i>";
$MESS["SC_FILES_OK"] = "All the files checked are available for reading and writing.";
$MESS["SC_FILES_FAIL"] = "Unavailable for reading or writing (first 10):";
$MESS["SC_SITE_CHARSET_FAIL"] = "Mixed encodings: UTF-8 and non UTF-8";
$MESS["SC_PATH_FAIL_SET"] = "The website root path must be empty, the current path is:";
$MESS["SC_NO_ROOT_ACCESS"] = "Cannot access the folder ";
$MESS["SC_SOCKET_F"] = "Socket Support";
$MESS["SC_CHECK_FULL"] = "Full Check";
$MESS["SC_CHECK_UPLOAD"] = "Upload Folder Check";
$MESS["SC_CHECK_KERNEL"] = "Kernel Check";
$MESS["SC_CHECK_FOLDER"] = "Folder Check";
$MESS["SC_CHECK_B"] = "Check";
$MESS["SC_STOP_B"] = "Stop";
$MESS["SC_TEST_NAME"] = "Test Name";
$MESS["SC_TEST_RES"] = "Result";
$MESS["SC_TEST_FAIL"] = "Invalid server response. Test cannot be completed.";
$MESS["SC_START_TEST_B"] = "Start Test";
$MESS["SC_STOP_TEST_B"] = "Stop";
$MESS["SC_COMMENT"] = "<b>Note:</b> possible problems revealed by the tests relate to the server configuration. You should contact your hosting company to resolve them.";
$MESS["SC_T_LOG"] = "Creating the log file";
$MESS["SC_T_SOCK"] = "Using sockets";
$MESS["SC_T_UPLOAD"] = "File upload";
$MESS["SC_T_UPLOAD_BIG"] = "Upload files over 4MB";
$MESS["SC_T_POST"] = "POST requests with many parameters";
$MESS["SC_T_PRECISION"] = "\"precision\" parameter value (no less than 10)";
$MESS["SC_T_MAIL"] = "E-mail sending";
$MESS["SC_T_MAIL_BIG"] = "Large e-mail sending (over 64 KB)";
$MESS["SC_T_MAIL_B_EVENT"] = "Check for unsent messages";
$MESS["SC_T_MAIL_B_EVENT_ERR"] = "Errors occurred while sending system e-mail messages. Messages not sent:";
$MESS["SC_T_REDIRECT"] = "Local redirects (LocalRedirect function)";
$MESS["SC_T_MEMORY"] = "Memory limit";
$MESS["SC_T_SESS"] = "Session retention";
$MESS["SC_T_SESS_UA"] = "Session retention without UserAgent";
$MESS["SC_T_CACHE"] = "Using cache files";
$MESS["SC_T_AUTH"] = "HTTP authorization";
$MESS["SC_T_EXEC"] = "File creation and execution";
$MESS["SC_T_SUHOSIN"] = "The suhosin module is disabled";
$MESS["SC_T_BX_CRONTAB"] = "The BX_CRONTAB constant (must not be defined)";
$MESS["SC_T_SECURITY"] = "The Apache mod_security module is disabled";
$MESS["SC_T_DELIMITER"] = "Fractional part delimiter";
$MESS["SC_T_DBCONN"] = "Redundant output in configuration files";
$MESS["SC_T_MYSQL_VER"] = "MySQL version";
$MESS["SC_T_TIME"] = "Database and web server times";
$MESS["SC_T_SQL_MODE"] = "MySQL Mode";
$MESS["SC_T_AUTOINC"] = "MySQL auto_increment value";
$MESS["SC_T_CHARSET"] = "Database table charset";
$MESS["SC_DB_CHARSET"] = "Database charset";
$MESS["SC_MBSTRING_NA"] = "Verification failed due to UTF configuration errors";
$MESS["SC_CONNECTION_CHARSET"] = "Connection charset";
$MESS["SC_TABLES_NEED_REPAIR"] = "Table integrity damaged, they need to be fixed.";
$MESS["SC_TABLE_ERR"] = "Error in table #VAL#:";
$MESS["SC_T_CHECK"] = "Table Check";
$MESS["SC_TEST_SUCCESS"] = "Success";
$MESS["SC_LOG_OK"] = "Log file created:";
$MESS["SC_F_OPEN"] = "Open";
$MESS["SC_SENT"] = "Sent on:";
$MESS["SC_SEC"] = "sec.";
$MESS["SC_DB_ERR"] = "Problem database version:";
$MESS["SC_DB_ERR_MODE"] = "The sql_mode variable in MySQL must be empty. Current value:";
$MESS["SC_DOCROOT_FAIL"] = "The paths in __FILE__ (#DIR0#) and _SERVER[DOCUMENT_ROOT] (#DIR1#) do not match.";
$MESS["SC_NO_PROXY"] = "Cannot connect to the proxy server.";
$MESS["SC_PROXY_ERR_RESP"] = "Invalid proxy assisted update server response.";
$MESS["SC_UPDATE_ERR_RESP"] = "Invalid update server response.";
$MESS["SC_FILE_EXISTS"] = "File exists:";
$MESS["SC_WARN_SUHOSIN"] = "The suhosin module loaded, some Control Panel problems may arise (suhosin.simulation=#VAL#).";
$MESS["SC_WARN_SECURITY"] = "The mod_security module loaded, some Control Panel problems may arise.";
$MESS["SC_DELIMITER_ERR"] = "Current delimiter: &quot;#VAL#&quot;, &quot;.&quot; is required.";
$MESS["SC_DB_MISC_CHARSET"] = "The table #TBL# charset (#T_CHAR#) does not match the database charset (#CHARSET#).";
$MESS["SC_COLLATE_WARN"] = "The collation value for &quot;#TABLE#&quot; (#VAL0#) differs from the database value (#VAL1#).";
$MESS["SC_TABLE_CHARSET_WARN"] = "The &quot;#TABLE#&quot; table contains fields in encoding not matching the database encoding.";
$MESS["SC_FIELDS_COLLATE_WARN"] = "The field &quot;#FIELD#&quot; result in the table &quot;#TABLE#&quot;  (#VAL1#) does not match that in the database (#VAL1#).";
$MESS["SC_TABLE_SIZE_WARN"] = "The size of the &quot;#TABLE#&quot; table is possibly too large (#SIZE# M).";
$MESS["SC_NOT_LESS"] = "Not less than #VAL# M.";
$MESS["SC_TIK_ADD_TEST"] = "Send Test Log";
$MESS["SC_SUPPORT_COMMENT"] = "If you have problems sending the message, please use the contact form at our site:";
$MESS["SC_NOT_FILLED"] = "The problem description is required.";
$MESS["SC_TEST_WARN"] = "The server configuration report is about to be collected.\\r\\nIf an error occurs, please uncheck the \"Send Test Log\" option and try again.";
$MESS["SC_SOCK_NA"] = "Verification failed due to socket error.";
$MESS["SC_T_CLONE"] = "Passing objects by reference";
$MESS["SC_T_GETIMAGESIZE"] = "getimagesize support for SWF";
$MESS["SC_TEST_DOMAIN_VALID"] = "The current domain is invalid (#VAL#). The domain name can only contain numbers, Latin letters and hyphens. The first domain level must be separated with a period (e.g. .com).";
$MESS["SC_SWF_WARN"] = "SWF objects may not run.";
$MESS["SC_TIME_DIFF"] = "The time is off by #VAL# seconds.";
$MESS["SC_T_MODULES"] = "Required PHP Modules";
$MESS["SC_MOD_MBSTRING"] = "mbstring support";
$MESS["SC_MB_UTF"] = "The website runs in UTF encoding";
$MESS["SC_MB_NOT_UTF"] = "The website runs in single byte encoding";
$MESS["SC_MB_CUR_SETTINGS"] = "mbstring parameters:";
$MESS["SC_MB_REQ_SETTINGS"] = "required:";
$MESS["SC_T_MBSTRING"] = "UTF configuration parameters (mbstring and BX_UTF)";
$MESS["SC_T_SITES"] = "Website Parameters";
$MESS["SC_BX_UTF"] = "Use the following code in <i>/bitrix/php_interface/dbconn.php</i>:
<code>define('BX_UTF', true);</code> ";
$MESS["SC_BX_UTF_DISABLE"] = "The BX_UTF constant must not be defined";
$MESS["SC_T_PHP"] = "PHP Required Parameters";
$MESS["SC_ERR_PHP_PARAM"] = "The parameter #PARAM# is #CUR#, but #REQ# is required.";
$MESS["SC_MYSQL_ERR_VER"] = "MySQL #CUR# is currently installed, but #REQ# is required.";
$MESS["SC_T_SERVER"] = "Server Variables";
$MESS["SC_MORE"] = "more";
$MESS["SC_CONNECTION_CHARSET_WRONG"] = "The database connection charset must be #VAL#, the current value is #VAL1#.";
$MESS["SC_CONNECTION_CHARSET_WRONG_NOT_UTF"] = "The database connection charset must not be UTF-8, the current value is: #VAL#.";
$MESS["SC_CONNECTION_COLLATION_WRONG_UTF"] = "The database connection collation must be utf8_unicode_ci, the current value is #VAL#.";
$MESS["SC_TABLE_CHECK_NA"] = "Verification failed due to database charset error.";
$MESS["SC_FIX"] = "Fix";
$MESS["SC_FIX_DATABASE"] = "Fix Database Errors";
$MESS["SC_FIX_DATABASE_CONFIRM"] = "The system will now attempt to fix database errors. This action is potentially dangerous. Create the database backup copy before you proceed.\\n\\nContinue?";
$MESS["SC_CHECK_TABLES_ERRORS"] = "Database tables have #VAL# encoding error(s), #VAL1# of which can be fixed automatically.";
$MESS["SC_CONNECTION_CHARSET_NA"] = "Verification failed due to connection encoding error.";
$MESS["SC_DATABASE_COLLATION_DIFF"] = "The database collation (#VAL1#) does not match the connection collation (#VAL0#).";
$MESS["SC_DATABASE_CHARSET_DIFF"] = "The database charset (#VAL1#) does not match the connection charset (#VAL0#).";
$MESS["SC_HELP_NOTOPIC"] = "Sorry, no help on this topic.";
$MESS["SC_HELP_OPENLOG"] = "This will create a text file with the unique name like site_checker_e45a34e4bf940ef3d78b2493cd56cc47.log in the /bitrix folder. Such names wil prevent the file from being downloaded by third parties. The system will save in this file the test results and extra debugging information.

If no such file can be created, check the system permissions on the <b>Disk Access Check</b> tab. If you cannot add the necessary permissions, contact your hosting service techsupport or administrator. You cannot set the operating system defined permissions in Bitrix Control Panel.";
$MESS["SC_HELP_CHECK_PHP_MODULES"] = "This will check for the PHP extensions required by the system. If there are missing extensions, shows the modules that cannot run without these extensions.

To add missing PHP extensions, contact your hosting techsupport. If you run the system at a local machine, you will have to install them manually; refer to documentation available at php.net.";
$MESS["SC_HELP_CHECK_PHP_SETTINGS"] = "This will check for the critical parameters defined in php.ini. Shows the parameters whose values will cause system malfunction. You will find the detailed parameter description at php.net.";
$MESS["SC_HELP_CHECK_SERVER_VARS"] = "This will check the server variables.

The value of HTTP_HOST is derived from the current virtual host (domain). Some browsers cannot save cookies for invalid domain names, which will cause cookie authorization failure.";
$MESS["SC_HELP_CHECK_MBSTRING"] = "The mbstring module is required for internationalization support. The module is very strict as to setting the correct parameters depending on the current website encoding: the parameters for UTF-8 encoding are different from those of any national charset (e.g. cp1252).

The following parameters are mandatory for UTF-8 based websites:
<b>mbstring.func_overload=2</b>
<b>mbstring.internal_encoding=utf-8</b>

The first parameter implicitly redirects PHP string functions calls to mbstring functions. The second parameter defines the text encoding.

If your website does not use UTF-8, the first parameter must be zero:
<b>mbstring.func_overload=0</b>

If you cannot disable function redirection for some reason, try using a single-byte encoding:
<b>mbstring.func_overload=2</b>
<b>mbstring.internal_encoding=latin1</b>

If the assigned values does not match the website parameters, you will encounter weird and bizarre errors like truncated words, broken XML import etc.

<b>Remember</b> that the <b>mbstring.func_overload</b> parameter is defined in the global php.ini (or in httpd.conf for a virtual server), while the encoding parameter sits in .htaccess.

All the Bitrix modules use the <i>BX_UTF</I> constant to resolve the current encoding. A UTF-8 website requires the following code in <i>/bitrix/php_interface/dbconn.php</i>:
<code>define('BX_UTF', true);</code>
";
$MESS["SC_HELP_CHECK_SITES"] = "Verifies general multisite parameters. If a website specifies the root directory path (which is required only for websites existing on different domains), that directory must contain a symbolic link to writable \"bitrix\" folder.

All the websites that share the same Bitrix system instance must use the same encoding: either UTF-8 or single byte.";
$MESS["SC_HELP_CHECK_SOCKET"] = "This will set the web server to establish a connection to itself which is required to verify networking functions and for other subsequent tests.

If this test fails, the subsequent tests requiring a child PHP process cannot be performed. This problem is usually caused by a firewall, restricted  IP access or HTTP/NTLM authorization. Disable these functions while performing the test.";
$MESS["SC_HELP_CHECK_DBCONN"] = "This will check the text output in the configuration files <i>dbconn.php</i> and <i>init.php</i>.

Even an excess space or newline may cause a compressed page to be unpackable and unreadable by a client browser.

Besides, authorizations and CAPTCHA problems may occur.";
$MESS["SC_HELP_CHECK_UPLOAD"] = "This will attempt to connect to the web server and send a chunk of binary data as a file. The server will then compare the received data with the original sample. If a problem arises, it may be caused by some parameter in <i>php.ini</I> prohibiting binary data transfer, or by inaccessible temporary folder (or <i>/bitrix/tmp</i>).

Should the problem appear, contact your hosting provider. If you are running the system at a local machine, you will have to configure the server manually.";
$MESS["SC_HELP_CHECK_UPLOAD_BIG"] = "This will upload a large binary file (over 4MB). If this test fails while the previous one succeeds, the problem may be the limit in php.ini (<b>post_max_size</b> or <b>upload_max_filesize</b>). Use phpinfo to get the current values (Settings - Tools - PHP Settings).

Insufficient disk space may cause this problem as well.";
$MESS["SC_HELP_CHECK_POST"] = "This will send a POST request with a large number of parameters. Some server protector software like \"suhosin\" may block verbose requests. This may prevent information block elements from being saved which is definitely a problem.";
$MESS["SC_HELP_CHECK_MAIL"] = "This will send an e-mail message to hosting_test@bitrixsoft.com using the standard PHP function \"mail\". A special mailbox exists to make the test conditions as real-life as possible.

This test sends the site check script as a test message and <b>never sends any user data</b>.

Note that the test does not verify the message delivery. Delivery to other mailboxes cannot be verified as well.

If the e-mail sending time exceeds one second, the server performance may experience severe degradation. Contact your hosting techsupport so that they configure delayed e-mail sending using spooler.

Alternatively, you can use cron to send the e-mails. To do so, add <code>define('BX_CRONTAB_SUPPORT', true);</code> to dbconn.php. Then, set cron to execute <i>php /var/www/bitrix/modules/main/tools/cron_events.php</I> every minute (replace <i>/var/www</i> with your website root).

If the call to mail() has failed, you cannot send e-mail from your server using conventional methods.

If your hosting provider offers alternative e-mail sending services, you can use them by calling the function \"custom_mail\". Define this function in <i>/bitrix/php_interface/dbconn.php</I>. If the system find this function definition, it will use the latter instead of PHP's \"mail\" with the same input parameters.";
$MESS["SC_HELP_CHECK_MAIL_BIG"] = "This will test bulk e-mails by sending the same message as in the previous text (the site check script) 10 times. Additionally, a newline character is inserted into the message subject, and the message is BCC'ed to noreply@bitrixsoft.com.

Such messages may not send if the server is configured incorrectly.

Should any problem appear, contact your hosting provider. If you are running the system at a local machine, you will have to configure the server manually.";
$MESS["SC_HELP_CHECK_MAIL_B_EVENT"] = "The database table B_EVENT stores the website's e-mail queue and logs the e-mail sending events. If some of the messages failed to be sent, possible reasons are invalid recipient address, incorrect e-mail template parameters or the server's e-mail subsystem.";
$MESS["SC_HELP_CHECK_LOCALREDIRECT"] = "After a Control Panel's form is saved (that is, a user clicked Save or Apply), the client is redirected to an initial page. This is done to prevent repeated form posts which may occur if a user refreshes the page. A redirect will only succeed if some crucial variables are defined correctly on a web server, and HTTP header rewrite is allowed.

If some of the server variables are redefined in <i>dbconn.php</i>, the test will use that redefinitions. In other words, redirect fully simulate the real life situations.";
$MESS["SC_HELP_CHECK_MEMORY_LIMIT"] = "This test creates an isolated PHP process to generate a variable whose size is incremented gradually. In the end, this will produce the amount of memory available to the PHP process.

PHP defines the memory limit in php.ini by setting the <b>memory_limit</b> parameter. However, this may be overridden on shared hostings. You should not trust this parameter.

The test attempts to increase the value of <b>memory_limit</b> using the code:
<code>ini_set(&quot;memory_limit&quot;, &quot;512M&quot;)</code>

If the current value is less than that, add this line of code to <i>/bitrix/php_interface/dbconn.php</i>.
";
$MESS["SC_HELP_CHECK_SESSION"] = "This will check if the server is capable of storing data using sessions. This is required to preserve authorization between hits.

This test will fail if no session support is installed on the server, an invalid session directory is specified in php.ini or if this directory is read-only.";
$MESS["SC_HELP_CHECK_SESSION_UA"] = "This will also test the session storage capability, but without setting the <i>User-Agent</i> HTTP header.

Many external applications and add-ons don't set this header: file and photo uploaders, WebDav clients etc.

If the test fails, the most likely problem is incorrect configuration of the <b>suhosin</b> PHP module.";
$MESS["SC_HELP_CHECK_CACHE"] = "This will check if a PHP process can create a <b>.tmp</b> file in the cache directory and then rename it to <b>.php</b>. Some Windows web server may fail to rename the file if the user permissions are configured incorrectly.";
$MESS["SC_HELP_CHECK_UPDATE"] = "This will try to establish a test connection to the update server using the Kernel module current settings. If the connection cannot be established, you will not be able to install updates or activate the trial version.

The most common reasons are incorrect proxy settings, firewall restrictions or invalid server networking parameters.";
$MESS["SC_HELP_CHECK_HTTP_AUTH"] = "This test will send the authorization data using the HTTP headers and then attempt to resolve the data using the REMOTE_USER server variable (or REDIRECT_REMOTE_USER). HTTP authorization is required for integration with third-party software.

If PHP runs in CGI/FastCGI mode (contact your hosting for details), the Apache server will require the mod_rewrite module and the following rule in .htaccess:
<b>RewriteRule .* - [E=REMOTE_USER:%{HTTP:Authorization}]</b>

Configure PHP to run as an Apache module if possible.";
$MESS["SC_HELP_CHECK_EXEC"] = "If PHP runs in CGI/FastCGI mode on a Unix system, the scripts require execution permissions, otherwise they will not run.
If this test fails, contact your hosting techsupport for necessary file permissions and set the contants <b>BX_FILE_PERMISSIONS</b> and <b>BX_DIR_PERMISSIONS</b> in <i>dbconn.php</i> accordingly.

Configure PHP to run as an Apache module if possible.";
$MESS["SC_HELP_CHECK_SUHOSIN"] = "The suhosin module is designed to protect servers and users from hackers. However, it can block many unmalicious actions a common user may perform. It is generally recommended that you disable this module. If you cannot do that, enable the simulation option which will check the data without actually blocking it:
<b>suhosin.simulation=1</b>

The Proactive Protection module has been designed with the Bitrix architecture in mind. It's a more effective way to protect your web solution.
";
$MESS["SC_HELP_CHECK_BX_CRONTAB"] = "To migrate the non-periodic agents and e-mail to cron, add the following constant to <i>/bitrix/php_interface/dbconn.php</i>:
<code>define('BX_CRONTAB_SUPPORT', true);</code>

With this constant set to \"true\", the system will run only the periodic agents when a hit occurs. Now add a task to cron to execute <i>/var/www/bitrix/modules/main/tools/cron_events.php</i> every minute (replace <i>/var/www</i> with the website root path).

The script defines the constant <b>BX_CRONTAB</b> which indicates that the script is activated by cron and runs only non-periodic agents. If you define this constant in <i>dbconn.php</i> by mistake, periodic agents will never run.";
$MESS["SC_HELP_CHECK_SECURITY"] = "The Apache's mod_security module, like the PHP's suhosin, is intended to protect the website against hackers, but eventually it just prevents normal user actions. It is recommended that you use the standard \"Proactive Protection\" module instead of mod_security.";
$MESS["SC_HELP_CHECK_DIVIDER"] = "A period must be used as the decimal mark. Once you have set the locale, you can redefine the decimal mark to any other symbol. Use the following code to resolve the problem:
<code>setlocale(LC_NUMERIC,'C');</code>
";
$MESS["SC_HELP_CHECK_PRECISION"] = "The PHP precision value (<b>precision</b>) must not be less than 10. Smaller values may cause bizarre errors. The default value is 14.";
$MESS["SC_HELP_CHECK_CLONE"] = "Since verion 5, PHP passes objects by reference rather than copy. However, there are PHP 5 builds that support legacy conventions and pass objects as copies.

To resolve this problem, download and install the latest PHP 5 build.";
$MESS["SC_HELP_CHECK_GETIMAGESIZE"] = "When you add a Flash object, the visual editor needs to get the object size and calls the standard PHP function <b>getimagesize</b> which requires the <b>Zlib</b> extension. This function may fail when called for a compressed Flash object if the <b>Zlib</b> extension is installed as a module. It needs to be built statically.

To resolve this problem, contact your hosting techsupport.";
$MESS["SC_HELP_CHECK_MYSQL_BUG_VERSION"] = "There are known MySQL version containing errors which may cause website malfunction.
<b>4.1.21</b> - sort functions work incorrectly in certain conditions;
<b>5.0.41</b> - the EXISTS function works incorrectly; the search functions return incorrect results;
<b>5.1.34</b> - the auto_increment step is 2 by default while 1 is required.

You need to update MySQL if you have one of these versions installed.";
$MESS["SC_HELP_CHECK_MYSQL_TIME"] = "This test compares the database system time with the web server time. These two may become mistimed if they are installed on individual machines, but the most frequent reason is incorrect time zone configuration.

Set the PHP time zone in <i>/bitrix/php_interface/dbconn.php</i>:
<code>date_default_timezone_set(&quot;Europe/London&quot;);</code> (use your region and city).

Set the database time zone by adding the following code to <i>/bitrix/php_interface/after_connect.php</i>:
<code>\$DB->Query(&quot;SET LOCAL time_zone='Europe/London'&quot;);</code> (use your region and city).

Please refer to http://en.wikipedia.org/wiki/List_of_tz_database_time_zones to get the list of standard correct region and city.";
$MESS["SC_HELP_CHECK_MYSQL_MODE"] = "The parameter <i>sql_mode</i> specifies the MySQL operation mode. Note that if accepts values incompatible with Bitrix solutions. Add the following code to <i>/bitrix/php_interface/after_connect.php</I> to set the default mode:
<code>\$DB->Query(&quot;SET sql_mode=''&quot;);</code>
";
$MESS["SC_HELP_CHECK_MYSQL_INCREMENT"] = "Verifies the value of the auto_increment_increment parameter (which controls the interval between successive column values) by creating a test table and adding two records to it. The value must be set to 1. Otherwise, add the following code to <i>/bitrix/php_interface/after_connect.php</i>:
<code>\$DB->Query(&quot;SET @@auto_increment_increment=1&quot;);</code>
";
$MESS["SC_HELP_CHECK_MYSQL_TABLE_CHARSET"] = "The charset of all the tables and fields must match the database charset. If the charset of any of the tables is defferent, you have to fix it manually using the SQL commands.

The table collation should match the database collations as well. If the charsets are configured correctly, mismatching collation will be fixed automatically.

<b>Attention!</b> Always create full backup copy of the database before changing the charset.";
$MESS["SC_HELP_CHECK_MYSQL_TABLE_STATUS"] = "This test uses the conventional MySQL table check mechanism. If the test finds one or more damaged table, you will be prompted to fix them.";
$MESS["SC_HELP_CHECK_MYSQL_DB_CHARSET"] = "This test check if the database charset and collation match those of the connection. MySQL uses these preferences to create new tables.

Such errors, if any occur can be fixed automatically if a current user has database write permission (ALTER DATABASE).
";
$MESS["SC_HELP_CHECK_MYSQL_CONNECTION_CHARSET"] = "This test will check the charset and collation the system uses when sending data to the MySQL server.

If your website uses <i>UTF-8</I>, the charset must be set to <i>utf8</I> and the collation - <i>utf8_unicode_ci</i>. If the website uses <i>iso-8859-1</i>, the connection must use the same charset.

To change the connection charset (for example, set it to UTF-8), add the following code to <i>/bitrix/php_interface/after_connect.php</i>:
<code>\$DB->Query('SET NAMES &quot;utf8&quot;');</code>

To change the collation, add the code <b>after the charset declaration</b>:
<code>\$DB->Query('SET collation_connection = &quot;utf8_unicode_ci&quot;');</code>

<b>Attention!</b> Once you have changed the new values, make sure your website functions properly.
";
$MESS["SC_HELP_CHECK_BACKTRACK_LIMIT"] = "PHP has a parameter to set the maximum length of the binding portion of a source string that the regular expression calls can make: <i>pcre.backtrack_limit</i>.

Generally, you have to increase this value by calling <i>ini_set</i>. Otherwise, there may occur errors in the web antivirus and the visual editor.  Mail distributions and some other features may also become inoperable.
";
$MESS["SC_T_UPLOAD_RAW"] = "Upload file using php://input";
$MESS["SC_HELP_CHECK_UPLOAD_RAW"] = "Sends binary data in the body of a POST request. However, the data sometimes may become damaged on the server side in which case the Flash based image uploader won't work.";
$MESS["SC_T_STRUCTURE"] = "Database structure";
$MESS["SC_WARN_DAV"] = "WebDav is disabled because the module mod_dav/mod_dav_fs is loaded.";
$MESS["SC_TABLE_COLLATION_NA"] = "Not checked due to table charset errors";
$MESS["SC_READ_MORE"] = "See details in <a href=\"?read_log=Y\" target=\"_blank\">website check log</a>.";
$MESS["SC_CHARSET_CONN_VS_RES"] = "The connection charset (#CONN#) is different than the result charset (#RES#).";
$MESS["SC_STRLEN_FAIL"] = "String functions return invalid results.";
$MESS["SC_T_RECURSION"] = "Stack size; pcre.recursion_limit";
$MESS["SC_HELP_CHECK_PCRE_RECURSION"] = "The parameter <i>pcre.recursion_limit</i> is set to 100000 by default. If recursion eats more memory than the system stack size can provide (commonly 8 MB), PHP will error out on complex regular expressions showing a <i>Segmentation fault</i> error message.

To disable stack size limit, edit the Apache startup script: <code>ulimit -s unlimited</code>
Alternatively, you can decrease the value of <i>pcre.recursion_limit</i> to 14000 or less.

This will prevent PHP catastrophic failures but may lead to inconsistencies in the behavior of string functions: for example, the forums may begin to show empty posts.";
$MESS["SC_PCRE_CLEAN"] = "Long text strings may be handled incorrectly due to system restrictions.";
$MESS["SC_T_METHOD_EXISTS"] = "method_exists called on line";
$MESS["SC_HELP_CHECK_METHOD_EXISTS"] = "The script fails when calling <i>method_exists</I> on some PHP versions. Please refer to this discussion for more information: <a href='http://bugs.php.net/bug.php?id=51425' target=_blank>http://bugs.php.net/bug.php?id=51425</a>
Install a different PHP version to resolve the issue.";
$MESS["SC_HELP_CHECK_MYSQL_TABLE_STRUCTURE"] = "The module installation packages always include information on the structure of database tables they use. When updating, the module installers may change the table structure and the module files (scripts).

If the module scripts do not match the current table structure, it will definitely bring about runtime errors.

There may be new database indexes that were added to the new distribution packages but not included in updates. It is because updating a system to include new indexes would take too long and fail in the end.

Website check will diagnose the <b>installed</b> modules and create and/or update the missing indexes and fields to ensure data integrity. However, you will have to review the log manually if the field type has changed.";
$MESS["ERR_MAX_INPUT_VARS"] = "The value of max_input_vars must be #MIN# or greater. The current value is: #CURRENT#";
$MESS["SC_T_APACHE"] = "Web server modules";
$MESS["ERR_OLD_VM"] = "You are running an outdated version of Bitrix Environment. Please install the most recent version to prevent configuration issues.";
$MESS["SC_ERR_NO_FIELD"] = "The field #FIELD# is missing from the table #TABLE#";
$MESS["SC_ERR_FIELD_DIFFERS"] = "Table #TABLE#: the field #FIELD# \"#CUR#\" does not match the description \"#NEW#\"";
$MESS["SC_ERR_NO_INDEX"] = "Index #INDEX# is missing from the table #TABLE#";
$MESS["SC_ERR_NO_TABLE"] = "The table #TABLE# does not exist.";
$MESS["SC_CHECK_TABLES_STRUCT_ERRORS"] = "There are errors in database structure (missing tables: #NO_TABLES#, missing fields: #NO_FIELDS#, different fields: #DIFF_FIELDS#, missing indexes: #NO_INDEXES#). Total issues: #VAL#. #VAL1# can be fixed right away.";
$MESS["SC_CHECK_TABLES_STRUCT_ERRORS_FIX"] = "The issues have been fixed, but some fields (#VAL#) have different types. You will have to fix them manually by reviewing the website check log.";
$MESS["SC_HELP_CHECK_INSTALL_SCRIPTS"] = "Users may occasionally forget to delete the installation scripts (restore.php, bitrixsetup.php) after system recovery or installation. This may become a serious security threat and result in website hijacking. If you have ignored the autodelete warning, remember to remove these files manually.";
$MESS["SC_T_INSTALL_SCRIPTS"] = "Service scripts in the site root";
?>