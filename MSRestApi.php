<?php
/**
 * MSRestApi
 *
 * Copyright (c) 2016, Andrey Artahanov <azgalot9@gmail.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Andrey Artahanov nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package   ms-restapi
 * @author    Andrey Artahanov <azgalot9@gmail.com>
 * @copyright 2016 Andrey Artahanov <azgalot9@gmail.com>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @since     File available since Release 0.0.2
 */
/**
 * MSRestApi - The main class
 *
 * @author    Andrey Artahanov <azgalot9@gmail.com>
 * @copyright 2016 Andrey Artahanov <azgalot9@gmail.com>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version   Release: 0.0.2
 * @link      https://github.com/azgalot/ms-api/
 * @link      https://online.moysklad.ru/api/remap/1.1/doc/index.html
 * @since     Class available since Release 0.0.2
 */

class MSRestApi
{
    /**
     * URL from RestAPI
     */
    const URL = 'https://online.moysklad.ru/api/remap/1.1';

    /**
     * Methods
     */
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';

    /**
     * Filters
     */
    const FILTER_OPERANDS = array('=', '>', '<', '>=', '<=', '!=');

    /**
     * Requests
     */
    const REQUEST_ATTRIBUTES_MAIN = array('metadata', 'all', 'bystore', 'byoperation');
    const REQUEST_ATTRIBUTES_SECOND = array(
        'accounts',
        'contactpersons',
        'packs',
        'cashiers',
        'positions'
    );

    /**
     * Restrictions
     */
    const MAX_DATA_VALUE = 10 * 1024 * 1024;

    /**
     * Login access to API
     * @var string
     * @access protected
     */
    protected $login;

    /**
     * Password access to API
     * @var string
     * @access protected
     */
    protected $password;

    /**
     * Curl instance
     * @var resource
     * @access protected
     */
    protected $curl;

    /**
     * Curl timeout
     * @var integer
     * @access protected
     */
    protected $timeout = 60;

    /**
     * 
     */
    protected $retry;

    /**
     * Entity mapping
     * @var entity
     * @access protected
     */
    protected $entity = array(
        "counterparty" => "entity",
        "consignment" => "entity",
        "currency" => "entity",
        "productFolder" => "entity",
        "service" => "entity",
        "product" => "entity",
        "contract" => "entity",
        "variant" => "entity",
        "project" => "entity",
        "state" => "entity",
        "employee" => "entity",
        "store" => "entity",
        "organization" => "entity",
        "retailshift" => "entity",
        "retailstore" => "entity",
        "cashier" => "entity",
        "customerOrder" => "entity",
        "demand" => "entity",
        "invoiceout" => "entity",
        "retaildemand" => "entity",
/* v1.1 \/ */
        "purchaseOrder" => "entity",
        "supply" => "entity",
        "invoicein" => "entity",
        "paymentin" => "entity",
        "paymentout" => "entity",
        "cashin" => "entity",
        "cashout" => "entity",
        "companysettings" => "entity",
        "expenseItem" => "entity",
        "country" => "entity",
        "uom" => "entity",
        "customentity" => "entity",
        "salesreturn" => "entity",
        "purchasereturn" => "entity",
/* v1.1 /\ */

        "stock" => "report",

        "assortment" => "pos",
        "openshift" => "pos",
        "closeshift" => "pos"
    );

    /**
     * Class constructor
     * @param string $login
     * @param string $password
     * @return void
     * @access public
     * @final
     */
    final public function __construct($login, $password)
    {
        $this->login = $login;
        $this->password = $password;
        $this->curl = curl_init();
        $this->retry = 0;
    }

