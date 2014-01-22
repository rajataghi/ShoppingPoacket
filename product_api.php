<?php

class API {
//connect to db
mysql_connect('localhost','root','') or die("cannot connect");

mysql_select_db('thedroidpeople') or die("cannot select");

//check if the called function exists. if yes, call it.

process_api(){
if(function_exists($_GET['method']))
  { 
   $_GET['method']() ;             //the method specified in the url call to the api
  
  }
  else
     echo "function not found" ;
}	 
//methods
private function login() {

$email = $_POST['email'] ;
$password = $_POST['password'] ;  //get login credentials of the user from the login form
$user_details=array() ;

 //basic validation
 if(!empty($email) and !empty($password)) {

    $sql= "SELECT * from members WHERE email='$email' and pass='$password'"; //members table contains info for all authenticated members
   
   if(mysql_num_rows($sql) > 0)                        
      { $user_details = mysql_fetch_array($sql,MYSQL_ASSOC);
  
        $user_details=json_encode($user_details) ;       //user details in json format
      
	  }
	else
		echo "invalid email address or password" ;
else 
   echo "email and/or password field is empty" ;
 }
}


private function getallproducts()
{
$q=mysql_query("select * from products");  //products table contains all product related information(mentioned in the design doc.)
$products=array() ;

 while($p=mysql_fetch_array($q,MYSQL_ASSOC))
   { 
     $products[]=$p ;
   
   }
  
  $products=json_encode($products); //list of all products in json format
 
}

}


$api = new API;        //initialize the API class
$api->process_api();

?>