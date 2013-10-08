<?php

function test_input($data)
{
	$data = trim($data);
	$data = stripslashes($data);
	$data = htmlspecialchars($data);
	return $data;
}

// define variables and set to empty values
$firstnameErr = $lastnameErr = $emailErr = $sentErr = $recaptcha_error = "";
$firstname = $lastname = $email = $phone = $address1 = $address2 = $addresscity = $addressstate = $addresscountry = $addresszip = $comment = "";
$sent = false;

function validate_input($input, $name, &$inputValue, $invalidChars, $invalidCharMessage)
{
	if (empty($_POST[$input]))
	{
		return $name." is required";
	}
	else
	{
		$inputValue = test_input($_POST[$input]);
		
		// check if name only contains letters and whitespace
		if (!preg_match($invalidChars, $inputValue))
		{
			return $invalidCharMessage; 
		}
	}
}

//if($_POST['submit']) 
if($_SERVER["REQUEST_METHOD"] == "POST")
{
	$firstnameErr = validate_input(
		"firstname",
		"First Name",
		$firstname,
		"/^[a-zA-Z ]*$/",
		"Only letters and white space allowed");
		
	$lastnameErr = validate_input(
		"lastname",
		"Last Name",
		$lastname,
		"/^[a-zA-Z ]*$/",
		"Only letters and white space allowed");
		
	$emailErr = validate_input(
		"email",
		"Email",
		$email,
		"/([\w\-]+\@[\w\-]+\.[\w\-]+)/",
		"Invalid email format");
		
	require_once('recaptchalib.php');
	$privatekey = "6LdShOgSAAAAAIZuxgbu5Pkcvduxdh5BWBTxq6Tl";
	$resp = recaptcha_check_answer ($privatekey,
                                	$_SERVER["REMOTE_ADDR"],
									$_POST["recaptcha_challenge_field"],
									$_POST["recaptcha_response_field"]);

	if (!$resp->is_valid) {
    	// What happens when the CAPTCHA was entered incorrectly
		$recaptcha_error = "The reCAPTCHA wasn't entered correctly. Go back and try it again.";
	} 
  
	// If there are no errors then continue to process form...
	if(empty($emailErr) && empty($firstnameErr) && empty($lastnameErr) && empty($recaptcha_error))
	{
		// Retrieve other form inputs...
		$phone          = test_input($_POST["phone"]);
		$address1       = test_input($_POST["address1"]);
		$address2       = test_input($_POST["address2"]);
		$addresscity    = test_input($_POST["addresscity"]);
		$addressstate   = test_input($_POST["addressstate"]);
		$addresszip     = test_input($_POST["addresszip"]);
		$addresscountry = test_input($_POST["addresscountry"]);
		$comment        = test_input($_POST["comment"]);
	
		/* Create XML Document */
		$xmlDoc = new DOMDocument('1.0');
		
		/* we want a nice output */
		$xmlDoc->formatOutput = true;
		
		/* Build Maximizer XML file */
			$xmlRoot = $xmlDoc->createElement('AllData');
			$xmlAttribute = $xmlDoc->createAttribute('xmlns');
			$xmlAttribute->value = 'http://www.maximizer.com/maximizer/xmlimportexport/3.0';
			$xmlRoot->appendChild($xmlAttribute);
			
			$xmlAttribute = $xmlDoc->createAttribute('xmlns:xsi');
			$xmlAttribute->value = 'http://www.w3.org/2001/XMLSchema-instance';
			$xmlRoot->appendChild($xmlAttribute);
			
			$xmlAttribute = $xmlDoc->createAttribute('xsi:schemaLocation');
			$xmlAttribute->value = 'http://www.maximizer.com/maximizer/xmlimportexport/3.0  MXISchema.xsd';
			$xmlRoot->appendChild($xmlAttribute);
			
			$xmlDoc->appendChild($xmlRoot);
			
			$xmlIndividual = $xmlDoc->createElement('Individual');
			
				$xmlFirstName = $xmlDoc->createElement('FirstName', $firstname);
				$xmlIndividual->appendChild($xmlFirstName);
			
				$xmlLastName = $xmlDoc->createElement('LastName', $lastname);
				$xmlIndividual->appendChild($xmlLastName);
			
				$xmlEmail = $xmlDoc->createElement('Email');
					$xmlEmailAddress = $xmlDoc->createElement('Address', $email);
				$xmlEmail->appendChild($xmlEmailAddress);
				$xmlIndividual->appendChild($xmlEmail);			
			
				$xmlPhone = $xmlDoc->createElement('Phone');
				$xmlPhoneNumber = $xmlDoc->createElement('Number', $phone);
					$xmlPhone->appendChild($xmlPhoneNumber);
				$xmlIndividual->appendChild($xmlPhone);			
			
				$xmlAddress = $xmlDoc->createElement('Address');
				
					$xmlAddressLine1 = $xmlDoc->createElement('AddressLine1', $address1);
					$xmlAddress->appendChild($xmlAddressLine1);
					
					$xmlAddressLine2 = $xmlDoc->createElement('AddressLine2', $address2);
					$xmlAddress->appendChild($xmlAddressLine2);
					
					$xmlAddressCity = $xmlDoc->createElement('City', $addresscity);
					$xmlAddress->appendChild($xmlAddressCity);
					
					$xmlAddressState = $xmlDoc->createElement('StateProvince', $addressstate);
					$xmlAddress->appendChild($xmlAddressState);
					
					$xmlAddressCountry = $xmlDoc->createElement('Country', $addresscountry);
					$xmlAddress->appendChild($xmlAddressCountry);
					
					$xmlAddressZipCode = $xmlDoc->createElement('ZipCode', $addresszip);
					$xmlAddress->appendChild($xmlAddressZipCode);
					
				$xmlIndividual->appendChild($xmlAddress);			
			
			$xmlRoot->appendChild($xmlIndividual);
		
		/* Email Detials */
		
			$mail_to   = "me@myemailaddress.com";
			$from_mail = "webform@mywebsite.com";
			$from_name = "PHP/XML Webform";
			$reply_to  = "webform@mywebsite.com";
			$subject   = "PHP/XML Webform";
		
		/* The plain-text part of the message just lists all the collected data... */
		
			$message   = "Name:".$firstname." ".$lastname."\r\nEmail: ".$email."\r\nPhone Number: ".$phone."\r\nAddress: ".$address1." ".$address2."\r\nCity: ".$addresscity."\r\nState: ".$addressstate."\r\nZip: ".$addresszip."\r\nCountry: ".$addresscountry."\r\nComments: ".$comment."\r\n";
		
		/* Attachment File */
		
			// No need to create an actual file, just use the content of the create XML document...
			$content = $xmlDoc->saveXML();
		
		/* Create the email header */
			
			// Generate a boundary
			$boundary = md5(uniqid(time()));
			
			// Email header
			$header = "From: ".$from_name." <".$from_mail.">\r\n";
			$header .= "Reply-To: ".$reply_to."\r\n";
			$header .= "MIME-Version: 1.0\r\n";
			
			// Multipart wraps the Email Content and Attachment
			$header .= "Content-Type: multipart/mixed; boundary=\"".$boundary."\"\r\n";
			$header .= "This is a multi-part message in MIME format.\r\n";
			$header .= "--".$boundary."\r\n";
			
			// text/plain
			$header .= "Content-type:text/plain; charset=iso-8859-1\r\n";
			$header .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
			$header .= "$message\r\n";
			$header .= "--".$boundary."\r\n";
			
			// Attachment
			$header .= "Content-Type: application/xml; name=\"".$file_name."\"\r\n";
			$header .= "Content-Transfer-Encoding: base64\r\n";
			$header .= "Content-Disposition: attachment; filename=\"".$file_name."\"\r\n\r\n";
			$header .= $content."\r\n";
			$header .= "--".$boundary."--";
			
		error_log($mail_to."\r\n".$subject."\r\n".$message."\r\n".$header);
		
		/* Send email */
		//if (mail($mail_to, $subject, $message, $header)) 
		if(true) 
		{
			$sent = true;
		} else 
		{
			$sentErr = "There was an error submitting your form, please try again later.";
		}
	}
}?>


