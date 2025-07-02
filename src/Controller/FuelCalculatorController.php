<?php

namespace Drupal\fuel_calculator\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for the fuel calculator page.
 */
class FuelCalculatorController extends ControllerBase {

  /**
   * Displays the fuel calculator page.
   *
   * @return array
   *   A render array for the calculator page.
   */
  public function calculatorPage(): array {
    return [
      '#title' => $this->t('Fuel Calculator'),
      'form' => $this->formBuilder()->getForm('Drupal\\fuel_calculator\\Form\\FuelCalculatorForm'),
    ];
  }

}
