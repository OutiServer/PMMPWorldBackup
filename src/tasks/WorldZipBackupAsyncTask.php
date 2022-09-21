<?php

declare(strict_types=1);

namespace outiserver\worldbackup\tasks;

use outiserver\worldbackup\language\LanguageManager;
use outiserver\worldbackup\WorldBackup;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use ZipArchive;

class WorldZipBackupAsyncTask extends AsyncTask
{
    /**
     * バックアップ先のフォルダ
     *
     * @var string
     */
    private string $backupFolderPath;

    /**
     * Worldのフォルダ
     * @var string
     */
    private string $worldPath;

    private string $backupPath;

    public function __construct(string $backupFolder, string $worldPath)
    {
        $this->backupFolderPath = $backupFolder;
        $this->worldPath = $worldPath;
        $this->backupPath = "";

        WorldBackup::getInstance()->getLogger()->info(LanguageManager::getInstance()->getLanguage(Server::getInstance()->getLanguage()->getLang())->translateString("system.worldbackup.zip.start"));
    }

    public function onRun(): void
    {
        $zip = new ZipArchive();
        $this->backupPath = "{$this->backupFolderPath}backups/" . date("Y-m-d-H-i-s") . ".worldbackup.zip";
        if ($zip->open($this->backupPath, ZipArchive::CREATE) === true) {
            $this->copy($zip, $this->worldPath);
            if (!@$zip->close()) {
                $this->setResult(false);
            } else {
                $this->setResult(true);
            }
        } else {
            $this->setResult(false);
        }
    }

    public function onCompletion(): void
    {
        if (!$this->getResult()) {
            WorldBackup::getInstance()->getLogger()->error(LanguageManager::getInstance()->getLanguage(Server::getInstance()->getLanguage()->getLang())->translateString("system.worldbackup.zip.failed"));
        } else {
            WorldBackup::getInstance()->getLogger()->info(LanguageManager::getInstance()->getLanguage(Server::getInstance()->getLanguage()->getLang())->translateString("system.worldbackup.zip.success"));
            WorldBackup::getInstance()->getLogger()->info(LanguageManager::getInstance()->getLanguage(Server::getInstance()->getLanguage()->getLang())->translateString("system.worldbackup.zip.path", [$this->backupPath]));
        }
    }

    private function copy(ZipArchive $zip, string $path, string $parentPath = '')
    {
        $dir = opendir($path);
        while (($entry = readdir($dir)) !== false) {
            if ($entry == '.' || $entry == '..') continue;
            else {
                $localPath = "$parentPath$entry";
                $fullpath = "$path/$entry";
                if (is_file($fullpath)) {
                    $zip->addFile($fullpath, "$localPath");
                } elseif (is_dir($fullpath)) {
                    $this->copy($zip, $fullpath, $localPath . '/');
                }
            }
        }
        closedir($dir);
    }
}