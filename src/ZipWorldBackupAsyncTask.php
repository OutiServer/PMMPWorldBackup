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
                $this->setResult(-1);
            }
            else {
                $this->setResult(0);
            }
        }
        else {
            $this->setResult(-2);
        }
    }

    public function onCompletion(): void
    {
        if ($this->getResult() === 0) {
            WorldBackup::getInstance()->getLogger()->info("ワールドバックアップを作成しました");
        }
        elseif ($this->getResult() === -1) {
            WorldBackup::getInstance()->getLogger()->error("ワールドバックアップの作成に失敗しました、ワールドデータが書き込み中ではありませんか？");
        }
        elseif ($this->getResult() === -2) {
            WorldBackup::getInstance()->getLogger()->error("ワールドバックアップの作成に失敗しました、backupフォルダに書き込み権限があることを確認してください");
        }
        else {
            WorldBackup::getInstance()->getLogger()->error("ワールドバックアップの作成に失敗しました、不明なエラー");
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