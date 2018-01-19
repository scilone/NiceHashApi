<?php

include 'curlHelper/curlHelper.php';

/**
 * Class NiceHashApi
 */
class NiceHashApi
{
    const API_BTC_CURRENCY      = 'https://api.coindesk.com/v1/bpi/currentprice.json';
    const API_URL               = 'https://api.nicehash.com/api';

    const METHOD_WORKERS        = 'stats.provider.workers';
    const METHOD_STATS          = 'stats.provider';
    const METHOD_DETAILED_STATS = 'stats.provider.ex';
    const METHOD_BALANCE        = 'balance';

    const METRIC_MH  = 'MH/s';
    const METRIC_TH  = 'TH/s';
    const METRIC_KH  = 'kH/s';
    const METRIC_GH  = 'GH/s';
    const METRIC_SOL = 'Sol/s';

    /**
     * @var string
     */
    private $addr;

    /**
     * @var CurlHelper
     */
    private $curl;

    /**
     * @var int
     */
    private $apiId;

    /**
     * @var string
     */
    private $apiKeyRead;

    /**
     * @var float
     */
    private $bitcoinPriceUsd;

    /**
     * @var float
     */
    private $bitcoinPriceGbp;

    /**
     * @var float
     */
    private $bitcoinPriceEur;

    /**
     * @var string
     */
    private $currencyFavorite = 'eur';

    public static $algorithm = [
        0 => 'Scrypt',
        1 => 'SHA256',
        2 => 'ScryptNf',
        3 => 'X11',
        4 => 'X13',
        5 => 'Keccak',
        6 => 'X15',
        7 => 'Nist5',
        8 => 'NeoScrypt',
        9 => 'Lyra2RE',
        10 => 'WhirlpoolX',
        11 => 'Qubit',
        12 => 'Quark',
        13 => 'Axiom',
        14 => 'Lyra2REv2',
        15 => 'ScryptJaneNf16',
        16 => 'Blake256r8',
        17 => 'Blake256r14',
        18 => 'Blake256r8vnl',
        19 => 'Hodl',
        20 => 'DaggerHashimoto',
        21 => 'Decred',
        22 => 'CryptoNight',
        23 => 'Lbry',
        24 => 'Equihash',
        25 => 'Pascal',
        26 => 'X11Gost',
        27 => 'Sia',
        28 => 'Blake2s',
        29 => 'Skunk'
    ];

    public static $algorithmMetric = [
        0 => self::METRIC_MH,
        1 => self::METRIC_TH,
        2 => self::METRIC_MH,
        3 => self::METRIC_MH,
        4 => self::METRIC_MH,
        5 => self::METRIC_MH,
        6 => self::METRIC_MH,
        7 => self::METRIC_MH,
        8 => self::METRIC_MH,
        9 => self::METRIC_MH,
        10 => self::METRIC_MH,
        11 => self::METRIC_MH,
        12 => self::METRIC_MH,
        13 => self::METRIC_KH,
        14 => self::METRIC_MH,
        15 => self::METRIC_KH,
        16 => self::METRIC_GH,
        17 => self::METRIC_GH,
        18 => self::METRIC_GH,
        19 => self::METRIC_KH,
        20 => self::METRIC_MH,
        21 => self::METRIC_GH,
        22 => self::METRIC_KH,
        23 => self::METRIC_GH,
        24 => self::METRIC_SOL,
        25 => self::METRIC_GH,
        26 => self::METRIC_MH,
        27 => self::METRIC_GH,
        28 => self::METRIC_GH,
        29 => self::METRIC_MH
    ];

    public static $location = [
        0 => 'EU',
        1 => 'US',
        2 => 'HK',
        3 => 'JP'
    ];

    /**
     * NiceHashApi constructor.
     *
     * @param string $addr
     * @param int    $apiId
     * @param string $apiKeyRead
     */
    public function __construct(string $addr, int $apiId = 0, string $apiKeyRead = '')
    {
        $this->addr       = $addr;
        $this->apiId      = $apiId;
        $this->apiKeyRead = $apiKeyRead;
        $this->curl = new CurlHelper();

        $this->getBitcoinPrice();
    }

