<?php

declare(strict_types=1);

namespace App\Services\SOAP;

use Exception;
use SoapFault;
use SoapVar;

/**
 * Class ApiClient
 *
 * used to build xml soap requests and can be customized to whatever user needs
 * Abilities to do 1D or 2D Authenications   
 * Abilities to do basic Authenications [username, password]
 * Abilities to do normal soap requests
 * 
 * @example (new App\Services\SOAP\ApiClient())->sendRequest($attributes = [], $method = "getData");
 * 
 * @see https://www.php.net/manual/en/class.soapclient.php
 * 
 * @author Eng. Mohamed Ammar
 */
class ApiClient
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    
    protected string $cert;
    protected string $passphrase;
    protected string $localKey;
    
    protected string $data;

    protected string $endpoint;

    /**
     * ApiClient constructor.
     * @TODO to be customized depend on 
     * @throws Exception
     */
    public function __construct()
    {
        $this->baseUrl = config('services.SOAP.base_url');
        $this->username = config('services.SOAP.username');
        $this->password = config('services.SOAP.password');

        //$this->cert = config('services.SOAP.cert');
        //$this->passphrase = config('services.SOAP.passphrase');
        //$this->localKey = config('services.SOAP.localKey');

        /** @TODO to be customized depend on you case*/
        if (!$this->baseUrl || !$this->username || !$this->password || !$this->data) {
            throw new Exception('Not find API uri or API configurations.', 500);
        }
    }

    /**
     * @return SoapClient
     * @throws SoapFault
     */
    protected function httpClient(): SoapClient
    {
        $arrContextOptions=stream_context_create([
            /** used to disable SSL verification when requesting the WSDL PATH */
            "ssl" => [ 
                "verify_peer" => false,
                "verify_peer_name" => false,
            ],
            /** 2D Authentication */
            /**
            'ssl' => [
                "verify_peer"       => true,
                "verify_peer_name"  => true,
                'peer_name'         => $this->endpoint,
                'local_cert'        => Storage::getPath().'/'.$this->cert,
                'local_pk'          =>  Storage::getPath().'/'.$this->localKey,
                'passphrase'        => $this->passphrase
            ]*/
        ]);
        return new SoapClient($this->baseUrl, [
            'uri'                => $this->endpoint,
            'features'           => SOAP_SINGLE_ELEMENT_ARRAYS,
            //'style'              => SOAP_RPC,
            //'use'                => SOAP_ENCODED,
            'soap_version'       => SOAP_1_2,
            'cache_wsdl'         => WSDL_CACHE_NONE,
            'connection_timeout' => 15,
            'trace'              => true,
            'exceptions'         => true,
            'encoding'           => 'UTF-8',
            'stream_context'     => $arrContextOptions,
            
            /** 1D Authentication */
            //'local_cert'         => Storage::getPath().'/'.$this->cert,
            //'passphrase'         => $this->passphrase
            
            /** basic authentication */
            //'login' => $this->username,
            //'password' => $this->password,
        ]);
    }

    /**
     * The getData function allows the user to get Data from endpoint
     *
     * @param string $method 
     * @param array $attributes
     *
     * @return mixed
     * @throws Exception|SoapFault
     */
    public function sendRequest(array $attributes = [], string $method = "getData")
    {
        $body = $this->requestBody($attributes);

        $query = new SoapVar($body, XSD_ANYXML); // can be overridden debend on need
        
        $response = $this->httpClient()->{$method}($query);

        return ResponseClass::make($response);
    }

    /**
     * user to build xml Data for the request  
     * 
     * @param array $attributes
     *
     * @return string
     */
    protected function requestBody(array $attributes = []): string
    {

        return implode('', [
            sprintf('<cmn:getData>'), // request data XML 

            sprintf('<data>%s</data>', $this->data ?? ($attributes['data']??'')), 
            
            sprintf('</cmn:getData>'),
        ]);
    }
}
