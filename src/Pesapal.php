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

        $token = $this->getToken();

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
                'Authorization: Bearer ' . $token,
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


        return $response;
    }



    //instatnt push notification
    public function ipn($pesapal_transaction_tracking_id, $pesapalnotification)
    {
        if ($pesapalnotification == "CHANGE" && $pesapal_transaction_tracking_id != '') {
            $status = $this->status_query($pesapal_transaction_tracking_id);
            return $status;
        }
    }




    //pesapal version 3
    public function status_query($tracking_id)
    {
        $this->getToken();
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->endpoint . '/api/Transactions/GetTransactionStatus?orderTrackingId=' . $tracking_id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->token,
                'Accept: application/json',
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

    public function refund_request($confirmation_code, $amount, $username, $remarks)
    {

        $this->getToken();


        $token = $this->token;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->endpoint . '/api/Transactions/RefundRequest',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '
{
    "confirmation_code": "' . $confirmation_code . '",
    "amount": ' . $amount . ',
    "remarks": "' . $remarks . '",
    "username": "' . $username . '",
   
}
',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);


        return $response;
    }



    public function cancel_order($order_tracking_id)
    {

        $this->getToken();


        $token = $this->token;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->endpoint . '/api/Transactions/CancelOrder',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '
{
    "order_tracking_id": "' . $order_tracking_id . '"
    
}
',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);


        return $response;
    }
}
