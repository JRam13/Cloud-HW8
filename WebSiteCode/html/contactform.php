<html>
<body>
	<h1>Add Contact</h1>

	<ul>
		<li style="display: inline"><a href="index.php">Contacts</a></li>
		<li style="display: inline"> | </li>
		<li style="display: inline"><a href="subscribe.php">Subscribe</a></li>
		<li style="display: inline"> | </li>
		<li style="display: inline"><a href="addcontact.php">Add Contact</a></li>
	</ul>

	<form action="contactform.php" method="POST">
		First Name: <input type="text" name="fname"><br />
		Last Name: <input type="text" name="lname"><br />
		<input type="submit" value="Submit">
	</form>

</body>
</html>

<?php

require_once 'awssdk/sdk.class.php';
 
// Set timezone to your local timezone (if it is not set in your php.ini)
date_default_timezone_set('America/Oregon');

$fname=$_POST['fname'];
$lname=$_POST['lname'];

if(!preg_match("/^[a-zA-Z'-]+$/",$fname)) { die ("Invalid First Name");} 
if(!preg_match("/^[a-zA-Z'-]+$/",$lname)) { die ("Invalid Last Name");} 

$item = $fname . " " . $lname;
$itemUrl = $fname . "_" . $lname;

$sdb = new AmazonSDB();
$putAttributesRequest["FirstName"] = array("value" => $fname); // Example add an attribute
$putAttributesRequest2["LastName"] = array("value" => $lname); // Example add an attribute
$response = $sdb->put_attributes('MyAddressBook', $item, $putAttributesRequest);
$response2 = $sdb->put_attributes('MyAddressBook', $item, $putAttributesRequest2);


//add SQS
// Send a message to the queue
$sqs = new AmazonSQS();
$body = $itemUrl.
	" "."

    <h1>My Address Book</h1>
	<h2>".$item."</h2>
	<img src='http://d3r6vrqzwazs3l.cloudfront.net/hlaurie.jpg' alt='logo' height='120' width='120'>
	<table border='1'>
	<tr>
	<td><strong>FirstName</strong></td>
	<td><strong>LastName</strong></td>
	</tr>
	<tr>
	<td>".$fname."</td>
	<td>".$lname."</td>
	</tr>
	</table>
    ";
$response = $sqs->send_message('https://sqs.us-east-1.amazonaws.com/283530117241/ContactQ', $body);

//upload to s3
//Instantiate the class
$s3 = new AmazonS3();
$bucket = 'contactsbucketsimpledb';
 
$filename = "mycontacts_".$itemUrl . ".html";

$body = "
	<br />
    <h2 style='text-align:center;'>Contact is being processed. Please check back later.</h2>
    <p>Hit back button and refresh the page. Usually takes 5 minutes.</p>
    ";
$response = $s3->create_object($bucket, $filename, array(
    'body' => $body,
    'acl'  => AmazonS3::ACL_PUBLIC,
    'contentType' => 'text/html'
));

// Success?
if($response->isOK()){
	echo 'Contact Successfully Added.';
}else{
	echo 'Please Try Again.';
}


?>