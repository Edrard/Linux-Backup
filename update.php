<?php


exec("git checkout origin/master src");
exec("git checkout origin/master backup.sh");
exec("git checkout origin/master composer.json");
exec("git checkout origin/master run_sync.php");
exec("git checkout origin/master update.php");
exec("composer update");
