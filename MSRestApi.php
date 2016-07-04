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
 * @since     File available since Release 0.0.1
 */
/**
 * MSRestApi - The main class
 *
 * @author    Andrey Artahanov <azgalot9@gmail.com>
 * @copyright 2016 Andrey Artahanov <azgalot9@gmail.com>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version   Release: 0.0.1
 * @link      https://github.com/azgalot/ms-api/
 * @link      https://online.moysklad.ru/api/remap/1.0/doc/index.html
 * @since     Class available since Release 0.0.1
 */

class MSRestApi
{
    /**
     * URL from RestAPI
     */
    const URL = 'https://online.moysklad.ru/api/remap/1.0';

    /**
     * Methods
     */
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';

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
     * 
     * 
     * 
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

        "stock" => "report",

        "retailgood" => "pos",
        "openshift" => "pos",
        "closeshift" => "pos"
    );

    /**
     * Class constructor
     * @param string $login
     * @param string $key
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
     * @param string $uuid
     * @param string $type
     * @return object
     * @access public
     * @final
     */
    final public function getData($type, $uuid)
    {
        $this->checkUuid($uuid);

        return $this->curlRequest(sprintf('%s/'.$this->entity[$type].'/'.$type.'/%s', self::URL, $uuid));
    }

    /**
     * Get data params.
     *
     * @param string $uuid
     * @param string $type
     * @param string $param
     * @return object
     * @access public
     * @final
     */
    final public function getDataParam($type, $uuid, $param = "attributes")
    {
        $this->checkUuid($uuid);

        return $this->curlRequest(sprintf('%s/'.$this->entity[$type].'/'.$type.'/%s/%s', self::URL, $uuid, $param));
    }

    /**
     * Create good.
     *
     * @param json $data
     * @param string $type
     * @return object
     * @access public
     * @final
     */
    final public function createData($type, $data)
    {
        $parameters['data'] = $data;

        return $this->curlRequest(sprintf('%s/'.$this->entity[$type].'/'.$type, self::URL), self::METHOD_PUT, $parameters);
    }

    /**
     * Delete good.
     *
     * @param string $uuid
     * @param string $type
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
     * Get list good.
     *
     * @param array $filter
     * @param integer $offset
     * @param integer $limit
     * @param string $type
     * @param array $filters
     * @return object
     * @access public
     * @final
     */
    final public function getDataList($type, $offset = 0, $limit = 100, $filters = array())
    {
        $parameters['offset'] = $offset;
        $parameters['limit'] = $limit;
        $parameters['filters'] = $filters;
    
        return $this->curlRequest(sprintf('%s/'.$this->entity[$type].'/'.$type, self::URL), self::METHOD_GET, $parameters);
    }

    /**
     * Get metadata.
     *
     * @param string $uuid
     * @param string $type
     * @return object
     * @access public
     * @final
     */
    final public function getMetaDataList($type)
    {
        return $this->curlRequest(sprintf('%s/'.$this->entity[$type].'/'.$type.'/metadata', self::URL));
    }

    /**
     * Update list good.
     *
     * @param json $data
     * @param string $type
     * @return object
     * @access public
     * @final
     */
    final public function updateDataList($type, $data)
    {
        $parameters['data'] = $data;
    
        return $this->curlRequest(sprintf('%s/'.$this->entity[$type].'/'.$type, self::URL), self::METHOD_PUT, $parameters);
    }

    /**
     * Delete list good.
     *
     * @param json $data
     * @param string $type
     * @return object
     * @access public
     * @final
     */
    final public function deleteDataList($type, $data)
    {
        $parameters['data'] = $data;

        return $this->curlRequest(sprintf('%s/'.$this->entity[$type].'/'.$type, self::URL), self::METHOD_POST, $parameters);
    }



    /**
     * Execution of the request
     * 
     * @param string $url
     * @param string $method
     * @param array $parameters
     * @param integer $timeout
     * @return mixed
     * @throws CurlException
     * @throws MSException
     * @access protected
     */
    protected function curlRequest($url, $method = 'GET', $parameters = null)
    {
        set_time_limit(0);

        time_nanosleep(0, 250000000);

        if (!is_null($parameters) && $method == self::METHOD_GET) {
            $url .= $this->httpBuildQuery($parameters);
        }

        if (!$this->curl) {
            $this->curl = curl_init();
        }

        //Set general arguments
        curl_setopt($this->curl, CURLOPT_USERAGENT, 'MS-API-client/1.1');
        curl_setopt($this->curl, CURLOPT_USERPWD, "{$this->login}:{$this->password}");
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_FAILONERROR, false);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, $this->getTimeout());
        curl_setopt($this->curl, CURLOPT_POST, false);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, array());
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array());

        if ($method == self::METHOD_POST) {
            curl_setopt($this->curl, CURLOPT_POST, true);
        } elseif (in_array($method, array(self::METHOD_PUT, self::METHOD_DELETE))) {
            curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method);
        }

        if (
            !is_null($parameters) &&
            in_array($method, array(self::METHOD_POST, self::METHOD_PUT, self::METHOD_DELETE)) &&
            isset($parameters['data'])
        ) {
            // if() {} // добавить проверку на величину передаваемых данных!!!
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Accept: */*'
            ));
            $data = json_encode($parameters['data']);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
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
     * @return SimpleXMLElement|DOMDocument
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
     * @param SimpleXMLElement|DOMDocument $result
     * @return string
     * @access private
     */
    private function getError($result)
    {
        $error = "";
        if(!empty($result['errors'])){
            foreach ($result['errors'] as $err) {
                $error .= "[".date("Y-m-d H:i:s")."] Error ".$err['parameter'].": ".$err['error']."\n";
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
        if (isset($parameters['filters']) && is_array($parameters['filters'])) {
            $filter = array();
            foreach ($parameters['filters'] as $name => $value) {
                $filter[$name] = $value;
            }
            unset($parameters['filters'], $name, $value);
            $parameters = array_merge($parameters, $filter);
        }

        return '?' . http_build_query($parameters);
    }

    /**
     * It clears the document from the trash.
     *
     * @param DOMDocument $document
     * @return DOMDocument
     * @access private
     */
    private function clearDomDocument(DOMDocument $document)
    {
        $tags = array('head', 'h1', 'h3');

        foreach ($tags as $tag) {
            $element = $document->getElementsByTagName($tag);
            if ($element->length > 0) {
                $element->item(0)->parentNode->removeChild($element->item(0));
            }
        }

        return $document;
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
    }

    /**
     * Do some actions when instance destroyed
     * @access public
     */
    public function __destruct()
    {
        curl_close($this->curl);
    }

    /**
     * @return integer
     * @access public
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param integer $timeout
     * @return MSRestApi
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;

        return $this;
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
