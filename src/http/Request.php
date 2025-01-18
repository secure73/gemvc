<?php

namespace Gemvc\Http;

use Gemvc\Helper\TypeChecker;
use Gemvc\Helper\TypeHelper;
use Gemvc\Http\JWTToken;

/**
 * Class Request provides a structured way for managing and validating incoming HTTP request data,
 * handling errors, and forward request to external APIs
 */
class Request
{
    public ?string $jwtTokenStringInHeader;
    public string $requestedUrl;
    public ?string $queryString;
    public ?string $error;
    private ?JWTToken $token;
    /**
     * @var null|string|array<string>
     */
    public null|string|array $authorizationHeader;
    public ?string $remoteAddress;
    /**
     * @var array<mixed>
     */
    public null|array $files;
    /**
     * @var array<mixed>
     */
    public array $post;

    /**
     * @var array<mixed>
     */
    public null|array $put;

    /**
     * @var array<mixed>
     */
    public null|array $patch;

    /**
     * @var string|array<mixed>
     */
    public string|array $get;
    public string $userMachine;
    public ?string $requestMethod;
    private string $id;
    private string $time;
    private float $start_exec;

    public ?string $cookies;

    /**
     * Summary of _searchableGetValues
     * @var array<mixed> $_searchableGetValues
     */
    private array $_searchableGetValues = [];
    
    /**
     * Define which fields are allowed for exact matching
     * @var array<string>
     */
    private array $_filterableGetValues = [];

    /**
     * Define which fields are allowed for ordering
     * @var array<string>
     */
    private array $_orderableGetValues = [];



    public function __construct()
    {
        $this->token = null;
        $this->files = null;
        $this->cookies = null;
        $this->error = "";
        $this->authorizationHeader = null;
        $this->jwtTokenStringInHeader = null;
        $this->requestMethod = null;
        $this->start_exec = microtime(true);
        $this->id = TypeHelper::guid();
        $this->time = TypeHelper::timeStamp();
    }

    /**
     * you can use string,int,float,bool,array,json,email,date,integer,number,boolean,url,datetime,ip,ipv4,ipv6
     * @param array $searchableGetValues
     * @example $this->request->searchable(['email'=>'email','name' => 'string'])
     * @return void or die with response
     */
    public function searchable(array $searchableGetValues):void
    {
        if(is_array($this->get ) && count($this->get) > 0){
            foreach ($this->get as $get_key => $get_value) {
                if (array_key_exists($get_key, $searchableGetValues)) {
                    if(TypeChecker::check($searchableGetValues[$get_key],$get_value)){ {
                       $this->_searchableGetValues[$get_key] = $get_value;
                   }
                }
                else{
                       $this->error .= "invalid search value type for $get_key , accepted type is: ". $searchableGetValues[$get_key];
                   }
                }
            }
        }
        if(strlen($this->error) > 0){
            Response::badRequest($this->error)->show();
            die();
        }
    }

    public function filterable(array $filterableGetValues):void
    {
        if(is_array($this->get ) && count($this->get) > 0){
            foreach ($this->get as $get_key => $get_value) {
                if (array_key_exists($get_key, $filterableGetValues)) {
                    if(TypeChecker::check($filterableGetValues[$get_key],$get_value)){ {
                       $this->_filterableGetValues[$get_key] = $get_value;
                   }
                }
                else{
                       $this->error .= "invalid filter value type for $get_key , accepted type is: ". $filterableGetValues[$get_key];
                   }
                }
            }
        }
        if(strlen($this->error) > 0){
            Response::badRequest($this->error)->show();
            die();
        }
    }

    public function orderable(array $orderableGetValues):void
    {
        if(is_array($this->get ) && count($this->get) > 0){
            foreach ($this->get as $get_key => $get_value) {
                if (array_key_exists($get_key, $orderableGetValues)) {
                    if(TypeChecker::check($orderableGetValues[$get_key],$get_value)){ {
                       $this->_orderableGetValues[$get_key] = $get_value;
                   }
                }
                else{
                       $this->error .= "invalid order value type for $get_key , accepted type is: ". $orderableGetValues[$get_key];
                   }
                }
            }
        }
        if(strlen($this->error) > 0){
            Response::badRequest($this->error)->show();
            die();
        }
    }



    public function getSearchableArray():array
    {
        return $this->_searchableGetValues;
    }

    public function getFilterableArray():array
    {
        return $this->_filterableGetValues;
    }


    public function getOrderableArray():array
    {
        return $this->_orderableGetValues;
    }




    public function __get(string $name): mixed
    {
        return $this->$name;
    }

    public function getJwtToken(): JwtToken|null
    {
        return $this->token;
    }

    public function setJwtToken(JWTToken $jwtToken): bool
    {
        if (!$jwtToken->verify()) {
            return false;
        }
        $this->token = $jwtToken;
        return true;
    }

    /**
     * @return int|false 
     * in case of Authenticated user with valid JWT Token return int user_id, otherwise return false 
     */
    public function userId(): false|int
    {
        if (!$this->token || $this->token->isTokenValid) {
            return false;
        }
        return $this->token->user_id;
    }

