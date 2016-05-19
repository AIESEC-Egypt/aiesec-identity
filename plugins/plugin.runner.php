<?php
require_once(dirname(__FILE__) . '/plugin.interface.php');

/**
 * Class PluginRunner
 */
class PluginRunner implements Plugin {

    /**
     * @var array
     */
    private $_plugins = array();

    function __construct($plugins) {
        foreach($plugins as $p) {
            require_once(dirname(__FILE__) . '/' . $p . '.plugin.php');
            $class = new $p();
            if($class instanceof Plugin) $this->_plugins[] = $class;
        }
    }

    function onLogin($user, $mysql)
    {
        $ok = true;
        foreach($this->_plugins as $p) {
            if($ok) {
                $ok = $p->onLogin($user, $mysql);
            }
        }
        return $ok;
    }

    function onFirstLogin($user, $mysql)
    {
        $ok = true;
        foreach($this->_plugins as $p) {
            if($ok) {
                $ok = $p->onFirstLogin($user, $mysql);
            }
        }
        return $ok;
    }

    function onListSites($scopes, $personId, $mysql)
    {
        $sites = array();
        foreach($this->_plugins as $p) {
            $s = $p->onListSites($scopes, $personId, $mysql);
            foreach ($s as $new) {
                $skip = false;
                foreach ($sites as $site) {
                    if(!$skip) {
                        if($new[0] == $site[0] && $new[1] == $site[1]) $skip = true;
                    }
                }
                if(!$skip) $sites[] = $new;
            }
        }
        return $sites;
    }

    function onBeforeLogout($accessToken, $personId)
    {
        $ok = true;
        foreach($this->_plugins as $p) {
            if($ok) {
                $ok = $p->onBeforeLogout($accessToken, $personId);
            }
        }
        return $ok;
    }

    function onAfterLogout()
    {
        $ok = true;
        foreach($this->_plugins as $p) {
            if($ok) {
                $ok = $p->onAfterLogout();
            }
        }
        return $ok;
    }

    function onCurrentPerson($person) {
        foreach($this->_plugins as $p) {
            $tmp = $p->onCurrentPerson($person);
            if($tmp !== FALSE) {
                $person = $tmp;
            } else {
                return false;
            }
        }
        return $person;
    }
}