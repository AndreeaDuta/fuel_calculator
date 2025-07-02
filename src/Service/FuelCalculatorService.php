<?php

namespace Drupal\fuel_calculator\Service;

/**
 * Service for calculating fuel costs and consumption.
 */
class FuelCalculatorService {

  /**
   * Calculate fuel spent and cost.
   *
   * @param float $distance
   *   The distance travelled in kilometers.
   * @param float $fuel_consumption
   *   The fuel consumption in liters per 100 kilometers.
   * @param float $fuel_price
   *   The fuel price per liter in EUR.
   *
   * @return array
   *   Array with 'fuel_spent' and 'fuel_cost' (both as strings with comma decimal).
   */
  public function calculate(float $distance, float $fuel_consumption, float $fuel_price): array {
    $fuel_spent = $distance * $fuel_consumption / 100;
    $fuel_cost = $fuel_spent * $fuel_price;
    // Format with comma as decimal separator, 1 decimal.
    return [
      'fuel_spent' => number_format($fuel_spent, 1, ',', ''),
      'fuel_cost' => number_format($fuel_cost, 1, ',', ''),
    ];
  }

}
