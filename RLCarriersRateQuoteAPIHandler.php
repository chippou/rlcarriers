<?php

class RLCarriersRateQuoteAPIHandler {
    private $soapClient;
    private $APIKey;
    private $rateParams;
    private $errors;
    private $endPointError;


    /**
     * Create a new RLCarriersRateQuoteAPIHandler object
     * @param String $apiEndpoint RLCarriers rate quote API endpoint
     * @param String $APIKey API key
     */
    public function __construct($APIKey = null) {
        $primaryAPIEndPoint = "https://api.rlcarriers.com/1.0.3/RateQuoteService.asmx?WSDL";
        $this->APIKey = $APIKey ? $APIKey : "UtMmMDFmEyZjhmYzctN2FhYy00NmRhLWEyYzNDAxJmNTYjJDC";
        $this->endPointError = false;
        try {
            $this->soapClient = new SoapClient($primaryAPIEndPoint);
        } catch(Exception $e) {
            $this->endPointError = true;
        }
        $this->errors = array();
    }

    /**
     * Get rate quote by parameters
     * @param Array $rateParams 
     *      $rateParams = [
     *          'QuoteType'    => (string) Quote type. Required.
     *          'DeclaredValue'=> (number) Declared value. Required.
     *          'Origin'       => [
     *              'ZipOrPostalCode' => (string) Zip code. Required,
     *              'CountryCode'     => (string) Country code. Required, either CAN or USA.
     *              'StateOrProvince' => (string) State or province. Optional.
     *              'City'            => (string) City. Optional
     *          ]
     *          'Destination'  => [
     *              'ZipOrPostalCode' => (string) Zip code. Required.
     *              'CountryCode'     => (string) Country code. Required, either CAN or USA.
     *              'StateOrProvince' => (string) State or province. Optional.
     *              'City'            => (string) City. Optional
     *          ]
     *          'Items'        => [
     *              Class             => (number) item class. Required
     *              Weight            => (number) item weight. Required
     *          ]
     *      ]
     * @param Boolean $hasAccessorialOptions Add ResidentialDelivery
     *  and DestinationLiftgate to Accessorials parameters
     * @return Object $result RLCarrier response object
     */
    public function getRateQuote($rateParams, $hasDestinationLiftgate = false, $hasResidentialDelivery = false) {
        $this->rateParams = $rateParams;
        $this->validateParams(); // validate request parameters
        // Set initial params default value
        $this->initDefaultParams($rateParams, $hasDestinationLiftgate, $hasResidentialDelivery);
        return $this->handleGetRateQuoteRequestAndResponse($rateParams);
    }

    /**
     * Get paller types
     * @return Void
     */
    public function getPalletTypes() {
        try {
            return json_encode($this->soapClient->GetPalletTypes(array('APIKey' => $this->APIKey)));
        } catch (\Exception $e) {
            print_r($e->getMessage());
        }
    }

    /** 
     * Validate request parameters
     * @return Void
     */
    public function validateParams() {
        $requiredParams = [
            'QuoteType', 'DeclaredValue', 
            'Origin', 'Destination' , 'ZipOrPostalCode',
            'Items', 'CountryCode', 'Class', 'Weight'
        ];
        $stringParams = ['QuoteType', 'ZipOrPostalCode', 'CountryCode'];
        $numberParams = ['Class', 'DeclaredValue'];
        $quoteTypeParams = ['Domestic', 'International'];
        $this->walkArrayAndValidateParams($this->rateParams, $requiredParams, $stringParams, $numberParams, $quoteTypeParams);
        if (count($requiredParams) > 0) {
            foreach($requiredParams as $param) {
                $this->errors[$param] = [];
                array_push($this->errors[$param], "$param is required!");
            }
        }
        
        if (count($this->errors) > 0) {
            throw new \Exception("Invalid request parameters! ". print_r($this->errors, true));
        }
    }

    /**
     * Walk though request parameters array and validate
     * @param Array $array Request parameters
     * @param Array $requiredParams Required parameters
     * @param Array $stringParams Parameters with string type
     * @param Array $numberParams Parameters with numer type
     * @param Array $quoteTypeParams Quotetype parameter as Domestic or International
     * @return Void
     */
    private function walkArrayAndValidateParams($array, &$requiredParams, &$stringParams, &$numberParams, &$quoteTypeParams){
        $index = 0;
        foreach($array as $key => $value){
            //If $value is an array.
            if(is_array($value)){
                // Check if the $key is required
                if (!in_array(gettype($key), ["float", "integer", "double"])) {
                    if (in_array($key, $requiredParams)) {
                        $index = array_search($key, $requiredParams);
                        unset($requiredParams[$index]);
                    }
                }
                //We need to loop through it.
                $this->walkArrayAndValidateParams($value, $requiredParams, $stringParams, $numberParams, $quoteTypeParams);
            } else{
                    // It is not an array, check if the $key is required 
                   if (in_array($key, $requiredParams)) {
                        $index = array_search($key, $requiredParams);
                        unset($requiredParams[$index]);
                   }
                   // validate type of parameters
                   $this->check2ndCondition($stringParams, $numberParams, $quoteTypeParams, $key, $value);
            }   
        }
    }

