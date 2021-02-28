<?php
App::uses('AppHelper', 'View/Helper');

require '../vendor/autoload.php';
class StripePaymentHelper extends AppHelper
{
    public function setPrice($price, $customerEmail, $customerSiteID, $customerName)
    {
        $stripe = new \Stripe\StripeClient(
            'sk key'
        );
        $customer = $stripe->customers->create([
            'email' => $customerEmail,
            'name' => $customerName,
            'description' => "Payment from the User ID: ".$customerSiteID
          ]);

        $price = (int)$price*100;
        $intent = $stripe->paymentIntents->create([
          'amount' => $price,
          'currency' => 'GBP',
          'customer' => $customer->id
        ]);
        // remove old payment intents
        $unixTimestamp = mktime(0, 0, 0, date("m"), date("d")-1, date("Y"));
        $res = $stripe->paymentIntents->all(array("created" => array("lte" => $unixTimestamp), "limit" => 100));
        $errorStatus = array("requires_payment_method", "requires_confirmation", "requires_action", "requires_capture");
        foreach ($res->data as $key => $value) {
            if (in_array($value->status, $errorStatus)) {
                $stripe->paymentIntents->cancel(
                    $value->id,
                    []
                );
            }
        }
        return json_encode(array('client_secret' => $intent->client_secret));
    }
}
