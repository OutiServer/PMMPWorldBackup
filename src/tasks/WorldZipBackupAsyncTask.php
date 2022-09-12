<?php

declare(strict_types=1);

namespace outiserver\worldbackup\tasks;

use outiserver\worldbackup\WorldBackup;
use pocketmine\scheduler\AsyncTask;
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

        WorldBackup::getInstance()->getLogger()->info("ワールドバックアップを作成しています...");
    }

    public function onRun(): void
    {
        $zip = new ZipArchive();
        $this->backupPath = "{$this->backupFolderPath}backups/" . date("Y-m-d-H-i-s") . ".worldbackup.zip";
        if ($zip->open($this->backupPath, ZipArchive::CREATE) === true) {
            $this->zipSub($zip, $this->worldPath);
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
            WorldBackup::getInstance()->getLogger()->error("ワールドバックアップの作成に失敗しました");
        } else {
            WorldBackup::getInstance()->getLogger()->info("ワールドバックアップ(ZIP)を作成しました、作成先: $this->backupPath");
        }
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

    private function zipSub(ZipArchive $zip, string $path, string $parentPath = '')
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
                    $this->zipSub($zip, $fullpath, $localPath . '/');
                }
            }
        }
        closedir($dir);
    }
}