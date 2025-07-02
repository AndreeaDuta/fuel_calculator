<?php

namespace Drupal\fuel_calculator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\fuel_calculator\Service\FuelCalculatorService;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides a fuel calculator form.
 */
class FuelCalculatorForm extends FormBase {

  /**
   * The fuel calculator service.
   *
   * @var \Drupal\fuel_calculator\Service\FuelCalculatorService
   */
  protected $calculator;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a FuelCalculatorForm object.
   *
   * @param \Drupal\fuel_calculator\Service\FuelCalculatorService $calculator
   *   The fuel calculator service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    FuelCalculatorService $calculator,
    RequestStack $request_stack,
    LoggerChannelFactoryInterface $logger_factory,
    AccountProxyInterface $current_user,
    ConfigFactoryInterface $config_factory,
  ) {
    $this->calculator = $calculator;
    $this->requestStack = $request_stack;
    $this->loggerFactory = $logger_factory;
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('fuel_calculator.calculator'),
      $container->get('request_stack'),
      $container->get('logger.factory'),
      $container->get('current_user'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'fuel_calculator_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->configFactory->get('fuel_calculator.settings');
    $request = $this->requestStack->getCurrentRequest();

    // Prefill from URL or config.
    $distance = $request->query->get('distance', $config->get('default_distance'));
    $fuel_consumption = $request->query->get('fuel_consumption', $config->get('default_fuel_consumption'));
    $fuel_price = $request->query->get('fuel_price', $config->get('default_fuel_price'));

    $form['#attributes']['class'][] = 'fuel-calculator-form';

    // Boxed container for all fields and results.
    $form['calculator_box'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['fuel-calculator-box']],
    ];

    $form['calculator_box']['distance'] = [
      '#type' => 'number',
      '#title' => $this->t('Distance travelled'),
      '#title_display' => 'before',
      '#default_value' => $distance,
      '#step' => 0.1,
      '#min' => 0.1,
      '#max' => 10000,
      '#required' => FALSE,
      '#field_suffix' => $this->t('km'),
      '#attributes' => ['class' => ['fuel-input']],
      '#wrapper_attributes' => ['class' => ['fuel-row']],
    ];

    $form['calculator_box']['fuel_consumption'] = [
      '#type' => 'number',
      '#title' => $this->t('Fuel consumption'),
      '#title_display' => 'before',
      '#default_value' => $fuel_consumption,
      '#step' => 0.1,
      '#min' => 0.1,
      '#max' => 100,
      '#required' => FALSE,
      '#field_suffix' => $this->t('l/100 km'),
      '#attributes' => ['class' => ['fuel-input']],
      '#wrapper_attributes' => ['class' => ['fuel-row']],
    ];

    $form['calculator_box']['fuel_price'] = [
      '#type' => 'number',
      '#title' => $this->t('Price per Liter'),
      '#title_display' => 'before',
      '#default_value' => $fuel_price,
      '#step' => 0.01,
      '#min' => 0.01,
      '#max' => 10,
      '#required' => FALSE,
      '#field_suffix' => $this->t('EUR'),
      '#attributes' => ['class' => ['fuel-input']],
      '#wrapper_attributes' => ['class' => ['fuel-row']],
    ];

    // Results area, boxed and bold.
    $results = $form_state->get('results');
    $form['calculator_box']['results'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['fuel-calculator-results-box']],
      'fuel_spent' => [
        '#markup' => '<span class="fuel-label"><strong>' . $this->t('Fuel spent') . '</strong></span> <span class="fuel-value">' . ($results['fuel_spent'] ?? '-') . '</span> <span class="fuel-unit">' . $this->t('liters') . '</span>',
      ],
      'fuel_cost' => [
        '#markup' => '<span class="fuel-label"><strong>' . $this->t('Fuel cost') . '</strong></span> <span class="fuel-value">' . ($results['fuel_cost'] ?? '-') . '</span> <span class="fuel-unit">' . $this->t('EUR') . '</span>',
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'calculate' => [
        '#type' => 'submit',
        '#value' => $this->t('Calculate'),
      ],
      'reset' => [
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#submit' => ['::resetForm'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $this->validateNumericFields($form_state);
    $this->validateFieldRanges($form_state);
  }

  /**
   * Validates that all required fields are numeric.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function validateNumericFields(FormStateInterface $form_state): void {
    $fields = ['distance', 'fuel_consumption', 'fuel_price'];
    foreach ($fields as $field) {
      $value = $form_state->getValue($field);
      if (!is_numeric($value)) {
        $form_state->setErrorByName($field, $this->t('Value must be numeric.'));
      }
    }
  }

  /**
   * Validates field value ranges.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function validateFieldRanges(FormStateInterface $form_state): void {
    $validations = [
      'distance' => ['min' => 0.1, 'max' => 10000, 'label' => 'Distance'],
      'fuel_consumption' => ['min' => 0.1, 'max' => 100, 'label' => 'Fuel consumption'],
      'fuel_price' => ['min' => 0.01, 'max' => 10, 'label' => 'Fuel price'],
    ];

    foreach ($validations as $field => $rules) {
      $value = $form_state->getValue($field);
      if ($value < $rules['min'] || $value > $rules['max']) {
        $form_state->setErrorByName($field, $this->t('@field must be between @min and @max.', [
          '@field' => $rules['label'],
          '@min' => $rules['min'],
          '@max' => $rules['max'],
        ]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $distance = (float) $form_state->getValue('distance');
    $fuel_consumption = (float) $form_state->getValue('fuel_consumption');
    $fuel_price = (float) $form_state->getValue('fuel_price');

    $results = $this->calculator->calculate($distance, $fuel_consumption, $fuel_price);
    $form_state->set('results', $results);
    $form_state->setRebuild();

    $this->logCalculation($distance, $fuel_consumption, $fuel_price, $results);
  }

  /**
   * Logs the calculation for audit purposes.
   *
   * @param float $distance
   *   The distance travelled.
   * @param float $fuel_consumption
   *   The fuel consumption.
   * @param float $fuel_price
   *   The fuel price.
   * @param array $results
   *   The calculation results.
   */
  protected function logCalculation(float $distance, float $fuel_consumption, float $fuel_price, array $results): void {
    $ip = $this->requestStack->getCurrentRequest()->getClientIp();
    $username = $this->currentUser->isAuthenticated() ? $this->currentUser->getAccountName() : 'Anonymous';

    $this->loggerFactory->get('fuel_calculator')->info('Calculation by @user (@ip): distance=@distance, consumption=@consumption, price=@price, spent=@spent, cost=@cost', [
      '@user' => $username,
      '@ip' => $ip,
      '@distance' => $distance,
      '@consumption' => $fuel_consumption,
      '@price' => $fuel_price,
      '@spent' => $results['fuel_spent'],
      '@cost' => $results['fuel_cost'],
    ]);
  }

  /**
   * Resets the form to default values.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function resetForm(array &$form, FormStateInterface $form_state): void {
    $form_state->setRedirect('<current>');
  }

}
