<?php

namespace outiserver\worldbackup\commands;

use outiserver\worldbackup\tasks\WorldCopyBackupAsyncTask;
use outiserver\worldbackup\tasks\WorldZipBackupAsyncTask;
use outiserver\worldbackup\WorldBackup;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use pocketmine\Server;

class WorldCopyBackupCommand extends Command implements PluginOwned
{
    private Plugin $plugin;

    public function __construct(Plugin $plugin, string $name, Translatable|string $description = "", Translatable|string|null $usageMessage = null, array $aliases = [])
    {
        parent::__construct($name, $description, $usageMessage, $aliases);

        $this->plugin = $plugin;
        $this->setPermission("worldbackup.command");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        Server::getInstance()->getAsyncPool()->submitTask(new WorldCopyBackupAsyncTask(WorldBackup::getInstance()->getDataFolder(), Server::getInstance()->getDataPath() . "worlds/"));
    }

    public function getOwningPlugin(): Plugin
    {
        return $this->plugin;
    }
}