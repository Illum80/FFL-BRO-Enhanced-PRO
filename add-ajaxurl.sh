#!/bin/bash
# Add ajaxurl definition after line 703
sed -i '704i\        var ajaxurl = "'"'"' . admin_url('"'"'admin-ajax.php'"'"') . '"'"'";' ffl-bro-enhanced-pro.php
echo "Added ajaxurl definition"
