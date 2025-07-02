<?php

namespace Drupal\fuel_calculator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a configuration form for fuel calculator settings.
 */
class FuelCalculatorSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['fuel_calculator.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'fuel_calculator_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('fuel_calculator.settings');

    $form['default_distance'] = [
      '#type' => 'number',
      '#title' => $this->t('Default distance (km)'),
      '#default_value' => $config->get('default_distance'),
      '#step' => 0.1,
      '#min' => 0.1,
      '#max' => 10000,
      '#required' => TRUE,
    ];
    $form['default_fuel_consumption'] = [
      '#type' => 'number',
      '#title' => $this->t('Default fuel consumption (l/100km)'),
      '#default_value' => $config->get('default_fuel_consumption'),
      '#step' => 0.1,
      '#min' => 0.1,
      '#max' => 100,
      '#required' => TRUE,
    ];
    $form['default_fuel_price'] = [
      '#type' => 'number',
      '#title' => $this->t('Default fuel price (EUR/liter)'),
      '#default_value' => $config->get('default_fuel_price'),
      '#step' => 0.01,
      '#min' => 0.01,
      '#max' => 10,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('fuel_calculator.settings')
      ->set('default_distance', $form_state->getValue('default_distance'))
      ->set('default_fuel_consumption', $form_state->getValue('default_fuel_consumption'))
      ->set('default_fuel_price', $form_state->getValue('default_fuel_price'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
