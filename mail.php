<?php 
	require_once('PHPMailer/PHPMailerAutoload.php');
	define('MASTER_MAIL' , 'webmaster@bembe.tk');

	//function sendEmail(string $from, string $replyto, $to, $cc, $bcc, string $subject, string $message, $attachment, bool $isHTML)
	function sendEmail(array $parameters)
	{
		$mail = new PHPMailer;
		
		if(!isset($parameters['from']))
			$from = MASTER_MAIL;
		else
			$from = $parameters['from'];
		
		if(phpMailer::ValidateAddress($from))
			$mail->setFrom($from);
		
		if(!isset($parameters['to']))
			throw new Exception('Must be set at least 1 recipient');
		else 
			$to = $parameters['to'];
			
		if(is_array($to))
		{
			foreach($to as $i => $val){
				if(phpMailer::ValidateAddress($val))
					$mail->addAddress($val);     // Add a recipient
				else
					error_log("Invalid email address" . $val . ", not added to the TO list.");
			}
		}
		else if(phpMailer::ValidateAddress($to))
				$mail->addAddress($to);     // Add a recipient
			else
				throw new Exception("Recipient email " . $to . " not valid!");
		
		if(isset($parameters['cc']))
		{
			$cc = $parameters['cc'];
			if(is_array($cc))
			{
				foreach($cc as $i => $val){
					if(phpMailer::ValidateAddress($val))
						$mail->addCC($val);     // Add a cc
					else
						error_log("Invalid email address" . $val . ", not added to the CC list.");
				}
			}
			else if(phpMailer::ValidateAddress($cc))
				$mail->addCC($cc);     // Add a cc
		}
		
		if(isset($parameters['bcc']))
		{
			$bcc = $parameters['bcc'];
			if(is_array($bcc))
			{
				foreach($bcc as $i => $val){
					if(phpMailer::ValidateAddress($val))
						$mail->addBCC($val);     // Add a bcc
					else	
						error_log("Invalid email address" . $val . ", not added to the BCC list.");
				}
			}
			else if(phpMailer::ValidateAddress($bcc))
				$mail->addBCC($bcc);     // Add a cc 
		}

		if(!isset($parameters['subject']))
			$subject = "";
		else
			$subject = $parameters['subject'];
		$mail->Subject = $subject;
		
		if(!isset($parameters['message']))
			$message = "";
		else 
			$message = $parameters['message'];
		$mail->Body = $message;
		
		if(!isset($parameters['isHTML']))
			$isHTML = (strpos($parameters['isHTML'],"<HTML>") == 0);
		else
			$isHTML = $parameters['isHTML'];
		
		$mail->isHTML($isHTML);     // Set email format to HTML
		
		if(isset($parameters['replyto']))
		{
			$replyto = $parameters['replyto'];
			if(phpMailer::ValidateAddress($replyto))		
				$mail->addReplyTo($parameters['replyto']);
			else 
				error_log('Invalid ReplyTo address '. $replyto);
		}
			
		if(isset($parameters['attachment']))
		{
			$attachment = $parameters['attachment'];
			if(is_array($attachment))
			{
				foreach($attachment as $i=>$file)
				{
					$mail->addAttachment($file);
				}
			}
			else 
				$mail->addAttachment($attachment);
		}
		
		if(!$mail->send()) {
			throw new Exception('Error sending email. Error:'.$mail->ErrorInfo);
		}
		
		return SUCCESS;
	}
	
	function createMailParameters(): array
	{
		return arrya();
	}
?>