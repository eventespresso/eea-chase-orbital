<?php

/**
 * ChasePaymentech_Request
 *
 * @author Routy Development LLC <support@getrouy.com>
 * @copyright (c) 2013, Routy Development
 * @package ChasePaymentech
 * @license see license.txt
 * @version 1.1
 * @link http://download.chasepaymentech.com/docs/orbital/orbital_gateway_xml_specification.pdf
 *
 */
abstract class ChasePaymentech_Request {

    protected $_username;
    protected $_password;
    protected $_merchant_id;
    protected $_bin = '000002'; // PNS
    protected $_terminal_id = '001'; // PNS Terminal
    protected $_industry_type = 'EC'; //E-commerce, RC for recurring payment
    protected $_log_file = false;
    protected $_test_mode = false;
    protected $_log_requests = false;
    protected $_post_string;
    protected $_retry_count = 0;
    protected $_version = '1.0';
    protected $_fields = array();
    protected $_request_xml;
    protected $_response = false;
    protected $_available_fields = array();
    protected $_curl_options = array();
    protected $_additional_headers = array();
    public $verify_fields = true;

    abstract protected function _handleResponse($response);

    /**
     * __construct
     *
     * Accepts connection parameters to overwrite any constants that may be
     * defined. Allows overwriting of standard settings through the options
     * array.
     *
     * @param string $username
     * @param string $password
     * @param string $merchant_id
     * @param array $options
     *
     */
    public function __construct($username = false, $password = false, $merchant_id = false, array $options = array()) {

        $this->_username = ($username ? $username : (defined('CHASEPAYMENTECH_USERNAME') ? CHASEPAYMENTECH_USERNAME : false));
        $this->_password = ($password ? $password : (defined('CHASEPAYMENTECH_PASSWORD') ? CHASEPAYMENTECH_PASSWORD : false));
        $this->_merchant_id = ($merchant_id ? $merchant_id : (defined('CHASEPAYMENTECH_MERCHANT_ID') ? CHASEPAYMENTECH_MERCHANT_ID : false));

        if (isset($options['bin'])) {
            $this->_bin = $options['bin'];
        }

        if (isset($options['terminal_id'])) {
            $this->_terminal_id = $options['terminal_id'];
        }

        if (isset($options['industry_type'])) {
            $this->_industry_type = $options['industry_type'];
        }

        $this->_curl_options = array(
            CURLOPT_SSL_VERIFYHOST => true,
            CURLOPT_FRESH_CONNECT => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_USERAGENT => 'Chase Paymentech Gateway/' . $this->_version
        );
    }

    public function __set($name, $value) {
        return $this->setField($name, $value);
    }

    /**
     * setRetryCount
     *
     * @param $number
     */
    public function setRetryCount($number) {
        $this->_retry_count = $number;

        return $this;
    }

    /**
     * setTraceNumber
     *
     * If you would like to implement retry logic, you will need to submit a
     * trace number with your request.
     *
     * @link http://download.chasepaymentech.com/docs/orbital/perl_sdk_developers_guide.pdf See page 46, or search TraceNumber
     * @param int $number
     *
     */
    public function setTraceNumber($number)
    {
        if(is_numeric($number)){
            $this->_curl_options[CURLOPT_TIMEOUT] = 90;
            $this->_additional_headers[] = 'Trace-Number: ' . $number;
        }

        return $this;
    }

    /**
     * setCurlOption
     *
     * Allows for user specified curl options to be injected into the request.
     *
     * @param int $option
     * @param mixed $value
     *
     */
    public function setCurlOption($option, $value)
    {
        $this->_curl_options[$option] = $value;

        return $this;
    }

    /**
     * setCurlOptions
     *
     * Allows for user specified curl options to be injected into the request.
     *
     * @param int $option
     *
     */
    public function setCurlOptions(array $options)
    {
        $this->_curl_options = $options;

        return $this;
    }

