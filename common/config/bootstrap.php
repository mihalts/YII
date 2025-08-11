<?php
Yii::setAlias("@common",   dirname(__DIR__));                         // /var/www/html/common
Yii::setAlias("@frontend", dirname(__DIR__, 2) . "/frontend");        // /var/www/html/frontend
Yii::setAlias("@backend",  dirname(__DIR__, 2) . "/backend");         // /var/www/html/backend
Yii::setAlias("@console",  dirname(__DIR__, 2) . "/console");         // /var/www/html/console
Yii::setAlias("@storage",  dirname(__DIR__, 2) . "/storage");         // /var/www/html/storage
