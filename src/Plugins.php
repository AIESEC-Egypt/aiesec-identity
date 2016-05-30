<?php
namespace AIESEC\Identity;

/**
 * Class Plugins
 * Plugin Runner
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @package AIESEC\Identity
 * @version 0.2
 */
class Plugins
{
    private static $_plugins;

    public static function init($plugins) {
        self::$_plugins = array();
        foreach($plugins as $p) {
            $p = '\\AIESEC\\Identity\\Plugin\\' . $p;
            $class = new $p();
            if($class instanceof Plugin) self::$_plugins[] = $class;
        }
    }

    public static function onLogin($authProvider, $current_person)
    {
        if(is_array(self::$_plugins)) {
            $ok = true;
            foreach(self::$_plugins as $p) {
                if($ok) {
                    $ok = $p->onLogin($authProvider, $current_person);
                } else {
                    break;
                }
            }
            return $ok;
        } else {
            trigger_error("Plugin Runner is not instantiated", E_USER_ERROR);
            return false;
        }
    }

    public static function onFirstLogin($authProvider, $current_person)
    {
        if(is_array(self::$_plugins)) {
            $ok = true;
            foreach(self::$_plugins as $p) {
                if($ok) {
                    $ok = $p->onFirstLogin($authProvider, $current_person);
                } else {
                    break;
                }
            }
            return $ok;
        } else {
            trigger_error("Plugin Runner is not instantiated", E_USER_ERROR);
            return false;
        }
    }

    public static function onListSites($scopes, $personId)
    {
        if(is_array(self::$_plugins)) {
            $sites = array();
            foreach(self::$_plugins as $p) {
                $s = $p->onListSites($scopes, $personId);
                foreach ($s as $new) {
                    $skip = false;
                    foreach ($sites as $site) {
                        if(!$skip) {
                            if($new[0] == $site[0] && $new[1] == $site[1]) $skip = true;
                        } else {
                            break;
                        }
                    }
                    if(!$skip) $sites[] = $new;
                }
            }
            return $sites;
        } else {
            trigger_error("Plugin Runner is not instantiated", E_USER_ERROR);
            return false;
        }
    }

    public static function onBeforeLogout($personId)
    {
        if(is_array(self::$_plugins)) {
            $ok = true;
            foreach(self::$_plugins as $p) {
                if($ok) {
                    $ok = $p->onBeforeLogout($personId);
                } else {
                    break;
                }
            }
            return $ok;
        } else {
            trigger_error("Plugin Runner is not instantiated", E_USER_ERROR);
            return false;
        }
    }

    public static function onAfterLogout()
    {
        if(is_array(self::$_plugins)) {
            $ok = true;
            foreach(self::$_plugins as $p) {
                if($ok) {
                    $ok = $p->onAfterLogout();
                } else {
                    break;
                }
            }
            return $ok;
        } else {
            trigger_error("Plugin Runner is not instantiated", E_USER_ERROR);
            return false;
        }
    }

    public static function onCurrentPerson($person)
    {
        if(is_array(self::$_plugins)) {
            foreach(self::$_plugins as $p) {
                $tmp = $p->onCurrentPerson($person);
                if($tmp !== FALSE) {
                    $person = $tmp;
                } else {
                    return false;
                }
            }
            return $person;
        } else {
            trigger_error("Plugin Runner is not instantiated", E_USER_ERROR);
            return false;
        }
    }
}