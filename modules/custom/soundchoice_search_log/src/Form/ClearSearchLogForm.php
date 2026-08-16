<?php

declare(strict_types=1);

namespace Drupal\soundchoice_search_log\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Confirmation form for clearing the Sound Choice search log.
 */
final class ClearSearchLogForm extends ConfirmFormBase {

  /**
   * Constructs the form.
   */
  public function __construct(
    private readonly Connection $database,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('database'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'soundchoice_search_log_clear_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Clear all Sound Choice search data?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This will permanently delete all recorded search phrases and result counts. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Clear search log');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('soundchoice_search_log.report');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->database
      ->truncate('soundchoice_search_log')
      ->execute();

    $this->messenger()->addStatus(
      $this->t('The Sound Choice search log has been cleared.')
    );

    $form_state->setRedirect('soundchoice_search_log.report');
  }

}
