<?php
///PHP mailer overclass
class Email{
	static function send($message,$subject,$to,$from=null,$options=[]){
		$mail = new PHPMailer(true);
		if(!$from){
			$from = ['email'=>$_ENV['mail']['mail.from'],
				'name'=>$_ENV['mail']['mail.fromName']];
		}
		
		
		try{
			$mail->IsSMTP(true);
			if(is_array($to)){
				foreach($to as $address){
					$mail->AddAddress($address);
					
				}
			}else{
				$mail->AddAddress($to);
				#alternative way, in adding name
				#$mail->addAddress('joe@example.net', 'Joe User');   
			}

			$mail->SMTPAuth = true;

			$mail->Host = $_ENV['mail']['smtp.host'];
			$mail->Port = $_ENV['mail']['smtp.port'];
			$mail->Username = $_ENV['mail']['smtp.user'];
			$mail->Password = $_ENV['mail']['smtp.password'];
			if($_ENV['mail']['smtp.secure']){
				$mail->SMTPSecure = $_ENV['mail']['smtp.secure'];
			}
			
			if($options['attachments']){
				foreach($attachments as $attachment){
					$mail->addAttachment($attachment['file'],$attachment['name']);
				}
			}
			$mail->AddReplyTo($from['email'],$from['name']);
			$mail->From = $from['email'];
			$mail->FromName = $from['name'];
			$mail->Subject  = $subject;
			$mail->IsHTML(true);
			$mail->Body = $message;
			$mail->AltBody = strip_tags(\Filter::br2Nl($message));
			$mail->Send();
		}catch (Exception $e){
			throw $e;
		}
	}
}

?>