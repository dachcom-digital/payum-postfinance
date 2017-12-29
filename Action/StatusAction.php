<?php

namespace DachcomDigital\Payum\PostFinance\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Request\GetStatusInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use PostFinance\Ecommerce\EcommercePaymentResponse;

class StatusAction implements ActionInterface
{
    /**
     * {@inheritDoc}
     *
     * @param GetStatusInterface $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = new ArrayObject($request->getModel());

        if (null === $model['STATUS']) {
            $request->markNew();
            return;
        }

        switch ($model['STATUS']) {
            case EcommercePaymentResponse::STATUS_AUTHORISED:
                $request->markAuthorized();
                break;
            case EcommercePaymentResponse::STATUS_PAYMENT_REQUESTED:
            case EcommercePaymentResponse::STATUS_PAYMENT:
                # change to const as soon as PR is merged and STATUS_AUTHORISATION_CANCELLATION_WAITING is available
            case 61:
                $request->markCaptured();
                break;
            case EcommercePaymentResponse::STATUS_INCOMPLETE_OR_INVALID:
            case EcommercePaymentResponse::STATUS_AUTHORISATION_REFUSED:
            case EcommercePaymentResponse::STATUS_PAYMENT_REFUSED:
                $request->markFailed();
                break;
            case EcommercePaymentResponse::STATUS_REFUND:
                $request->markRefunded();
                break;
            case EcommercePaymentResponse::STATUS_CANCELLED_BY_CLIENT:
                $request->markCanceled();
                break;
            default:
                $request->markUnknown();
                break;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
