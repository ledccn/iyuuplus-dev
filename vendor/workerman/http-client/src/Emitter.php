<?php
namespace Workerman\Http;
class Emitter
{
    /**
     * [event=>[[listener1, once?], [listener2,once?], ..], ..]
     */
    protected $_eventListenerMap = array();

    /**
     * On.
     *
     * @param $event_name
     * @param $listener
     * @return $this
     */
    public function on($event_name, $listener)
    {
        $this->emit('newListener', $event_name, $listener);
        $this->_eventListenerMap[$event_name][] = array($listener, 0);
        return $this;
    }

    /**
     * Once.
     *
     * @param $event_name
     * @param $listener
     * @return $this
     */
    public function once($event_name, $listener)
    {
        $this->_eventListenerMap[$event_name][] = array($listener, 1);
        return $this;
    }

    /**
     * RemoveListener.
     *
     * @param $event_name
     * @param $listener
     * @return $this
     */
    public function removeListener($event_name, $listener)
    {
        if(!isset($this->_eventListenerMap[$event_name]))
        {
            return $this;
        }
        foreach($this->_eventListenerMap[$event_name] as $key=>$item)
        {
            if($item[0] === $listener)
            {
                $this->emit('removeListener', $event_name, $listener);
                unset($this->_eventListenerMap[$event_name][$key]);
            }
        }
        if(empty($this->_eventListenerMap[$event_name]))
        {
            unset($this->_eventListenerMap[$event_name]);
        }
        return $this;
    }

    /**
     * RemoveAllListeners.
     *
     * @param null $event_name
     * @return $this
     */
    public function removeAllListeners($event_name = null)
    {
        $this->emit('removeListener', $event_name);
        if(null === $event_name)
        {
            $this->_eventListenerMap = array();
            return $this;
        }
        unset($this->_eventListenerMap[$event_name]);
        return $this;
    }

    /**
     *
     * Listeners.
     *
     * @param $event_name
     * @return array
     */
    public function listeners($event_name)
    {
        if(empty($this->_eventListenerMap[$event_name]))
        {
            return array();
        }
        $listeners = array();
        foreach($this->_eventListenerMap[$event_name] as $item)
        {
            $listeners[] = $item[0];
        }
        return $listeners;
    }

    /**
     * Emit.
     *
     * @param null $event_name
     * @return bool
     */
    public function emit($event_name = null)
    {
        if(empty($event_name) || empty($this->_eventListenerMap[$event_name]))
        {
            return false;
        }
        foreach($this->_eventListenerMap[$event_name] as $key=>$item)
        {
             $args = func_get_args();
             unset($args[0]);
             call_user_func_array($item[0], $args);
             // once ?
             if($item[1])
             {
                 unset($this->_eventListenerMap[$event_name][$key]);
                 if(empty($this->_eventListenerMap[$event_name]))
                 {
                     unset($this->_eventListenerMap[$event_name]);
                 }
             }
        }
        return true;
    }

}
