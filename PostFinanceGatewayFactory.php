<?php

namespace DachcomDigital\Payum\PostFinance;

use DachcomDigital\Payum\PostFinance\Action\CaptureOffSiteAction;
use DachcomDigital\Payum\PostFinance\Action\ConvertPaymentAction;
use DachcomDigital\Payum\PostFinance\Action\NotifyAction;
use DachcomDigital\Payum\PostFinance\Action\StatusAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

class PostFinanceGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults([

            'payum.factory_name'           => 'postfinance',
            'payum.factory_title'          => 'PostFinance E-Commerce',
            'payum.action.capture'         => new CaptureOffSiteAction(),
            'payum.action.notify'          => new NotifyAction(),
            'payum.action.status'          => new StatusAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),
        ]);

        if (empty($config['payum.api'])) {
            $config['payum.default_options'] = [
                'environment'      => Api::TEST,
                'shaInPassphrase'  => '',
                'shaOutPassphrase' => '',
                'pspid'            => '',
                'sandbox'          => true,
            ];

            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = ['shaInPassphrase', 'shaOutPassphrase', 'pspid'];

            $config['payum.api'] = static function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Api(
                    [
                        'sandbox'            => $config['environment'] === Api::TEST,
                        'shaInPassphrase'    => $config['shaInPassphrase'],
                        'shaOutPassphrase'   => $config['shaOutPassphrase'],
                        'pspid'              => $config['pspid'],
                        'optionalParameters' => $config['optionalParameters'] ?? []
                    ],
                    $config['payum.http_client'],
                    $config['httplug.message_factory']
                );
            };
        }
    }
}