    /**
     * setFields
     *
     * Set XML elements for the request.
     *
     * @param array Key => Value pairs of XML elements.
     *
     */
    public function setFields(array $fields) {
        foreach($fields as $key => $value){
            $this->setField($key, $value);
        }

        return $this;
    }

    /**
     * setField
     *
     * Set XML element for the request.
     *
     * @param string $name
     * @param string $value
     *
     */
    public function setField($name, $value) {
        if($this->verify_fields && !in_array($name, $this->_available_fields)){
            throw new ChasePaymentechException('Invalid field. The field name you provided is not a valid field.');
        }
        $this->_fields[$name] = $value;

        return $this;
    }

    /**
     * _setPostString
     *
     * Stores the XML request to the object and returns the XML
     *
     * @return string
     *
     */
    protected function _setPostString() {

        $xml = $this->_request_xml->asXML();

        $this->_post_string = $xml;

        return $xml;

    }

    /**
     * getPostString
     *
     * Retuns the XML request string
     *
     * @return string
     *
     */
    public function getPostString() {
        return $this->_post_string;
    }

    /**
     * _getPostUrl
     *
     * Returns the current URL taking in account for the need to switch
     * servers for a retry upon failure of the prior URL.
     *
     * @return string
     *
     */
    protected function _getPostUrl() {

        /*
         * Chase provides multiple URLs in case of an inability
         * to connect to their first server.
         */
        $urls = array(
            'test' => array(
                'https://orbitalvar1.chasepaymentech.com/authorize',
                'https://orbitalvar2.chasepaymentech.com/authorize'
            ),
            'prod' => array(
                'https://orbital1.chasepaymentech.com/authorize',
                'https://orbital2.chasepaymentech.com/authorize'
            )
        );

        $mode = ($this->_test_mode == true) ? 'test' : 'prod';

        return $urls[$mode][$this->_retry_count];
    }

    /**
     * setLogFile
     *
     * Set location of the log file.
     *
     * @param string $path
     *
     */
    public function setLogFile($path)
    {
        $this->_log_file = $path;

        return $this;
    }

    /**
     * setTestMode
     *
     * In test mode, requests will be submitted to the test environment URLs.
     *
     * @param boolean $status
     *
     */
    public function setTestMode($status = false)
    {
        $this->_test_mode = $status;

        return $this;
    }

    /**
     * setLogRequests
     *
     * Log communications with the Orbital Gateway. Logs are cleaned of sensitive data prior to being stored, including
     * your Connection Credentials and the Credit Card Number / CVV of the transaction.
     *
     * @param boolean $status
     * @param boolean $force_logging
     * @throws ChasePaymentechException
     *
     */
    public function setLogRequests($status = false, $force_logging = false)
    {
        if($this->_test_mode === true || $status === false || $force_logging === true){
            $this->_log_requests = $status;
        } else {
            throw new ChasePaymentechException('Requests cannot be logged in production. Test mode must be enabled prior to enabling request logs.');
        }

        return $this;

    }

