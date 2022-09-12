<?php

declare(strict_types=1);

namespace outiserver\worldbackup;

use outiserver\worldbackup\commands\WorldCopyBackupCommand;
use outiserver\worldbackup\commands\WorldZipBackupCommand;
use outiserver\worldbackup\tasks\WorldCopyBackupAsyncTask;
use outiserver\worldbackup\tasks\WorldZipBackupAsyncTask;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;

class WorldBackup extends PluginBase
{
    use SingletonTrait;

    public const CONFIG_VERSION = "1.0.0";

    private Config $config;

    protected function onLoad(): void
    {
        self::setInstance($this);
    }

    protected function onEnable(): void
    {
        if (@file_exists("{$this->getDataFolder()}config.yml")) {
            $config = new Config("{$this->getDataFolder()}config.yml", Config::YAML);
            if ($config->get("version") !== self::CONFIG_VERSION) {
                rename("{$this->getDataFolder()}config.yml", "{$this->getDataFolder()}config.yml.{$config->get("version")}");
                $this->getLogger()->warning("config.yml バージョンが違うため、上書きしました");
                $this->getLogger()->warning("前バージョンのconfig.ymlは{$this->getDataFolder()}config.yml.{$config->get("version")}にあります");
            }
        }

        if (!file_exists("{$this->getDataFolder()}backups/")) {
            mkdir("{$this->getDataFolder()}backups/");
        }

        $this->saveResource("config.yml");
        $this->config = new Config("{$this->getDataFolder()}config.yml", Config::YAML);

        if ($this->config->get("mode") !== "zip" and $this->config->get("mode") !== "copy") {
            $this->config->set("mode", "copy");
            $this->config->save();
            $this->getLogger()->warning("Configのmodeの設定値が不正だったため、上書きしました");
        }

        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(
            function (): void {
                switch ($this->config->get("mode")) {
                    case "zip":
                        $this->getServer()->getAsyncPool()->submitTask(new WorldZipBackupAsyncTask($this->getDataFolder(), "{$this->getServer()->getDataPath()}worlds/"));
                        break;
                    case "copy":
                        $this->getServer()->getAsyncPool()->submitTask(new WorldCopyBackupAsyncTask($this->getDataFolder(), "{$this->getServer()->getDataPath()}worlds/"));
                        break;
                }
            }),
            $this->getConfig()->get("interval", 60) * 60 * 20);

        $this->getServer()->getCommandMap()->registerAll($this->getName(), [
            new WorldZipBackupCommand($this, "worldzipbackup", "ワールドバックアップをZIP形式で作成する", "/worldzipbackup", []),
            new WorldCopyBackupCommand($this, "worldcopybackup", "ワールドバックアップをコピー形式で作成する", "/worldcopybackup", []),
        ]);
    }

    public function getConfig(): Config
    {
        return $this->config;
    }
}