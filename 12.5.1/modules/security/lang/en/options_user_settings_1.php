<?
$MESS ['SEC_OTP_ACCESS_DENIED'] = "You cannot edit parameters of one-time passwords.";
$MESS ['SEC_OTP_SWITCH_ON'] = "Enable Compound Passwords";
$MESS ['SEC_OTP_SECRET_KEY'] = "Secret Key (supplied with the device)";
$MESS ['SEC_OTP_INIT'] = "Initialization";
$MESS ['SEC_OTP_PASS1'] = "The first device password (click and write down)";
$MESS ['SEC_OTP_PASS2'] = "The second device password (click again and write down)";
$MESS ['SEC_OTP_NOTE'] = "<h3 style=\"clear:both\"><br>One-Time Password</h3>
<img src=\"/bitrix/images/security/etoken_pass.png\" align=\"left\" style=\"margin-right:10px;\">
The <a href=\"http://en.wikipedia.org/wiki/One-time_password\">one-time password</a> (<b>OTP</b>) concept empowers the standard authorization scheme and significantly reinforces the web project security. The one-time password system requires a physical hardware token (device) (e.g., <a href=\"http://www.safenet-inc.com/products/data-protection/two-factor-authentication/etoken-pass/\">SafeNet eToken PASS</a>) or special OTP software. The site administrator is strongly recommended to use OTP to ensure the best security.
<h3 style=\"clear:both\"><br>Usage</h3>
<img src=\"/bitrix/images/security/en_pass_form.png\" align=\"left\" style=\"margin-right:10px;\">
If the OTP system is enabled, a user can authorize with a login and a compound password that consists of a standard password and a one-time device password (6 digits). The one-time password (see <font style=\"color:red\">2</font> on the figure) is entered in the \"Password\" field together with the standard password (see <font style=\"color:red\">1</font> on the figure) without space, in the authorization form.<br>
The OTP authentication takes effect after the secret key and <b>consecutively generated one-time passwords</b> obtained from the device are entered.
<h3 style=\"clear:both\"><br>Initialization</h3>
When initializing or repeatedly synchronizing the device, you will have to provide the two <b>consecutively generated one-time passwords</b> obtained from the device.
<h3 style=\"clear:both\"><br>Description</h3>
The OTP authorization system was developed by the Initiative for Open Authentication (<a href=\"http://www.openauthentication.org/\">OATH</a>).<br>
The implementation is based on the HMAC algorithm and the SHA-1 hash function. To calculate the OTP value, the system takes the two parameters on input: the secret key (initial value for the generator) and the counter current value (the required cycles of generation). Upon initialization of the device, the initial value is stored in the device as well as on the site. The device counter increments each time a new OTP is generated, the server counter - upon each successful OTP authentication.<br>
Each lot of OTP devices is shipped with an encrypted file containing the initial values (secret keys) for all devices in a lot. The values are bound to the device serial numbers printed on the device body.<br>
If the device and the server generator counters become desynchronized, you can easily resynchronize them by resetting the server value to the value stored in the device. This procedure requires that a system administrator (or a user owning sufficient permissions) generates two consequent OTP values and enters them in the OTP form.";
?>