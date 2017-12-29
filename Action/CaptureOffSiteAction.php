<?php

namespace DachcomDigital\Payum\PostFinance\Action;

use DachcomDigital\Payum\PostFinance\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Reply\HttpPostRedirect;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;

/**
 * @property Api $api
 */
class CaptureOffSiteAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface, GenericTokenFactoryAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;
    use GenericTokenFactoryAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    /**
     * {@inheritDoc}
     *
     * @param Capture $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $httpRequest = new GetHttpRequest();
        $this->gateway->execute($httpRequest);

        //we are back from postFinance site so we have to just update model.
        if (isset($httpRequest->query['PAYID'])) {
            $model->replace($httpRequest->query);
        } else {
            $extradata = $model['COMPLUS'] ? json_decode($model['COMPLUS'], true) : [];

            if (false == isset($extradata['capture_token']) && $request->getToken()) {
                $extradata['capture_token'] = $request->getToken()->getHash();
            }

            if (false == isset($extradata['notify_token']) && $request->getToken() && $this->tokenFactory) {
                $notifyToken = $this->tokenFactory->createNotifyToken(
                    $request->getToken()->getGatewayName(),
                    $request->getToken()->getDetails()
                );

                $extradata['notify_token'] = $notifyToken->getHash();
                $model['PARAMVAR'] = $notifyToken->getHash();

            }

            $model['COMPLUS'] = json_encode($extradata);

            //payment/capture/xy
            $targetUrl = $request->getToken()->getTargetUrl();

            //accept url
            if (null === $model['ACCEPTURL'] && $request->getToken()) {
                $model['ACCEPTURL'] = $targetUrl;
            }

            //cancel url
            if (null === $model['CANCELURL'] && $request->getToken()) {
                $model['CANCELURL'] = $targetUrl;
            }

            throw new HttpPostRedirect(
                $this->api->getOffSiteUrl(),
                $this->api->prepareOffSitePayment($model->toUnsafeArray())
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
