# Payum PostFinance

## Important Information
PostFinance fires the callback page **twice** if the user clicks the *"abort"* or *"ok"* button.
You need to implement a custom HttpRequestVerifierBuilder like [here](https://github.com/coreshop/PayumPostFinanceBundle/blob/master/src/CoreShop/Payum/PostFinance/Security/HttpRequestVerifier.php#L54) to disable the token invalidation!

## PostFinance Backend Configuration

1. In the Global Security Parameters tab, choose "each parameter followed by the passphrase."
2. The Hash algorithm needs to be SHA-512
3. Make sure to provide an SHA-IN pass phrase in "data and origin verification" tab
4. Check "I would like to receive transaction feedback parameters on the redirection URLs and supply a SHA-OUT pass phrase." in "Transaction feedback"

## Server To Server
You may want to enable the server-to-server functionality:
Go to "Transaction feedback" and set "Direct HTTP server-to-server request" to "Always deferred (not immediately after the payment)." for example.
In both URL fields you need to add `http://your-domain.com/payment/notify/<PARAMVAR>`. Note the `<PARAMVAR>` var. It gets replaced by postFinance.

**Important:** Set "Request method" to "GET" since the notifyAction only listens to the request query.

## Offline Authorisation
If you have enabled "Authorisation" in "Global transaction parameters -> Default Operation Code", you need to enable the request for status changes:
Go to "Transaction feedback -> HTTP request for status changes" and set "Timing of the request" to "For each offline status change (payment, cancellation, etc.)."
In the URL field add the same url as in section "HTTP server-to-server request".

## Language Parameter
The `LANGUAGE` Parameter cannot be set in the ConvertPaymentAction since there is no general language getter available in Payum.
To add this field you need to add a custom Extension (Check [this file](https://github.com/coreshop/PayumPostFinanceBundle/blob/master/src/CoreShop/Payum/PostFinance/Extension/ConvertPaymentExtension.php#L41) to get the Idea).

### Required Parameters
These Fields are required:
- `environment` (default 'Test')
- `shaInPassphrase`
- `shaOutPassphrase`
- `pspid`

### Optional Parameters
You can pass optional parameters to the `optionalParameters` config node.
You'll find all available fields [here](https://e-payment.postfinance.ch/ncol/param_cookbook.asp).

## To-Do
- Handle deprecated tokens?

## Copyright and License
Copyright: [DACHCOM.DIGITAL](http://dachcom-digital.ch)
For licensing details please visit [LICENSE.md](LICENSE.md)

### v2.0.0
- Bump dependencies, code improvements
