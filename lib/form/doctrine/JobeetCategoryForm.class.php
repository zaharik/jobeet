<?php

/**
 * JobeetCategory form.
 *
 * @package    jobeet
 * @subpackage form
 * @author     Your name here
 * @version    SVN: $Id: sfDoctrineFormTemplate.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class JobeetCategoryForm extends BaseJobeetCategoryForm
{
  public function configure()
  {
    unset(
      $this['jobeet_affiliates_list'],
      $this['created_at'], $this['updated_at']
    );
 
    $this->embedI18n(array('en', 'fr', 'ru'));
    $this->widgetSchema->setLabel('en', 'English');
    $this->widgetSchema->setLabel('fr', 'French');
    $this->widgetSchema->setLabel('ru', 'Russian');
  }
}
