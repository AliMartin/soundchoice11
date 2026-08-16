<?php

declare(strict_types=1);

namespace Drupal\soundchoice_search_log\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Sound Choice search log report.
 */
final class SearchLogController extends ControllerBase {

  /**
   * Constructs the controller.
   */
  public function __construct(
    private readonly Connection $database,
    private readonly DateFormatterInterface $dateFormatter,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('database'),
      $container->get('date.formatter'),
    );
  }

  /**
   * Builds the search report.
   */
  public function report(): array {
    $build = [];

    $total_searches = (int) $this->database
      ->select('soundchoice_search_log', 's')
      ->countQuery()
      ->execute()
      ->fetchField();

    $zero_count_query = $this->database
      ->select('soundchoice_search_log', 's');
    $zero_count_query->condition('result_count', 0);

    $zero_result_searches = (int) $zero_count_query
      ->countQuery()
      ->execute()
      ->fetchField();

    $build['summary'] = [
      '#type' => 'container',
      'intro' => [
        '#markup' => '<p>This report contains anonymous search phrases submitted through the Artist Search page. Searches made by users with the <em>access site reports</em> permission are excluded.</p>',
      ],
      'stats' => [
        '#theme' => 'item_list',
        '#items' => [
          $this->t('Total searches logged: @count', ['@count' => $total_searches]),
          $this->t('Zero-result searches: @count', ['@count' => $zero_result_searches]),
        ],
      ],
      'actions' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['soundchoice-search-log-actions']],
        'clear' => [
          '#type' => 'link',
          '#title' => $this->t('Clear search log'),
          '#url' => \Drupal\Core\Url::fromRoute('soundchoice_search_log.clear'),
          '#attributes' => [
            'class' => ['button', 'button--danger'],
          ],
        ],
      ],
    ];

    $popular_query = $this->database->select('soundchoice_search_log', 's');
    $popular_query->addField('s', 'search_term');
    $popular_query->addExpression('COUNT(*)', 'search_count');
    $popular_query->addExpression('AVG(result_count)', 'average_results');
    $popular_query->groupBy('search_term');
    $popular_query->orderBy('search_count', 'DESC');
    $popular_query->orderBy('search_term', 'ASC');
    $popular_query->range(0, 25);

    $popular_rows = [];
    foreach ($popular_query->execute() as $row) {
      $popular_rows[] = [
        $row->search_term,
        (int) $row->search_count,
        number_format((float) $row->average_results, 1),
      ];
    }

    $build['popular'] = [
      '#type' => 'details',
      '#title' => $this->t('Most common searches'),
      '#open' => TRUE,
      'table' => [
        '#type' => 'table',
        '#header' => [
          $this->t('Search phrase'),
          $this->t('Searches'),
          $this->t('Average results'),
        ],
        '#rows' => $popular_rows,
        '#empty' => $this->t('No searches have been logged yet.'),
      ],
    ];

    $zero_query = $this->database->select('soundchoice_search_log', 's');
    $zero_query->addField('s', 'search_term');
    $zero_query->addExpression('COUNT(*)', 'search_count');
    $zero_query->addExpression('MAX(created)', 'last_searched');
    $zero_query->condition('result_count', 0);
    $zero_query->groupBy('search_term');
    $zero_query->orderBy('search_count', 'DESC');
    $zero_query->orderBy('last_searched', 'DESC');
    $zero_query->range(0, 25);

    $zero_rows = [];
    foreach ($zero_query->execute() as $row) {
      $zero_rows[] = [
        $row->search_term,
        (int) $row->search_count,
        $this->dateFormatter->format((int) $row->last_searched, 'short'),
      ];
    }

    $build['zero'] = [
      '#type' => 'details',
      '#title' => $this->t('Zero-result searches'),
      '#open' => TRUE,
      'table' => [
        '#type' => 'table',
        '#header' => [
          $this->t('Search phrase'),
          $this->t('Searches'),
          $this->t('Last searched'),
        ],
        '#rows' => $zero_rows,
        '#empty' => $this->t('No zero-result searches have been logged.'),
      ],
    ];

    $recent_query = $this->database->select('soundchoice_search_log', 's');
    $recent_query->fields('s', ['search_term', 'result_count', 'created']);
    $recent_query->orderBy('created', 'DESC');
    $recent_query->range(0, 100);

    $recent_rows = [];
    foreach ($recent_query->execute() as $row) {
      $recent_rows[] = [
        $row->search_term,
        (int) $row->result_count,
        $this->dateFormatter->format((int) $row->created, 'short'),
      ];
    }

    $build['recent'] = [
      '#type' => 'details',
      '#title' => $this->t('Recent searches'),
      '#open' => TRUE,
      'table' => [
        '#type' => 'table',
        '#header' => [
          $this->t('Search phrase'),
          $this->t('Results'),
          $this->t('Date'),
        ],
        '#rows' => $recent_rows,
        '#empty' => $this->t('No searches have been logged yet.'),
      ],
    ];

    return $build;
  }

}
