<?php
/**
 *	Australian ABN Lookup Adaptor
 *	Please refer to http://abr.business.gov.au/ for API details
 *	@author Zeno <zeno_yu@yahoo.com> 
 */
class Zeno_Abnlookup_Model_Abnlookup extends Mage_Core_Model_Abstract {

	
	/**
	 *	Check ABN (with SoapClient support)
	 *	usage: Zeno_Abnlookup_Model_Abnlookup::checkABN('')
	 *	@param string $strABN
	 *	@return array mainName / stateCode / postcode
	 */
	public static function checkABN($strABN){
		try{
			$client = new SoapClient("http://abr.business.gov.au/ABRXMLSearch/ABRXMLSearch.asmx?WSDL");
			$params = array(
					'searchString'    				=> $strABN,
					'includeHistoricalDetails'=> 'n',
					'authenticationGuid'      => ABN_GUID
			);
			$objABNLookupResult = $client->ABRSearchByABN($params);
	
			//echo '<pre>';print_r($objABNLookupResult);echo '</pre>';
			if( isset( $objABNLookupResult->ABRPayloadSearchResults->response->exception ) ){
				//echo $objABNLookupResult->ABRPayloadSearchResults->response->exception->exceptionDescription;
				return null;
			}else{
				if( isset($objABNLookupResult->ABRPayloadSearchResults->response->businessEntity->mainName->organisationName) ){
					return array( "mainName" => (string)$objABNLookupResult->ABRPayloadSearchResults->response->businessEntity->mainName->organisationName,
							"stateCode" => (string)$objABNLookupResult->ABRPayloadSearchResults->response->businessEntity->mainBusinessPhysicalAddress->stateCode,
							"postcode" => (string)$objABNLookupResult->ABRPayloadSearchResults->response->businessEntity->mainBusinessPhysicalAddress->postcode
					);
				}elseif( isset($objABNLookupResult->ABRPayloadSearchResults->response->businessEntity->mainTradingName->organisationName) ){
					return array( "mainName" => (string)$objABNLookupResult->ABRPayloadSearchResults->response->businessEntity->mainTradingName->organisationName,
							"stateCode" => (string)$objABNLookupResult->ABRPayloadSearchResults->response->businessEntity->mainBusinessPhysicalAddress->stateCode,
							"postcode" => (string)$objABNLookupResult->ABRPayloadSearchResults->response->businessEntity->mainBusinessPhysicalAddress->postcode
					);
	
				}else
					return null;
			}
		}catch(Exception $e){
		}
		return null;
	}

	/**
	 * checkABN with native soap function
	 * @param string $strABN
	 * @return array
	 */
	public static function checkABN_raw( $strABN ){
		$host = "abr.business.gov.au";
		$port = 80;
		$soap_url = "/ABRXMLSearch/ABRXMLSearch.asmx";
		$soap_action = "http://abr.business.gov.au/ABRXMLSearch/ABRSearchByABN";

		$params = array(
			    'searchString'    		=> $strABN,
			    'includeHistoricalDetails'=> 'n',
			    'authenticationGuid'      => ABN_GUID
			  );
		// do the soap (uncomment it when you are ready)
		$data = ABNLookup::SOAP_Call($host, $port, $soap_url, $soap_action, $params);
		
		 $arrReturnArray = array( 	"mainName" => '',
									"stateCode" => '',
									"postcode" => '',
									"entityDescription" => '' );

		if( preg_match('/<mainTradingName>(.*)<\/mainTradingName>/is',$data,$arrMatch) )
			$arrReturnArray['mainName'] = $arrMatch[1];

		if( preg_match('/<organisationName>([^<]*)<\/organisationName>/is',$data,$arrMatch) )
			$arrReturnArray['mainName'] = $arrMatch[1];

		if( preg_match('/<stateCode>(.*)<\/stateCode>/is',$data,$arrMatch) )
			$arrReturnArray['stateCode'] = $arrMatch[1];

		if( preg_match('/<postcode>(.*)<\/postcode>/is',$data,$arrMatch) )
			$arrReturnArray['postcode'] = $arrMatch[1];

		if( preg_match('/<entityDescription>(.*)<\/entityDescription>/is',$data,$arrMatch) )
			$arrReturnArray['entityDescription'] = $arrMatch[1];
		
		if( $arrReturnArray['mainName'] =='' )
			return null;
		return $arrReturnArray;
	}

	/**
	 *	SOAP_Call (without the new php5 soap objects)
	 *	@param string $host Address of the server that is running the webservice        
	 *	@param string $port Port to access the webservice; http => 80                   
	 *	@param string $url Url of the webservice; where shall be posted to             
	 *	@param string $action The soap action; including the namespace                    
	 *	@param string $vars The arguments of the webservice in an associative array 
	 */
	public static function SOAP_Call($host, $port, $url, $action, $vars) {
	    
	    // define eol that is used by the server
	    // windows webservices seem to need a \r\n
	    $eol = "\r\n";
	    
	    // extract namespace from soap action
	    preg_match('/(https?:\/\/.*\/)(.*)/', $action, $matches) or die("Invalid SOAPAction: '$action'");
	    $soap_ns = $matches[1];
	    $soap_action = $matches[2];
	    
	    // create soap envelope
	    // convert to utf8 and get the content length
	    $content = 
	        "<?xml version=\"1.0\" encoding=\"utf-8\"?>".$eol.
	        "<soap:Envelope xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\">".$eol.
	        "  <soap:Body>".$eol.
	        "    <".$soap_action." xmlns=\"".$soap_ns."\">".$eol;
	    while (list($key, $value) = each($vars)) {
	        $content.= "      <".$key.">".$value."</".$key.">".$eol;
	    }
	    $content .= 
	        "    </".$soap_action.">".$eol.
	        "  </soap:Body>".$eol.
	        "</soap:Envelope>";
	    $content = utf8_encode($content);
	    $content_length = strlen($content);
	    
	    // create soap header
	    $headers = 
	        "POST ".$url." HTTP/1.1".$eol.
	        "Host: ".str_replace("ssl://","",$host)."".$eol.
	        "Connection: close".$eol.    
	        "Content-Type: text/xml; charset=utf-8".$eol.
	        "Content-Length: ".$content_length."".$eol.
	        "SOAPAction: \"".$soap_ns.$soap_action."\"".$eol.$eol;
	 
	    // make connection to server and post header and the soap envelope
	    $fp = fsockopen($host, $port, $errno, $errstr, 10);
	    
	    if (!$fp) {
	        return false;
	    }
	    fputs($fp, $headers);
	    fputs($fp, $content);
	    
	    stream_set_timeout($fp, 20);
	    
	    $data = "";
	    $status = socket_get_status($fp);
	    while(!feof($fp) && !$status['timed_out']) {
	        $data .= fgets($fp, 1024);
	        $status = socket_get_status($fp);
	    }
	    
	    fclose($fp);
	    
	    return $data;
	}

}
