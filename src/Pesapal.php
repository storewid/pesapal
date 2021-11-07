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

    private string $amount,
        $firstname,
        $lastname,
        $email,
        $phone_number,
        $type,
        $description,
        $reference,
        $post_xml;


    public function __construct($key, $secret, $endpoint, $currency, $callback, $token = null)
    {

        $this->key = $key;
        $this->secret = $secret;
        $this->endpoint = $endpoint;
        $this->currency = $currency;
        $this->callback = $callback;
        $this->token = $token;
    }

    public function processpayment($firstname, $lastname, $phone_number, $email, $amount, $description, $reference, $type = "MERCHANT",)
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
