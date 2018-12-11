<?php
declare(strict_types=1);

namespace Yireo\MagentoEuVatRates\Test\Unit;

use PHPUnit\Framework\TestCase;
use Yireo\MagentoEuVatRates\MagentoRates;
use Yireo\EuVatRates\Rates as EuRates;

/**
 * Class MagentoRatesTest
 *
 * @package Yireo\EuVatRates\Test\Unit
 */
class MagentoRatesTest extends TestCase
{
    /**
     * @test
     */
    public function testRates()
    {
        $magentoRates = $this->getMagentoRates()->getRates();
        $this->assertNotEmpty($magentoRates);

        $euRates = $this->getEuRates()->getRates();
        foreach ($magentoRates as $magentoRate) {
            $this->doTestMagentoRate($magentoRate, $euRates);
        }
    }

    private function doTestMagentoRate($magentoRate, $euRates)
    {
        $country = $magentoRate['country'];
        $rate = $magentoRate['rate'];

        if ((int)$rate === 0) {
            return;
        }

        $this->assertArrayHasKey($country, $euRates['rates'], var_export($magentoRate, true));
        $euRateInfo = $euRates['rates'][$country];
        $matchStandardRate = ($rate == $euRateInfo['standard_rate']);
        $matchReducedRate = in_array($rate, (array)$euRateInfo['reduced_rates']);

        $message = sprintf('Rate %s for %s was not matched: %s', $rate, $country, var_export($euRateInfo, true));
        $this->assertTrue($matchStandardRate || $matchReducedRate, $message);
    }

    /**
     * @return MagentoRates
     */
    private function getMagentoRates(): MagentoRates
    {
        return new MagentoRates();
    }

    /**
     * @return EuRates
     */
    private function getEuRates(): EuRates
    {
        return new EuRates();
    }
}
