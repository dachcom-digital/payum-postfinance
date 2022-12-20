<?php

namespace DachcomDigital\Payum\PostFinance;

use Http\Message\MessageFactory;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\HttpClientInterface;

class Api
{
    protected HttpClientInterface $client;
    protected MessageFactory $messageFactory;

    public const TEST = 'test';
    public const PRODUCTION = 'production';

    // parameters that will be included in the SHA-OUT Hash
    protected array $signatureParams = [
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

    protected array $options = [
        'hashingMethod'      => 'sha512',
        'shaInPassphrase'    => null,
        'shaOutPassphrase'   => null,
        'pspid'              => null,
        'environment'        => self::TEST,
        'optionalParameters' => []
    ];

    /**
     * @throws \Payum\Core\Exception\InvalidArgumentException if an option is invalid
     */
    public function __construct(array $options, HttpClientInterface $client, MessageFactory $messageFactory)
    {
        $this->client = $client;
        $this->messageFactory = $messageFactory;

        $optionObject = ArrayObject::ensureArrayObject($options);
        $optionObject->defaults($this->options);
        $optionObject->validateNotEmpty([
            'shaInPassphrase',
            'shaOutPassphrase',
            'pspid',
        ]);

        if (false === is_bool($options['sandbox'])) {
            throw new LogicException('The boolean sandbox option must be set.');
        }

        $this->options = $optionObject->toUnsafeArray();
    }

    public function verifyHash(array $params): bool
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

    public function getOffSiteUrl(): string
    {
        if ($this->options['sandbox'] === false) {
            return 'https://e-payment.postfinance.ch/ncol/prod/orderstandard.asp';
        }

        return 'https://e-payment.postfinance.ch/ncol/test/orderstandard.asp';
    }

    public function prepareOffSitePayment(array $params): array
    {
        //check for valid fields here?
        $this->addGlobalParams($params);

        return $params;
    }

    protected function addGlobalParams(array &$params): void
    {
        $params = array_merge($this->options['optionalParameters'], $params);

        // remove empty entries
        $params = array_filter($params);

        $params['PSPID'] = $this->options['pspid'];
        $params['SHASIGN'] = $this->createShaHash($params, $this->options['shaInPassphrase']);

    }

    public function createShaHash(array $data, string $signature): string
    {
        uksort($data, 'strnatcasecmp');
        $hashParts = [];
        foreach ($data as $key => $value) {

            $str = $this->stringValue($value);
            if ($str === '' || $key === 'SHASIGN') {
                continue;
            }

            $hashParts[] = strtoupper($key) . '=' . $str . $signature;
        }

        return strtoupper(hash(strtolower($this->options['hashingMethod']), implode('', $hashParts)));
    }

    public function stringValue(mixed $value): string
    {
        if ($value === 0) {
            return '0';
        }

        return (string) $value;
    }
}
