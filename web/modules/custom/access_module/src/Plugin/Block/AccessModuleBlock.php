<?php

namespace Drupal\access_module\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
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
   * Stores the connection object.
   *
   * @var object
   */
  protected $connection;

  protected $entity_manager;

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
   * @param \Drupal\Core\Entity\EntityTypeManager $entity 
   *   The entity type manager service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The connection service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SessionManagerInterface $session_manager, Connection $connection, EntityTypeManager $entity) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->sessionManager = $session_manager;
    $this->connection = $connection;
    $this->entity_manager = $entity;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('session_manager'),
      $container->get('database'),
      $container->get('entity_type.manager'),
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

  /**
   * Queries the top 5 students based on access.
   *
   * @return array
   *   Returns an array of students.
   */
  protected function getTopActiveStudents() {
    // Query logged-in users with the "student" role.
    $query = $this->connection->select('users_field_data', 'u');
    $query->fields('u', ['uid']);
    $query->condition('status', 1);
    // Join with user__roles table.
    $query->join('user__roles', 'ur', 'u.uid = ur.entity_id'); 
    $query->condition('ur.roles_target_id', 'students');
    $query->orderBy('access', 'DESC');
    $query->range(0, 5);
      
    $uids = $query->execute()->fetchCol();

    // Load user entities.
    $users = $this->entity_manager->getStorage('user')->loadMultiple($uids);

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
