<?php

	require_once("base.php");
	require_once("models.php");
	
    if(!isset($_SESSION))
      {
        session_start();
      }
      //var_dump($_SESSION);

    ///controller file will controll the flow of the application 


	function array_sort($array, $on, $order=SORT_ASC){

	    $new_array = array();
	    $sortable_array = array();

	    if (count($array) > 0) {
	        foreach ($array as $k => $v) {
	            if (is_array($v)) {
	                foreach ($v as $k2 => $v2) {
	                    if ($k2 == $on) {
	                        $sortable_array[$k] = $v2;
	                    }
	                }
	            } else {
	                $sortable_array[$k] = $v;
	            }
	        }

	        switch ($order) {
	            case SORT_ASC:
	                asort($sortable_array);
	                break;
	            case SORT_DESC:
	                arsort($sortable_array);
	                break;
	        }

	        foreach ($sortable_array as $k => $v) {
	            $new_array[$k] = $array[$k];
	        }
	    }

	    return $new_array;
	}

	function getAccountType($type_code)
	{
		$myacc = new account();
		$myacc->account_code = $type_code;
		$resultArry = $myacc->getThisAccount();
		//print_r($resultArry); exit;
		return $resultArry;

	}


    function checkProfile($mode = "normal", $msisdn)  //requires nmsisdn  //normal or cron
    {
    	$newMCReq =  new Request();
    	$newMCReq->msisdn = $msisdn;
	    $newMCReq->QueryProfile();	    
	    $responseAssets = $newMCReq->makeRequest();
	    if($responseAssets == 'timeout')
	    {
	    	return "timeout";
	    }
	    $profile = parseProfile($responseAssets);
	    if($mode == "cron" )
	    {
	    	return $profile;
	    }

	    if((isset($profile['AccountId'])) && (isset($profile['ContactId'])))
	    {
	    	$_SESSION['AccountId'] = $profile['AccountId'];
	    	$_SESSION['ContactId'] = $profile['ContactId'];
	    	return 'true';
	    }else
	    {
	    	return 'false';
	    }
	    
    }

    function TryAgain()
    {

    	$msg = "Dear Customer, <br> Your request cannot be processed at the moment. Please try again later, we apologize for the inconvenience.";
    	return $msg;
    }
    function ServiceUnavailable()
    {

    	$msg = "Dear Customer, <br> This service is not available at the moment. Please try again later, we apologize for the inconvenience.";
    	return $msg;
    }

    function InsufficientBalance($productName)
    {
    	$msg = "Dear Customer, <br> Your balance is insufficient to purchase <b>$productName</b>. Please topup or select another bundle.";
    	return $msg;
    }

    function requestTopup($TransArray)
    {
    	$req = new Request();
        $req->msisdn = $TransArray['msisdn'];
        $req->txn_ref = $TransArray['txn_ref'];
        $profile  = checkProfile("cron", $TransArray['msisdn']);
        //var_dump($profile); exit;
        if(is_array($profile))
        {
        	$AccountId = $profile['AccountId'];
        	$ContactId = $profile['ContactId'];
        	$req->AirtimeTopup($TransArray['amount'], $AccountId, $ContactId);
        	$response = $req->makeRequest();
        	$result = parseXmlTopup($response);
        	return $result;	
        }else
        {
        	return "timeout";
        }
        
        
        if($response == "timeout")
        {
        	return "timeout";
        }
        
       /* if($result['ErrorCode'] == 0) //successful
        {
          return "successful";
        }else // not successful
        {
          return "failed";
        }*/
        
    }

  
    function getMainBal()
    {
	    $newRequest2 =  new Request();
	    $newRequest2->msisdn =  $_SESSION['nmsisdn'];
	    $newRequest2->QueryBalance();
	    $responseBal = $newRequest2->makeRequest();
	    if($responseBal == 'timeout')
	    {
	    	header("Location: error404.php");
	    }
	    $bal = parseXmlBalance($responseBal);

	    //var_dump($bal); exit;
	    $val = "NaN";
	    foreach ($bal as $b) {

	             $dummy = getMainBalance($b);
	             if($dummy != "")
	             {
	                $val = $dummy;
	             }
	    }
	
		return $val; 
	}

	function getSummarybundle()
	{
		$newBundleRequest =  new Request();
	    $newBundleRequest->msisdn = $_SESSION['nmsisdn'];
	    $newBundleRequest->QueryAssets();
	    $responseAssets = $newBundleRequest->makeRequest();
	    if($responseAssets == 'timeout')
	    {
	    	header("Location: error404.php");
	    }
	    $resultAsset = parseXmlAssets($responseAssets, 'summary');
	    //print_r($resultAsset); exit;
	    /*var_dump($resultAsset);
	    exit;
	    */

	    return $resultAsset;
	}

	function buyBundle($txn_ref, $timestamp, $myProduct)
	{
		//print_r($myProduct); exit;
		$newBundleRequest =  new Request();
	    $newBundleRequest->msisdn = $_SESSION['nmsisdn'];
	    $newBundleRequest->requestBundle($txn_ref, $timestamp, $myProduct);
	    $responseAssets = $newBundleRequest->makeRequest();
	    if($responseAssets =='timeout')
	    {
	    	return "timeout";
	    }
	    $orderResponse = parseBundleRequest($responseAssets);
	    //var_dump($orderResponse); exit;
	    return $orderResponse;
	    //$resultAsset = parseXmlAssets($responseAssets, 'summary');
	}

	
	if(isset($_POST['get_prod_desc']))
	{
		//var_dump($_POST);
		getProductDesc($_POST['get_prod_desc']);
	}
	function getProductDesc($pid)
	{
		//echo $pid;
		$newProduct = new Product();
		$newProduct->product_id = $pid;
		$result = $newProduct->getThisProduct();
		echo $result[0]['product_desc'];
		//return "got here $pid";
	}

	function formatBalanceDisplay($accArray)
    {
      //data
      if($accArray['suffix'] == "MB")
      {
        $newBalance = $accArray['CURRENT_BAL']/(1024*1024);
        $newBalance = number_format($newBalance, 2);
        return $newBalance;
      }
      if($accArray['prefix'] == "₦")
      {

        $newBalance = $accArray['CURRENT_BAL'];
        $newBalance = number_format($newBalance, 2);
        return $newBalance;
      }
      else
      {
        $newBalance = $accArray['CURRENT_BAL'];
        //$newBalance = number_format($newBalance);
        return $newBalance;
      }

    }

    function reformatDate($oldDate)
    {

    	$newDate = date("d-m-Y", strtotime($oldDate));
    	return $newDate;
    }

    function getPendingTopupRequest()
    {
    	$pendingTrans =  new Transaction();
 		$pendingList = $pendingTrans->getTransactionTopupPending();
 		return $pendingList;
    }

?>