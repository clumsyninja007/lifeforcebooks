<?php 
      // note: important information has been replaced with "xxx"
      // the file uses the ConstantContacts API for saving contacts
      // and it uses the PHPMailer API to send contacts emails

			require_once 'xxx/autoload.php';
			require_once 'xxx/PHPMailerAutoload.php';
		
			use Ctct\ConstantContact;
			use Ctct\Components\Contacts\Contact;
			use Ctct\Components\Contacts\ContactList;
			use Ctct\Components\Contacts\EmailAddress;
			use Ctct\Exceptions\CtctException;
		
			// Enter your Constant Contact APIKEY and ACCESS_TOKEN
			define("APIKEY", "xxx");
			define("ACCESS_TOKEN", "xxx");
		
      // returns the User's IP address
			function getUserIP()
			{
		    	$client  = @$_SERVER['HTTP_CLIENT_IP'];
		    	$forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
		    	$remote  = $_SERVER['REMOTE_ADDR'];
		
		    	if(filter_var($client, FILTER_VALIDATE_IP))
		    	{
		      	  	$ip = $client;
		    	}
		    	elseif(filter_var($forward, FILTER_VALIDATE_IP))
		    	{
		        	$ip = $forward;
		    	}
		    	else
		    	{
		        	$ip = $remote;
		    	}
		
		    	return $ip;
			}
		
      // calls the above function and saves the result to a variable
			$user_ip = getUserIP();
		
      // attempt to create a new contact
			$cc = new ConstantContact(APIKEY);
			$contact_created = False;
		
			// attempt to fetch lists in the account, catching any exceptions and printing the errors to 	screen
			try {
		    	$lists = $cc->getLists(ACCESS_TOKEN);
			} catch (CtctException $ex) {
		    	foreach ($ex->getErrors() as $error) {
		        	print_r($error);
			    }
			}
		
			// check if the form was submitted
			if (isset($_POST['email']) && strlen($_POST['email']) > 1) {
			    $action = "Getting Contact By Email Address";
			    try {
					$email = strip_tags($_POST['email']);
					$first_name = strip_tags($_POST['first_name']);
					$last_name = strip_tags($_POST['last_name']);
			        // check to see if a contact with the email addess already exists in the account
			        $response = $cc->getContactByEmail(ACCESS_TOKEN, $email);
		
			        // create a new contact if one does not exist
		 	       if (empty($response->results)) {
		 	           $action = "Creating Contact";
		
		    	        $contact = new Contact();
		    	        $contact->addEmail($email);
		    	        $contact->addList('xxx');
		     	        $contact->first_name = $first_name;
		    	        $contact->last_name = $last_name;
		
		         	   /*
		         	    * The third parameter of addContact defaults to false, but if this were set to true it would tell Constant
		                * Contact that this action is being performed by the contact themselves, and gives the ability to
		            	 * opt contacts back in and trigger Welcome/Change-of-interest emails.
		         	    *
		        	     * See: http://developer.constantcontact.com/docs/contacts-api/contacts-index.html#opt_in
		         	    */
		            $returnContact = $cc->addContact(ACCESS_TOKEN, $contact, true);
					$contact_created = True;
		
		            // update the existing contact if address already existed
		       	 } else {
		            $action = "Updating Contact";
		
		            $contact = $response->results[0];
		            $contact->addList('xxx');
		            $contact->first_name = $first_name;
		            $contact->last_name = $last_name;
		
		            /*
		             * The third parameter of updateContact defaults to false, but if this were set to true it would tell
		             * Constant Contact that this action is being performed by the contact themselves, and gives the ability to
		             * opt contacts back in and trigger Welcome/Change-of-interest emails.
		             *
		             * See: http://developer.constantcontact.com/docs/contacts-api/contacts-index.html#opt_in
		             */
		            $returnContact = $cc->updateContact(ACCESS_TOKEN, $contact, true);
					$contact_created = True;
		        }
		
		        // catch any exceptions thrown during the process and print the errors to screen
		    } catch (CtctException $ex) {
		        echo '<span class="label label-important">Error ' . $action . '</span>';
		        echo '<div class="container alert-error"><pre class="failure-pre">';
		        print_r($ex->getErrors());
		        echo '</pre></div>';
		        die();
		    }
		
		}
		
    // if a new contact was successfully created
		if ($contact_created == True) {
		
      // if the user reached this page via a form with the 'ebook' action
      // send them an email with a link to their free download
			if ((array_key_exists('action', $_POST)) and
		   	   ($_POST['action'] == 'ebook')) {
		
				$mail = new PHPMailer;
		
				//$mail->SMTPDebug = 3;                               // Enable verbose debug output
		
				// Set mailer to use SMTP
				$mail->isSMTP();
		
				// Specify SMTP server
				$mail->Host = 'xxx';
		
				$mail->Port = 465; // TCP port to connect to
				$mail->SMTPSecure = 'ssl'; // Enable TLS encryption, `ssl` also accepted
		
				$mail->SMTPAuth = true; // Enable SMTP authentication
				$mail->Username = 'xxx'; // SMTP username
				$mail->Password = 'xxx'; // SMTP password
		
				$mail->setFrom('xxx'); // Who is the message sent from?
				$mail->addAddress($email, $first_name . ' ' . $last_name); // Add a recipient
				$mail->isHTML(true); // Set email format to HTML
		
				$mail->Subject = 'Free eBook!';
				$mail->Body    = 'Click the link to download your free eBook!
				http://www.lfb2016test.org/simpleDown.php';
		
		  // if the user got to this page via a form with the action "audible"
			} elseif ((array_key_exists('action', $_POST)) and
		  		     ($_POST['action'] == 'audible')) {
		
        // save the location of the ip_list and code_list files to variables
				$ip_list = 'xxx/ip_list';
				$all_codes = 'xxx/code_list';
		
        // search the ip_list file for the user's IP
        // to make sure they don't get multiple codes
				if( exec('grep '.escapeshellarg($user_ip)." $ip_list")) {
		        	
          // if the user has had their IP logged, send them to the access-denied page
					$url = 'access-denied.html';
		
					// clear out the output buffer
					while (ob_get_status()) 
					{
						ob_end_clean();
					}
		
					// no redirect
					header( "Location: $url" );
		
		    	}
          
        // if this is a new user
				else {
					
          // grab a new code from the code_list file
					$code_list = fopen($all_codes,"r");
					$code_found = false;
					$line_num = 1;
		
					while ((! feof($code_list)) and ($code_found == false))
		  			{
						$code_found = false;
						$line = fgets($code_list);
		
		  				if (strpos($line,"used:") === false) {
							$code = $line;
							$code_found = true;
						}
						$line_num = $line_num + 1;
		  			}
		
					fclose($code_list);
					
          // if an unused code was found
					if ($code_found == true) {
		
            // mark the code as used
						$used_code = file_get_contents($all_codes);
						$parsed = str_replace("$code", "used:$line", $used_code);
						file_put_contents($all_codes, $parsed);
		
            // add the user to the IP list
						file_put_contents($ip_list, $user_ip."\n", FILE_APPEND | LOCK_EX);
		
            // send them an email with their code
						$mail = new PHPMailer;
		
						//$mail->SMTPDebug = 3;                               // Enable verbose debug output
		
						// Set mailer to use SMTP
						$mail->isSMTP();
		
						// Specify SMTP server
						$mail->Host = 'xxx';
		
						$mail->Port = 465; // TCP port to connect to
						$mail->SMTPSecure = 'ssl'; // Enable TLS encryption, `ssl` also accepted
		
						$mail->SMTPAuth = true; // Enable SMTP authentication
						$mail->Username = 'xxx'; // SMTP username
						$mail->Password = 'xxx'; // SMTP password
		
						$mail->setFrom('xxx'); // Who is the message sent from?
						$mail->addAddress($email, $first_name . ' ' . $last_name); // Add a recipient
						$mail->isHTML(true); // Set email format to HTML
		
						$mail->Subject = 'Audible code';
						$mail->Body    = "Your code is: $code
						<br />In order to redeem your code, please follow these steps:<br />
						<ol>
						    <li>Go to my book's page on " . '<a href="http://Audible.com" target="_blank" data-saferedirecturl="https://www.google.com/url?hl=en&amp;q=http://Audible.com&amp;source=gmail&amp;ust=1478416713165000&amp;usg=AFQjCNGgLSVb23Y6Wf8UwQpGdOYxIN82cg">Audible.com</a>: <a href="xxx" target="_blank">xxx</a>' . "</li>
							<li>Add the audiobook to your cart.</li>
							<li>Create a new " . '<a href="http://Audible.com" target="_blank" data-saferedirecturl="https://www.google.com/url?hl=en&amp;q=http://Audible.com&amp;source=gmail&amp;ust=1478416713165000&amp;usg=AFQjCNGgLSVb23Y6Wf8UwQpGdOYxIN82cg">Audible.com</a>' . " account or log in.</li>" .
							'<li>Enter the promo code and click "Redeem" on the cart page.</li>
							<li>To change the price from full price to $0.00, click the box next to "1 Credit" and click the "update" button to apply the credit to your purchase.</li>' .
							"<li>Complete checkout, and start listening to the free copy of the book.</li>
						</ol>";
						$mail->AltBody = "Your code is: $code
						In order to redeem your code, please follow these steps:
						1. Go to my book's page on Audible.com: xxx. Add the audiobook to your cart. 3. Create a new Audible account or log in. 4. Enter the promo code and click " . '"Redeem" on the cart page. 5. To change the price from full price to $0.00, click the box next to "1 Credit" and click the "update"' . " button to apply the credit to your purchase. 6. Complete checkout, and start listening to the free copy of the book.";
						?>
            
            // change the success message to let the user know that they should receive an email
						<script>$("#success_message").text("Thank you for signing up! You should receive an email with your Audible promo code shortly!")</script>
						
            <?php
            
            // if no unused code was found
					} else {
						
            // send me an email to let me know that the promotion needs to be ended
						$mail = new PHPMailer;
		
						//$mail->SMTPDebug = 3;                       // Enable verbose debug output
		
						// Set mailer to use SMTP
						$mail->isSMTP();
		
						// Specify SMTP server
						$mail->Host = 'xxx';
		
						$mail->Port = 465; // TCP port to connect to
						$mail->SMTPSecure = 'ssl'; // Enable TLS encryption, `ssl` also accepted
		
						$mail->SMTPAuth = true; // Enable SMTP authentication
						$mail->Username = 'xxx'; // SMTP username
						$mail->Password = 'xxx'; // SMTP password
		
						$mail->setFrom('xxx'); // Who is the message sent from?
						$mail->addAddress('xxx', 'xxx'); // Add a recipient
						$mail->isHTML(true); // Set email format to HTML
		
						$mail->Subject = 'Audible codes';
						$mail->Body    = 'All the Audible codes have been used.';
		
            // redirect the user to a page that apologises for there being no more codes left
						$url = 'sorry.html';
		
						// clear out the output buffer
						while (ob_get_status()) 
						{
						    ob_end_clean();
						}
		
						// no redirect
						header( "Location: $url" );
					}
		
				}		
		
			}
			if(!$mail->send()) {
		    				echo 'Message could not be sent.';
		    				echo 'Mailer Error: ' . $mail->ErrorInfo;
						} else {
		    				echo 'Message has been sent';
						}
		
		}
		?>
