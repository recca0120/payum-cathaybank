<?php

namespace PayumTw\Cathaybank;

use Http\Message\MessageFactory;
use Payum\Core\Exception\Http\HttpException;
use Payum\Core\HttpClientInterface;

class Api
{
    /**
     * @var HttpClientInterface
     */
    protected $client;

    /**
     * @var MessageFactory
     */
    protected $messageFactory;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @param array               $options
     * @param HttpClientInterface $client
     * @param MessageFactory      $messageFactory
     *
     * @throws \Payum\Core\Exception\InvalidArgumentException if an option is invalid
     */
    public function __construct(array $options, HttpClientInterface $client, MessageFactory $messageFactory)
    {
        $this->options = $options;
        $this->client = $client;
        $this->messageFactory = $messageFactory;
    }

    /**
     * @param array $fields
     *
     * @return array
     */
    protected function doRequest($method, array $fields)
    {
        $headers = [];

        $request = $this->messageFactory->createRequest($method, $this->getApiEndpoint(), $headers, http_build_query($fields));

        $response = $this->client->send($request);

        if (false == ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            throw HttpException::factory($request, $response);
        }

        return $response;
    }

    /**
     * @return string
     */
    public function getApiEndpoint()
    {
        return $this->options['sandbox'] ?
            'https://sslpayment.uwccb.com.tw/EPOSService/Payment/OrderInitial.aspx' :
            'https://sslpayment.uwccb.com.tw/EPOSService/Payment/OrderInitial.aspx';
    }

    /**
     * preparePayment.
     *
     * @param array $params
     * @param mixed $request
     *
     * @return array
     */
    public function preparePayment(array $params)
    {
        $supportedParams = [
            'STOREID'     => $this->options['STOREID'],
            'ORDERNUMBER' => null,
            'AMOUNT'      => null,
        ];

        $params = array_filter(array_replace(
            $supportedParams,
            array_intersect_key($params, $supportedParams)
        ));

        $params['AMOUNT'] = 588;

        $cavalue = hash('md5', $params['STOREID'].$params['ORDERNUMBER'].$params['AMOUNT'].$this->options['CUBKEY']);

        $strRqXML = "<?xml version='1.0' encoding='UTF-8'?>\n";
        $strRqXML .= "<MERCHANTXML>\n";
        $strRqXML .= '<CAVALUE>'.$cavalue."</CAVALUE>\n";
        $strRqXML .= "<ORDERINFO>\n";
        $strRqXML .= '<STOREID>'.$params['STOREID']."</STOREID>\n";    //廠商代號
        $strRqXML .= '<ORDERNUMBER>'.$params['ORDERNUMBER']."</ORDERNUMBER>\n"; //訂單編號
        $strRqXML .= '<AMOUNT>'.$params['AMOUNT']."</AMOUNT>\n";   //授權金額
        $strRqXML .= "</ORDERINFO>\n";
        $strRqXML .= "</MERCHANTXML>\n";

        return [
            'strRqXML' => $strRqXML,
        ];
    }

    /**
     * getRedirectUrl.
     *
     * @param mixed $request
     *
     * @return string
     */
    public function getRedirectUrl($request)
    {
        $scheme = parse_url($request->getToken()->getTargetUrl());
        parse_str($scheme['query'], $temp);

        return sprintf('%s://%s%s', $scheme['scheme'], $scheme['host'], $scheme['path']);
    }

    /**
     * parseStrRsXML.
     *
     * @param string $xml
     * @param mixed  $request
     *
     * @return string
     */
    public function parseStrRsXML($xml, $request)
    {
        if (preg_match('/<ORDERNUMBER>(.+)<\/ORDERNUMBER>/', $xml, $match)) {
            $cavalue = hash('md5', $this->options['hostname'].$this->options['CUBKEY']);
            $redirectUrl = $this->getRedirectUrl($request).'?ORDERNUMBER='.$match[1];

            return "<?xml version='1.0' encoding='UTF-8'?><MERCHANTXML><CAVALUE>$cavalue</CAVALUE><RETURL>".$redirectUrl.'</RETURL></MERCHANTXML>';
        }

        return '';
    }
}
