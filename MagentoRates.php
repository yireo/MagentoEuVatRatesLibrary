<?php
declare(strict_types=1);

namespace Yireo\MagentoEuVatRates;

use GuzzleHttp\Client;
use Yireo\MagentoEuVatRates\Exception\InvalidInputException;

/**
 * Class MagentoRates
 *
 * @package Yireo\MagentoEuVatRates
 * @see https://github.com/yireo/Magento_EU_Tax_Rates
 */
class MagentoRates
{
    /**
     * @var string
     */
    private $url = 'https://raw.githubusercontent.com/';

    /**
     * @var string
     */
    private $path = 'yireo/Magento_EU_Tax_Rates/master/tax_rates_eu.csv';

    /**
     * @var string
     */
    private $cacheFolder;

    /**
     * @var bool
     */
    private $enableCache = true;

    /**
     * @var array
     */
    private $columns = [
        'code' => 'Code',
        'country' => 'Country',
        'state' => 'State',
        'zip' => 'Zip/Post Code',
        'rate' => 'Rate',
        'zip_is_range' => 'Zip/Post is Range',
        'range_from' => 'Range From',
        'range_to' => 'Range To',
        'default' => 'default'
    ];

    /**
     * Rates constructor.
     *
     * @param string $cacheFolder
     * @param string $accessKey
     */
    public function __construct(
        string $cacheFolder = null
    ) {
        if (!$cacheFolder) {
            $cacheFolder = __DIR__;
        }

        $this->cacheFolder = $cacheFolder;
    }

    /**
     * @param string $path
     */
    public function setPath(string $path)
    {
        $this->path = $path;
    }

    /**
     * @return array
     * @throws InvalidInputException
     */
    public function getRates(): array
    {
        $rows = $this->getResultFromCachableMethod('getFreshRates');
        if (empty($rows)) {
            throw new InvalidInputException('CSV is empty');
        }

        $headerRow = array_shift($rows);
        $this->validateHeaderRow($headerRow);

        foreach ($rows as $row) {
            if (empty($row[0])) {
                continue;
            }
            $i = 0;
            $rate = [];
            foreach ($this->columns as $columnCode => $columnName) {
                $rate[$columnCode] = $row[$i] ?? '';
                $i++;
            }
            $rates[] = $rate;
        }

        return $rates;
    }

    /**
     * @return array
     */
    public function getFreshRates(): array
    {
        $client = new Client(['base_uri' => $this->url]);
        $response = $client->get($this->path);
        $contents = $response->getBody()->getContents();
        $rates = array_map('str_getcsv', explode("\n", $contents));

        return $rates;
    }

    /**
     * @param string $method
     *
     * @return array
     */
    public function getResultFromCachableMethod(string $method): array
    {
        if (!is_dir($this->cacheFolder)) {
            mkdir($this->cacheFolder);
        }

        $uniqueInfo = get_class($this) . $method . $this->url . $this->path;
        $cacheFile = $this->cacheFolder . '/' . md5($uniqueInfo) . '.ser';

        if ($this->allowCache($cacheFile) && $this->enableCache) {
            return unserialize(file_get_contents($cacheFile));
        }

        $data = $this->$method();

        if ($this->enableCache) {
            file_put_contents($cacheFile, serialize($data));
        }

        return $data;
    }

    /**
     * @param $file
     *
     * @return bool
     * @todo: Rewrite to proper PSR cache interface
     */
    public function allowCache($file)
    {
        if (!file_exists($file)) {
            return false;
        }

        if (filemtime($file) < (time() + 15 * 60)) {
            return false;
        }

        return true;
    }

    /**
     * Disable the cache
     */
    public function disableCache()
    {
        $this->enableCache = false;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @param array $headerRow
     * @return bool
     */
    private function validateHeaderRow(array $headerRow): bool
    {
        if (count($headerRow) !== count($this->columns)) {
            throw new InvalidInputException('CSV header is of unexpected size');
        }

        $i = 0;
        foreach ($this->columns as $columnCode => $columnName) {
            if ($headerRow[$i] != $columnName) {
                throw new InvalidInputException(sprintf('CSV header contains unexpected value "%s" at position %d',
                    $headerRow[$i], $i));
            }
            $i++;
        }

        return true;
    }
}
