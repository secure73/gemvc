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
     * Summary of _arr_filterBy
     * @var array<mixed> $_arr_filterBy
     */
    private array $_arr_filterBy = [];

    /**
     * Define which fields are allowed for exact matching
     * @var array<string>
     */
    private array $_arr_find_like = [];

    private ?string $_sort_by;
    private ?string $_sort_by_asc;
    private int $_page_number = 1;
    private int $_per_page;



    public function __construct()
    {
        $this->token = null;
        $this->files = null;
        $this->cookies = null;
        $this->error = null;
        $this->_sort_by = null;
        $this->_sort_by_asc = null;
        $this->authorizationHeader = null;
        $this->jwtTokenStringInHeader = null;
        $this->requestMethod = null;
        $this->start_exec = microtime(true);
        $this->id = TypeHelper::guid();
        $this->time = TypeHelper::timeStamp();
        /**@phpstan-ignore-next-line */
        $this->_per_page = $_ENV["QUERY_LIMIT"] ?? 10;

    }

    /**
     * you can use string,int,float,bool,array,json,email,date,integer,number,boolean,url,datetime,ip,ipv4,ipv6
     * @param array<string> $searchableGetValues
     * @example $this->request->filterable(['email'=>'email','name' => 'string'])
     * @return void or die with response
     */
    public function filterable(array $searchableGetValues): void
    {
        if (isset($this->get["filter_by"])) {
            $getFilterBy = $this->get["filter_by"];
            if (is_string($getFilterBy) && strlen($getFilterBy) > 0) {
                $split_where = explode(",", $getFilterBy); {
                    foreach ($split_where as $item_string) {
                        $inhalt = explode("=", $item_string);
                        if (count($inhalt) == 2) {
                            if (array_key_exists($inhalt[0], $searchableGetValues)) {
                                if (TypeChecker::check($searchableGetValues[$inhalt[0]], $inhalt[1])) { {
                                        $this->_arr_filterBy[$inhalt[0]] = $inhalt[1];
                                    }
                                } else {
                                    $this->error .= "invalid search value type for" . $inhalt[0] . " , accepted type is: " . $searchableGetValues[$inhalt[0]];
                                }
                            }
                        } else {
                            Response::badRequest("filter_by request shall be formatted as key=value and seperated by , example: filter_by=country_id=3,company_id=4 ")->show();
                            die();
                        }
                    }
                }
            }
            if ($this->error) {
                Response::badRequest($this->error)->show();
                die();
            }
        }
    }

    /**
     * you can use string,int,float,bool,array,json,email,date,integer,number,boolean,url,datetime,ip,ipv4,ipv6
     * @param array<string> $filterableGetValues
     * @example $this->request->filterable(['email'=>'email','name' => 'string'])
     * @return void or die with response
     */
    public function findable(array $filterableGetValues): void
    {
        if (isset($this->get["find_like"])) {
            $getFindLike = $this->get["find_like"];
            if (is_string($getFindLike) && strlen($getFindLike) > 0) {
                $split_where = explode(",", $getFindLike); {
                    foreach ($split_where as $item_string) {
                        $inhalt = explode("=", $item_string);
                        if (count($inhalt) == 2) {
                            if (array_key_exists($inhalt[0], $filterableGetValues)) {
                                if (TypeChecker::check($filterableGetValues[$inhalt[0]], $inhalt[1])) { {
                                        $this->_arr_find_like[$inhalt[0]] = $inhalt[1];
                                    }
                                } else {
                                    $this->error .= "invalid search value type for" . $inhalt[0] . " , accepted type is: " . $filterableGetValues[$inhalt[0]];
                                }
                            }
                        } else {
                            Response::badRequest("find_like request shall be formatted as key=value and seperated by , example: find_like=name=anton,email=ant@ ")->show();
                            die();
                        }
                    }
                }
            }
            if ($this->error) {
                Response::badRequest($this->error)->show();
                die();
            }
        }
    }

    /**
     * @param array<string> $sortableGetValues
     * @example $this->request->sortable(['email','id])
     * @return void or die with response
     */
    public function sortable(array $sortableGetValues): void
    {
        if (isset($this->get["sort_by_asc"])) {
            if (is_string($this->get["sort_by_asc"]) && strlen($this->get["sort_by_asc"]) > 0) {
                if (in_array($this->get["sort_by_asc"], $sortableGetValues)) {
                    $this->_sort_by_asc = $this->get["sort_by_asc"];
                } else {
                    $this->error .= "invalid search value type for" . $this->get["sort_by"];
                }

                if ($this->error) {
                    Response::badRequest($this->error)->show();
                    die();
                }
            }
        }
        if (isset($this->get["sort_by"])) {
            if (is_string($this->get["sort_by"]) && strlen($this->get["sort_by"]) > 0) {
                if (in_array($this->get["sort_by"], $sortableGetValues)) {
                    $this->_sort_by = $this->get["sort_by"];
                } else {
                    $this->error .= "invalid search value type for " . $this->get["sort_by"];
                }
                if ($this->error) {
                    Response::badRequest($this->error)->show();
                    die();
                }
            }
        }
    }

    public function setPageNumber(): void
    {
        if (isset($this->get["page_number"])) {
            $result = $this->intValueGet("page_number");
            if ($result === false) {
                Response::badRequest("page_number shall be integer")->show();
                die();
            }
            if ($result < 0) {
                Response::badRequest("per_number shall be positive")->show();
                die();
            }
            $this->_page_number = $result;
        }
    }

    public function setPerPage(): void
    {
        if (isset($this->get["per_page"])) {
            $result = $this->intValueGet("page_number");
            if ($result === false) {
                Response::badRequest("per_page shall be integer")->show();
                die();
            }
            if ($result < 0) {
                Response::badRequest("per_page shall be positive")->show();
                die();
            }
            $this->_per_page = $result;
        }
    }

    public function getPageNumber(): int
    {
        return $this->_page_number;
    }

    public function getPerPage(): int
    {
        return $this->_per_page;
    }



    /**
     * Summary of getFilterable
     * @return array<mixed>
     */
    public function getFilterable(): array
    {
        return $this->_arr_filterBy;
    }

    /**
     * Summary of getFindable
     * @return array<mixed>
     */
    public function getFindable(): array
    {
        return $this->_arr_find_like;
    }


    /**
     * Summary of getSortable
     * @return string|null
     */
    public function getSortable(): string|null
    {
        return $this->_sort_by;
    }

    /**
     * Summary of getSortable
     * @return string|null
     */
    public function getSortableAsc(): string|null
    {
        return $this->_sort_by_asc;
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
            if (!is_string($this->post[$key])) {
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
        if ($this->files !== null) {
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
        if ($this->files !== null) {
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

    public static function mapPost(Request $request, object $object): void
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
    private function defineSchema(array $toValidatePost, string $get_or_post): bool
    {
        $target = $this->post;
        if ($get_or_post === 'get') {
            $target = $this->get;
        }
        if ($get_or_post === 'put') {
            $target = $this->put;
        } elseif ($get_or_post === 'patch') {
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
        if (!is_array($target)) { //if target is not array then return false
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
            $validationResult = TypeChecker::check($validationString, $validation_key);
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
                $validationResult = TypeChecker::check($optionals_value, $optionals_key);
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
