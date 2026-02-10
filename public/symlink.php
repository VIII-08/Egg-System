<?php
// 1. Where are the actual images? (Go up one folder, then into storage)
$target = '../storage/app/public'; 

// 2. What do we call the shortcut?
$link = 'storage'; 

// 3. Create the link
if(file_exists($link)) {
    echo "The 'storage' shortcut ALREADY exists.";
} else {
    symlink($target, $link);
    echo "Success! The 'storage' shortcut has been CREATED.";
}
?>