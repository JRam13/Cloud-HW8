<?php

//This is a script that runs a polling of the SQS.
//It updates the S3 with the html and sends a notification via SNS

//Please note that a Queue was already made in the SQS dashboard with 
//a 5 minute delay interval.

// Receive a message
$sqs = new AmazonSQS();
$responsesqs = $sqs->receive_message('https://sqs.us-east-1.amazonaws.com/283530117241/ContactQ');

//grab body and sqs information
if(!empty($responsesqs->body->ReceiveMessageResult) == 1){
	$receipt = $responsesqs->body->ReceiveMessageResult->Message->ReceiptHandle->to_string();
	$bodyHTML = $responsesqs->body->ReceiveMessageResult->Message->Body;

	//format body html
	$str = strtok($bodyHTML, " ");
	$len = strlen($str);
	$bodyFinal = substr($bodyHTML, $len, strlen($bodyHTML));

	//if body is not blank, save it to s3, delete it from the queue
	if(!empty($bodyHTML)){

		//upload to s3
		// Instantiate the class
		$s3 = new AmazonS3();
		$bucket = 'contactsbucketsimpledb';
		 
		$filename = "mycontacts_".$str.".html";

		$responses3 = $s3->create_object($bucket, $filename, array(
		    'body' => $bodyFinal,
		    'acl'  => AmazonS3::ACL_PUBLIC,
		    'contentType' => 'text/html'
		));

		//delete from queue
		$sqs->delete_message('https://sqs.us-east-1.amazonaws.com/283530117241/ContactQ', $receipt);

		//send notification
		$sns = new AmazonSNS();
	 
		//Get topic attributes
		$responsesns = $sns->publish(
	    'arn:aws:sns:us-east-1:283530117241:51083-updated',
	    "User has been added to the contacts <https://s3-us-west-2.amazonaws.com/contactsbucketsimpledb/".$filename.">",
	    array(
	        'Subject' => 'Contact Added'
	    ));

	}
}

?>