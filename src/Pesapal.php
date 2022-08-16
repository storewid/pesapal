<?php

namespace Storewid\Pesapal;

use Storewid\Pesapal\OAuth\OAuthConsumer;
use Storewid\Pesapal\OAuth\OAuthRequest;
use Storewid\Pesapal\OAuth\OAuthSignatureMethod_HMAC_SHA1;

class Pesapal
{

    private $token;
    private $key;
    private $secret;
    private $endpoint;
    private $currency;
    private $callback;
    private $ipn_id;
    private $ipn_url;

    private string $amount,
        $firstname,
        $lastname,
        $email,
        $phone_number,
        $type,
        $description,
        $reference,
        $post_xml;


    public function __construct($key, $secret, $endpoint, $currency, $callback, $ipn_url = null, $token = null)
    {

        $this->key = $key;
        $this->secret = $secret;
        $this->endpoint = $endpoint;
        $this->currency = $currency;
        $this->callback = $callback;
        $this->token = $token;
        $this->ipn_url = $ipn_url;
    }

    public function getToken()
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->endpoint . '/api/Auth/RequestToken',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
          "consumer_key": "' . $this->key . '",
          "consumer_secret": "' . $this->secret . '"
        }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Accept: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $this->token = json_decode($response)->token;
        return $this->token;
    }

    public function registerIpn($ipn_url)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->endpoint . '/api/URLSetup/RegisterIPN',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
    "url": "' . $ipn_url . '",
    "ipn_notification_type": "GET"
}
',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json',
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $this->ipn_id = json_decode($response)->ipn_id;

        return $this->ipn_id;
    }


    public function submitOrder($firstname, $lastname, $phone_number, $email, $amount, $description, $reference, $type = "MERCHANT")
    {
        $this->getToken();
        $this->registerIpn($this->ipn_url);

        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->email = $email;
        $this->phone_number = $phone_number;
        $this->type = $type;
        $this->description = $description;
        $this->reference = $reference;
        $this->amount = $amount;

        $token = $this->token;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->endpoint . '/api/Transactions/SubmitOrderRequest',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '
{
    "id": "' . $reference . '",
    "currency": "' . $this->currency . '",
    "amount": ' . $amount . ',
    "description": "' . $description . '",
    "callback_url": "' . $this->callback . '",
    "notification_id": "' . $this->ipn_id . '",
    "billing_address": {
        "email_address": "' . $email . '",
        "phone_number": "",
        "country_code": "",
        "first_name": "' . $firstname . '",
        "middle_name": "",
        "last_name": "' . $lastname . '",
        "line_1": "",
        "line_2": "",
        "city": "",
        "state": "",
        "postal_code": "",
        "zip_code": ""
    }
}
',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json',
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $res = json_encode($response);


        return $res;
    }

    public function processpayment($firstname, $lastname, $phone_number, $email, $amount, $description, $reference, $type = "MERCHANT")
    {
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->email = $email;
        $this->phone_number = $phone_number;
        $this->type = $type;
        $this->description = $description;
        $this->reference = $reference;
        $this->amount = $amount;

        $token = NULL;


        $signature_method = new OAuthSignatureMethod_HMAC_SHA1();

        $params = [
            'amount' => $this->amount,
            'description' => $this->description,
            'type' => 'MERCHANT',
            'reference' => $this->reference,
            'first_name' => $this->firstname,
            'last_name' => $this->lastname,
            'email' => $this->email,
            'currency' => $this->currency,
            'phonenumber' => $this->phone_number,
            'width' => '100%',
            'height' => '100%',
        ];


        $xml = $this->construct_xml_request();


        $consumer = new OAuthConsumer($this->key, $this->secret);

        $iframe_src = OAuthRequest::from_consumer_and_token($consumer, $token, "GET", $this->endpoint,  $params);

        $iframe_src->set_parameter("oauth_callback", $this->callback);

        $iframe_src->set_parameter("pesapal_request_data", $xml);

        $iframe_src->sign_request($signature_method, $consumer, $token);

        return '<iframe src="' . $iframe_src . '" width="' . $params['width'] . '" height="' .  $params['height'] . '" scrolling="auto" frameBorder="0"> <p>Unable to load the payment page</p> </iframe>';
    }


    //instatnt push notification
    public function ipn($pesapal_merchant_reference, $pesapal_transaction_tracking_id, $pesapalnotification)
    {
        if ($pesapalnotification == "CHANGE" && $pesapal_transaction_tracking_id != '') {
            $status = $this->queryStatus($pesapal_merchant_reference, $pesapal_transaction_tracking_id);
            return $status;
        }
    }

    //this functionality queries for status of the transaction
    public function queryStatus($pesapal_merchant_reference, $pesapal_transaction_tracking_id)
    {
        $params = NULL;

        $consumer = new OAuthConsumer($this->key, $this->secret);

        $signature_method = new OAuthSignatureMethod_HMAC_SHA1();

        $request_status = OAuthRequest::from_consumer_and_token($consumer, $this->token, "GET", $this->endpoint, $params);

        $request_status->set_parameter("pesapal_merchant_reference", $pesapal_merchant_reference);

        $request_status->set_parameter("pesapal_transaction_tracking_id",  $pesapal_transaction_tracking_id);

        $request_status->sign_request($signature_method, $consumer, $this->token);




        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_status);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_HEADER, 1);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        if (defined('CURL_PROXY_REQUIRED')) if (CURL_PROXY_REQUIRED == 'True') {

            $proxy_tunnel_flag = (defined('CURL_PROXY_TUNNEL_FLAG') && strtoupper(CURL_PROXY_TUNNEL_FLAG) == 'FALSE') ? false : true;

            curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, $proxy_tunnel_flag);

            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);

            curl_setopt($ch, CURLOPT_PROXY, CURL_PROXY_SERVER_DETAILS);
        }

        $response = curl_exec($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        $raw_header  = substr($response, 0, $header_size - 4);

        $headerArray = explode("\r\n\r\n", $raw_header);
        $header      = $headerArray[count($headerArray) - 1];

        $elements = preg_split("/=/", substr($response, $header_size));
        $status = $elements[1];
        curl_close($ch);

        return $status;
    }

    public function construct_xml_request()
    {

        $post_xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
    <PesapalDirectOrderInfo 
        xmlns:xsi=\"http://www.w3.org/2001/XMLSchemainstance\" 
        xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" 
        Amount=\"" . $this->amount . "\" 
        Description=\"" . $this->description . "\" 
        Type=\"" . $this->type . "\" 
        Reference=\"" . $this->reference . "\" 
        FirstName=\"" . $this->firstname . "\" 
        LastName=\"" . $this->lastname . "\" 
        Currency=\"" . $this->currency . "\" 
        Email=\"" . $this->email . "\" 
        PhoneNumber=\"" . $this->phone_number . "\" 
        xmlns=\"http://www.pesapal.com\" />";

        $post_xml = htmlentities($post_xml);
        return $post_xml;
    }
}
