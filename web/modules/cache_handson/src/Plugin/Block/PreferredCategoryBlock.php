<?php

declare(strict_types=1);

namespace Drupal\cache_handson\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\Entity\User;

/**
 * Provides a block to display articles from the user’s preferred category.
 *
 * @Block(
 *   id = "preferred_category_block",
 *   admin_label = @Translation("Preferred Category Block"),
 *   category = @Translation("Custom"),
 * )
 */
class PreferredCategoryBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user) {
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
  public function build(): array {
    $uid = $this->currentUser->id();
    $user = User::load($uid);

    // Ensure 'field_preferred_category' exists and has a value
    $preferred_category = $user->get('field_preferred_category')->entity ?? NULL;
    if (!$preferred_category) {
      return ['#markup' => $this->t('No preferred category selected.')];
    }

    // Query nodes (articles) that have the preferred category
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('status', 1)
      ->condition('type', 'article')
      ->condition('field_category', $preferred_category->id())
      ->sort('created', 'DESC')
      ->range(0, 3)
      ->accessCheck(FALSE);

    $nids = $query->execute();
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

    if (empty($nodes)) {
      return ['#markup' => $this->t('No articles found in your preferred category.')];
    }

    $items = [];
    $cache_tags = [];
    foreach ($nodes as $node) {
      $items[] = [
        '#markup' => $node->toLink()->toString(),
      ];
      $cache_tags[] = 'node:' . $node->id();
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#cache' => [
        'tags' => $cache_tags,
        'contexts' => ['preferred_taxonomy'],
      ],
    ];
  }
}