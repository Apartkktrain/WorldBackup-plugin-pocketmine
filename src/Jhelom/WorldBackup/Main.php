<?php
declare(strict_types=1);

namespace Jhelom\WorldBackup;


use Exception;
use Jhelom\Core\Logging;
use Jhelom\Core\PluginBaseEx;
use Jhelom\Core\PluginUpdater;
use Jhelom\WorldBackup\Commands\WorldBackupCommand;
use Jhelom\WorldBackup\Services\WorldBackupService;
use pocketmine\event\level\LevelLoadEvent;
use pocketmine\event\Listener;

/**
 * Class Main
 * @package Jhelom\WorldBackup
 */
class Main extends PluginBaseEx implements Listener
{
    private const PLUGIN_DOWNLOAD_URL_DOMAIN = 'https://github.com';
    private const PLUGIN_DOWNLOAD_URL_PATH = '/jhelom/WorldBackup-plugin-pocketmine/releases';

    /** @var Main */
    static private $instance;
    private $task;

    /**
     * @return Main
     */
    static public function getInstance(): Main
    {
        return Main::$instance;
    }

    public function onLoad()
    {
        $this->getLogger()->debug('onLoad');

        parent::onLoad();
        Main::$instance = $this;

        // config

        $supportedLanguages = ['jpn', 'eng'];

        foreach ($supportedLanguages as $lang) {
            $this->saveResource('messages.' . $lang . '.yml', true);

        }

        // messages

        $message_file = $this->getDataFolder() . 'messages.' . $this->getServer()->getLanguage()->getLang() . '.yml';

        if (!is_file($message_file)) {
            $message_file = $this->getDataFolder() . 'messages.eng.yml';
        }

        Messages::load($message_file);

        // restore

        $service = WorldBackupService::getInstance();

        try {
            $service->executeRestorePlan();
            $service->autoBackup();
        } catch (Exception $e) {
            Logging::logException($e);
        }
    }

    public function onEnable()
    {
        $this->getLogger()->debug('onEnable');
        parent::onEnable();

        $updater = new PluginUpdater($this, self::PLUGIN_DOWNLOAD_URL_DOMAIN, self::PLUGIN_DOWNLOAD_URL_PATH);
        $updater->update();

        // task

        $this->task = new TimerTask();
        $interval = 1200 * 60 * 12; // 1 minutes * 60 * 12 = 12 hour

        // TODO: scheduler
        if (method_exists($this, 'getScheduler')) {
            $this->getScheduler()->scheduleDelayedRepeatingTask($this->task, $interval, $interval);
        } else {
            $this->getLogger()->debug('Scheduler = Server');
            /** @noinspection PhpUndefinedMethodInspection */
            $this->getServer()->getScheduler()->scheduleDelayedRepeatingTask($this->task, $interval, $interval);
        }

        // register

        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        // setup commands

        $this->setupCommands([
            new WorldBackupCommand($this)
        ]);
    }

    public function onLevelLoad(LevelLoadEvent $event)
    {
        $this->getLogger()->debug('LevelLoadEvent: ' . $event->getLevel()->getName());
    }
}

