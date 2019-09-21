<?php
    require_once('BastionParse.php');

    $parser = new BastionParse();
    //$parser->Parse();
    //$parser->DownloadImages();
    //$parser->SortItemsByCategories();
    $parser->ShowItemCategories(true, true);
    
    echo "Complited!";
?>
