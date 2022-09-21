<?php

declare(strict_types=1);

namespace outiserver\worldbackup\tasks;

use outiserver\worldbackup\language\LanguageManager;
use outiserver\worldbackup\WorldBackup;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class WorldCopyBackupAsyncTask extends AsyncTask
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

        WorldBackup::getInstance()->getLogger()->info(LanguageManager::getInstance()->getLanguage(Server::getInstance()->getLanguage()->getLang())->translateString("system.worldbackup.copy.start"));
    }

    public function onRun(): void
    {
        $this->backupPath = "{$this->backupFolderPath}backups/" . date("Y-m-d-H-i-s");
        mkdir($this->backupPath);
        $this->copy($this->worldPath, $this->backupPath);
    }

    public function onCompletion(): void
    {
        WorldBackup::getInstance()->getLogger()->info(LanguageManager::getInstance()->getLanguage(Server::getInstance()->getLanguage()->getLang())->translateString("system.worldbackup.copy.success"));
        WorldBackup::getInstance()->getLogger()->info(LanguageManager::getInstance()->getLanguage(Server::getInstance()->getLanguage()->getLang())->translateString("system.worldbackup.copy.path", [$this->backupPath]));
    }

    private function copy($dir, $new_dir)
    {
        $dir = rtrim($dir, '/') . '/';
        $new_dir = rtrim($new_dir, '/') . '/';

        if (is_dir($dir)) {
            if (!is_dir($new_dir)) {
                mkdir($new_dir);
                chmod($new_dir, 0777);
            }

            if ($handle = opendir($dir)) {
                while (false !== ($file = readdir($handle))) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }
                    if (is_dir($dir . $file)) {
                        $this->copy($dir . $file, $new_dir . $file);
                    } else {
                        copy($dir . $file, $new_dir . $file);
                    }
                }
                closedir($handle);
            }
        }
    }
}