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
    private function getValueCurrencyFavorite() :float
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