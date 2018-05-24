<?php

	$phonenumber = $_GET['MSISDN'];  
    $sessionID = $_GET['sessionId'];  
    $servicecode = $_GET['serviceCode'];  
    $ussdString = $_GET['text'];


/*
	//NGROK
	$user_phonenumber = $_POST['phoneNumber'];
    $phonenumber = str_replace("+", "", $user_phonenumber); 	//remove the "+" in phone number
    $sessionID = $_POST['sessionId'];  
    $servicecode = $_POST['serviceCode'];  
    $ussdString = $_POST['text'];
*/
	require_once('settings.php');

	//Current user username
	$username = "lj".$phonenumber;    

	// Create connection **MYSQLI
	$conn = new mysqli($servername, $dbase_username, $password, $dbname);

	// Check connection
	if ($conn->connect_error) {
	    die("Connection failed: " . $conn->connect_error);
	}

	$level =0; 

	if($ussdString != ""){  
	    $ussdString=  str_replace("#", "*", $ussdString);  
	    $ussdString_explode = explode("*", $ussdString);
	    $level = count($ussdString_explode);  
    }

    //echo ussd_text
    function ussd_proceed ($ussd_text){  
    	echo $ussd_text;  
	}
	
	//If user exists or not
    $query = "SELECT * from subscribers where phone_Number ='$phonenumber'";
	if ($result=mysqli_query($conn,$query)){
		$row = $result->fetch_assoc();
		if(mysqli_num_rows($result) > 0){
			$isSwahili = $row['Swahili'];
			//View my account menu			
			if ($level==0){
				displaymenu($isSwahili);
			}    
		    if ($level>0){  
			    switch ($ussdString_explode[0]) {  
					case 1: //Update stock 
						if($row['business_Type'] == 'Kiosk'){
							update_purchases_kiosk($ussdString_explode,$phonenumber, $username, $conn, $isSwahili);
						}else
				    		update_purchases($ussdString_explode,$phonenumber, $username, $conn);
				    	break;  
				    case 2:  //Update sales
					    update_sales($ussdString_explode,$phonenumber, $username, $conn);  
					    break;
				    case 3:  //Profits and losses
				    	profit_losses($ussdString_explode,$phonenumber, $username, $conn);				   
				    	break; 
				    case 4: 
						//Loans 
						if($isSwahili == 1)
						$ussd_text = "END \n HUNA USAHIHI WA MIKOPO YA LEJA KWA WAKATI HUU. LAZIMA UTUMIE LEJA KWA ZAIDI YA MIEZI SITA\nASANTE.";
						else
				   		$ussd_text="END \n YOU ARE NOT LEGIBLE FOR LEJA LOANS AT THE MOMENT. YOU MUST HAVE USED LEJA FOR MORE THAN 6 MONTHS\nTHANK YOU.";  
			   			ussd_proceed($ussd_text);
				    	break;
				    case 5:
				    	//Statements
				        statements($ussdString_explode,$phonenumber, $username, $conn);
				    	break;
				    case 6:
				    	//MyAccount
				        //display_my_account_menu($ussdString_explode,$phonenumber, $username, $conn);
						getHelp($ussdString_explode,$phonenumber, $username, $conn);
				    	break;
				    case 0:
				    	die();
				    	break;
				    default:
				    	$ussd_text = "INVALID OPTION";
						ussd_proceed($ussd_text);
				    	break; 
				}  //End switch
			}  
			
		}
		else{
		  	//Registration menu
		  	 if ($level==0){  
			    	$ussd_text="CON \nWELCOME TO LEJA.\n1. REGISTER\n2. ABOUT LEJA\n3. KISWAHILI";  
			   		ussd_proceed($ussd_text);    
			    } 
				
				//First selection either registration or about
				if ($level>0){  
				    switch ($ussdString_explode[0])  
					    {  
						case 1:  
							$isSwahili = 0;
						    register($ussdString_explode,$phonenumber, $conn, $isSwahili);  
						    break;  
					    case 2:  
						    about($ussdString_explode,$phonenumber);
						    
						    break;
					    case 3:  
				   			//Change langauage
							$isSwahili = 1;
							register($ussdString_explode,$phonenumber, $conn, $isSwahili);  
							break;
						default:						
							$ussd_text = "INVALID CHOICE.";
							ussd_proceed($ussd_text);
				    }//End switch  
			    }  	
			}//End else
		}
		else{
		    $ussd_text = "Query failed";
			ussd_proceed($ussd_text);
		}



	function displaymenu($isSwahili){  
		if ($isSwahili == 1){
			$ussd_text = "CON \n1:UNUNUZI \n2: MAUZO\n3: FAIDA NA KUPOTEZA\n4: MIKOPO\n5: TAARIFA\n6: MSAADA\n00: TOKA";
		}else
			$ussd_text="CON \n1: PURCHASES\n2: SALES\n3: PROFITS & LOSSES \n4: LOANS \n5: STATEMENTS \n 6: HELP \n00: EXIT";  
		ussd_proceed($ussd_text);  
	}

	function displayNewMemberMenu(){
		$ussd_text = "CON \nWE ARE HAPPY TO SEE YOU HERE.\n1. FIRST PURCHASE \n00. EXIT";
		ussd_proceed($ussd_text);
	}

	function display_my_account_menu($details,$phone, $active_user, $conne){
		$sql = "SELECT Name, id_Number, business_Type, Swahili FROM subscribers WHERE username = '$active_user";
		$result = $conne->query($sql);
		if ($result->num_rows > 0){
			$row = fetch_assoc(); 
			$ussd_text = "END <br>Account Details<br>";
			$ussd_text .= $row["Name"]."<br>";
			$ussd_text .= "Id Number: ".$row["id_Number"]."<br>";
			$ussd_text .= "Business Type: ".$row["business_Type"]."<br>";
			
			ussd_proceed($ussd_text);
		}
	}

	function register($details,$phone, $conne, $isSwahili){      
		if (count($details)==1){   
			if($isSwahili == 1)
				$ussd_text="CON \n EKEZA MAJINA YAKO:";  
			else 
				$ussd_text="CON \n ENTER YOUR NAMES:";
			ussd_proceed($ussd_text);
 
		} 
		else if(count($details) == 2){
			  
			if($isSwahili == 1)
				$ussd_text = "CON EKEZA NAMBARI YA KITAMBULISHO:\n";  
			else 
				$ussd_text="CON ENTER ID NUMBER:\n";
			ussd_proceed($ussd_text);
		}
		else if(count($details) == 3){	 
			if($isSwahili == 1)
				$ussd_text= "CON EKEZA AINA YA BIASHARA:\n";  
			else 
				$ussd_text="CON ENTER TYPE OF BUSINESS:\n";  
			ussd_proceed($ussd_text);  
		}
		else if(count($details) == 4){  
			$name=$details[1];			
			$id_number=$details[2];
			$business_type=$details[3];    
			

			//Validate and sanitize
			if(!filter_var($name, FILTER_SANITIZE_STRING) === TRUE){
				$name = NULL;
			}
			if(!filter_var($id_number, FILTER_VALIDATE_INT) === TRUE){
				$id_number = NULL;
			}
			if(!filter_var($business_type, FILTER_SANITIZE_STRING) === TRUE){
				$business_type = NULL;
			}


			//============   Tablename    ===================
			$username = "lj".$phone;

			//=================Write into database all the details=========================== 
			$sql = "INSERT INTO subscribers (Name, phone_Number, id_Number, business_Type, username, registration_Platform, Swahili) 
					VALUES ('$name', '$phone', '$id_number', '$business_type' , '$username', 'MOBILE', '$isSwahili')";
			if($conne->query($sql) == TRUE){
				if ($business_type == 'kiosk'){
					create_table_kiosk($phone, $conne);
				}else if($business_type == 'mama mboga'){
					create_table_mamamboga($phone, $conne);
				}else if($business_type == 'hotel'){
					create_table_hotel($phone, $conne);
				}else if($business_type == 'mtumba'){
					create_table_mtumba($phone, $conne);
				}else if($business_type == 'movie'){
					create_table_movie($phone, $conne);
				}else{
					create_table($phone, $conne);
				}

				$sql = "SELECT * FROM subscribers where username = '$active_user'";
				$result = $conn->query(sql);
				if($results->num_rows > 1){
					$row = fetch_assoc();
					$isSwahili=$row['Swahili'];
				}
				
				if($isSwahili == 1){
					$message = "Asante $name kwa kuchagua Leja.\nWewe ni mwanachama mpya wetu.";
					$sms_message = "Mpenzi $name, \nTumefurahia kuwa nawe hapa.\nUnaweza kupata huduma zetu kwa kubonyeza *384*567#\nPata habari zaidi kutuhusu kwa kwenda kwa mtandao tembelea https://pundojnr.github.io/leja_USIU\n Timu ya Leja!";
				}else{
					$message = "Thank you $name for choosing Leja.\nYou are our new member.";
					$sms_message = "Dear $name, \nWe are excited to have you on the App!\nAccess our service through *384*567# for delightful services.\nHappy sales! Leja Team.";
				}
				sendSMS($phone, $sms_message);

				echo "END ".$message;
			}
			else{
				echo "error: ".$sql ."\n" .$conne->error;
			}
		}  
	}
	function create_table_mamamboga($phone, $conne){
		$tableName = "lj" .$phone;
		$sql = "CREATE TABLE $tableName (
			_date TIMESTAMP,
			id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			purchases INT(30),
			veges INT(30),
			tomatoes INT (30),
			onions INT (30),
			cereals INT(30),
			fruits INT(30),
			others INT (30),
			expenditure INT(100),
			sales INT (100),
			balance INT (100)
			)";
		if ($conne->query($sql) === TRUE) {
		
			$sql = "INSERT INTO $tableName (purchases, veg, tomatoes, onions, cereals, fruits, others, expenditure, sales, balance) VALUES ('0','0','0','0','0','0','0','0','0','0')";
			
		   if($conne->query($sql)== TRUE){

		   }else{
			   echo "error".$sql."\n" .$conne->error;
		   }
		}else{
			echo"error creating table" .$conne->error;
		}
	}

	function create_table_kiosk($phone, $conne){
		$tableName = "lj".$phone;
    	$sql = "CREATE TABLE $tableName (
    		_date TIMESTAMP,
			id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
			purchases INT(30),
			bread INT(30),
			milk INT(30),
			eggs INT(30),
			expenditure INT(100),
			sales INT(100),
			balance INT(100)
			)";
		if ($conne->query($sql) === TRUE) {
		     //echo "Table for $tableName is successfully created";
		     $sql = "INSERT INTO $tableName (purchases, bread, milk, eggs, expenditure,sales,balance) VALUES ('0','0','0','0','0','0','0')"; 

		    if($conne->query($sql) == TRUE){
		        //echo "successfully inserted";
		    }
		    else{
		        echo "error: ".$sql ."\n" .$conne->error;
		    }
		} 
		else {
		    echo "Error creating table: " . $conne->error;
		}	
	}

	function create_table($phone, $conne){
    	$tableName = "lj".$phone;
    	$sql = "CREATE TABLE $tableName (
    		_date TIMESTAMP,
			id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
			purchases INT(30),
			expenditure INT(100),
			sales INT(100),
			balance INT(100)
			)";
		if ($conne->query($sql) === TRUE) {
		     //echo "Table for $tableName is successfully created";
		     $sql = "INSERT INTO $tableName (purchases,expenditure,sales,balance) VALUES ('0','0','0','0')"; 

		    if($conne->query($sql) == TRUE){
		        //echo "successfully inserted";
		    }
		    else{
		        echo "error: ".$sql ."\n" .$conne->error;
		    }
		} 
		else {
		    echo "Error creating table: " . $conne->error;
		}
    }

    function about(){
		if($isSwahili == 1)
    		$ussd_text="END <br>Leja ni mfumo wa usimamizi wa hesabu wa USSD. Leja inakusaidia kuweka kumbukumbu na kufanya hesabu ya biashara yako ili kuhakikisha urahisi na faida.<br>Tembelea http://www.leja.co.ke kujifunza zaidi";   
		else 
			$ussd_text="END \nLeja is a USSD based inventory management system. Leja helps you keep records and make calculation of your business to ensure your convenience and lots of profits.\nVisit https://pundojnr.github.io/leja_USIU to learn more";   
		ussd_proceed($ussd_text); 

	}
	
	function update_purchases_kiosk($details,$phone, $active_user, $conne, $isSwahili){		
	    if (count($details)==1){ 
			if($isSwahili == 1)
		    	$ussd_text="CON \n Ingiza thamani ya mikate uliyouza leo: ";  
			else 
		    	$ussd_text="CON \n Bread value sold today? ";  
			ussd_proceed($ussd_text);  
	    }  
	    else if (count($details)==2){  
			if($isSwahili == 1)
		   		$ussd_text="CON \n Thamani ya maziwa uliyouza leo";  
			else 
		    	$ussd_text="CON \nMilk value sold today?";  
			ussd_proceed($ussd_text);  
	    }   
	    else if (count($details)==3){  
			if($isSwahili == 1)
		   		$ussd_text="CON \n Thamani ya mayai uliyouza leo";  
			else 
		    	$ussd_text="CON \nEggs value sold today?";  
			ussd_proceed($ussd_text);  
		}
		else if (count($details)==4){
			if($isSwahili == 1)
				$ussd_text="CON \n Thamani ya sukari uliyouza leo";
			else
				$ussd_text="CON \n Sugar value sold today";
			ussd_proceed($ussd_text);
		}  

	    else if(count($details) == 5){  
		    $bread=$details[1];  
		    $milk=$details[2];
			$eggs=$details[3];
			$sugar=$details[4];


		$sql = "SELECT  balance FROM $active_user ORDER BY id DESC LIMIT 1";
		$result = $conne->query($sql);
		if ($result->num_rows > 0) {
				$row = $result->fetch_assoc();
				//Calculations
			    $t_bal = $row["balance"] + $bread + $milk + $eggs + $sugar;
			} else {
			    echo "0 results";
			}
	      
		    $sql = "INSERT INTO $active_user (bread,milk, eggs,balance) VALUES ('$bread','$milk', '$eggs','$sugar','$t_bal')"; 

		    if($conne->query($sql) == TRUE){
				if($isSwahili == 1)
		        	$ussd_text="END \n Umeandika manunuzi kwa mafanikio";  
				else 
		       		 $ussd_text="END \nYou have successfully recorded your purchases";  
				ussd_proceed($ussd_text); 
		    }
		    else{
		        echo "error: ".$sql ."\n" .$conne->error;
		    }
	    }  
	}
	
