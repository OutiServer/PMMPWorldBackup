<?php

declare(strict_types=1);

namespace outiserver\worldbackup\commands;

use outiserver\worldbackup\language\LanguageManager;
use outiserver\worldbackup\tasks\WorldCopyBackupAsyncTask;
use outiserver\worldbackup\tasks\WorldZipBackupAsyncTask;
use outiserver\worldbackup\WorldBackup;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use pocketmine\Server;

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
        if (!isset($args[0])) {
            $sender->sendMessage(LanguageManager::getInstance()->getLanguage($sender->getLanguage()->getLang())->translateString("command.worldbackup.error"));
            return;
        }

        switch ($args[0]) {
            case "zip":
                Server::getInstance()->getAsyncPool()->submitTask(new WorldZipBackupAsyncTask(WorldBackup::getInstance()->getDataFolder(), Server::getInstance()->getDataPath() . "worlds/"));
                break;
            case "copy":
                Server::getInstance()->getAsyncPool()->submitTask(new WorldCopyBackupAsyncTask(WorldBackup::getInstance()->getDataFolder(), Server::getInstance()->getDataPath() . "worlds/"));
                break;
            default:
                $sender->sendMessage(LanguageManager::getInstance()->getLanguage($sender->getLanguage()->getLang())->translateString("command.worldbackup.error"));
                break;
        }
    }

    public function getOwningPlugin(): Plugin
    {
        return $this->plugin;
    }
}