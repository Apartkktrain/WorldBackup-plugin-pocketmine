<?php
declare(strict_types=1);

namespace Jhelom\WorldBackup;


use Exception;
use Jhelom\Core\CommandInvoker;
use Jhelom\Core\ISupportedLanguage;
use Jhelom\Core\PluginBaseEx;
use Jhelom\WorldBackup\Commands\WorldBackupCommand;
use Jhelom\WorldBackup\Services\WorldBackupService;
use pocketmine\event\Listener;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

/**
 * Class Main
 * @package Jhelom\WorldBackup
 */
class Main extends PluginBaseEx implements Listener
{
    private const PLUGIN_UPDATE_URL = 'https://github.com/jhelom/WorldBackup-plugin-pocketmine/releases';

    /** @var WorldBackupService */
    private $backupService;

    /** @var Messages */
    private $messages;

    /** @var Task */
    private $task;

    /** @var ICalendar */
    private $calendar;

    public function onLoad()
    {
        parent::onLoad();

        $this->saveDefaultConfig();
        $this->reloadConfig();

        $isDebug = $this->getConfig()->get('debug', false);

        if ($isDebug) {
            $colors = [
                TextFormat::GREEN,
                TextFormat::AQUA,
                TextFormat::BLUE,
                TextFormat::DARK_PURPLE,
                TextFormat::RED
            ];

            foreach ($colors as $color) {
                $this->getLogger()->warning($color . '*** DEBUG MODE ***');
            }
        }

        $this->calendar = $isDebug ? new TestCalendar($this) : new Calendar();
        $this->backupService = new WorldBackupService($this, $this->calendar);

        // messages

        $this->messages = new Messages($this, $this->getAvailableMessagePath());

        // restore

        try {
            $this->backupService->autoBackup();
            $this->backupService->executeRestorePlan();
        } catch (Exception $e) {
            $this->getLogger()->logException($e);
        }
    }

    public function onEnable()
    {
        parent::onEnable();

        // task

        $this->task = new TimerTask($this);
        $interval = $this->calendar->getInterval();

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
    }

    /**
     * @return WorldBackupService
     */
    public function getBackupService(): WorldBackupService
    {
        return $this->backupService;
    }

    /**
     * @return Messages
     */
    public function getMessages(): Messages
    {
        return $this->messages;
    }

    /**
     * @return CommandInvoker[]
     */
    protected function setupCommands(): array
    {
        return [
            new WorldBackupCommand($this)
        ];
    }


    /**
     * @return string[]
     */
    protected function getSupportedLanguages(): array
    {
        return [
            ISupportedLanguage::ENGLISH,
            ISupportedLanguage::JAPANESE
        ];
    }

    /**
     * @return string|null
     */
    protected function getPluginUpdateUrl(): ?string
    {
        return self::PLUGIN_UPDATE_URL;
    }
}

