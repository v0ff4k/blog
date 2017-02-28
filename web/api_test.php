<?php

error_reporting(E_ALL);


function wsse_auth($username, $secret)
{
 
 //user salts must be fetchet before, we storet it here ! 
//    $salt = array(//user -> predefined salts
//    'root' => 'q9pujahufk04cwkkoso4ocwcoc8wco4',
//    'guest' => '39mrw78bkrms4gso4w48k4swccscosk',
//    'admin' => 'iylfd0dn55skggks8w0sggwg4wcsoc8',
//    );
    
    $salt_url = 'http://www.test.local/app_dev.php/'.$username.'/salt';
    $salt = implode('', file($salt_url));
 
//var_dump($salt);die("-*/-*/-*/-*/");
//    $ps =  $secret.$salt;//pass+salt
//    $sp =  $salt.$secret;//salt+pass
    
    date_default_timezone_set('Asia/Bishkek');
    //date in ISO 8601
    $created = date("c");//old was ISO 8601 ("Y-m-d\TH:i:sO") 
    // or  date(DATE_ISO8601, (time() - 86400))
    
    //create password like in simfony bcrypt(pass+salt)
    $options = [
    'cost' => 13,
    'salt' => $salt,
    ];
    
    //pass as it stores in the db, but we send it packed with nonce string !!!
    $secret = password_hash($secret, PASSWORD_BCRYPT, $options);//bcrypt pass+salt
    
    //salt strong base62 nonce string(in ideal, we must receive it from server)
    $nonce =  base64_encode( sha1(base64_decode(uniqid()) . $created . $secret, true));

    
    //password
    $digest = base64_encode(sha1(base64_decode($nonce).$created.$secret,true));
//waiting this
//$expected = base64_encode( sha1(base64_decode($nonce). $created. $secret, true));

    return sprintf('X-WSSE: UsernameToken Username="%s", PasswordDigest="%s", Nonce="%s", Created="%s"',
                                            $username,        $digest,        $nonce,        $created
    );
 //example   
//X-WSSE: UsernameToken Username="admin", PasswordDigest="b1Z6+89BbcbdDIHnG0u6OYeXRLk=", 
//Nonce="ZjhWdmdMWHVtWQ==", Created="2016-10-31T11:57:20Z"
}

if( isset($_GET['u']) && isset($_GET['p']) ){
    $user = $_GET['u'];
    $pass = $_GET['p'];
}

if( isset($_GET['rm']) && isset($_GET['u']) ){
$request = $_GET['rm'];//request method  [GET, PUT, POST, DELETE ]// get val/upload/formpost/
$val = $_GET['val'];//value for that request metod
}

// only auth user
if( $user && $pass &&  //auth   and not post some data
        !$request && !$val ){

        $curl_handle = curl_init();

        //var_dump(generate_wsse_header($user, $pass));die();
        $header  = array(
            /* "Cache-Control: max-age=0", *\
            *  "Pragma: no-cache",         */
            wsse_auth($user, $pass) 
            );

        curl_setopt($curl_handle, CURLOPT_URL, 'http://www.test.local/app_dev.php/api/');
        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $header);
        
        curl_setopt($curl_handle, CURLOPT_HEADER, false);//returns headers //4dev
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);//returns content //4dev
        curl_setopt($curl_handle, CURLOPT_MAXREDIRS, '10');//lim to redirect ONLY 10 times !!!
        curl_setopt($curl_handle, CURLOPT_AUTOREFERER, true);//allow to redirects
        curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, true);//follow  redirects

        echo "<pre>";
        print_r( curl_exec($curl_handle) );//print result of request !

        //echo "!!!!!!!!!!!!!!!!!!!!!!!!";
        //echo curl_getinfo($curl_handle, CURLINFO_HTTP_CODE); 

        curl_close($curl_handle);

    }
    
 // post some value
if(  $user && $pass &&  //auth   and  post some data
        $request && $val ){

        $curl_handle = curl_init();

        //var_dump(generate_wsse_header($user, $pass));die();
        //$header  = array("Cache-Control: max-age=0", "Pragma: no-cache" );

        curl_setopt($curl_handle, CURLOPT_URL, 'http://www.test.local/app_dev.php/api/');
        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $header);

        curl_setopt($curl_handle, CURLOPT_HEADER, false);//returns headers //4dev
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);//returns content //4dev
        curl_setopt($curl_handle, CURLOPT_MAXREDIRS, '10');//lim to redirect ONLY 10 times !!!
        curl_setopt($curl_handle, CURLOPT_AUTOREFERER, true);//allow to redirects
        curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, true);//follow  redirects
                
        curl_setopt( $curl_handle, CURLOPT_CUSTOMREQUEST, strtoupper($request) );// Request Method
        $curl_post_data = array(
        'value' => htmlentities($val),//some posted value IN HTML ENTITES !!!
        'someval' => 'some test',
        'apikey' => 'key001'//some api key
        );
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $curl_post_data);
        
        
        echo "<pre>";
        print_r( curl_exec($curl_handle) );//print result of request !

        curl_close($curl_handle);
}