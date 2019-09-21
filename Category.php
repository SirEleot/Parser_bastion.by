<?php
    /**
     * undocumented class
     */
    class Category
    {
        public $id;
        public $name;
        public $parent;

        public function __construct(string $name, int $id, int $parent = 0) {
            $this->id = $id;
            $this->name = $name;
            $this->parent = $parent;
        }
    }
    
?>
