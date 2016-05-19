<?php

interface Plugin
{
    function onLogin($user, $mysql);

    function onFirstLogin($user, $mysql);

    function onListSites($scopes, $personId, $mysql);

    function onBeforeLogout($accessToken, $personId);

    function onAfterLogout();

    function onCurrentPerson($person);
}