    /**
     * Get data.
     * 
     * @param array $params
     * @param array $filters
     * @return array
     * @access public
     * @final
     */
    final public function getData(
        $params,
        $filters = null
    )
    {
        if (empty($params)) {
            throw new InvalidArgumentException('The `params` can not be empty');
        }

        $uri = self::URL . '/' . $this->entity[reset($params)] . '/';

        foreach ($params as $param) {
            $uri .= $param . '/';
        }
        unset($param);
        $uri = trim($uri, '/');

        switch (count($params)) {
            case 1:
                $parameters['offset'] = (!empty($filters['limit'])) ? $filters['limit'] : 100;
                $parameters['limit'] = (!empty($filters['offset'])) ? $filters['offset'] : 0;
                $parameters['filters'] = (!empty($filters['filter'])) ? $filters['filter'] : null;
                break;
            case 2:
                if (!in_array($params[1], self::REQUEST_ATTRIBUTES_MAIN)) {
                    $this->checkUuid($params[1]);
                }
                break;
            case 3:
                $this->checkUuid($params[1]);
                if (!in_array($params[2], self::REQUEST_ATTRIBUTES_SECOND)) {
                    throw new InvalidArgumentException(sprintf('Wrong attribute: `%s`', $params[2]));
                }
                break;
            case 4:
                $this->checkUuid($params[1]);
                if (!in_array($params[2], self::REQUEST_ATTRIBUTES_SECOND)) {
                    throw new InvalidArgumentException(sprintf('Wrong attribute: `%s`', $params[2]));
                }
                $this->checkUuid($params[3]);
                break;
        }

        return $this->curlRequest($uri, self::METHOD_GET, $filters);
    }

    /**
     * Create data.
     *
     * @param json $type
     * @param string $data
     * @return object
     * @access public
     * @final
     */
    final public function createData($type, $data)
    {
        $parameters['data'] = $data;

        return $this->curlRequest(sprintf('%s/'.$this->entity[$type].'/'.$type, self::URL), self::METHOD_POST, $parameters);
    }

    /**
     * Update data.
     *
     * @param string $type
     * @param string $uuid
     * @param json $data
     * @return object
     * @access public
     * @final
     */
    final public function updateData($type, $uuid, $data)
    {
        $parameters['data'] = $data;

        return $this->curlRequest(sprintf('%s/'.$this->entity[$type].'/'.$type.'/%s', self::URL, $uuid), self::METHOD_PUT, $parameters);
    }

    /**
     * Delete data.
     *
     * @param string $type
     * @param string $uuid
     * @return object
     * @access public
     * @final
     */
    final public function deleteData($type, $uuid)
    {
        $this->checkUuid($uuid);

        return $this->curlRequest(sprintf('%s/'.$this->entity[$type].'/'.$type.'/%s', self::URL, $uuid), self::METHOD_DELETE);
    }

