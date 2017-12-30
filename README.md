# Payum PostFinance

## PostFinance Backend Configuration

1. In the Global Security Parameters tab, choose "each parameter followed by the passphrase."
2. The Hash algorithm needs to be SHA-512
3. Make sure to provide an SHA-IN pass phrase in "data and origin verification" tab
4. Check "I would like to receive transaction feedback parameters on the redirection URLs and supply a SHA-OUT pass phrase." in "Transaction feedback"

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