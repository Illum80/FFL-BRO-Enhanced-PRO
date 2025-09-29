#!/bin/bash
# Fix the malformed JavaScript lines

# Replace line 709
sed -i "709s|.*|        var ajaxurl = \"' . admin_url('admin-ajax.php') . '\";\"|" ffl-bro-enhanced-pro.php

# Replace line 745 if it exists
sed -i "745s|.*|        var ajaxurl = \"' . admin_url('admin-ajax.php') . '\";\"|" ffl-bro-enhanced-pro.php

echo "Fixed JavaScript syntax errors"
