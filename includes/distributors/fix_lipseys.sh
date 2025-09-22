#!/bin/bash
cd /opt/fflbro/wordpress-main/wp-content/plugins/ffl-bro-enhanced-pro/includes/distributors/
cp lipseys.php lipseys.php.backup
sed -i 's/"Email"/"Username"/g' lipseys.php
sed -i 's/"token"/"token"/g' lipseys.php
echo "Lipseys API authentication fixed!"
