<?php

spl_autoload_register(function ($class){
    require_once $class.".php";
});
class Event extends EventGenerator
{
    public function triger()
    {
        echo "Event<br>";
    }
}

class Observer1 implements Observer{
    public function update()
    {
        // TODO: Implement update() method.
        echo "逻辑1<br>";
    }
}

class Observer2 implements Observer{
    public function update()
    {
        // TODO: Implement update() method.
        echo "逻辑2<br>";
    }
}

$event = new Event();
$event->addObserver(new Observer1());
$event->addObserver(new Observer2());
$event->triger();
$event->notify();
$event->getName();