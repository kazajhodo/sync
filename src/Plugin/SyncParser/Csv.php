<?php

namespace Drupal\sync\Plugin\SyncParser;

use Drupal\sync\Plugin\SyncFetcherInterface;
use Drupal\sync\Plugin\SyncParserBase;

/**
 * Plugin implementation of the 'csv' sync parser.
 *
 * @SyncParser(
 *   id = "csv",
 *   label = @Translation("CSV"),
 * )
 */
class Csv extends SyncParserBase {

  /**
   * {@inheritdoc}
   */
  protected function defaultSettings() {
    return [
      'header' => TRUE,
      'delimiter' => ',',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected function parse($data, SyncFetcherInterface $fetcher) {
    $use_header = $this->configuration['header'];
    $rows = array_filter(explode(PHP_EOL, $data));
    $csv = array_map(function ($row) {
      return str_getcsv($row, $this->configuration['delimiter']);
    }, $rows);
    $page_size = $fetcher->getPageSize();
    if ($page_size) {
      $fetcher->setPageEnabled(TRUE);
      if ($use_header) {
        $header = array_shift($csv);
      }
      $max = $page_size * $fetcher->getPageNumber();
      $min = $max - $page_size;
      $csv = array_slice($csv, $min, $page_size);
      if ($use_header) {
        $csv = array_merge([$header], $csv);
      }
    }
    if ($use_header) {
      $found = [];
      foreach ($csv[0] as $key => $value) {
        if (isset($found[$value])) {
          $csv[0][$key] .= ' ' . $found[$value];
        }
        $found[$value] = isset($found[$value]) ? $found[$value] + 1 : 1;
      }
      array_walk($csv, function (&$a) use ($csv) {
        $a = array_slice($a, 0, count($csv[0]));
        if (count($csv[0]) === count($a)) {
          $a = array_combine($csv[0], $a);
        }
      });
      array_shift($csv);
    }
    return $csv;
  }

}
