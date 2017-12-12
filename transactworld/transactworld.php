<?php
defined('_JEXEC') or die('Restricted access');

/**

 */
if (!class_exists('vmPSPlugin'))
    require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');

class plgVmPaymentTransactworld extends vmPSPlugin {

    // instance of class
    public static $_this = false;

    function __construct(& $subject, $config) {
		
		parent::__construct($subject, $config);
	
		$this->_loggable = true;
		$this->tableFields = array_keys($this->getTableSQLFields());
		$this->_tablepkey = 'id'; 
		$this->_tableId = 'id'; 
		$varsToPush = array(
			'toid' => array('','int'),
                        'partenerid' => array('','int'),
			'ipaddr' => array('','int'),
			'workingkey' => array('','char'),
		    'totype' => array('','char'),
                    'testurl' => array('','char'),
                    'liveurl' => array('','char'),
			'mode' => array('','char'),
			'description' => array('','text'),
			'payment_currency' => array('', 'int'),
		    'status_pending' => array('', 'char'),
		    'status_success' => array('', 'char'),
		    'status_canceled' => array('', 'char'),
		    'countries' => array('', 'char'),
		    'min_amount' => array('', 'int'),
		    'max_amount' => array('', 'int'),
		    'secure_post' => array('', 'int'),
		    'ipn_test' => array('', 'int'),
		    'no_shipping' => array('', 'int'),
		    'address_override' => array('', 'int'),
		    'cost_per_transaction' => array('', 'int'),
		    'cost_percent_total' => array('', 'int'),
		    'tax_id' => array(0, 'int')
		);
	
		$this->setConfigParameterable($this->_configTableFieldName, $varsToPush);	
    }
    
 	public function getVmPluginCreateTableSQL() {
		return $this->createTableSQL('Payment TRANSACTWORLD Table');
    }
    
	function getTableSQLFields() {
		$SQLfields = array(
		    'id' => 'int(11) UNSIGNED NOT NULL AUTO_INCREMENT',
		    'virtuemart_order_id' => 'int(1) UNSIGNED',
		    'order_number' => ' char(64)',
		    'virtuemart_paymentmethod_id' => 'mediumint(1) UNSIGNED',
		    'payment_name' => 'varchar(5000)',
			'transactworld_custom' => ' varchar(255)',
		    'amount' => 'decimal(15,5) NOT NULL DEFAULT \'0.00000\'',
			'status' => 'varchar(225)',
			'mode'=> 'varchar(225)',
			
			'productinfo' => 'text',
			
		);
	return $SQLfields;
	}
	
	function plgVmConfirmedOrder($cart, $order) {		
		if (!($method = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id))) {
		    return null; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement($method->payment_element)) {
		    return false;
		}
		$session = JFactory::getSession();
		$return_context = $session->getId();
		$this->logInfo('plgVmConfirmedOrder order number: ' . $order['details']['BT']->order_number, 'message');

