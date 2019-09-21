<?php    
    require_once('Item.php');
    require_once('Category.php');

    class BastionParse
    {
        //settings

        //url
        private $home_url= "https://bastion.by";
        private $catalog_uri = "/catalog";

        //file names
        private $fileName_itemLinks = "./itemLinks.json";
        private $fileName_badLinks = "./badLinks.json";
        private $fileName_items = "./items.json";
        private $fileName_categoryList = "./categoryList.json";

        //system
        private $catalogPagesCount = 128;
        private $maxExecutionTime = 1200;
        private $debug = false;
        private $update;

        //props
        private $itemLinks = array();
        private $items = array();
        private $badLinks;
        private $categoryList = array();

        function __construct($update = false)
        {
            ini_set ( 'max_execution_time', $this->maxExecutionTime);
            $this->update = $update;
            $strig_badLinks = @file_get_contents($this->fileName_badLinks);
            $this->badLinks = $strig_badLinks ? json_decode($strig_badLinks) : array();
        }

        public function ShowItemCategories($inFile = false, $json = false)
        {
            if(count($this->categoryList) == 0)
                $this->SortItemsByCategories();
            $string = "";
            $id = 0;
            $list = array(new Category("Base", $id));
            foreach ($this->categoryList as $key => $value) {
                if(!$inFile){
                    echo "<p>$key</p>";
                    foreach ($value as $k => $v) {
                        echo "<p style=\"padding-left:30px;\">$k</p>";
                    }
                }else{
                    $string .= $key."\n";
                    $list[] = new Category($key, ++$id);
                    $parent = $id;            
                    foreach ($value as $k => $v) {
                        $string .= "\t$k\n";
                        $list[] = new Category($k, ++$id, $parent);
                    }             
                }
            }
            if($inFile) {
                if($json)
                    file_put_contents("categories.json", json_encode($list));
                else 
                    file_put_contents("categories.txt", $string);
            }                
        }

        public function SortItemsByCategories()
        {
            $jsonString = @file_get_contents($this->fileName_categoryList);
            if(!$jsonString || $this->update || $this->debug){
                if(count($this->items) == 0)
                    $this->Parse();
                foreach ($this->items as $key => $value) {
                    if(!array_key_exists($value->category, $this->categoryList))
                        $this->categoryList[$value->category] = array();
                    $category = &$this->categoryList[$value->category];
                    if(!array_key_exists($value->subcategory, $category))
                        $category[$value->subcategory] = array();
                    $category[$value->subcategory][] = $key;
                    unset($category);
                }
                file_put_contents($this->fileName_categoryList,  json_encode($this->categoryList));
            }else $this->categoryList = json_decode($jsonString);
        }

        public function Parse()
        {
            $this->ParseItemLinks();
            if(count($this->itemLinks) > 0) $this->GetItems();
            
            echo "<h2>Найденые ссылки: ".count($this->itemLinks)." шт.</h2>";              
            echo "<h2>Найденные товары: ".count($this->items)." товаров</h2>";           
            if(count($this->badLinks) > 0) echo "<h2>Нерабочие ссылки: ".count($this->badLinks)." шт</h2>";
        }

        public function DownloadImages()
        {
            if(count($this->items) == 0)
                $this->Parse();
            print_r(count($this->items));
            foreach ($this->items as $key => $value) {
                print_r($value->img_url);
                file_put_contents("./img/image_$key.jpg", fopen($value->img_url, 'r'));
                if($this->debug)
                    if($key > 3) break; 
            }
        }

        private function GetItems()
        {
            $jsonString = @file_get_contents($this->fileName_items);
            if(!$jsonString || $this->update || $this->debug){
                $this->ParseItems();
                if(count($this->items) > 0)
                    file_put_contents($this->fileName_items,  json_encode($this->items));          
            } else {
                $this->items = json_decode($jsonString);
            }
        }

        private function ParseItemLinks()
        {            
            $jsonString = @file_get_contents($this->fileName_itemLinks);
            if(!$jsonString || $this->update || $this->debug){
                $this->GetAllItemsLinks($this->home_url.$this->catalog_uri);
                if($this->debug) $this->catalogPagesCount = 3;
                for ($i=2; $i <= $this->catalogPagesCount; $i++) { 
                    $this->GetAllItemsLinks($this->home_url.$this->catalog_uri."?PAGEN_2=$i");
                }
                if(count($this->itemLinks) > 0)
                    file_put_contents($this->fileName_itemLinks,  json_encode($this->itemLinks));   
            }else $this->itemLinks = json_decode($jsonString);
        }
       
        private function GetAllItemsLinks($url)
        {            
            $preg = "/href=\"\/detail\/.+\/\"/i";
            $page = file_get_contents($url);
            if ( preg_match_all($preg, $page, $m )) {
                foreach( $m[0] as $url ){
                    $url = str_replace("href=","",$url);
                    $url = str_replace("\"","",$url);
                    $url = trim($url);
                    $url = $this->home_url.$url;
                    if(!in_array($url, $this->itemLinks))
                        $this->itemLinks[] = $url;
                }
            }
        }
        
        private function ParseItems()
        {
            $count = count($this->itemLinks);
            if($this->debug) $count = 3;
            if($count > 0){
                $this->badLinks = array();
                for ($i=0; $i < $count; $i++) { 
                    $page = @file_get_contents($this->itemLinks[$i]);
                    if(!$page) {
                        $this->badLinks[] = $this->itemLinks[$i];
                        continue;
                    }
                    $props = $this->GetItemProps($page);
                    if(count($props) > 0)
                        $this->items[] = new Item( $props, $this->GetItemImageLink($page), $this->GetItemPrice($page));                       
                    else continue;                    
                }
                if(count($this->badLinks) > 0)
                    file_put_contents($this->fileName_badLinks,  json_encode($this->badLinks));
                
            }else echo "<h2>Не найдено ссылок на товар.</h2>";            
        }

        private function GetItemProps(string $string) : array
        {
            $preg = '/(<span\s+itemprop=\"name\">)(.*)(<\/span>)/i';                    
            $props = array();
            preg_match_all($preg, $string, $props);
            return preg_match_all($preg, $string, $props)? $props[2] : [];
        }

        private function GetItemImageLink(string $string)
        {
            $preg = '/<a\s+class=\"b-slider__item.*<img\s+src=\"(.+)\"\salt/i';                   
            $image = array();
            $img = preg_match_all($preg, $string, $image) ? $image[1][0] : "";
            return $img == "" ? $img : $this->home_url.$img;
        }

        private function GetItemPrice(string $string)
        {
            $preg = '/data-price-show=\"(.*)\"\s/i';                   
            $price = array();
            preg_match($preg, $string, $price);
            return  preg_match($preg, $string, $price) ? $price[1] : -1.0;
        }
    }    
?>