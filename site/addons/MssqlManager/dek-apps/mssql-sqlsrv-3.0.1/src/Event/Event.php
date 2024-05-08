<?php

namespace DekApps\MssqlProcedure\Event;

/**
 * @example tests see unitest
 * using: add listener on destructor
 * Event::instance()->bind(Event::ON_KILL, function ($that){echo 'That's all folks.';})
 */
class Event implements IEvent
{

    private $events = [];

    /**
     * Call this method to get singleton
     */
    public static function instance(): IEvent
    {
        static $instance = null;
        if ($instance === null) {
            // Late static binding (PHP 5.3+)
            $instance = new static();
        }

        return $instance;
    }

    /**
     * Make constructor private, so nobody can call "new Class".
     */
    private function __construct()
    {
        
    }

    /**
     * Make clone magic method private, so nobody can clone instance.
     */
    private function __clone()
    {
        
    }

    /**
     * Make sleep magic method private, so nobody can serialize instance.
     */
    private function __sleep()
    {
        
    }

    /**
     * Make wakeup magic method private, so nobody can unserialize instance.
     */
    private function __wakeup()
    {
        
    }

    public function trigger(string $event, ...$args): bool
    {
        if (($res = $this->isTriggered($event))) {
            foreach ($this->events[$event] as $clsr) {
                call_user_func_array($clsr, $args);
            }
        }
        return $res;
    }

    private function isTriggered(string $event): bool
    {
        return isset($this->events[$event]) && is_array($this->events[$event]) && count($this->events[$event]) > 0;
    }

    public function bind(string $event, \Closure $clsr): IEvent
    {
        $this->events[$event][] = $clsr;
        return $this;
    }

    public function unBind(string $event): IEvent
    {
        unset($this->events[$event]);
        return $this;
    }

}
