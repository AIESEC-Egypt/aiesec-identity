<?php
namespace AIESEC\Identity;

/**
 * Interface Plugin
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @package AIESEC\Identity
 * @version 0.2
 */
interface Plugin
{
    function onLogin($authProvider, $current_person);

    function onFirstLogin($authProvider, $current_person);

    function onListSites($scopes, $personId);

    function onBeforeLogout($personId);

    function onAfterLogout();

    function onCurrentPerson($person);
}