    public function setEurCurrencyFavorite()
    {
        $this->currencyFavorite = 'eur';
    }

    public function setUsdCurrencyFavorite()
    {
        $this->currencyFavorite = 'usd';
    }

    public function setGbpCurrencyFavorite()
    {
        $this->currencyFavorite = 'gbp';
    }

    /**
     * @return float
     */
    public function getValueCurrencyFavorite() :float
    {
        switch ($this->currencyFavorite) {
            case 'usd':
                return $this->bitcoinPriceEur;
            case 'gpb':
                return $this->bitcoinPriceEur;
            default:
                return $this->bitcoinPriceEur;
        }
    }

    /**
     * @return string
     */
    public function getCurrencyFavorite() :string
    {
        return $this->currencyFavorite;
    }

    /**
     * @param string $result
     *
     * @return stdClass
     */
    private function decodeResult(string $result) :stdClass
    {
        $return = json_decode($result);

        $return->json = $result;

        return $return;
    }

    /**
     * @param string $algo
     *
     * @return stdClass
     */
    public function getStatsWorkers(string $algo = '') :stdClass
    {
        $params = [
            'method' => self::METHOD_WORKERS,
            'addr'   => $this->addr
        ];

        if ($algo !== '') {
            $params['algo'] = $algo;
        }

        $this->curl->execute(self::API_URL . '?' . http_build_query($params));

        return $this->decodeResult($this->curl->getResult())->result;
    }

    /**
     * @return stdClass
     */
    public function getStats() :stdClass
    {
        $params = [
            'method' => self::METHOD_STATS,
            'addr'   => $this->addr
        ];

        $this->curl->execute(self::API_URL . '?' . http_build_query($params));

        return $this->decodeResult($this->curl->getResult())->result;
    }

    /**
     * This method was ratelimited
     *
     * @param int $from //timestamp
     *
     * @return stdClass
     */
    public function getDetailedStats(int $from = 0) :stdClass
    {
        $params = [
            'method' => self::METHOD_DETAILED_STATS,
            'addr'   => $this->addr
        ];

        if ($from > 0) {
            $params['from'] = $from;
        }

        $this->curl->execute(self::API_URL . '?' . http_build_query($params));

        return $this->decodeResult($this->curl->getResult())->result;
    }

    /**
     * @throws Exception
     *
     * @return stdClass
     */
    public function getBalance() :stdClass
    {
        if ($this->apiKeyRead === '' || $this->apiId === 0) {
            throw new Exception('ApiId or ApiKeyRead are missing');
        }

        $params = [
            'method' => self::METHOD_BALANCE,
            'addr'   => $this->addr,
            'id'     => $this->apiId,
            'key'    => $this->apiKeyRead
        ];

        $this->curl->execute(self::API_URL . '?' . http_build_query($params));

        $result = $this->decodeResult($this->curl->getResult());

        $result->result->balance_pending_value   = round(
            $result->result->balance_pending * $this->getValueCurrencyFavorite(),
            2
        );
        $result->result->balance_confirmed_value = round(
            $result->result->balance_confirmed * $this->getValueCurrencyFavorite(),
            2
        );

        return $result->result;
    }

    /**
     * @return void
     */
    private function getBitcoinPrice()
    {
        if ($this->bitcoinPriceEur !== null) {
            return;
        }

        $this->curl->execute(self::API_BTC_CURRENCY);

        $result = json_decode($this->curl->getResult());

        if (isset($result->bpi->USD->rate_float) === true) {
            $this->bitcoinPriceUsd = $result->bpi->USD->rate_float;
        }

        if (isset($result->bpi->GBP->rate_float) === true) {
            $this->bitcoinPriceGbp = $result->bpi->GBP->rate_float;
        }

        if (isset($result->bpi->EUR->rate_float) === true) {
            $this->bitcoinPriceEur = $result->bpi->EUR->rate_float;
        }
    }
}