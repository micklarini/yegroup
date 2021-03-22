<?php

namespace App\Services;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;

/**
 * Currency exchange rates client.
 *
 * @author Michael Larin <micklarini@yandex.ru>
 */
class ExchangeRates
{
    /**
     * Http client
     *
     * @var GuzzleHttp\Client
     */
    protected Client $http;

    /**
     * Constructor
     *
     * @param \GuzzleHttp\Client $client
     */
    public function __construct(Client $client)
    {
        $this->http = $client;
    }

    /**
     * Load currencies rates and optionally currencies definitions
     *
     * @param Datetime $date Date of currency rates to fetch
     * @param array $options An array of loading options
     *
     * @return void
     */
    public function fetch(\DateTime $date, array $options = []): void
    {
        //TODO: Extended error handling. Change return value to code or message.
        if ($options['defs'] || (DB::table('currencies')->count() == 0)) {
            $this->updateCurrenciesDefs();
        }
        $this->getRates($date);
    }

    /**
     * Fetch and store currencies definitions
     *
     * @return void
     */
    private function updateCurrenciesDefs(): void
    {
        $config = config('app.xrates');

        $request = $this->http->get($config['currencies'], [
            'timeout' => $config['timeout'],
            'connect_timeout' => true,
            'http_errors' => true,
        ]);
        $response = $request ? $request->getBody()->getContents() : null;
        if (empty($response)) {
            throw new \Exception(__('Cannot load currencies definitions'));
            return;
        }

        $defs = new \SimpleXmlIterator($response);
        unset($response);
        $currencies = [];

        foreach ($defs->Item as $item) {
            $currencies[] = [
                'charcode' => (string) $item->ISO_Char_Code,
                'numcode' => (int) $item->ISO_Num_Code,
                'name' => (string) $item->Name,
                'name_eng' => (string) $item->EngName,
            ];
        }
        DB::table('currencies')->upsert($currencies, ['charcode', 'numcode'], ['numcode', 'name', 'name_eng']);
    }

    /**
     * Fetch and store available currencies rates at @date
     *
     * @param Datetime $date Date of currency rates to fetch
     *
     * @return void
     */
    private function getRates(\DateTime $date): void
    {
        //TODO: Check future dates???
        $config = config('app.xrates');
        $query = $config['queryformat'];
        array_walk($query, fn(&$val) => $val = $date->format($val));

        $request = $this->http->get($config['rates'], [
            'timeout' => $config['timeout'],
            'connect_timeout' => true,
            'http_errors' => true,
            'query' => $query,
        ]);
        $response = $request ? $request->getBody()->getContents() : null;
        if (empty($response)) {
            throw new \Exception(__('Cannot load exchange rates'));
            return;
        }

        $defs = new \SimpleXmlIterator($response);
        unset($response);
        $rates = [];

        foreach ($defs->Valute as $valute) {
            $rates[] = [
                'datereq' => $date->format('Y-m-d'),
                'charcode' => (string) $valute->CharCode,
                'nominal' => (int) $valute->Nominal,
                'rate' => str_replace(',', '.', $valute->Value) * 1.0,
            ];
        }
        DB::table('rates')->upsert($rates, ['datereq', 'charcode'], ['nominal', 'rate']);
    }
}