function update_purchases_mamamboga($details,$phone,$active_user,$conne){
	if (count($details)==1){
		if($isSwahili == 1)
			$ussd_text = "CON \n Thamani ya mboga ulizouza leo: ";
		else
			$ussd_text = "CON \n Value of vegetables sold today: ";
		ussd_proceed($ussd_text);
	}else if (count($details)==2){
		if($isSwahili == 1)
			$ussd_text = "CON \n Thamani ya nyanya uliouza leo: ";
		else
			$ussd_text = "CON \n Value of tomatoes sold today: ";
		ussd_proceed($ussd_text);
	}
	else if (count($details)==3){
		if($isSwahili == 1)
			$ussd_text = "CON \n Thamani ya vitungu ulivyouza leo: ";
		else
			$ussd_text = "CON \n Value of onions sold today: ";
		ussd_proceed($ussd_text);
	}else if (count($details)==4){
		if ($isSwahili == 2)
			$ussd_text = "CON \n Thamani ya nafaka uliyouza leo: ";
		else
			$ussd_text = "CON \n Value of cereals sold today: ";
		ussd_proceed($ussd_text);
	} else if (count($details)==5){
		if ($isSwahili == 1)
			$ussd_text = "CON \n Thamani ya matunda uliyouza leo: ";
		else
			$ussd_text = "CON \n Value of fruits sold today: ";
		ussd_proceed($ussd_text);
	}else if (count($details)==6){
		if($isSwahili == 1)
			$ussd_text = "CON \n Thamani ya bidhaa zingine ulizouza kwa jumla: ";
		else
			$ussd_text = "CON \n Value other goods sold in total: ";
		ussd_proceed($ussd_text);
	}
	else if(count($details) == 7){
		$veges = $details[1];
		$tomatoes = $details[2];
		$onions = $details[3];
		$cereals = $details[4];
		$fruits = $details[5];
		$others = $details[6];
	}
	$sql = "SELECT  balance FROM $active_user ORDER BY id DESC LIMIT 1";
	$result = $conne->query($sql);
	if ($result->num_rows > 0) {
			$row = $result->fetch_assoc();
			//Calculations
			$t_bal = $row["balance"] + $veges + $tomatoes + $onions + $cereals + $fruits + $others;
		} else {	
			echo "0 results";
	 $sql = "INSERT INTO $active_user (veges,tomatoes,onions,cereals,fruits,others,balance) VALUES ('$veges','$onions','$cereals','$fruits','$others','$balance')";
		
		if($conne->query($sql) == TRUE){
			if($isSwahili == 1)
				$ussd_text="END \n Umeandika manunuzi kwa mafanikio";  
			else 
					$ussd_text="END \nYou have successfully recorded your purchases";  
			ussd_proceed($ussd_text); 
		}
		else{
			echo "error: ".$sql ."\n" .$conne->error;
		}
	}

	function update_purchases_hotel($details,$phone,$active_user,$conne){
		if (count($details)==1){
			if ($isSwahili == 1)
				$ussd_text ="Thamani ya Chapati uliyouza";
			else
				$ussd_text ="Cost of Chapati sold";
			ussd_proceed($ussd_text);
		}else if (count($details)==2){
			if($isSwahili == 1)
				$ussd_text = "Thamani ya ugali uliyouza";
			else
				$ussd_text = "Cost of Ugali sold";
			ussd_proceed($ussd_text);
		}else if (count($details)==3){
			if($isSwahili == 1)
				$ussd_text = "Thamani ya mboga uliyouza";
			else
				$ussd_text = "Cost of Greens sold";
			ussd_proceed($ussd_text);
		}else if (count($details)==4){
				if($isSwahili == 1)
					$ussd_text = "Thamani ya wali uliyouza";
				else
					$ussd_text = "Cost of Rice sold";
				ussd_proceed($ussd_text);
		}else if (count($details) == 4){
			if ($isSwahili == 1)
				$ussd_text = "Thamani ya nyama uliyouza";
			else
				$ussd_text = "Cost of Meat sold";
			ussd_proceed($ussd_text);
		}else if (count($details)==5){
			if($isSwahili == 1)
				$ussd_text = "Thamani ya vinywaji uliyouza";
			else
				$ussd_text = "Cost of drinks sold";
			ussd_proceed($ussd_text);
		}
		else if (count($details)== 6){

		}
		else if (count($details)==7){
			$chapati = $details[1];
			$ugali = $details[2];
			$rice = $details[3];
			$greens = $details[4];
			$meat = $details[5];
			$drinks = $details[6];
		}
		$sql = "SELECT  balance FROM $active_user ORDER BY id DESC LIMIT 1";
		$result = $conne->query($sql);
		if ($result->num_rows > 0) {
				$row = $result->fetch_assoc();
				//Calculations
				$t_bal = $row["balance"] + $chapati + $ugali + $rice + $greens + $meat + $drinks ;
			} else {	
				echo "0 results";
		 $sql = "INSERT INTO $active_user (chapati,ugali,rice,greens,meat,drinks,balance) VALUES ('$chapati','$ugali','$rice','$greens','$meat','$drinks','$balance')";
			
			if($conne->query($sql) == TRUE){
				if($isSwahili == 1)
					$ussd_text="END \n Umeandika manunuzi kwa mafanikio";  
				else 
						$ussd_text="END \nYou have successfully recorded your purchases";  
				ussd_proceed($ussd_text); 
			}
			else{
				echo "error: ".$sql ."\n" .$conne->error;
			}
		}
	

    function update_purchases($details,$phone, $active_user, $conne){
	    if (count($details)==1){ 
			if($isSwahili == 1)
		    	$ussd_text="CON \n Ingiza thamani ya bidhaa ulizonunulia leo: ";  
			else 
		    	$ussd_text="CON \nEnter value of the goods you have bought today: ";  
			ussd_proceed($ussd_text);  
	    }  
	    else if (count($details)==2){  
			if($isSwahili == 1)
		   		$ussd_text="CON \n Matumizi ya ziada kwa bidhaa (kwa mfano usafirishaji)";  
			else 
		    	$ussd_text="CON \nExtra expenditure on goods(eg. transportation)";  
			ussd_proceed($ussd_text);  
	    }  

	    else if(count($details) == 3){  
		    $purchases=$details[1];  
		    $expenditure=$details[2];

		    //Validate data
		    if(filter_var($purchases, FILTER_VALIDATE_INT) === FALSE){
		    	$purchases = NULL;
		    }
		    if(filter_var($expenditure, FILTER_VALIDATE_INT) === FALSE){
		    	$expenditure = NULL;
		    } 

		$sql = "SELECT  balance FROM $active_user ORDER BY id DESC LIMIT 1";
		$result = $conne->query($sql);
		if ($result->num_rows > 0) {
				$row = $result->fetch_assoc();
				//Calculations
			    $t_bal = $row["balance"] + $purchases + $expenditure;
			} else {
			    echo "0 results";
			}
	      
		    $sql = "INSERT INTO $active_user (purchases,expenditure,balance) VALUES ('$purchases','$expenditure','$t_bal')"; 

		    if($conne->query($sql) == TRUE){
				if($isSwahili == 1)
		        	$ussd_text="END \n Umeandika manunuzi kwa mafanikio";  
				else 
		       		 $ussd_text="END \nYou have successfully recorded your purchases";  
				ussd_proceed($ussd_text); 
		    }
		    else{
		        echo "error: ".$sql ."\n" .$conne->error;
		    }
	    }  
	}
	

	function update_sales($details,$phone, $active_user, $conne){
	     if (count($details)==1){
			if($isSwahili == 1)
				$ussd_text="END Ingiza thamani ya jumla ya bidhaa zote kuuzwa leo"; 
			else 
		    	$ussd_text="CON \nEnter the total value of all goods sold today";  
			ussd_proceed($ussd_text); 
	    }   

	    else if(count($details) == 2){  
		    $sales=$details[1]; 

		     //Validate data
		    if(filter_var($sales, FILTER_VALIDATE_INT) === FALSE){
		    	$purchases = NULL;
		    }

		$sql = "SELECT  balance FROM $active_user ORDER BY id DESC LIMIT 1";
		$result = $conne->query($sql);
		if ($result->num_rows > 0) {
				$row = $result->fetch_assoc();
				//calculations
			    $t_bal = $row["balance"] - $sales;
			} else {
			   // echo "0 results";
			}	      
		    $sql = "INSERT INTO $active_user (sales,balance) VALUES ( '$sales','$t_bal')"; 

		    if($conne->query($sql) == TRUE){
				
			if($isSwahili == 1)
				$ussd_text="END \n Umeandika kumbukumbu yako kwa ufanisi";  
			else 
				$ussd_text="END \nYou have successfully recorded your sales";  
			ussd_proceed($ussd_text); 
		    }
		    else{
		        echo "error: ".$sql ."\n" .$conne->error;
		    }
	    }  
	}

	function profit_losses($details,$phone, $active_user, $conne){
		$sql = "SELECT balance FROM $active_user ORDER BY id DESC LIMIT 1";
		$result = $conne->query($sql);
		if ($result->num_rows > 0) {
				$row = $result->fetch_assoc();
				//calculations
			    $t_bal = $row["balance"];
			    if($t_bal > 0){
					if($isSwahili == 1)
						$ussd_text="END \n Una POSA ya: $t_bal. uza bidhaa zaidi na ulipate faida.";
					else
			    		$ussd_text="END \nYou have a LOSS of: $t_bal. Sell more products and earn.";  
		   			ussd_proceed($ussd_text);
			    }elseif ($t_bal < 0){
					if($isSwahili == 1)
						$ussd_text="END \n Una FAIDA ya .".-1*$t_bal. "uzaa bidhaa zaidi ili upate zaidi.";
					else
						$ussd_text="END \nYou have a PROFIT of: ".-1*$t_bal."Keep selling your goods and earn more";  
		   			ussd_proceed($ussd_text);
			    }else{
					if($isSwahili == 1)
						$ussd_text = "END \n Faida na hasara yako ni sawa";
					else
			    		$ussd_text = "END \nYour profit and loss is balanced";
			    	ussd_proceed($ussd_text);
			    }
			} else {
				$ussd_text = "No results";
				ussd_proceed($ussd_text);
		}
	}

	function statements($details,$phone, $active_user, $conne){
		//Fetch email address of user
/*		$sql = "SELECT * FROM subscribers WHERE phone_Number = '$phone'";

		$result = $conne->query($sql);
		if(!$result){
			$ussd_text = "END Ooops!\n
						We cannot find your email. $phone
					";
			ussd_proceed($ussd_text);
        }else{
        	$row=mysqli_fetch_array($result);
        	$to = $row['email'];
        }
		*/
		$sql = "SELECT * FROM $active_user ORDER BY id";
		$result =$conne->query($sql);
		if(!$result)
		{
			$ussd_text = "END No results";
			ussd_proceed($ussd_text);
		}

		$no = 1;
		$total_sales = 0;
		$total_purchases = 0;
		$total_expenditure = 0;
		
		//mail to:
		$subject = "Statements for your business";

		$message = "<html><body>";
		$message .= '<h3 style="margin: 1em 0 0.5em 0;color: #788699;font-size: 22px;line-height: 40px;font-weight: normal;text-transform: uppercase;font-family: Orienta, sans-serif;letter-spacing: 1px;font-style: italic;">
				Here is your full statement
				</h3>';
		$message .= '<table style="font-family: verdana,arial,sans-serif;font-size:11px;color:#333333;border-width: 1px;border-color: #999999;border-collapse: collapse;">
						<tr>
							<th style="background:#b5cfd2;border-width: 1px;padding: 8px;border-style: solid;border-color: #999999;">Date</th>
							<th style="background:#b5cfd2;border-width: 1px;padding: 8px;border-style: solid;border-color: #999999;">Purchases</th>
							<th style="background:#b5cfd2;border-width: 1px;padding: 8px;border-style: solid;border-color: #999999;">Expenditure</th>
							<th style="background:#b5cfd2;border-width: 1px;padding: 8px;border-style: solid;border-color: #999999;">Sales</th>
							<th style="background:#b5cfd2;border-width: 1px;padding: 8px;border-style: solid;border-color: #999999;">Balance</th>
						</tr>
				';
		
		while ($row=mysqli_fetch_array($result)){		
			$message .= '
				<tr>
					<td style="background:#F5F5F5;border-width: 1px;padding: 8px;border-style: solid;border-color: #999999;">'.$row['_date'].'</td>
		            <td style="background:#F5F5F5;border-width: 1px;padding: 8px;border-style: solid;border-color: #999999;">'.$row['purchases'].'</td>
		            <td style="background:#F5F5F5;border-width: 1px;padding: 8px;border-style: solid;border-color: #999999;">'.$row['expenditure'].'</td>
		            <td style="background:#F5F5F5;border-width: 1px;padding: 8px;border-style: solid;border-color: #999999;">'.$row['sales'].'</td>
		            <td style="background:#F5F5F5;border-width: 1px;padding: 8px;border-style: solid;border-color: #999999;">'.$row['balance'].'</td>
	           	</tr>';
           	$total_purchases += $row['purchases'];
           	$total_expenditure += $row['expenditure'];
           	$total_sales += $row['sales'];
            $no++;
			}//End while

		$message .= "</table>";
		$message .= "</body></html>";

		$message .= "<br>";
		$message .= '<h3 style="color: lightgrey;">Grand totals</h3>';
		$message .="Total sales:".$total_sales."<br>";
		$message .="Total purchases:".$total_purchases."<br>";
		$message .="Total Expenditure;".$total_expenditure."<br>";
		$message .="Net Total:".($total_sales-($total_expenditure + $total_purchases))."<br>"; 

		// Set content-type when sending HTML email
		$headers = "MIME-Version: 1.0" . "\r\n";
		$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

		// More headers
		$headers .= 'From: Leja Business Statements <statements@leja.co.ke>' . "\n";
		$headers .= "Return-Path: statements@leja.co.ke\n"; // Return path for errors
		$headers .= "Content-type: text/html; charset=iso-8859-1";
		$headers .= 'X-Mailer: PHP/' . phpversion();

		$retval = mail($to,$subject,$message,$headers);
		

		//Also send a message
		$clean_message = "LEJA MINI-STATEMENT"."\n";
		
		while ($row=mysqli_fetch_array($result)){		
			$clean_message .= '
					$row[_date]
					$row[purchases]
					$row[expenditure]
					$row[sales]
					$row[balance]
					';
           	$total_purchases += $row['purchases'];
           	$total_expenditure += $row['expenditure'];
           	$total_sales += $row['sales'];
            $no++;
		}//End while

		$clean_message .="Total sales:".$total_sales."\n";
		$clean_message .="Total purchases:".$total_purchases."\n";
		$clean_message .="Total Expenditure;".$total_expenditure."\n";
		$clean_message .="Net Total:".($total_sales-($total_expenditure + $total_purchases))."\n"; 

		// Send sms
		sendSMS($phone, $clean_message);

		if($retval == TRUE){
			$ussd_text = "END We have sent an SMS with your statement.";
			ussd_proceed($ussd_text);
		}else{
			$ussd_text = "END Ooops!<br>
							We encountered an error while fetching your statements. <br>Kindly try again later.
						";
			ussd_proceed($ussd_text);
		}

		$conne->close();
	}

	function sendSMS($recepient, $message){
		require_once('AfricasTalkingGateway.php');

		$username   = "brianokinyi";
		$apikey     = "1c5223686d70be30004883b4be54a7c3fa29a063d0716ba2d7164ece2434e409";

		$gateway    = new AfricasTalkingGateway($username, $apikey);

		try 
		{
		  $results = $gateway->sendMessage($recepient, $message);
		}
		catch ( AfricasTalkingGatewayException $e )
		{
			//echo "Encountered an error while sending: ".$e->getMessage();
		}
	}

	function getHelp($ussdString_explode) {

		$ussd_text = "CON \n 1:Type of businesses that we carter for. \n 2:How to get loans. 3:How many times should a business person use Leja in a day. 4:For more information visit <a href='leja.co.ke'>leja.co.ke</a>";
		ussd_proceed($ussd_text);
	}
	if ($level==6){
		getHelp();
	}
	if ($level > 6){
		switch($ussdString_explode[6]){
			case 1:
				if($isSwahili == 1)
					$ussd_text = "END \n ";
				else
					$ussd_text = "END \n We carter for different kinds of small and medium businesses such as kiosk,hotel,mamamboga and movie";
			ussd_proceed($ussd_text);
			break;
			case 2:
				if($isSwahili == 1)
					$ussd_text="END \n Ili uweze kupata mikopo lazima uwe umekuwa mwanachama wetu kwa zaidi ya miezi sita.";
				else
					$ussd_text = "END \n In order to get loans you must have used Leja consistently for at least 6 months.";
			ussd_proceed($ussd_text);
			break;
			case 3:
				if($isSwahili == 1)
					$ussd_text = "END \n Mwanabiashara anakubaliwa kuitumia Leja atakavyo lakini anashauriwa kuitumia mara moja kwa siku haswa jioni. Ndiposa aweze kuandika mauzo ya siku hiyo";
				else
					$ussd_text = "END \n A business person is allowed to use Leja as many times as possible but adviced for only once a day. That is the evening when balancing the books for the day";
			ussd_proceed($ussd_text);
			break;
			case 0:
			die();
			break;
			default:
			 $ussd_text ="INVALID OPTION";
			 ussd_proceed($ussd_text);

		}
	}
}
}
?>