    public function getError(): string|null
    {
        return $this->error;
    }

    public function getId(): string
    {
        return $this->id;
    }
    public function getTime(): string
    {
        return $this->time;
    }

    public function getStartExecutionTime(): float
    {
        return $this->start_exec;
    }

    public function intValuePost(string $key): int|false
    {
        if (!isset($this->post[$key]) || empty($this->post[$key]) || !is_numeric($this->post[$key])) {
            return false;
        }
        return (int) $this->post[$key];
    }

    public function floatValuePost(string $key): float|false
    {
        if (!isset($this->post[$key]) || empty($this->post[$key]) || !is_numeric($this->post[$key])) {
            return false;
        }
        return (float) $this->post[$key];
    }

    public function intValueGet(string $key): int|false
    {
        if (!isset($this->get[$key]) || empty($this->get[$key]) || !is_numeric($this->get[$key])) {
            return false;
        }
        return (int) $this->get[$key];
    }

    public function floatValueGet(string $key): float|false
    {
        if (!isset($this->get[$key]) || empty($this->get[$key]) || !is_numeric($this->get[$key])) {
            return false;
        }
        return (float) $this->get[$key];
    }

    /**
     * @param  array<string> $toValidatePost Define Post Schema to validation
     * @return bool
     * definePostSchma(['email'=>'email' , 'id'=>'int' , '?name' => 'string'])
     * @help   : ?name means it is optional
     * @in     case of false $this->error will be set
     */
    public function definePostSchema(array $toValidatePost): bool
    {
        return $this->defineSchema($toValidatePost, 'post');
    }

    /**
     * Summary of defineGetSchma
     * @param array<string> $toValidateGet
     * @return bool
     */
    public function defineGetSchema(array $toValidateGet): bool
    {
        return $this->defineSchema($toValidateGet, 'get');
    }

    /**
     * @param  array<string> $toValidatePut Define PUT Schema to validation
     * @return bool
     * definePutSchma(['email'=>'email' , 'id'=>'int' , '?name' => 'string'])
     * @help   : ?name means it is optional
     * @in     case of false $this->error will be set
     */
    public function definePutSchema(array $toValidatePut): bool
    {
        return $this->defineSchema($toValidatePut, 'put');
    }

    /**
     * @param  array<string> $toValidatePatch Define Patch Schema to validation
     * @return bool
     * definePatchSchma(['email'=>'email' , 'id'=>'int' , '?name' => 'string'])
     * @help   : ?name means it is optional
     * @in     case of false $this->error will be set
     */
    public function definePatchSchema(array $toValidatePatch): bool
    {
        return $this->defineSchema($toValidatePatch, 'patch');
    }

    public function setPostToObject(object $class): bool
    {
        try {
            foreach ($this->post as $key => $value) {
                if (property_exists($class, $key)) {
                    $class->$key = $value;
                }
            }
            return true;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }
        return false;
    }

    /**
     * Validates string lengths in a dictionary against min and max constraints.
     * @param array<string, string> $stringPosts A dictionary where keys are strings and values are strings in the format "min|max" (both min and max are optional).
     * @example $stringPosts = [
     *     'username' => '3|15',  // Min length 3, max length 15
     *     'password' => '8|',    // Min length 8, no max limit
     *     'nickname' => '|20',   // No min limit, max length 20
     *     'bio' => '',           // No min or max limit
     * ];
     * @return bool Returns true if all validations pass, false otherwise. Sets $this->error on failure.
     */
    public function validateStringPosts(array $stringPosts): bool
    {
        $errors = [];

        foreach ($stringPosts as $key => $constraint) {
            // Check if POST key exists
            if (!isset($this->post[$key])) {
                $errors[] = "Missing POST key '$key'";
                continue;  // Skip further checks if key is missing
            }

            // Set default min and max values
            $min = 0;
            $max = PHP_INT_MAX;

            // Parse the constraint string if provided
            if (!empty($constraint)) {
                list($minConstraint, $maxConstraint) = explode('|', $constraint) + [0, null];
                if (is_numeric($minConstraint)) {
                    $min = (int) $minConstraint;
                }
                if (is_numeric($maxConstraint)) {
                    $max = (int) $maxConstraint;
                }
            }
            if(!is_string($this->post[$key])){
                $errors[] = "POST key '$key' is not a string";
                continue;
            }

            // Validate string length against min and max constraints
            $stringLength = strlen($this->post[$key]);
            if ($stringLength < $min || $stringLength > $max) {
                $errors[] = "String length for post '$key' is {$stringLength}, which is outside the range ({$min}-{$max})";
            }
        }

        // If errors were found, set them and return false
        if (!empty($errors)) {
            $this->error = implode(', ', $errors);  // Combine all errors into a single string
            return false;
        }

        return true;
    }

