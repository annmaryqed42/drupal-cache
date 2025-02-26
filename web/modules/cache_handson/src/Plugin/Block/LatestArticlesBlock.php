<?php

declare(strict_types=1);

namespace Drupal\cache_handson\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\core\session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides 'Latest Articles' block displaying the title of last 3 articles created.
 *
 * @Block(
 *   id = "latest_articles_block",
 *   admin_label = @Translation("Latest Articles"),
 * )
 */
class LatestArticlesBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\core\session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;
  
  /**
   * Constructs a new LatestArticlesBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the block.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration,
   $plugin_id,
   $plugin_definition, 
   EntityTypeManagerInterface $entity_type_manager,
   AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array{
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
    -> condition('status', 1)
    -> condition('type', 'article')
    -> sort('created', 'DESC')
    -> range(0, 3)
    ->accessCheck(FALSE);

    $nids = $query->execute();
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

    foreach ($nodes as $node) {
      $items[] = ['#markup' => $node->label()];
      $cache_tags[] = 'node:' . $node->id();
    }

    $user_email = $this->currentUser->getEmail() ?? $this->t('No email available');
    $items[] = ['#markup' => $this->t('Your email: @email', ['@email' => $user_email])];

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#cache' => [
        'tags' => $cache_tags,
        'contexts' => ['user'],
      ]
    ];
  }
}