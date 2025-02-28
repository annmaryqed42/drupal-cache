<?php

namespace Drupal\cache_handson\Cache;

use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Defines a custom cache context based on the preferred taxonomy terms.
 */
class PreferredTaxonomyCacheContext implements CacheContextInterface {

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a PreferredTaxonomyCacheContext object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel(): string {
    return t('Preferred Taxonomy Cache Context');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext(): string {
    $uid = $this->currentUser->id();
    $user = $this->entityTypeManager->getStorage('user')->load($uid);

    if ($user && $user->hasField('field_preferred_category')) {
      $preferences = $user->get('field_preferred_category')->getValue();

      // Concatenate taxonomy term IDs as a unique cache identifier.
      return implode('_', array_map(function ($item) {
        return $item['target_id'];
      }, $preferences));
    }

    // Default cache context for users without preferences.
    return 'none';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}