<?php

namespace Drupal\access_module\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\menu_test\Access\AccessCheck;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an access module block.
 *
 * @Block(
 *   id = "access_module_access_module",
 *   admin_label = @Translation("Access Module"),
 *   category = @Translation("Custom")
 * )
 */
class AccessModuleBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The session_manager service.
   *
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  protected $sessionManager;

  /**
   * Constructs a new AccessModuleBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   *   The session_manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SessionManagerInterface $session_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->sessionManager = $session_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('session_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'foo' => $this->t('Hello world!'),
    ];
  }

  // /**
  //  * {@inheritdoc}
  //  */
  // public function blockForm($form, FormStateInterface $form_state) {
  //   $form['foo'] = [
  //     '#type' => 'textarea',
  //     '#title' => $this->t('Foo'),
  //     '#default_value' => $this->configuration['foo'],
  //   ];
  //   return $form;
  // }

  // /**
  //  * {@inheritdoc}
  //  */
  // public function blockSubmit($form, FormStateInterface $form_state) {
  //   $this->configuration['foo'] = $form_state->getValue('foo');
  // }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Fetch the top active students.
    $students = $this->getTopActiveStudents();

    // Build the render array for the block content.
    $content = [
      '#theme' => 'access_module_top_active_students',
      '#students' => $students,
    ];

    return $content;
  }

  protected function getTopActiveStudents() {
    // Query logged-in users with the "student" role.
    $query = \Drupal::entityQuery('user')
      ->condition('status', 1)
      ->condition('roles', 'student')
      ->sort('access', 'DESC')
      ->range(0, 5);
      
    $query->accessCheck(TRUE);

    $uids = $query->execute();

    // Load user entities.
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadMultiple($uids);

    // Extract relevant data and return the result.
    $result = [];
    foreach ($users as $user) {
      $result[] = [
        'name' => $user->getDisplayName(),
      ];
    }

    return $result;
  }

}
