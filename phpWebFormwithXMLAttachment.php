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
	$privatekey = "<private_key>"; /* ENTER YOUR INFO HERE */
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
		
			$mail_to   = "me@myemailaddress.com"; /* ENTER YOUR INFO HERE */
			$from_mail = "webform@mywebsite.com"; /* ENTER YOUR INFO HERE */
			$from_name = "PHP/XML Webform";       /* ENTER YOUR INFO HERE */
			$reply_to  = "webform@mywebsite.com"; /* ENTER YOUR INFO HERE */
			$subject   = "PHP/XML Webform";       /* ENTER YOUR INFO HERE */
		
		/* The plain-text part of the message just lists all the collected data... */
		
			$message   = "Name:".$firstname." ".$lastname."\r\nEmail: ".$email."\r\nPhone Number: ".$phone."\r\nAddress: ".$address1." ".$address2."\r\nCity: ".$addresscity."\r\nState: ".$addressstate."\r\nZip: ".$addresszip."\r\nCountry: ".$addresscountry."\r\nComments: ".$comment."\r\n";
		
		/* Attachment File */
		
			// No need to create an actual file, just use the content of the create XML document...
			$content = chunk_split(base64_encode($xmlDoc->saveXML()));
			
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
			$header .= "Content-Type: application/xml; name=\""."attachment.xml"."\"\r\n";
			$header .= "Content-Transfer-Encoding: base64\r\n";
			$header .= "Content-Disposition: attachment; filename=\""."attachment.xml"."\"\r\n\r\n";
			$header .= $content."\r\n";
			$header .= "--".$boundary."--";
			
		error_log($mail_to."\r\n".$subject."\r\n".$message."\r\n".$header);
		
		/* Send email */
		if (mail($mail_to, $subject, $message, $header)) 
		{
			$sent = true;
		} else 
		{
			$sentErr = "There was an error submitting your form, please try again later.";
		}
	}
}?>

<style>
#webform-wrapper
{
	-webkit-box-shadow: 2px 2px 10px rgba(50, 50, 50, 0.75);
	-moz-box-shadow:    2px 2px 10px rgba(50, 50, 50, 0.75);
	box-shadow:         2px 2px 10px rgba(50, 50, 50, 0.75);

	width:450px;
	margin:auto;
	border-radius:8px;
	border:1px solid #ccc;
	padding:30px;
	background-color:#eee;
}

#webform-wrapper h3
{
	text-align: center;
}

#webform
{
	font-family: sans-serif;
}

#webform table
{
	border:none;
	width:100%;
}

#webform table td
{
	border-bottom: 1px solid #efefef;
}

#webform input[type=text], #webform textarea
{
	width: 100%;
	-webkit-appearance:none;
	background-color: white;
	border:thin solid #888;
	border-radius: 3px;
	font-size:11pt;
	margin: 8px auto;
	padding: 8px 8px;
}

#webform input[type=submit], #webform input[type=reset]
{
	-webkit-box-shadow: 2px 2px 10px rgba(50, 50, 50, 0.75);
	-moz-box-shadow:    2px 2px 10px rgba(50, 50, 50, 0.75);
	box-shadow:         2px 2px 10px rgba(50, 50, 50, 0.75);

	background-image: none;
	-webkit-appearance:none;
	background-color: green;
	border:1px solid #888;
	border-radius: 5px;
	color:white;
	font-weight: bold;
	margin: 0px 10px;
	min-width: 100px;
	padding:10px;
	text-transform: uppercase;
}
#webform input[type=reset]
{
	background: red;
}
#webform input[type=reset]:hover
{	
	background: #6c1e1e;
}
#webform input[type=submit]:hover
{
	background-color: #133b13;
}

#webform .error, #webform .required
{
	color:red;
	margin: 0px 10px 10px 10px;
}
</style>

<div id="webform-wrapper">

	<!-- Display error / success message -->
	<h3>
	<?php
		if($sent)
		{
			echo "Thanks ".$firstname.", your form has been submitted.";
		}
		else
		{
			echo $sentErr;
		}
	?>	
	</h3>
	
	<!-- Only display the form if it hasn't been sent yet -->
	<form id="webform" method="post" action="#" style="<?php if($sent){echo 'display:none';}else{echo 'display:block';}?>;"> 
		<h3>PHP Web Form with XML Attachment</h3>   
		<table>
			<tr>
				<td><input type="text" name="firstname" placeholder="First Name" value="<?php echo $firstname;?>"><br/>
					<p class="error"><?php echo $firstnameErr;?></p></td>
				<td><p class="required">*</p></td>
			</tr>
			<tr>
				<td><input type="text" name="lastname" placeholder="Last Name" value="<?php echo $lastname;?>"><br/>
					<p class="error"><?php echo $lastnameErr;?></p></td>
				<td><p class="required">*</p></td>
			</tr>
			<tr>
				<td><input type="text" name="email" placeholder="E-mail" value="<?php echo $email;?>"><br/>
					<p class="error"><?php echo $emailErr;?></p></td>
				<td><p class="required">*</p></td>
			</tr>
			<tr>
				<td><input type="text" name="phone" placeholder="555.555.5555" value="<?php echo $phone;?>"></td>
				<td class="error"></td>
			</tr>
			<tr>
				<td><input type="text" name="address1" placeholder="Address Line 1" value="<?php echo $address1;?>"></td>
				<td class="error"></td>
			</tr>
			<tr>
				<td><input type="text" name="address2" placeholder="Address Line 2" value="<?php echo $address2;?>"></td>
				<td class="error"></td>
			</tr>
			<tr>
				<td><input type="text" name="addresscity" placeholder="City" value="<?php echo $addresscity;?>"></td>
				<td class="error"></td>
			</tr>
			<tr>
				<td><input type="text" name="addressstate" placeholder="State / Province" value="<?php echo $addressstate;?>"></td>
				<td class="error"></td>
			</tr>
			<tr>
				<td><input type="text" name="addresszip" placeholder="Zip" value="<?php echo $addresszip;?>"></td>
				<td class="error"></td>
			</tr>
			<tr>
				<td><input type="text" name="addresscountry" placeholder="Country" value="<?php echo $addresscountry;?>"></td>
				<td class="error"></td>
			</tr>
			<tr>
				<td><textarea name="comment" placeholder="Tell us how much you like this form?" rows="5" cols="40"><?php echo $comment;?></textarea></td>
				<td class="error"></td>
			</tr>
		<tr>
		<tr>
			<td align="center" style="text-align:center">
				<center>
				<?php
					require_once('recaptchalib.php');
					$publickey = "<public_key>"; /* ENTER YOUR INFO HERE */
					echo recaptcha_get_html($publickey);
		        ?><br/>
		        <p class="error"><?php echo $recaptcha_error;?></p>
				</center>
			</td>
			<td></td>
		</tr>
		<tr>			
			<td align="center" style="text-align:center">
				<center>
					<input type="reset" name="Reset" value="Reset">
					<input type="submit" name="submit" value="submit">
				</center>
			</td>
			<td></td>
			</tr>
		</table>	
	</form>
</div>

<h3>

</h3>