<!-- Display error / success message -->
<?php
	if($sent)
	{
		echo "Thanks ".$firstname." your form has been submitted.";
	}
	else
	{
		echo $sentErr;
	}
?>

<!-- Only display the form if it hasn't been sent yet -->
<div style="<?php if($sent){echo 'display:none';}else{echo 'display:block';}?>">	
	<form class="brochure_request" method="post" action=""> 
		<p><span class="error">* required field.</span></p>   
		<table width="100%" border="1">
		<tr><td>First Name:</td><td><input type="text" name="firstname" value="<?php echo $firstname;?>"><br/><span class="error"><?php echo $firstnameErr;?></span></td><td class="error">* </td></tr>
		<tr><td>Last Name:</td><td><input type="text" name="lastname" value="<?php echo $lastname;?>"><br/><span class="error"><?php echo $lastnameErr;?></span></td><td class="error">* </td></tr>
		<tr><td>E-mail:</td><td><input type="text" name="email" value="<?php echo $email;?>"><br/><span class="error"><?php echo $emailErr;?></span></td><td class="error">*</span></td></tr>
		<tr><td>Phone Number:</td><td><input type="text" name="phone" value="<?php echo $phone;?>"></td><td class="error"></td></tr>
		<tr><td>Address Line 1:</td><td><input type="text" name="address1" value="<?php echo $address1;?>"></td><td class="error"></td></tr>
		<tr><td>Address Line 2:</td><td><input type="text" name="address2" value="<?php echo $address2;?>"></td><td class="error"></td></tr>
		<tr><td>City:</td><td><input type="text" name="addresscity" value="<?php echo $addresscity;?>"></td><td class="error"></td></tr>
		<tr><td>State / Province:</td><td><input type="text" name="addressstate" value="<?php echo $addressstate;?>"></td><td class="error"></td></tr>
		<tr><td>Zip / Postal Code:</td><td><input type="text" name="addresszip" value="<?php echo $addresszip;?>"></td><td class="error"></td></tr>
		<tr><td>Country:</td><td><input type="text" name="addresscountry" value="<?php echo $addresscountry;?>"></td><td class="error"></td></tr>
		<tr><td>Comment</td><td><textarea name="comment" rows="5" cols="40"><?php echo $comment;?></textarea></td><td class="error"></td></tr>
		<tr>
		<td colspan=3 align="center">
			<?php
				require_once('recaptchalib.php');
				$publickey = "6LdShOgSAAAAAOuY5ojER_XxRW1dYNW7gyHt-Kmj";
				echo recaptcha_get_html($publickey);
	        ?><br/>
	        <span class="error"><?php echo $recaptcha_error;?></span>
		</td>
		</tr>
		<tr><td colspan="2"><input type="submit" name="submit" value="submit"> </td><td></td></tr>
		</table>	
	</form>
</div>

<h3>

</h3>