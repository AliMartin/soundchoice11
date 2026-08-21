<?php

declare(strict_types=1);

namespace Drupal\soundchoice_reviews\EventSubscriber;

use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Blocks direct canonical requests for Review nodes.
 */
final class ReviewCanonicalSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => ['onRequest', 28],
    ];
  }

  /**
   * Returns a 404 for Review node canonical pages.
   */
  public function onRequest(RequestEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }

    $request = $event->getRequest();

    if ($request->attributes->get('_route') !== 'entity.node.canonical') {
      return;
    }

    $node = $request->attributes->get('node');

    if ($node instanceof NodeInterface && $node->bundle() === 'review') {
      throw new NotFoundHttpException();
    }
  }

}
