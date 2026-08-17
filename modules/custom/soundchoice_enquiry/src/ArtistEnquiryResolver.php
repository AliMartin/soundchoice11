<?php

declare(strict_types=1);

namespace Drupal\soundchoice_enquiry;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeInterface;

/**
 * Resolves and validates Artists used by the shared enquiry form.
 */
final class ArtistEnquiryResolver {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   * Loads a viewable Artist node from a node ID.
   */
  public function resolve(mixed $artist_id): ?NodeInterface {
    if (!is_scalar($artist_id)) {
      return NULL;
    }

    $artist_id = filter_var(
      (string) $artist_id,
      FILTER_VALIDATE_INT,
      ['options' => ['min_range' => 1]],
    );

    if (!$artist_id) {
      return NULL;
    }

    $node = $this->entityTypeManager
      ->getStorage('node')
      ->load((int) $artist_id);

    if (
      !$node instanceof NodeInterface ||
      $node->bundle() !== 'artist' ||
      !$node->access('view', $this->currentUser)
    ) {
      return NULL;
    }

    return $node;
  }

  /**
   * Returns the Artist membership term label in lowercase.
   */
  public function getMembership(NodeInterface $artist): string {
    if (
      !$artist->hasField('field_membership') ||
      $artist->get('field_membership')->isEmpty()
    ) {
      return '';
    }

    $term = $artist->get('field_membership')->entity;

    return $term ? mb_strtolower(trim($term->label())) : '';
  }

  /**
   * Returns the Artist contact email field value.
   */
  public function getContactEmail(NodeInterface $artist): string {
    if (
      !$artist->hasField('field_contact_email') ||
      $artist->get('field_contact_email')->isEmpty()
    ) {
      return '';
    }

    return trim((string) $artist->get('field_contact_email')->value);
  }

}
