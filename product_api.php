<?php

class API {
//connect to db
$memcache = new Memcache() ;

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

public function get_request_method(){  //returns the type of request made
	
	return $_SERVER['REQUEST_METHOD'];
	
	}
		
		
private function cleanInputs($data){              //this method is used to extract information about a particular product(used for search and deletion)
			$clean_input = array();               
			if(is_array($data)){
				foreach($data as $k => $v){
					$clean_input[$k] = $this->cleanInputs($v);
				}
			}else{
				if(get_magic_quotes_gpc()){
					$data = trim(stripslashes($data));                      // Found it on stackoverflow, as I did not know how to do this part.
				}
				$data = strip_tags($data);
				$clean_input = trim($data);
			}
			return $clean_input;
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
		return $user_details ;
      
	  }
	else
		echo "invalid email address or password" ;
else 
   echo "email and/or password field is empty" ;
 }
}


private function getallproducts()
{
  if($this->get_request_method() != "GET"){         //confirm whether the request method is get otherwise no data is retrieved
				echo "incorrect request method" ;
			}
  else {			
        $key=md5("select * from products")         //for memcache
		$q=mysql_query("select * from products");  //products table contains all product related information(mentioned in the design doc.)

		$products=array() ;

		while($p=mysql_fetch_array($q,MYSQL_ASSOC))
			{	 
				$products[]=$p ;
   
			}
  
  $products=json_encode($products);                       //list of all products in json format
  $memcache->set($key,$products,TRUE,500) ;              //cache the result for 500 seconds
  return $products ;
        }
 }
 
 private function deleteproduct(){
			                                               // confirm the type of request
	if($this->get_request_method() != "DELETE"){
				echo "incorrect request method" ;
			}
	else {
			
		$del=$this->cleanInputs($_GET);
		$id = (int)$this->$del['id'];               //get the id of the product to be deleted.
		if($id > 0) {
		
		mysql_query("DELETE from products where id = $id") ;  //compare with id field in the database
		echo "record successfully deleted" ;
		
		}
		
		else
			  echo "no record with this id" ;
		}
 }
 
 private function getproduct(){                         //retrieve info about a single product
	if($this->get_request_method() != "GET"){         
				echo "incorrect request method" ;
			}
    else {
	
		$del = $this->cleanInputs($_GET);
	    $id = (int)$this->$del['id'];               //get the id of product to be found.
        if($id > 0) {
			$search_query=mysql_query("SELECT * from products where id = $id") ;	
			
			$search_result = mysql_fetch_array($search_query,MYSQL_ASSOC);
  
            $search_result=json_encode($search_result) ;
			return $search_result ;                         //return product info in json format
		}
		else 
			echo "no product with this id" ;
	} 
 }
 
 private function addproduct(){                        //function to add a new product to the database         
                                                       /* name,info,barcode,tags are "form" fields which give info about the product to be added
                                                          function to be called when form is submitted
													   */
	$Name = json_decode($_POST['name']);												   
	$Info = json_decode($_POST['info']);
    $Barcode = json_decode($_POST['barcode']);
	$Tags = json_decode($_POST['tags']);
    	
    $insert_query = "INSERT into products(id,name,info,barcode,tags) 
	                     values('','$Name','$Info','$Barcode','$Tags')";
    $execute_query = mysql_query($insert_query);
	
	echo "Record successfully inserted" ;
 
 }
 

 }


$api = new API;        //initialize the API class
$api->process_api();

?>