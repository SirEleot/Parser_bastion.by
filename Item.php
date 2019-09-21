<?php
    class Item 
    {
        public  $name;
        public  $category;
        public  $subcategory;
        public  $produces;
        public  $price;
        public  $img_url;

        function __construct(array $props, string $img_url, string $price)
        {
            for ($i=1; $i < count($props); $i++) { 
                switch ($i) {
                    case 1:
                        $this->category = $props[$i];
                        break;
                    case 2:
                        $this->subcategory = $props[$i];
                        break;
                    case 3:
                        $this->produces = $props[$i];
                        break;
                    case 4:
                        $this->name = $props[$i];
                        break;
                    
                    default:
                        break;
                }
            }
            $this->img_url = $img_url;
            $this->price = $price;
        }
    }
    
?>
