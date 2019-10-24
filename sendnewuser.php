<?php

//Set variable defaults
$requestorName='';
$requestorPhone='';
$requestorEmail='';
$dueDate='';
$givenName='';
$surname='';
$employeeType='';
$department='';
$title='';
$employeeNumber='';
$supervisor='';
$building='';
$telephoneNumber='';
$mailStop='';
$o365User='';
$o365Level='';
$vpnUser='';
$mobileNumber='';
$mobileProvider='';
$username='';
$ou='CN=Users,DC=omahasteaks,DC=com';
$password='';
$vpnContact='';
$groups=array();
$groupAllMailUsers='';

//Gather form values
$requestorName=$_POST["requestorName"];
$requestorPhone=$_POST["requestorPhone"];
$requestorEmail=$_POST["requestorEmail"];
$dueDate=$_POST["dueDate"];
$givenName=$_POST["givenName"];
$surname=$_POST["surname"];
$employeeType=$_POST["employeeType"];
$department=$_POST["department"];
$title=$_POST["title"];
$employeeNumber=$_POST["employeeNumber"];
$supervisor=$_POST["supervisor"];
$building=$_POST["building"];
$telephoneNumber=$_POST["telephoneNumber"];
$mailStop=$_POST["mailStop"];
$o365User=$_POST["o365User"];
$o365Level=$_POST["o365Level"];
$vpnUser=$_POST["vpnUser"];
$mobileNumber=$_POST["mobileNumber"];
$mobileProvider=$_POST["mobileProvider"];
$groupAllMailUsers=$_POST["groupAllMailUsers"];

/*
 * Alltel              @message.alltel.com
 * AT&T                @txt.att.net
 * Boost Mobile        @myboostmobile.com
 * Sprint              @messaging.sprintpcs.com
 * T-Mobile            @tmomail.net
 * US Cellular         @email.uscc.net
 * Verizon             @vtext.com
 * Virgin Mobile       @vmobl.com
 * Republic Wireless   @text.republicwireless.com
 * Cricket             @sms.mycricket.com
 */

//Convert supervisor email to supervisor sAMAccountName

$supervisorTemp='';
$supervisorTemp=explode('@',$supervisor);
$supervisor=$supervisorTemp[0];

//Prepare email values
$mailBody='';
$username='';
$username.=$givenName;
$username.=substr($surname,0,1);
$password="Fall2019";
//Adjust organizational unit based upon employee type
if ($employeeType == "Seasonal") {
    if ($o365User == "o365Yes") {
        $ou="OU=SeaWEmail,OU=Seasonal,DC=omahasteaks,DC=com";
    }
    else $ou="OU=Seasonal,DC=omahasteaks,DC=com";
}

//Configure multifactor authentication contact for VPN access
if ($vpnUser) {
    $vpnContact=str_replace("-","",$mobileNumber);
    $vpnContact.=$mobileProvider;
}

//Configure groups
if ($o365User){
    array_push($groups,$o365Level);
}
if ($groupAllMailUsers){
    array_push($groups,"CN=All Mail Users,CN=Users,DC=omahasteaks,DC=com");
}

//Remove any empty entries in the $groups array
$groups=array_filter($groups);

//Email variables

$recipient="andrew.kiebach@gmail.com";
$subject="New user; Requested by: $dueDate; $requestorPhone";
$mailBody.="----------Run in PowerShell----------\r\n";
$mailBody.="if (Get-ADUser -F {SamAccountName -eq \"$username\"}) { `\r\n";
$mailBody.="Write-Warning \"WARNING: A user account \"$username\" already exists in Active Directory, user not created\" `\r\n";
$mailBody.="} `\r\n";
$mailBody.="else { `\r\n";
$mailBody.="New-ADUser `\r\n";
$mailBody.="-SamAccountName \"$username\" `\r\n";
$mailBody.="-UserPrincipalName \"$username@omahasteaks.com\" `\r\n";
$mailBody.="-Name \"$givenName $surname\" `\r\n";
$mailBody.="-GivenName \"$givenName\" `\r\n";
$mailBody.="-Surname \"$surname\" `\r\n";
$mailBody.="-Enabled \$True `\r\n";
$mailBody.="-ChangePasswordAtLogon \$True `\r\n";
$mailBody.="-DisplayName \"$givenName $surname\" `\r\n";
$mailBody.="-Department \"$department\" `\r\n";
$mailBody.="-Description \"$department\" `\r\n";
$mailBody.="-Title \"$title\" `\r\n";
$mailBody.="-EmployeeNumber \"$employeeNumber\" `\r\n";
$mailBody.="-Manager \"$supervisor\" `\r\n";
$mailBody.="-Office \"$building\" `\r\n";
$mailBody.="-OfficePhone \"$telephoneNumber\" `\r\n";
$mailBody.="-Path \"$ou\" `\r\n";
$mailBody.="-ScriptPath \"Login\" `\r\n";
$mailBody.="-AccountPassword (convertto-securestring \"$password\" -AsPlainText -Force) `\r\n";
/* Causing issues at the moment, review and correct when time permits
 * $mailBody.="-OtherAttributes @{'extensionAttribute1'=\"$mailStop\"; 'extensionAttribute15'=\"$vpnContact\"; } `\r\n";
 */
$mailBody.="}\r\n";
$mailBody.="--------------------\r\n";

//Loop through $groups array to generate Add-ADGroupMember commands
foreach ($groups as $identity) {
    $mailBody.="Add-ADGroupMember -Identity \"$identity\" -Members \"$username\";\r\n";
};

$mailBody.="----------Run in Exchange Management Shell----------\r\n";
$mailBody.="\r\n";
$mailBody.="Enable-Mailbox -Identity $username@omahasteaks.com -Database \"Mailbox 1\"\r\n";

/*
 * Original version of the $mailBody variable for a CSV 
 * $mailBody.="$givenName,$surname,$username,$password,$ou,$employeeType,$department,$title,$supervisor,$building,$telephoneNumber,$mailStop,$o365User,$o365Level,$vpnUser,$mobileNumber,$mobileProvider,\r\n";
*/

if (mail($recipient, $subject, $mailBody, "From: $requestorName <$requestorEmail>")) {
    echo "New user message sent";
    echo "$mailBody";
}
else {
    echo "Error: Message not sent, please contact Helpdesk";
}
?>
