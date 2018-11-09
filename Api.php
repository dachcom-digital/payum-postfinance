<?php

namespace DachcomDigital\Payum\PostFinance;

use Http\Message\MessageFactory;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
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

    const TEST = 'test';

    const PRODUCTION = 'production';

    // parameters that will be included in the SHA-OUT Hash
    protected $signatureParams = [
        'AAVADDRESS',
        'AAVCHECK',
        'AAVMAIL',
        'AAVNAME',
        'AAVPHONE',
        'AAVZIP',
        'ACCEPTANCE',
        'ALIAS',
        'AMOUNT',
        'BIC',
        'BIN',
        'BRAND',
        'CARDNO',
        'CCCTY',
        'CN',
        'COLLECTOR_BIC',
        'COLLECTOR_IBAN',
        'COMPLUS',
        'CREATION_STATUS',
        'CREDITDEBIT',
        'CURRENCY',
        'CVCCHECK',
        'DCC_COMMPERCENTAGE',
        'DCC_CONVAMOUNT',
        'DCC_CONVCCY',
        'DCC_EXCHRATE',
        'DCC_EXCHRATESOURCE',
        'DCC_EXCHRATETS',
        'DCC_INDICATOR',
        'DCC_MARGINPERCENTAGE',
        'DCC_VALIDHOURS',
        'DEVICEID',
        'DIGESTCARDNO',
        'ECI',
        'ED',
        'EMAIL',
        'ENCCARDNO',
        'FXAMOUNT',
        'FXCURRENCY',
        'IP',
        'IPCTY',
        'MANDATEID',
        'MOBILEMODE',
        'NBREMAILUSAGE',
        'NBRIPUSAGE',
        'NBRIPUSAGE_ALLTX',
        'NBRUSAGE',
        'NCERROR',
        'ORDERID',
        'PAYID',
        'PAYIDSUB',
        'PAYMENT_REFERENCE',
        'PM',
        'SCO_CATEGORY',
        'SCORING',
        'SEQUENCETYPE',
        'SIGNDATE',
        'STATUS',
        'SUBBRAND',
        'SUBSCRIPTION_ID',
        'TRXDATE',
        'VC',
        'WALLET'
    ];

    protected $options = [
        'hashingMethod'     => 'sha512',
        'shaInPassphrase'   => null,
        'shaOutPassphrase'  => null,
        'pspid'             => null,
        'environment'       => self::TEST,
        'optionalParameters' => []
    ];

    /**
     * @param array               $options
     * @param HttpClientInterface $client
     * @param MessageFactory      $messageFactory
     *
     * @throws \Payum\Core\Exception\InvalidArgumentException if an option is invalid
     */
    public function __construct(array $options, HttpClientInterface $client, MessageFactory $messageFactory)
    {
        $options = ArrayObject::ensureArrayObject($options);
        $options->defaults($this->options);
        $options->validateNotEmpty([
            'shaInPassphrase',
            'shaOutPassphrase',
            'pspid',
        ]);

        if (false == is_bool($options['sandbox'])) {
            throw new LogicException('The boolean sandbox option must be set.');
        }

        $this->options = $options;
        $this->client = $client;
        $this->messageFactory = $messageFactory;
    }

    /**
     * Verify if the hash of the given parameter is correct
     *
     * @param array $params
     *
     * @return bool
     */
    public function verifyHash(array $params)
    {
        if (empty($params['SHASIGN'])) {
            return false;
        }

        $data = [];
        $hash = null;

        foreach ($params as $key => $value) {
            $data[strtoupper($key)] = $value;
        }
        if (isset($data['SHASIGN'])) {
            $signData = [];
            foreach ($this->signatureParams as $param) {
                if (isset($data[$param])) {
                    $signData[$param] = $data[$param];
                }
            }
            $hash = $this->createShaHash(
                $signData,
                $this->options['shaOutPassphrase']
            );
        }

        return $hash === $data['SHASIGN'];
    }

    /**
     * @return string
     */
    public function getOffSiteUrl()
    {
        if ($this->options['sandbox'] === false) {
            return 'https://e-payment.postfinance.ch/ncol/prod/orderstandard.asp';
        }

        return 'https://e-payment.postfinance.ch/ncol/test/orderstandard.asp';
    }

    /**
     * @param  array $params
     *
     * @return array
     */
    public function prepareOffSitePayment(array $params)
    {
        //check for valid fields here?

        $this->addGlobalParams($params);
        return $params;
    }

    /**
     * @param  array $params
     */
    protected function addGlobalParams(array &$params)
    {
        $params = array_merge($this->options['optionalParameters'], $params);

        // remove empty entries
        $params = array_filter($params);

        $params['PSPID'] = $this->options['pspid'];
        $params['SHASIGN'] = $this->createShaHash($params, $this->options['shaInPassphrase']);

    }

    /**
     * @param array $data
     * @param       $signature
     * @return string
     */
    public function createShaHash(array $data, $signature)
    {
        uksort($data, 'strnatcasecmp');
        $hashParts = [];
        foreach ($data as $key => $value) {
            $str = $this->stringValue($value);
            if ($str == '' || $key == 'SHASIGN') {
                continue;
            }
            $hashParts[] = strtoupper($key) . '=' . $str . $signature;
        }
        return strtoupper(hash(strtolower($this->options['hashingMethod']), implode('', $hashParts)));
    }

    /**
     * @param $value
     * @return string
     */
    public function stringValue($value)
    {
        if ($value === 0) {
            return '0';
        }

        return (string)$value;
    }
}
