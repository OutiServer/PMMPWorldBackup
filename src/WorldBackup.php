<?php

declare(strict_types=1);

namespace outiserver\worldbackup;

use JackMD\ConfigUpdater\ConfigUpdater;
use outiserver\worldbackup\commands\WorldBackupCommand;
use outiserver\worldbackup\language\LanguageManager;
use outiserver\worldbackup\tasks\WorldCopyBackupAsyncTask;
use outiserver\worldbackup\tasks\WorldZipBackupAsyncTask;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;

class WorldBackup extends PluginBase
{
    use SingletonTrait;

    private Config $config;

    private LanguageManager $languageManager;

    protected function onLoad(): void
    {
        self::setInstance($this);
    }

    protected function onEnable(): void
    {
        $this->saveResource("config.yml");
        $this->config = new Config("{$this->getDataFolder()}config.yml", Config::YAML);

        ConfigUpdater::checkUpdate($this, $this->config, "version", 1);

        $this->languageManager = new LanguageManager("{$this->getFile()}resources/lang");

        if (!file_exists("{$this->getDataFolder()}backups/")) {
            mkdir("{$this->getDataFolder()}backups/");
        }

        if ($this->config->get("mode") !== "zip" and $this->config->get("mode") !== "copy") {
            $this->config->set("mode", "copy");
            $this->config->save();
            $this->getLogger()->warning($this->languageManager->getLanguage($this->getServer()->getLanguage()->getLang())->translateString("system.config_mode_error"));
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
            new WorldBackupCommand($this, "worldbackup", "Create World Backup", "/worldbackup [zip|copy]", []),
        ]);
    }

    public function getConfig(): Config
    {
        return $this->config;
    }
}