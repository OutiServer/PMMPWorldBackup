<?php

declare(strict_types=1);

namespace outiserver\worldbackup;

use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;

class WorldBackup extends PluginBase
{
    use SingletonTrait;

    private Config $config;

    protected function onLoad(): void
    {
        self::setInstance($this);
    }

    protected function onEnable(): void
    {
        if (!file_exists( "{$this->getDataFolder()}backups/")) {
            mkdir("{$this->getDataFolder()}backups/");
        }

        $this->saveResource("config.yml");
        $this->config = new Config("{$this->getDataFolder()}config.yml", Config::YAML);

        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(
            function (): void {
                $this->getServer()->getAsyncPool()->submitTask(new WorldBackupAsyncTask($this->getDataFolder(), "{$this->getServer()->getDataPath()}worlds/"));
            }),
            $this->getConfig()->get("interval", 60) * 60 * 20);

        $this->getServer()->getCommandMap()->registerAll($this->getName(), [
            new WorldBackupCommand($this, "worldbackup", "ワールドバックアップを作成する", "/worldbackup", []),
        ]);
    }

    public function getConfig(): Config
    {
        return $this->config;
    }
}