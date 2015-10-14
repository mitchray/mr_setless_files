<?php
namespace Concrete\Package\MrSetlessFiles;

use Events;
use Concrete\Core\File\File;
use Concrete\Core\File\Set\Set as FileSet;
use Package;

defined('C5_EXECUTE') or die("Access Denied.");

class Controller extends Package
{
  protected $pkgHandle = 'mr_setless_files';
  protected $appVersionRequired = '5.7';
  protected $pkgVersion = '1.0';

  public function getPackageDescription()
  {
    return t("Automatically put files without a set into their own fileset");
  }

  public function getPackageName()
  {
    return t("Fileset for Setless Files");
  }

  public function install()
  {
    parent::install();
    $setless_fs = FileSet::getByName('Setless');

    if (empty($setless_fs)) {
      $setless_fs = FileSet::createAndGetSet('Setless', 1);
    }
  }

  public function uninstall()
  {
    $setless_fs = FileSet::getByName('Setless');

    if (!empty($setless_fs)) {
      $setless_fs->delete();
    }

    parent::uninstall();
  }

  public function on_start()
  {
    Events::addListener('on_file_add', function($e) {
      $setless_fs = FileSet::getByName('Setless');
      /* @var $e \Concrete\Core\File\Event\FileVersion */
      $event_object = $e->getFileVersionObject();
      $setless_fs->addFileToSet($event_object);
    });

    Events::addListener('on_file_added_to_set', function($e) {
      $setless_fs = FileSet::getByName('Setless');
      /* @var $e \Concrete\Core\File\Event\FileSetFile */
      $event_object = $e->getFileSetFileObject();
      $file = File::getByID($event_object->fID);
      $file_sets = $file->getFileSets();
      $file_set_ids = array();
      foreach ($file_sets as $file_set) {
        $file_set_ids[] = $file_set->fsID;
      }

      // If file is in multiple sets and setless is one of them, remove from setless
      if (count($file_set_ids) >= 2 && in_array($setless_fs->fsID, $file_set_ids)) {
        $setless_fs->removeFileFromSet($file);
      }
    });

    Events::addListener('on_file_removed_from_set', function($e) {
      $setless_fs = FileSet::getByName('Setless');
      /* @var $e \Concrete\Core\File\Event\FileSetFile */
      $event_object = $e->getFileSetFileObject();
      $file = File::getByID($event_object->fID);
      $file_sets = $file->getFileSets();

      // If file is no longer in any sets, add to setless
      if (count($file_sets) == 0) {
        $setless_fs->addFileToSet($file);
      }
    });
  }
}
