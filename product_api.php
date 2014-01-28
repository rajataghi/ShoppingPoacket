<?php
	class API
	{
		public
		function __construct()
		{ //connect to the database
			mysql_connect('localhost','root','') or die("cannot connect");
			mysql_select_db('thedroidpeople') or die("cannot select");
			$mc= new Memcache();
			$mc->connect('localhost', 11211);
		}
        
		/*
		 * Public method for accessing the api.
		 * This method calls the method based on the query string
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

		public
		function get_request_method()
		{
			return $_SERVER['REQUEST_METHOD'];
		}

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

				$data = strip_tags($data);
				$clean_input = trim($data);
			}

			return $clean_input;
		}

		private
		function login()
		{
			$email = $_POST['email'] ;
			$password = $_POST['password'] ;
			$user_details=array() ;
			
			if(!empty($email) and !empty($password))
			{
				$sql= "SELECT * from members WHERE email='$email' and pass='$password'" ;
				
				if(mysql_num_rows($sql) > 0)
				{
					$user_details = mysql_fetch_array($sql,MYSQL_ASSOC);
					$user_details=json_encode($user_details) ;
					return $user_details ;
				}
				else echo "invalid email address or password" ;
				else    echo "email and/or password field is empty" ;
			}

		}

		private
		function getallproducts()
		{
			
			if($this->get_request_method() != "GET")
			{
				echo "incorrect request method" ;
			}
			else
			{
				$products=array() ;
				$products = $mc->get("products") ;
				
				if($products === false)
				{
					$q=mysql_query("select * from products");
					while($p=mysql_fetch_array($q,MYSQL_ASSOC))
					{
						$products[]=$p ;
					}

					$mc->set("products",serialize($products),0,100) ;
				}
				else
				{
					$products2= unserialize($products) ;
					$products=$products2 ;
				}

				$products=json_encode($products);
				return $products ;
			}

		}

		private
		function deleteproduct()
		{
			
			if($this->get_request_method() != "DELETE")
			{
				echo "incorrect request method" ;
			}
			else
			{
				$del=$this->cleanInputs($_GET);
				$id = (int)$this->$del['id'];
				
				if($id > 0)
				{
					mysql_query("DELETE from products where id = $id") ;
					echo "record successfully deleted" ;
				}
				else  echo "no record with this id" ;
			}

		}

		private
		function getproduct()
		{
			
			if($this->get_request_method() != "GET")
			{
				echo "incorrect request method" ;
			}
			else
			{
				$del = $this->cleanInputs($_GET);
				$id = (int)$this->$del['id'];
				
				if($id > 0)
				{
					$search_query=mysql_query("SELECT * from products where id = $id") ;
					$search_result = mysql_fetch_array($search_query,MYSQL_ASSOC);
					$search_result=json_encode($search_result) ;
					return $search_result ;
				}
				else echo "no product with this id" ;
			}

		}

		private
		function addproduct()
		{
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
    // Initiate Library
	
	$api = new API;
	$api->process_api();
	?>