    public function forwardToRemoteApi(string $remoteApiUrl): JsonResponse
    {

        $jsonResponse = new JsonResponse();
        $caller = new ApiCall();
        if($this->files !== null){
            $caller->files = $this->files;
        }
        $caller->authorizationHeader = $this->authorizationHeader;

        $response = $caller->post($remoteApiUrl, $this->post);
        if (!$response) {
            $jsonResponse->create($caller->http_response_code, null, 0, $caller->error);
            return $jsonResponse;
        }
        $response = json_decode($response);
        $jsonResponse->create($caller->http_response_code, $response);
        return $jsonResponse;
    }

    /**
     * @param string $remoteApiUrl
     * @param string|null $authorizationHeader
     * @return JsonResponse
     * this function forward incomming post request as post to remote API and return remote api response as JsonResponse Object
     */
    public function forwardPost(string $remoteApiUrl, string $authorizationHeader = null): JsonResponse
    {

        $jsonResponse = new JsonResponse();
        $caller = new ApiCall();
        if($this->files !== null){
            $caller->files = $this->files;
        }
        $caller->authorizationHeader = $authorizationHeader ? $authorizationHeader : $this->authorizationHeader;

        $response = $caller->post($remoteApiUrl, $this->post);
        if (!$response) {
            $jsonResponse->create($caller->http_response_code, null, 0, $caller->error);
            return $jsonResponse;
        }
        $response = json_decode($response);
        $jsonResponse->create($caller->http_response_code, $response);
        return $jsonResponse;
    }

    public static function mapPost(Request $request , object $object): void
    {
        $name = get_class($object);
        /*if (!is_array($request->post) || !count($request->post)) {
            $request->error = 'there is no incoming post detected';
            Response::badRequest("there is no incoming post detected for mappping to $name")->show();
            die();
        }*/
        foreach ($request->post as $postName => $value) {
            try {
                if (property_exists($object, $postName)) {
                    $object->$postName = $value;
                }
            } catch (\Exception $e) {
                $request->error = "post $postName cannot be set because " . $e->getMessage();
                Response::unprocessableEntity("post $postName cannot be set to $name because " . $e->getMessage())->show();
                die();
            }
        }
    }


    // Private methods
        /**
     * @param  array<string> $toValidatePost Define Post Schema to validation
     * @return bool
     * validatePosts(['email'=>'email' , 'id'=>'int' , '?name' => 'string'])
     * @help   : ?name means it is optional
     * @in     case of false $this->error will be set
     */
    private function defineSchema(array $toValidatePost , string $get_or_post): bool
    {
        $target = $this->post;
        if($get_or_post === 'get'){
            $target = $this->get;
        }
        if($get_or_post === 'put'){
            $target = $this->put;
        }
        elseif($get_or_post === 'patch'){
            $target = $this->patch;
        }
        //TODO: brake this function into smaller functions
        $errors = []; // Initialize an empty array to store errors
        $requires = [];
        $optionals = [];
        $all = [];
        foreach ($toValidatePost as $validation_key => $validationString) {
            if (substr($validation_key, 0, 1) === '?') {
                $validation_key = ltrim($validation_key, '?');
                $optionals[$validation_key] = $validationString;
            } else {
                $requires[$validation_key] = $validationString;
            }
            $all[$validation_key] = $validationString;
        }
        if(!is_array( $target )){ //if target is not array then return false
            $this->error = "there is no  $get_or_post data";
            return false;
        }
        foreach ($target as $postName => $postValue) {
            if (!array_key_exists($postName, $all)) {
                $errors[$postName] = "unwanted $get_or_post $postName";
                $target = [];
            }
        }
        if (count($errors) > 0) { //if unwanted post exists , stop process and return false
            foreach ($errors as $error) {
                $this->error .= $error . ', '; // Combine errors into a single string
            }
            return false;
        }

        foreach ($requires as $validation_key => $validation_value) {      //now only check existence of requires post 
            if ((!isset($target[$validation_key]) || empty($target[$validation_key]))) {
                $errors[] = "Missing required field: $validation_key";
            }
        }
        if (count($errors) > 0) { //if requires not exists , stop process and return false
            foreach ($errors as $error) {
                $this->error .= $error . ', '; // Combine errors into a single string
            }
            return false;
        }

        foreach ($requires as $validation_key => $validationString) { //now validate requires post Schema
            $validationResult = TypeChecker::check($validationString,$validation_key);
            if (!$validationResult) {
                $errors[] = "Invalid value for $get_or_post field: $validation_key";
            }
        }
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->error .= $error . ', '; // Combine errors into a single string
            }
            return false;
        }

        foreach ($optionals as $optionals_key => $optionals_value) { //check optionals if post exists and not null then do check

            if (isset($target[$optionals_key]) && !empty($target[$optionals_key])) {
                $validationResult = TypeChecker::check($optionals_value,$optionals_key);
                if (!$validationResult) {
                    $errors[] = "Invalid value for $get_or_post field: $optionals_key";
                }
            }
        }
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->error .= $error . ', '; // Combine errors into a single string
            }
            return false;
        }
        return true;
    }

}
