<?php

declare(strict_types=1);

namespace outiserver\worldbackup;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use pocketmine\Server;
use pocketmine\world\World;

class WorldBackupCommand extends Command implements PluginOwned
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
        Server::getInstance()->getAsyncPool()->submitTask(new ZipWorldBackupAsyncTask(WorldBackup::getInstance()->getDataFolder(), Server::getInstance()->getDataPath() . "worlds/"));
    }

    public function getOwningPlugin(): Plugin
    {
        return $this->plugin;
    }
}