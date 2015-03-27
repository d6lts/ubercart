<?php

/**
 * @file
 * Contains \Drupal\uc_tax\Controller\TaxController.
 */

namespace Drupal\uc_tax\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Controller routines for tax routes.
 */
class TaxController extends ControllerBase {

  /**
   * Displays a list of tax rates.
   */
  public function overview() {
    $header = array(t('Name'), t('Rate'), t('Taxed products'), t('Taxed product types'), t('Taxed line items'), t('Weight'), array('data' => t('Operations'), 'colspan' => 4));

    $rows = array();
    foreach (uc_tax_rate_load() as $rate_id => $rate) {
      $rows[] = array(
        SafeMarkup::checkPlain($rate->name),
        $rate->rate * 100 . '%',
        $rate->shippable ? t('Shippable products') : t('Any product'),
        implode(', ', $rate->taxed_product_types),
        implode(', ', $rate->taxed_line_items),
        $rate->weight,
        $this->l(t('edit'), new Url('uc_tax.rate_edit', ['tax_rate' => $rate_id])),
        // l(t('conditions'), 'admin/store/settings/taxes/manage/uc_tax_' . $rate_id),
        $this->l(t('clone'), new Url('uc_tax.rate_clone', ['tax_rate' => $rate_id])),
        $this->l(t('delete'), new Url('uc_tax.rate_delete', ['tax_rate' => $rate_id])),
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
    $rate = uc_tax_rate_load($tax_rate);
    $name = $rate->name;

    // Tweak the name and unset the rate ID.
    $rate->name = t('Copy of !name', ['!name' => $rate->name]);
    $rate->id = NULL;

    // Save the new rate without clearing the Rules cache.
    $rate = uc_tax_rate_save($rate, FALSE);

    // Clone the associated conditions as well.
    // if ($conditions = rules_config_load('uc_tax_' . $rate_id)) {
    //   $conditions->id = NULL;
    //   $conditions->name = '';
    //   $conditions->save('uc_tax_' . $rate->id);
    // }

    // entity_flush_caches();

    // Display a message and redirect back to the overview.
    drupal_set_message(t('Tax rate %name cloned.', ['%name' => $name]));

    return $this->redirect('uc_tax.overview');
  }

}
