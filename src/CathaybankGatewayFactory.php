<?php

namespace PayumTW\Cathaybank;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;
use PayumTW\Cathaybank\Action\CaptureAction;
use PayumTW\Cathaybank\Action\ConvertPaymentAction;
use PayumTW\Cathaybank\Action\StatusAction;

class CathaybankGatewayFactory extends GatewayFactory
{
    /**
     * {@inheritdoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'payum.factory_name'           => 'cathaybank',
            'payum.factory_title'          => 'Cathaybank',
            'payum.action.capture'         => new CaptureAction(),
            'payum.action.status'          => new StatusAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),
        ]);

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = [
                'STOREID'  => '',
                'CUBKEY'   => '',
                'hostname' => '',
                'sandbox'  => true,
            ];
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = ['STOREID', 'CUBKEY', 'hostname'];

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Api((array) $config, $config['payum.http_client'], $config['httplug.message_factory']);
            };
        }
    }
}