    /**
     * Check for value type of request parameters
     * @param Array $stringParams Parameters in string
     * @param Array $numberParams Parameters in number
     * @param Array $quoteTypeParams
     * @param String $key Parameter key
     * @param Any $value Paramter value
     * @return Void
     */
    private function check2ndCondition($stringParams, $numberParams, $quoteTypeParams, $key, $value) {
        if (gettype($value) !== "string" && in_array($key, $stringParams)) {
            if (!array_key_exists($key, $this->errors)) {
                $this->errors[$key] = [];
            }
            array_push($this->errors[$key], "$key must be a string!");
        }

        if (!in_array(gettype($value), ["float", "integer", "double"]) && in_array($key, $numberParams)) {
            if (!array_key_exists($key, $this->errors)) {
                $this->errors[$key] = [];
            }
            array_push($this->errors[$key], "$key must be a number!");
        }

        if ($key === 'QuoteType' && !in_array($value, $quoteTypeParams)) {
            if (!array_key_exists($key, $this->errors)) {
                $this->errors[$key] = [];
            }
            array_push($this->errors[$key], "$key must be either ".implode(" or ", $quoteTypeParams));
        }
    }

    /**
     * Init default parameters
     * @return Void
     */
    private function initDefaultParams(&$rateParams, $hasDestinationLiftgate, $hasResidentialDelivery) {
        $rateParams["CODAmount"] = 0;
        foreach($rateParams["Items"] as &$item) {
            $item["Width"] = 0;
            $item["Height"] = 0;
            $item["Length"] = 0;
        }

        if (($hasDestinationLiftgate || $hasResidentialDelivery) 
            && !array_key_exists("Accessorials", $rateParams)) {
            $rateParams["Accessorials"] = [];
            if ($hasDestinationLiftgate) {
                array_push($rateParams["Accessorials"], "DestinationLiftgate");
            }
            if ($hasResidentialDelivery) {
                array_push($rateParams["Accessorials"], "ResidentialDelivery");
            }
        }
    }

    /**
     * Get ratequote response from soap endpoint
     * @param Array $rateParams Ratequote parameters, described above
     * @return stdClass $responseData
     *      $responseData = [
     *          'success'           => (boolean) Response status.
     *          'responseMessages'  => (object) Response messages from SOAP server.
     *          'serviceLevels'     => (array) Service levels
     *          'rateQuoteMessages' => (array) Rate quote messages
     *          'fullResponseData'  => (object) Raw response data from SOAP server
     *      ]
     */
    private function handleGetRateQuoteRequestAndResponse($rateParams) {
        $responseData = new stdClass();
        if (!$this->endPointError) {
            $rawResponse = $this->soapClient->GetRateQuote(array('APIKey' => $this->APIKey, 'request' => $rateParams));
            $responseData->success =  $rawResponse->GetRateQuoteResult->WasSuccess;
            $responseData->responseMessages = $rawResponse->GetRateQuoteResult->Messages;

            if ($responseData->success) {
                $rateQuoteResult = $rawResponse->GetRateQuoteResult->Result;
                if ($rateQuoteResult->ServiceLevels) {
                    $responseData->serviceLevels = $rateQuoteResult->ServiceLevels;
                }
                    
                if ($rateQuoteResult->Charges) {
                    $responseData->charges = $rateQuoteResult->Charges->Charge;
                }
                    
                if ($rateQuoteResult->Messages && $rateQuoteResult->Messages->Message) {
                    $responseData->rateQuoteMessages = $rateQuoteResult->Messages->Message;
                }
            }
            $responseData->fullResponseData = $rawResponse;
        } else {
            $responseData = (object) [
                "success" => false,
                "responseMessages" => [
                    "Messages" => [
                        "string" => [
                            "Could not connect to the Endpoint, something when wrong!"
                        ]
                    ]
                ]
            ];
        }

        return $responseData;
    }
}