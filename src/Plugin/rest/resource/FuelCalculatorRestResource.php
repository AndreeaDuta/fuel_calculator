<?php

namespace Drupal\fuel_calculator\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\fuel_calculator\Service\FuelCalculatorService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Provides a REST resource for fuel calculation.
 *
 * @RestResource(
 *   id = "fuel_calculator_rest_resource",
 *   label = @Translation("Fuel Calculator REST Resource"),
 *   uri_paths = {
 *     "create" = "/api/fuel-calculate"
 *   }
 * )
 */
class FuelCalculatorRestResource extends ResourceBase {

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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a FuelCalculatorRestResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\fuel_calculator\Service\FuelCalculatorService $calculator
   *   The fuel calculator service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    FuelCalculatorService $calculator,
    RequestStack $request_stack,
    AccountProxyInterface $current_user,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->calculator = $calculator;
    $this->requestStack = $request_stack;
    $this->currentUser = $current_user;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('fuel_calculator.calculator'),
      $container->get('request_stack'),
      $container->get('current_user'),
      $container->get('logger.factory')
    );
  }

  /**
   * Responds to POST requests.
   *
   * @param array $data
   *   The request data.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function post($data): JsonResponse {
    // Authentication check.
    if (!$this->currentUser->isAuthenticated()) {
      throw new HttpException(401, 'Authentication required.');
    }

    // Validate input data.
    $validation_result = $this->validateInput($data);
    if ($validation_result !== TRUE) {
      return new JsonResponse(['message' => $validation_result], 400);
    }

    $results = $this->calculator->calculate(
      (float) $data['distance'],
      (float) $data['fuel_consumption'],
      (float) $data['fuel_price']
    );

    $this->logCalculation($data, $results);

    return new JsonResponse($results);
  }

  /**
   * Validates the input data.
   *
   * @param array $data
   *   The input data to validate.
   *
   * @return string|true
   *   TRUE if valid, error message if invalid.
   */
  protected function validateInput(array $data): string|true {
    $required_fields = ['distance', 'fuel_consumption', 'fuel_price'];

    // Check for required fields.
    foreach ($required_fields as $field) {
      if (!isset($data[$field])) {
        return "Missing required field: $field";
      }
      if (!is_numeric($data[$field])) {
        return "Field $field must be numeric";
      }
    }

    // Validate ranges.
    $validations = [
      'distance' => ['min' => 0.1, 'max' => 10000, 'label' => 'Distance'],
      'fuel_consumption' => ['min' => 0.1, 'max' => 100, 'label' => 'Fuel consumption'],
      'fuel_price' => ['min' => 0.01, 'max' => 10, 'label' => 'Fuel price'],
    ];

    foreach ($validations as $field => $rules) {
      $value = (float) $data[$field];
      if ($value < $rules['min'] || $value > $rules['max']) {
        return "{$rules['label']} must be between {$rules['min']} and {$rules['max']}";
      }
    }

    return TRUE;
  }

  /**
   * Logs the calculation for audit purposes.
   *
   * @param array $input_data
   *   The input data.
   * @param array $results
   *   The calculation results.
   */
  protected function logCalculation(array $input_data, array $results): void {
    $ip = $this->requestStack->getCurrentRequest()->getClientIp();
    $username = $this->currentUser->isAuthenticated() ? $this->currentUser->getAccountName() : 'Anonymous';

    $this->loggerFactory->get('fuel_calculator')->info('REST calculation by @user (@ip): distance=@distance, consumption=@consumption, price=@price, spent=@spent, cost=@cost', [
      '@user' => $username,
      '@ip' => $ip,
      '@distance' => $input_data['distance'],
      '@consumption' => $input_data['fuel_consumption'],
      '@price' => $input_data['fuel_price'],
      '@spent' => $results['fuel_spent'],
      '@cost' => $results['fuel_cost'],
    ]);
  }

}
