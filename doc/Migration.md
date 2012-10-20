## How to move OCAL from Aiki Based system to new one on dev server

Dump database from old server. You can use dump script from private directory

    $ mysqldump -u openclipart -p<PASSWORD> openclipart | gzip > openclipart.org-`date +%F`.sql.gz

copy that file to dev server, you can use scp to do that

    $ scp <USER>@openclipart.org/srv/www/openclipart.org/private/

run the script on dev server and run recreate_tags.php

    $ mysql -u<USER> -p<PASS> openclipart_dev < openclipart.org-<DATE>.sql.gz

NOTE: maybe you need to call

    $ zcat openclipart.org-<DATE>.sql.gz | mysql -u<USER> -p<PASS> openclipart_dev

    $ php -f recreate_tags.php

then you can rsync svg files

    sudo rsync -avz --exclude '*png' -e ssh <USER>@openclipart.org:~/openclipart/people/ people
