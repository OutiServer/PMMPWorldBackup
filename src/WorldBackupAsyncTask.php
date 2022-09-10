<?php

declare(strict_types=1);

namespace outiserver\worldbackup;

use pocketmine\scheduler\AsyncTask;
use ZipArchive;

class WorldBackupAsyncTask extends AsyncTask
{
    /**
     * バックアップ先のフォルダ
     *
     * @var string
     */
    private string $backupFolder;

    /**
     * Worldのフォルダ
     * @var string
     */
    private string $worldPath;

    private string $backupPath;

    public function __construct(string $backupFolder, string $worldPath)
    {
        $this->backupFolder = $backupFolder;
        $this->worldPath = $worldPath;
        $this->backupPath = "";

        WorldBackup::getInstance()->getLogger()->info("ワールドバックアップを作成しています...");
    }

    public function onRun(): void
    {
        $this->backupPath = "{$this->backupFolder}backups/" . date("Y-m-d-H-i-s");
        mkdir($this->backupPath);
        $this->copy($this->worldPath, $this->backupPath);
    }

    public function onCompletion(): void
    {
        WorldBackup::getInstance()->getLogger()->info("ワールドバックアップ(コピー)は正常に作成されたはずです");
        WorldBackup::getInstance()->getLogger()->info("コピー先: $this->backupPath");
    }

    private function copy($dir, $new_dir)
    {
        $dir     = rtrim($dir, '/').'/';
        $new_dir = rtrim($new_dir, '/').'/';

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
                    if (is_dir($dir.$file)) {
                        $this->copy($dir.$file, $new_dir.$file);
                    } else {
                        @copy($dir.$file, $new_dir.$file);
                    }
                }
                closedir($handle);
            }
        }
    }
}