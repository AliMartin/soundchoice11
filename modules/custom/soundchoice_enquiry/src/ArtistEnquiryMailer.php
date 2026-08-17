<?php

declare(strict_types=1);

namespace Drupal\soundchoice_enquiry;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Sends routed Artist enquiry emails.
 */
final class ArtistEnquiryMailer {

  private const SOUND_CHOICE_EMAIL = 'hello@soundchoice.co.uk';

  public function __construct(
    private readonly MailManagerInterface $mailManager,
    private readonly LanguageManagerInterface $languageManager,
    private readonly EmailValidatorInterface $emailValidator,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Sends an Artist enquiry using membership-based routing.
   */
  public function send(
    WebformSubmissionInterface $submission,
    NodeInterface $artist,
    string $membership,
    string $artist_contact_email,
  ): void {
    $data = $submission->getData();

    $reply_to = trim((string) ($data['email'] ?? ''));
    if (!$this->emailValidator->isValid($reply_to)) {
      $reply_to = NULL;
    }

    $artist_contact_email = trim($artist_contact_email);
    $valid_artist_email = $this->emailValidator->isValid($artist_contact_email);

    // Premium enquiries are always managed by Sound Choice.
    // Pioneer/Partner enquiries go direct to the Artist when a valid contact
    // email is present, with Sound Choice copied in.
    if (
      in_array($membership, ['pioneer', 'partner'], TRUE) &&
      $valid_artist_email
    ) {
      $to = $artist_contact_email;
      $cc = self::SOUND_CHOICE_EMAIL;
      $route = $membership . '_direct';
    }
    else {
      $to = self::SOUND_CHOICE_EMAIL;
      $cc = '';
      $route = $membership === 'premium' ? 'premium' : 'soundchoice_fallback';
    }

    $params = [
      'subject' => 'New Sound Choice enquiry for ' . $artist->label(),
      'body' => $this->buildBody($submission, $artist, $membership),
      'cc' => $cc,
      'from' => self::SOUND_CHOICE_EMAIL,
    ];

    $langcode = $this->languageManager
      ->getDefaultLanguage()
      ->getId();

    $result = $this->mailManager->mail(
      'soundchoice_enquiry',
      'artist_enquiry',
      $to,
      $langcode,
      $params,
      $reply_to,
      TRUE,
    );

    $logger = $this->loggerFactory->get('soundchoice_enquiry');

    if (!empty($result['result'])) {
      $logger->info(
        'Artist enquiry @sid for @artist routed via @route to @to.',
        [
          '@sid' => $submission->id(),
          '@artist' => $artist->label(),
          '@route' => $route,
          '@to' => $to,
        ],
      );
    }
    else {
      $logger->error(
        'Failed to send Artist enquiry @sid for @artist to @to.',
        [
          '@sid' => $submission->id(),
          '@artist' => $artist->label(),
          '@to' => $to,
        ],
      );
    }
  }

  /**
   * Builds the plain-text enquiry email body.
   */
  private function buildBody(
    WebformSubmissionInterface $submission,
    NodeInterface $artist,
    string $membership,
  ): string {
    $data = $submission->getData();

    $lines = [
      'A new Artist enquiry has been submitted through Sound Choice.',
      '',
      'Artist: ' . $artist->label(),
      'Artist node ID: ' . $artist->id(),
      'Membership: ' . ($membership !== '' ? ucfirst($membership) : 'Not set'),
      '',
      'Name: ' . $this->value($data, 'name'),
      'Email: ' . $this->value($data, 'email'),
      'Telephone: ' . $this->value($data, 'telephone'),
      'Date of event: ' . $this->value($data, 'event_date'),
      'Event location: ' . $this->value($data, 'location'),
      '',
      'Message:',
      $this->value($data, 'message'),
      '',
      'Submission ID: ' . $submission->id(),
    ];

    return implode("\n", $lines);
  }

  /**
   * Safely gets a scalar Webform value.
   */
  private function value(array $data, string $key): string {
    $value = $data[$key] ?? '';

    if (!is_scalar($value) || trim((string) $value) === '') {
      return 'Not provided';
    }

    return trim((string) $value);
  }

}