    /**
     * _sendRequest
     *
     * Submits request to Chase Paymentech Orbital Gateway
     *
     * @return ChasePaymentech_Response
     *
     */
    protected function _sendRequest() {

        $post_string = $this->_setPostString();

        /*
         * Retrieve the URL for the request
         */
        $url = $this->_getPostUrl();

        /*
         * Important that the header is sent as an array
         */
        $header = array('POST /AUTHORIZE HTTP/1.0');
        $header[] = 'MIME-Version: 1.0';
        $header[] = 'Content-type: Application/PTI56';
        $header[] = 'Content-length: ' . strlen($post_string);
        $header[] = 'Content-transfer-encoding: text';
        $header[] = 'Request-number: 1';
        $header[] = 'Document-type: Request';
        $header[] = 'Interface-Version: Chase Paymentech PHP Library' . $this->_version;

        /*
         * Loop through additional headers and add them to the request.
         *
         */
        if($this->_additional_headers && count($this->_additional_headers) > 0){
            foreach($this->_additional_headers as $_header){
                if(!empty($_header)){
                    $header[] = $_header;
                }
            }
        }

        $ch = curl_init($url);

        /*
         * Loop through the curl options and add them to the request.
         *
         */
        if($this->_curl_options && count($this->_curl_options) > 0){
            foreach($this->_curl_options as $opt_name => $opt_value){
                if(!in_array($opt_name, array(CURLOPT_RETURNTRANSFER, CURLOPT_HTTPHEADER, CURLOPT_POST, CURLOPT_POSTFIELDS))){
                    curl_setopt($ch, $opt_name, $opt_value);
                }
            }
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);

        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($this->_log_file) {

            $curl_error = curl_error($ch);

            if ($curl_error) {
                file_put_contents($this->_log_file, "----CURL ERROR @ ".date('Y-m-d H:i:s')." \----\n$curl_error\n\n", FILE_APPEND);
            }

            if ($status != 200) {
                file_put_contents($this->_log_file, "----CURL ERROR @ ".date('Y-m-d H:i:s')." \----\nUnable to connect to server; Response Code: {$status}. Retry Count: {$this->_retry_count}\n\n", FILE_APPEND);
            }

            if($this->_log_requests == true){
                file_put_contents($this->_log_file, "----Request @ ".date('Y-m-d H:i:s')." \----\n".(($this->_test_mode == false) ? $this->cleanLogEntry($this->_post_string) : $this->_post_string)."\n", FILE_APPEND);
                file_put_contents($this->_log_file, "Endpoint: " . $url . "\n", FILE_APPEND);
                file_put_contents($this->_log_file, "----Response @ ".date('Y-m-d H:i:s')." \----\n".(($this->_test_mode == false) ? $this->cleanLogEntry($response) : $response)."\n\n", FILE_APPEND);
            }
        }

        curl_close($ch);

        /*
         * If the request returns a non-successful HTTP code then we will retry the request and submit it through
         * a backup URL.
         */
        if ($status != 200) {
            $this->_retry_count++;
            if ($this->_retry_count <= 1) {
                return $this->_sendRequest();
            }
        }

        return $this->_handleResponse($response);
    }

    /**
     * cleanLogEntry
     *
     * Removes Credit Card numbers and CVV from logs
     *
     * @param $string Request/Response string to clean
     *
     */
    private function cleanLogEntry($string)
    {
        $string = preg_replace('/(<AccountNum>)([0-9]+)(<\/AccountNum>)/','$1**** REMOVED ****$3',$string);
        $string = preg_replace('/(<CardSecVal>)([0-9]+)(<\/CardSecVal>)/','$1**** REMOVED ****$3',$string);
        $string = preg_replace('/(<OrbitalConnectionUsername>)(\w+)(<\/OrbitalConnectionUsername>)/i','$1**** REMOVED ****$3',$string);
        $string = preg_replace('/(<OrbitalConnectionPassword>)(\w+)(<\/OrbitalConnectionPassword>)/i','$1**** REMOVED ****$3',$string);
        return $string;
    }

    /**
     * getCreditCardBrand
     *
     * @param $account_number Credit Card number to get brand for
     * @return mixed string on success | false on failure
     *
     */
    public static function getCreditCardBrand($account_number)
    {
        if(!empty($account_number)){
            $account_number = preg_replace('/\D/', '', $account_number);
            $cards = array(
                'Visa' => '^4[0-9]{12}(?:[0-9]{3})?$',
                'MasterCard' => '^5[1-5][0-9]{14}$',
                'Discover' => '^6(?:011|5[0-9]{2})[0-9]{12}$',
                'American Express' => '^3[47][0-9]{13}$',
                'JCB' => '^(?:2131|1800|35\d{3})\d{11}$',
                'Diners Club' => '^3(?:0[0-5]|[68][0-9])[0-9]{11}$'
            );
            foreach($cards as $type => $match){
                if(preg_match('/'.$match.'/', $account_number) === 1){
                    return $type;
                }
            }
        }
        return false;
    }

}

?>