		if (!class_exists('VirtueMartModelOrders'))
		    require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );
		if (!class_exists('VirtueMartModelCurrency'))
		    require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'currency.php');		
		    
		
		//$usr = JFactory::getUser();
		$new_status = '';	
		$usrBT = $order['details']['BT'];
		$address = ((isset($order['details']['ST'])) ? $order['details']['ST'] : $order['details']['BT']);

		if (!class_exists('TableVendors'))
		    require(JPATH_VM_ADMINISTRATOR . DS . 'table' . DS . 'vendors.php');
		$vendorModel = VmModel::getModel('Vendor');
		$vendorModel->setId(1);
		$vendor = $vendorModel->getVendor();
		$vendorModel->addImages($vendor, 1);
		
                
                
                $q = 'SELECT `currency_code_3` FROM `#__virtuemart_currencies` WHERE `virtuemart_currency_id`="' . $order['details']['BT']->order_currency . '" ';
                $db = JFactory::getDBO();
                $db->setQuery($q);
                $currency_code_3 = $db->loadResult();
                
                
		$toid = $this->_getMerchantToid($method);
		if (empty($toid)) {
		    vmInfo(JText::_('VMPAYMENT_TRANSACTWORLD_MERCHANT_TOID_NOT_SET'));
		    return false;
		}
		$workingkey = $method->workingkey;
		$totype = $method->totype;
                $partenerid = $method->partenerid;
		$ipaddr = $method->ipaddr;
                $testurl = $method->testurl;
                $liveurl = $method->liveurl;
		$mode = $method->mode;
		
		$redirect_Url    = JROUTE::_ (JURI::root () . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginresponsereceived&on=' . $order['details']['BT']->order_number . '&pm=' . $order['details']['BT']->virtuemart_paymentmethod_id . '&Itemid=' . JRequest::getInt ('Itemid'));
		$description = $method->description;
		$ship_address = $address->address_1;
              
                $hashSequence = md5( $checksumdump = $toid ."|".$totype."|".(float)$order['details']['BT']->order_total."|" . $order['details']['BT']->order_number ."|". $redirect_Url ."|". $workingkey);
              
		//var_dump($checksumdump . " -- This is checksum!!!");
                
		if(isset($address->address_2)){
	    	$ship_address .=  ", ".$address->address_2;
		}
		
		
		/***********************************************************************************/
                
                $country_code = array(
					"AF"=>"093", 
					"AX"=>"358", 
					"AL"=>"355",
					"DZ"=>"231",
					"AS"=>"684",
					"AD"=>"376",
					"AO"=>"244",
					"AI"=>"001",
					"AQ"=>"000",
					"AG"=>"001",
					"AR"=>"054",
					"AM"=>"374",
					"AW"=>"297",
					"AU"=>"061",
					"AT"=>"043",
					"AZ"=>"994",
					"BS"=>"001",
					"BH"=>"973",
					"BD"=>"880",
					"BB"=>"001",
					"BY"=>"375",
					"BE"=>"032",
					"BZ"=>"501",
					"BJ"=>"229",
					"BM"=>"001",
					"BT"=>"975",
					"BO"=>"591",
					"BA"=>"387",
					"BW"=>"267",
					"BV"=>"000",
					"BR"=>"055",
					"IO"=>"246",
					"VG"=>"001",
					"BN"=>"673",
					"BG"=>"359",
					"BF"=>"226",
					"BI"=>"257",
					"KH"=>"855",
					"CM"=>"237",
					"CA"=>"001",
					"CV"=>"238",
					"KY"=>"001",
					"CF"=>"236",
					"TD"=>"235",
					"CL"=>"056",
					"CN"=>"086",
					"CX"=>"061",
					"CC"=>"061",
					"CC"=>"061",
					"CO"=>"057",
					"KM"=>"269",
					"CK"=>"682",
					"CR"=>"506",
					"CI"=>"225",
					"HR"=>"385",
					"CU"=>"053",
					"CY"=>"357",
					"CZ"=>"420",
					"CD"=>"243",
					"DK"=>"045",
					"DJ"=>"253",
					"DM"=>"001",
					"DO"=>"001",
					"EC"=>"593",
					"EG"=>"020",
					"SV"=>"503",
					"GQ"=>"240",
					"ER"=>"291",
					"EE"=>"372",
					"ET"=>"251",
					"FK"=>"500",
					"FO"=>"298",
					"FJ"=>"679",
					"FI"=>"358",
					"FR"=>"033",
					"GF"=>"594",
					"PF"=>"689",
					"TF"=>"000",
					"GA"=>"241",
					"GM"=>"220",
					"GE"=>"995",
					"DE"=>"049",
					"GH"=>"233",
					"GI"=>"350",
					"GR"=>"030",
					"GL"=>"299",
					"GD"=>"001",
					"GP"=>"590",
					"GU"=>"001",
					"GT"=>"502",
					"GG"=>"000",
					"GN"=>"224",
					"GW"=>"245",
					"GY"=>"592",
					"HT"=>"509",
					"HM"=>"672",
					"HN"=>"504",
					"HK"=>"852",
					"HU"=>"036",
					"IS"=>"354",
					"IN"=>"091",
					"ID"=>"062",
					"IR"=>"098",
					"IQ"=>"964",
					"IE"=>"353",
					"IL"=>"972",
					"IT"=>"039",
					"JM"=>"001",
					"JP"=>"081",
					"JE"=>"044",
					"JO"=>"962",
					"KZ"=>"007",
					"KE"=>"254",
					"KI"=>"686",
					"KW"=>"965",
					"KG"=>"996",
					"LA"=>"856",
					"LV"=>"371",
					"LB"=>"961",
					"LS"=>"266",
					"LR"=>"231",
					"LY"=>"218",
					"LI"=>"423",
					"LT"=>"370",
					"LU"=>"352",
					"MO"=>"853",
					"MK"=>"389",
					"MG"=>"261",
					"MW"=>"265",
					"MY"=>"060",
					"MV"=>"960",
					"ML"=>"223",
					"MT"=>"356",
					"MH"=>"692",
					"MQ"=>"596",
					"MR"=>"222",
					"MU"=>"230",
					"YT"=>"269",
					"MX"=>"052",
					"FM"=>"691",
					"MD"=>"373",
					"MC"=>"377",
					"MN"=>"976",
					"ME"=>"382",
					"MS"=>"001",
					"MA"=>"212",
					"MZ"=>"258",
					"MM"=>"095",
					"NA"=>"264",
					"NR"=>"674",
					"NP"=>"977",
					"AN"=>"599",
					"NL"=>"031",
					"NC"=>"687",
					"NZ"=>"064",
					"NI"=>"505",
					"NE"=>"227",
					"NG"=>"234",
					"NU"=>"683",
					"NF"=>"672",
					"KP"=>"850",
					"MP"=>"001",
					"NO"=>"047",
					"OM"=>"968",
					"PK"=>"092",
					"PW"=>"680",
					"PS"=>"970",
					"PA"=>"507",
					"PG"=>"675",
					"PY"=>"595",
					"PE"=>"051",
					"PH"=>"063",
					"PN"=>"064",
					"PL"=>"048",
					"PT"=>"351",
					"PR"=>"001",
					"QA"=>"974",
					"CG"=>"242",
					"RE"=>"262",
					"RO"=>"040",
					"RU"=>"007",
					"RW"=>"250",
					"BL"=>"590",
					"SH"=>"290",
					"KN"=>"001",
					"LC"=>"001",
					"MF"=>"590",
					"PM"=>"508",
					"VC"=>"001",
					"WS"=>"685",
					"SM"=>"378",
					"ST"=>"239",
					"SA"=>"966",
					"SN"=>"221",
					"RS"=>"381",
					"SC"=>"248",
					"SL"=>"232",
					"SG"=>"065",
					"SK"=>"421",
					"SI"=>"386",
					"SB"=>"677",
					"SO"=>"252",
					"ZA"=>"027",
					"GS"=>"000",
					"KR"=>"082",
					"ES"=>"034",
					"LK"=>"094",
					"SD"=>"249",
					"SR"=>"597",
					"SJ"=>"047",
					"SZ"=>"268",
					"SE"=>"046",
					"CH"=>"041",
					"SY"=>"963",
					"TW"=>"886",
					"TJ"=>"992",
					"TZ"=>"255",
					"TH"=>"066",
					"TL"=>"670",
					"TG"=>"228",
					"TK"=>"690",
					"TO"=>"676",
					"TT"=>"001",
					"TN"=>"216",
					"TR"=>"090",
					"TM"=>"993",
					"TC"=>"001",
					"TV"=>"688",
					"UG"=>"256",
					"UA"=>"380",
					"AE"=>"971",
					"GB"=>"044",
					"US"=>"001",
					"VI"=>"001",
					"UY"=>"598",
					"UZ"=>"998",
					"VU"=>"678",
					"VA"=>"379",
					"VE"=>"058",
					"VN"=>"084",
					"WF"=>"681",
					"EH"=>"212",
					"YE"=>"967",
					"ZM"=>"260",
					"ZW"=>"263"
					);

$country_value = $country_code[ShopFunctions::getCountryByID($order['details']['BT']->virtuemart_country_id, 'country_2_code')];
               
                /*********************************************************************************/
		
		
		$post_variables = Array(
		   "key" => $workingkey,
                    "toid" => $toid,
                    "totype" => $totype,
                    "partenerid" => $partenerid,
                    "ipaddr" => $ipaddr,
                    "pctype" => "1_1|1_2",
                    "reservedField1" => "",
                    "reservedField2" => "",
                    "testurl" => $testurl,
                    "liveurl" => $liveurl,
                    "paymenttype" => '',
                    "cardtype" => '',
					"description" => $order['details']['BT']->order_number,
					
					
                   
                    "reference_no" => $order['details']['BT']->order_number,		    
		  
		    "orderdescription" => $order['details']['BT']->order_number,
		    "amount" =>(float)$order['details']['BT']->order_total,
			"mode" => $mode,
			"firstname" => $order['details']['BT']->first_name,
            "lastname" => $order['details']['BT']->last_name,
            "TMPL_street" => $order['details']['BT']->address_1." ".$order['details']['BT']->address_2,
			"city" => $order['details']['BT']->city,
			"state" => isset($order['details']['BT']->virtuemart_state_id) ? ShopFunctions::getStateByID($order['details']['BT']->virtuemart_state_id) : '',
			"TMPL_COUNTRY" => ShopFunctions::getCountryByID($order['details']['BT']->virtuemart_country_id, 'country_2_code'),
			"zipcode" =>  $order['details']['BT']->zip,
			"phone" => $order['details']['BT']->phone_1,
			"telnocc" => $country_value,
			"email" => $order['details']['BT']->email,
			"currency" => $currency_code_3,
			'TMPL_CURRENCY' => $currency_code_3,
                        
                        
		    "ship_name" => $address->first_name." ".$address->last_name,
			"ship_address" => $ship_address,			
		    "TMPL_zip" => $address->zip,
		    "TMPL_city" => $address->city,
		    "TMPL_state" => isset($address->virtuemart_state_id) ? ShopFunctions::getStateByID($address->virtuemart_state_id) : '',
		    "country" => ShopFunctions::getCountryByID($address->virtuemart_country_id, 'country_2_code'),
		    "ship_phone" => $address->phone_1,
			
			"checksum" => $hashSequence,
			"redirecturl" => $redirect_Url,
                        
		);
		
		//var_dump($post_variables);
              //  exit();
		
		$dbValues['order_number'] = $order['details']['BT']->order_number;
		$dbValues['payment_name'] = $this->renderPluginName($method, $order);
		$dbValues['virtuemart_paymentmethod_id'] = $cart->virtuemart_paymentmethod_id;
		$dbValues['description'] = '$description';//$description;
		$dbValues['transactworld_custom'] = $return_context;
		$dbValues['billing_currency'] = $method->payment_currency;
		$dbValues['amount'] =(float) $totalInPaymentCurrency;
		$this->storePSPluginInternalData($dbValues);
	
		$url = $this->_getTRANSACTWORLDUrlHttps($method);
		
		// add spin image
		$html = '<html><head><title>Redirection</title></head><body><div style="margin: auto; text-align: center;">';
		$html .= '<form action="' . "https://" . $url . '" method="post" name="vm_transactworld_form" >';
		$html.= '<input type="submit"  value="' . JText::_('VMPAYMENT_TRANSACTWORLD_REDIRECT_MESSAGE') . '" />';
		foreach ($post_variables as $name => $value) {
		    $html.= '<input type="hidden" style="" name="' . $name . '" value="' . htmlspecialchars($value) . '" />';
		}
		$html.= '</form></div>';
		$html.= ' <script type="text/javascript">';
		$html.= ' document.vm_transactworld_form.submit();';
		$html.= ' </script></body></html>';
	
		
		$cart->_confirmDone = false;
		$cart->_dataValidated = false;
		$cart->setCartIntoSession();
		JRequest::setVar('html', $html);
    }
    
	function plgVmgetPaymentCurrency($virtuemart_paymentmethod_id, &$paymentCurrencyId) {
		if (!($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
		    return null; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement($method->payment_element)) {
		    return false;
		}
		$this->getPaymentCurrency($method);
		$paymentCurrencyId = $method->payment_currency;
    }

    function plgVmOnPaymentResponseReceived(&$html) {
		if (!class_exists('VirtueMartCart'))
	    require(JPATH_VM_SITE . DS . 'helpers' . DS . 'cart.php');
		if (!class_exists('shopFunctionsF'))
		    require(JPATH_VM_SITE . DS . 'helpers' . DS . 'shopfunctionsf.php');
		if (!class_exists('VirtueMartModelOrders'))
		    require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );
		
		$virtuemart_paymentmethod_id = JRequest::getInt('pm', 0);
		$order_number = JRequest::getString('on', 0);	
		
		$vendorId = 0;
		if (!($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
		    return null; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement($method->payment_element)) {
		    return null;
		}	
		if (!($virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number))) {
		    return null;
		}
		if (!($paymentTable = $this->getDataByOrderId($virtuemart_order_id) )) {
		    
		    return '';
		}
		$payment_name = $this->renderPluginName($method);
		
   		
                $response = array();
                $response = $_POST;
             
                
                
                
                if($response['status']=='Y'){
                    
		$new_status = $method->status_success;
                            
		}
		else if ($response['status']=='N')
                {
			$new_status = $method->status_canceled;
		} 
                else{
                    $new_status = $method->status_pending;
                }
                
                
		

                
               // print_r($new_status);die;
		$modelOrder = VmModel::getModel('orders');
		$order['order_status'] = $new_status;
                                //print_r($order);die;
		$order['customer_notified'] = 1;
		$order['comments'] = '';
		$modelOrder->updateStatusForOneOrder($virtuemart_order_id, $order, true);
                //print_r($_POST);die;
        
		$this->_storeTransactworldInternalData($method, $response, $virtuemart_order_id,$paymentTable->transactworld_custom);
		if($response['status']=='Y'){		
			$html = $this->_getPaymentResponseHtml($paymentTable, $payment_name, $response);
		}
		else{
			$cancel_return = JROUTE::_(JURI::root() . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginUserPaymentCancel&on=' .$order_number.'&pm='.$virtuemart_paymentmethod_id);
			$html= ' <script type="text/javascript">';
			$html.= 'window.location = "'.$cancel_return.'"';
			$html.= ' </script>';
			JRequest::setVar('html', $html);
		}
	
		//We delete the old stuff
		// get the correct cart / session
		$cart = VirtueMartCart::getCart();
		$cart->emptyCart();
		return true;
    }
    
	function _getPaymentResponseHtml($paymentTable, $payment_name, $response) {
		$html = '<table>' . "\n";
		$html .= $this->getHtmlRow('TRANSACTWORLD_PAYMENT_NAME', $payment_name);		
		if (!empty($paymentTable)) {
		    //$html .= $this->getHtmlRow('PAYMENTZ_ORDER_NUMBER', $paymentTable->order_number);
                    $html .= $this->getHtmlRow('TRANSACTWORLD_VIRTUEMART_ORDER_ID', $paymentTable->virtuemart_order_id);
		}
		
		
		$tot_amount = $response['amount']." INR";
		$html .= $this->getHtmlRow('TRANSACTWORLD_AMOUNT', $tot_amount);
		
	
		return $html;
    }
    
	function _storeTransactworldInternalData($method, $response, $virtuemart_order_id,$custom) {
      
		$virtuemart_paymentmethod_id = JRequest::getInt('pm', 0);
		//$response_fields['payment_name'] = $this->renderPluginName($method);	
		//$response_fields['virtuemart_order_id'] = $virtuemart_order_id;
		//$response_fields['virtuemart_paymentmethod_id'] = $virtuemart_paymentmethod_id;
		//$response_fields['order_number'] = $response['txnid'];
		$response_fields['trackingid'] = $response['trackingid'];
		//$response_fields['paymentz_custom'] = $custom;
		$response_fields['amount'] = $response['amount'];
		$response_fields['desc'] = $response['desc'];
		$response_fields['checksum'] = $response['checksum'];
		$response_fields['status'] = $response['status'];
		//$response_fields['mode'] = ucfirst($response['mode']);
	//	$response_fields['mihpayid'] = $response['mihpayid'];
		//$response_fields['productinfo'] = $response['productinfo'];
		//$response_fields['txnid'] = $response['txnid'];
  		
		$this->storePSPluginInternalData($response_fields, 'virtuemart_order_id', true);
    }
    
 	function plgVmOnUserPaymentCancel() {
		if (!class_exists('VirtueMartModelOrders'))
		    require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );
	
		$order_number = JRequest::getString('on', '');
		$virtuemart_paymentmethod_id = JRequest::getInt('pm', '');
		if (empty($order_number) or empty($virtuemart_paymentmethod_id) or !$this->selectedThisByMethodId($virtuemart_paymentmethod_id)) {
		    return null;
		}
		if (!($virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number))) {
			return null;
		}
		if (!($paymentTable = $this->getDataByOrderId($virtuemart_order_id))) {
		    return null;
		}
	
		VmInfo(Jtext::_('VMPAYMENT_TRANSACTWORLD_PAYMENT_CANCELLED'));
		$session = JFactory::getSession();
		$return_context = $session->getId();
		if (strcmp($paymentTable->transactworld_custom, $return_context) === 0) {
		    $this->handlePaymentUserCancel($virtuemart_order_id);
		}
		return true;
    }
    
	
	
    
	function plgVmOnShowOrderBEPayment($virtuemart_order_id, $payment_method_id) {
		if (!$this->selectedThisByMethodId($payment_method_id)) {
		    return null; // Another method was selected, do nothing
		}
		if (!($paymentTable = $this->_getTransactworldInternalData($virtuemart_order_id) )) {
		    // JError::raiseWarning(500, $db->getErrorMsg());
		    return '';
		}
		$q = 'SELECT `currency_code_3` FROM `#__virtuemart_currencies` WHERE `virtuemart_currency_id`="' . $paymentTable->billing_currency . '" ';
		$db = JFactory::getDBO();
		$db->setQuery($q);
		$currency_code_3 = $db->loadResult();
		//$html = '<table class="adminlist">' . "\n";
		//$html .=$this->getHtmlHeaderBE();
		//$html .= $this->getHtmlRowBE('PAYMENTZ_PAYMENT_NAME', $paymentTable->payment_name);		
		//echo "<pre>";print_r($paymentTable);echo "</pre>";
		//$html .= $this->getHtmlRowBE('PAYMENTZ_VIRTUEMART_ORDER_ID', $paymentTable->virtuemart_order_id);
		//$html .= $this->getHtmlRowBE('PAYMENTZ_RESPONSE_MESSAGE', $paymentTable->status);
		//$html .= $this->getHtmlRowBE('PAYMENTZ_PAYMENT_ID', $paymentTable->mihpayid);
		//$html .= $this->getHtmlRowBE('PAYMENTZ_AMOUNT', $paymentTable->amount.' INR');
		//$html .= $this->getHtmlRowBE('PAYMENTZ_MODE', $paymentTable->mode);
		//$html .= $this->getHtmlRowBE('PAYMENTZ_PAYMENT_TRANSACTION_ID', $paymentTable->txnid);
		//$html .= $this->getHtmlRowBE('PAYMENTZ_PAYMENT_DATE', $paymentTable->modified_on);
		//$html .= '</table>' . "\n";
		return $html;
    }

    function _getTransactworldInternalData($virtuemart_order_id, $order_number = '') {
		$db = JFactory::getDBO();
		$q = 'SELECT * FROM `' . $this->_tablename . '` WHERE ';
		if ($order_number) {
		    $q .= " `order_number` = '" . $order_number . "'";
		} else {
		    $q .= ' `virtuemart_order_id` = ' . $virtuemart_order_id;
		}
		$db->setQuery($q);
		if (!($paymentTable = $db->loadObject())) {
		    // JError::raiseWarning(500, $db->getErrorMsg());
		    return '';
		}
		return $paymentTable;
    } 
	
	
    
	function _getMerchantToid($method) {		
		return $method->toid;
    }
    
	function _getTRANSACTWORLDUrlHttps($method) {
		//$url = 'test.paymentz.in/_payment';
		//$url = 'staging.paymentz.com/transaction/PayProcessController';
               // var_dump($mode);
              // if($mode == "TEST") 
                if($method->mode == 'TEST')
               {
               $test = $method->testurl;
		return $test;
               }
               else{
                    $live = $method->liveurl;
		return $live;
               }
    }   
	
	
    
	function getCosts(VirtueMartCart $cart, $method, $cart_prices) {
		if (preg_match('/%$/', $method->cost_percent_total)) {
		    $cost_percent_total = substr($method->cost_percent_total, 0, -1);
		} else {
		    $cost_percent_total = $method->cost_percent_total;
		}
		return ($method->cost_per_transaction + ($cart_prices['salesPrice'] * $cost_percent_total * 0.01));
    }
    
	protected function checkConditions($cart, $method, $cart_prices) {
		$this->convert($method);
		$address = (($cart->ST == 0) ? $cart->BT : $cart->ST);
		$amount = $cart_prices['salesPrice'];
		$amount_cond = ($amount >= $method->min_amount AND $amount <= $method->max_amount
			OR
			($method->min_amount <= $amount AND ($method->max_amount == 0) ));
		$countries = array();
		if (!empty($method->countries)) {
		    if (!is_array($method->countries)) {
			$countries[0] = $method->countries;
		    } 
                    else {
			$countries = $method->countries;
		    }
		}
		// probably did not gave his BT:ST address
		if (!is_array($address)) {
		    $address = array();
		    $address['virtuemart_country_id'] = 0;
		}
		if (!isset($address['virtuemart_country_id']))
		    $address['virtuemart_country_id'] = 0;
		if (in_array($address['virtuemart_country_id'], $countries) || count($countries) == 0) {
		    if ($amount_cond) {
			return true;
		    }
		}
		return false;
    }
    
 	function convert($method) {
		$method->min_amount = (float) $method->min_amount;
		$method->max_amount = (float) $method->max_amount;
    }
    
	function plgVmOnStoreInstallPaymentPluginTable($jplugin_id) {
		return $this->onStoreInstallPluginTable($jplugin_id);
    }
    
    public function plgVmOnSelectCheckPayment(VirtueMartCart $cart) {
		return $this->OnSelectCheck($cart);
    }
    
	public function plgVmDisplayListFEPayment(VirtueMartCart $cart, $selected = 0, &$htmlIn) {
		return $this->displayListFE($cart, $selected, $htmlIn);
    }
    
	public function plgVmonSelectedCalculatePricePayment(VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name) {
		return $this->onSelectedCalculatePrice($cart, $cart_prices, $cart_prices_name);
    }
    
    function plgVmOnCheckAutomaticSelectedPayment(VirtueMartCart $cart, array $cart_prices = array(),   &$paymentCounter) {
		return $this->onCheckAutomaticSelected($cart, $cart_prices,  $paymentCounter);
    }
    
 	public function plgVmOnShowOrderFEPayment($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name) {
		$this->onShowOrderFE($virtuemart_order_id, $virtuemart_paymentmethod_id, $payment_name);
    }
    
 	function plgVmonShowOrderPrintPayment($order_number, $method_id) {
		return $this->onShowOrderPrint($order_number, $method_id);
    }
    
    function plgVmDeclarePluginParamsPayment($name, $id, &$data) {
		return $this->declarePluginParams('payment', $name, $id, $data);
    }

    function plgVmSetOnTablePluginParamsPayment($name, $id, &$table) {
		return $this->setOnTablePluginParams($name, $id, $table);
    }
    
    
    function plgVmDeclarePluginParamsPaymentVM3( &$data) {
            return $this->declarePluginParams('payment', $data);
    }    

}
