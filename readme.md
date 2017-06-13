
![Nem](https://image.ibb.co/hhtJAv/image.png)![Wordpress](https://image.ibb.co/nvJPiF/image.png) ![WooCommerce](https://image.ibb.co/mWsmVv/image.png)
# Connecting WordPress via WooCommerce to the NEM blockchain 
One of NEM’s many strengths is its API. Each node in the network is an API in itself. And you can interact with the Blockchain through a simple HTTP GET request.  

The purpose of this tutorial is to show how you can connect to the NEM blockchain with a very basic usage of PHP and a little Javascript inside Wordpress. 
  
## What we will build? 

We will create a plugin for Wordpress with WooCommerce.  

- It should have a settings page. 
![Setttings](http://imgh.us/jun_01__17.28.06.jpg) 

- It should inform the user how to pay with XEM from a wallet (copy) or mobile wallet (QR). 
  
![XEM wallet](http://imgh.us/jun_01__15.12.34_1.jpg)

- It should convert USD and EUR into XEM in real-time. 
- It should look for transactions on a NEM account and match it by a reference and amount.  
- If a matched transaction is found, create an order.  
  
## Tools 
So where do you start? Well, you will need some tools, and for this tutorial, we will use [Wordpress](https://wordpress.org/download/) and [WooCommerce](https://woocommerce.com/) to make a simple Proof Of Concept payment gateway that uses the NEM blockchain.   

Wordpress will be used to serve the website, and WooCommerce will add shopping functionality to WordPress.  

Wordpress is a PHP framework, so this is the language that will be used to serve content and create backend functionality. To communicate with the backend and add interactive functionality to the frontend, we will use a little Javascript with jQuery.  
  
  
## Getting started 
First, we need to setup the plugin in practice with WordPress. You can clone the tutorial project from [Github](https://github.com/RobertoSnap/woocommerce-gateway-xem-turtorial) by running this command: ```git clone https://github.com/RobertoSnap/woocommerce-gateway-xem-turtorial.git``` 

Let us walk through the most important files and functions. 
  
### woocommerce-gateway-xem.php 
```php 
  
   protected function __construct() { 
           add_action( 'plugins_loaded', array( $this, 'init_gateways' ) ); 
       } 
  
       public function init_gateways() { 
  
           include_once ( plugin_basename('includes/class-wc-gateway-xem.php')); 
           include_once ( plugin_basename('includes/class-xem-ajax.php')); 
           include_once ( plugin_basename('includes/class-xem-currency.php')); 
  
           /* 
            * Need make woocommerce aware of the Gateway class 
            * */ 
           add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateways' ) ); 
  
       } 
  
       public function add_gateways( $methods ) { 
           $methods[] = 'WC_Gateway_Xem'; 
           return $methods; 
       } 
``` 
This is where the plugin is initiated from Wordpress. It will only be initiated when active, so make sure your plugin is active from the WordPress backend.  We use a singleton class to initiate it ```$GLOBALS['wc_xem'] = WC_Xem::get_instance();``` 
  
![active Plugin](http://imgh.us/jun_01__14.43.33.jpg) 
  
```init_gateways``` is executed when Wordpress runs action ```plugins_loaded```.  Here we include the needed PHP files that will run the plugin and we use a WooCommerce function to add another custom payment gateway with this line ```add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateways' ) );``` 
  
### /includes/class-wc-gateway-xem.php 
```php 
class WC_Gateway_Xem extends WC_Payment_Gateway { 
  
} 
``` 
To create a new payment method in WooCommerce, you extend the class ```WC_Payment_Gateway```, and this is what attaches the layout and functionality when a customer checks out an order.  
  
#### __construct() 
Here we set information about the payment gateway and assign our options.  At the end of this function, we tell WordPress to include javascript code that we need in checkout.  
  
#### payment_fields() 
What we echo out here will be displayed in the payment method layout for the customer.  
  
![The QR is generated with Javascript](http://imgh.us/jun_01__15.12.34.jpg) 
  
As this point, we know the total amount for the order, time to transfer this amount into XEM. We do this by calling  ```Xem_Currency::get_xem_amount($this->get_order_total(), strtoupper(get_woocommerce_currency())```. This function converts USD and EUR into Micro XEM ( 1 XEM = 1000000 Micro XEM) with CoinMarketCap API. Notice, we always return Micro XEM to avoid storing money in a binary floating point. For humans it is easier to read XEM, so each time we display a XEM amount we run it through: 
```php 
function micro_xem_to_xem($amount){ 
       return $amount / 1000000; 
   } 
``` 
More about this below. We then lock down the amount in the backend with a session ```WC()->session->set('xem_amount', $xem_amount);``` so we can use it later when we need to check if we have received a payment for this amount.  
  
We also need to create a unique reference for the user. We do this with ```wp_create_nonce()``` which creates a hash from user information, session and [salt](https://codeseekah.com/2012/04/09/why-wordpress-authentication-unique-keys-and-salts-are-important/) which should be unique for each Wordpress installation.  
  
#### payment_scripts() 
Include the javascript and pass some variables from PHP to Javascript with ```wp_localize_script()```. 
  
#### validate_fields() 
This function is called right before WooCommerce finalizes a payment. We check for a matched XEM transaction. This is the server-side wall if someone would try to complete the checkout by editing the javascript. 
  
#### process_payment()  
If validate_fields() passes, we create the WooCommerce order and reset the payment information. We save the XEM transaction on the order, so we can later check that the XEM transaction is not used twice. 
  
#### /assets/js/xem-checkout.js 
The javascript adds some visual and initiates an ajax request. 
  
1. Visuals -  
  It is easy to generate a QR code that can be used by mobile NEM wallets; we only need to pass it the correct data. First, we create an object with the right data model in ```this.paymentData```. Then we pass this data over to the QR code generator which is a simple JS library I got from [davidshimjs](https://davidshimjs.github.io/qrcodejs/). You can see how this js library is attached to the global javascript scope in class-wc-gateway-xem->payment_scripts(). Notice that the QR expects Micro XEM, which is  
  
```javascript 
//Prepare NEM qr code data 
               // Invoice model for QR 
               this.paymentData = { 
                   "v": wc_xem_params.test ? 1 : 2, 
                   "type": 2, 
                   "data": { 
                       "addr": this.xemAddress.toUpperCase().replace(/-/g, ''), 
                       "amount": this.xemAmount, 
                       "msg": this.xemRef, 
                       "name": "XEM payment to " + wc_xem_params.store 
                   } 
               }; 
               //Generate the QR code with address 
               new QRCode("xem-qr", { 
                   text: JSON.stringify(this.paymentData), 
                   size: 256, 
                   fill: '#000', 
                   quiet: 0, 
                   ratio: 2 
               }); 
``` 
- We must also disable the payment button, as this checkout waits for payment to come in outside the system. 
- We add copy functionality to xem-address, reference and amount with another JS library. 
- We show progress to the user each time it checks for payment with another JS library. 
 
2. In checkForXemPayment it sends an AJAX request to the server calling it to check if a payment is received for this amount and reference. 
```javascript 
checkForXemPayment: function () { 
           this.nanobar.go(25); 
           $.ajax({ 
               url: wc_xem_params.wc_ajax_url, 
               type: 'post', 
               data: { 
                   action: 'woocommerce_check_for_payment', 
                   nounce: wc_xem_params.nounce 
               } 
           }).done(function (res) { 
               if(res.success === true && res.data.match === true){ 
                   $( '#place_order' ).attr( 'disabled', false); 
                   $( '#place_order' ).trigger( 'click'); 
               } 
               setTimeout(function() { 
                   xemPayment.checkForXemPayment(); 
               }, 5000); 
           }); 
           this.nanobar.go(100); 
       } 
``` 
This function is initiated once, and then will repeat itself every 5 seconds. If it returns with a valid transaction, it will simulate the user clicking the pay button which will then trigger class-wc-gateway-xem.php->validate_fields() where it checks that it also has a valid transaction server-side.  


### /includes/class-xem-ajax.php 
This is where the ajax request from the javascript ends up. There is some boilerplate in this php file which makes it easier to create ajax callable functions. To add an ajax funtion you only need to create the ```public static function my_ajax_function() { //Code here }``` and reference it inside ```add_ajax_events()``` by adding my "my_ajax_function" to the ```$ajax_events``` array. It will then be callable with this jQuery.ajax function: 
  
```javascript 
$.ajax({ 
               url: wc_xem_params.wc_ajax_url, 
               type: 'post', 
               data: { 
                   action: 'woocommerce_my_ajax_function', 
                   nounce: wc_xem_params.nounce 
               } 
           }).done(function (res) 
              console.log(res); 
              //Code when ajax call is done here 
           }); 
``` 
  
As you saw in the previous javascript snippet, the javascript calls woocommerce_check_for_payment which calls ```check_for_payment()```.  Now we can start checking if our NEM account has received any payments. The flow is as follows:  
1. We recreate the reference server side. If the user and session are the same, we will receive the same ref from the same hash. 
2. When we added the payment fields to checkout, we also saved the initial XEM amount in a session variable server-side. So we bring this up again and put it into ```$xem_amount_locked```.  
3.  We use ```class-nem-api.php``` to get the latest 25 transactions on our NEM account. More about this [here](http://bob.nem.ninja/docs/#requesting-transaction-data-for-an-account) and below.  
4. The message comes encrypted from NEM API. We decode the message ```$t->transaction->message->payload``` with  
```php 
   private static function hex2str($hex) { 
       $str = ''; 
       for($i=0;$i<strlen($hex);$i+=2) $str .= chr(hexdec(substr($hex,$i,2))); 
       return $str; 
   } 
``` 
5. If our reference and transaction message is the same we then go into checking the amount. Right now the NEM mobile wallet rounds amount down to 1 decimal, so we need to round ```$xem_amount_locked``` and ```$t->transaction->amount``` to one decimal in XEM. As you may see, the amount we receive from the NEM API is in Micro XEM, so we need to convert this to XEM (Could use Mircor XEM also). To do this we just divide it by / 1000000 (6 zeros). See function ```micro_xem_to_xem()```. In others words, we accept differences in amount decimals. 
6. If the amount is higher or equal the transaction amount, we have a matched transaction We then check that this transaction has not been used on any past orders with ```self::not_used_xem_transaction($matched_transaction)```. If it also passes this check, we send a success signal back to the javascript which will initate the class-wc-gateway-xem->validate_fields(). 
If the transaction passes, we will create the order and log the XEM transaction to that order.  


### /includes/class-nem-api.php 
This class takes care of sending the HTTP GET request for transactions to a NEM node. You can find servers that can receives requests [here](http://chain.nem.ninja/#/nodes/). We have hard coded some: 
```php 
private static $servers = array( 
       'bigalice3.nem.ninja', 
       'alice2.nem.ninja', 
       'go.nem.ninja' 
   ); 
``` 
To get the latest transactions on an account, just use the [/account/transfers/incoming](http://bob.nem.ninja/docs/#requesting-transaction-data-for-an-account) endpoint. Wordpress comes featured with a built-in HTTP GET request ```wp_remote_get( $url )```. When i work with Wordpress i allways normalize the request with ```rest_ensure_response( $res )```.  After that we can open the reponse with ```json_decode($res->data['body'])``` and you should see a list of transactions like this: 
```php 
Array 
( 
   [0] => stdClass Object 
       ( 
           [meta] => stdClass Object 
               ( 
                   [innerHash] => stdClass Object 
                       ( 
                       ) 
  
                   [id] => 112693 
                   [hash] => stdClass Object 
                       ( 
                           [data] => 5449a7c6e482995e41f6b0ae727ec374e75f50c9987bb2951fb34e24aacee530 
                       ) 
  
                   [height] => 952748 
               ) 
  
           [transaction] => stdClass Object 
               ( 
                   [timeStamp] => 67793818 
                   [amount] => 2308992 
                   [signature] => 46265c8a1f96ae2e2fe252ebb158d23b8ff8b191c8ec32ff1dc011061c4817d3ec7c4680141ef2c2ef1db92fbfa41e48b302c8502f926f5d73ed9233dfe0f300 
                   [fee] => 2000000 
                   [recipient] => TBFLJ2LTBOIFKRMYHSI3TEKK6ISQOBJILNTPAJ2Q 
                   [type] => 257 
                   [deadline] => 67797418 
                   [message] => stdClass Object 
                       ( 
                           [payload] => 34306562343335613139 
                           [type] => 1 
                       ) 
  
                   [version] => -1744830463 
                   [signer] => f7f20bc8ac6ab22cefff44529b7402ef4ccf5f105b3db1fad3200c142bdeacb4 
               ) 
  
       ) 
  
   [1] => stdClass Object 
       ( 
           [meta] => stdClass Object 
               ( 
                   [innerHash] => stdClass Object 
                       ( 
                       ) 
  
                   [id] => 112310 
                   [hash] => stdClass Object 
                       ( 
                           [data] => df7aa5d7502af8c2691b0bd6b60e7dcba904543d17ef68035dd259a1d61e8207 
                       ) 
  
                   [height] => 945919 
               ) 
  
           [transaction] => stdClass Object 
               ( 
                   [timeStamp] => 67380067 
                   [amount] => 5021757 
                   [signature] => 37e937e3274d839af062d8d4ddb15cc7f2903ae037ee35b2c4f36d6c80754b1eb7c1acabb1a94fefa731e83653fd19514565cd06ffe646855f5ba3c473962605 
                   [fee] => 2000000 
                   [recipient] => TBFLJ2LTBOIFKRMYHSI3TEKK6ISQOBJILNTPAJ2Q 
                   [type] => 257 
                   [deadline] => 67383667 
                   [message] => stdClass Object 
                       ( 
                           [payload] => 34383162613765383434 
                           [type] => 1 
                       ) 
  
                   [version] => -1744830463 
                   [signer] => 957c281f457c45254c545ba34d6ba1ea2cdf963f8aac4e2644bde663bd4ebb08 
               ) 
  
       ) 
) 
  
``` 
  
  
### /includes/class-xem-currency.php 
  
We need to convert USD and EUR to XEM. [CoinMarketCap](https://coinmarketcap.com/api/) has an API where we can get updated rates even without having an account.  
  
```php
   public static function get_xem_amount($amount, $currency = "EUR"){

        $response = false;
        $currency = strtoupper($currency);
        
        //Check if we allready have a transient with rates information
        if(!get_transient( 'xem_currency_data')) {

            //Get Value of NEM to currency
            switch ( $currency ) {
                case 'EUR':
                    $response = wp_remote_get('https://api.coinmarketcap.com/v1/ticker/nem/?convert=EUR');
                    break;
                case 'USD':
                    $response = wp_remote_get('https://api.coinmarketcap.com/v1/ticker/nem/?convert=USD');
                    break;
                case 'ALL':
                    $response = wp_remote_get('https://api.coinmarketcap.com/v1/ticker/nem/?convert=USD');
                    break;
                default:
                    self::error("Currency not supported");
            }

            $response = rest_ensure_response($response);
      
            //Decode the json string
            $data = json_decode($response->data['body']);
            //Set a transient that expires after one minute
            set_transient( 'xem_currency_data', $response->data['body'], 60  );
        }else{
            //We had a transient with rates so use this as $data
            $data = json_decode(get_transient( 'xem_currency_data'));
        }
    
        //Lets prepare callback
        $callback = array(
            $data[0]
        );

        //Set the amount
        switch ($currency) {
            case 'EUR':
                $callback['amount'] = $amount / $data[0]->price_eur;
                break;
            case 'USD':
                $callback['amount'] = $amount / $data[0]->price_usd;
                break;
            default:
                self::error("Currency not supported");
        }
        //Check if amount got set and round it. Allways return Micro XEM
        if (!empty($callback['amount']) && $callback['amount'] > 0){
            $amount_xem = round( $callback['amount'], 6, PHP_ROUND_HALF_UP );
            $amount_micro_xem = $amount_xem * 1000000;
            return (int)$amount_micro_xem;
        }

        return self::error("Something wrong with amount");

    }         
 ```
 As with the NEM API we also us ```wp_remote_get( $url ) ```.  We then check the response and calculate the amount in FIAT to amount XEM. I found when working with XEM that it is good practice to round the amount to 6 decimals which is the same number of zeros as is used in Micro XEM. I received a tip that it is good practice to always work with whole numbers when storing money, so Micro XEM is what this function returns. If we want to display it, just format it by dividing by 1 000 000.  

Because we only need updated rates each minute and the rates do not differ between users, we can use a [WordPress transient](https://codex.wordpress.org/Transients_API) to cache the rates.  
  
 
## What can be done better? 
This is a POC for a simple XEM payment gateway in WooCommerce. The source code is open and available for anyone to enhance [here](https://github.com/RobertoSnap/woocommerce-gateway-xem). If you want to create some other functionality against NEM Blockchain for Wordpress, feel free to copy and use code you need from this example.  

When making this tutorial, I saw several designs choices that could be improved upon for a production environment. Feel free to make your suggestions and enhancements in Github.  
  
* Js - Find a better solution for checking for payments then running an AJAX call each 5 seconds. 
* PHP - Just checking last 25 transactions to match payments is not a reliable solution for bigger WooCommerce sites.  
* PHP - There is a better way to create a unique checkout hash then wp_create_nounce(). 
* PHP - Support more currencies by triangulating from for example XEM -> USD -> CAD or any other way. 
* PHP - Better way of storing ref and amount server side. 
* Js - Make sure all checkout fields are filled before checking for payment. And notify the user. 
* PHP - Better way of matching transactions, then looping through every last one. 
 