<?php

/**
 * @file
 * Contains \Drupal\uc_taxes\Controller\TaxesController.
 */

namespace Drupal\uc_taxes\Controller;

use Drupal\Component\Utility\String;
use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for tax routes.
 */
class TaxesController extends ControllerBase {

  /**
   * Displays a list of tax rates.
   */
  public function overview() {
    $header = array(t('Name'), t('Rate'), t('Taxed products'), t('Taxed product types'), t('Taxed line items'), t('Weight'), array('data' => t('Operations'), 'colspan' => 4));

    $rows = array();
    foreach (uc_taxes_rate_load() as $rate_id => $rate) {
      $rows[] = array(
        String::checkPlain($rate->name),
        $rate->rate * 100 . '%',
        $rate->shippable ? t('Shippable products') : t('Any product'),
        implode(', ', $rate->taxed_product_types),
        implode(', ', $rate->taxed_line_items),
        $rate->weight,
        l(t('edit'), 'admin/store/settings/taxes/' . $rate_id . '/edit'),
        // l(t('conditions'), 'admin/store/settings/taxes/manage/uc_taxes_' . $rate_id),
        l(t('clone'), 'admin/store/settings/taxes/' . $rate_id . '/clone'),
        l(t('delete'), 'admin/store/settings/taxes/' . $rate_id . '/delete'),
      );
    }

    $build['taxes'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No rates available.'),
    );

    return $build;
  }

  /**
   * Clones a tax rate.
   */
  public function saveClone($tax_rate) {
    // Load the source rate object.
    $rate = uc_taxes_rate_load($tax_rate);
    $name = $rate->name;

    // Tweak the name and unset the rate ID.
    $rate->name = t('Copy of !name', array('!name' => $rate->name));
    $rate->id = NULL;

    // Save the new rate without clearing the Rules cache.
    $rate = uc_taxes_rate_save($rate, FALSE);

    // Clone the associated conditions as well.
    // if ($conditions = rules_config_load('uc_taxes_' . $rate_id)) {
    //   $conditions->id = NULL;
    //   $conditions->name = '';
    //   $conditions->save('uc_taxes_' . $rate->id);
    // }

    // entity_flush_caches();

    // Display a message and redirect back to the overview.
    drupal_set_message(t('Tax rate %name cloned.', array('%name' => $name)));

    return $this->redirect('uc_taxes.overview');
  }

}
