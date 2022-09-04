<?php

declare(strict_types=1);

namespace outiserver\worldbackup;

use pocketmine\scheduler\AsyncTask;
use ZipArchive;

class ZipWorldBackupAsyncTask extends AsyncTask
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

    public function __construct(string $backupFolder, string $worldPath)
    {
        $this->backupFolder = $backupFolder;
        $this->worldPath = $worldPath;

        WorldBackup::getInstance()->getLogger()->info("ワールドバックアップを作成しています...");
    }

    public function onRun(): void
    {
        $zip = new ZipArchive();
        if ($zip->open("{$this->backupFolder}backups/" . date("Y-m-d-H-i-s") . ".worldbackup.zip", ZipArchive::CREATE) === true) {
            $this->zipSub($zip, $this->worldPath);
            if (!@$zip->close()) {
                $this->setResult(false);
            }
            else {
                $this->setResult(true);
            }
        }
        else {
            $this->setResult(false);
        }
    }

    public function onCompletion(): void
    {
        if ($this->getResult()) {
            WorldBackup::getInstance()->getLogger()->info("ワールドバックアップを作成しました");
        }
        else {
            WorldBackup::getInstance()->getLogger()->error("ワールドバックアップの作成に失敗しました");
        }
    }

    private function zipSub(ZipArchive $zip, string $path, string $parentPath = '')
    {
        $dir = opendir($path);
        while (($entry = readdir($dir)) !== false) {
            // ここでbackupを除外しないと疑似無限ループになるので
            if ($entry == '.' || $entry == '..' || str_ends_with($entry, "backup") || str_ends_with($entry, "backup.zip")) continue;
            else {
                $localPath = "$parentPath$entry";
                $fullpath = "$path/$entry";
                if (is_file($fullpath)) {
                    $zip->addFile($fullpath, "$localPath");
                } elseif (is_dir($fullpath)) {
                    $this->zipSub($zip, $fullpath, $localPath . '/');
                }
            }
        }
        closedir($dir);
    }
}