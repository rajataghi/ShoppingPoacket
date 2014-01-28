<?php
	class API
	{
		public
		function __construct()
		{   //connect to the database
			mysql_connect('localhost','root','') or die("cannot connect");
			mysql_select_db('thedroidpeople') or die("cannot select");
			$mc= new Memcache();
			$mc->connect('localhost', 11211);
		}
        
		/*
		 * Public method for accessing the api.
		 * This method calls the specific method based on the query string
		 *
		 */
		 
		public
		function process_api()
		{   
			
			if(function_exists($_GET['method']))
			{
				$_GET['method']() ;
			}
			else     echo "function not found" ;
		}
		
        /*
		 * Public function to get the request method. 
		 *
		 */
		
		public
		function get_request_method()
		{
			return $_SERVER['REQUEST_METHOD'];
		}
		
        /*
		 * method to clean information about a particular product. 
		 * product information contains backslashes(escape sequences) and html tags,
		 * removes these to clearly identify product attributes and their values
		 */
	       
		private
		function cleanInputs($data)
		{
			$clean_input = array();
			
			if(is_array($data))
			{
				foreach($data as $k => $v)
				{
					$clean_input[$k] = $this->cleanInputs($v);
				}

			}
			else
			{
				
				if(get_magic_quotes_gpc())
				{
					$data = trim(stripslashes($data));
				}
                //remove various tags which may be part of the product info,like html tags.
				$data = strip_tags($data);
				$clean_input = trim($data);
			}

			return $clean_input;
		}
        
		/* 
		 *	Simple login function
		 *  Login must be by POST method
		 *  email : <USER EMAIL>
		 *  password : <USER PASSWORD>
		 */
		 
		private
		function login()
		{
			$email = $_POST['email'] ;
			$password = $_POST['password'] ;
			$user_details=array() ;
			
			//Input validations
			if(!empty($email) and !empty($password))
			{
				$sql= "SELECT * from members WHERE email='$email' and pass='$password'" ;
				
				if(mysql_num_rows($sql) > 0)
				{
					$user_details = mysql_fetch_array($sql,MYSQL_ASSOC);
					//convert user details to JSON format
					$user_details=json_encode($user_details) ;   
					return $user_details ;
				}
				else echo "invalid email address or password" ;
				else    echo "email and/or password field is empty" ;
			}

		}
		
		/*
		 * method to get information about all products. 
		 * if query is present in cache,db is not accessed
		 *
		 */

		private
		function getallproducts()
		{
			//validation if the request method is GET
			if($this->get_request_method() != "GET")
			{
				echo "incorrect request method" ;
			}
			else
			{
				$products=array() ;
				//check if the query is "memcached"
				$products = $mc->get("products") ;
				
				if($products === false)
				{  //when query is not in cache,access db
					$q=mysql_query("select * from products");
					while($p=mysql_fetch_array($q,MYSQL_ASSOC))
					{
						$products[]=$p ;
					}
                    //add the query to cache for the next 100sec
					$mc->set("products",serialize($products),0,100) ;
				}
				else
				{   // use query from cache
					$products2= unserialize($products) ;
					$products=$products2 ;
				}
                //encode product info in JSON format
				$products=json_encode($products);
				return $products ;
			}

		}
        
		/*
		 * method to delete a product from the database. 
		 * product id in the query is compared with id's in the db
		 *
		 */
		 
		private
		function deleteproduct()
		{
			//validation if the request method is DELETE
			if($this->get_request_method() != "DELETE")
			{
				echo "incorrect request method" ;
			}
			else
			{   //get product id from the request
				$del=$this->cleanInputs($_GET);
				$id = (int)$this->$del['id'];
				
				if($id > 0)
				{   //delete product from db
					mysql_query("DELETE from products where id = $id") ;
					echo "record successfully deleted" ;
				}
				else  echo "no record with this id" ;
			}

		}
		
		/*
		 * method to get information about a particular product. 
		 *
		 */

		private
		function getproduct()
		{
			
			if($this->get_request_method() != "GET")
			{
				echo "incorrect request method" ;
			}
			else
			{   //get product id from the request
				$del = $this->cleanInputs($_GET);
				$id = (int)$this->$del['id'];
				
				if($id > 0)
				{   //retrieve information about the product from the db
					$search_query=mysql_query("SELECT * from products where id = $id") ;
					$search_result = mysql_fetch_array($search_query,MYSQL_ASSOC);
					//encode product information is JSON format
					$search_result=json_encode($search_result) ;
					return $search_result ;
				}
				else echo "no product with this id" ;
			}

		}
		
		/*
		 * method to add a new product to the db. 
		 *
		 */

		private
		function addproduct()
		{   //decode product info from the request
			$Name = json_decode($_POST['name']);
			$Info = json_decode($_POST['info']);
			$Barcode = json_decode($_POST['barcode']);
			$Tags = json_decode($_POST['tags']);
			//add new product to the database
			$insert_query = "INSERT into products(id,name,info,barcode,tags) 
	                     values('','$Name','$Info','$Barcode','$Tags')";
			$execute_query = mysql_query($insert_query);
			echo "Record successfully inserted" ;
		}

	}
    // Initiate Library
	
	$api = new API;
	$api->process_api();
	?>