    /**
     * Execution of the request
     * 
     * @param string $url
     * @param string $method
     * @param array $parameters
     * @return mixed
     * @throws CurlException
     * @throws MSException
     * @access protected
     */
    public function curlRequest($url, $method = 'GET', $parameters = null)
    {
        time_nanosleep(0, 250000000);

        if (!is_null($parameters) && !empty($parameters) && $method == self::METHOD_GET) {
            $url .= $this->httpBuildQuery($parameters);
        }

        if (!$this->curl) {
            $this->curl = curl_init();
        }

        //Set general arguments
        curl_setopt($this->curl, CURLOPT_USERPWD, "{$this->login}:{$this->password}");
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_FAILONERROR, false);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, 60);

        if (
            !is_null($parameters) &&
            in_array($method, array(self::METHOD_POST, self::METHOD_PUT)) &&
            !empty($parameters['data'])
        ) {
            if (strlen(json_encode($parameters['data'])) > self::MAX_DATA_VALUE) {
                throw new MSException(
                    sprintf(
                        'The POST data size should not exceed `%s` bytes',
                        self::MAX_DATA_VALUE
                    )
                );
            }
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json'
            ));
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($parameters['data']));
            if ($method == self::METHOD_PUT) {
                curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method);
            }
            if ($method == self::METHOD_POST) {
                curl_setopt($this->curl, CURLOPT_POST, true);
            }
        }

        if (in_array($method, array(self::METHOD_DELETE))) {
            curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json'
            ));
        }

        $response = curl_exec($this->curl);

        $statusCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

        $errno = curl_errno($this->curl);
        $error = curl_error($this->curl);

        if ($errno && in_array($errno, array(6, 7, 28, 34, 35)) && $this->retry < 3) {
            $errno = null;
            $error = null;
            $this->retry += 1;
            $this->curlRequest(
                $url,
                $method,
                $parameters
            );
        }

        if ($errno) {
            throw new CurlException($error, $errno);
        }

        $result = $this->getResult($response);

        if ($statusCode >= 400) {
            throw new MSException($this->getError($result), $statusCode);
        }

        return $result;
    }

    /**
     * Gets the query result.
     *
     * @param string $response
     * @return array
     * @access private
     */
    private function getResult($response)
    {
        $result = json_decode($response, true);

        return $result;
    }

    /**
     * Get error.
     * 
     * @param array
     * @return string
     * @access private
     */
    private function getError($result)
    {
        $error = "";
        if(!empty($result['errors'])){
            foreach ($result['errors'] as $err) {
                if(!empty($err['parameter'])){
                    $error .= "[".date("Y-m-d H:i:s")."] Error ".$err['parameter'].": ".$err['error']."\n";
                }else{
                    $error .= "[".date("Y-m-d H:i:s")."] Error: ".$err['error']."\n";
                }
            }
        }else{
            $error = "[".date("Y-m-d H:i:s")."] Internal server error";
        }

        return $error;
    }

    /**
     * Http build query.
     *
     * @param array $parameters
     * @return string
     * @access private
     */
    private function httpBuildQuery($parameters)
    {
        if (is_array($parameters)) {
            $params = array();
            $filter = '';
            $filters = array();
            foreach ($parameters as $name => $value) {
                if ($name == 'filter') {
                    if (!empty($value) & is_array($value)) {
                        $filter = '&' . $this->buildFilter($value);
                    }
                    continue;
                }
                $filters[$name] = $value;
            }
            unset($name, $value);
            $params = array_merge($params, $filters);
        }

        return '?' . http_build_query($params) . $filter;
    }

    /**
     * build filter.
     *
     * @param array $filter
     * @return string
     * @access private
     */
    private function buildFilter($filters)
    {
        $params = '';
        foreach ($filters as $filter) {
            if (!in_array($filter['operand'], self::FILTER_OPERANDS)) {
                continue;
            }
            $params .= $filter['name'] . $filter['operand'] . $filter['value'] . ';';
        }
        unset($filter);
        $params = trim($params, ';');

        return 'filter=' . $params;
    }

    /**
     * Check uuid.
     * 
     * @param string $uuid
     * @throws InvalidArgumentException
     * @access private
     */
    private function checkUuid($uuid)
    {
        if (is_null($uuid) || empty($uuid)) {
            throw new InvalidArgumentException('The `uuid` can not be empty');
        }

        if (!preg_match("#^[\w\d]{8}-[\w\d]{4}-[\w\d]{4}-[\w\d]{4}-[\w\d]{12}$#", $uuid)) {
            if (preg_match("#^[a-z\d]+$#i", $uuid)) {
                throw new InvalidArgumentException(sprintf('Wrong attribute: `%s`', $uuid));
            }
            throw new InvalidArgumentException('The `uuid` has invalid format');
        }
    }

    /**
     * Do some actions when instance destroyed
     * @access public
     */
    public function __destruct()
    {
        curl_close($this->curl);
    }
 
}

/**
 * Exception for CURL
 * @author Andrey Artahanov <azgalot9@gmail.com>
 */
class CurlException extends RuntimeException
{
}

/**
 * Exception for Moy Sklad
 * @author Andrey Artahanov <azgalot9@gmail.com>
 */
class MSException extends RuntimeException
{
}
