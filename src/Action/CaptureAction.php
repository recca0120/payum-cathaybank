<?php

namespace PayumTW\Cathaybank\Action;

use Payum\Core\Action\GatewayAwareAction;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\Reply\HttpPostRedirect;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetHttpRequest;
use PayumTW\Cathaybank\Api;

class CaptureAction extends GatewayAwareAction implements ApiAwareInterface
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * {@inheritdoc}
     */
    public function setApi($api)
    {
        if (false == $api instanceof Api) {
            throw new UnsupportedApiException(sprintf('Not supported. Expected %s instance to be set as api.', Api::class));
        }

        $this->api = $api;
    }

    /**
     * {@inheritdoc}
     *
     * @param Capture $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());
        $httpRequest = new GetHttpRequest();
        $this->gateway->execute($httpRequest);

        if (isset($httpRequest->request['strRsXML']) === true) {
            $content = $this->api->parseStrRsXML($httpRequest->request['strRsXML'], $request);
            $statusCode = empty($content) === false ? 200 : 404;
            throw new HttpResponse($content, $statusCode, [
                'content-type' => 'text/xml; charset=utf-8',
            ]);
        } elseif (isset($httpRequest->query['ORDERNUMBER']) === true) {
            $model->replace($httpRequest->query);
        } else {
            throw new HttpPostRedirect(
                $this->api->getApiEndpoint(),
                $this->api->preparePayment($model->toUnsafeArray(), $request)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
